<?php
$client_id = valid($_POST['client_id'] ?? '');

$first_name = valid($_POST['first_name'] ?? '');
$last_name = valid($_POST['last_name'] ?? '');
$middle_name = valid($_POST['middle_name'] ?? '');
$gender = valid($_POST['gender'] ?? '');
$phone_code = valid($_POST['phone_code'] ?? '');
$phone_number = valid($_POST['phone_number'] ?? '');
$phone = '+' . preg_replace('/[^0-9]/', '', $phone_code . $phone_number);
$email = valid($_POST['email'] ?? '');

$passport_number_raw = valid($_POST['passport_number'] ?? '');
$passport_number = preg_replace('/[^0-9]/', '', $passport_number_raw);
$birth_date_raw = valid($_POST['birth_date'] ?? '');
$passport_expiry_raw = valid($_POST['passport_expiry_date'] ?? '');
$nationality = valid($_POST['nationality'] ?? '');

$visit_dates_raw = valid($_POST['visit_dates'] ?? '');
$days_until_visit = valid($_POST['days_until_visit'] ?? '');
$notes = valid($_POST['notes'] ?? '');
$city_ids = $_POST['city_ids'] ?? [];
$additional_fields = $_POST['additional_fields'] ?? [];
$sale_price = valid($_POST['sale_price'] ?? null);

$agent_id = null;
if ($user_data['user_group'] != 4) {
    $agent_id = valid($_POST['agent_id'] ?? null);
    $agent_id = empty($agent_id) ? null : $agent_id;
} else {
    $agent_id = $user_data['user_id'];
}

$validate = function ($value, $emptyMsg) {
    if (empty($value)) {
        message('Ошибка', $emptyMsg, 'error', '');
    }
};

$validate($client_id, 'ID анкеты не найден!');
$validate($first_name, 'Поле "Имя" обязательно для заполнения!');
$validate($last_name, 'Поле "Фамилия" обязательно для заполнения!');
$validate($phone_code, 'Поле "Код страны" телефона обязательно для заполнения!');
$validate($phone_number, 'Поле "Номер телефона" обязательно для заполнения!');
$validate($passport_number_raw, 'Поле "Номер паспорта" обязательно для заполнения!');
$validate($city_ids, 'Необходимо выбрать хотя бы одну категорию!');

$client_name_parts = array_filter([$last_name, $first_name, $middle_name]);
$client_name = trim(implode(' ', $client_name_parts));

$birth_date = !empty($birth_date_raw) ? DateTime::createFromFormat('d.m.Y', $birth_date_raw)->format('Y-m-d') : null;
$passport_expiry_date = !empty($passport_expiry_raw) ? DateTime::createFromFormat('d.m.Y', $passport_expiry_raw)->format('Y-m-d') : null;
$visit_date_start = null;
$visit_date_end = null;
if (!empty($visit_dates_raw)) {
    $dates = explode(' - ', $visit_dates_raw);
    if (count($dates) == 2) {
        $visit_date_start = DateTime::createFromFormat('d.m.Y', trim($dates[0]))->format('Y-m-d');
        $visit_date_end = DateTime::createFromFormat('d.m.Y', trim($dates[1]))->format('Y-m-d');
    }
}

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    if (!empty($city_ids)) {
        if ($sale_price === null || $sale_price === '') {
            message('Ошибка', 'Необходимо указать стоимость!', 'error', '');
        }

        if (!is_numeric($sale_price)) {
            message('Ошибка', 'Некорректная стоимость', 'error', '');
        }
    }

    $stmt_client = $pdo->prepare("SELECT `client_status`, `center_id` FROM `clients` WHERE `client_id` = :client_id");
    $stmt_client->execute([':client_id' => $client_id]);
    $current_client_data = $stmt_client->fetch(PDO::FETCH_ASSOC);
    $center_id = $current_client_data['center_id'];
    $new_status = $current_client_data['client_status'];

    $sql = "
        UPDATE `clients` SET
            `agent_id` = :agent_id, `client_name` = :client_name, `client_status` = :status, `first_name` = :first_name, `last_name` = :last_name, 
            `middle_name` = :middle_name, `gender` = :gender, `phone` = :phone, `email` = :email, 
            `passport_number` = :passport_number, `birth_date` = :birth_date, 
            `passport_expiry_date` = :passport_expiry_date, `nationality` = :nationality, 
            `visit_date_start` = :visit_date_start, 
            `visit_date_end` = :visit_date_end, `days_until_visit` = :days_until_visit, `notes` = :notes, `sale_price` = :sale_price
        WHERE `client_id` = :client_id
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':agent_id' => $agent_id,
        ':client_name' => $client_name,
        ':status' => $new_status,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':middle_name' => !empty($middle_name) ? $middle_name : null,
        ':gender' => $gender,
        ':phone' => $phone,
        ':email' => !empty($email) ? $email : null,
        ':passport_number' => !empty($passport_number) ? $passport_number : null,
        ':birth_date' => $birth_date,
        ':passport_expiry_date' => $passport_expiry_date,
        ':nationality' => !empty($nationality) ? $nationality : null,
        ':visit_date_start' => $visit_date_start,
        ':visit_date_end' => $visit_date_end,
        ':days_until_visit' => ($days_until_visit !== '' && $days_until_visit !== null) ? (int) $days_until_visit : null,
        ':notes' => !empty($notes) ? $notes : null,
        ':sale_price' => $sale_price,
        ':client_id' => $client_id
    ]);

    $stmt_delete_inputs = $pdo->prepare("DELETE FROM `client_input_values` WHERE `client_id` = :client_id");
    $stmt_delete_inputs->execute([':client_id' => $client_id]);

    if (!empty($additional_fields)) {
        $stmt_inputs = $pdo->prepare("
            INSERT INTO `client_input_values` (`client_id`, `input_id`, `value`) 
            VALUES (:client_id, :input_id, :value)
        ");
        foreach ($additional_fields as $input_id => $value) {
            $value = valid($value);
            if (!empty($value) && is_numeric($input_id)) {
                $stmt_inputs->execute([
                    ':client_id' => $client_id,
                    ':input_id' => $input_id,
                    ':value' => $value
                ]);
            }
        }
    }

    if (!empty($city_ids)) {
        // Удаляем старые категории перед добавлением новых
        $stmt_delete_cities = $pdo->prepare("DELETE FROM `client_cities` WHERE `client_id` = :client_id");
        $stmt_delete_cities->execute([':client_id' => $client_id]);

        $stmt_city = $pdo->prepare("
            INSERT INTO `client_cities` (`client_id`, `city_id`) VALUES (:client_id, :city_id)
        ");
        foreach ($city_ids as $city_id) {
            if (is_numeric($city_id)) {
                $stmt_city->execute([
                    ':client_id' => $client_id,
                    ':city_id' => $city_id
                ]);
            }
        }
    }

    $pdo->commit();

    message('Уведомление', 'Изменения успешно сохранены!', 'success', 'clients&center=' . $center_id . '&status=' . $new_status);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сохранить изменения. SQL Error: ' . $e->getMessage(), 'error', '');
}
$pdo = null;