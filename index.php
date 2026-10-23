<?php
$pageTitle = 'Home'; require 'includes/header.php';
$base = "SELECT p.*, COALESCE(s.sold_qty, 0) AS sold_qty
         FROM products p
         LEFT JOIN (
             SELECT oi.product_id, SUM(oi.qty) AS sold_qty
             FROM order_items oi
             JOIN orders o ON o.id=oi.order_id
             WHERE oi.product_id IS NOT NULL AND o.status<>'Cancelled'
             GROUP BY oi.product_id
         ) s ON s.product_id=p.id
         WHERE 1=1";
$new  = $pdo->query("$base AND p.is_new=1 ORDER BY p.id DESC LIMIT 4")->fetchAll();
$best = $pdo->query("$base AND p.is_best=1 ORDER BY p.id DESC LIMIT 4")->fetchAll();
?>
<a class="hero-banner" href="products.php" aria-label="Shop SuperUltraPoop products">
  <img src="assets/images/banner.jpg?v=<?= @filemtime(__DIR__ . '/assets/images/banner.jpg') ?: 1 ?>" alt="Welcome to SuperUltraPoop">
</a>
<h2 class="sec"><?= t('new_arrivals') ?></h2><div class="grid"><?php foreach ($new as $p) product_card($p); ?></div>
<h2 class="sec"><?= t('best_sellers') ?></h2><div class="grid"><?php foreach ($best as $p) product_card($p); ?></div>
<?php require 'includes/footer.php'; ?>
