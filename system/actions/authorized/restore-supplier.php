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
    SELECT * 
    FROM `fin_suppliers` 
    WHERE `id` = :supplier_id
    ");
    $stmt->execute([
        ':supplier_id' => $supplier_id
    ]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Поставщик не найден!', 'error', '');
    }

    $supplier_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($supplier_data['status'] > '0') {
        message('Ошибка', 'Поставщику не требуется восстановление!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `fin_suppliers` 
        SET `status` = '1' 
        WHERE `id`   = :supplier_id
    ");
    $stmt->execute([
        ':supplier_id' => $supplier_id
    ]);

    message('Уведомление', 'Восстановление выполнено!', 'success', 'finance');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось восстановить поставщика. Попробуйте позже.', 'error', '');
}