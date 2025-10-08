<?php

$supplier_id = valid($_POST['supplier-edit-id'] ?? '');

if (empty($supplier_id)) {
    redirectAJAX('finance');
}

if (!preg_match('/^[0-9]{1,11}$/u', $supplier_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

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
    SELECT * 
    FROM `fin_suppliers` 
    WHERE `id` = :id
    ");
    $stmt->execute([
      ':id' => $supplier_id
    ]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Поставщик не найдена!', 'error', '');
    }

    $supplier_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($supplier_data['status'] === 0) {
        message('Ошибка', 'Поставщик удалена!', 'error', '');
    }

    if ($supplier_data['name'] !== $supplier_name) {
        $stmt = $pdo->prepare("
        SELECT 1 
        FROM `fin_suppliers` 
        WHERE `name` = :name
        ");
        $stmt->execute([
          ':name' => $supplier_name
        ]);
        if ($stmt->rowCount() > 0) {
            message('Ошибка', 'Данный поставщик уже существует!', 'error', '');
        }
    }

    $stmt = $pdo->prepare("
        UPDATE `fin_suppliers` 
        SET 
            `name`   = :name,
            `status` = :status 
        WHERE `id`   = :id
    ");

    $stmt->execute([
        ':name'       => $supplier_name,
        ':status'     => $supplier_status,
        ':id'  => $supplier_id
    ]);

    message('Уведомление', 'Сохранение выполнено!', 'success', 'finance');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сохранить изменения. Попробуйте позже.', 'error', '');
}