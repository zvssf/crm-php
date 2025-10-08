<?php
$user_id = valid($_POST['user-id'] ?? '');

if (empty($user_id)) {
    redirectAJAX('customers');
}

if (!preg_match('/^[0-9]{1,11}$/u', $user_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT `user_id` 
        FROM `users` 
        WHERE `user_status` > '0' 
        AND `user_id`       = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Такого сотрудника нет или он уже удален!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `users` 
        SET `user_status` = '0' 
        WHERE `user_id`   = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);

    message('Уведомление', 'Удаление выполнено!', 'success', 'customers');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось выполнить удаление. Попробуйте позже.', 'error', '');
}