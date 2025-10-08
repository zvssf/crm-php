<?php
$supplier_name   = valid($_POST['supplier-name'] ?? '');
$supplier_status = valid($_POST['select-supplier-status'] ?? '');

$validate = function($value, $pattern, $emptyMsg, $invalidMsg) {
    if (empty($value)) {
        message('Ошибка', $emptyMsg, 'error', '');
    }
    if (!preg_match('/^' . $pattern . '$/u', $value)) {
        message('Ошибка', $invalidMsg, 'error', '');
    }
};

$validate($supplier_name,   '[a-zA-Zа-яА-Я0-9 ]{3,25}',   'Введите название поставщика!',  'Недопустимое значение названия!');
$validate($supplier_status, '[0-9]',                      'Выберите статус поставщика!',   'Недопустимое значение статуса!');

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
    SELECT 1 
    FROM `fin_suppliers` 
    WHERE `name` = :name
    ");
    $stmt->execute([
      ':name' => $supplier_name
    ]);

    if ($stmt->rowCount() > 0) {
        message('Ошибка', 'Данный поставщик уже имеется!', 'error', '');
    }

    $stmt = $pdo->prepare("
        INSERT INTO `fin_suppliers` (
            `name`,
            `status`
        ) VALUES (
            :name,
            :status
        )
    ");

    $stmt->execute([
        ':name'   => $supplier_name,
        ':status' => $supplier_status
    ]);

    message('Уведомление', 'Добавление выполнено!', 'success', 'finance');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось добавить поставщика. Попробуйте позже.', 'error', '');
}