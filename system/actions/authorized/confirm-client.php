<?php
$client_id = valid($_POST['client-id'] ?? '');
$final_city_id = valid($_POST['final-city-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

$appointment_datetime_raw = valid($_POST['appointment_datetime'] ?? '');
$appointment_datetime_db = null;

if (empty($appointment_datetime_raw)) {
    message('Ошибка', 'Необходимо указать дату и время записи!', 'error', '');
} else {
    try {
        $dt = new DateTime($appointment_datetime_raw);
        $appointment_datetime_db = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        message('Ошибка', 'Неверный формат даты и времени!', 'error', '');
    }
}

if (empty($final_city_id) || !preg_match('/^[0-9]{1,11}$/u', $final_city_id)) {
    message('Ошибка', 'Необходимо выбрать финальную категорию!', 'error', '');
}

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    // 1. Удаляем все старые категории для этой анкеты
    $stmt_delete = $pdo->prepare("DELETE FROM `client_cities` WHERE `client_id` = :client_id");
    $stmt_delete->execute([':client_id' => $client_id]);

    // 2. Добавляем одну единственную выбранную категорию
    $stmt_insert = $pdo->prepare("INSERT INTO `client_cities` (`client_id`, `city_id`) VALUES (:client_id, :city_id)");
    $stmt_insert->execute([':client_id' => $client_id, ':city_id' => $final_city_id]);

    // 3. Получаем данные анкеты (агент, стоимость, паспорт, имя, центр)
    $stmt_client = $pdo->prepare("SELECT `agent_id`, `sale_price`, `passport_number`, `client_name`, `center_id` FROM `clients` WHERE `client_id` = :client_id FOR UPDATE");
    $stmt_client->execute([':client_id' => $client_id]);
    $client_info = $stmt_client->fetch(PDO::FETCH_ASSOC);

    // 4. Определяем параметры для обновления
    $recording_uid = uniqid();
    $payment_status = 0;
    $paid_from_balance = 0.00;
    
    // 5. Логика списания средств и определения статуса оплаты
    if ($client_info && !empty($client_info['agent_id']) && !empty($client_info['sale_price']) && $client_info['sale_price'] > 0) {
        $agent_id = $client_info['agent_id'];
        $sale_price = (float) $client_info['sale_price'];

        $stmt_agent = $pdo->prepare("SELECT `user_balance` FROM `users` WHERE `user_id` = :agent_id FOR UPDATE");
        $stmt_agent->execute([':agent_id' => $agent_id]);
        $agent_balance = (float) $stmt_agent->fetchColumn();
        
        if ($agent_balance >= $sale_price) {
            // Сценарий 1: Денег достаточно
            $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :sale_price WHERE `user_id` = :agent_id")
                ->execute([':sale_price' => $sale_price, ':agent_id' => $agent_id]);
            
            $payment_status = 1; // Оплачено
            $paid_from_balance = $sale_price;
        }
        // Сценарий 2 (Денег недостаточно) уже покрыт значениями по умолчанию ($payment_status = 0)
    }

    // 6. Единый запрос на обновление анкеты
    $stmt_update_client = $pdo->prepare(
        "UPDATE `clients` SET 
            `client_status` = 2, 
            `recording_uid` = :recording_uid,
            `payment_status` = :payment_status,
            `paid_from_balance` = :paid_from_balance,
            `paid_from_credit` = 0.00,
            `appointment_datetime` = :appointment_datetime
        WHERE `client_id` = :client_id AND `client_status` = 1"
    );
    $stmt_update_client->execute([
        ':recording_uid' => $recording_uid,
        ':payment_status' => $payment_status,
        ':paid_from_balance' => $paid_from_balance,
        ':appointment_datetime' => $appointment_datetime_db,
        ':client_id' => $client_id
    ]);

    // 6. Списываем себестоимость с баланса привязанных поставщиков
    $stmt_city_cost = $pdo->prepare("SELECT `cost_price` FROM `settings_cities` WHERE `city_id` = :city_id");
    $stmt_city_cost->execute([':city_id' => $final_city_id]);
    $cost_price = $stmt_city_cost->fetchColumn();

    if ($cost_price > 0) {
        $stmt_suppliers = $pdo->prepare("SELECT `supplier_id` FROM `city_suppliers` WHERE `city_id` = :city_id");
        $stmt_suppliers->execute([':city_id' => $final_city_id]);
        $supplier_ids = $stmt_suppliers->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($supplier_ids)) {
            $stmt_update_supplier = $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` - :cost WHERE `id` = :supplier_id");
            foreach ($supplier_ids as $supplier_id) {
                $stmt_update_supplier->execute([':cost' => $cost_price, ':supplier_id' => $supplier_id]);
            }
        }
    }

    // 7. Отменяем остальные анкеты-дубликаты (статус "В работе") и уведомляем агентов
    if ($client_info && !empty($client_info['passport_number'])) {
        // Сначала находим ID дубликатов и их агентов, чтобы отправить уведомления
        $stmt_find_dups = $pdo->prepare("
            SELECT client_id, agent_id, center_id, client_name 
            FROM `clients` 
            WHERE `passport_number` = :passport_number 
              AND `client_id` != :client_id 
              AND `client_status` = 1
        ");
        $stmt_find_dups->execute([
            ':passport_number' => $client_info['passport_number'],
            ':client_id'       => $client_id
        ]);
        $duplicates = $stmt_find_dups->fetchAll(PDO::FETCH_ASSOC);

        // Выполняем отмену
        $stmt_cancel_duplicates = $pdo->prepare(
            "UPDATE `clients` 
             SET `client_status` = 7 
             WHERE `passport_number` = :passport_number 
               AND `client_id` != :client_id 
               AND `client_status` = 1"
        );
        $stmt_cancel_duplicates->execute([
            ':passport_number' => $client_info['passport_number'],
            ':client_id'       => $client_id
        ]);

        // Рассылаем уведомления
        foreach ($duplicates as $dup) {
            if ($dup['agent_id']) {
                send_notification(
                    $pdo,
                    $dup['agent_id'],
                    'Заявка отменена (Дубликат)',
                    "Анкета '{$dup['client_name']}' (ID: {$dup['client_id']}) отменена, так как была выбрана другая заявка с этим паспортом.",
                    'warning',
                    "/?page=clients&center={$dup['center_id']}&status=7"
                );
            }
        }
    }

    // 8. Уведомляем агента об успешной записи
    if ($client_info && !empty($client_info['agent_id'])) {
        send_notification(
            $pdo,
            $client_info['agent_id'],
            'Анкета записана',
            "Ваша анкета '{$client_info['client_name']}' (ID: {$client_id}) успешно переведена в статус 'Записанные'.",
            'success',
            "/?page=clients&center={$client_info['center_id']}&status=2"
        );
    }

    $pdo->commit();

    message('Уведомление', 'Анкета отмечена как "Записанная"!', 'success', '');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось обновить статус анкеты.', 'error', '');
}