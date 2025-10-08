<?php

$center_id = valid($_POST['center-id'] ?? '');

if (empty($center_id)) {
    redirectAJAX('settings-centers');
}

if (!preg_match('/^[0-9]{1,11}$/u', $center_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT 1 
        FROM `settings_centers` 
        WHERE `center_status` > '0' 
        AND `center_id`       = :center_id
    ");
    $stmt->execute([':center_id' => $center_id]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Такого визового центра нет или он уже удален!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_centers` 
        SET `center_status` = '0' 
        WHERE `center_id`   = :center_id
    ");
    $stmt->execute([
      ':center_id' => $center_id
    ]);

    message('Уведомление', 'Удаление выполнено!', 'success', 'settings-centers');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось выполнить удаление. Попробуйте позже.', 'error', '');
}