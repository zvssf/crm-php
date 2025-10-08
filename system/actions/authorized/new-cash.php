<?php
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
    SELECT 1 
    FROM `fin_cashes` 
    WHERE `name` = :name
    ");
    $stmt->execute([
      ':name' => $cash_name
    ]);

    if ($stmt->rowCount() > 0) {
        message('Ошибка', 'Дання касса уже имеется!', 'error', '');
    }

    $stmt = $pdo->prepare("
        INSERT INTO `fin_cashes` (
            `name`,
            `status`
        ) VALUES (
            :name,
            :status
        )
    ");

    $stmt->execute([
        ':name'   => $cash_name,
        ':status' => $cash_status
    ]);

    message('Уведомление', 'Добавление выполнено!', 'success', 'finance');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось добавить кассу. Попробуйте позже.', 'error', '');
}