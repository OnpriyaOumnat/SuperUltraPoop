<?php
require_once __DIR__ . '/../includes/functions.php';
admin_required();

header('Content-Type: application/json; charset=utf-8');

// ย้อนหลัง 30 วันนับจากวันนี้ (เวลาไทย)
$now = new DateTimeImmutable('today');
$start = $now->modify('-29 days');
$endExclusive = $now->modify('+1 day');

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
$statement->execute([$start->format('Y-m-d 00:00:00'), $endExclusive->format('Y-m-d 23:59:59')]);
$dailySales = $statement->fetchAll(PDO::FETCH_KEY_PAIR);

$labels = [];
$values = [];
$days = 30;
for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
    $day = $start->modify("+$dayOffset days");
    $labels[] = $day->format('d/m');
    $values[] = (float)($dailySales[$day->format('Y-m-d')] ?? 0);
}

$formatThaiDate = static fn(DateTimeImmutable $date): string =>
    $date->format('d/m/') . substr((string)($date->format('Y') + 543), -2);
$end = $now;
$period = 'ช่วง ' . $formatThaiDate($start) . ' - ' . $formatThaiDate($end)
    . ' · อัปเดตอัตโนมัติทุก 1 นาที · นับจากคำสั่งซื้อที่ชำระเงินแล้ว';

echo json_encode(
    ['labels' => $labels, 'values' => $values, 'period' => $period],
    JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
