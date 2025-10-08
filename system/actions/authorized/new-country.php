<?php
$country_name      = valid($_POST['country-name'] ?? '');
$country_status    = valid($_POST['select-country-status'] ?? '');

$validate = function($value, $pattern, $emptyMsg, $invalidMsg) {
    if (empty($value) || $value === 'hide') {
        message('Ошибка', $emptyMsg, 'error', '');
    }
    if (!preg_match('/^' . $pattern . '$/u', $value)) {
        message('Ошибка', $invalidMsg, 'error', '');
    }
};

$validate($country_name,   '[a-zA-Zа-яА-Я0-9 ]{3,25}', 'Введите название страны!',   'Недопустимое значение названия!');
$validate($country_status, '[0-9]',                    'Выберите статус страны!',    'Недопустимое значение статуса!');

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
    SELECT 1 
    FROM `settings_countries` 
    WHERE `country_name` = :name
    ");
    $stmt->execute([
      ':name'   => $country_name
    ]);

    if ($stmt->rowCount() > 0) {
        message('Ошибка', 'Данная страна уже имеется!', 'error', '');
    }

    $stmt = $pdo->prepare("
        INSERT INTO `settings_countries` (
            `country_name`,
            `country_status`
        ) VALUES (
            :name,
            :status
        )
    ");

    $stmt->execute([
        ':name'     => $country_name,
        ':status'   => $country_status
    ]);

    message('Уведомление', 'Добавление выполнено!', 'success', 'settings-countries');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось добавить страну. Попробуйте позже.', 'error', '');
}