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

    // Функция для перерасчета и погашения задолженностей
    function process_agent_repayments($pdo, $agent_id, $transaction_amount) {
        // Шаг 1: Получаем текущий баланс агента и сразу обновляем его с учетом новой транзакции
        $stmt_agent = $pdo->prepare("SELECT `user_balance` FROM `users` WHERE `user_id` = :agent_id FOR UPDATE");
        $stmt_agent->execute([':agent_id' => $agent_id]);
        $current_balance = (float) $stmt_agent->fetchColumn();
        
        $new_balance = $current_balance + (float) $transaction_amount;
        
        // Сразу обновляем баланс в базе данных
        $pdo->prepare("UPDATE `users` SET `user_balance` = :new_balance WHERE `user_id` = :agent_id")
            ->execute([':new_balance' => $new_balance, ':agent_id' => $agent_id]);

        // Шаг 2: Если обновленный баланс стал неотрицательным, конвертируем все кредитные анкеты
        if ($new_balance >= 0) {
            $stmt_credits = $pdo->prepare(
                "SELECT `client_id`, `paid_from_credit` FROM `clients` 
                 WHERE `agent_id` = :agent_id AND `payment_status` = 2 
                 ORDER BY `client_id` ASC FOR UPDATE"
            );
            $stmt_credits->execute([':agent_id' => $agent_id]);
            $credit_clients = $stmt_credits->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($credit_clients as $client) {
                $pdo->prepare(
                    "UPDATE `clients` SET 
                        `payment_status` = 1, 
                        `paid_from_balance` = `paid_from_balance` + :credit_amount, 
                        `paid_from_credit` = 0 
                     WHERE `client_id` = :client_id"
                )->execute([':credit_amount' => $client['paid_from_credit'], ':client_id' => $client['client_id']]);
            }
        }
        
        // Шаг 3: Снова проверяем баланс. Если на нем есть средства, ищем "неоплаченные" анкеты.
        // Переменная new_balance теперь будет нашим "кошельком", который мы тратим.
        if ($new_balance > 0) {
            $stmt_unpaid = $pdo->prepare(
                "SELECT `client_id`, `sale_price` FROM `clients` 
                 WHERE `agent_id` = :agent_id AND `payment_status` = 0 
                 ORDER BY `client_id` ASC FOR UPDATE"
            );
            $stmt_unpaid->execute([':agent_id' => $agent_id]);
            $unpaid_clients = $stmt_unpaid->fetchAll(PDO::FETCH_ASSOC);
    
            foreach ($unpaid_clients as $client) {
                if ($new_balance <= 0) break;
    
                $sale_price = (float) $client['sale_price'];
                if ($new_balance >= $sale_price) {
                    $new_balance -= $sale_price; // Уменьшаем "кошелек"
                    
                    $pdo->prepare(
                        "UPDATE `clients` SET 
                            `payment_status` = 1, 
                            `paid_from_balance` = :sale_price,
                            `paid_from_credit` = 0
                         WHERE `client_id` = :client_id"
                    )->execute([':sale_price' => $sale_price, ':client_id' => $client['client_id']]);
                } else {
                    break;
                }
            }
        }
    
        // Шаг 4: Обновляем баланс агента в БД ОДИН РАЗ финальным значением после всех списаний
        $pdo->prepare("UPDATE `users` SET `user_balance` = :final_balance WHERE `user_id` = :agent_id")
            ->execute([':final_balance' => $new_balance, ':agent_id' => $agent_id]);
    }

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