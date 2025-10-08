<?php

$center_id = valid($_POST['center-edit-id'] ?? '');

if (empty($center_id)) {
    redirectAJAX('settings-centers');
}

if (!preg_match('/^[0-9]{1,11}$/u', $center_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

$center_name   = valid($_POST['center-name'] ?? '');
$country_id    = valid($_POST['select-country'] ?? '');
$center_status = valid($_POST['select-center-status'] ?? '');

$validate = function($value, $pattern, $emptyMsg, $invalidMsg) {
    if (empty($value)) {
        message('Ошибка', $emptyMsg, 'error', '');
    }
    if (!preg_match('/^' . $pattern . '$/u', $value)) {
        message('Ошибка', $invalidMsg, 'error', '');
    }
};

$validate($center_name,   '[a-zA-Zа-яА-Я0-9 ]{3,25}', 'Введите название визового центра!',  'Недопустимое значение названия!');
$validate($center_status, '[0-9]',                    'Выберите статус визового центра!',   'Недопустимое значение статуса!');
$validate($country_id,    '[0-9]{1,11}',                 'Выберите страну!',         'Недопустимое значение страны!');

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
    SELECT * 
    FROM `settings_centers` 
    WHERE `center_id` = :center_id
    ");
    $stmt->execute([
      ':center_id' => $center_id
    ]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Визовый центр не найден!', 'error', '');
    }

    $center_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($center_data['center_status'] === 0) {
        message('Ошибка', 'Визовый центр удалён!', 'error', '');
    }

    if ($center_data['center_name'] !== $center_name) {
        $stmt = $pdo->prepare("
        SELECT 1 
        FROM `settings_centers` 
        WHERE `center_name` = :name
        ");
        $stmt->execute([
          ':name' => $center_name
        ]);
        if ($stmt->rowCount() > 0) {
            message('Ошибка', 'Данный визовый центр уже существует!', 'error', '');
        }
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_centers` 
        SET 
            `center_name`   = :name,
            `country_id`    = :country_id,
            `center_status` = :status 
        WHERE `center_id`   = :center_id
    ");

    $stmt->execute([
        ':name'       => $center_name,
        ':country_id' => $country_id,
        ':status'     => $center_status,
        ':center_id'  => $center_id
    ]);

    message('Уведомление', 'Сохранение выполнено!', 'success', 'settings-centers');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сохранить изменения. Попробуйте позже.', 'error', '');
}