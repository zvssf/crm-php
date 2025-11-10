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

/**
 * Вспомогательная функция для "умного" поиска значения рядом с меткой.
 * Она ищет метку и возвращает все, что идет после нее до конца строки.
 * @param string $text - Полный текст для поиска.
 * @param string $label - Метка, которую нужно найти (например, "Visa Category").
 * @return string|null - Найденное значение или null.
 */
function find_value_after_label($text, $label) {
    if (preg_match('/' . preg_quote($label, '/') . '[\s:]*([^\r\n]+)/i', $text, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

/**
 * Извлекает данные из PDF VFS, используя множественный поиск по паттернам.
 * @param string $full_text - Полный текст PDF.
 * @return array - Ассоциативный массив с данными: ['names' => [], 'passports' => [], 'categories' => []].
 */
function extract_tabular_data_vfs($full_text) {
    $result = [
        'names'      => [],
        'passports'  => [],
        'categories' => [],
    ];

    // 1. Извлекаем все номера паспортов (наш самый надежный якорь)
    // Паттерн ищет: (Буква)(Буква или Цифра)(6 любых символов, кроме пробела)(2 Цифры)
    // Это найдет "U3xxxxxx19" даже если 'x' - это другой символ.
    preg_match_all('/[A-Z][A-Z0-9]\S{6}[0-9]{2}/', $full_text, $passport_matches);
    if (!empty($passport_matches[0])) {
        $result['passports'] = array_unique($passport_matches[0]);
    }

    // 2. Извлекаем все имена (два слова, написанные заглавными буквами, идущие подряд)
    preg_match_all('/([A-Z]{2,})\s+([A-Z]{2,})/', $full_text, $name_matches, PREG_SET_ORDER);
    if (!empty($name_matches)) {
        foreach ($name_matches as $match) {
            // Отсеиваем ложные срабатывания вроде "VISA CATEGORY", "APPOINTMENT DETAILS"
            if (stripos($match[0], 'VISA') === false && stripos($match[0], 'APPOINTMENT') === false && stripos($match[0], 'SHORT TERM') === false) {
                 $result['names'][] = $match[0];
            }
        }
    }
    
    // 3. Извлекаем все категории (ищем "Short Term" или "Long Term" и еще одно слово)
    preg_match_all('/(Short\s+Term\s+\w+|Long\s+Term\s+\w+)/i', $full_text, $category_matches);
    if (!empty($category_matches[0])) {
         // Убираем лишние пробелы и оставляем только уникальные значения
        $cleaned_categories = array_map(function($item) {
            return preg_replace('/\s+/', ' ', $item);
        }, $category_matches[0]);
        $result['categories'] = array_unique($cleaned_categories);
    }

    return $result;
}

header('Content-Type: application/json');

// --- НАЧАЛО БЛОКА БАЗОВОЙ ВАЛИДАЦИИ ЗАГРУЗКИ ---
if (empty($_FILES['client_pdfs'])) {
    echo json_encode(['status' => 'error', 'message' => 'Файлы не были отправлены.']);
    exit;
}
$file = $_FILES['client_pdfs'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'Размер файла превысил лимит.', UPLOAD_ERR_FORM_SIZE  => 'Размер файла превысил лимит формы.',
        UPLOAD_ERR_PARTIAL    => 'Файл был получен только частично.', UPLOAD_ERR_NO_FILE    => 'Файл не был загружен.',
        UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка.', UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
        UPLOAD_ERR_EXTENSION  => 'PHP-расширение остановило загрузку файла.',
    ];
    $message = $upload_errors[$file['error']] ?? 'Неизвестная ошибка загрузки.';
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}
if ($file['type'] !== 'application/pdf') {
    echo json_encode(['status' => 'error', 'message' => 'Файл не является PDF.']);
    exit;
}
$tmp_path = $file['tmp_name'];
// --- КОНЕЦ БЛОКА БАЗОВОЙ ВАЛИДАЦИИ ЗАГРУЗКИ ---

$debug_log = []; 
$response = ['status' => 'error', 'message' => 'Неизвестная ошибка в процессе обработки.'];

try {
    $pdo = db_connect();
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($tmp_path);
    $text = $pdf->getText();
    $debug_log[] = "PDF-парсер: Текст успешно извлечен (" . mb_strlen($text) . " символов).";
    
    $clean_text = preg_replace('/\s+/', '', $text);

    // --- ШАГ 1: ОПРЕДЕЛЕНИЕ ТИПА ПАРСЕРА И ИЗВЛЕЧЕНИЕ ДАННЫХ ---
    $parser_type = null;
    $pdf_category = null;
    $pdf_applicant_name = null;
    $all_passports_in_text = [];
    $pdf_applicant_names = []; // Инициализируем массив для имен
    $pdf_categories = []; // Инициализируем массив для категорий

    if (preg_match('/V\s*F\s*S/i', $text)) {
        $parser_type = 'VFS';
        $debug_log[] = "Тип документа: Определен как VFS.";
        
        // Используем новую табличную функцию
        $vfs_data = extract_tabular_data_vfs($text);
        
        $pdf_applicant_names = $vfs_data['names'];
        $all_passports_in_text = $vfs_data['passports'];
        $pdf_categories = $vfs_data['categories'];

        // Для дальнейшей логики берем данные первого аппликанта как основные
        $pdf_category = $pdf_categories[0] ?? null;
        $pdf_applicant_name = $pdf_applicant_names[0] ?? null; // Оставляем для обратной совместимости логов

    } elseif (preg_match('/B\s*L\s*S/i', $text)) {
        $parser_type = 'BLS';
        $debug_log[] = "Тип документа: Определен как BLS.";

        $pdf_category = find_value_after_label($text, 'Visa Type');
        
        preg_match('/([A-Z\*]+\s+[A-Z\*]+)\s+IST\d+/i', $text, $name_matches);
        $pdf_applicant_name = !empty($name_matches[1]) ? trim($name_matches[1]) : null;

        preg_match_all('/[x\*]+[A-Z0-9]{3}/i', $text, $passport_matches);
        if (!empty($passport_matches[0])) {
            $all_passports_in_text = array_unique($passport_matches[0]);
        }
        
    } else {
        $response['message'] = 'Не удалось определить тип документа (поддерживается VFS, BLS).';
        throw new Exception($response['message']);
    }

    $debug_log[] = "Извлечение категории: Найдена строка '" . ($pdf_category ? valid($pdf_category) : 'пусто') . "'.";
    $debug_log[] = "Извлечение имени: Найдена строка '" . ($pdf_applicant_name ? valid($pdf_applicant_name) : 'пусто') . "'.";
    $debug_log[] = "Извлеченные паспорта из PDF: " . (!empty($all_passports_in_text) ? implode(', ', $all_passports_in_text) : 'не найдены');
    
    // --- ШАГ 2: ОПРЕДЕЛЕНИЕ ВИЗОВОГО ЦЕНТРА ---
    $center_id = null;
    $stmt_rules = $pdo->query("SELECT `center_id`, `center_identifier_text` FROM `pdf_parsing_rules` WHERE `rule_status` = 1");
    $rules = $stmt_rules->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rules as $rule) { if (stripos($text, $rule['center_identifier_text']) !== false) { $center_id = $rule['center_id']; $debug_log[] = "Правило ВЦ: Найдено совпадение для ID " . $center_id . " по фразе '" . valid($rule['center_identifier_text']) . "'."; break; } }
    if (!$center_id) { $response['message'] = 'Не удалось определить ВЦ по правилам.'; throw new Exception($response['message']); }

    // --- ШАГ 3: ПОИСК АНКЕТЫ ---
    $stmt_passports = $pdo->prepare("SELECT `client_id`, `first_name`, `last_name`, `passport_number`, `client_status`, `family_id`, `pdf_file_path` FROM `clients` WHERE `center_id` = :center_id AND `client_status` IN (1, 2)");
    $stmt_passports->execute([':center_id' => $center_id]);
    $potential_clients = $stmt_passports->fetchAll(PDO::FETCH_ASSOC);
    $debug_log[] = "Поиск кандидатов: Найдено " . count($potential_clients) . " анкет (статусы 1 и 2) для ВЦ ID " . $center_id . ".";
    
    $found_clients = find_matching_clients($all_passports_in_text, $potential_clients, $parser_type, $pdf_applicant_names ?? [$pdf_applicant_name], $debug_log);

    $debug_log[] = "Результат поиска: Найдено " . count($found_clients) . " подходящих анкет.";
    
    if (count($found_clients) > 1) {
        $response['message'] = 'Найдено несколько анкет-дубликатов.';
        throw new Exception($response['message']);
    }
    if (count($found_clients) === 0) {
        $response['message'] = 'Подходящая анкета не найдена.';
        throw new Exception($response['message']);
    }
    
    $client_data = $found_clients[0];
    
    if (!empty($client_data['pdf_file_path'])) {
        $response['message'] = 'Анкета №' . $client_data['client_id'] . ' найдена, но у нее уже есть прикрепленный PDF-файл.';
        throw new Exception($response['message']);
    }
    
    $client_id = $client_data['client_id'];
    $client_status = $client_data['client_status'];
    
    // --- ШАГ 4: ОБНОВЛЕНИЕ АНКЕТЫ И ПРИВЯЗКА ФАЙЛА ---
    $pdo->beginTransaction();

    if ($client_status == 1) {
        $debug_log[] = "Статус анкеты: 'В работе'. Запуск полной финансовой логики...";
        
        // Новая логика определения категории
        // 1. Получаем страну ВЦ
        $stmt_country = $pdo->prepare("SELECT `country_id` FROM `settings_centers` WHERE `center_id` = :center_id");
        $stmt_country->execute([':center_id' => $center_id]);
        $country_id = $stmt_country->fetchColumn();

        $final_city = null;
        if ($country_id) {
            // 2. Получаем все возможные категории для этой страны
            $stmt_categories = $pdo->prepare("SELECT `city_id`, `city_category`, `cost_price` FROM `settings_cities` WHERE `country_id` = :country_id AND `city_status` = 1 AND `city_category` IS NOT NULL AND `city_category` != ''");
            $stmt_categories->execute([':country_id' => $country_id]);
            $possible_categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
            $debug_log[] = "Поиск категории: Найдено " . count($possible_categories) . " возможных категорий в настройках для страны ID " . $country_id . ".";
            
            // 3. Ищем первое совпадение одной из категорий в тексте PDF
            foreach ($possible_categories as $cat) {
                if (stripos($text, $cat['city_category']) !== false) {
                    $final_city = $cat; // Нашли!
                    $debug_log[] = "Конвертация категории: В тексте PDF найдено совпадение с категорией '" . valid($cat['city_category']) . "'. ID города: " . $cat['city_id'] . ".";
                    break;
                }
            }
        }

        if (!$final_city) {
            $response['message'] = 'Не удалось найти в PDF-файле ни одной из доступных категорий виз для этой страны.';
            throw new Exception($response['message']);
        }
        $final_city_id = $final_city['city_id'];
        
        $pdo->prepare("DELETE FROM `client_cities` WHERE `client_id` = ?")->execute([$client_id]);
        $pdo->prepare("INSERT INTO `client_cities` (`client_id`, `city_id`) VALUES (?, ?)")->execute([$client_id, $final_city_id]);
        $debug_log[] = "Обновление категорий: Старые удалены, новая (city_id=" . $final_city_id . ") установлена.";

        $stmt_client_full = $pdo->prepare("SELECT `agent_id`, `sale_price`, `passport_number` FROM `clients` WHERE `client_id` = :client_id FOR UPDATE");
        $stmt_client_full->execute([':client_id' => $client_id]);
        $client_info = $stmt_client_full->fetch(PDO::FETCH_ASSOC);

        $recording_uid = uniqid();
        $payment_status = 0;
        $paid_from_balance = 0.00;
        
        if ($client_info && !empty($client_info['agent_id']) && !empty($client_info['sale_price']) && $client_info['sale_price'] > 0) {
            $agent_id = $client_info['agent_id'];
            $sale_price = (float) $client_info['sale_price'];
            $stmt_agent = $pdo->prepare("SELECT `user_balance` FROM `users` WHERE `user_id` = :agent_id FOR UPDATE");
            $stmt_agent->execute([':agent_id' => $agent_id]);
            $agent_balance = (float) $stmt_agent->fetchColumn();
            if ($agent_balance >= $sale_price) {
                $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - :sale_price WHERE `user_id` = :agent_id")->execute([':sale_price' => $sale_price, ':agent_id' => $agent_id]);
                $payment_status = 1;
                $paid_from_balance = $sale_price;
            }
        }
        $debug_log[] = "Проверка оплаты: Статус оплаты установлен в " . $payment_status . ". Списано с баланса: " . $paid_from_balance . ".";

        $stmt_update_client = $pdo->prepare("UPDATE `clients` SET `client_status` = 2, `recording_uid` = :recording_uid, `payment_status` = :payment_status, `paid_from_balance` = :paid_from_balance, `paid_from_credit` = 0.00 WHERE `client_id` = :client_id AND `client_status` = 1");
        $stmt_update_client->execute([':recording_uid' => $recording_uid, ':payment_status' => $payment_status, ':paid_from_balance' => $paid_from_balance, ':client_id' => $client_id]);
        $debug_log[] = "Обновление анкеты: Статус изменен на 'Записанные'.";

        $cost_price = $final_city['cost_price'];
        if ($cost_price > 0) {
            $stmt_suppliers = $pdo->prepare("SELECT `supplier_id` FROM `city_suppliers` WHERE `city_id` = :city_id");
            $stmt_suppliers->execute([':city_id' => $final_city_id]);
            $supplier_ids = $stmt_suppliers->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($supplier_ids)) {
                $stmt_update_supplier = $pdo->prepare("UPDATE `fin_suppliers` SET `balance` = `balance` - :cost WHERE `id` = :supplier_id");
                foreach ($supplier_ids as $supplier_id) {
                    $stmt_update_supplier->execute([':cost' => $cost_price, ':supplier_id' => $supplier_id]);
                }
            }
        }
        $debug_log[] = "Списание себестоимости: Списано " . ($cost_price ?? 0) . " с " . (count($supplier_ids ?? [])) . " поставщиков.";

        if ($client_info && !empty($client_info['passport_number'])) {
            $stmt_cancel_duplicates = $pdo->prepare("UPDATE `clients` SET `client_status` = 7 WHERE `passport_number` = :passport_number AND `client_id` != :client_id AND `client_status` = 1");
            $stmt_cancel_duplicates->execute([':passport_number' => $client_info['passport_number'], ':client_id' => $client_id]);
            $debug_log[] = "Отмена дубликатов: Запущен процесс отмены для паспорта '" . valid($client_info['passport_number']) . "'.";
        }
    }

    if (!defined('ROOT')) { define('ROOT', dirname(dirname(__DIR__))); }
    $new_filename = $client_id . '_' . uniqid() . '.pdf';
    $save_dir = ROOT . '/private_uploads/client_pdfs/';
    if (!is_dir($save_dir)) { mkdir($save_dir, 0755, true); }
    $save_path = $save_dir . $new_filename;

    if (move_uploaded_file($tmp_path, $save_path)) {
        $stmt_update_path = $pdo->prepare("UPDATE `clients` SET `pdf_file_path` = :pdf_path WHERE `client_id` = :client_id");
        $stmt_update_path->execute([':pdf_path' => $new_filename, ':client_id' => $client_id]);
        $debug_log[] = "Сохранение файла: Файл '" . valid($new_filename) . "' успешно сохранен и привязан к анкете.";
    } else {
        $response['message'] = 'Не удалось сохранить файл на сервере.';
        throw new Exception($response['message']);
    }
    
    $pdo->commit();
    
    $response = [ 'status' => 'success', 'message' => 'Файл успешно привязан.', 'client_id' => $client_id ];

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error_log("PDF Upload Exception: " . $e->getMessage());
}

function find_matching_clients($pdf_passports, $potential_clients, $parser_type, $pdf_applicant_names, &$debug_log) {
    $found = [];
    if (empty($pdf_passports)) return $found;

    foreach ($potential_clients as $client) {
        $client_passport = $client['passport_number'];
        if (empty($client_passport)) continue;

        foreach ($pdf_passports as $pdf_passport) {
            $is_match = false;
            // Прямое совпадение
            if (strcasecmp($client_passport, $pdf_passport) == 0) {
                $is_match = true;
            } 
            // Проверка по маске
            else {
                if ($parser_type === 'VFS' && strlen($client_passport) > 4) {
                    $start = substr($client_passport, 0, 2);
                    $end = substr($client_passport, -2);
                    if (preg_match('/^' . preg_quote($start, '/') . '[x\*]+' . preg_quote($end, '/') . '$/i', $pdf_passport)) {
                        $is_match = true;
                    }
                } elseif ($parser_type === 'BLS' && strlen($client_passport) > 3) {
                    $end = substr($client_passport, -3);
                    if (preg_match('/[x\*]+' . preg_quote($end, '/') . '$/i', $pdf_passport)) {
                        $is_match = true;
                    }
                }
            }

            if ($is_match) {
                $found[] = $client;
                $debug_log[] = "Совпадение: Найден кандидат (Анкета ID: " . $client['client_id'] . ") по паспорту '" . valid($pdf_passport) . "'.";
                break; 
            }
        }
    }
    
    // Новая логика уточнения по имени
    if (count($found) > 1 && !empty($pdf_applicant_names)) {
        $debug_log[] = "Уточнение: Найдено несколько кандидатов (" . count($found) . "). Запуск проверки по имени.";
        $name_filtered = [];
        foreach ($found as $client) {
            $client_first_name = trim(strtoupper($client['first_name']));
            $client_last_name = trim(strtoupper($client['last_name']));
            if (empty($client_first_name) || empty($client_last_name)) continue;

            foreach($pdf_applicant_names as $pdf_name_str) {
                $pdf_name_str_upper = trim(strtoupper($pdf_name_str));

                // Проверяем прямое и обратное вхождение имени и фамилии
                $match_normal = (strpos($pdf_name_str_upper, $client_first_name) !== false && strpos($pdf_name_str_upper, $client_last_name) !== false);
                $match_swapped = (strpos($pdf_name_str_upper, $client_last_name) !== false && strpos($pdf_name_str_upper, $client_first_name) !== false);

                if ($match_normal || $match_swapped) {
                    $name_filtered[] = $client;
                    $debug_log[] = "Уточнение: Анкета ID " . $client['client_id'] . " ПОДТВЕРЖДЕНА по имени '" . valid($pdf_name_str) . "'.";
                    break; // Переходим к следующему клиенту из БД, т.к. этот уже подтвержден
                }
            }
        }
        return array_unique($name_filtered, SORT_REGULAR); // Убираем дубликаты, если несколько имен подошли к одной анкете
    }

    return $found;
}

$response['message'] .= "<br><br><hr style='border-top: 1px solid #555;'><b style='color: #727cf5;'>Отладочная информация:</b><br><div style='font-size: 11px; font-family: monospace;'>" . implode("<br>", $debug_log) . "</div>";
echo json_encode($response);

exit;