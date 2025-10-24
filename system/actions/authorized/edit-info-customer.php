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
$user_tel_2       = valid($_POST['user-tel-2'] ?? '');
$user_status      = valid($_POST['select-status'] ?? '');
$user_group       = valid($_POST['select-group'] ?? '');
$user_supervisor  = valid($_POST['select-supervisor'] ?? '');
$user_manager     = valid($_POST['select-manager'] ?? '');
$user_credit_limit = valid($_POST['user_credit_limit'] ?? '0.00');
$countries_post   = $_POST['countries'] ?? [];
$can_export = isset($_POST['can_export']) ? 1 : 0;

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

$fullTel2 = null;
if (!empty($user_tel_2)) {
    $cleanTel2 = preg_replace('/[+\-\s\(\)]+/', '', $user_tel_2);
    if (!empty($cleanTel2)) {
        $fullTel2 = '+' . ltrim($cleanTel2, '+');
    }
}

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
      $stmt = $pdo->prepare("SELECT 1 FROM `users` WHERE `user_tel` = :tel AND `user_id` != :user_id");
      $stmt->execute([':tel' => $fullTel, ':user_id' => $user_id]);
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
          `user_tel_2`        = :tel_2,
          `user_status`       = :status,
          `user_group`        = :group,
          `user_supervisor`   = :supervisor,
          `user_address`      = :address,
          `user_website`      = :website,
          `user_messengers`   = :messengers,
          `user_comment`      = :comment,
          `user_credit_limit` = :credit_limit,
          `can_export`        = :can_export
      WHERE `user_id`         = :user_id
  ");

  $stmt->execute([
      ':firstname'   => $user_firstname,
      ':lastname'    => $user_lastname,
      ':login'       => $user_login,
      ':tel'         => $fullTel,
      ':tel_2'       => $fullTel2,
      ':status'      => $user_status,
      ':group'       => $user_group,
      ':supervisor'  => $supervisor,
      ':address'     => $user_address,
      ':website'     => $user_website,
      ':messengers'  => $user_messengers,
      ':comment'     => $user_comment,
      ':credit_limit'=> ($user_group == 4) ? $user_credit_limit : 0.00,
      ':user_id'     => $user_id,
      ':can_export'  => ($user_group == 1) ? 1 : $can_export // Директору экспорт разрешен всегда
  ]);

  // Сначала удаляем все старые привязки стран для этого пользователя
  $stmt_delete_countries = $pdo->prepare("DELETE FROM `user_countries` WHERE `user_id` = :user_id");
  $stmt_delete_countries->execute([':user_id' => $user_id]);

  // Если новая группа - "Агент" и выбраны страны, добавляем новые привязки
  if ($user_group === '4' && !empty($countries_post)) {
      $stmt_insert_country = $pdo->prepare(
          "INSERT INTO `user_countries` (`user_id`, `country_id`) VALUES (:user_id, :country_id)"
      );

      foreach ($countries_post as $country_id) {
          if (is_numeric($country_id)) {
              $stmt_insert_country->execute([
                  ':user_id'    => $user_id,
                  ':country_id' => $country_id
              ]);
          }
      }
  }

  message('Уведомление', 'Сохранение выполнено!', 'success', 'customers');

} catch (PDOException $e) {
  error_log('DB Error: ' . $e->getMessage());
  message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
}