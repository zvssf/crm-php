<?php

$cash_id = valid($_POST['cash-id'] ?? '');

if (empty($cash_id)) {
    redirectAJAX('finance');
}

if (!preg_match('/^[0-9]{1,11}$/u', $cash_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT 1 
        FROM `fin_cashes` 
        WHERE `status` > '0' 
        AND `id`       = :id
    ");
    $stmt->execute([':id' => $cash_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Такой кассы нет или она уже удалена!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `fin_cashes` 
        SET `status` = '0' 
        WHERE `id`   = :id
    ");
    $stmt->execute([
      ':id' => $cash_id
    ]);

    message('Уведомление', 'Удаление выполнено!', 'success', 'finance');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось выполнить удаление. Попробуйте позже.', 'error', '');
}