<?php
require_once 'includes/functions.php';
if (empty($_SESSION['user_id'])) redirect('login.php');

$orderNo = trim($_GET['no'] ?? $_POST['no'] ?? '');
$orderStatement = $pdo->prepare(
    "SELECT o.*, p.status AS payment_status, p.transfer_amount, p.transfer_slip, p.transfer_submitted_at
     FROM orders o
     JOIN payments p ON p.order_id = o.id
     WHERE o.order_no = ? AND o.user_id = ? AND o.payment_method = 'transfer'
     LIMIT 1"
);
$orderStatement->execute([$orderNo, $_SESSION['user_id']]);
$order = $orderStatement->fetch();
if (!$order) {
    http_response_code(404);
    exit('ไม่พบคำสั่งซื้อ');
}
if ($order['payment_status'] === 'paid') redirect('order-success.php?no=' . urlencode($order['order_no']));

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $amountInput = trim($_POST['transfer_amount'] ?? '');
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $amountInput) || (float)$amountInput <= 0) {
        $errors[] = 'กรุณากรอกยอดโอนให้ถูกต้อง';
    }

    $slip = $_FILES['transfer_slip'] ?? null;
    if (!$slip || $slip['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'กรุณาแนบสลิปโอนเงิน';
    } elseif ($slip['error'] !== UPLOAD_ERR_OK) {
        $errors[] = $slip['error'] === UPLOAD_ERR_INI_SIZE || $slip['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'ไฟล์สลิปต้องมีขนาดไม่เกิน 2MB'
            : 'อัปโหลดสลิปไม่สำเร็จ กรุณาลองอีกครั้ง';
    } elseif ($slip['size'] > 2 * 1024 * 1024) {
        $errors[] = 'ไฟล์สลิปต้องมีขนาดไม่เกิน 2MB';
    } else {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($slip['tmp_name']);
        if (!isset($allowedTypes[$mime])) {
            $errors[] = 'รองรับไฟล์สลิป JPG, PNG หรือ WEBP เท่านั้น';
        }
    }

    $newSlipPath = null;
    if (!$errors) {
        $uploadDirectory = __DIR__ . '/uploads/transfers';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            $errors[] = 'ไม่สามารถเตรียมพื้นที่จัดเก็บสลิปได้';
        } else {
            $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
            if (!move_uploaded_file($slip['tmp_name'], $uploadDirectory . '/' . $filename)) {
                $errors[] = 'บันทึกไฟล์สลิปไม่สำเร็จ';
            } else {
                $newSlipPath = 'uploads/transfers/' . $filename;
            }
        }
    }

    if (!$errors) {
        $update = $pdo->prepare(
            "UPDATE payments
             SET transfer_amount = ?, transfer_reference = NULL, transfer_slip = ?, transfer_submitted_at = NOW()
             WHERE order_id = ? AND status = 'pending'"
        );
        try {
            $update->execute([(float)$amountInput, $newSlipPath, $order['id']]);
        } catch (PDOException $exception) {
            if ($newSlipPath !== null && is_file(__DIR__ . '/' . $newSlipPath)) {
                unlink(__DIR__ . '/' . $newSlipPath);
            }
            throw $exception;
        }
        if ($update->rowCount() !== 1) {
            if ($newSlipPath !== null && is_file(__DIR__ . '/' . $newSlipPath)) {
                unlink(__DIR__ . '/' . $newSlipPath);
            }
            $errors[] = 'ไม่สามารถบันทึกข้อมูลได้ เนื่องจากสถานะการชำระเงินเปลี่ยนแปลงแล้ว กรุณาโหลดหน้าใหม่';
        } else {
            if (
                is_string($order['transfer_slip'])
                && preg_match('#\Auploads/transfers/[a-f0-9]{32}\.(?:jpg|png|webp)\z#', $order['transfer_slip'])
                && is_file(__DIR__ . '/' . $order['transfer_slip'])
            ) {
                unlink(__DIR__ . '/' . $order['transfer_slip']);
            }
            flash('บันทึกข้อมูลการโอนแล้ว กรุณารอร้านตรวจสอบยอดเงิน');
            redirect('transfer-payment.php?no=' . urlencode($order['order_no']));
        }
    }
}

$pageTitle = 'แจ้งชำระเงิน';
require 'includes/header.php';
?>
<div class="two">
  <section class="box">
    <h1>ชำระเงินด้วย QR</h1>
    <p>เลขที่คำสั่งซื้อ: <b><?= h($order['order_no']) ?></b></p>
    <p>ยอดที่ต้องโอน: <b><?= money($order['total']) ?></b></p>
    <img src="image/QRcode.png" alt="QR Code สำหรับโอนเงิน" style="display:block;width:min(100%,360px);height:auto;margin:18px auto">
    <p class="muted small">กรุณาตรวจสอบชื่อผู้รับและยอดเงินในแอปธนาคารก่อนยืนยันการโอน</p>
  </section>
  <section class="box">
    <h2>แจ้งข้อมูลการโอน</h2>
    <?php if ($order['payment_status'] === 'failed'): ?>
      <div class="alert err">คำสั่งซื้อนี้ถูกยกเลิกหรือการชำระเงินไม่สำเร็จ กรุณาติดต่อร้าน</div>
    <?php elseif ($order['transfer_submitted_at']): ?>
      <div class="alert">แจ้งโอนแล้วเมื่อ <?= h($order['transfer_submitted_at']) ?> — สถานะ: รอตรวจสอบจากร้าน</div>
      <p>ยอดที่แจ้งโอน: <b><?= money($order['transfer_amount']) ?></b></p>
      <?php if ($order['transfer_slip']): ?>
        <p>สลิปโอนเงิน: <a href="transfer-slip.php?id=<?= (int)$order['id'] ?>" target="_blank" rel="noopener" class="order-link">เปิดดูสลิป</a></p>
      <?php endif; ?>
      <p class="muted">หน้านี้จะตรวจสอบสถานะให้อัตโนมัติ และไปหน้าสั่งซื้อสำเร็จเมื่อร้านยืนยันการรับเงิน</p>
      <meta http-equiv="refresh" content="10">
    <?php else: ?>
      <p>หลังโอนเงินแล้ว กรอกยอดและแนบสลิปเพื่อให้ร้านตรวจสอบรายการได้</p>
      <?php foreach ($errors as $error): ?><div class="alert err"><?= h($error) ?></div><?php endforeach; ?>
      <form method="post" enctype="multipart/form-data" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="no" value="<?= h($order['order_no']) ?>">
        <label for="transfer_amount">ยอดเงินที่โอน (บาท)</label>
        <input id="transfer_amount" name="transfer_amount" type="number" min="0.01" step="0.01" required value="<?= h($_POST['transfer_amount'] ?? $order['total']) ?>">
        <label for="transfer_slip">แนบสลิปโอนเงิน (JPG, PNG หรือ WEBP ไม่เกิน 2MB)</label>
        <input id="transfer_slip" name="transfer_slip" type="file" accept="image/jpeg,image/png,image/webp" required>
        <p><button class="btn">แจ้งโอนเงิน</button></p>
      </form>
    <?php endif; ?>
  </section>
</div>
<?php require 'includes/footer.php'; ?>
