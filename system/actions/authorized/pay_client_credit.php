<?php
$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    // 1. Получаем данные анкеты
    $stmt_client = $pdo->prepare("SELECT `agent_id`, `sale_price`, `payment_status` FROM `clients` WHERE `client_id` = :client_id");
    $stmt_client->execute([':client_id' => $client_id]);
    $client_info = $stmt_client->fetch(PDO::FETCH_ASSOC);

    if (!$client_info || $client_info['payment_status'] != 0) {
        message('Ошибка', 'Эту анкету нельзя оплатить в кредит.', 'error', '');
    }

    $agent_id = $client_info['agent_id'];
    $sale_price = (float) $client_info['sale_price'];

    // 2. Получаем данные агента
    $stmt_agent = $pdo->prepare("SELECT `user_balance`, `user_credit_limit` FROM `users` WHERE `user_id` = :agent_id FOR UPDATE");
    $stmt_agent->execute([':agent_id' => $agent_id]);
    $agent_data = $stmt_agent->fetch(PDO::FETCH_ASSOC);

    if (!$agent_data) {
        message('Ошибка', 'Агент не найден.', 'error', '');
    }

    $agent_balance = (float) $agent_data['user_balance'];
    $agent_credit_limit = (float) $agent_data['user_credit_limit'];

    // 3. Проверяем, позволяет ли кредитный лимит совершить операцию
    if (($agent_balance - $sale_price) < -$agent_credit_limit) {
        message('Ошибка', 'Недостаточно кредитного лимита у агента!', 'error', '');
    }

    // 4. Рассчитываем суммы
    $paid_from_balance = max(0, $agent_balance);
    $paid_from_credit = $sale_price - $paid_from_balance;

    // 5. Обновляем баланс агента и данные анкеты
    $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :sale_price WHERE `user_id` = :agent_id")
        ->execute([':sale_price' => $sale_price, ':agent_id' => $agent_id]);

    $recording_uid = uniqid(); // Генерируем уникальный ID
    $stmt_update_client = $pdo->prepare(
        "UPDATE `clients` SET 
            `payment_status` = 2, 
            `paid_from_balance` = :paid_from_balance, 
            `paid_from_credit` = :paid_from_credit,
            `recording_uid` = :recording_uid 
         WHERE `client_id` = :client_id"
    );
    $stmt_update_client->execute([
        ':paid_from_balance' => $paid_from_balance,
        ':paid_from_credit'  => $paid_from_credit,
        ':recording_uid'     => $recording_uid,
        ':client_id'         => $client_id
    ]);

    $pdo->commit();
    message('Уведомление', 'Анкета успешно оплачена в кредит!', 'success', 'reload');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error (pay_client_credit): ' . $e->getMessage());
    message('Ошибка', 'Не удалось выполнить операцию.', 'error', '');
}