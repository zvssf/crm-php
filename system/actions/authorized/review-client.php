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

    // --- ЛОГИКА УВЕДОМЛЕНИЙ ---
    // Если Агент (4) отправляет Менеджеру (статус 6)
    if ($user_data['user_group'] == 4 && $next_status == 6) {
        // Находим менеджера этого агента
        $stmt_manager = $pdo->prepare("SELECT user_supervisor FROM users WHERE user_id = :agent_id");
        $stmt_manager->execute([':agent_id' => $user_data['user_id']]);
        $manager_id = $stmt_manager->fetchColumn();

        if ($manager_id) {
            send_notification(
                $pdo, 
                $manager_id, 
                'Новая анкета на проверку', 
                "Агент {$user_data['user_firstname']} {$user_data['user_lastname']} отправил анкету №{$client_id} на проверку.", 
                'info', 
                "/?page=clients&center={$client['center_id']}&status=6"
            );
        }
    }

    elseif ($user_data['user_group'] == 3 && $next_status == 5) {
        // Находим всех директоров
        $stmt_directors = $pdo->query("SELECT user_id FROM users WHERE user_group = 1 AND user_status = 1");
        while ($dir_id = $stmt_directors->fetchColumn()) {
            send_notification(
                $pdo, 
                $dir_id, 
                'Акета на рассмотрении', 
                "Менеджер {$user_data['user_firstname']} {$user_data['user_lastname']} отправил анкету №{$client_id} на проверку.", 
                'info', 
                "/?page=clients&center={$client['center_id']}&status=5"
            );
        }
    }

    message('Уведомление', 'Анкета отправлена на рассмотрение!', 'success', '');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось отправить анкету на рассмотрение.', 'error', '');
}
$pdo = null;