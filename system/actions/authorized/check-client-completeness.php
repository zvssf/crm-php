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

    $required_fields = [
        'first_name', 'last_name', 'middle_name', 'gender', 'phone', 'email',
        'passport_number', 'birth_date', 'passport_expiry_date', 'nationality',
        'visit_date_start', 'visit_date_end', 'days_until_visit'
    ];

    $is_complete = true;
    foreach ($required_fields as $field) {
        if (empty($client[$field])) {
            $is_complete = false;
            break;
        }
    }

    echo json_encode(['is_complete' => $is_complete]);

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    echo json_encode(['is_complete' => false, 'message' => 'Ошибка базы данных.']);
}
exit;