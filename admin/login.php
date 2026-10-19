<?php
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('index.php');
}

$adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
$error = '';

// ล็อกชั่วคราวเมื่อกรอกผิดหลายครั้ง (ต้องมีตาราง login_attempts: ดู database/add_login_attempts.sql)
const LOGIN_MAX_FAILS  = 5;    // ผิดได้กี่ครั้ง
const LOGIN_WINDOW_MIN = 10;   // นับภายในกี่นาที (และล็อกนานเท่านี้)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

function login_recent_fails(PDO $pdo, string $ip): int
{
    try {
        $s = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL ' . LOGIN_WINDOW_MIN . ' MINUTE)');
        $s->execute([$ip]);
        return (int)$s->fetchColumn();
    } catch (PDOException $e) {
        return 0;   // ยังไม่ได้สร้างตาราง: ไม่ล็อก แต่ล็อกอินยังใช้งานได้ปกติ
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (login_recent_fails($pdo, $ip) >= LOGIN_MAX_FAILS) {
        $error = 'กรอกผิดหลายครั้งเกินไป กรุณารอ ' . LOGIN_WINDOW_MIN . ' นาทีแล้วลองใหม่';
    } else {
        $statement = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ?');
        $statement->execute([trim($_POST['username'] ?? '')]);
        $admin = $statement->fetch();

        // ตรวจรหัสผ่านด้วย password_verify เท่านั้น (ไม่รับค่าแฮชเป็นรหัสผ่านอีกต่อไป)
        if ($admin && password_verify($_POST['password'] ?? '', (string)$admin['password_hash'])) {
            try {
                $pdo->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
            } catch (PDOException $e) {
            }
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            redirect('index.php');
        }

        try {
            $pdo->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([$ip]);
            $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
        } catch (PDOException $e) {
        }
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เข้าสู่ระบบแอดมิน | SuperUltraPoop</title>
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
      <h1 class="text-2xl font-bold text-kawaii-pink-deep">Admin Login</h1>
      <p class="mt-1 text-sm text-kawaii-ink/60">เข้าสู่ระบบจัดการร้าน SuperUltraPoop</p>
    </div>
    <?php if ($error !== ''): ?>
      <p class="mb-5 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700" role="alert"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label for="username" class="mb-1 block text-sm font-semibold">ชื่อผู้ใช้</label>
        <input id="username" name="username" autocomplete="username" required class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none transition focus:border-kawaii-pink focus:ring-2 focus:ring-kawaii-pink/20">
      </div>
      <div>
        <label for="password" class="mb-1 block text-sm font-semibold">รหัสผ่าน</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none transition focus:border-kawaii-pink focus:ring-2 focus:ring-kawaii-pink/20">
      </div>
      <button class="w-full rounded-2xl bg-kawaii-pink px-5 py-3 font-bold text-white shadow-md shadow-kawaii-pink/30 transition hover:bg-kawaii-pink-deep">เข้าสู่ระบบ</button>
    </form>
    <?php if ($adminCount === 0): ?>
      <a href="bootstrap.php" class="mt-3 block w-full rounded-2xl border border-kawaii-pink px-5 py-3 text-center font-bold text-kawaii-pink-deep transition hover:bg-kawaii-pink-light">สร้างบัญชีแอดมินคนแรก</a>
    <?php endif; ?>
    <a href="../index.php" class="mt-5 block text-center text-sm text-kawaii-ink/60 hover:text-kawaii-pink-deep">← กลับไปหน้าร้าน</a>
  </main>
</body>
</html>
