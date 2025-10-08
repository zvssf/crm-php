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

    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `user_id` = :user_id");
    $stmt->execute([':user_id' => $user_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Аккаунт не найден!', 'error', '');
    }

    $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer_data['user_status'] > '0') {
        message('Ошибка', 'Аккаунту не требуется восстановление!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `users` 
        SET `user_status` = '1' 
        WHERE `user_id`   = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);

    message('Уведомление', 'Восстановление выполнено!', 'success', 'customers');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось восстановить аккаунт. Попробуйте позже.', 'error', '');
}