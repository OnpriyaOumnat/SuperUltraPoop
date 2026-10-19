<?php
require_once __DIR__ . '/../includes/functions.php';
admin_required();

$currentAdmin = $pdo->prepare('SELECT id FROM admins WHERE id = ?');
$currentAdmin->execute([(int)$_SESSION['admin_id']]);
if (!$currentAdmin->fetchColumn()) {
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
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
        $exists = $pdo->prepare('SELECT 1 FROM admins WHERE username = ?');
        $exists->execute([$username]);
        if ($exists->fetchColumn()) {
            $errors[] = 'ชื่อผู้ใช้นี้มีคนใช้งานแล้ว';
        } else {
            try {
                $create = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
                $create->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                flash('สร้างบัญชีแอดมินเรียบร้อยแล้ว');
                redirect('create-admin.php');
            } catch (PDOException $e) {
                $driverCode = $e->errorInfo[1] ?? null;
                if ($e->getCode() === '23000' && (int)$driverCode === 1062) {
                    $errors[] = 'ชื่อผู้ใช้นี้มีคนใช้งานแล้ว';
                } else {
                    throw $e;
                }
            }
        }
    }
}

$admins = $pdo->query('SELECT id, username FROM admins ORDER BY id')->fetchAll();
$pageTitle = 'จัดการแอดมิน';
require __DIR__ . '/header.php';
?>
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
  <section class="rounded-3xl border border-kawaii-pink-light bg-white p-5 shadow-sm sm:p-7">
    <div class="mb-6">
      <span class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-kawaii-pink-light text-2xl">🔐</span>
      <h2 class="text-xl font-bold">สร้างบัญชีแอดมิน</h2>
      <p class="mt-1 text-sm text-kawaii-ink/60">หน้านี้สำหรับแอดมินที่เข้าสู่ระบบแล้วเท่านั้น</p>
    </div>

    <?php foreach ($errors as $error): ?>
      <p class="mb-3 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700" role="alert"><?= h($error) ?></p>
    <?php endforeach; ?>

    <form method="post" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label for="username" class="mb-1 block text-sm font-semibold">ชื่อผู้ใช้</label>
        <input id="username" name="username" value="<?= h($username) ?>" autocomplete="username" minlength="3" maxlength="32" pattern="[a-zA-Z0-9_.-]{3,32}" required class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none transition focus:border-kawaii-pink focus:ring-2 focus:ring-kawaii-pink/20">
        <p class="mt-1 text-xs text-kawaii-ink/60">3–32 ตัวอักษร ใช้ภาษาอังกฤษ ตัวเลข จุด ขีดกลาง หรือขีดล่าง</p>
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
      <button class="w-full rounded-2xl bg-kawaii-pink px-5 py-3 font-bold text-white shadow-md shadow-kawaii-pink/30 transition hover:bg-kawaii-pink-deep">สร้างบัญชี</button>
    </form>
  </section>

  <section class="overflow-hidden rounded-3xl border border-kawaii-pink-light bg-white shadow-sm">
    <div class="border-b border-kawaii-pink-light px-5 py-4 sm:px-6">
      <h2 class="text-lg font-bold">บัญชีแอดมิน</h2>
      <p class="text-sm text-kawaii-ink/60">มีแอดมินทั้งหมด <?= number_format(count($admins)) ?> บัญชี</p>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="bg-kawaii-cream/70 text-kawaii-ink/70">
          <tr>
            <th class="px-5 py-3 font-semibold">ลำดับ</th>
            <th class="px-5 py-3 font-semibold">ชื่อผู้ใช้</th>
            <th class="px-5 py-3 font-semibold">สถานะ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-kawaii-pink-light">
          <?php foreach ($admins as $admin): ?>
            <tr class="hover:bg-kawaii-cream/40">
              <td class="px-5 py-4"><?= (int)$admin['id'] ?></td>
              <td class="px-5 py-4 font-semibold"><?= h($admin['username']) ?></td>
              <td class="px-5 py-4">
                <?php if ((int)$admin['id'] === (int)$_SESSION['admin_id']): ?>
                  <span class="rounded-full bg-kawaii-mint px-3 py-1 text-xs font-semibold">บัญชีของคุณ</span>
                <?php else: ?>
                  <span class="rounded-full bg-kawaii-blue px-3 py-1 text-xs font-semibold">แอดมิน</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php require __DIR__ . '/footer.php'; ?>
