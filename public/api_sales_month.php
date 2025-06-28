<?php
require __DIR__ . '/../config/init.php';
require __DIR__ . '/../src/auth.php';
requireRole(['Admin']);

$year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
if ($month < 1 || $month > 12) { $month = (int)date('n'); }
if ($year < 2000 || $year > 2100) { $year = (int)date('Y'); }

$start = sprintf('%04d-%02d-01', $year, $month);
$end   = date('Y-m-d', strtotime("$start +1 month"));

$stmt = $pdo->prepare(
    "SELECT DATE(paid_at) AS day,
            SUM(amount) AS total,
            SUM(CASE WHEN method='cash' THEN amount ELSE 0 END) AS cash_total,
            SUM(CASE WHEN method='card' THEN amount ELSE 0 END) AS card_total
       FROM payments
      WHERE paid_at >= ? AND paid_at < ?
      GROUP BY DATE(paid_at)
      ORDER BY DATE(paid_at)"
);
$stmt->execute([$start, $end]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$data = [];
for ($i = 1; $i <= $daysInMonth; $i++) {
    $data[$i] = ['total' => 0, 'cash' => 0, 'card' => 0];
}
foreach ($rows as $r) {
    $day = (int)substr($r['day'], -2);
    $data[$day] = [
        'total' => (float)$r['total'],
        'cash'  => (float)$r['cash_total'],
        'card'  => (float)$r['card_total']
    ];
}

$result = [
    'days'  => array_keys($data),
    'total' => array_column($data, 'total'),
    'cash'  => array_column($data, 'cash'),
    'card'  => array_column($data, 'card')
];

header('Content-Type: application/json');
echo json_encode($result);
