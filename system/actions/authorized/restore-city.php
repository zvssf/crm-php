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
    SELECT * 
    FROM `settings_cities` 
    WHERE `city_id` = :city_id
    ");
    $stmt->execute([
        ':city_id' => $city_id
    ]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Город не найден!', 'error', '');
    }

    $city_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($city_data['city_status'] > '0') {
        message('Ошибка', 'Городу не требуется восстановление!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_cities` 
        SET `city_status` = '1' 
        WHERE `city_id`   = :city_id
    ");
    $stmt->execute([
        ':city_id' => $city_id
    ]);

    message('Уведомление', 'Восстановление выполнено!', 'success', 'settings-cities');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось восстановить визовый центр. Попробуйте позже.', 'error', '');
}