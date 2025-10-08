<?php

$city_id = valid($_POST['city-id'] ?? '');

if (empty($city_id)) {
    redirectAJAX('settings-cities');
}

if (!preg_match('/^[0-9]{1,11}$/u', $city_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT 1 
        FROM `settings_cities` 
        WHERE `city_status` > '0' 
        AND `city_id`       = :city_id
    ");
    $stmt->execute([':city_id' => $city_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Такого города нет или он уже удален!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_cities` 
        SET `city_status` = '0' 
        WHERE `city_id`   = :city_id
    ");
    $stmt->execute([
        ':city_id' => $city_id
    ]);

    message('Уведомление', 'Удаление выполнено!', 'success', 'settings-cities');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось выполнить удаление. Попробуйте позже.', 'error', '');
}