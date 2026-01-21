<?php

// Подключаем ключевые файлы проекта, как в index.php
require_once dirname(__DIR__, 3) . '/system/config.php';
require_once SYSTEM . '/functions.php';

// Подключаем автозагрузчик Composer для доступа к сторонним библиотекам
if (file_exists(ROOT . '/vendor/autoload.php')) {
    require_once ROOT . '/vendor/autoload.php';
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Ошибка сервера: Composer autoloader не найден. Выполните `composer install`.']);
    exit;
}

header('Content-Type: application/json');

/**
 * Ищет подходящие анкеты в базе данных на основе данных из PDF.
 */
function find_matching_clients($pdf_passports, $potential_clients, $parser_type, $pdf_applicant_names, &$debug_log) {
    $found = [];
    if (empty($pdf_passports)) return $found;

    foreach ($potential_clients as $client) {
        $client_passport = $client['passport_number'];
        if (empty($client_passport)) continue;

        foreach ($pdf_passports as $pdf_passport) {
            $pdf_passport = trim($pdf_passport);
            $debug_log[] = "--> Сравнение: Паспорт из БД '{$client_passport}' VS Паспорт из PDF '{$pdf_passport}'.";
            $is_match = false;

            // 1. Прямое совпадение
            if (strcasecmp($client_passport, $pdf_passport) == 0) {
                $is_match = true;
            }
            // 2. Маска VFS (U3xxxxxx19)
            elseif (strpos($pdf_passport, 'x') !== false || strpos($pdf_passport, 'X') !== false) {
                if (strlen($client_passport) > 4 && strlen($pdf_passport) > 4) {
                    $start_db = substr($client_passport, 0, 2);
                    $end_db = substr($client_passport, -2);
                    $start_pdf = substr($pdf_passport, 0, 2);
                    $end_pdf = substr($pdf_passport, -2);
                    if (strcasecmp($start_db, $start_pdf) == 0 && strcasecmp($end_db, $end_pdf) == 0) {
                        $is_match = true;
                    }
                }
            }
            // 3. Маска BLS (*****969)
            elseif (strpos($pdf_passport, '*') !== false) {
                 if (strlen($client_passport) > 3 && strlen($pdf_passport) > 3) {
                    $end_db = substr($client_passport, -3);
                    $end_pdf = substr($pdf_passport, -3);
                    if (strcasecmp($end_db, $end_pdf) == 0) {
                        $is_match = true;
                    }
                }
            }

            if ($is_match) {
                $found[] = $client;
                break; 
            }
        }
    }
    
    // Фильтрация по имени, если найдено несколько
    if (count($found) > 1 && !empty($pdf_applicant_names)) {
        $name_filtered = [];
        foreach ($found as $client) {
            $client_first_name = trim(strtoupper($client['first_name']));
            $client_last_name = trim(strtoupper($client['last_name']));
            if (empty($client_first_name) || empty($client_last_name)) continue;

            foreach($pdf_applicant_names as $pdf_name_str) {
                $pdf_name_str_upper = trim(strtoupper($pdf_name_str));
                // Проверка прямого и обратного порядка имен
                $match_normal = (strpos($pdf_name_str_upper, $client_first_name) !== false && strpos($pdf_name_str_upper, $client_last_name) !== false);
                $match_swapped = (strpos($pdf_name_str_upper, $client_last_name) !== false && strpos($pdf_name_str_upper, $client_first_name) !== false);

                if ($match_normal || $match_swapped) {
                    $name_filtered[] = $client;
                    break;
                }
            }
        }
        if (!empty($name_filtered)) {
            return array_unique($name_filtered, SORT_REGULAR);
        }
    }

    return $found;
}

// --- ОБРАБОТКА ВЫБОРА ДУБЛИКАТА ---
if (isset($_POST['action']) && $_POST['action'] === 'resolve_duplicate') {
    $client_id = valid($_POST['client_id'] ?? '');
    $temp_filename = valid($_POST['temp_file'] ?? '');
    // Получаем и декодируем данные PDF
    $pdf_data_raw = $_POST['pdf_data'] ?? '{}';
    $pdf_data = json_decode($pdf_data_raw, true);

    if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Некорректный ID анкеты.']);
        exit;
    }
    
    if (empty($temp_filename) || !preg_match('/^temp_resolve_[a-z0-9]+\.pdf$/i', $temp_filename)) {
        echo json_encode(['status' => 'error', 'message' => 'Некорректное имя файла.']);
        exit;
    }

    // Данные из PDF
    $pdf_category_name = $pdf_data['category'] ?? null;
    $pdf_datetime_raw = $pdf_data['datetime'] ?? null; // Это может быть "2025-10-27 02:00 PM"

    $temp_path = ROOT . '/private_uploads/client_pdfs/' . $temp_filename;
    if (!file_exists($temp_path)) {
        echo json_encode(['status' => 'error', 'message' => 'Временный файл истек. Загрузите заново.']);
        exit;
    }

    try {
        $pdo = db_connect();
        $pdo->beginTransaction();

        // 1. Получаем данные выбранной анкеты
        $stmt_client = $pdo->prepare("SELECT `agent_id`, `sale_price`, `passport_number`, `client_status`, `pdf_file_path`, `center_id` FROM `clients` WHERE `client_id` = :id FOR UPDATE");
        $stmt_client->execute([':id' => $client_id]);
        $client_data = $stmt_client->fetch(PDO::FETCH_ASSOC);

        if (!$client_data) throw new Exception('Анкета не найдена.');
        if (!empty($client_data['pdf_file_path'])) throw new Exception('Файл уже прикреплен.');

        $center_id = $client_data['center_id'];

        // 2. Обработка даты и времени
        // PHP DateTime корректно обрабатывает 12-часовой формат (AM/PM) автоматически
        $appointment_datetime_db = null;
        if ($pdf_datetime_raw) {
            try {
                $dt = new DateTime($pdf_datetime_raw);
                $appointment_datetime_db = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                // Если дата кривая, можно упасть или оставить null (лучше упасть, это важно)
                throw new Exception('Неверный формат даты в данных PDF.');
            }
        } else {
             // Если даты нет в PDF, ставим текущую (fallback)
             $appointment_datetime_db = date('Y-m-d H:i:s');
        }

        // 3. Логика записи (Если анкета "В работе")
        if ($client_data['client_status'] == 1) {
            
            // 3.1 Поиск и обновление категории (City)
            // Ищем категорию по имени из PDF в этом ВЦ
            if ($pdf_category_name) {
                $stmt_city = $pdo->prepare("
                    SELECT city_id FROM `settings_cities` sc
                    JOIN `settings_centers` sce ON sc.country_id = sce.country_id
                    WHERE sce.center_id = :center_id AND sc.city_category = :category_name AND sc.city_status = 1
                ");
                $stmt_city->execute([':center_id' => $center_id, ':category_name' => $pdf_category_name]);
                $final_city_id = $stmt_city->fetchColumn();

                if (!$final_city_id) {
                    throw new Exception('Категория "' . valid($pdf_category_name) . '" из PDF не найдена в настройках ВЦ.');
                }

                // Обновляем привязку города
                $pdo->prepare("DELETE FROM `client_cities` WHERE `client_id` = :id")->execute([':id' => $client_id]);
                $pdo->prepare("INSERT INTO `client_cities` (`client_id`, `city_id`) VALUES (:cid, :fid)")->execute([':cid' => $client_id, ':fid' => $final_city_id]);
                
                // Обновляем себестоимость (списание у поставщика)
                $stmt_cc = $pdo->prepare("SELECT `cost_price` FROM `settings_cities` WHERE `city_id` = ?");
                $stmt_cc->execute([$final_city_id]);
                $cost = $stmt_cc->fetchColumn();
                if ($cost > 0) {
                    $stmt_sup = $pdo->prepare("SELECT `supplier_id` FROM `city_suppliers` WHERE `city_id` = ?");
                    $stmt_sup->execute([$final_city_id]);
                    $sups = $stmt_sup->fetchAll(PDO::FETCH_COLUMN);
                    if($sups) {
                        $upd_s = $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` - ? WHERE `id` = ?");
                        foreach($sups as $sid) $upd_s->execute([$cost, $sid]);
                    }
                }
            } else {
                 throw new Exception('В данных PDF отсутствует категория визы.');
            }

            // 3.2 Финансы (Списание с баланса агента)
            $agent_id = $client_data['agent_id'];
            $sale_price = (float)$client_data['sale_price'];
            $passport = $client_data['passport_number'];
            
            $payment_status = 0;
            $paid_from_balance = 0.00;
            
            if ($agent_id && $sale_price > 0) {
                $stmt_bal = $pdo->prepare("SELECT `user_balance` FROM `users` WHERE `user_id` = :id FOR UPDATE");
                $stmt_bal->execute([':id' => $agent_id]);
                $balance = (float) $stmt_bal->fetchColumn();
                
                if ($balance >= $sale_price) {
                    $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :p WHERE `user_id` = :id")->execute([':p' => $sale_price, ':id' => $agent_id]);
                    $payment_status = 1;
                    $paid_from_balance = $sale_price;
                }
            }
            
            // 3.3 Обновление статуса и даты записи
            $recording_uid = uniqid();
            $pdo->prepare("
                UPDATE `clients` SET 
                    client_status = 2,
                    recording_uid = :uid,
                    payment_status = :ps,
                    paid_from_balance = :pb,
                    paid_from_credit = 0.00,
                    appointment_datetime = :adt 
                WHERE client_id = :id
            ")->execute([
                ':uid' => $recording_uid,
                ':ps' => $payment_status,
                ':pb' => $paid_from_balance,
                ':adt' => $appointment_datetime_db, // Используем дату из PDF
                ':id' => $client_id
            ]);

            // 3.4 ОТМЕНА ДУБЛИКАТОВ (Статус 1 -> 7) и уведомления
            if (!empty($passport)) {
                // Ищем дубликаты для уведомлений
                $stmt_dups = $pdo->prepare("
                    SELECT client_id, agent_id, client_name 
                    FROM `clients` 
                    WHERE passport_number = :passport 
                      AND client_id != :current_id 
                      AND client_status = 1
                      AND center_id = :center_id
                ");
                $stmt_dups->execute([
                    ':passport' => $passport,
                    ':current_id' => $client_id,
                    ':center_id' => $center_id
                ]);
                $dups = $stmt_dups->fetchAll(PDO::FETCH_ASSOC);

                // Отменяем
                $pdo->prepare("
                    UPDATE `clients` 
                    SET client_status = 7 
                    WHERE passport_number = :passport 
                      AND client_id != :current_id 
                      AND client_status = 1
                      AND center_id = :center_id
                ")->execute([
                    ':passport' => $passport,
                    ':current_id' => $client_id,
                    ':center_id' => $center_id
                ]);

                // Уведомляем
                foreach ($dups as $dup) {
                    if ($dup['agent_id']) {
                        send_notification(
                            $pdo,
                            $dup['agent_id'],
                            'Заявка отменена (PDF)',
                            "Анкета '{$dup['client_name']}' (ID: {$dup['client_id']}) отменена, так как к дубликату был прикреплен PDF.",
                            'warning',
                            "/?page=clients&center={$center_id}&status=7"
                        );
                    }
                }
            }
        }

        // 4. Перемещаем файл
        $final_filename = $client_id . '_' . uniqid() . '.pdf';
        $final_path = ROOT . '/private_uploads/client_pdfs/' . $final_filename;
        
        if (!rename($temp_path, $final_path)) throw new Exception('Ошибка сохранения файла.');
        
        $pdo->prepare("UPDATE `clients` SET `pdf_file_path` = ? WHERE `client_id` = ?")->execute([$final_filename, $client_id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'client_id' => $client_id]);
        exit;

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

if (empty($_FILES['client_pdfs'])) {
    echo json_encode(['status' => 'error', 'message' => 'Файлы не были отправлены.']);
    exit;
}
$file = $_FILES['client_pdfs'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка загрузки файла.']);
    exit;
}
$tmp_path = $file['tmp_name'];

$parser_dir = ROOT . '/python_parser/';
$temp_uploads_dir = $parser_dir . 'temp_uploads/';
$new_temp_filename = uniqid('pdf_', true) . '.pdf';
$new_temp_path = $temp_uploads_dir . $new_temp_filename;

if (!move_uploaded_file($tmp_path, $new_temp_path)) {
    echo json_encode(['status' => 'error', 'message' => 'Не удалось переместить загруженный файл.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Неизвестная ошибка.'];
$debug_log = [];

try {
    $is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    $script_path = $is_windows ? $parser_dir . 'run_parser.bat' : $parser_dir . 'run_parser.sh';
    $universal_path = str_replace('\\', '/', $new_temp_path);
    $escaped_pdf_path = '"' . $universal_path . '"';
    $command = $script_path . ' ' . $escaped_pdf_path;

    exec($command, $output_array, $return_var);
    $raw_output = implode("\n", $output_array);
    
    if ($return_var !== 0) {
        error_log("Python Parser Error: " . $raw_output);
        throw new Exception('Ошибка при обработке файла Python-скриптом.');
    }

    $parsed_data = json_decode($raw_output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Не удалось декодировать ответ от Python-скрипта.');
    }

    $group_urn = $parsed_data['group_urn'] ?? null;
    $applicants_data = $parsed_data['applicants'] ?? [];
    $provider = $parsed_data['provider'] ?? null;

    if (!$group_urn || empty($applicants_data)) {
        throw new Exception('Недостаточно данных в PDF для обработки.');
    }

    $pdo = db_connect();
    
    // --- ШАГ 2: ОПРЕДЕЛЕНИЕ ВЦ ---
    $stmt_rule = $pdo->prepare("SELECT `center_id` FROM `pdf_parsing_rules` WHERE `center_identifier_text` = :urn AND `rule_status` = 1");
    $stmt_rule->execute([':urn' => $group_urn]);
    $center_id = $stmt_rule->fetchColumn();

    if (!$center_id) {
        throw new Exception('Визовый центр для URN "' . valid($group_urn) . '" не найден или не настроен.');
    }

    // --- ШАГ 3: ПОИСК АНКЕТ ---
    $main_applicant = $applicants_data[0];
    $pdf_passports = array_column($applicants_data, 'passport');
    $pdf_applicant_names = array_column($applicants_data, 'name');

    // Расширенный запрос для красивого отображения в таблице дублей
    $stmt_clients = $pdo->prepare("
        SELECT 
            c.*,
            agent.user_firstname as agent_firstname,
            agent.user_lastname as agent_lastname,
            manager.user_firstname as manager_firstname,
            manager.user_lastname as manager_lastname,
            GROUP_CONCAT(DISTINCT sc.city_name SEPARATOR ', ') as client_cities_list,
            GROUP_CONCAT(DISTINCT sc.city_category SEPARATOR ', ') as client_categories_list,
            GROUP_CONCAT(cc.city_id) as assigned_city_ids
        FROM `clients` c
        LEFT JOIN `users` agent ON c.agent_id = agent.user_id
        LEFT JOIN `users` manager ON agent.user_supervisor = manager.user_id
        LEFT JOIN `client_cities` cc ON c.client_id = cc.client_id
        LEFT JOIN `settings_cities` sc ON cc.city_id = sc.city_id
        WHERE c.center_id = :center_id AND c.client_status IN (1, 2)
        GROUP BY c.client_id
    ");
    $stmt_clients->execute([':center_id' => $center_id]);
    $potential_clients = $stmt_clients->fetchAll(PDO::FETCH_ASSOC);

    $found_clients = find_matching_clients($pdf_passports, $potential_clients, $provider, $pdf_applicant_names, $debug_log);

    if (count($found_clients) === 0) {
        throw new Exception('Подходящая анкета не найдена в базе данных.');
    }

    // Подготовка данных из PDF
    $pdf_category_name = $main_applicant['visa_category'] ?? null;
    $appointment_datetime_raw = $main_applicant['datetime_raw'] ?? null;
    if (!$pdf_category_name) throw new Exception('Категория визы не найдена в PDF.');
    if (empty($appointment_datetime_raw)) throw new Exception('Дата записи не найдена в PDF.');

    try {
        $dt = new DateTime($appointment_datetime_raw);
        $appointment_datetime_db = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        throw new Exception('Неверный формат даты в PDF.');
    }

    // Поиск группы дублей по паспорту среди потенциальных клиентов
    $duplicates_group = [];

    if (count($found_clients) === 1) {
        $main_client   = $found_clients[0];
        $main_passport = isset($main_client['passport_number']) ? $main_client['passport_number'] : '';

        if (!empty($main_passport)) {
            foreach ($potential_clients as $client) {
                if (!empty($client['passport_number']) && $client['passport_number'] === $main_passport) {
                    $duplicates_group[] = $client;
                }
            }

            if (count($duplicates_group) > 1) {
                $debug_log[] = "--> Найдена группа дублей по паспорту '{$main_passport}', количество: " . count($duplicates_group) . ".";
            }
        }
    }

    // === ЛОГИКА ДУБЛЕЙ ===
    if (count($found_clients) > 1 || (isset($duplicates_group) && count($duplicates_group) > 1)) {

        // Если нашли группу дублей по паспорту — используем её,
        // иначе используем результат find_matching_clients()
        $candidates = (isset($duplicates_group) && count($duplicates_group) > 1)
            ? $duplicates_group
            : $found_clients;

        // Перемещаем файл во временное хранилище для ручного разрешения
        $save_dir = ROOT . '/private_uploads/client_pdfs/';
        if (!is_dir($save_dir)) mkdir($save_dir, 0755, true);
        
        // Имя файла с префиксом temp_resolve_
        $temp_holding_name = 'temp_resolve_' . uniqid() . '.pdf';
        rename($new_temp_path, $save_dir . $temp_holding_name);

        echo json_encode([
            'status'   => 'duplicates',
            'message'  => 'Найдено несколько подходящих анкет.',
            'candidates' => $candidates,
            'temp_file'  => $temp_holding_name,
            'pdf_data'   => [
                'category' => $pdf_category_name,
                'datetime' => $appointment_datetime_db,
                'center_id' => $center_id
            ],
            'debug' => $debug_log
        ]);
        exit;
    }

    // === ЛОГИКА ОДИНОЧНОЙ АНКЕТЫ ===
    $client_data = $found_clients[0];
    $client_id = $client_data['client_id'];

    if (!empty($client_data['pdf_file_path'])) {
        throw new Exception('К анкете №' . $client_id . ' уже прикреплен PDF-файл.');
    }

    $pdo->beginTransaction();

    // Если анкета "В работе", проводим запись и оплату
    if ($client_data['client_status'] == 1) {
        
        // 1. Поиск категории
        $stmt_city = $pdo->prepare("
            SELECT city_id FROM `settings_cities` sc
            JOIN `settings_centers` sce ON sc.country_id = sce.country_id
            WHERE sce.center_id = :center_id AND sc.city_category = :category_name AND sc.city_status = 1
        ");
        $stmt_city->execute([':center_id' => $center_id, ':category_name' => $pdf_category_name]);
        $final_city_id = $stmt_city->fetchColumn();

        if (!$final_city_id) {
            throw new Exception('Категория "' . valid($pdf_category_name) . '" не найдена в настройках.');
        }

        $assigned_ids = explode(',', $client_data['assigned_city_ids']);
        if (!in_array($final_city_id, $assigned_ids)) {
             throw new Exception('Категория из PDF не совпадает с категориями анкеты.');
        }

        // 2. Обновление категорий
        $pdo->prepare("DELETE FROM `client_cities` WHERE `client_id` = :client_id")->execute([':client_id' => $client_id]);
        $pdo->prepare("INSERT INTO `client_cities` (`client_id`, `city_id`) VALUES (:client_id, :city_id)")->execute([':client_id' => $client_id, ':city_id' => $final_city_id]);

        // 3. Финансы
        $stmt_client_info = $pdo->prepare("SELECT `agent_id`, `sale_price` FROM `clients` WHERE `client_id` = :client_id FOR UPDATE");
        $stmt_client_info->execute([':client_id' => $client_id]);
        $client_info = $stmt_client_info->fetch(PDO::FETCH_ASSOC);

        $recording_uid = uniqid();
        $payment_status = 0;
        $paid_from_balance = 0.00;

        if ($client_info && !empty($client_info['agent_id']) && $client_info['sale_price'] > 0) {
            $agent_id = $client_info['agent_id'];
            $sale_price = (float) $client_info['sale_price'];
            $stmt_agent = $pdo->prepare("SELECT `user_balance` FROM `users` WHERE `user_id` = :agent_id FOR UPDATE");
            $stmt_agent->execute([':agent_id' => $agent_id]);
            $agent_balance = (float) $stmt_agent->fetchColumn();
            
            if ($agent_balance >= $sale_price) {
                $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :sale_price WHERE `user_id` = :agent_id")
                    ->execute([':sale_price' => $sale_price, ':agent_id' => $agent_id]);
                $payment_status = 1;
                $paid_from_balance = $sale_price;
            }
        }

        // 4. Обновление анкеты
        $stmt_update_client = $pdo->prepare("
            UPDATE `clients` SET 
                `client_status` = 2, 
                `recording_uid` = :recording_uid,
                `payment_status` = :payment_status,
                `paid_from_balance` = :paid_from_balance,
                `paid_from_credit` = 0.00,
                `appointment_datetime` = :appointment_datetime
            WHERE `client_id` = :client_id
        ");
        $stmt_update_client->execute([
            ':recording_uid' => $recording_uid,
            ':payment_status' => $payment_status,
            ':paid_from_balance' => $paid_from_balance,
            ':appointment_datetime' => $appointment_datetime_db,
            ':client_id' => $client_id
        ]);

        // 5. Списание себестоимости
        $stmt_city_cost = $pdo->prepare("SELECT `cost_price` FROM `settings_cities` WHERE `city_id` = :city_id");
        $stmt_city_cost->execute([':city_id' => $final_city_id]);
        $cost_price = $stmt_city_cost->fetchColumn();

        if ($cost_price > 0) {
            $stmt_suppliers = $pdo->prepare("SELECT `supplier_id` FROM `city_suppliers` WHERE `city_id` = :city_id");
            $stmt_suppliers->execute([':city_id' => $final_city_id]);
            $supplier_ids = $stmt_suppliers->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($supplier_ids)) {
                $upd_sup = $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` - :cost WHERE `id` = :sid");
                foreach ($supplier_ids as $sid) $upd_sup->execute([':cost' => $cost_price, ':sid' => $sid]);
            }
        }
    }

    // Сохранение файла
    $save_dir = ROOT . '/private_uploads/client_pdfs/';
    if (!is_dir($save_dir)) mkdir($save_dir, 0755, true);
    $final_filename = $client_id . '_' . uniqid() . '.pdf';
    $final_path = $save_dir . $final_filename;

    if (rename($new_temp_path, $final_path)) {
        $stmt_update_path = $pdo->prepare("UPDATE `clients` SET `pdf_file_path` = :pdf_path WHERE `client_id` = :client_id");
        $stmt_update_path->execute([':pdf_path' => $final_filename, ':client_id' => $client_id]);
    } else {
        throw new Exception('Не удалось сохранить файл на сервере.');
    }
    
    $pdo->commit();
    $response = [
        'status'    => 'success',
        'message'   => 'Файл успешно привязан.',
        'client_id' => $client_id,
        'debug'     => $debug_log
    ];

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("PDF Upload Exception: " . $e->getMessage());
    $response['message'] = $e->getMessage();
} finally {
    if (file_exists($new_temp_path)) unlink($new_temp_path);
}

echo json_encode($response);
exit;