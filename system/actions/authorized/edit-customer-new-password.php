<?php

$user_id = valid($_GET['user-id'] ?? '');

if (empty($user_id)) {
    redirectAJAX('customers');
}

if (!preg_match('/^[0-9]{1,11}$/u', $user_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

$new_password     = valid($_POST['new-password'] ?? '');
$confirm_password = valid($_POST['confirm-password'] ?? '');

$validatePassword = function($password, $fieldName) {
    if (empty($password)) {
        message('Ошибка', "Введите {$fieldName}!", 'error', '');
    }
    if (!preg_match('/^[a-zA-Z0-9]{3,25}$/u', $password)) {
        message('Ошибка', 'Пароль может содержать только латинские буквы и цифры (3–25 символов).', 'error', '');
    }
};

$validatePassword($new_password, 'новый пароль');
$validatePassword($confirm_password, 'подтверждение пароля');

if ($new_password !== $confirm_password) {
    message('Ошибка', 'Пароли не совпадают!', 'error', '');
}

$passwordHash = md5($new_password);

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `user_id` = :user_id");
    $stmt->execute([
      ':user_id' => $user_id
    ]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Аккаунт не найден!', 'error', '');
    }

    $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer_data['user_status'] === '0') {
        message('Ошибка', 'Аккаунт удален!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `users` 
        SET `user_password` = :password 
        WHERE `user_id`     = :user_id
    ");

    $stmt->execute([
        ':password' => $passwordHash,
        ':user_id'  => $user_id
    ]);

    message('Уведомление', 'Пароль изменён!', 'success', 'customers');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
}