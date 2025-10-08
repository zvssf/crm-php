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

    if ($center_data['center_status'] > '0') {
        message('Ошибка', 'Визовому центру не требуется восстановление!', 'error', '');
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_centers` 
        SET `center_status` = '1' 
        WHERE `center_id`   = :center_id
    ");
    $stmt->execute([':center_id' => $center_id]);

    message('Уведомление', 'Восстановление выполнено!', 'success', 'settings-centers');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось восстановить визовый центр. Попробуйте позже.', 'error', '');
}