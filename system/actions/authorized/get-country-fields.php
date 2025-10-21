<?php
header('Content-Type: application/json');

$country_id = valid($_POST['country_id'] ?? '');

if (empty($country_id) || !preg_match('/^[0-9]{1,11}$/u', $country_id)) {
    echo json_encode(['error' => 'Недопустимый ID страны!']);
    exit;
}

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare("
        SELECT `field_name`, `is_visible`, `is_required` 
        FROM `settings_country_fields` 
        WHERE `country_id` = :country_id
    ");
    $stmt->execute([':country_id' => $country_id]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $settings = [];
    foreach ($fields as $field) {
        $settings[$field['field_name']] = [
            'is_visible'  => (bool)$field['is_visible'],
            'is_required' => (bool)$field['is_required']
        ];
    }
    
    echo json_encode($settings);

} catch (PDOException $e) {
    error_log('DB Error get-country-fields: ' . $e->getMessage());
    echo json_encode(['error' => 'Ошибка базы данных.']);
}
exit;