<?php
$center_id = valid($_POST['center_id'] ?? '');

$first_name  = valid($_POST['first_name'] ?? '');
$last_name   = valid($_POST['last_name'] ?? '');
$middle_name = valid($_POST['middle_name'] ?? null);
$gender      = valid($_POST['gender'] ?? 'male');
$phone_code         = valid($_POST['phone_code'] ?? null);
$phone_number       = valid($_POST['phone_number'] ?? null);
$phone              = !empty($phone_code) || !empty($phone_number) ? '+' . preg_replace('/[^0-9]/', '', $phone_code . $phone_number) : null; // Оставляем для обратной совместимости, если где-то используется
$email       = valid($_POST['email'] ?? null);

$passport_number_raw  = valid($_POST['passport_number'] ?? null);

if (!empty($passport_number_raw) && !preg_match('/^[a-zA-Z0-9]+$/', $passport_number_raw)) {
    message('Ошибка', 'Номер паспорта должен содержать только латинские буквы и цифры!', 'error', '');
}

$passport_number      = $passport_number_raw;
$birth_date_raw       = valid($_POST['birth_date'] ?? null);
$passport_expiry_raw  = valid($_POST['passport_expiry_date'] ?? null);
$nationality          = valid($_POST['nationality'] ?? null);

$monitoring_dates_raw = valid($_POST['monitoring_dates'] ?? null);
$visit_dates_raw    = valid($_POST['visit_dates'] ?? null);
$days_until_visit   = valid($_POST['days_until_visit'] ?? null);
$notes              = valid($_POST['notes'] ?? null);

$middle_name = !empty($middle_name) ? $middle_name : null;
$phone_code = !empty($phone_code) ? preg_replace('/[^0-9]/', '', $phone_code) : null;
$phone_number = !empty($phone_number) ? preg_replace('/[^0-9]/', '', $phone_number) : null;
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

$personas = $_POST['personas'] ?? [];

// --- НАЧАЛО БЛОКА ВАЛИДАЦИИ ---

// Загружаем настройки полей для этого визового центра
$field_settings = [];
if ($center_id) {
    $pdo_temp = db_connect();
    $stmt_fields = $pdo_temp->prepare("SELECT field_name, is_required FROM settings_center_fields WHERE center_id = ? AND is_required = 1");
    $stmt_fields->execute([$center_id]);
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
if (isset($field_settings['monitoring_dates'])) $validate($monitoring_dates_raw, 'Поле "Даты мониторинга" обязательно для заполнения!');
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

// --- ДАТЫ МОНИТОРИНГА ---
$monitoring_date_start = null;
$monitoring_date_end = null;
if (!empty($monitoring_dates_raw)) {
    $dates = explode(' - ', $monitoring_dates_raw);
    if (count($dates) == 2) {
        $monitoring_date_start = DateTime::createFromFormat('d.m.Y', trim($dates[0]))->format('Y-m-d');
        $monitoring_date_end = DateTime::createFromFormat('d.m.Y', trim($dates[1]))->format('Y-m-d');
    }
}

// --- ДАТЫ ВИЗИТА ---
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

    // --- ОБЩАЯ ВАЛИДАЦИЯ СТОИМОСТИ ---
    if (!empty($city_ids)) {
        if ($sale_price === null || $sale_price === '') {
            message('Ошибка', 'Необходимо указать стоимость!', 'error', '');
        }
        if (!is_numeric($sale_price)) {
            message('Ошибка', 'Некорректная стоимость', 'error', '');
        }
    }
    // --- КОНЕЦ ВАЛИДАЦИИ ---


    $family_id = null;

    // ЕСЛИ ЕСТЬ ДОПОЛНИТЕЛЬНЫЕ ПЕРСОНЫ, СОЗДАЕМ СЕМЬЮ
    if (!empty($personas)) {
        $stmt_family = $pdo->prepare("INSERT INTO `families` (`created_at`) VALUES (NOW())");
        $stmt_family->execute();
        $family_id = $pdo->lastInsertId();
    }

    // --- СОХРАНЕНИЕ ОСНОВНОЙ АНКЕТЫ ---
    $sql = "
        INSERT INTO `clients` (
            `family_id`, `center_id`, `agent_id`, `creator_id`, `client_name`, `client_status`, `first_name`, `last_name`, `middle_name`, 
            `gender`, `phone_code`, `phone_number`, `email`, `passport_number`, `birth_date`, `passport_expiry_date`, 
            `nationality`, `monitoring_date_start`, `monitoring_date_end`, `visit_date_start`, `visit_date_end`, `days_until_visit`, `notes`, `sale_price`
        ) VALUES (
            :family_id, :center_id, :agent_id, :creator_id, :client_name, :status, :first_name, :last_name, :middle_name, 
            :gender, :phone_code, :phone_number, :email, :passport_number, :birth_date, :passport_expiry_date, 
            :nationality, :monitoring_date_start, :monitoring_date_end, :visit_date_start, :visit_date_end, :days_until_visit, :notes, :sale_price
        )
    ";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':family_id' => $family_id, // Добавлено поле
        ':center_id' => $center_id,
        ':agent_id' => $agent_id,
        ':creator_id' => $user_data['user_id'],
        ':client_name' => $client_name,
        ':status' => $status,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':middle_name' => $middle_name,
        ':gender' => $gender,
        ':phone_code' => $phone_code,
        ':phone_number' => $phone_number,
        ':email' => $email,
        ':passport_number' => $passport_number,
        ':birth_date' => $birth_date,
        ':passport_expiry_date' => $passport_expiry_date,
        ':nationality' => $nationality,
        ':monitoring_date_start' => $monitoring_date_start,
        ':monitoring_date_end' => $monitoring_date_end,
        ':visit_date_start' => $visit_date_start,
        ':visit_date_end' => $visit_date_end,
        ':days_until_visit' => $days_until_visit,
        ':notes' => $notes,
        ':sale_price' => $sale_price
    ]);

    $new_client_id = $pdo->lastInsertId();

    if ($new_client_id > 0) {
        // Сохраняем значения доп. полей для ОСНОВНОЙ анкеты
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

        // --- СОХРАНЕНИЕ ДОПОЛНИТЕЛЬНЫХ ПЕРСОН ---
        if (!empty($personas) && $family_id) {
            foreach ($personas as $persona_data) {
                // Валидация данных персоны (упрощенная, но в стиле проекта)
                $p_first_name = valid($persona_data['first_name'] ?? '');
                $p_last_name = valid($persona_data['last_name'] ?? '');
                $p_passport_number = valid($persona_data['passport_number'] ?? '');

                if (empty($p_first_name) || empty($p_last_name) || empty($p_passport_number)) {
                    // Пропускаем персону, если обязательные поля не заполнены
                    continue;
                }

                $p_middle_name = valid($persona_data['middle_name'] ?? null);
                $p_gender = valid($persona_data['gender'] ?? 'male');
                $p_phone_code = valid($persona_data['phone_code'] ?? null);
                $p_phone_number = valid($persona_data['phone_number'] ?? null);
                $p_email = valid($persona_data['email'] ?? null);
                $p_birth_date_raw = valid($persona_data['birth_date'] ?? null);
                $p_passport_expiry_raw = valid($persona_data['passport_expiry_date'] ?? null);
                $p_nationality = valid($persona_data['nationality'] ?? null);
                $p_additional_fields = $persona_data['additional_fields'] ?? [];
                
                $p_birth_date = !empty($p_birth_date_raw) ? DateTime::createFromFormat('d.m.Y', $p_birth_date_raw)->format('Y-m-d') : null;
                $p_passport_expiry_date = !empty($p_passport_expiry_raw) ? DateTime::createFromFormat('d.m.Y', $p_passport_expiry_raw)->format('Y-m-d') : null;

                $sql_relatives = "
                    INSERT INTO `client_relatives` (
                        `family_id`, `first_name`, `last_name`, `middle_name`, `gender`, `phone_code`, `phone_number`, 
                        `email`, `passport_number`, `birth_date`, `passport_expiry_date`, `nationality`
                    ) VALUES (
                        :family_id, :first_name, :last_name, :middle_name, :gender, :phone_code, :phone_number,
                        :email, :passport_number, :birth_date, :passport_expiry_date, :nationality
                    )
                ";
                $stmt_relative = $pdo->prepare($sql_relatives);
                $stmt_relative->execute([
                    ':family_id' => $family_id,
                    ':first_name' => $p_first_name,
                    ':last_name' => $p_last_name,
                    ':middle_name' => !empty($p_middle_name) ? $p_middle_name : null,
                    ':gender' => $p_gender,
                    ':phone_code' => !empty($p_phone_code) ? preg_replace('/[^0-9]/', '', $p_phone_code) : null,
                    ':phone_number' => !empty($p_phone_number) ? preg_replace('/[^0-9]/', '', $p_phone_number) : null,
                    ':email' => !empty($p_email) ? $p_email : null,
                    ':passport_number' => $p_passport_number,
                    ':birth_date' => $p_birth_date,
                    ':passport_expiry_date' => $p_passport_expiry_date,
                    ':nationality' => !empty($p_nationality) ? $p_nationality : null
                ]);

                $new_relative_id = $pdo->lastInsertId();

                // Сохраняем доп. поля для персоны
                if ($new_relative_id > 0 && !empty($p_additional_fields)) {
                    $stmt_relative_inputs = $pdo->prepare("
                        INSERT INTO `client_input_values` (`relative_id`, `input_id`, `value`) 
                        VALUES (:relative_id, :input_id, :value)
                    ");
                    foreach ($p_additional_fields as $input_id => $value) {
                         $value = valid($value);
                         if (!empty($value) && is_numeric($input_id)) {
                            $stmt_relative_inputs->execute([
                                ':relative_id' => $new_relative_id,
                                ':input_id'  => $input_id,
                                ':value'     => $value
                            ]);
                        }
                    }
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