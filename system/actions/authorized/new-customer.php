<?php
$user_firstname   = valid($_POST['user-firstname'] ?? '');
$user_lastname    = valid($_POST['user-lastname'] ?? '');
$user_login       = valid($_POST['user-login'] ?? '');
$user_tel         = valid($_POST['user-tel'] ?? '');
$user_status      = valid($_POST['select-status'] ?? '');
$user_group       = valid($_POST['select-group'] ?? '');
$user_supervisor  = valid($_POST['select-supervisor'] ?? '');
$user_manager     = valid($_POST['select-manager'] ?? '');
$new_password     = valid($_POST['new-password'] ?? '');
$confirm_password = valid($_POST['confirm-password'] ?? '');
$user_credit_limit = valid($_POST['user_credit_limit'] ?? '0.00');

$user_address     = valid($_POST['user_address'] ?? '');
$user_website     = valid($_POST['user_website'] ?? '');
$user_comment     = valid($_POST['user_comment'] ?? '');

$messengers_post = $_POST['messengers'] ?? [];
$messenger_parts = [];
if (is_array($messengers_post)) {
    foreach ($messengers_post as $key => $value) {
        $clean_key = valid($key);
        $clean_value = valid($value);
        if (!empty($clean_value)) {
            $messenger_parts[] = $clean_key . ':' . $clean_value;
        }
    }
}
$user_messengers = implode('|', $messenger_parts);


$cleanTel = preg_replace('/[+\-\s\(\)]+/', '', $user_tel);
$fullTel  = '+' . ltrim($cleanTel, '+');

$validate = function($value, $pattern, $emptyMsg, $invalidMsg) {
    if (empty($value)) {
        message('Ошибка', $emptyMsg, 'error', '');
    }
    if (!preg_match('/^' . $pattern . '$/u', $value)) {
        message('Ошибка', $invalidMsg, 'error', '');
    }
};

$validate($user_firstname,   '[a-zA-Zа-яА-Я0-9\s-]{2,25}',  'Введите имя!',                    'Недопустимое значение имени!');
$validate($user_lastname,    '[a-zA-Zа-яА-Я0-9\s-]{2,25}',  'Введите фамилию!',                'Недопустимое значение фамилии!');
$validate($cleanTel,         '[0-9]{7,15}',                 'Введите номер телефона!',         'Недопустимое значение номера телефона!');
$validate($user_status,      '[0-9]',                       'Выберите статус!',                'Недопустимое значение статуса!');
$validate($user_group,       '[1-4]',                       'Выберите группу пользователей!',  'Недопустимое значение группы!');

if (!filter_var($user_login, FILTER_VALIDATE_EMAIL)) {
    message('Ошибка', 'Недопустимое значение email!', 'error', '');
}









$validate($new_password,     '[a-zA-Z0-9]{3,25}', 'Введите пароль!', 'Пароль может содержать только латинские буквы и цифры (3–25 символов)');
$validate($confirm_password, '[a-zA-Z0-9]{3,25}', 'Повторите пароль!', 'Пароль может содержать только латинские буквы и цифры (3–25 символов)');

if ($new_password !== $confirm_password) {
    message('Ошибка', 'Пароли не совпадают!', 'error', '');
}

$supervisor = 0;

if ($user_group === '3') {
    $validate($user_supervisor, '[0-9]{1,11}', 'Выберите руководителя!', 'Недопустимое значение руководителя!');
    $supervisor = $user_supervisor;
} elseif ($user_group === '4') {
    $validate($user_manager, '[0-9]{1,11}', 'Выберите менеджера!', 'Недопустимое значение менеджера!');
    $supervisor = $user_manager;
}

$passwordHash = md5($new_password);

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
    SELECT 1 
    FROM `users` 
    WHERE `user_login` = :login
    ");
    $stmt->execute([
      ':login' => $user_login
    ]);
    if ($stmt->rowCount() > 0) {
        message('Ошибка', 'Данный email уже занят!', 'error', '');
    }

    $stmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `user_tel` = :tel");
    $stmt->execute([':tel' => $fullTel]);
    if ($stmt->rowCount() > 0) {
        message('Ошибка', 'Данный номер телефона уже занят!', 'error', '');
    }

    $stmt = $pdo->prepare("
        INSERT INTO `users` (
            `user_login`,
            `user_password`,
            `user_group`,
            `user_status`,
            `user_session_key`,
            `user_firstname`,
            `user_lastname`,
            `user_tel`,
            `user_supervisor`,
            `user_address`,
            `user_website`,
            `user_messengers`,
            `user_comment`,
            `user_credit_limit`
        ) VALUES (
            :login,
            :password,
            :group,
            :status,
            '',
            :firstname,
            :lastname,
            :tel,
            :supervisor,
            :address,
            :website,
            :messengers,
            :comment,
            :credit_limit
        )
    ");

    $stmt->execute([
        ':login'       => $user_login,
        ':password'    => $passwordHash,
        ':group'       => $user_group,
        ':status'      => $user_status,
        ':firstname'   => $user_firstname,
        ':lastname'    => $user_lastname,
        ':tel'         => $fullTel,
        ':supervisor'  => $supervisor,
        ':address'     => $user_address,
        ':website'     => $user_website,
        ':messengers'  => $user_messengers,
        ':comment'     => $user_comment,
        ':credit_limit'=> ($user_group == 4) ? $user_credit_limit : 0.00
    ]);

    message('Уведомление', 'Добавление выполнено!', 'success', 'customers');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
}