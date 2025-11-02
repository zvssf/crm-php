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
    WHERE `id` = :transaction_id AND `operation_type` = 0
    FOR UPDATE
    ");
    $stmt->execute([':transaction_id' => $transaction_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Транзакция не найдена или не является отмененной!', 'error', '');
    }

    $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $amount = (float) $transaction_data['amount'];

    // Шаг 1: Восстанавливаем баланс кассы (возвращаем сумму транзакции).
    $stmt_cash = $pdo->prepare("UPDATE `fin_cashes` SET `balance` = `balance` + :amount WHERE `id` = :cash_id");
    $stmt_cash->execute([':amount' => $amount, ':cash_id' => $transaction_data['cash_id']]);

    // Шаг 2: Восстанавливаем балансы агента/поставщика и оплаты по анкетам.
    if ($transaction_data['agent_id'] !== NULL) { // Это была транзакция ПРИХОДА от агента
        
        // Шаг 2а: Используем центральную финансовую функцию для восстановления.
        // Она получит сумму транзакции, добавит ее к балансу агента и АВТОМАТИЧЕСКИ
        // попытается оплатить все неоплаченные/кредитные анкеты этого агента.
        // Это элегантно решает все сложные сценарии.
        process_agent_repayments($pdo, $transaction_data['agent_id'], $amount);

        // Определяем исходный тип операции для восстановления
        $operation_type = 1;

    } elseif ($transaction_data['supplier_id'] !== NULL) { // Это была транзакция РАСХОДА поставщику
        
        // Восстанавливаем баланс поставщика. Сумма расхода отрицательная, 
        // поэтому вычитание отрицательной суммы приведет к ее прибавлению.
        $stmt_supplier = $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` + :amount WHERE `id` = :id");
        $stmt_supplier->execute([':amount' => $amount, ':id' => $transaction_data['supplier_id']]);

        // Определяем исходный тип операции для восстановления
        $operation_type = 2;
    } else {
        // На случай, если транзакция была странной (например, внутренний перевод)
        $operation_type = $transaction_data['amount'] > 0 ? 1 : 2;
    }
    
    // Шаг 3: Восстанавливаем саму транзакцию.
    $stmt_restore = $pdo->prepare("
        UPDATE `fin_transactions` 
        SET `operation_type` = :operation_type 
        WHERE `id` = :transaction_id
    ");
    $stmt_restore->execute([
        ':operation_type' => $operation_type,
        ':transaction_id' => $transaction_id
    ]);

    $pdo->commit();
    message('Уведомление', 'Транзакция успешно восстановлена!', 'success', 'finance');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error on restore-transaction: ' . $e->getMessage());
    message('Ошибка', 'Не удалось восстановить транзакцию: ' . $e->getMessage(), 'error', '');
}