<?php

$center_id = valid($_POST['center-edit-id'] ?? '');
$field_settings_json = $_POST['field_settings'] ?? '';

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
    $pdo->beginTransaction();

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
        WHERE `center_name` = :name AND `center_id` != :center_id
        ");
        $stmt->execute([
          ':name'       => $center_name,
          ':center_id'  => $center_id
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

    // Обновляем настройки полей: сначала удаляем старые, потом вставляем новые
    $stmt_delete = $pdo->prepare("DELETE FROM `settings_center_fields` WHERE `center_id` = :center_id");
    $stmt_delete->execute([':center_id' => $center_id]);

    if (!empty($field_settings_json)) {
        $field_settings = json_decode($field_settings_json, true);
        
        if (is_array($field_settings)) {
            $sql_fields = "
                INSERT INTO `settings_center_fields` (
                    `center_id`, 
                    `field_name`, 
                    `is_visible`, 
                    `is_required`
                ) VALUES (
                    :center_id, 
                    :field_name, 
                    :is_visible, 
                    :is_required
                )
            ";
            $stmt_fields = $pdo->prepare($sql_fields);

            foreach ($field_settings as $field_name => $settings) {
                $stmt_fields->execute([
                    ':center_id'    => $center_id,
                    ':field_name'   => $field_name,
                    ':is_visible'   => !empty($settings['is_visible']) ? 1 : 0,
                    ':is_required'  => !empty($settings['is_required']) ? 1 : 0
                ]);
            }
        }
    }

    $pdo->commit();

    message('Уведомление', 'Сохранение выполнено!', 'success', 'settings-centers');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сохранить изменения. Попробуйте позже.', 'error', '');
}