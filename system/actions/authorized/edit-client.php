<?php
$client_id = valid($_POST['client_id'] ?? '');

$first_name = valid($_POST['first_name'] ?? '');
$last_name = valid($_POST['last_name'] ?? '');
$middle_name = valid($_POST['middle_name'] ?? '');
$gender = valid($_POST['gender'] ?? '');
$phone_code = valid($_POST['phone_code'] ?? null);
$phone_number = valid($_POST['phone_number'] ?? null);
$email = valid($_POST['email'] ?? '');

$passport_number_raw = valid($_POST['passport_number'] ?? '');

if (!empty($passport_number_raw) && !preg_match('/^[0-9]+$/', $passport_number_raw)) {
    message('Ошибка', 'Номер паспорта должен содержать только цифры!', 'error', '');
}

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

// --- НАЧАЛО БЛОКА ВАЛИДАЦИИ ---

$center_id = null;
if ($client_id) {
    $pdo_temp = db_connect();
    $stmt_center = $pdo_temp->prepare("SELECT center_id FROM clients WHERE client_id = ?");
    $stmt_center->execute([$client_id]);
    $center_id = $stmt_center->fetchColumn();
}

$country_id = null;
if ($center_id) {
    $pdo_temp = db_connect();
    $stmt_country = $pdo_temp->prepare("SELECT country_id FROM settings_centers WHERE center_id = ?");
    $stmt_country->execute([$center_id]);
    $country_id = $stmt_country->fetchColumn();
    $pdo_temp = null;
}

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
$validate($client_id, 'ID анкеты не найден!');
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
if (isset($field_settings['notes'])) $validate($notes, 'Поле "Ваши пометки" обязательно для заполнения!');

// --- КОНЕЦ БЛОКА ВАЛИДАЦИИ ---

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

    // --- НАЧАЛО БЛОКА ПРОВЕРКИ ИЗМЕНЕНИЯ ЦЕНЫ ДИРЕКТОРОМ ---
    $stmt_old_client = $pdo->prepare("SELECT `client_status`, `agent_id`, `sale_price` FROM `clients` WHERE `client_id` = :client_id");
    $stmt_old_client->execute([':client_id' => $client_id]);
    $old_client_data = $stmt_old_client->fetch(PDO::FETCH_ASSOC);

    if ($old_client_data && $old_client_data['client_status'] == 2 && $user_data['user_group'] == 1) {
        $old_sale_price = (float) $old_client_data['sale_price'];
        $new_sale_price = (float) $sale_price;

        if ($new_sale_price > $old_sale_price) {
            // Сценарий А: Цена увеличилась
            $price_difference = $new_sale_price - $old_sale_price;
            
            $stmt_agent_balance = $pdo->prepare("SELECT `user_balance` FROM `users` WHERE `user_id` = :agent_id");
            $stmt_agent_balance->execute([':agent_id' => $old_client_data['agent_id']]);
            $agent_balance = (float) $stmt_agent_balance->fetchColumn();

            if ($agent_balance < $price_difference) {
                message('Ошибка', 'У агента недостаточно средств на балансе для покрытия разницы в стоимости!', 'error', '');
            }

            // Списываем разницу с агента и добавляем к оплате анкеты
            $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :diff WHERE `user_id` = :agent_id")
                ->execute([':diff' => $price_difference, ':agent_id' => $old_client_data['agent_id']]);
            
            $pdo->prepare("UPDATE `clients` SET `paid_from_balance` = `paid_from_balance` + :diff WHERE `client_id` = :client_id")
                ->execute([':diff' => $price_difference, ':client_id' => $client_id]);

        } elseif ($new_sale_price < $old_sale_price) {
            // Сценарий Б: Цена уменьшилась
            $refund_amount = $old_sale_price - $new_sale_price;

            // Уменьшаем сумму оплаты по анкете
            $pdo->prepare("UPDATE `clients` SET `paid_from_balance` = `paid_from_balance` - :refund WHERE `client_id` = :client_id")
                ->execute([':refund' => $refund_amount, ':client_id' => $client_id]);
            
            // Вызываем перераспределение средств
            process_agent_repayments($pdo, $old_client_data['agent_id'], $refund_amount);
        }
    }
    // --- КОНЕЦ БЛОКА ПРОВЕРКИ ИЗМЕНЕНИЯ ЦЕНЫ ДИРЕКТОРОМ ---

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
            `middle_name` = :middle_name, `gender` = :gender, `phone_code` = :phone_code, `phone_number` = :phone_number, `email` = :email, 
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
        ':phone_code' => !empty($phone_code) ? preg_replace('/[^0-9]/', '', $phone_code) : null,
        ':phone_number' => !empty($phone_number) ? preg_replace('/[^0-9]/', '', $phone_number) : null,
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