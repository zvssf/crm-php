<?php
header('Content-Type: application/json');

$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    echo json_encode(['error' => 'Недопустимое значение ID анкеты!']);
    exit;
}

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare("
        SELECT sc.city_id, sc.city_name, sc.city_category
        FROM `client_cities` cc
        JOIN `settings_cities` sc ON cc.city_id = sc.city_id
        WHERE cc.client_id = :client_id
    ");
    $stmt->execute([':client_id' => $client_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categories);

} catch (PDOException $e) {
    error_log('DB Error fetching client categories: ' . $e->getMessage());
    echo json_encode(['error' => 'Ошибка при загрузке категорий.']);
}
exit;