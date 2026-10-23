<?php
// ฟังก์ชันกลางของระบบ (ใช้ร่วมกันทั้งหน้าบ้านและหลังบ้าน)
// ตั้งค่า cookie ของ session ให้ปลอดภัยขึ้น (ต้องเรียกก่อน session_start)
date_default_timezone_set('Asia/Bangkok');
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $isHttps,   // เปิดอัตโนมัติเมื่อเว็บเป็น HTTPS
    'httponly' => true,       // JavaScript อ่าน cookie นี้ไม่ได้
    'samesite' => 'Lax',      // กัน cookie ถูกส่งไปกับคำขอข้ามเว็บ
]);
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo->exec("SET time_zone = '+07:00'");

class OrderWorkflowException extends RuntimeException {}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }   // กัน XSS
function money($n) { return '฿' . number_format((float)$n, 2); }
function redirect($url) { header('Location: ' . $url); exit; }
function order_statuses() {
    return [
        'Pending' => ['label' => 'รอดำเนินการ', 'description' => 'รอตรวจสอบคำสั่งซื้อหรือการชำระเงิน'],
        'Paid' => ['label' => 'ชำระเงินแล้ว', 'description' => 'บันทึกการชำระเงินที่ตรวจสอบแล้ว'],
        'Processing' => ['label' => 'กำลังเตรียมสินค้า', 'description' => 'แพ็กสินค้าและเตรียมจัดส่ง'],
        'Shipped' => ['label' => 'จัดส่งแล้ว', 'description' => 'บันทึกเลขพัสดุให้ลูกค้าติดตาม'],
        'Completed' => ['label' => 'สำเร็จ', 'description' => 'คำสั่งซื้อเสร็จสมบูรณ์'],
        'Cancelled' => ['label' => 'ยกเลิก', 'description' => 'คำสั่งซื้อถูกยกเลิก'],
    ];
}
function order_status_label($status) {
    $statuses = order_statuses();
    return $statuses[$status]['label'] ?? (string)$status;
}
function order_status_description($status) {
    $statuses = order_statuses();
    return $statuses[$status]['description'] ?? 'ไม่ทราบสถานะคำสั่งซื้อ';
}
function next_order_statuses($status) {
    $transitions = [
        'Pending' => ['Paid', 'Cancelled'],
        'Paid' => ['Processing', 'Cancelled'],
        'Processing' => ['Shipped', 'Cancelled'],
        'Shipped' => ['Completed'],
        'Completed' => [],
        'Cancelled' => [],
    ];
    return $transitions[$status] ?? [];
}
function flash($msg = null) {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return; }
    $m = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']); return $m;
}

// ---------- CSRF ----------
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('CSRF token ไม่ถูกต้อง'); }
}

// ---------- ตั้งค่าร้าน ----------
function setting($key, $default = '') {
    global $pdo; static $c = null;
    if ($c === null) $c = $pdo->query("SELECT setting_key, setting_value FROM store_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    return $c[$key] ?? $default;
}

// ---------- รูปสินค้า (ถ้าไม่มีไฟล์ ใช้ placeholder) ----------
function img($p) {
    $f = $p['image'] ?? '';
    return ($f && is_file(__DIR__ . '/../' . $f)) ? $f : 'assets/images/placeholder.svg';
}

// ---------- ตะกร้า (เก็บใน Session จึงไม่หายเมื่อเปลี่ยนหมวดหมู่) ----------
function cart_count() { return array_sum($_SESSION['cart'] ?? []); }
// ตัวเลือกสี/แบบของสินค้า: เก็บในคอลัมน์ color คั่นด้วยคอมมา เช่น "Pom,Nuts"
function variants($p) { return array_values(array_filter(array_map('trim', explode(',', (string)($p['color'] ?? ''))))); }
// ตะกร้าใช้ key รูปแบบ "id|ตัวเลือก" (ถ้าไม่มีตัวเลือกใช้แค่ "id")
function cart_items() {
    global $pdo; $rows = [];
    foreach ($_SESSION['cart'] ?? [] as $key => $qty) {
        [$id, $variant] = array_pad(explode('|', (string)$key, 2), 2, '');
        $st = $pdo->prepare("SELECT * FROM products WHERE status='active' AND id=?"); $st->execute([(int)$id]);
        if (!$p = $st->fetch()) continue;
        $p['qty'] = min((int)$qty, (int)$p['stock']);   // ป้องกันเกินสต็อก
        $p['variant'] = $variant; $p['key'] = (string)$key;
        if ($p['qty'] > 0) $rows[] = $p;
    }
    return $rows;
}
function safe_back($url, $fallback) {   // อนุญาตเฉพาะ path ภายในเว็บ
    return (is_string($url) && preg_match('#^/[^/\\\\\r\n][^\r\n]*$#', $url)) ? $url : $fallback;
}

// ---------- การ์ดสินค้า ----------
function product_card($p) {
    global $lang;
    $out = $p['stock'] <= 0;
    $inactive = ($p['status'] ?? 'active') !== 'active';
    ?>
    <div class="card">
      <a href="product-detail.php?id=<?= (int)$p['id'] ?>" class="card-img">
        <img src="<?= h(img($p)) ?>" alt="<?= h($p['name']) ?>" loading="lazy" <?= $inactive ? 'style="filter:grayscale(1);opacity:.65"' : '' ?>>
        <?php if ($p['is_new']): ?><span class="badge b-new"><?= t('new_arrival_badge') ?></span><?php endif; ?>
        <?php if ($p['is_best']): ?><span class="badge b-best"><?= t('best_seller_badge') ?></span><?php endif; ?>
        <?php if ($inactive): ?><span class="badge" style="top:auto;left:auto;right:10px;bottom:10px;background:#777"><?= t('sold_out') ?></span><?php endif; ?>
      </a>
      <div class="card-body">
        <h3><?= h(pname($p)) ?></h3>
        <p class="muted small"><?= h($lang === 'en' ? ($p['name_th'] ?: $p['name']) : $p['name']) ?></p>
        <div class="price"><?= money($p['price']) ?></div>
        <div class="small muted">
          <?= sprintf(t('sold_count'), number_format((int)($p['sold_qty'] ?? 0))) ?> ·
          <span class="<?= ($out || $inactive) ? 'red' : 'green' ?>"><?= $inactive ? t('sold_out') : ($out ? t('out_of_stock') : sprintf(t('in_stock'), (int)$p['stock'])) ?></span>
        </div>
        <div class="row">
          <a class="btn btn-light" href="product-detail.php?id=<?= (int)$p['id'] ?>"><?= t('view_details') ?></a>
          <form method="post" action="cart.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="back" value="<?= h($_SERVER['REQUEST_URI']) ?>">
            <button class="btn" <?= ($out || $inactive) ? 'disabled' : '' ?>><i class="fa-solid fa-cart-plus"></i> <?= t('add_to_cart') ?></button>
          </form>
        </div>
      </div>
    </div>
    <?php
}

// ---------- สมาชิก / แอดมิน ----------
function admin_required() {
    if (!isset($_SESSION['admin_id'])) {
        redirect('login.php');
    }
}
require_once __DIR__ . '/lang.php';
