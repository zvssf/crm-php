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

if (!empty($passport_number_raw) && !preg_match('/^[a-zA-Z0-9]+$/', $passport_number_raw)) {
    message('Ошибка', 'Номер паспорта должен содержать только латинские буквы и цифры!', 'error', '');
}

$passport_number = $passport_number_raw;
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

$personas = $_POST['personas'] ?? [];

// --- НАЧАЛО БЛОКА ВАЛИДАЦИИ ---

$center_id = null;
if ($client_id) {
    $pdo_temp = db_connect();
    $stmt_center = $pdo_temp->prepare("SELECT center_id FROM clients WHERE client_id = ?");
    $stmt_center->execute([$client_id]);
    $center_id = $stmt_center->fetchColumn();
}

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
$validate($client_id, 'ID анкеты не найден!');
$validate($first_name, 'Поле "Имя" обязательно для заполнения!');
$validate($last_name, 'Поле "Фамилия" обязательно для заполнения!');
$validate($passport_number_raw, 'Поле "Номер паспорта" обязательно для заполнения!');
if ($user_data['user_group'] != 4) {
    $validate($agent_id, 'Поле "Агент" обязательно для заполнения!');
}
$validate($city_ids, 'Необходимо выбрать хотя бы одну категорию!');
$validate($sale_price, 'Поле "Стоимость" обязательно для заполнения!');

// Валидация телефона (всегда обязательна, как на форме)
$validate($phone_code, 'Поле "Код страны" телефона обязательно для заполнения!');
$validate($phone_number, 'Поле "Номер телефона" обязательно для заполнения!');

// Динамическая валидация на основе настроек центра
if (isset($field_settings['middle_name'])) $validate($middle_name, 'Поле "Отчество" обязательно для заполнения!');
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

    // --- НАЧАЛО БЛОКА ИЗМЕНЕНИЯ СТОИМОСТИ ЗАПИСАННОЙ АНКЕТЫ ---
    $stmt_old_client = $pdo->prepare("SELECT * FROM `clients` WHERE `client_id` = :client_id FOR UPDATE");
    $stmt_old_client->execute([':client_id' => $client_id]);
    $old_client_data = $stmt_old_client->fetch(PDO::FETCH_ASSOC);

    // Логика применяется только для анкет в статусе "Записанные" (2) и только Директором (1)
    if ($old_client_data && $old_client_data['client_status'] == 2 && $user_data['user_group'] == 1) {
        
        $old_sale_price = (float) $old_client_data['sale_price'];
        $new_sale_price = (float) $sale_price;
        $price_difference = $new_sale_price - $old_sale_price;
        $agent_id_for_op = $old_client_data['agent_id'];

        // Если цена действительно изменилась
        if (abs($price_difference) > 0.01 && !empty($agent_id_for_op)) {
            
            // --- СЛУЧАЙ 1: ЦЕНА УМЕНЬШИЛАСЬ (Возврат) ---
            if ($price_difference < 0) {
                $refund_amount = abs($price_difference);

                // Уменьшаем сумму оплаты по анкете, сначала за счет кредита, потом за счет баланса
                $old_credit = (float)$old_client_data['paid_from_credit'];
                $credit_reduction = min($old_credit, $refund_amount);
                $balance_reduction = $refund_amount - $credit_reduction;

                $pdo->prepare("UPDATE `clients` SET `paid_from_credit` = `paid_from_credit` - :credit_reduct, `paid_from_balance` = `paid_from_balance` - :balance_reduct WHERE `client_id` = :client_id")
                    ->execute([':credit_reduct' => $credit_reduction, ':balance_reduct' => $balance_reduction, ':client_id' => $client_id]);
                
                // Если после уменьшения кредит стал нулевым, анкета считается полностью оплаченной
                if (($old_credit - $credit_reduction) < 0.01) {
                     $pdo->prepare("UPDATE `clients` SET `payment_status` = 1 WHERE `client_id` = :client_id")->execute([':client_id' => $client_id]);
                }
                
                // Возвращенную сумму отправляем на перераспределение в любом случае
                process_agent_repayments($pdo, $agent_id_for_op, $refund_amount);
            }
            // --- СЛУЧАЙ 2: ЦЕНА УВЕЛИЧИЛАСЬ (Доплата) ---
            else {
                $surcharge = $price_difference;

                $stmt_agent = $pdo->prepare("SELECT `user_balance` FROM `users` WHERE `user_id` = :agent_id FOR UPDATE");
                $stmt_agent->execute([':agent_id' => $agent_id_for_op]);
                $agent_balance = (float) $stmt_agent->fetchColumn();

                $payment_from_balance = min($surcharge, max(0, $agent_balance));
                $new_credit = $surcharge - $payment_from_balance;

                // Списываем ВСЮ сумму доплаты с баланса агента, уводя его в минус, если необходимо.
                // Эта операция игнорирует кредитный лимит.
                $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :surcharge WHERE `user_id` = :agent_id")
                    ->execute([':surcharge' => $surcharge, ':agent_id' => $agent_id_for_op]);
                
                // Обновляем анкету
                $update_params = [':payment' => $payment_from_balance, ':client_id' => $client_id];
                $sql_update_client = "UPDATE `clients` SET `paid_from_balance` = `paid_from_balance` + :payment";
                
                if ($new_credit > 0) {
                    $sql_update_client .= ", `paid_from_credit` = `paid_from_credit` + :new_credit, `payment_status` = 2";
                    $update_params[':new_credit'] = $new_credit;
                }
                $sql_update_client .= " WHERE `client_id` = :client_id";
                $pdo->prepare($sql_update_client)->execute($update_params);
            }
        }
    }
    // --- КОНЕЦ БЛОКА ИЗМЕНЕНИЯ СТОИМОСТИ ---

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

    // --- НАЧАЛО БЛОКА ОБРАБОТКИ ПЕРСОН ---
    $family_id = $old_client_data['family_id'];

    // 1. Создаем семью, если ее не было, а персоны появились
    if (!empty($personas) && !$family_id) {
        $stmt_family = $pdo->prepare("INSERT INTO `families` (`created_at`) VALUES (NOW())");
        $stmt_family->execute();
        $family_id = $pdo->lastInsertId();
        $stmt_update_client_family = $pdo->prepare("UPDATE `clients` SET `family_id` = :family_id WHERE `client_id` = :client_id");
        $stmt_update_client_family->execute([':family_id' => $family_id, ':client_id' => $client_id]);
    } 
    // 2. Удаляем семью, если она была, а все персоны удалены
    elseif (empty($personas) && $family_id) {
        // Сначала удаляем всех родственников и их доп. поля
        $stmt_get_relatives = $pdo->prepare("SELECT relative_id FROM `client_relatives` WHERE `family_id` = :family_id");
        $stmt_get_relatives->execute([':family_id' => $family_id]);
        $relatives_to_delete_all = $stmt_get_relatives->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($relatives_to_delete_all)) {
            $placeholders = implode(',', array_fill(0, count($relatives_to_delete_all), '?'));
            $pdo->prepare("DELETE FROM `client_input_values` WHERE `relative_id` IN ($placeholders)")->execute($relatives_to_delete_all);
            $pdo->prepare("DELETE FROM `client_relatives` WHERE `relative_id` IN ($placeholders)")->execute($relatives_to_delete_all);
        }

        // Затем отвязываем основную анкету и удаляем саму семью
        $stmt_update_client_family = $pdo->prepare("UPDATE `clients` SET `family_id` = NULL WHERE `client_id` = :client_id");
        $stmt_update_client_family->execute([':client_id' => $client_id]);
        
        $stmt_delete_family = $pdo->prepare("DELETE FROM `families` WHERE `family_id` = :family_id");
        $stmt_delete_family->execute([':family_id' => $family_id]);
        $family_id = null;
    }

    if ($family_id) {
        // 3. Получаем ID всех существующих родственников и тех, что пришли из формы
        $stmt_existing_relatives = $pdo->prepare("SELECT `relative_id` FROM `client_relatives` WHERE `family_id` = :family_id");
        $stmt_existing_relatives->execute([':family_id' => $family_id]);
        $existing_relative_ids = $stmt_existing_relatives->fetchAll(PDO::FETCH_COLUMN);

        $submitted_relative_ids = [];
        foreach ($personas as $p_data) {
            if (!empty($p_data['relative_id'])) {
                $submitted_relative_ids[] = (int)$p_data['relative_id'];
            }
        }
        
        // 4. Находим и удаляем тех, кого нет в форме
        $relatives_to_delete = array_diff($existing_relative_ids, $submitted_relative_ids);
        if (!empty($relatives_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($relatives_to_delete), '?'));
            $pdo->prepare("DELETE FROM `client_input_values` WHERE `relative_id` IN ($placeholders)")->execute($relatives_to_delete);
            $pdo->prepare("DELETE FROM `client_relatives` WHERE `relative_id` IN ($placeholders)")->execute($relatives_to_delete);
        }

        // 5. Обновляем и добавляем персон
        foreach ($personas as $persona_data) {
            $p_first_name = valid($persona_data['first_name'] ?? '');
            $p_last_name = valid($persona_data['last_name'] ?? '');
            
            if (empty($p_first_name) || empty($p_last_name)) continue; // Пропускаем пустые

            $p_relative_id = valid($persona_data['relative_id'] ?? null);
            $p_middle_name = valid($persona_data['middle_name'] ?? null);
            $p_gender = valid($persona_data['gender'] ?? 'male');
            $p_phone_code = valid($persona_data['phone_code'] ?? null);
            $p_phone_number = valid($persona_data['phone_number'] ?? null);
            $p_email = valid($persona_data['email'] ?? null);
            $p_passport_number = valid($persona_data['passport_number'] ?? '');
            $p_birth_date_raw = valid($persona_data['birth_date'] ?? null);
            $p_passport_expiry_raw = valid($persona_data['passport_expiry_date'] ?? null);
            $p_nationality = valid($persona_data['nationality'] ?? null);
            $p_additional_fields = $persona_data['additional_fields'] ?? [];
            
            $p_birth_date = !empty($p_birth_date_raw) ? DateTime::createFromFormat('d.m.Y', $p_birth_date_raw)->format('Y-m-d') : null;
            $p_passport_expiry_date = !empty($p_passport_expiry_raw) ? DateTime::createFromFormat('d.m.Y', $p_passport_expiry_raw)->format('Y-m-d') : null;

            $params = [
                ':first_name' => $p_first_name, ':last_name' => $p_last_name, ':middle_name' => !empty($p_middle_name) ? $p_middle_name : null,
                ':gender' => $p_gender, ':phone_code' => !empty($p_phone_code) ? preg_replace('/[^0-9]/', '', $p_phone_code) : null,
                ':phone_number' => !empty($p_phone_number) ? preg_replace('/[^0-9]/', '', $p_phone_number) : null,
                ':email' => !empty($p_email) ? $p_email : null, ':passport_number' => $p_passport_number, ':birth_date' => $p_birth_date,
                ':passport_expiry_date' => $p_passport_expiry_date, ':nationality' => !empty($p_nationality) ? $p_nationality : null
            ];

            if (!empty($p_relative_id)) { // Обновление
                $sql_relative = "UPDATE `client_relatives` SET `first_name`=:first_name, `last_name`=:last_name, `middle_name`=:middle_name, `gender`=:gender, `phone_code`=:phone_code, `phone_number`=:phone_number, `email`=:email, `passport_number`=:passport_number, `birth_date`=:birth_date, `passport_expiry_date`=:passport_expiry_date, `nationality`=:nationality WHERE `relative_id`=:relative_id";
                $params[':relative_id'] = $p_relative_id;
                $pdo->prepare($sql_relative)->execute($params);
                $current_relative_id = $p_relative_id;
            } else { // Добавление
                $sql_relative = "INSERT INTO `client_relatives` (family_id, first_name, last_name, middle_name, gender, phone_code, phone_number, email, passport_number, birth_date, passport_expiry_date, nationality) VALUES (:family_id, :first_name, :last_name, :middle_name, :gender, :phone_code, :phone_number, :email, :passport_number, :birth_date, :passport_expiry_date, :nationality)";
                $params[':family_id'] = $family_id;
                $pdo->prepare($sql_relative)->execute($params);
                $current_relative_id = $pdo->lastInsertId();
            }

            // Обновляем доп. поля для текущей персоны
            $pdo->prepare("DELETE FROM `client_input_values` WHERE `relative_id` = ?")->execute([$current_relative_id]);
            if (!empty($p_additional_fields)) {
                $stmt_relative_inputs = $pdo->prepare("INSERT INTO `client_input_values` (`relative_id`, `input_id`, `value`) VALUES (:relative_id, :input_id, :value)");
                foreach ($p_additional_fields as $input_id => $value) {
                     $value = valid($value);
                     if (!empty($value) && is_numeric($input_id)) {
                        $stmt_relative_inputs->execute([':relative_id' => $current_relative_id, ':input_id' => $input_id, ':value' => $value]);
                    }
                }
            }
        }
    }
    // --- КОНЕЦ БЛОКА ОБРАБОТКИ ПЕРСОН ---

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