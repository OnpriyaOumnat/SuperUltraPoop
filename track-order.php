<?php
require_once 'includes/functions.php';
$pageTitle = t('track_title'); 
require 'includes/header.php';
$no = trim($_GET['no'] ?? ''); $o = null;
if ($no !== '') { $st = $pdo->prepare("SELECT * FROM orders WHERE order_no=?"); $st->execute([$no]); $o = $st->fetch(); }
?>
<section class="box">
  <h1><?= t('track_title') ?></h1>
  <form class="filters"><input name="no" value="<?= h($no) ?>" placeholder="<?= t('track_placeholder') ?>"><button class="btn"><?= t('search_btn') ?></button></form>
</section>
<?php if ($no !== '' && !$o): ?><div class="alert err"><?= t('order_not_found') ?></div><?php endif; ?>
<?php if ($o): $it = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $it->execute([$o['id']]); ?>
<div class="box">
  <p><?= t('order_number') ?>: <b><?= h($o['order_no']) ?></b> | <?= t('order_date_label') ?>: <?= h($o['created_at']) ?></p>
  <p><?= t('order_status') ?>: <span class="status"><?= h($o['status']) ?> — <?= h(order_status_label($o['status'])) ?></span></p>
  <p class="muted"><?= h(order_status_description($o['status'])) ?></p>
  <?php if ($o['tracking_no']): ?><p><?= t('tracking_no') ?>: <b><?= h($o['tracking_no']) ?></b></p><?php endif; ?>
  <table><?php foreach ($it->fetchAll() as $i): ?><tr><td><?= h($i['product_name']) ?><?= $i['variant'] ? ' (' . h($i['variant']) . ')' : '' ?> × <?= (int)$i['qty'] ?></td><td><?= money($i['price'] * $i['qty']) ?></td></tr><?php endforeach; ?></table>
  <p><b><?= t('total') ?> <?= money($o['total']) ?></b></p>
</div><?php endif; require 'includes/footer.php'; ?>
