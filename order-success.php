<?php
require_once 'includes/functions.php';
$st = $pdo->prepare("SELECT o.*, p.status AS payment_status FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE o.order_no=?");
$st->execute([$_GET['no'] ?? '']); $o = $st->fetch();
if (!$o) {
    http_response_code(404);
    $pageTitle = t('order_not_found'); require 'includes/header.php';
    echo '<p>' . t('order_not_found') . '</p>';
    require 'includes/footer.php';
    exit;
}
if ($o['payment_method'] === 'transfer') {
    if ((int)$o['user_id'] !== (int)($_SESSION['user_id'] ?? 0)) { http_response_code(404); exit(t('order_not_found')); }
    if ($o['payment_status'] !== 'paid') redirect('transfer-payment.php?no=' . urlencode($o['order_no']));
}
$pageTitle = t('order_success'); require 'includes/header.php';
$it = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $it->execute([$o['id']]);
?>
<div class="box"><h1><?= t('order_success') ?></h1>
<p><?= t('order_no_label') ?>: <b><?= h($o['order_no']) ?></b> (<?= t('keep_order_no') ?>)</p>
<table><?php foreach ($it->fetchAll() as $i): ?><tr><td><?= h($i['product_name']) ?><?= $i['variant'] ? ' (' . h($i['variant']) . ')' : '' ?> × <?= (int)$i['qty'] ?></td><td><?= money($i['price'] * $i['qty']) ?></td></tr><?php endforeach; ?></table>
<p><?= t('shipping_fee') ?> <?= money($o['shipping']) ?> | <b><?= t('total') ?> <?= money($o['total']) ?></b></p>
<p><?= t('payment_label') ?>: <?= $o['payment_method'] == 'cod' ? t('payment_cod') : t('payment_transfer') ?></p>
<a class="btn" href="track-order.php?no=<?= h($o['order_no']) ?>"><?= t('track_order') ?></a></div>
<?php require 'includes/footer.php'; ?>
