<?php
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

    $country_id = $pdo->lastInsertId();

    if ($country_id && !empty($field_settings_json)) {
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
    message('Уведомление', 'Добавление выполнено!', 'success', 'settings-countries');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось добавить страну. Попробуйте позже.', 'error', '');
}