<?php

$country_id = valid($_POST['country-edit-id'] ?? '');

if (empty($country_id)) {
    redirectAJAX('settings-countries');
}

if (!preg_match('/^[0-9]{1,11}$/u', $country_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

$country_name      = valid($_POST['country-name'] ?? '');
$country_status    = valid($_POST['select-country-status'] ?? '');

$field_settings_json = $_POST['field_settings'] ?? '';

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
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
    SELECT * 
    FROM `settings_countries` 
    WHERE `country_id` = :country_id
    ");
    $stmt->execute([
      ':country_id' => $country_id
    ]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Страна не найдена!', 'error', '');
    }

    $country_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($country_data['country_status'] === '0') {
        message('Ошибка', 'Страна удалена!', 'error', '');
    }

    if ($country_data['country_name'] !== $country_name) {
        $stmt = $pdo->prepare("
        SELECT 1 
        FROM `settings_countries` 
        WHERE `country_name` = :name 
        AND `country_id` != :country_id
        ");
        $stmt->execute([
          ':name'   => $country_name,
          ':country_id' => $country_id
        ]);
        if ($stmt->rowCount() > 0) {
            message('Ошибка', 'Данная страна уже существует!', 'error', '');
        }
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_countries` 
        SET 
            `country_name`   = :name, 
            `country_status` = :status 
        WHERE `country_id`   = :country_id
    ");

    $stmt->execute([
        ':name'     => $country_name,
        ':status'   => $country_status,
        ':country_id'  => $country_id
    ]);

    // Обновляем настройки полей: сначала удаляем старые, потом вставляем новые
    $stmt_delete = $pdo->prepare("DELETE FROM `settings_country_fields` WHERE `country_id` = :country_id");
    $stmt_delete->execute([':country_id' => $country_id]);

    if (!empty($field_settings_json)) {
        $field_settings = json_decode($field_settings_json, true);
        
        if (is_array($field_settings)) {
            $sql_fields = "
                INSERT INTO `settings_country_fields` (
                    `country_id`, 
                    `field_name`, 
                    `is_visible`, 
                    `is_required`
                ) VALUES (
                    :country_id, 
                    :field_name, 
                    :is_visible, 
                    :is_required
                )
            ";
            $stmt_fields = $pdo->prepare($sql_fields);

            foreach ($field_settings as $field_name => $settings) {
                $stmt_fields->execute([
                    ':country_id'   => $country_id,
                    ':field_name'   => $field_name,
                    ':is_visible'   => !empty($settings['is_visible']) ? 1 : 0,
                    ':is_required'  => !empty($settings['is_required']) ? 1 : 0
                ]);
            }
        }
    }

    $pdo->commit();
    message('Уведомление', 'Сохранение выполнено!', 'success', 'settings-countries');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сохранить изменения. Попробуйте позже.', 'error', '');
}