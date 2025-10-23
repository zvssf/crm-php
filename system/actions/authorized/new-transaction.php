<?php
$operation_type      = valid($_POST['select-operation-type'] ?? '');
$amount    = valid($_POST['amount'] ?? '');
$select_cash    = valid($_POST['select-cash'] ?? '');
$select_agent    = valid($_POST['select-agent'] ?? '');
$select_supplier = valid($_POST['select-supplier'] ?? '');
$transaction_comment = valid($_POST['transaction-comment'] ?? NULL);

$validate = function($value, $pattern, $emptyMsg, $invalidMsg) {
    if (empty($value) || $value === 'hide') {
        message('Ошибка', $emptyMsg, 'error', '');
    }
    if (!preg_match('/^' . $pattern . '$/u', $value)) {
        message('Ошибка', $invalidMsg, 'error', '');
    }
};

$validate($operation_type, '[0-9]',                    'Выберите тип транзакции!',    'Недопустимое значение типа транзакции!');
$validate($amount,   '[0-9. ]{4,15}', 'Введите сумму транзакции!',   'Недопустимое значение суммы!');
$validate($select_cash, '[0-9]{1,11}',              'Выберите кассу!',               'Недопустимое значение кассы!');

if($operation_type === '1') {
    $validate($select_agent, '[0-9]{1,11}',                    'Выберите агента!',    'Недопустимое значение агента!');
}

try {
    $pdo = db_connect();
    $pdo->beginTransaction();


    // --- ОСНОВНАЯ ЛОГИКА ТРАНЗАКЦИИ ---
    $stmt = $pdo->prepare("SELECT 1 FROM `fin_cashes` WHERE `id` = :id ");
    $stmt->execute([':id' => $select_cash]);
    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Данная касса не найдена!', 'error', '');
    }

    if($operation_type === '1') {
        $stmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `user_id` = :id ");
        $stmt->execute([':id' => $select_agent]);
        if ($stmt->rowCount() === 0) {
            message('Ошибка', 'Данный агент не найден!', 'error', '');
        }
    }

    if($operation_type === '2' && $select_supplier !== 'hide') {
        $stmt = $pdo->prepare("SELECT 1 FROM `fin_suppliers` WHERE `id` = :id ");
        $stmt->execute([':id' => $select_supplier]);
        if ($stmt->rowCount() === 0) {
            message('Ошибка', 'Данный поставщик не найден!', 'error', '');
        }
    }

    $amount_to_db = 0;
    $agent_id_to_db = NULL;
    $supplier_id_to_db = NULL;

    if ($operation_type === '1') { // ПРИХОД
        $agent_id_to_db = $select_agent;
        $amount_to_db = $amount;
        
        // Обновляем баланс кассы
        $pdo->prepare("UPDATE `fin_cashes` SET `balance` = `balance` + :amount WHERE `id` = :id")->execute([':amount' => $amount, ':id' => $select_cash]);
        
        // Вызываем функцию перерасчета, которая теперь сама управляет балансом агента
        process_agent_repayments($pdo, $agent_id_to_db, $amount);

    } else { // РАСХОД
        $amount_to_db = -$amount;
        $agent_id_to_db = NULL;
        $pdo->prepare("UPDATE `fin_cashes` SET `balance` = `balance` - :amount WHERE `id` = :id")->execute([':amount' => $amount, ':id' => $select_cash]);
        
        if ($select_supplier !== 'hide') {
            $supplier_id_to_db = $select_supplier;
            $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` + :amount WHERE `id` = :id")->execute([':amount' => $amount, ':id' => $supplier_id_to_db]);
        }
    }

    // Записываем саму транзакцию в историю
    $stmt = $pdo->prepare("
        INSERT INTO `fin_transactions` (
            `operation_type`, `amount`, `cash_id`, `agent_id`, `supplier_id`, `comment`
        ) VALUES (
            :operation_type, :amount, :cash_id, :agent_id, :supplier_id, :comment
        )
    ");
    $stmt->execute([
        ':operation_type' => $operation_type,
        ':amount'         => $amount_to_db,
        ':cash_id'        => $select_cash,
        ':agent_id'       => $agent_id_to_db,
        ':supplier_id'    => $supplier_id_to_db,
        ':comment'        => $transaction_comment
    ]);

    $pdo->commit();
    message('Уведомление', 'Добавление выполнено!', 'success', 'finance');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось добавить транзакцию. Попробуйте позже.', 'error', '');
}