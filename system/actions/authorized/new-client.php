<?php
$center_id = valid($_POST['center_id'] ?? '');

$first_name  = valid($_POST['first_name'] ?? '');
$last_name   = valid($_POST['last_name'] ?? '');
$middle_name = valid($_POST['middle_name'] ?? null);
$gender      = valid($_POST['gender'] ?? 'male');
$phone_code         = valid($_POST['phone_code'] ?? '');
$phone_number       = valid($_POST['phone_number'] ?? '');
$phone              = !empty($phone_code) || !empty($phone_number) ? '+' . preg_replace('/[^0-9]/', '', $phone_code . $phone_number) : null;
$email       = valid($_POST['email'] ?? null);

$passport_number_raw  = valid($_POST['passport_number'] ?? null);
$passport_number      = preg_replace('/[^0-9]/', '', $passport_number_raw);
$birth_date_raw       = valid($_POST['birth_date'] ?? null);
$passport_expiry_raw  = valid($_POST['passport_expiry_date'] ?? null);
$nationality          = valid($_POST['nationality'] ?? null);

$visit_dates_raw    = valid($_POST['visit_dates'] ?? null);
$days_until_visit   = valid($_POST['days_until_visit'] ?? null);
$notes              = valid($_POST['notes'] ?? null);

$middle_name = !empty($middle_name) ? $middle_name : null;
$phone = !empty($phone) ? $phone : null;
$email = !empty($email) ? $email : null;
$passport_number = !empty($passport_number) ? $passport_number : null;
$nationality = !empty($nationality) ? $nationality : null;
$days_until_visit = ($days_until_visit !== '' && $days_until_visit !== null) ? (int)$days_until_visit : null;
$notes = !empty($notes) ? $notes : null;
$city_ids = $_POST['city_ids'] ?? [];
$additional_fields = $_POST['additional_fields'] ?? [];
$sale_price = valid($_POST['sale_price'] ?? null);

$agent_id = null;
if ($user_data['user_group'] == 4) {
    $agent_id = $user_data['user_id'];
} else {
    $agent_id = valid($_POST['agent_id'] ?? null);
}


// --- НАЧАЛО БЛОКА ВАЛИДАЦИИ ---

// Получаем ID страны для загрузки настроек
$country_id = null;
if ($center_id) {
    $pdo_temp = db_connect();
    $stmt_country = $pdo_temp->prepare("SELECT country_id FROM settings_centers WHERE center_id = ?");
    $stmt_country->execute([$center_id]);
    $country_id = $stmt_country->fetchColumn();
    $pdo_temp = null;
}

// Загружаем настройки полей для этой страны
$field_settings = [];
if ($country_id) {
    $pdo_temp = db_connect();
    $stmt_fields = $pdo_temp->prepare("SELECT field_name, is_required FROM settings_country_fields WHERE country_id = ? AND is_required = 1");
    $stmt_fields->execute([$country_id]);
    $db_settings = $stmt_fields->fetchAll(PDO::FETCH_KEY_PAIR);
    if ($db_settings) {
        $field_settings = $db_settings;
    }
    $pdo_temp = null;
}

$validate = function($value, $emptyMsg) {
    if (empty($value)) {
        message('Ошибка', $emptyMsg, 'error', '');
    }
};

// Стандартная обязательная валидация
$validate($center_id, 'ID визового центра не найден!');
$validate($first_name, 'Поле "Имя" обязательно для заполнения!');
$validate($last_name, 'Поле "Фамилия" обязательно для заполнения!');
$validate($passport_number_raw, 'Поле "Номер паспорта" обязательно для заполнения!');
if ($user_data['user_group'] != 4) {
    $validate($agent_id, 'Поле "Агент" обязательно для заполнения!');
}
$validate($city_ids, 'Необходимо выбрать хотя бы одну категорию!');
$validate($sale_price, 'Поле "Стоимость" обязательно для заполнения!');

// Динамическая валидация на основе настроек страны
if (isset($field_settings['middle_name'])) $validate($middle_name, 'Поле "Отчество" обязательно для заполнения!');
if (isset($field_settings['phone'])) {
    $validate($phone_code, 'Поле "Код страны" телефона обязательно для заполнения!');
    $validate($phone_number, 'Поле "Номер телефона" обязательно для заполнения!');
}
if (isset($field_settings['gender'])) $validate($gender, 'Поле "Пол" обязательно для заполнения!');
if (isset($field_settings['email'])) $validate($email, 'Поле "Email" обязательно для заполнения!');
if (isset($field_settings['birth_date'])) $validate($birth_date_raw, 'Поле "Дата рождения" обязательно для заполнения!');
if (isset($field_settings['passport_expiry_date'])) $validate($passport_expiry_raw, 'Поле "Срок действия паспорта" обязательно для заполнения!');
if (isset($field_settings['nationality'])) $validate($nationality, 'Поле "Национальность" обязательно для заполнения!');
if (isset($field_settings['visit_dates'])) $validate($visit_dates_raw, 'Поле "Даты визита" обязательно для заполнения!');
if (isset($field_settings['days_until_visit'])) $validate($days_until_visit, 'Поле "Дни до визита" обязательно для заполнения!');

// --- КОНЕЦ БЛОКА ВАЛИДАЦИИ ---

$client_name_parts = array_filter([$last_name, $first_name, $middle_name]);
$client_name = trim(implode(' ', $client_name_parts));

if (empty($client_name)) {
    $client_name = "Черновик анкеты";
}

$status = 3; // Все новые анкеты всегда отправляются в Черновики

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

    $sql = "
        INSERT INTO `clients` (
            `center_id`, `agent_id`, `creator_id`, `client_name`, `client_status`, `first_name`, `last_name`, `middle_name`, 
            `gender`, `phone`, `email`, `passport_number`, `birth_date`, `passport_expiry_date`, 
            `nationality`, `visit_date_start`, `visit_date_end`, `days_until_visit`, `notes`, `sale_price`
        ) VALUES (
            :center_id, :agent_id, :creator_id, :client_name, :status, :first_name, :last_name, :middle_name, 
            :gender, :phone, :email, :passport_number, :birth_date, :passport_expiry_date, 
            :nationality, :visit_date_start, :visit_date_end, :days_until_visit, :notes, :sale_price
        )
    ";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':center_id' => $center_id,
        ':agent_id' => $agent_id,
        ':creator_id' => $user_data['user_id'],
        ':client_name' => $client_name,
        ':status' => $status,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':middle_name' => $middle_name,
        ':gender' => $gender,
        ':phone' => $phone,
        ':email' => $email,
        ':passport_number' => $passport_number,
        ':birth_date' => $birth_date,
        ':passport_expiry_date' => $passport_expiry_date,
        ':nationality' => $nationality,
        ':visit_date_start' => $visit_date_start,
        ':visit_date_end' => $visit_date_end,
        ':days_until_visit' => $days_until_visit,
        ':notes' => $notes,
        ':sale_price' => $sale_price
    ]);

    $new_client_id = $pdo->lastInsertId();

    if ($new_client_id > 0) {
        // Сохраняем значения доп. полей
        if (!empty($additional_fields)) {
            $stmt_inputs = $pdo->prepare("
                INSERT INTO `client_input_values` (`client_id`, `input_id`, `value`) 
                VALUES (:client_id, :input_id, :value)
            ");
            foreach ($additional_fields as $input_id => $value) {
                $value = valid($value);
                if (!empty($value) && is_numeric($input_id)) {
                    $stmt_inputs->execute([
                        ':client_id' => $new_client_id,
                        ':input_id'  => $input_id,
                        ':value'     => $value
                    ]);
                }
            }
        }
        if (!empty($city_ids)) {
            $stmt_city = $pdo->prepare("
                INSERT INTO `client_cities` (`client_id`, `city_id`) VALUES (:client_id, :city_id)
            ");
            foreach ($city_ids as $city_id) {
                if (is_numeric($city_id)) {
                    $stmt_city->execute([
                        ':client_id' => $new_client_id,
                        ':city_id'   => $city_id
                    ]);
                }
            }
        }
        
        $pdo->commit();
        message('Уведомление', 'Анкета успешно создана!', 'success', 'clients&center=' . $center_id . '&status=' . $status);
    } else {
        $pdo->rollBack();
        message('Ошибка', 'Не удалось создать запись в базе данных.', 'error', '');
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось создать анкету. SQL Error: ' . $e->getMessage(), 'error', '');
}
$pdo = null;