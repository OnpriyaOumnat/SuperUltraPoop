<?php
$pageTitle = 'สมาชิก';
require __DIR__ . '/header.php';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT u.id, u.name, u.email, u.phone, COUNT(o.id) AS order_count,
    COALESCE(SUM(CASE WHEN o.status IN ('Paid','Processing','Shipped','Completed') THEN o.total END), 0) AS spent
    FROM users u LEFT JOIN orders o ON o.user_id = u.id";
$params = [];
if ($search !== '') {
    $sql .= ' WHERE u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?';
    $term = '%' . $search . '%';
    $params = [$term, $term, $term];
}
$sql .= ' GROUP BY u.id, u.name, u.email, u.phone ORDER BY u.id DESC';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$customers = $statement->fetchAll();
?>
<section class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <p class="text-sm text-kawaii-ink/60">บัญชีสมาชิกที่สมัครในร้าน</p>
    <h2 class="mt-1 text-2xl font-bold">รายชื่อสมาชิก</h2>
  </div>
  <span class="rounded-2xl bg-kawaii-pink-light px-4 py-2 text-sm font-bold"><?= number_format(count($customers)) ?> รายชื่อ</span>
</section>

<section class="overflow-hidden rounded-3xl border border-kawaii-pink-light bg-white shadow-sm">
  <form method="get" class="flex flex-wrap gap-3 border-b border-kawaii-pink-light p-4 sm:p-5">
    <label class="sr-only" for="customer-search">ค้นหาสมาชิก</label>
    <input id="customer-search" name="q" value="<?= h($search) ?>" placeholder="ค้นหาชื่อ อีเมล หรือเบอร์โทร" class="min-w-[220px] flex-1 rounded-2xl border border-kawaii-pink-light px-4 py-2.5 outline-none transition focus:border-kawaii-pink focus:ring-2 focus:ring-kawaii-pink/20">
    <button class="rounded-2xl bg-kawaii-pink px-5 py-2.5 text-sm font-bold text-white transition hover:bg-kawaii-pink-deep">ค้นหา</button>
    <?php if ($search !== ''): ?><a href="customers.php" class="rounded-2xl border border-kawaii-pink-light px-5 py-2.5 text-sm font-semibold hover:bg-kawaii-cream">ล้าง</a><?php endif; ?>
  </form>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[760px] text-left text-sm">
      <thead class="bg-kawaii-cream/70 text-kawaii-ink/70">
        <tr>
          <th class="px-5 py-3 font-semibold">ชื่อสมาชิก</th>
          <th class="px-5 py-3 font-semibold">อีเมล</th>
          <th class="px-5 py-3 font-semibold">เบอร์โทร</th>
          <th class="px-5 py-3 text-right font-semibold">ออเดอร์</th>
          <th class="px-5 py-3 text-right font-semibold">ยอดสั่งซื้อ</th>
          <th class="px-5 py-3 text-right font-semibold">รายละเอียด</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-kawaii-pink-light">
        <?php if (!$customers): ?>
          <tr><td colspan="6" class="px-5 py-10 text-center text-kawaii-ink/60"><?= $search !== '' ? 'ไม่พบสมาชิกที่ตรงกับการค้นหา' : 'ยังไม่มีสมาชิกสมัครบัญชี' ?></td></tr>
        <?php else: foreach ($customers as $customer): ?>
          <tr class="hover:bg-kawaii-cream/40">
            <td class="px-5 py-4 font-semibold"><a href="member-detail.php?id=<?= (int)$customer['id'] ?>" class="text-kawaii-pink-deep hover:underline"><?= h($customer['name']) ?></a></td>
            <td class="px-5 py-4"><?= h($customer['email']) ?></td>
            <td class="px-5 py-4 font-num tabular-nums"><?= h($customer['phone'] ?: '-') ?></td>
            <td class="px-5 py-4 text-right font-num tabular-nums"><?= number_format((int)$customer['order_count']) ?></td>
            <td class="px-5 py-4 text-right whitespace-nowrap font-num font-semibold tabular-nums"><?= h(money($customer['spent'])) ?></td>
            <td class="px-5 py-4 text-right"><a href="member-detail.php?id=<?= (int)$customer['id'] ?>" class="rounded-xl bg-kawaii-pink-light px-3 py-2 text-xs font-bold text-kawaii-pink-deep hover:bg-kawaii-pink">ดูรายละเอียด</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
