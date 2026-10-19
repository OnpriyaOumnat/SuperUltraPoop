<?php
require_once __DIR__ . '/../includes/functions.php';
admin_required();

header('Content-Type: application/json; charset=utf-8');

$start = new DateTimeImmutable('2026-10-15');
$endExclusive = $start->modify('+30 days');

$statement = $pdo->prepare("
    SELECT DATE(COALESCE(p.paid_at, o.created_at)) AS day, SUM(o.total) AS total
    FROM orders o
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE o.status IN ('Paid','Processing','Shipped','Completed')
      AND COALESCE(p.paid_at, o.created_at) >= ?
      AND COALESCE(p.paid_at, o.created_at) < ?
    GROUP BY DATE(COALESCE(p.paid_at, o.created_at))
    ORDER BY day
");
$statement->execute([$start->format('Y-m-d'), $endExclusive->format('Y-m-d')]);
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
$end = $endExclusive->modify('-1 day');
$period = 'ช่วง ' . $formatThaiDate($start) . ' - ' . $formatThaiDate($end)
    . ' · อัปเดตอัตโนมัติทุก 1 นาที · นับจากวันที่ชำระเงิน (ถ้าไม่มีวันที่ชำระ ใช้วันที่สั่งซื้อ)';

echo json_encode(
    ['labels' => $labels, 'values' => $values, 'period' => $period],
    JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
