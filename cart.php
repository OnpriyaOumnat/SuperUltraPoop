<?php
require_once 'includes/functions.php';
// จัดการตะกร้าด้วย POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0); $key = (string)($_POST['key'] ?? $id);
    $back = safe_back($_POST['back'] ?? '', 'cart.php');
    if ($act === 'add' || $act === 'set') {
        $st = $pdo->prepare("SELECT stock, color FROM products WHERE id=? AND status='active'"); $st->execute([$id]); $pr = $st->fetch();
        if (!$pr) { flash(t('product_not_found')); redirect($back); }
        $stock = (int)$pr['stock'];
        if ($act === 'add') {
            $opts = variants($pr); $variant = trim($_POST['variant'] ?? '');
            if (!in_array($variant, $opts, true)) $variant = $opts[0] ?? '';   // ตรวจฝั่งเซิร์ฟเวอร์ ไม่เลือกใช้ตัวแรก
            $key = $variant !== '' ? "$id|$variant" : (string)$id;
        }
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $new = $act === 'add' ? ($_SESSION['cart'][$key] ?? 0) + $qty : $qty;
        if ($new > $stock) { $new = $stock; flash(sprintf(t('stock_exceed'), $stock)); }
        if ($new > 0) $_SESSION['cart'][$key] = $new; else flash(t('out_of_stock'));
        if (!empty($_POST['buy_now'])) redirect('checkout.php');
        if ($act === 'add' && $new > 0 && !isset($_SESSION['flash'])) {
            $multi = count($opts) > 1 && !isset($_POST['variant']);
            flash(t('added_to_cart') . ($multi ? " (" . t('color') . ": $variant)" : ''));
        }
    } elseif ($act === 'remove') unset($_SESSION['cart'][$key]);
    redirect($back);
}
$pageTitle = t('cart_title'); require 'includes/header.php';
$items = cart_items(); $sub = 0;
?>
<h1><?= t('cart_title') ?></h1>
<?php if (!$items): ?><div class="box"><?= t('cart_empty') ?> <a class="btn" href="products.php"><?= t('browse_products') ?></a></div>
<?php else: ?>
<div class="box"><table>
<tr><th><?= t('product') ?></th><th><?= t('price') ?></th><th><?= t('quantity') ?></th><th><?= t('total') ?></th><th></th></tr>
<?php foreach ($items as $p): $line = $p['price'] * $p['qty']; $sub += $line; ?>
<tr>
  <td><?= h(pname($p)) ?><?= $p['variant'] ? ' <span class="status">' . h($p['variant']) . '</span>' : '' ?></td><td><?= money($p['price']) ?></td>
  <td><form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="set"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="key" value="<?= h($p['key']) ?>">
    <input class="qty" type="number" name="qty" min="1" max="<?= (int)$p['stock'] ?>" value="<?= (int)$p['qty'] ?>"><button class="btn btn-light"><?= t('update') ?></button></form></td>
  <td><?= money($line) ?></td>
  <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="key" value="<?= h($p['key']) ?>"><button class="btn btn-light" data-confirm="<?= t('delete_confirm') ?>"><?= t('delete') ?></button></form></td>
</tr><?php endforeach; ?>
</table>
<p><?= sprintf(t('items_total'), array_sum(array_column($items, 'qty'))) ?> | <b><?= t('subtotal') ?> <?= money($sub) ?></b></p>
<a class="btn btn-light" href="products.php"><?= t('continue_shopping') ?></a> <a class="btn" href="checkout.php"><?= t('checkout') ?></a></div>
<?php endif; require 'includes/footer.php'; ?>
