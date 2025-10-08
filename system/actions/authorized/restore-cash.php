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
    SELECT * 
    FROM `fin_cashes` 
    WHERE `id` = :cash_id
    ");
    $stmt->execute([
        ':cash_id' => $cash_id
    ]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Касса не найдена!', 'error', '');
    }

    $cash_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cash_data['status'] > '0') {
        message('Ошибка', 'Кассе не требуется восстановление!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `fin_cashes` 
        SET `status` = '1' 
        WHERE `id`   = :cash_id
    ");
    $stmt->execute([
        ':cash_id' => $cash_id
    ]);

    message('Уведомление', 'Восстановление выполнено!', 'success', 'finance');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось восстановить кассу. Попробуйте позже.', 'error', '');
}