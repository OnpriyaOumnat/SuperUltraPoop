<?php
$pageTitle = 'Products'; require 'includes/header.php';
$cat = (int)($_GET['cat'] ?? 0);
$q    = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'best_selling';
$orderBy = [
    'best_selling' => 'COALESCE(s.sold_qty, 0) DESC, p.id DESC',
    'new' => 'p.id DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
][$sort] ?? 'COALESCE(s.sold_qty, 0) DESC, p.id DESC';

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
$categoryUrl = fn($categoryId) => 'products.php?' . http_build_query(array_filter(
    ['cat' => $categoryId, 'q' => $q, 'sort' => $sort],
    fn($value) => $value !== '' && $value !== 0
));

$sql = "SELECT p.*, COALESCE(s.sold_qty, 0) AS sold_qty
        FROM products p
        LEFT JOIN (
            SELECT oi.product_id, SUM(oi.qty) AS sold_qty
            FROM order_items oi
            JOIN orders o ON o.id=oi.order_id
            WHERE oi.product_id IS NOT NULL AND o.status<>'Cancelled'
            GROUP BY oi.product_id
        ) s ON s.product_id=p.id
        WHERE 1=1";
$args = [];
if ($cat > 0) {
    $sql .= ' AND p.category_id = ?';
    $args[] = $cat;
}
if ($q !== '') { $sql .= " AND (p.name LIKE ? OR p.name_th LIKE ?)"; $args[] = "%$q%"; $args[] = "%$q%"; }
$st = $pdo->prepare("$sql ORDER BY $orderBy"); $st->execute($args);
$items = $st->fetchAll();
?>
<h2 class="sec"><?= t('select_category') ?></h2>
<div class="tabs" aria-label="<?= t('select_category') ?>">
  <a class="tab <?= $cat === 0 ? 'active' : '' ?>" href="<?= h($categoryUrl(0)) ?>" <?= $cat === 0 ? 'aria-current="page"' : '' ?>><?= t('all_products') ?></a>
  <?php foreach ($categories as $category): ?>
    <a class="tab <?= $cat === (int)$category['id'] ? 'active' : '' ?>"
       href="<?= h($categoryUrl((int)$category['id'])) ?>"
       <?= $cat === (int)$category['id'] ? 'aria-current="page"' : '' ?>><?= h($category['name']) ?></a>
  <?php endforeach; ?>
</div>
<form class="filters" method="get">
  <?php if ($cat > 0): ?><input type="hidden" name="cat" value="<?= $cat ?>"><?php endif; ?>
  <input name="q" value="<?= h($q) ?>" placeholder="<?= t('search_placeholder') ?>">
  <select name="sort" onchange="this.form.submit()">
    <option value="best_selling" <?= $sort=='best_selling'?'selected':'' ?>><?= t('sort_best') ?></option>
    <option value="new" <?= $sort=='new'?'selected':'' ?>><?= t('sort_new') ?></option>
    <option value="price_asc" <?= $sort=='price_asc'?'selected':'' ?>><?= t('sort_price_asc') ?></option>
    <option value="price_desc" <?= $sort=='price_desc'?'selected':'' ?>><?= t('sort_price_desc') ?></option>
  </select>
  <button class="btn"><?= t('search_btn') ?></button>
</form>
<p class="muted"><?= sprintf(t('showing_items'), count($items)) ?></p>
<div class="grid"><?php foreach ($items as $p) product_card($p); ?></div>
<?php if (!$items): ?><p><?= t('no_products') ?></p><?php endif; ?>
<?php require 'includes/footer.php'; ?>
