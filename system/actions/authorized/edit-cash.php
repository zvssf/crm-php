<?php

$cash_id = valid($_POST['cash-edit-id'] ?? '');

if (empty($cash_id)) {
    redirectAJAX('finance');
}

if (!preg_match('/^[0-9]{1,11}$/u', $cash_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

$cash_name   = valid($_POST['cash-name'] ?? '');
$cash_status = valid($_POST['select-cash-status'] ?? '');

$validate = function($value, $pattern, $emptyMsg, $invalidMsg) {
    if (empty($value)) {
        message('Ошибка', $emptyMsg, 'error', '');
    }
    if (!preg_match('/^' . $pattern . '$/u', $value)) {
        message('Ошибка', $invalidMsg, 'error', '');
    }
};

$validate($cash_name,   '[a-zA-Zа-яА-Я0-9 ]{3,25}',   'Введите название кассы!',  'Недопустимое значение названия!');
$validate($cash_status, '[0-9]',                      'Выберите статус кассы!',   'Недопустимое значение статуса!');

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
    SELECT * 
    FROM `fin_cashes` 
    WHERE `id` = :id
    ");
    $stmt->execute([
      ':id' => $cash_id
    ]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Касса не найдена!', 'error', '');
    }

    $cash_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cash_data['status'] === 0) {
        message('Ошибка', 'Касса удалена!', 'error', '');
    }

    if ($cash_data['name'] !== $cash_name) {
        $stmt = $pdo->prepare("
        SELECT 1 
        FROM `fin_cashes` 
        WHERE `name` = :name
        ");
        $stmt->execute([
          ':name' => $cash_name
        ]);
        if ($stmt->rowCount() > 0) {
            message('Ошибка', 'Данная касса уже существует!', 'error', '');
        }
    }

    $stmt = $pdo->prepare("
        UPDATE `fin_cashes` 
        SET 
            `name`   = :name,
            `status` = :status 
        WHERE `id`   = :id
    ");

    $stmt->execute([
        ':name'       => $cash_name,
        ':status'     => $cash_status,
        ':id'  => $cash_id
    ]);

    message('Уведомление', 'Сохранение выполнено!', 'success', 'finance');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сохранить изменения. Попробуйте позже.', 'error', '');
}