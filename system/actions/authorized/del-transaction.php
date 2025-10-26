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
    // Вычитаем сумму транзакции из баланса кассы.
    $stmt_cash = $pdo->prepare("UPDATE `fin_cashes` SET `balance` = `balance` - :amount WHERE `id` = :cash_id");
    $stmt_cash->execute([':amount' => $amount, ':cash_id' => $transaction_data['cash_id']]);

    // Шаг 2: Корректировка балансов агента/поставщика и откат оплат по логам.
    if ($transaction_data['operation_type'] == 1 && $transaction_data['agent_id'] !== NULL) { // Это был ПРИХОД от агента
        
        // Шаг 2а: Откат оплат по анкетам на основе лога.
        if (!empty($transaction_data['affected_clients_log'])) {
            $affected_clients = json_decode($transaction_data['affected_clients_log'], true);

            if (is_array($affected_clients)) {
                // Сортируем в обратном порядке, чтобы сначала отменить оплаты, а потом возвраты кредитов
                rsort($affected_clients);
                
                foreach ($affected_clients as $log_entry) {
                    $client_id = (int) $log_entry['client_id'];
                    $paid_amount = (float) $log_entry['amount'];
                    $payment_type = $log_entry['type'];

                    if ($payment_type === 'full_payment') {
                        // Если была полная оплата, просто возвращаем анкету в статус "Не оплачена"
                        $pdo->prepare(
                            "UPDATE `clients` SET 
                                `payment_status` = 0, 
                                `paid_from_balance` = 0, 
                                `paid_from_credit` = 0 
                            WHERE `client_id` = :client_id"
                        )->execute([':client_id' => $client_id]);

                    } elseif ($payment_type === 'credit_repayment') {
                        // Если было погашение кредита, возвращаем долг
                        $pdo->prepare(
                            "UPDATE `clients` SET 
                                `paid_from_balance` = `paid_from_balance` - :amount, 
                                `paid_from_credit` = `paid_from_credit` + :amount,
                                `payment_status` = 2
                            WHERE `client_id` = :client_id"
                        )->execute([':amount' => $paid_amount, ':client_id' => $client_id]);
                    }
                }
            }
        }
        
        // Шаг 2б: Корректировка баланса агента. Просто вычитаем сумму всей транзакции.
        $stmt_agent = $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :amount WHERE `user_id` = :user_id");
        $stmt_agent->execute([':amount' => $amount, ':user_id' => $transaction_data['agent_id']]);

    } elseif ($transaction_data['operation_type'] == 2 && $transaction_data['supplier_id'] !== NULL) { // Это был РАСХОД поставщику
        
        // При расходе amount отрицательный, поэтому вычитание `(-amount)` приведет к возврату средств.
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
    message('Ошибка', 'Не удалось отменить транзакцию. Попробуйте позже.', 'error', '');
}