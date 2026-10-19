<?php
$routes = [
    'index.php' => 'index.php',
    'products.php' => 'products.php',
    'product-form.php' => 'product-form.php',
    'product-detail.php' => 'product-form.php',
    'orders.php' => 'orders.php',
    'order-detail.php' => 'order-detail.php',
    'categories.php' => 'products.php',
    'login.php' => 'login.php',
    'logout.php' => 'logout.php',
];

$legacyPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (!isset($routes[$legacyPage])) {
    http_response_code(404);
    exit('ไม่พบหน้า');
}

$query = [];
$stringParameters = match ($legacyPage) {
    'products.php' => ['q', 'cat'],
    'orders.php' => ['q', 'status', 'from', 'to'],
    default => [],
};
foreach ($stringParameters as $parameter) {
    if (isset($_GET[$parameter]) && is_string($_GET[$parameter])) {
        $query[$parameter] = $_GET[$parameter];
    }
}

if (in_array($legacyPage, ['product-form.php', 'product-detail.php', 'order-detail.php'], true)) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id && $id > 0) {
        $query['id'] = $id;
    }
}

$destination = '../admin/' . $routes[$legacyPage];
if ($query) {
    $destination .= '?' . http_build_query($query);
}
header('Location: ' . $destination, true, 302);
exit;
