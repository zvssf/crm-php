<?php

$supplier_id = valid($_POST['supplier-id'] ?? '');

if (empty($supplier_id)) {
    redirectAJAX('finance');
}

if (!preg_match('/^[0-9]{1,11}$/u', $supplier_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT 1 
        FROM `fin_suppliers` 
        WHERE `status` > '0' 
        AND `id`       = :id
    ");
    $stmt->execute([':id' => $supplier_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Такого поставщика нет или он уже удален!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `fin_suppliers` 
        SET `status` = '0' 
        WHERE `id`   = :id
    ");
    $stmt->execute([
      ':id' => $supplier_id
    ]);

    message('Уведомление', 'Удаление выполнено!', 'success', 'finance');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось выполнить удаление. Попробуйте позже.', 'error', '');
}