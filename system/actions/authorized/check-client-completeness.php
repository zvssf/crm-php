<?php
$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    echo json_encode(['is_complete' => false, 'message' => 'Неверный ID анкеты.']);
    exit;
}

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM `clients` WHERE `client_id` = :client_id");
    $stmt->execute([':client_id' => $client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        echo json_encode(['is_complete' => false, 'message' => 'Анкета не найдена.']);
        exit;
    }

    // --- НАЧАЛО БЛОКА ДИНАМИЧЕСКОЙ ПРОВЕРКИ ---
    $center_id = $client['center_id'];

    // Получаем список обязательных полей из настроек ВЦ
    $stmt_fields = $pdo->prepare("SELECT `field_name` FROM `settings_center_fields` WHERE `center_id` = :center_id AND `is_required` = 1");
    $stmt_fields->execute([':center_id' => $center_id]);
    $required_center_fields = array_column($stmt_fields->fetchAll(PDO::FETCH_ASSOC), 'field_name');

    // Добавляем поля, которые всегда обязательны
    $always_required = ['first_name', 'last_name', 'passport_number', 'sale_price'];
    $fields_to_check = array_unique(array_merge($always_required, $required_center_fields));

    // Проверяем заполненность полей
    foreach ($fields_to_check as $field) {
        if ($field === 'phone' && (empty($client['phone_code']) || empty($client['phone_number']))) {
            echo json_encode(['is_complete' => false]); exit;
        }
        if (!isset($client[$field]) || $client[$field] === '' || $client[$field] === null) {
            echo json_encode(['is_complete' => false]); exit;
        }
    }

    // Проверяем наличие категорий
    $stmt_cities = $pdo->prepare("SELECT COUNT(*) FROM `client_cities` WHERE `client_id` = :client_id");
    $stmt_cities->execute([':client_id' => $client_id]);
    if ($stmt_cities->fetchColumn() == 0) {
        echo json_encode(['is_complete' => false]); exit;
    }

    // Проверяем наличие агента
    if (empty($client['agent_id'])) {
        echo json_encode(['is_complete' => false]); exit;
    }
    
    // Если все проверки пройдены
    echo json_encode(['is_complete' => true]);
    // --- КОНЕЦ БЛОКА ДИНАМИЧЕСКОЙ ПРОВЕРКИ ---

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    echo json_encode(['is_complete' => false, 'message' => 'Ошибка базы данных.']);
}
$pdo = null;
exit;