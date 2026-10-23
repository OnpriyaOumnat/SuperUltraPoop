<?php
require_once 'includes/functions.php';
$soldProducts = "LEFT JOIN (
    SELECT oi.product_id, SUM(oi.qty) AS sold_qty
    FROM order_items oi
    JOIN orders o ON o.id=oi.order_id
    WHERE oi.product_id IS NOT NULL AND o.status<>'Cancelled'
    GROUP BY oi.product_id
) s ON s.product_id=p.id";
$st = $pdo->prepare("SELECT p.*, COALESCE(s.sold_qty, 0) AS sold_qty
                     FROM products p
                     $soldProducts
                     WHERE p.id=?");
$st->execute([(int)($_GET['id'] ?? 0)]); $p = $st->fetch();
if (!$p) { http_response_code(404); exit('ไม่พบสินค้า'); }
$pageTitle = $p['name']; require 'includes/header.php';
$rel = $pdo->prepare("SELECT p.*, COALESCE(s.sold_qty, 0) AS sold_qty
                      FROM products p
                      $soldProducts
                      WHERE p.id<>?
                      ORDER BY COALESCE(s.sold_qty, 0) DESC, p.id DESC
                      LIMIT 4");
$rel->execute([$p['id']]);
$out = $p['stock'] <= 0;
$inactive = $p['status'] !== 'active';
?>
<p class="product-back"><a class="btn btn-light" href="products.php"><?= t('back_to_products') ?></a></p>
<div class="two detail box">
  <img src="<?= h(img($p)) ?>" alt="<?= h($p['name']) ?>" <?= $inactive ? 'style="filter:grayscale(1);opacity:.65"' : '' ?>>
  <div>
    <h1><?= h(pname($p)) ?></h1><p class="muted"><?= h($lang === 'en' ? ($p['name_th'] ?: $p['name']) : $p['name']) ?></p>
    <div class="price" style="font-size:28px"><?= money($p['price']) ?></div>
    <p class="muted small">
      <?= sprintf(t('sold_count'), number_format((int)$p['sold_qty'])) ?> ·
      <span class="<?= ($out || $inactive) ? 'red' : 'green' ?>"><?= $inactive ? t('sold_out') : ($out ? t('out_of_stock') : sprintf(t('in_stock'), (int)$p['stock'])) ?></span>
    </p>
    <p><?= nl2br(h($p['description'])) ?></p>
    <?php if ($p['scent']): ?><p><?= t('scent') ?>: <?= h($p['scent']) ?></p><?php endif; ?>
    <?php $opts = variants($p); if (count($opts) == 1): ?><p><?= t('color') ?>: <?= h($opts[0]) ?></p><?php endif; ?>
    <form method="post" action="cart.php" class="row">
      <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
      <input type="hidden" name="back" value="<?= h($_SERVER['REQUEST_URI']) ?>">
      <?php if (count($opts) > 1): ?>
<style>
.sup-opts{display:flex;gap:12px;flex-wrap:wrap;margin:6px 0 16px}
.sup-opt{position:relative;display:inline-block!important;margin:0!important;cursor:pointer}
.sup-opt input{display:none!important}
.sup-opt span{display:inline-block;padding:10px 28px;border:2px solid #ff8fb8;border-radius:999px;background:#fff;color:#ff6fa5;font-weight:700;transition:.2s}
.sup-opt:hover span{background:#ffe4ef;transform:translateY(-2px)}
.sup-opt input:checked+span{background:linear-gradient(135deg,#ff8fb8,#ff6fa5);color:#fff;border-color:#ff6fa5;box-shadow:0 6px 14px rgba(255,111,165,.4)}
.sup-opt input:checked+span::before{content:"✓ "}
</style>
<div style="flex-basis:100%"><p class="muted small"><?= t('select_variant') ?></p>
  <div class="sup-opts">
  <?php foreach ($opts as $i => $o): ?>
    <label class="sup-opt"><input type="radio" name="variant" value="<?= h($o) ?>" <?= $i == 0 ? 'checked' : '' ?>><span><?= h($o) ?></span></label>
  <?php endforeach; ?>
  </div></div>
<?php endif; ?>
      <input class="qty" type="number" name="qty" value="1" min="1" max="<?= max(1,(int)$p['stock']) ?>">
      <button class="btn" <?= ($out || $inactive) ? 'disabled' : '' ?>><?= t('add_to_cart') ?></button>
      <button class="btn btn-light" name="buy_now" value="1" <?= ($out || $inactive) ? 'disabled' : '' ?>><?= t('buy_now') ?></button>
    </form>
  </div>
</div>
<h2 class="sec"><?= t('related_products') ?></h2>
<div class="grid"><?php foreach ($rel->fetchAll() as $r) product_card($r); ?></div>
<?php require 'includes/footer.php'; ?>
