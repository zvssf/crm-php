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

    $stmt = $pdo->prepare("SELECT * FROM `settings_countries` WHERE `country_id` = :country_id");
    $stmt->execute([':country_id' => $country_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Страна не найдено!', 'error', '');
    }

    $country_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($country_data['country_status'] > '0') {
        message('Ошибка', 'Стране не требуется восстановление!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_countries` 
        SET `country_status` = '1' 
        WHERE `country_id`   = :country_id
    ");
    $stmt->execute([':country_id' => $country_id]);

    message('Уведомление', 'Восстановление выполнено!', 'success', 'settings-countries');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось восстановить направление. Попробуйте позже.', 'error', '');
}