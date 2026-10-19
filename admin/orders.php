<?php
$pageTitle = 'คำสั่งซื้อ';
require __DIR__ . '/header.php';

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$sql = 'SELECT * FROM orders WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (order_no LIKE ? OR customer_name LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($status !== '') {
    $sql .= ' AND status = ?';
    $params[] = $status;
}
if ($from !== '') {
    $sql .= ' AND DATE(created_at) >= ?';
    $params[] = $from;
}
if ($to !== '') {
    $sql .= ' AND DATE(created_at) <= ?';
    $params[] = $to;
}
$statement = $pdo->prepare($sql . ' ORDER BY id DESC');
$statement->execute($params);
$orders = $statement->fetchAll();

$statuses = order_statuses();
$counts = $pdo->query('SELECT status, COUNT(*) FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$allCount = array_sum($counts);
$hasFilter = $q !== '' || $status !== '' || $from !== '' || $to !== '';
$chipUrl = fn($selectedStatus) => 'orders.php?' . http_build_query(array_filter(
    ['q' => $q, 'status' => $selectedStatus, 'from' => $from, 'to' => $to],
    fn($value) => $value !== ''
));
$paymentMethods = ['cod' => 'เก็บเงินปลายทาง', 'transfer' => 'โอนเงิน'];
// ปุ่มขั้นต่อไปของแต่ละสถานะ (พาไปหน้ารายละเอียดตรงฟอร์มเปลี่ยนสถานะ)
$nextAction = ['Pending' => 'ยืนยันชำระเงิน', 'Paid' => 'เริ่มเตรียมสินค้า', 'Processing' => 'ใส่เลขพัสดุ', 'Shipped' => 'ปิดงาน'];
?>

<section class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <p class="text-sm text-kawaii-ink/60">ค้นหา ตรวจสอบการชำระเงิน และติดตามสถานะคำสั่งซื้อ</p>
    <h2 class="mt-1 text-2xl font-bold">จัดการคำสั่งซื้อ</h2>
  </div>
  <p class="text-sm text-kawaii-ink/60">พบ <span class="font-bold text-kawaii-ink"><?= number_format(count($orders)) ?></span> รายการ</p>
</section>

<nav class="mb-5 flex flex-wrap gap-2" aria-label="กรองตามสถานะ">
  <a href="<?= h($chipUrl('')) ?>"
     class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition <?= $status === '' ? 'border-kawaii-pink bg-kawaii-pink text-white shadow-sm' : 'border-kawaii-pink-light bg-white hover:bg-kawaii-pink-light' ?>">
    ทั้งหมด <span class="rounded-full px-2 py-0.5 text-xs <?= $status === '' ? 'bg-white/20' : 'bg-kawaii-pink-light' ?>"><?= number_format($allCount) ?></span>
  </a>
  <?php foreach ($statuses as $code => $info): ?>
    <a href="<?= h($chipUrl($code)) ?>"
       class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition <?= $status === $code ? 'border-kawaii-pink bg-kawaii-pink text-white shadow-sm' : 'border-kawaii-pink-light bg-white hover:bg-kawaii-pink-light' ?>">
      <?= h($info['label']) ?>
      <span class="rounded-full px-2 py-0.5 text-xs <?= $status === $code ? 'bg-white/20' : 'bg-kawaii-pink-light' ?>"><?= number_format((int)($counts[$code] ?? 0)) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<section class="mb-5 rounded-3xl border border-kawaii-pink-light bg-white p-4 shadow-sm sm:p-5">
  <form method="get" class="flex flex-wrap items-end gap-3">
    <input type="hidden" name="status" value="<?= h($status) ?>">
    <div class="min-w-[220px] flex-1">
      <label for="order-search" class="mb-1 block text-sm font-semibold">ค้นหา</label>
      <input id="order-search" name="q" type="search" value="<?= h($q) ?>" placeholder="เลขที่คำสั่งซื้อ หรือชื่อลูกค้า"
             class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-2.5 outline-none transition focus:border-kawaii-pink focus:ring-2 focus:ring-kawaii-pink/20">
    </div>
    <div>
      <label for="order-from" class="mb-1 block text-sm font-semibold">ตั้งแต่วันที่</label>
      <input id="order-from" name="from" type="date" value="<?= h($from) ?>"
             class="rounded-2xl border border-kawaii-pink-light px-4 py-2.5 outline-none focus:border-kawaii-pink">
    </div>
    <div>
      <label for="order-to" class="mb-1 block text-sm font-semibold">ถึงวันที่</label>
      <input id="order-to" name="to" type="date" value="<?= h($to) ?>"
             class="rounded-2xl border border-kawaii-pink-light px-4 py-2.5 outline-none focus:border-kawaii-pink">
    </div>
    <div class="flex gap-2">
      <button class="rounded-2xl bg-kawaii-pink px-5 py-2.5 text-sm font-bold text-white transition hover:bg-kawaii-pink-deep">ค้นหา</button>
      <?php if ($hasFilter): ?>
        <a href="orders.php" class="rounded-2xl border border-kawaii-pink-light px-5 py-2.5 text-sm font-semibold transition hover:bg-kawaii-cream">ล้างตัวกรอง</a>
      <?php endif; ?>
    </div>
  </form>
</section>

<section class="overflow-hidden rounded-3xl border border-kawaii-pink-light bg-white shadow-sm">
  <div class="border-b border-kawaii-pink-light px-5 py-4 sm:px-6">
    <h3 class="text-lg font-bold">รายการคำสั่งซื้อ</h3>
    <p class="text-sm text-kawaii-ink/60">ตรวจสอบรายละเอียดและจัดการสถานะคำสั่งซื้อ</p>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[950px] text-left text-sm">
      <thead class="bg-kawaii-cream/70 text-kawaii-ink/70">
        <tr>
          <th class="px-5 py-3 font-semibold">เลขที่</th>
          <th class="px-5 py-3 font-semibold">ลูกค้า</th>
          <th class="px-5 py-3 font-semibold">วันที่สั่งซื้อ</th>
          <th class="px-5 py-3 font-semibold">ชำระเงิน</th>
          <th class="px-5 py-3 text-right font-semibold">ยอดรวม</th>
          <th class="px-5 py-3 font-semibold">สถานะ</th>
          <th class="px-5 py-3 text-right font-semibold">ขั้นต่อไป</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-kawaii-pink-light">
        <?php if (!$orders): ?>
          <tr><td colspan="7" class="px-5 py-12 text-center text-kawaii-ink/60">🔍 ไม่พบคำสั่งซื้อตามเงื่อนไขที่เลือก</td></tr>
        <?php else: foreach ($orders as $order): ?>
          <tr class="transition hover:bg-kawaii-cream/40">
            <td class="px-5 py-4">
              <a class="font-num font-semibold tabular-nums text-kawaii-pink-deep hover:underline" href="order-detail.php?id=<?= (int)$order['id'] ?>"><?= h($order['order_no']) ?></a>
            </td>
            <td class="px-5 py-4">
              <span class="block font-semibold"><?= h($order['customer_name']) ?></span>
              <span class="font-num text-xs tabular-nums text-kawaii-ink/60"><?= h($order['phone']) ?></span>
            </td>
            <td class="whitespace-nowrap px-5 py-4 font-num tabular-nums"><?= h(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
            <td class="whitespace-nowrap px-5 py-4"><?= h($paymentMethods[$order['payment_method']] ?? $order['payment_method']) ?></td>
            <td class="whitespace-nowrap px-5 py-4 text-right font-num font-semibold tabular-nums"><?= h(money($order['total'])) ?></td>
            <td class="px-5 py-4">
              <?= status_badge($order['status']) ?>
            </td>
            <td class="px-5 py-4 text-right">
              <?php if (isset($nextAction[$order['status']])): ?>
                <a href="order-detail.php?id=<?= (int)$order['id'] ?>#status-form"
                   class="whitespace-nowrap rounded-xl bg-kawaii-pink px-3 py-2 text-xs font-bold text-white shadow-sm shadow-kawaii-pink/30 transition hover:bg-kawaii-pink-deep"><?= h($nextAction[$order['status']]) ?></a>
              <?php else: ?>
                <a href="order-detail.php?id=<?= (int)$order['id'] ?>"
                   class="whitespace-nowrap text-sm font-semibold text-kawaii-ink/60 hover:text-kawaii-pink-deep hover:underline">ดูรายละเอียด</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
