<?php
$pageTitle = 'จัดการสินค้า';
require_once __DIR__ . '/../includes/functions.php';
admin_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') !== 'delete') {
        http_response_code(400);
        exit('คำขอไม่ถูกต้อง');
    }

    $productId = (int)($_POST['id'] ?? 0);
    $delete = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $delete->execute([$productId]);
    flash($delete->rowCount() ? 'ลบสินค้าแล้ว' : 'ไม่พบสินค้าที่ต้องการลบ');
    redirect('products.php');
}

$search = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['cat'] ?? 0);
$low = isset($_GET['low']);   // แสดงเฉพาะสินค้าที่เปิดขายและเหลือน้อย
$sql = 'SELECT p.*, c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE 1=1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (p.name LIKE ? OR p.name_th LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
}
if ($categoryId > 0) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $categoryId;
}
if ($low) {
    $sql .= ' AND p.stock <= 3 AND p.status = ?';
    $params[] = 'active';
}
$sql .= ' ORDER BY p.id DESC';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$products = $statement->fetchAll();
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
require __DIR__ . '/header.php';
?>
<section class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <p class="text-sm text-kawaii-ink/60">เพิ่ม แก้ไข หรือลบสินค้าในร้าน</p>
    <h2 class="mt-1 text-2xl font-bold">สินค้าทั้งหมด</h2>
  </div>
  <a href="product-form.php" class="rounded-2xl bg-kawaii-pink px-5 py-3 text-sm font-bold text-white shadow-md shadow-kawaii-pink/30 transition hover:bg-kawaii-pink-deep">+ เพิ่มสินค้า</a>
</section>

<section class="overflow-hidden rounded-3xl border border-kawaii-pink-light bg-white shadow-sm">
  <form method="get" class="flex flex-wrap gap-3 border-b border-kawaii-pink-light p-4 sm:p-5">
    <label class="sr-only" for="product-search">ค้นหาสินค้า</label>
    <input id="product-search" name="q" value="<?= h($search) ?>" placeholder="ค้นหาชื่อสินค้า" class="min-w-[220px] flex-1 rounded-2xl border border-kawaii-pink-light px-4 py-2.5 outline-none transition focus:border-kawaii-pink focus:ring-2 focus:ring-kawaii-pink/20">
    <label class="sr-only" for="product-category">เลือกหมวดหมู่</label>
    <select id="product-category" name="cat" class="rounded-2xl border border-kawaii-pink-light px-4 py-2.5 outline-none focus:border-kawaii-pink">
      <option value="0">ทุกหมวดหมู่</option>
      <?php foreach ($categories as $category): ?>
        <option value="<?= (int)$category['id'] ?>" <?= $categoryId === (int)$category['id'] ? 'selected' : '' ?>><?= h($category['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="rounded-2xl bg-kawaii-pink px-5 py-2.5 text-sm font-bold text-white transition hover:bg-kawaii-pink-deep">ค้นหา</button>
    <?php if ($search !== '' || $categoryId > 0): ?><a href="products.php" class="rounded-2xl border border-kawaii-pink-light px-5 py-2.5 text-sm font-semibold hover:bg-kawaii-cream">ล้าง</a><?php endif; ?>
  </form>

  <div class="flex flex-wrap items-center justify-between gap-2 border-b border-kawaii-pink-light px-5 py-3 text-sm text-kawaii-ink/60">
    <span>พบสินค้า <span class="font-num tabular-nums"><?= number_format(count($products)) ?></span> รายการ</span>
    <a href="<?= $low ? 'products.php' : 'products.php?low=1' ?>" class="rounded-full px-3 py-1 text-xs font-bold <?= $low ? 'bg-kawaii-pink text-white' : 'bg-amber-100 text-amber-700 hover:bg-amber-200' ?>">⚠️ เหลือน้อย<?= $low ? ' ✕' : '' ?></a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[850px] text-left text-sm">
      <thead class="bg-kawaii-cream/70 text-kawaii-ink/70">
        <tr>
          <th class="px-5 py-3 font-semibold">รูปสินค้า</th>
          <th class="px-5 py-3 font-semibold">ชื่อสินค้า</th>
          <th class="px-5 py-3 font-semibold">หมวดหมู่</th>
          <th class="px-5 py-3 text-right font-semibold">ราคา</th>
          <th class="px-5 py-3 text-right font-semibold">สต็อก</th>
          <th class="px-5 py-3 font-semibold">สถานะ</th>
          <th class="px-5 py-3 text-right font-semibold">จัดการ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-kawaii-pink-light">
        <?php if (!$products): ?>
          <tr><td colspan="7" class="px-5 py-10 text-center text-kawaii-ink/60">ไม่พบสินค้า</td></tr>
        <?php else: foreach ($products as $product): ?>
          <tr class="hover:bg-kawaii-cream/40 <?= $product['status'] === 'active' ? '' : 'opacity-60' ?>">
            <td class="px-5 py-3">
              <img src="../<?= h(img($product)) ?>" alt="<?= h($product['name']) ?>" class="h-14 w-14 rounded-xl object-cover">
            </td>
            <td class="px-5 py-3">
              <p class="font-semibold"><?= h($product['name']) ?></p>
              <?php if ($product['name_th'] !== ''): ?><p class="text-xs text-kawaii-ink/60"><?= h($product['name_th']) ?></p><?php endif; ?>
            </td>
            <td class="px-5 py-3"><?= h($product['cat_name'] ?? '-') ?></td>
            <td class="px-5 py-3 text-right whitespace-nowrap font-num tabular-nums"><?= h(money($product['price'])) ?></td>
            <td class="px-5 py-3 text-right">
              <?php $stock = (int)$product['stock']; ?>
              <?php if ($stock <= 0): ?>
                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700">หมด</span>
              <?php elseif ($stock <= 3): ?>
                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">เหลือ <span class="font-num tabular-nums"><?= $stock ?></span></span>
              <?php else: ?>
                <span class="font-num tabular-nums"><?= $stock ?></span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3">
              <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $product['status'] === 'active' ? 'bg-kawaii-mint text-emerald-800' : 'bg-gray-100 text-gray-600' ?>">
                <?= $product['status'] === 'active' ? 'เปิดขาย' : 'ซ่อนอยู่' ?>
              </span>
            </td>
            <td class="px-5 py-3">
              <div class="flex justify-end gap-3">
                <a href="product-form.php?id=<?= (int)$product['id'] ?>" class="rounded-xl bg-kawaii-blue px-3 py-2 text-xs font-bold hover:brightness-95">แก้ไข</a>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                  <button class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50" data-confirm="ยืนยันลบสินค้า <?= h($product['name']) ?>?">ลบ</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
