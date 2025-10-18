<?php
// 1. Получение ID и Проверка Прав
$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

if ($user_data['user_group'] != 1) {
    message('Ошибка', 'У вас нет прав для выполнения этого действия!', 'error', '');
}

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    // 2. Получение Данных Анкеты
    $stmt_client = $pdo->prepare(
        "SELECT c.agent_id, c.sale_price, c.payment_status, cc.city_id 
         FROM `clients` c
         LEFT JOIN `client_cities` cc ON c.client_id = cc.client_id
         WHERE c.client_id = :client_id AND c.client_status = 2"
    );
    $stmt_client->execute([':client_id' => $client_id]);
    $client_info = $stmt_client->fetch(PDO::FETCH_ASSOC);

    // 3. Валидация
    if (!$client_info) {
        message('Ошибка', 'Анкета не найдена или уже не находится в статусе "Записанные".', 'error', '');
    }

    $agent_id = $client_info['agent_id'];
    $sale_price = (float) $client_info['sale_price'];
    $payment_status = (int) $client_info['payment_status'];
    $final_city_id = $client_info['city_id'];

    // 4. Финансовая Корректировка — Агент
    if ($agent_id && $sale_price > 0 && in_array($payment_status, [1, 2])) {
        $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` + :sale_price WHERE `user_id` = :agent_id")
            ->execute([':sale_price' => $sale_price, ':agent_id' => $agent_id]);
    }

    // 5. Финансовая Корректировка — Поставщик
    if ($final_city_id) {
        $stmt_city_cost = $pdo->prepare("SELECT `cost_price` FROM `settings_cities` WHERE `city_id` = :city_id");
        $stmt_city_cost->execute([':city_id' => $final_city_id]);
        $cost_price = (float) $stmt_city_cost->fetchColumn();

        if ($cost_price > 0) {
            $stmt_suppliers = $pdo->prepare("SELECT `supplier_id` FROM `city_suppliers` WHERE `city_id` = :city_id");
            $stmt_suppliers->execute([':city_id' => $final_city_id]);
            $supplier_ids = $stmt_suppliers->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($supplier_ids)) {
                $stmt_update_supplier = $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` + :cost WHERE `id` = :supplier_id");
                foreach ($supplier_ids as $supplier_id) {
                    $stmt_update_supplier->execute([':cost' => $cost_price, ':supplier_id' => $supplier_id]);
                }
            }
        }
    }

    // 6. Обновление Статуса Анкеты
    $stmt_update_client = $pdo->prepare(
        "UPDATE `clients` SET 
            `client_status` = 1, 
            `payment_status` = 0, 
            `paid_from_balance` = 0.00, 
            `paid_from_credit` = 0.00 
         WHERE `client_id` = :client_id"
    );
    $stmt_update_client->execute([':client_id' => $client_id]);

    $pdo->commit();
    message('Уведомление', 'Анкета возвращена в работу. Финансовые операции отменены.', 'success', 'reload');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error (revert-recorded-client): ' . $e->getMessage());
    message('Ошибка', 'Не удалось вернуть анкету в работу.', 'error', '');
}