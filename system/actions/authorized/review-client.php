<?php
$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

try {
    $pdo = db_connect();

    // Шаг 1: Проверка на заполненность
    $stmt_check = $pdo->prepare("SELECT * FROM `clients` WHERE `client_id` = :client_id");
    $stmt_check->execute([':client_id' => $client_id]);
    $client = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        message('Ошибка', 'Анкета не найдена.', 'error', '');
    }

    $required_fields = [
        'first_name', 'last_name', 'gender', 'phone', 'email',
        'passport_number', 'birth_date', 'passport_expiry_date', 'nationality',
        'visit_date_start', 'visit_date_end', 'days_until_visit'
    ];
    foreach ($required_fields as $field) {
        if (empty($client[$field])) {
            message('Ошибка', 'Анкета заполнена не полностью. Отправка на рассмотрение невозможна.', 'error', '');
        }
    }

    // Шаг 2: Определяем следующий статус на основе того, КТО выполняет действие
    // Если действие выполняет Менеджер (группа 3), анкета идет к Директору (статус 5).
    // Если действие выполняет Агент (группа 4), анкета идет к Менеджеру (статус 6).
    $next_status = match ((int)$user_data['user_group']) {
        3 => 5, // от Менеджера -> к Директору
        4 => 6, // от Агента -> к Менеджеру
        default => 5, // По умолчанию (например, если Директор отправляет чужой черновик)
    };

    // Шаг 4: Обновляем статус анкеты
    $stmt_update = $pdo->prepare("UPDATE `clients` SET `client_status` = :next_status WHERE `client_id` = :client_id AND `client_status` = 3");
    $stmt_update->execute([':next_status' => $next_status, ':client_id' => $client_id]);

    message('Уведомление', 'Анкета отправлена на рассмотрение!', 'success', '');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось отправить анкету на рассмотрение.', 'error', '');
}
$pdo = null;