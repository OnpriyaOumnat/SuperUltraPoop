<?php
require_once __DIR__ . '/../includes/functions.php';
admin_required();

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId || $orderId < 1) {
    redirect('orders.php');
}

$orderStatement = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$orderStatement->execute([$orderId]);
$order = $orderStatement->fetch();
if (!$order) {
    http_response_code(404);
    exit('ไม่พบคำสั่งซื้อ');
}

$statuses = order_statuses();
$nextStatuses = next_order_statuses($order['status']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $newStatus = $_POST['status'] ?? '';
    if (!in_array($newStatus, $nextStatuses, true)) {
        flash('เปลี่ยนสถานะไม่ได้: กรุณาทำตามลำดับสถานะคำสั่งซื้อ');
        redirect("order-detail.php?id=$orderId");
    }

    try {
        $pdo->beginTransaction();
        $lockOrder = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $lockOrder->execute([$orderId]);
        $lockedOrder = $lockOrder->fetch();
        if (!$lockedOrder || !in_array($newStatus, next_order_statuses($lockedOrder['status']), true)) {
            throw new OrderWorkflowException('สถานะคำสั่งซื้อเปลี่ยนแปลงแล้ว กรุณาโหลดหน้าใหม่');
        }

        $lockPayment = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? FOR UPDATE');
        $lockPayment->execute([$orderId]);
        $payment = $lockPayment->fetch();
        if (
            $lockedOrder['payment_method'] === 'transfer'
            && in_array($newStatus, ['Paid', 'Processing', 'Shipped', 'Completed'], true)
            && (
                !$payment
                || (
                    empty($payment['transfer_slip'])
                    && empty($payment['transfer_reference'])
                )
                || round((float)$payment['transfer_amount'], 2) !== round((float)$lockedOrder['total'], 2)
            )
        ) {
            throw new OrderWorkflowException('ยังยืนยันไม่ได้: ต้องมีสลิปโอนเงินและยอดแจ้งโอนต้องตรงกับยอดคำสั่งซื้อ');
        }

        if ($newStatus === 'Cancelled' && !$lockedOrder['stock_restored']) {
            $itemsStatement = $pdo->prepare('SELECT product_id, qty FROM order_items WHERE order_id = ? AND product_id IS NOT NULL');
            $itemsStatement->execute([$orderId]);
            foreach ($itemsStatement->fetchAll() as $item) {
                $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')
                    ->execute([$item['qty'], $item['product_id']]);
            }
            $pdo->prepare('UPDATE orders SET stock_restored = 1 WHERE id = ?')->execute([$orderId]);
        }

        $trackingNumber = trim($_POST['tracking_no'] ?? '') ?: null;
        $pdo->prepare('UPDATE orders SET status = ?, tracking_no = ? WHERE id = ?')
            ->execute([$newStatus, $trackingNumber, $orderId]);
        if ($newStatus === 'Paid') {
            $pdo->prepare("UPDATE payments SET status = 'paid', paid_at = COALESCE(paid_at, NOW()) WHERE order_id = ?")
                ->execute([$orderId]);
        } elseif ($newStatus === 'Cancelled' && (!$payment || $payment['status'] !== 'paid')) {
            $pdo->prepare("UPDATE payments SET status = 'failed' WHERE order_id = ?")->execute([$orderId]);
        }

        $pdo->commit();
        $cancelMessage = $payment && $payment['status'] === 'paid'
            ? 'ยกเลิกคำสั่งซื้อและคืนสต็อกแล้ว โปรดตรวจสอบและดำเนินการคืนเงินแยกต่างหาก'
            : 'ยกเลิกคำสั่งซื้อและคืนสต็อกแล้ว';
        flash($newStatus === 'Cancelled'
            ? $cancelMessage
            : 'อัปเดตสถานะเป็น ' . order_status_label($newStatus) . ' แล้ว');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception instanceof OrderWorkflowException) {
            flash($exception->getMessage());
            redirect("order-detail.php?id=$orderId");
        }
        throw $exception;
    }

    redirect("order-detail.php?id=$orderId");
}

$paymentStatement = $pdo->prepare('SELECT * FROM payments WHERE order_id = ?');
$paymentStatement->execute([$orderId]);
$payment = $paymentStatement->fetch();
$itemsStatement = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStatement->execute([$orderId]);
$items = $itemsStatement->fetchAll();

$flow = ['Pending', 'Paid', 'Processing', 'Shipped', 'Completed'];
$currentStep = array_search($order['status'], $flow, true);
$cancelled = $order['status'] === 'Cancelled';
$isTransfer = $order['payment_method'] === 'transfer';
$paymentStatuses = ['pending' => 'รอชำระ', 'paid' => 'ชำระแล้ว', 'failed' => 'ไม่สำเร็จ'];
$pageTitle = 'รายละเอียดคำสั่งซื้อ';
require __DIR__ . '/header.php';
?>

<section class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <p class="text-sm text-kawaii-ink/60">สั่งเมื่อ <?= h(date('d/m/Y H:i', strtotime($order['created_at']))) ?></p>
    <h2 class="mt-1 text-2xl font-bold">คำสั่งซื้อ <span class="font-num tabular-nums"><?= h($order['order_no']) ?></span></h2>
    <div class="mt-2"><?= status_badge($order['status']) ?></div>
  </div>
  <a href="orders.php" class="text-sm font-bold text-kawaii-pink-deep hover:underline">← กลับรายการคำสั่งซื้อ</a>
</section>

<div class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(300px,0.8fr)]">
  <div class="space-y-5">
    <section id="ship-label" class="rounded-3xl border border-kawaii-pink-light bg-white p-5 shadow-sm sm:p-6">
      <p class="mb-2 hidden text-lg font-bold print:block">คำสั่งซื้อ <?= h($order['order_no']) ?></p>
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-lg font-bold">👤 ลูกค้าและที่อยู่จัดส่ง</h3>
        <button type="button" onclick="window.print()" class="rounded-xl bg-kawaii-pink-light px-3 py-2 text-xs font-bold text-kawaii-pink-deep transition hover:bg-kawaii-pink hover:text-white print:hidden">🖨️ พิมพ์ใบแปะกล่อง</button>
      </div>
      <dl class="grid gap-x-5 gap-y-4 border-t border-kawaii-pink-light pt-4 sm:grid-cols-2">
        <div><dt class="text-xs font-semibold text-kawaii-ink/60">ชื่อลูกค้า</dt><dd class="mt-1 font-semibold"><?= h($order['customer_name']) ?></dd></div>
        <div><dt class="text-xs font-semibold text-kawaii-ink/60">เบอร์โทร</dt><dd class="mt-1 font-num font-semibold tabular-nums"><a class="hover:text-kawaii-pink-deep" href="tel:<?= h($order['phone']) ?>"><?= h($order['phone']) ?></a></dd></div>
        <div class="sm:col-span-2"><dt class="text-xs font-semibold text-kawaii-ink/60">ที่อยู่</dt><dd class="mt-1 leading-relaxed"><?= nl2br(h($order['address'])) ?><br>
          <?php
          // ต่อที่อยู่แบบไทย: ต./อ./จ. (กรุงเทพฯ ใช้ แขวง/เขต) ไม่ใส่ซ้ำถ้าชื่อมีคำนำหน้าอยู่แล้ว
          $bkk = ($order['province'] === 'กรุงเทพมหานคร');
          $pre = fn($text, $prefix) => ($text === '' || preg_match('/^(อำเภอ|อ\.|ตำบล|ต\.|เขต|แขวง|จังหวัด|จ\.)/u', $text)) ? $text : $prefix . $text;
          $addrParts = array_filter([
              $pre($order['subdistrict'] ?? '', $bkk ? 'แขวง' : 'ต.'),
              $pre($order['district'], $bkk ? 'เขต' : 'อ.'),
              $bkk ? $order['province'] : $pre($order['province'], 'จ.'),
              $order['postcode'],
          ], fn($x) => $x !== '');
          echo h(implode(' ', $addrParts));
          ?></dd></div>
        <?php if ($order['tracking_no']): ?>
          <div class="sm:col-span-2"><dt class="text-xs font-semibold text-kawaii-ink/60">เลขพัสดุ</dt><dd class="mt-1 font-num font-semibold tabular-nums"><?= h($order['tracking_no']) ?></dd></div>
        <?php endif; ?>
      </dl>
    </section>

    <section class="overflow-hidden rounded-3xl border border-kawaii-pink-light bg-white shadow-sm">
      <div class="border-b border-kawaii-pink-light px-5 py-4 sm:px-6"><h3 class="text-lg font-bold">🧸 รายการสินค้า</h3></div>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[520px] text-left text-sm">
          <thead class="bg-kawaii-cream/70 text-kawaii-ink/70">
            <tr><th class="px-5 py-3 font-semibold">สินค้า</th><th class="px-5 py-3 text-right font-semibold">ราคา</th><th class="px-5 py-3 text-right font-semibold">จำนวน</th><th class="px-5 py-3 text-right font-semibold">รวม</th></tr>
          </thead>
          <tbody class="divide-y divide-kawaii-pink-light">
            <?php foreach ($items as $item): ?>
              <tr>
                <td class="px-5 py-4"><span class="font-semibold"><?= h($item['product_name']) ?></span><?php if ($item['variant']): ?><span class="mt-1 block text-xs text-kawaii-ink/60">ตัวเลือก: <?= h($item['variant']) ?></span><?php endif; ?></td>
                <td class="whitespace-nowrap px-5 py-4 text-right"><?= h(money($item['price'])) ?></td>
                <td class="whitespace-nowrap px-5 py-4 text-right">× <?= (int)$item['qty'] ?></td>
                <td class="whitespace-nowrap px-5 py-4 text-right font-semibold"><?= h(money($item['price'] * $item['qty'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="space-y-2 border-t border-kawaii-pink-light px-5 py-4 sm:px-6">
        <div class="flex justify-between text-sm"><span class="text-kawaii-ink/60">ยอดสินค้า</span><span><?= h(money($order['subtotal'])) ?></span></div>
        <div class="flex justify-between text-sm"><span class="text-kawaii-ink/60">ค่าจัดส่ง</span><span><?= h(money($order['shipping'])) ?></span></div>
        <div class="flex justify-between border-t border-kawaii-pink-light pt-3 font-bold"><span>ยอดรวมสุทธิ</span><span class="text-kawaii-pink-deep"><?= h(money($order['total'])) ?></span></div>
      </div>
    </section>

    <section class="rounded-3xl border border-kawaii-pink-light bg-white p-5 shadow-sm sm:p-6">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-lg font-bold">💳 การชำระเงิน</h3>
        <?php if ($payment): ?>
          <span class="rounded-full bg-kawaii-pink-light px-3 py-1 text-xs font-semibold"><?= h($paymentStatuses[$payment['status']] ?? $payment['status']) ?></span>
        <?php endif; ?>
      </div>
      <dl class="grid gap-4 border-t border-kawaii-pink-light pt-4 sm:grid-cols-2">
        <div><dt class="text-xs font-semibold text-kawaii-ink/60">วิธีชำระ</dt><dd class="mt-1 font-semibold"><?= $order['payment_method'] === 'cod' ? 'เก็บเงินปลายทาง' : 'โอนเงิน' ?></dd></div>
        <?php if ($isTransfer): ?>
          <div><dt class="text-xs font-semibold text-kawaii-ink/60">ยอดที่แจ้งโอน</dt><dd class="mt-1 font-semibold"><?= $payment && $payment['transfer_amount'] !== null ? h(money($payment['transfer_amount'])) : 'ยังไม่แจ้ง' ?></dd></div>
          <div class="sm:col-span-2">
            <dt class="text-xs font-semibold text-kawaii-ink/60">สลิปโอนเงิน</dt>
            <dd class="mt-2">
              <?php if (!empty($payment['transfer_slip'])): ?>
                <a href="../transfer-slip.php?id=<?= (int)$order['id'] ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 font-semibold text-kawaii-pink-deep hover:underline">
                  <img src="../transfer-slip.php?id=<?= (int)$order['id'] ?>" alt="สลิปโอนเงินคำสั่งซื้อ <?= h($order['order_no']) ?>" class="h-20 w-20 rounded-xl border border-kawaii-pink-light bg-white object-cover">
                  เปิดดูสลิปเต็ม →
                </a>
              <?php elseif (!empty($payment['transfer_reference'])): ?>
                <span class="text-sm text-kawaii-ink/70">รายการเดิม — เลขอ้างอิง: <?= h($payment['transfer_reference']) ?></span>
              <?php else: ?>
                <span class="text-sm text-kawaii-ink/60">ยังไม่ได้แนบสลิป</span>
              <?php endif; ?>
            </dd>
          </div>
          <div><dt class="text-xs font-semibold text-kawaii-ink/60">แจ้งเมื่อ</dt><dd class="mt-1 font-semibold"><?= !empty($payment['transfer_submitted_at']) ? h(date('d/m/Y H:i', strtotime($payment['transfer_submitted_at']))) : '-' ?></dd></div>
        <?php endif; ?>
      </dl>
      <?php if ($isTransfer): ?>
        <p class="mt-4 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800">⚠️ โปรดยืนยันยอดเงินจริงจากบัญชีธนาคารก่อนเปลี่ยนสถานะเป็นชำระเงินแล้ว</p>
      <?php endif; ?>
      <?php if ($cancelled): ?>
        <p class="mt-4 rounded-2xl bg-kawaii-blue/60 px-4 py-3 text-sm">📦 สถานะสต็อก: <?= $order['stock_restored'] ? 'คืนสต็อกแล้ว' : 'ยังไม่ได้คืนสต็อก' ?></p>
        <?php if ($payment && $payment['status'] === 'paid'): ?>
          <p class="mt-3 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">รับชำระแล้วก่อนยกเลิก โปรดตรวจสอบและดำเนินการคืนเงินแยกต่างหาก</p>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>

  <div class="space-y-5">
    <?php if ($nextStatuses): ?>
      <form id="status-form" method="post" class="scroll-mt-24 rounded-3xl border border-kawaii-pink bg-white p-5 shadow-sm sm:p-6">
        <?= csrf_field() ?>
        <h3 class="mb-4 text-lg font-bold">✏️ เปลี่ยนสถานะ</h3>
        <div class="mb-4">
          <label for="status" class="mb-1 block text-sm font-semibold">สถานะถัดไป</label>
          <select id="status" name="status" required class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink">
            <?php foreach ($nextStatuses as $nextStatus): ?>
              <option value="<?= h($nextStatus) ?>"><?= h($statuses[$nextStatus]['label']) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="mt-1 text-xs text-kawaii-ink/60">เปลี่ยนได้เฉพาะตามลำดับขั้นตอนเท่านั้น</p>
        </div>
        <div class="mb-4">
          <label for="tracking_no" class="mb-1 block text-sm font-semibold">เลขพัสดุ</label>
          <input id="tracking_no" name="tracking_no" value="<?= h($order['tracking_no']) ?>" placeholder="กรอกเมื่อจัดส่งแล้ว" class="font-num w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink">
        </div>
        <button class="w-full rounded-2xl bg-kawaii-pink px-5 py-3 font-bold text-white shadow-md shadow-kawaii-pink/30 transition hover:bg-kawaii-pink-deep">บันทึกสถานะ</button>
      </form>
    <?php else: ?>
      <section class="rounded-3xl border border-kawaii-pink-light bg-white p-5 shadow-sm">
        <p class="rounded-2xl bg-kawaii-blue/60 px-4 py-3 text-sm">🔒 คำสั่งซื้อนี้อยู่ในสถานะสิ้นสุดแล้ว ไม่สามารถเปลี่ยนสถานะต่อได้</p>
      </section>
    <?php endif; ?>

    <section class="rounded-3xl border border-kawaii-pink-light bg-white p-5 shadow-sm sm:p-6">
      <h3 class="mb-5 text-lg font-bold">🧭 ขั้นตอนคำสั่งซื้อ</h3>
      <ol class="space-y-4">
        <?php foreach ($flow as $stepIndex => $stepCode):
          $stepClass = $cancelled ? 'future' : ($stepIndex < $currentStep ? 'done' : ($stepIndex === $currentStep ? 'current' : 'future'));
          $stepColor = $stepClass === 'done' ? 'bg-emerald-100 text-emerald-700' : ($stepClass === 'current' ? 'bg-kawaii-pink text-white' : 'bg-gray-100 text-gray-500'); ?>
          <li class="flex gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold <?= $stepColor ?>"><?= $stepClass === 'done' ? '✓' : $stepIndex + 1 ?></span>
            <div><p class="font-semibold"><?= h($statuses[$stepCode]['label']) ?></p><p class="text-xs text-kawaii-ink/60"><?= h($statuses[$stepCode]['description']) ?></p></div>
          </li>
        <?php endforeach; ?>
        <li class="flex gap-3">
          <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold <?= $cancelled ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-500' ?>">✕</span>
          <div><p class="font-semibold"><?= h($statuses['Cancelled']['label']) ?></p><p class="text-xs text-kawaii-ink/60"><?= h($statuses['Cancelled']['description']) ?></p></div>
        </li>
      </ol>
    </section>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
