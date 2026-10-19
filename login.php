<?php
require_once 'includes/functions.php'; $err = '';
$next = ($_GET['next'] ?? $_POST['next'] ?? '') === 'checkout.php' ? 'checkout.php' : 'account.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = $pdo->prepare("SELECT * FROM users WHERE email=?"); $s->execute([trim($_POST['email'] ?? '')]); $u = $s->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true); $_SESSION['user_id'] = $u['id']; redirect($next);
    }
    $err = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
}
$pageTitle = 'Login'; require 'includes/header.php';
?>
<div class="box form" style="max-width:420px;margin:auto"><h1>เข้าสู่ระบบ</h1>
<?php if ($err): ?><div class="alert err"><?= h($err) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="next" value="<?= h($next) ?>"><label>อีเมล</label><input type="email" name="email" required><label>รหัสผ่าน</label><input type="password" name="password" required>
<p><button class="btn">Login</button> <a href="register.php<?= $next === 'checkout.php' ? '?next=checkout.php' : '' ?>">สมัครสมาชิก</a></p></form></div>
<?php require 'includes/footer.php'; ?>
