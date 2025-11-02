<?php
$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

try {
    $pdo = db_connect();

    // Шаг 1: Получение данных анкеты
    $stmt_check = $pdo->prepare("SELECT * FROM `clients` WHERE `client_id` = :client_id");
    $stmt_check->execute([':client_id' => $client_id]);
    $client = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        message('Ошибка', 'Анкета не найдена.', 'error', '');
    }

    // Шаг 2: Динамическая проверка на заполненность (дублирует логику check-client-completeness для надежности)
    $center_id = $client['center_id'];

    $stmt_fields = $pdo->prepare("SELECT `field_name` FROM `settings_center_fields` WHERE `center_id` = :center_id AND `is_required` = 1");
    $stmt_fields->execute([':center_id' => $center_id]);
    $required_center_fields = array_column($stmt_fields->fetchAll(PDO::FETCH_ASSOC), 'field_name');

    $always_required = ['first_name', 'last_name', 'passport_number', 'sale_price'];
    $fields_to_check = array_unique(array_merge($always_required, $required_center_fields));

    foreach ($fields_to_check as $field) {
        if ($field === 'phone' && (empty($client['phone_code']) || empty($client['phone_number']))) {
            message('Ошибка', 'Анкета заполнена не полностью. Отправка на рассмотрение невозможна.', 'error', '');
        }
        if ($field === 'visit_dates' && (empty($client['visit_date_start']) || empty($client['visit_date_end']))) {
            message('Ошибка', 'Анкета заполнена не полностью. Отправка на рассмотрение невозможна.', 'error', '');
        }
        if (!isset($client[$field]) || $client[$field] === '' || $client[$field] === null) {
            message('Ошибка', 'Анкета заполнена не полностью. Отправка на рассмотрение невозможна.', 'error', '');
        }
    }

    $stmt_cities = $pdo->prepare("SELECT COUNT(*) FROM `client_cities` WHERE `client_id` = :client_id");
    $stmt_cities->execute([':client_id' => $client_id]);
    if ($stmt_cities->fetchColumn() == 0) {
        message('Ошибка', 'Анкета заполнена не полностью. Необходимо выбрать хотя бы одну категорию.', 'error', '');
    }

    if (empty($client['agent_id'])) {
        message('Ошибка', 'Анкета заполнена не полностью. Необходимо назначить агента.', 'error', '');
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