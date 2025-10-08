<?php
$user_firstname = valid($_POST['user-firstname'] ?? '');
$user_lastname  = valid($_POST['user-lastname'] ?? '');
$user_login     = valid($_POST['user-login'] ?? '');
$user_tel       = valid($_POST['user-tel'] ?? '');

$cleanTel = preg_replace('/[+\-\s\(\)]+/', '', $user_tel);
$fullTel = '+' . ltrim($cleanTel, '0123456789');
$fullTel = '+' . ltrim($cleanTel, '+');

$validate = function($value, $pattern, $emptyMsg, $invalidMsg) {
  if (empty($value)) {
      message('Ошибка', $emptyMsg, 'error', '');
  }
  if (!preg_match($pattern, $value)) {
      message('Ошибка', $invalidMsg, 'error', '');
  }
};

$validate($user_firstname, '/^[a-zA-Zа-яА-Я0-9]{2,25}$/ui',     'Введите имя!',             'Недопустимое значение имени!');
$validate($user_lastname,  '/^[a-zA-Zа-яА-Я0-9]{2,25}$/ui',     'Введите фамилию!',         'Недопустимое значение фамилии!');
$validate($user_login,     '/^.+@.+\..+$/u',                    'Введите email!',           'Недопустимое значение email!');
$validate($cleanTel,       '/^[0-9]{7,15}$/u',                  'Введите номер телефона!',  'Недопустимое значение номера телефона!');

if (!filter_var($user_login, FILTER_VALIDATE_EMAIL)) {
  message('Ошибка', 'Недопустимое значение email!', 'error', '');
}

try {
  $pdo = db_connect();

  if ($user_login !== $user_data['user_login']) {
      $stmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `user_login` = :login");
      $stmt->execute([':login' => $user_login]);
      if ($stmt->rowCount() > 0) {
          message('Ошибка', 'Данный email уже занят!', 'error', '');
      }
  }

  $displayTel = '+' . $cleanTel;
  if ($displayTel !== $user_data['user_tel']) {
      $stmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `user_tel` = :tel");
      $stmt->execute([':tel' => $displayTel]);
      if ($stmt->rowCount() > 0) {
          message('Ошибка', 'Данный номер телефона уже занят!', 'error', '');
      }
  }

  $stmt = $pdo->prepare("
      UPDATE `users` 
      SET 
          `user_firstname`  = :firstname,
          `user_lastname`   = :lastname,
          `user_login`      = :login,
          `user_tel`        = :tel
      WHERE `user_id`       = :user_id
  ");

  $stmt->execute([
      ':firstname' => $user_firstname,
      ':lastname'  => $user_lastname,
      ':login'     => $user_login,
      ':tel'       => $displayTel,
      ':user_id'   => $user_data['user_id']
  ]);

  message('Уведомление', 'Сохранение выполнено!', 'success', 'profile');

} catch (PDOException $e) {
  error_log('DB Error: ' . $e->getMessage());
  message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
}