<?php

$login    = valid($_POST['login'] ?? '');
$password = valid($_POST['password'] ?? '');

if (empty($login)) {
    message('Ошибка', 'Введите логин!', 'error', '');
}
if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
    message('Ошибка', 'Недопустимое значение email!', 'error', '');
}
if (empty($password)) {
    message('Ошибка', 'Введите пароль!', 'error', '');
}
if (!preg_match('/^[a-z0-9]{3,32}$/i', $password)) {
    message('Ошибка', 'Пароль может содержать только латинские буквы и цифры, от 3 до 32 символов!', 'error', '');
}

$ip = getIP();
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP) ?: $ip;
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT attempts, last_attempt 
        FROM login_attempts 
        WHERE user_login  = :login 
        OR ip_address     = :ip
    ");
    $stmt->execute([
      ':login'  => $login,
      ':ip'     => $ip
    ]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($attempt && $attempt['attempts'] >= 5) {
        $last = new DateTime($attempt['last_attempt']);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $last->getTimestamp();

        if ($diff < 900) {
            message('Ошибка', 'Аккаунт временно заблокирован.', 'error', '');
        } else {
            $pdo->prepare("
            DELETE 
            FROM login_attempts 
            WHERE user_login  = :login 
            OR ip_address     = :ip
            ")->execute([
                  ':login'  => $login,
                  ':ip'     => $ip
                ]);
        }
    }

    $passwordHash = md5($password);

    $stmt = $pdo->prepare("
    SELECT * 
    FROM users 
    WHERE user_login = :login
    ");
    $stmt->execute([
      ':login' => $login
    ]);

    if (!$stmt->rowCount()) {
        incrementLoginAttempt($pdo, $login, $ip);
        message('Ошибка', 'Неверный логин или пароль!', 'error', '');
    }

    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (in_array($user_data['user_status'], [0, 2], true)) {
        message('Ошибка', 'Аккаунт недоступен!', 'error', '');
    }

    if ($user_data['user_password'] !== $passwordHash) {
        incrementLoginAttempt($pdo, $login, $ip);
        message('Ошибка', 'Неверный логин или пароль!', 'error', '');
    }

    $pdo->prepare("
    DELETE 
    FROM login_attempts 
    WHERE user_login  = :login 
    OR ip_address     = :ip
    ")->execute([
      ':login'  => $login,
      ':ip'     => $ip
    ]);

    $session_key = encryptSTR($passwordHash);

    $updateStmt = $pdo->prepare("
    UPDATE users 
    SET user_session_key  = :session_key 
    WHERE user_id         = :user_id
    ");
    $updateStmt->execute([
        ':session_key' => $session_key,
        ':user_id'     => $user_data['user_id']
    ]);

    setrawcookie('session_key', $session_key, time() + 2592000, '/', '', false, true);

    redirectAJAX('dashboard');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
}