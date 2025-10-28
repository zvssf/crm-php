<?php
$transaction_id = valid($_POST['transaction-id'] ?? '');

if (empty($transaction_id)) {
    redirectAJAX('finance');
}

if (!preg_match('/^[0-9]{1,11}$/u', $transaction_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
    SELECT * 
    FROM `fin_transactions` 
    WHERE `id` = :transaction_id AND `operation_type` != 0
    FOR UPDATE
    ");
    $stmt->execute([':transaction_id' => $transaction_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Транзакция не найдена или уже отменена!', 'error', '');
    }

    $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $amount = (float) $transaction_data['amount'];

    // Шаг 1: Корректировка баланса кассы.
    $stmt_cash = $pdo->prepare("UPDATE `fin_cashes` SET `balance` = `balance` - :amount WHERE `id` = :cash_id");
    $stmt_cash->execute([':amount' => $amount, ':cash_id' => $transaction_data['cash_id']]);

    // Шаг 2: Корректировка балансов агента/поставщика и откат оплат по логам.
    if ($transaction_data['operation_type'] == 1 && $transaction_data['agent_id'] !== NULL) { // Это был ПРИХОД от агента
        
        // Шаг 2а: Откат оплат по анкетам на основе лога.
        if (!empty($transaction_data['affected_clients_log'])) {
            $affected_clients = json_decode($transaction_data['affected_clients_log'], true);

            if (is_array($affected_clients)) {
                
                foreach ($affected_clients as $log_entry) {
                    $client_id_from_log = (int) $log_entry['client_id'];
                    $paid_amount = (float) $log_entry['amount'];

                    // Проверяем, был ли UID ВООБЩЕ записан в лог.
                    // Это нужно для совместимости со старыми транзакциями, где ключа не было.
                    if (!isset($log_entry['recording_uid'])) {
                        continue; // Пропускаем только очень старые транзакции без ключа
                    }
                    $uid_from_log = $log_entry['recording_uid']; // Теперь мы знаем, что ключ есть (может быть null)

                    // Проверяем текущее состояние анкеты
                    $stmt_check_client = $pdo->prepare("SELECT `recording_uid`, `client_status` FROM `clients` WHERE `client_id` = :client_id");
                    $stmt_check_client->execute([':client_id' => $client_id_from_log]);
                    $client_from_db = $stmt_check_client->fetch(PDO::FETCH_ASSOC);

                    // Если анкета все еще "Записана" И ее UID совпадает с тем, что в логе, делаем откат
                    if ($client_from_db && $client_from_db['client_status'] == 2 && $client_from_db['recording_uid'] === $uid_from_log) {
                        $pdo->prepare(
                            "UPDATE `clients` SET 
                                `payment_status` = 2, 
                                `paid_from_balance` = `paid_from_balance` - :amount_sub, 
                                `paid_from_credit` = `paid_from_credit` + :amount_add 
                            WHERE `client_id` = :client_id"
                        )->execute([
                            ':amount_sub' => $paid_amount,
                            ':amount_add' => $paid_amount,
                            ':client_id' => $client_id_from_log
                        ]);
                    }
                    // В противном случае ничего не делаем с анкетой, деньги просто вернутся на баланс агента.
                }
            }
        }

        // Шаг 2б: Прямая и простая корректировка баланса агента.
        $stmt_agent = $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :amount WHERE `user_id` = :user_id");
        $stmt_agent->execute([':amount' => $amount, ':user_id' => $transaction_data['agent_id']]);

    } elseif ($transaction_data['operation_type'] == 2 && $transaction_data['supplier_id'] !== NULL) { // Это был РАСХОД поставщику
        
        $stmt_supplier = $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` - :amount WHERE `id` = :id");
        $stmt_supplier->execute([':amount' => $amount, ':id' => $transaction_data['supplier_id']]);
    }
    
    // Шаг 3: Архивирование транзакции (установка operation_type = 0).
    $stmt_cancel = $pdo->prepare("UPDATE `fin_transactions` SET `operation_type` = '0' WHERE `id` = :transaction_id");
    $stmt_cancel->execute([':transaction_id' => $transaction_id]);

    $pdo->commit();
    message('Уведомление', 'Транзакция отменена!', 'success', 'finance');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error on del-transaction: ' . $e->getMessage());
    message('Ошибка', 'Не удалось отменить транзакцию: ' . $e->getMessage(), 'error', '');
}