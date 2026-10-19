<?php
require_once __DIR__ . '/../includes/functions.php';
admin_required();

$memberId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$memberId || $memberId < 1) {
    http_response_code(404);
    exit('ไม่พบสมาชิก');
}

$memberStatement = $pdo->prepare('SELECT id, name, email, phone FROM users WHERE id = ?');
$memberStatement->execute([$memberId]);
$member = $memberStatement->fetch();
if (!$member) {
    http_response_code(404);
    exit('ไม่พบสมาชิก');
}

$orderStatement = $pdo->prepare('SELECT id, order_no, total, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC');
$orderStatement->execute([$memberId]);
$orders = $orderStatement->fetchAll();

$pageTitle = 'รายละเอียดสมาชิก';
require __DIR__ . '/header.php';
?>
<div class="mb-5">
  <a href="customers.php" class="text-sm font-semibold text-kawaii-pink-deep hover:underline">← กลับไปหน้ารายชื่อสมาชิก</a>
  <h2 class="mt-2 text-2xl font-bold">รายละเอียดสมาชิก</h2>
</div>

<section class="mb-6 rounded-3xl border border-kawaii-pink-light bg-white p-5 shadow-sm sm:p-7">
  <div class="mb-5 flex items-center gap-4">
    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-kawaii-pink-light text-2xl">👤</span>
    <div>
      <h3 class="text-xl font-bold"><?= h($member['name']) ?></h3>
      <p class="text-sm text-kawaii-ink/60">สมาชิก #<?= (int)$member['id'] ?></p>
    </div>
  </div>
  <dl class="grid gap-4 border-t border-kawaii-pink-light pt-5 sm:grid-cols-2">
    <div>
      <dt class="text-xs font-semibold text-kawaii-ink/60">อีเมล</dt>
      <dd class="mt-1 break-all font-semibold"><?= h($member['email']) ?></dd>
    </div>
    <div>
      <dt class="text-xs font-semibold text-kawaii-ink/60">เบอร์โทร</dt>
      <dd class="mt-1 font-semibold"><?= h($member['phone'] ?: '-') ?></dd>
    </div>
  </dl>
</section>

<section class="overflow-hidden rounded-3xl border border-kawaii-pink-light bg-white shadow-sm">
  <div class="border-b border-kawaii-pink-light px-5 py-4 sm:px-6">
    <h3 class="text-lg font-bold">ประวัติคำสั่งซื้อ</h3>
    <p class="text-sm text-kawaii-ink/60">ทั้งหมด <?= number_format(count($orders)) ?> รายการ</p>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[600px] text-left text-sm">
      <thead class="bg-kawaii-cream/70 text-kawaii-ink/70">
        <tr>
          <th class="px-5 py-3 font-semibold">เลขที่คำสั่งซื้อ</th>
          <th class="px-5 py-3 font-semibold">วันที่</th>
          <th class="px-5 py-3 text-right font-semibold">ยอดรวม</th>
          <th class="px-5 py-3 font-semibold">สถานะ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-kawaii-pink-light">
        <?php if (!$orders): ?>
          <tr><td colspan="4" class="px-5 py-10 text-center text-kawaii-ink/60">สมาชิกคนนี้ยังไม่มีคำสั่งซื้อ</td></tr>
        <?php else: foreach ($orders as $order): ?>
          <tr class="hover:bg-kawaii-cream/40">
            <td class="px-5 py-4 font-semibold"><?= h($order['order_no']) ?></td>
            <td class="px-5 py-4 whitespace-nowrap"><?= h($order['created_at']) ?></td>
            <td class="px-5 py-4 text-right whitespace-nowrap"><?= h(money($order['total'])) ?></td>
            <td class="px-5 py-4"><?= status_badge($order['status']) ?><p class="mt-1 text-xs text-kawaii-ink/60"><?= h(order_status_description($order['status'])) ?></p></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
