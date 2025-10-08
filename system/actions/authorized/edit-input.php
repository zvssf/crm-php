<?php


$input_id = valid($_POST['input-edit-id'] ?? '');

if (empty($input_id)) {
    redirectAJAX('settings-inputs');
}

if (!preg_match('/^[0-9]{1,11}$/u', $input_id)) {
    message('Ошибка', 'Недопустимое значение ID!', 'error', '');
}

$input_name         = valid($_POST['input-name'] ?? '');
$input_type         = valid($_POST['select-input-type'] ?? '');
$input_select_data  = valid($_POST['input-select-data'] ?? '');

$validate = function($value, $pattern, $emptyMsg, $invalidMsg) {
    if (empty($value)) {
        message('Ошибка', $emptyMsg, 'error', '');
    }
    if (!preg_match('/^' . $pattern . '$/u', $value)) {
        message('Ошибка', $invalidMsg, 'error', '');
    }
};

$validate($input_name,            '[a-zA-Zа-яА-Я0-9 ]{3,25}',     'Введите название дополнительного поля!', 'Недопустимое значение названия!');
$validate($input_type,            '[1-3]',                        'Выберите тип дополнительного поля!',     'Недопустимое значение типа!');
if ($input_type === '2' || $input_type === '3') {
    $validate($input_select_data, '[a-zA-Zа-яА-Я0-9\| ]{1,128}',  'Введите значения для поля!',             'Недопустимые значения для поля!');
} else {
    $input_select_data = '';
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
    SELECT * 
    FROM `settings_inputs` 
    WHERE `input_id` = :input_id
    ");
    $stmt->execute([
      ':input_id' => $input_id
    ]);

    if ($stmt->rowCount() === 0) {
        message('Ошибка', 'Дополнительное поле не найдено!', 'error', '');
    }

    $input_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($input_data['input_status'] === '0') {
        message('Ошибка', 'Дополнительное поле удалено!', 'error', '');
    }

    if ($input_data['input_name'] !== $input_name) {
        $stmt = $pdo->prepare("
        SELECT 1 
        FROM `settings_inputs` 
        WHERE `input_name` = :name
        ");
        $stmt->execute([
          ':name' => $input_name
        ]);
        if ($stmt->rowCount() > 0) {
            message('Ошибка', 'Данное дополнительное поле уже существует!', 'error', '');
        }
    }

    $stmt = $pdo->prepare("
        UPDATE `settings_inputs` 
        SET 
            `input_name`        = :name,
            `input_type`        = :type,
            `input_select_data` = :select_data
        WHERE `input_id`        = :input_id
    ");

    $stmt->execute([
        ':name'         => $input_name,
        ':type'         => $input_type,
        ':select_data'  => $input_select_data,
        ':input_id'     => $input_id
    ]);

    message('Уведомление', 'Сохранение выполнено!', 'success', 'settings-inputs');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сохранить изменения. Попробуйте позже.', 'error', '');
}