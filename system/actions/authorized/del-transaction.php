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
    ");
    $stmt->execute([':transaction_id' => $transaction_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Транзакция не найдена или уже отменена!', 'error', '');
    }

    $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $amount = (float) $transaction_data['amount'];

    // Шаг 1: Корректировка баланса кассы
    $stmt_cash = $pdo->prepare("UPDATE `fin_cashes` SET `balance` = `balance` - :amount WHERE `id` = :cash_id");
    $stmt_cash->execute([':amount' => $amount, ':cash_id' => $transaction_data['cash_id']]);

    // Шаг 2: Корректировка балансов агента/поставщика и откат оплат
    if ($transaction_data['operation_type'] == 1 && $transaction_data['agent_id'] !== NULL) { // Это был приход от агента
        
        // Шаг 2а: Откат оплат по анкетам
        if (!empty($transaction_data['affected_clients_log'])) {
            $affected_clients = json_decode($transaction_data['affected_clients_log'], true);

            if (is_array($affected_clients)) {
                $stmt_revert_client = $pdo->prepare(
                    "UPDATE `clients` SET 
                        `paid_from_balance` = `paid_from_balance` - :amount_paid,
                        `payment_status` = 0
                    WHERE `client_id` = :client_id"
                );

                foreach ($affected_clients as $log_entry) {
                    $stmt_revert_client->execute([
                        ':amount_paid' => (float) $log_entry['amount_paid'],
                        ':client_id'   => (int) $log_entry['client_id']
                    ]);
                }
            }
        }
        
        // Шаг 2б: Корректировка баланса агента
        $stmt_agent = $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :amount WHERE `user_id` = :user_id");
        $stmt_agent->execute([':amount' => $amount, ':user_id' => $transaction_data['agent_id']]);

    } elseif ($transaction_data['operation_type'] == 2 && $transaction_data['supplier_id'] !== NULL) { // Это был расход поставщику
        
        // При расходе amount отрицательный, поэтому вычитание `(-amount)` приведет к сложению
        $stmt_supplier = $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` - :amount WHERE `id` = :id");
        $stmt_supplier->execute([':amount' => $amount, ':id' => $transaction_data['supplier_id']]);

    }
    
    // Шаг 3: Архивирование транзакции
    $stmt_cancel = $pdo->prepare("UPDATE `fin_transactions` SET `operation_type` = '0' WHERE `id` = :transaction_id");
    $stmt_cancel->execute([':transaction_id' => $transaction_id]);

    $pdo->commit();
    message('Уведомление', 'Транзакция отменена!', 'success', 'finance');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error on del-transaction: ' . $e->getMessage());
    message('Ошибка', 'Не удалось отменить транзакцию. Попробуйте позже.', 'error', '');
}