<?php
require_once 'includes/functions.php';
if (!cart_items()) { flash('ตะกร้าว่าง'); redirect('cart.php'); }
if (empty($_SESSION['user_id'])) redirect('login.php?next=checkout.php');
$errors = []; $ship = (float)setting('shipping_fee', 0);
$s = $pdo->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$_SESSION['user_id']]); $u = $s->fetch();
if (!$u) { unset($_SESSION['user_id']); redirect('login.php?next=checkout.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = array_map('trim', array_intersect_key($_POST, array_flip(['name','phone','address','province','district','subdistrict','postcode','payment'])));
    $labels = ['name' => 'ชื่อ-นามสกุล', 'phone' => 'เบอร์โทรศัพท์', 'address' => 'ที่อยู่จัดส่ง', 'province' => 'จังหวัด', 'district' => 'อำเภอ/เขต', 'subdistrict' => 'ตำบล/แขวง'];
    foreach ($labels as $k => $label) if (($f[$k] ?? '') === '') $errors[] = "กรุณาระบุ$label";
    if (!preg_match('/^[0-9+\-\s]{8,15}$/', $f['phone'] ?? '')) $errors[] = 'เบอร์โทรไม่ถูกต้อง';
    if (!preg_match('/^[0-9]{5}$/', $f['postcode'] ?? '')) $errors[] = 'รหัสไปรษณีย์ต้องมี 5 หลัก';
    if (!in_array($f['payment'] ?? '', ['transfer', 'cod'], true)) $errors[] = 'เลือกวิธีชำระเงิน';
    if (!$errors) {
        try {
            $pdo->beginTransaction();   // สร้างออเดอร์ + ตัดสต็อกพร้อมกัน ถ้าพลาดให้ rollback
            $sub = 0; $lines = []; $need = [];
            foreach ($_SESSION['cart'] as $key => $qty) {
                $pid = (int)$key; $variant = explode('|', (string)$key, 2)[1] ?? '';
                $s = $pdo->prepare("SELECT id,name,price,stock FROM products WHERE id=? AND status='active' FOR UPDATE");
                $s->execute([$pid]); $p = $s->fetch();
                $need[$pid] = ($need[$pid] ?? 0) + $qty;   // รวมสต็อกข้ามตัวเลือกของสินค้าเดียวกัน
                if (!$p || $p['stock'] < $need[$pid]) throw new Exception('สินค้า ' . ($p['name'] ?? '') . ' คงเหลือไม่พอ');
                $sub += $p['price'] * $qty; $lines[] = [$p, (int)$qty, $variant];
            }
            $orderNo = 'SUP' . date('ymd') . strtoupper(bin2hex(random_bytes(2)));
            $total = $sub + $ship;
            $pdo->prepare("INSERT INTO orders (order_no,user_id,customer_name,phone,address,province,district,subdistrict,postcode,subtotal,shipping,total,payment_method) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$orderNo, $_SESSION['user_id'] ?? null, $f['name'], $f['phone'], $f['address'], $f['province'], $f['district'], $f['subdistrict'], $f['postcode'], $sub, $ship, $total, $f['payment']]);
            $oid = $pdo->lastInsertId();
            foreach ($lines as [$p, $qty, $variant]) {
                $pdo->prepare("INSERT INTO order_items (order_id,product_id,product_name,variant,price,qty) VALUES (?,?,?,?,?,?)")->execute([$oid, $p['id'], $p['name'], $variant ?: null, $p['price'], $qty]);
                $pdo->prepare("UPDATE products SET stock=stock-? WHERE id=?")->execute([$qty, $p['id']]);
            }
            $pdo->prepare("INSERT INTO payments (order_id,method,amount,status,is_demo) VALUES (?,?,?, 'pending', 1)")->execute([$oid, $f['payment'], $total]);
            $pdo->commit();
            unset($_SESSION['cart']);
            if ($f['payment'] === 'transfer') redirect('transfer-payment.php?no=' . urlencode($orderNo));
            redirect('order-success.php?no=' . urlencode($orderNo));
        } catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $errors[] = $e->getMessage(); }
    }
}
$pageTitle = 'Checkout'; require 'includes/header.php';
$items = cart_items(); $sub = 0; foreach ($items as $p) $sub += $p['price'] * $p['qty'];
$v = fn($k, $d = '') => h($_POST[$k] ?? $d);
?>
<h1>สั่งซื้อสินค้า</h1>
<?php foreach ($errors as $e): ?><div class="alert err"><?= h($e) ?></div><?php endforeach; ?>
<div class="two">
<form method="post" class="box form"><?= csrf_field() ?>
  <label>ชื่อ-นามสกุล</label><input name="name" required value="<?= $v('name', $u['name'] ?? '') ?>">
  <label>เบอร์โทรศัพท์</label><input name="phone" required value="<?= $v('phone', $u['phone'] ?? '') ?>">
  <label>ที่อยู่จัดส่ง <span class="muted small">(บ้านเลขที่ หมู่ ซอย ถนน)</span></label>
  <textarea name="address" required rows="3" placeholder="เช่น 99/9 หมู่ 5 ซอยสุขใจ ถนนพหลโยธิน"><?= $v('address') ?></textarea>
  <label>จังหวัด</label>
  <select name="province" id="addr-province" required data-want="<?= $v('province') ?>"><option value="">กำลังโหลดรายการ…</option></select>
  <label>อำเภอ/เขต</label>
  <select name="district" id="addr-district" required data-want="<?= $v('district') ?>"><option value="">— เลือกจังหวัดก่อน —</option></select>
  <label>ตำบล/แขวง</label>
  <select name="subdistrict" id="addr-subdistrict" required data-want="<?= $v('subdistrict') ?>"><option value="">— เลือกอำเภอก่อน —</option></select>
  <label>รหัสไปรษณีย์</label>
  <select name="postcode" id="addr-zip" required data-want="<?= $v('postcode') ?>"><option value="">— เลือกจังหวัดก่อน —</option></select>
  <p id="addr-msg" class="muted small" role="status"></p>
  <label>วิธีชำระเงิน</label>
  <select name="payment"><option value="transfer">โอนเงิน</option><option value="cod" <?= ($_POST['payment'] ?? '')=='cod'?'selected':'' ?>>เก็บเงินปลายทาง</option></select>
  <p><button class="btn">ยืนยันคำสั่งซื้อ</button></p>
</form>
<div class="box"><h3>สรุปรายการ</h3>
  <?php foreach ($items as $p): ?><p><?= h($p['name']) ?><?= $p['variant'] ? ' (' . h($p['variant']) . ')' : '' ?> × <?= (int)$p['qty'] ?> <b style="float:right"><?= money($p['price'] * $p['qty']) ?></b></p><?php endforeach; ?>
  <hr><p>ยอดสินค้า <b style="float:right"><?= money($sub) ?></b></p>
  <p>ค่าจัดส่ง <b style="float:right"><?= money($ship) ?></b></p>
  <p>รวมทั้งสิ้น <b style="float:right"><?= money($sub + $ship) ?></b></p>
  <p class="muted small">* การโอนเงินต้องแจ้งยอดและเลขอ้างอิง แล้วรอร้านตรวจสอบก่อนยืนยันคำสั่งซื้อ</p>
</div></div>
<script src="assets/js/thai-address.js?v=<?= @filemtime(__DIR__ . '/assets/js/thai-address.js') ?: 1 ?>"></script>
<?php require 'includes/footer.php'; ?>
