<?php
require_once __DIR__ . '/../includes/functions.php';
admin_required();

header('Content-Type: application/json; charset=utf-8');

// รับค่าเดือนที่ต้องการดู เช่น 2026-10 (ถ้าไม่ส่งมา ให้ใช้เดือนปัจจุบัน)
$monthInput = trim($_GET['month'] ?? '');
if (!preg_match('/^\d{4}-\d{2}$/', $monthInput)) {
    $monthInput = date('Y-m');
}

try {
    $firstDay = new DateTimeImmutable($monthInput . '-01');
} catch (Exception $e) {
    $firstDay = new DateTimeImmutable(date('Y-m-01'));
}

$daysInMonth = (int)$firstDay->format('t'); // จำนวนวันในเดือนนั้น (เช่น 28, 30, 31)
$start = $firstDay;
$endExclusive = $firstDay->modify('+1 month');

// ดึงยอดขายของแต่ละวันในเดือนที่เลือก
$statement = $pdo->prepare("
    SELECT DATE(CONVERT_TZ(COALESCE(p.paid_at, o.created_at), '+00:00', '+07:00')) AS day, 
           SUM(o.total) AS total
    FROM orders o
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE o.status IN ('Paid','Processing','Shipped','Completed')
      AND COALESCE(p.paid_at, o.created_at) >= ?
      AND COALESCE(p.paid_at, o.created_at) < ?
    GROUP BY day
    ORDER BY day
");
$statement->execute([$start->format('Y-m-d 00:00:00'), $endExclusive->format('Y-m-d 00:00:00')]);
$dailySales = $statement->fetchAll(PDO::FETCH_KEY_PAIR);

$labels = [];
$values = [];
$monthlyTotal = 0;

for ($d = 1; $d <= $daysInMonth; $d++) {
    $currentDay = $firstDay->modify('+' . ($d - 1) . ' days');
    $dayKey = $currentDay->format('Y-m-d');
    $val = (float)($dailySales[$dayKey] ?? 0);
    
    $labels[] = $currentDay->format('d/m');
    $values[] = $val;
    $monthlyTotal += $val;
}

// ชื่อเดือนภาษาไทย
$thaiMonths = [
    '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
    '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
    '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
];
$mNum = $firstDay->format('m');
$yearBE = (int)$firstDay->format('Y') + 543;
$period = 'ประจำเดือน ' . ($thaiMonths[$mNum] ?? '') . ' ' . $yearBE . ' · ยอดรวมทั้งเดือน: ฿' . number_format($monthlyTotal, 2);

echo json_encode(
    ['labels' => $labels, 'values' => $values, 'period' => $period, 'total' => $monthlyTotal],
    JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
