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

    $stmt = $pdo->prepare("SELECT * FROM `settings_inputs` WHERE `input_id` = :input_id");
    $stmt->execute([':input_id' => $input_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Дополнительное поле не найдено!', 'error', '');
    }

    $input_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($input_data['input_status'] > '0') {
        message('Ошибка', 'Дополнительному полю не требуется восстановление!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_inputs` 
        SET `input_status`  = '1' 
        WHERE `input_id`    = :input_id
    ");
    $stmt->execute([':input_id' => $input_id]);

    message('Уведомление', 'Восстановление выполнено!', 'success', 'settings-inputs');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось восстановить поле. Попробуйте позже.', 'error', '');
}