<?php
$city_name = valid($_POST['city-name'] ?? '');
$city_category = valid($_POST['city-category'] ?? NULL);
$country_id = valid($_POST['select-country'] ?? '');
$city_status = valid($_POST['select-status'] ?? '');
$cost_price = valid($_POST['cost_price'] ?? '0.00');
$min_sale_price = valid($_POST['min_sale_price'] ?? '0.00');
$inputs = $_POST['inputs'] ?? [];
$suppliers = $_POST['suppliers'] ?? [];

$validate = function ($value, $pattern, $emptyMsg, $invalidMsg) {
    if (empty($value) || $value === 'hide') {
        message('Ошибка', $emptyMsg, 'error', '');
    }
    if (!preg_match('/^' . $pattern . '$/u', $value)) {
        message('Ошибка', $invalidMsg, 'error', '');
    }
};

$validate($city_name, '[a-zA-Zа-яА-Я0-9 ]{3,100}', 'Введите название города!', 'Недопустимое значение названия!');
$validate($country_id, '[0-9]{1,11}', 'Выберите страну ВЦ!', 'Недопустимое значение страны ВЦ!');
$validate($city_status, '[0-9]', 'Выберите статус города!', 'Недопустимое значение статуса!');
$validate($cost_price, '[0-9.]{1,13}', 'Укажите себестоимость!', 'Недопустимое значение себестоимости!');
$validate($min_sale_price, '[0-9.]{1,13}', 'Укажите мин. цену продажи!', 'Недопустимое значение мин. цены!');

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT 
            1 
        FROM 
            `settings_cities` 
        WHERE 
            `city_name` = :name AND `city_category` = :category AND `country_id` = :country_id
    ");
    $stmt->execute([
        ':name' => $city_name,
        ':category' => $city_category,
        ':country_id' => $country_id
    ]);
    if ($stmt->rowCount() > 0) {
        message('Ошибка', 'Такой город с такой категорией уже добавлен для этой страны!', 'error', '');
    }

    $stmt = $pdo->prepare("
        INSERT INTO `settings_cities` (
            `city_name`,
            `city_category`,
            `country_id`, 
            `city_status`,
            `cost_price`,
            `min_sale_price`
        ) VALUES (
            :name, 
            :category,
            :country_id, 
            :status,
            :cost_price,
            :min_sale_price
        )
    ");
    $stmt->execute([
        ':name' => $city_name,
        ':category' => $city_category,
        ':country_id' => $country_id,
        ':status' => $city_status,
        ':cost_price' => $cost_price,
        ':min_sale_price' => $min_sale_price
    ]);

    $city_id = $pdo->lastInsertId();

    if (!empty($inputs)) {
        $stmt = $pdo->prepare("
            INSERT INTO `settings_city_inputs` (
                `city_id`, 
                `input_id`
            ) VALUES (
                :city_id, 
                :input_id
            )
        ");

        foreach ($inputs as $input_id) {
            if (!is_numeric($input_id)) {
                continue;
            }

            $stmt->execute([
                ':city_id' => $city_id,
                ':input_id' => $input_id
            ]);
        }

        if (!empty($suppliers)) {
            $stmt_supplier = $pdo->prepare("
                INSERT INTO `city_suppliers` (
                    `city_id`, 
                    `supplier_id`
                ) VALUES (
                    :city_id, 
                    :supplier_id
                )
            ");
            foreach ($suppliers as $supplier_id) {
                if (is_numeric($supplier_id)) {
                    $stmt_supplier->execute([
                        ':city_id' => $city_id,
                        ':supplier_id' => $supplier_id
                    ]);
                }
            }
        }
    }

    $pdo->commit();
    message('Уведомление', 'Город успешно добавлен!', 'success', 'settings-cities');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось добавить город. Попробуйте позже.', 'error', '');
}