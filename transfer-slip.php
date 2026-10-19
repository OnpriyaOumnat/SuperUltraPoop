<?php
require_once __DIR__ . '/includes/functions.php';

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId || $orderId < 1) {
    http_response_code(404);
    exit('ไม่พบสลิปโอนเงิน');
}

$statement = $pdo->prepare(
    'SELECT o.user_id, p.transfer_slip
     FROM orders o
     JOIN payments p ON p.order_id = o.id
     WHERE o.id = ? AND o.payment_method = \'transfer\'
     LIMIT 1'
);
$statement->execute([$orderId]);
$payment = $statement->fetch();
$isAdmin = !empty($_SESSION['admin_id']);
$isOrderOwner = !empty($_SESSION['user_id'])
    && $payment
    && (int)$payment['user_id'] === (int)$_SESSION['user_id'];

if (!$payment || (!$isAdmin && !$isOrderOwner) || empty($payment['transfer_slip'])) {
    http_response_code(404);
    exit('ไม่พบสลิปโอนเงิน');
}

if (!preg_match('#\Auploads/transfers/[a-f0-9]{32}\.(?:jpg|png|webp)\z#', $payment['transfer_slip'])) {
    http_response_code(404);
    exit('ไม่พบสลิปโอนเงิน');
}

$uploadDirectory = realpath(__DIR__ . '/uploads/transfers');
$slipPath = realpath(__DIR__ . '/' . $payment['transfer_slip']);
if (
    $uploadDirectory === false
    || $slipPath === false
    || strpos($slipPath, $uploadDirectory . DIRECTORY_SEPARATOR) !== 0
    || !is_file($slipPath)
) {
    http_response_code(404);
    exit('ไม่พบสลิปโอนเงิน');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($slipPath);
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowedTypes, true)) {
    http_response_code(404);
    exit('ไม่พบสลิปโอนเงิน');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($slipPath));
header('Content-Disposition: inline; filename="transfer-slip.' . pathinfo($slipPath, PATHINFO_EXTENSION) . '"');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($slipPath);
