<?php
require_once 'includes/functions.php'; $err = '';
$next = ($_GET['next'] ?? $_POST['next'] ?? '') === 'checkout.php' ? 'checkout.php' : 'account.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $pw = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'กรอกชื่อและอีเมลให้ถูกต้อง';
    elseif (strlen($pw) < 8) $err = 'รหัสผ่านอย่างน้อย 8 ตัวอักษร';
    else {
        $c = $pdo->prepare("SELECT id FROM users WHERE email=?"); $c->execute([$email]);
        if ($c->fetch()) $err = 'อีเมลนี้ถูกใช้แล้ว';
        else {
            $pdo->prepare("INSERT INTO users (name,email,phone,password_hash) VALUES (?,?,?,?)")->execute([$name, $email, $phone, password_hash($pw, PASSWORD_DEFAULT)]);
            session_regenerate_id(true); $_SESSION['user_id'] = $pdo->lastInsertId(); redirect($next);
        }
    }
}
$pageTitle = 'Register'; require 'includes/header.php';
?>
<div class="box form" style="max-width:420px;margin:auto"><h1>สมัครสมาชิก</h1>
<?php if ($err): ?><div class="alert err"><?= h($err) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="next" value="<?= h($next) ?>"><label>ชื่อ</label><input name="name" required><label>อีเมล</label><input type="email" name="email" required>
<label>เบอร์โทร</label><input name="phone"><label>รหัสผ่าน (8 ตัวขึ้นไป)</label><input type="password" name="password" required minlength="8">
<p><button class="btn">Register</button></p></form></div>
<?php require 'includes/footer.php'; ?>
