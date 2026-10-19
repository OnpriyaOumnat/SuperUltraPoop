<?php
$pageTitle = 'Track Order'; require 'includes/header.php';
$no = trim($_GET['no'] ?? ''); $o = null;
if ($no !== '') { $st = $pdo->prepare("SELECT * FROM orders WHERE order_no=?"); $st->execute([$no]); $o = $st->fetch(); }
?>
<section class="box">
  <h1>ติดตามคำสั่งซื้อ</h1>
  <form class="filters"><input name="no" value="<?= h($no) ?>" placeholder="เลขที่คำสั่งซื้อ เช่น SUP260101ABCD"><button class="btn">ค้นหา</button></form>
</section>
<?php if ($no !== '' && !$o): ?><div class="alert err">ไม่พบคำสั่งซื้อนี้</div><?php endif; ?>
<?php if ($o): $it = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $it->execute([$o['id']]); ?>
<div class="box">
  <p>เลขที่: <b><?= h($o['order_no']) ?></b> | วันที่: <?= h($o['created_at']) ?></p>
  <p>สถานะคำสั่งซื้อ: <span class="status"><?= h($o['status']) ?> — <?= h(order_status_label($o['status'])) ?></span></p>
  <p class="muted"><?= h(order_status_description($o['status'])) ?></p>
  <?php if ($o['tracking_no']): ?><p>เลขพัสดุ: <b><?= h($o['tracking_no']) ?></b></p><?php endif; ?>
  <table><?php foreach ($it->fetchAll() as $i): ?><tr><td><?= h($i['product_name']) ?><?= $i['variant'] ? ' (' . h($i['variant']) . ')' : '' ?> × <?= (int)$i['qty'] ?></td><td><?= money($i['price'] * $i['qty']) ?></td></tr><?php endforeach; ?></table>
  <p><b>ยอดรวม <?= money($o['total']) ?></b></p>
</div><?php endif; require 'includes/footer.php'; ?>
