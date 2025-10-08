<?php

$input_id = valid($_POST['input-id'] ?? '');

if (empty($input_id)) {
    redirectAJAX('settings-inputs');
}

if (!preg_match('/^[0-9]{1,11}$/u', $input_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT 1 
        FROM `settings_inputs` 
        WHERE `input_status`  > '0' 
        AND `input_id`        = :input_id
    ");
    $stmt->execute([':input_id' => $input_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Такого дополнительного поля нет или оно уже удалено!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_inputs` 
        SET `input_status`  = '0' 
        WHERE `input_id`    = :input_id
    ");
    $stmt->execute([':input_id' => $input_id]);

    message('Уведомление', 'Удаление выполнено!', 'success', 'settings-inputs');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось выполнить удаление. Попробуйте позже.', 'error', '');
}