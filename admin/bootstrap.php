<?php
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('index.php');
}

$adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount > 0) {
    redirect('login.php');
}

$username = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!preg_match('/\A[a-zA-Z0-9_.-]{3,32}\z/', $username)) {
        $errors[] = 'ชื่อผู้ใช้ต้องยาว 3–32 ตัว และใช้ได้เฉพาะ a-z, A-Z, 0-9, จุด, ขีดกลาง หรือขีดล่าง';
    }
    if (mb_strlen($password, 'UTF-8') < 8) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
    }

    if (!$errors) {
        $createdAdminId = null;
        $adminAlreadyExists = false;
        try {
            $existingAdmin = $pdo->query('SELECT id FROM admins LIMIT 1')->fetchColumn();
            if ($existingAdmin) {
                $adminAlreadyExists = true;
            } else {
                $create = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
                $create->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                $createdAdminId = (int)$pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            $driverCode = $e->errorInfo[1] ?? null;
            if ($e->getCode() === '23000' && (int)$driverCode === 1062) {
                $errors[] = 'ชื่อผู้ใช้นี้มีคนใช้งานแล้ว';
            } else {
                throw $e;
            }
        }

        if ($adminAlreadyExists) {
            redirect('login.php');
        }

        if ($createdAdminId !== null) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $createdAdminId;
            $_SESSION['admin_username'] = $username;
            redirect('index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>สร้างแอดมินคนแรก | SuperUltraPoop</title>
  <link href="https://fonts.googleapis.com/css2?family=Mali:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: {
      colors: { kawaii: { pink: '#ff8fb8', 'pink-deep': '#ff6fa5', 'pink-light': '#ffe4ef', cream: '#fff8ec', ink: '#6b4c5a' } },
      fontFamily: { sans: ['Mali', 'Tahoma', 'sans-serif'] }
    } } };
  </script>
</head>
<body class="flex min-h-screen items-center justify-center bg-kawaii-cream px-4 py-10 font-sans text-kawaii-ink">
  <main class="w-full max-w-md rounded-3xl border border-kawaii-pink-light bg-white p-7 shadow-xl shadow-kawaii-pink/10 sm:p-9">
    <div class="mb-7 text-center">
      <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-3xl bg-kawaii-pink-light text-3xl">🧸</div>
      <h1 class="text-2xl font-bold text-kawaii-pink-deep">สร้างแอดมินคนแรก</h1>
      <p class="mt-1 text-sm text-kawaii-ink/60">ตั้งค่าบัญชีผู้ดูแลระบบเพื่อเริ่มใช้งานร้าน</p>
    </div>
    <?php foreach ($errors as $error): ?>
      <p class="mb-3 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700" role="alert"><?= h($error) ?></p>
    <?php endforeach; ?>
    <form method="post" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label for="username" class="mb-1 block text-sm font-semibold">ชื่อผู้ใช้</label>
        <input id="username" name="username" value="<?= h($username) ?>" autocomplete="username" minlength="3" maxlength="32" pattern="[a-zA-Z0-9_.-]{3,32}" required class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none transition focus:border-kawaii-pink focus:ring-2 focus:ring-kawaii-pink/20">
      </div>
      <div>
        <label for="password" class="mb-1 block text-sm font-semibold">รหัสผ่าน</label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none transition focus:border-kawaii-pink focus:ring-2 focus:ring-kawaii-pink/20">
        <p class="mt-1 text-xs text-kawaii-ink/60">อย่างน้อย 8 ตัวอักษร</p>
      </div>
      <div>
        <label for="confirm_password" class="mb-1 block text-sm font-semibold">ยืนยันรหัสผ่าน</label>
        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none transition focus:border-kawaii-pink focus:ring-2 focus:ring-kawaii-pink/20">
      </div>
      <button class="w-full rounded-2xl bg-kawaii-pink px-5 py-3 font-bold text-white shadow-md shadow-kawaii-pink/30 transition hover:bg-kawaii-pink-deep">สร้างบัญชีและเข้าสู่ระบบ</button>
    </form>
    <a href="login.php" class="mt-5 block text-center text-sm text-kawaii-ink/60 hover:text-kawaii-pink-deep">← กลับไปหน้าเข้าสู่ระบบ</a>
  </main>
</body>
</html>
