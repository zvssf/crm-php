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
    WHERE `id` = :transaction_id
    ");
    $stmt->execute([
        ':transaction_id' => $transaction_id
    ]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Транзакция не найдена!', 'error', '');
    }

    $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transaction_data['operation_type'] > '0') {
        message('Ошибка', 'Транзакции не требуется восстановление!', 'error', '');
    }

    $operation_type = 2;
    if($transaction_data['agent_id'] !== NULL) {
        $operation_type = 1;
    }

    $stmt = $pdo->prepare("
        UPDATE `fin_cashes` 
        SET `balance` = `balance` + :amount 
        WHERE `id`   = :cash_id
    ");
    $stmt->execute([
        ':amount' => $transaction_data['amount'],
        ':cash_id' => $transaction_data['cash_id']
    ]);

    if($transaction_data['agent_id'] !== NULL) {
        $stmt = $pdo->prepare("
        UPDATE `users` 
        SET `user_balance` = `user_balance` - :amount 
        WHERE `user_id`   = :user_id
    ");
    $stmt->execute([
        ':amount' => $transaction_data['amount'],
        ':user_id' => $transaction_data['agent_id']
    ]);
    }

    if($transaction_data['supplier_id'] !== NULL) {
        $stmt = $pdo->prepare("
        UPDATE `fin_suppliers` 
        SET `balance` = `balance` - :amount 
        WHERE `id`   = :id
    ");
    $stmt->execute([
        ':amount' => $transaction_data['amount'],
        ':id' => $transaction_data['supplier_id']
    ]);
    }

    $stmt = $pdo->prepare("
        UPDATE `fin_transactions` 
        SET `operation_type` = :operation_type 
        WHERE `id`   = :transaction_id
    ");
    $stmt->execute([
        ':operation_type' => $operation_type,
        ':transaction_id' => $transaction_id
    ]);

    $pdo->commit();
    message('Уведомление', 'Восстановление выполнено!', 'success', 'finance');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось восстановить транзакцию. Попробуйте позже.', 'error', '');
}