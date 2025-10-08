<?php
// message('Уведомление', '!', 'success', '');
$transaction_id      = valid($_POST['transaction-edit-id'] ?? '');
$amount              = valid($_POST['amount'] ?? '');
$transaction_comment = valid($_POST['transaction-comment'] ?? NULL);


$validate = function($value, $pattern, $emptyMsg, $invalidMsg) {
    if (empty($value)) {
        message('Ошибка', $emptyMsg, 'error', '');
    }
    if (!preg_match('/^' . $pattern . '$/u', $value)) {
        message('Ошибка', $invalidMsg, 'error', '');
    }
};

$validate($transaction_id, '[0-9]{1,11}',   'ID транзакции не найден!',      'Недопустимый ID транзакции!');
$validate($amount,         '[0-9.]{1,15}',  'Введите сумму транзакции!',     'Недопустимое значение суммы!');

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM `fin_transactions` WHERE `id` = :id");
    $stmt->execute([':id' => $transaction_id]);
    
    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Редактируемая транзакция не найдена!', 'error', '');
    }
    $old_transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    $old_amount = $old_transaction['amount'];
    $new_amount = ($old_transaction['operation_type'] == 1) ? $amount : -$amount;
    $diff_amount = $new_amount - $old_amount;

    $stmt_cash = $pdo->prepare("UPDATE `fin_cashes` SET `balance` = `balance` + :diff WHERE `id` = :id");
    $stmt_cash->execute([':diff' => $diff_amount, ':id' => $old_transaction['cash_id']]);

    if ($old_transaction['agent_id']) {
        $stmt_agent = $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :diff WHERE `user_id` = :id");
        $stmt_agent->execute([':diff' => $diff_amount, ':id' => $old_transaction['agent_id']]);
    }
    
    if ($old_transaction['supplier_id']) {
        $stmt_supplier = $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` - :diff WHERE `id` = :id");
        $stmt_supplier->execute([':diff' => $diff_amount, ':id' => $old_transaction['supplier_id']]);
    }

    $stmt_update = $pdo->prepare("
        UPDATE `fin_transactions` 
        SET 
            `amount` = :amount,
            `comment` = :comment
        WHERE `id` = :id
    ");
    $stmt_update->execute([
        ':amount'  => $new_amount,
        ':comment' => $transaction_comment,
        ':id'      => $transaction_id
    ]);

    $pdo->commit();
    message('Уведомление', 'Изменения сохранены!', 'success', 'finance');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сохранить изменения. Попробуйте позже.', 'error', '');
}