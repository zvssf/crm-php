<?php
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

$validate($input_name,              '[a-zA-Zа-яА-Я0-9 ]{3,25}',     'Введите название дополнительного поля!', 'Недопустимое значение названия!');
$validate($input_type,              '[1-3]',                        'Выберите тип дополнительного поля!',     'Недопустимое значение типа!');
if ($input_type === '2' || $input_type === '3') {
    $validate($input_select_data,   '[a-zA-Zа-яА-Я0-9\| ]{1,128}',  'Введите значения для поля!',             'Недопустимые значения для поля!');
} else {
    $input_select_data = '';
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
    SELECT 1 
    FROM `settings_inputs` 
    WHERE `input_name` = :name
    ");
    $stmt->execute([
      ':name' => $input_name
    ]);

    if ($stmt->rowCount() > 0) {
        message('Ошибка', 'Данное дополнительное поле уже имеется!', 'error', '');
    }

    $stmt = $pdo->prepare("
        INSERT INTO `settings_inputs` (
            `input_name`,
            `input_type`,
            `input_status`,
            `input_select_data`
        ) VALUES (
            :name,
            :type,
            '1',
            :select_data
        )
    ");

    $stmt->execute([
        ':name'         => $input_name,
        ':type'         => $input_type,
        ':select_data'  => $input_select_data
    ]);

    message('Уведомление', 'Добавление выполнено!', 'success', 'settings-inputs');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось добавить поле. Попробуйте позже.', 'error', '');
}