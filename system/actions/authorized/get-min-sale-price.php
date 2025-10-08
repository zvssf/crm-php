<?php
header('Content-Type: application/json');

$city_ids = $_POST['city_ids'] ?? [];

if (empty($city_ids) || !is_array($city_ids)) {
    echo json_encode(['success' => false, 'min_price' => 0]);
    exit;
}

$params = [];
foreach ($city_ids as $id) {
    if (is_numeric($id)) {
        $params[] = (int)$id;
    }
}

if (empty($params)) {
    echo json_encode(['success' => false, 'min_price' => 0]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($params), '?'));

try {
    $pdo = db_connect();
    
    $min_price = 0;

    echo json_encode(['success' => true, 'min_price' => $min_price]);

} catch (PDOException $e) {
    error_log('DB Error get-min-sale-price: ' . $e->getMessage());
    echo json_encode(['success' => false, 'min_price' => 0]);
}
exit;
?>