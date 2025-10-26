<?php
$operation_type      = valid($_POST['select-operation-type'] ?? '');
$amount_raw          = $_POST['amount'] ?? '';
$select_cash         = valid($_POST['select-cash'] ?? '');
$select_agent        = valid($_POST['select-agent'] ?? '');
$select_supplier     = valid($_POST['select-supplier'] ?? '');
$transaction_comment = valid($_POST['transaction-comment'] ?? NULL);

// Очищаем сумму от всех нечисловых символов, кроме точки
$amount = preg_replace('/[^0-9.]/', '', $amount_raw);

$validate = function($value, $pattern, $emptyMsg, $invalidMsg) {
    if ($value === '' || $value === null || $value === 'hide') {
        message('Ошибка', $emptyMsg, 'error', '');
    }
    if (!preg_match('/^' . $pattern . '$/u', (string) $value)) {
        message('Ошибка', $invalidMsg, 'error', '');
    }
};

$validate($operation_type, '[1-2]',        'Выберите тип транзакции!', 'Недопустимое значение типа транзакции!');
$validate($amount,         '[0-9.]+',      'Введите сумму транзакции!', 'Недопустимое значение суммы!');
$validate($select_cash,    '[0-9]{1,11}',  'Выберите кассу!',           'Недопустимое значение кассы!');

if($operation_type === '1') {
    $validate($select_agent, '[0-9]{1,11}', 'Выберите агента!', 'Недопустимое значение агента!');
}

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    // --- ПРОВЕРКИ СУЩЕСТВОВАНИЯ СУЩНОСТЕЙ ---
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

    if($operation_type === '2' && !empty($select_supplier) && $select_supplier !== 'hide') {
        $stmt = $pdo->prepare("SELECT 1 FROM `fin_suppliers` WHERE `id` = :id ");
        $stmt->execute([':id' => $select_supplier]);
        if ($stmt->rowCount() === 0) {
            message('Ошибка', 'Данный поставщик не найден!', 'error', '');
        }
    }

    // --- ОСНОВНАЯ ЛОГИКА ТРАНЗАКЦИИ ---
    $amount_to_db = 0;
    $agent_id_to_db = NULL;
    $supplier_id_to_db = NULL;
    $affected_clients_log = NULL;

    if ($operation_type === '1') { // ПРИХОД
        $agent_id_to_db = $select_agent;
        $amount_to_db = $amount;
        
        // Обновляем баланс кассы
        $pdo->prepare("UPDATE `fin_cashes` SET `balance` = `balance` + :amount WHERE `id` = :id")->execute([':amount' => $amount, ':id' => $select_cash]);
        
        // ВЫЗЫВАЕМ ЦЕНТРАЛЬНУЮ ФУНКЦИЮ ПЕРЕРАСЧЕТА И ПОЛУЧАЕМ ЛОГ
        $log_data = process_agent_repayments($pdo, $agent_id_to_db, $amount);
        if (!empty($log_data)) {
            $affected_clients_log = json_encode($log_data);
        }

    } else { // РАСХОД
        $amount_to_db = -$amount;
        $agent_id_to_db = NULL;
        $pdo->prepare("UPDATE `fin_cashes` SET `balance` = `balance` - :amount WHERE `id` = :id")->execute([':amount' => $amount, ':id' => $select_cash]);
        
        if (!empty($select_supplier) && $select_supplier !== 'hide') {
            $supplier_id_to_db = $select_supplier;
            $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` + :amount WHERE `id` = :id")->execute([':amount' => $amount, ':id' => $supplier_id_to_db]);
        }
    }

    // Записываем саму транзакцию в историю
    $stmt = $pdo->prepare("
        INSERT INTO `fin_transactions` (
            `operation_type`, `amount`, `cash_id`, `agent_id`, `supplier_id`, `comment`, `affected_clients_log`
        ) VALUES (
            :operation_type, :amount, :cash_id, :agent_id, :supplier_id, :comment, :affected_clients_log
        )
    ");
    $stmt->execute([
        ':operation_type' => $operation_type,
        ':amount'         => $amount_to_db,
        ':cash_id'        => $select_cash,
        ':agent_id'       => $agent_id_to_db,
        ':supplier_id'    => $supplier_id_to_db,
        ':comment'        => $transaction_comment,
        ':affected_clients_log' => $affected_clients_log
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