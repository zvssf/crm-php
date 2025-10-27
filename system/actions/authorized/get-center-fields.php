<?php
header('Content-Type: application/json');

$center_id = valid($_POST['center_id'] ?? '');

if (empty($center_id) || !preg_match('/^[0-9]{1,11}$/u', $center_id)) {
    echo json_encode(['error' => 'Недопустимый ID визового центра!']);
    exit;
}

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare("
        SELECT `field_name`, `is_visible`, `is_required` 
        FROM `settings_center_fields` 
        WHERE `center_id` = :center_id
    ");
    $stmt->execute([':center_id' => $center_id]);
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
    error_log('DB Error get-center-fields: ' . $e->getMessage());
    echo json_encode(['error' => 'Ошибка базы данных.']);
}
exit;