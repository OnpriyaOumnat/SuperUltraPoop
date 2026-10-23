<?php
require_once 'includes/functions.php';
if (empty($_SESSION['user_id'])) redirect('login.php');
$pageTitle = t('my_account'); require 'includes/header.php';
$s = $pdo->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$_SESSION['user_id']]); $u = $s->fetch();
$o = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC"); $o->execute([$_SESSION['user_id']]);
?>
<div class="box"><h1><?= t('my_account') ?></h1><p><?= h($u['name']) ?> | <?= h($u['email']) ?> | <?= h($u['phone']) ?></p></div>
<div class="box"><h3><?= t('order_history') ?></h3><table><tr><th><?= t('order_no') ?></th><th><?= t('order_date') ?></th><th><?= t('total') ?></th><th><?= t('status') ?></th></tr>
<?php foreach ($o->fetchAll() as $r): ?><tr><td><a href="track-order.php?no=<?= h($r['order_no']) ?>"><?= h($r['order_no']) ?></a></td><td><?= h($r['created_at']) ?></td><td><?= money($r['total']) ?></td><td><span class="status" title="<?= h(order_status_description($r['status'])) ?>"><?= h($r['status']) ?> — <?= h(order_status_label($r['status'])) ?></span></td></tr><?php endforeach; ?></table></div>
<?php require 'includes/footer.php'; ?>
