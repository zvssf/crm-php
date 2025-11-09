<?php

$login = valid($_POST['login'] ?? '') ?? '';
    
if (empty($login)) {
    message('Ошибка', 'Введите логин!', 'error', '');
  }
  if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
    message('Ошибка', 'Недопустимое значение email!', 'error', '');
  }




  try {
    $pdo = db_connect();
  
    // $passwordHash = md5($password);
  
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_login = :login");
      $stmt->execute([
          ':login'   => $login
      ]);
  
      if (!$stmt->rowCount()) {
        message('Ошибка', 'Аккаунт не найден!', 'error', '');
    }
  
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
  
    if (in_array($user_data['user_status'], [0, 2], true)) {
      message('Ошибка', 'Аккаунт недоступен!', 'error', '');
  }
  
    // $session_key = encryptSTR($passwordHash);
  
    // $updateStmt = $pdo->prepare("UPDATE users SET user_session_key = :session_key WHERE user_id = :user_id");
    //   $updateStmt->execute([
    //       ':session_key' => $session_key,
    //       ':user_id'     => $user_data['user_id']
    //   ]);
  
    // setrawcookie('session_key', $session_key, time() + 2592000, '/', '', false, true);
  
    // redirectAJAX('dashboard');

    message('Информация', 'Временно не доступно!', 'warning', '');
  
  } catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
  }