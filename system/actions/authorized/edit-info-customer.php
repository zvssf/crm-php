<?php

$user_id = valid($_GET['user-id'] ?? '');

if (empty($user_id)) {
    redirectAJAX('customers');
}

if (!preg_match('/^[0-9]{1,11}$/u', $user_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

$user_firstname   = valid($_POST['user-firstname'] ?? '');
$user_lastname    = valid($_POST['user-lastname'] ?? '');
$user_login       = valid($_POST['user-login'] ?? '');
$user_tel         = valid($_POST['user-tel'] ?? '');
$user_status      = valid($_POST['select-status'] ?? '');
$user_group       = valid($_POST['select-group'] ?? '');
$user_supervisor  = valid($_POST['select-supervisor'] ?? '');
$user_manager     = valid($_POST['select-manager'] ?? '');

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

$validate($user_firstname,   '[a-zA-Zа-яА-Я0-9\s-]{2,25}',  'Введите имя!',                     'Недопустимое значение имени!');
$validate($user_lastname,    '[a-zA-Zа-яА-Я0-9\s-]{2,25}',  'Введите фамилию!',                 'Недопустимое значение фамилии!');
$validate($cleanTel,         '[0-9]{7,15}',                 'Введите номер телефона!',          'Недопустимое значение номера телефона!');
$validate($user_status,      '[0-9]',                       'Выберите статус!',                 'Недопустимое значение статуса!');
$validate($user_group,       '[1-4]',                       'Выберите группу пользователей!',   'Недопустимое значение группы!');

if (!filter_var($user_login, FILTER_VALIDATE_EMAIL)) {
    message('Ошибка', 'Недопустимое значение email!', 'error', '');
}

$supervisor = 0;

if ($user_group === '3') {
  $validate($user_supervisor, '[0-9]{1,11}', 'Выберите руководителя!', 'Недопустимое значение руководителя!');
  $supervisor = $user_supervisor;
} elseif ($user_group === '4') {
  $validate($user_manager, '[0-9]{1,11}', 'Выберите менеджера!', 'Недопустимое значение менеджера!');
  $supervisor = $user_manager;
}

try {
  $pdo = db_connect();

  $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `user_id` = :user_id");
  $stmt->execute([':user_id' => $user_id]);

  if ($stmt->rowCount() === 0) {
      message('Ошибка', 'Аккаунт не найден!', 'error', '');
  }

  $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($customer_data['user_status'] === '0') {
      message('Ошибка', 'Аккаунт удален!', 'error', '');
  }

  if ($user_login !== $customer_data['user_login']) {
      $stmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `user_login` = :login");
      $stmt->execute([':login' => $user_login]);
      if ($stmt->rowCount() > 0) {
          message('Ошибка', 'Данный email уже занят!', 'error', '');
      }
  }

  if ($fullTel !== $customer_data['user_tel']) {
      $stmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `user_tel` = :tel");
      $stmt->execute([':tel' => $fullTel]);
      if ($stmt->rowCount() > 0) {
          message('Ошибка', 'Данный номер телефона уже занят!', 'error', '');
      }
  }

  $stmt = $pdo->prepare("
      UPDATE `users` 
      SET 
          `user_firstname`    = :firstname,
          `user_lastname`     = :lastname,
          `user_login`        = :login,
          `user_tel`          = :tel,
          `user_status`       = :status,
          `user_group`        = :group,
          `user_supervisor`   = :supervisor
      WHERE `user_id`         = :user_id
  ");

  $stmt->execute([
      ':firstname'   => $user_firstname,
      ':lastname'    => $user_lastname,
      ':login'       => $user_login,
      ':tel'         => $fullTel,
      ':status'      => $user_status,
      ':group'       => $user_group,
      ':supervisor'  => $supervisor,
      ':user_id'     => $user_id
  ]);

  message('Уведомление', 'Сохранение выполнено!', 'success', 'customers');

} catch (PDOException $e) {
  error_log('DB Error: ' . $e->getMessage());
  message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
}