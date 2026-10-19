<?php
require_once 'includes/functions.php';
$st = $pdo->prepare("SELECT o.*, p.status AS payment_status FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE o.order_no=?");
$st->execute([$_GET['no'] ?? '']); $o = $st->fetch();
if (!$o) {
    http_response_code(404);
    $pageTitle = 'Order not found'; require 'includes/header.php';
    echo '<p>ไม่พบคำสั่งซื้อ</p>';
    require 'includes/footer.php';
    exit;
}
if ($o['payment_method'] === 'transfer') {
    if ((int)$o['user_id'] !== (int)($_SESSION['user_id'] ?? 0)) { http_response_code(404); exit('ไม่พบคำสั่งซื้อ'); }
    if ($o['payment_status'] !== 'paid') redirect('transfer-payment.php?no=' . urlencode($o['order_no']));
}
$pageTitle = 'Order Success'; require 'includes/header.php';
$it = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $it->execute([$o['id']]);
?>
<div class="box"><h1>🎉 สั่งซื้อสำเร็จ</h1>
<p>เลขที่คำสั่งซื้อ: <b><?= h($o['order_no']) ?></b> (เก็บไว้ใช้ติดตามสถานะ)</p>
<table><?php foreach ($it->fetchAll() as $i): ?><tr><td><?= h($i['product_name']) ?><?= $i['variant'] ? ' (' . h($i['variant']) . ')' : '' ?> × <?= (int)$i['qty'] ?></td><td><?= money($i['price'] * $i['qty']) ?></td></tr><?php endforeach; ?></table>
<p>ค่าจัดส่ง <?= money($o['shipping']) ?> | <b>รวม <?= money($o['total']) ?></b></p>
<p>ชำระเงิน: <?= $o['payment_method'] == 'cod' ? 'เก็บเงินปลายทาง' : 'โอนเงิน (ยืนยันแล้ว)' ?></p>
<a class="btn" href="track-order.php?no=<?= h($o['order_no']) ?>">ติดตามคำสั่งซื้อ</a></div>
<?php require 'includes/footer.php'; ?>
