<?php

$country_id = valid($_POST['country-id'] ?? '');

if (empty($country_id)) {
    redirectAJAX('settings-countries');
}

if (!preg_match('/^[0-9]{1,11}$/u', $country_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT 1 
        FROM `settings_countries` 
        WHERE `country_status` > '0' 
        AND `country_id`       = :country_id
    ");
    $stmt->execute([':country_id' => $country_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Такой страны нет или она уже удаленф!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_countries` 
        SET `country_status` = '0' 
        WHERE `country_id`   = :country_id
    ");
    $stmt->execute([':country_id' => $country_id]);

    message('Уведомление', 'Удаление выполнено!', 'success', 'settings-countries');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось выполнить удаление. Попробуйте позже.', 'error', '');
}