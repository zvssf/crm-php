<?php
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

$validate($center_name,   '[a-zA-Zа-яА-Я0-9 ]{3,25}',   'Введите название визового центра!',  'Недопустимое значение названия!');
$validate($center_status, '[0-9]',                      'Выберите статус визового центра!',   'Недопустимое значение статуса!');
$validate($country_id,    '[0-9]{1,11}',                 'Выберите страну!',         'Недопустимое значение страны!');

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
    SELECT 1 
    FROM `settings_centers` 
    WHERE `center_name` = :name AND `country_id` = :country_id
    ");
    $stmt->execute([
      ':name' => $center_name,
      ':country_id' => $country_id
    ]);

    if ($stmt->rowCount() > 0) {
        message('Ошибка', 'Данный визовый центр уже имеется в этой стране!', 'error', '');
    }

    $stmt = $pdo->prepare("
        INSERT INTO `settings_centers` (
            `center_name`,
            `country_id`,
            `center_status`
        ) VALUES (
            :name,
            :country_id,
            :status
        )
    ");

    $stmt->execute([
        ':name'       => $center_name,
        ':country_id' => $country_id,
        ':status'     => $center_status
    ]);

    message('Уведомление', 'Добавление выполнено!', 'success', 'settings-centers');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось добавить визовый центр. Попробуйте позже.', 'error', '');
}