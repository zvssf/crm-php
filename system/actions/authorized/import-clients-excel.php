<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

// --- ПРОВЕРКА ПРАВ ---

// Пока разрешаем импорт только директору.
// Если нужно — можно расширить список групп.
if (empty($user_data) || (int)$user_data['user_group'] !== 1) {
    message('Ошибка', 'У вас нет прав для импорта анкет из Excel.', 'error', '');
}

// --- ВАЛИДАЦИЯ ВХОДНЫХ ДАННЫХ ---

$center_id = valid($_POST['center_id'] ?? '');
if (empty($center_id) || !preg_match('/^[0-9]{1,11}$/u', $center_id)) {
    message('Ошибка', 'Некорректный визовый центр.', 'error', '');
}

// Агент по умолчанию (если не удастся определить из таблицы)
$default_agent_id = valid($_POST['default_agent_id'] ?? '');
if (!empty($default_agent_id) && !preg_match('/^[0-9]{1,11}$/u', $default_agent_id)) {
    message('Ошибка', 'Некорректный агент по умолчанию.', 'error', '');
}
if (empty($default_agent_id) && (int)$user_data['user_group'] === 4) {
    // Если импорт делает агент — используем его самого по умолчанию
    $default_agent_id = (string)$user_data['user_id'];
}

// Файл
if (empty($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    message('Ошибка', 'Файл Excel не был загружен.', 'error', '');
}

$tmp_file = $_FILES['import_file']['tmp_name'];
if (!is_uploaded_file($tmp_file)) {
    message('Ошибка', 'Некорректный файл загрузки.', 'error', '');
}

// --- НОРМАЛИЗАЦИЯ ИМЁН СТОЛБЦОВ ---

$normalize_header = function ($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    // приводим к нижнему регистру и убираем лишние пробелы
    return mb_strtolower(preg_replace('/\s+/u', ' ', $value));
};

// Маппинг из формы: map[ключ] = "Название столбца в Excel"
$map_raw = $_POST['map'] ?? [];
$mapping = []; // ключ (first_name/last_name/…) => нормализованное имя столбца

foreach ($map_raw as $key => $header) {
    $header = valid($header ?? '');
    $norm   = $normalize_header($header);
    if ($norm !== '') {
        $mapping[$key] = $norm;
    }
}

// Минимальный набор, чтобы вообще было что импортировать
if (
    empty($mapping['first_name']) &&
    empty($mapping['last_name']) &&
    empty($mapping['passport_number'])
) {
    message(
        'Ошибка',
        'Укажите хотя бы один столбец из ФИО или номера паспорта (Фамилия, Имя или Паспорт).',
        'error',
        ''
    );
}

try {
    // --- ЧТЕНИЕ EXCEL ---

    $spreadsheet = IOFactory::load($tmp_file);
    $sheet       = $spreadsheet->getActiveSheet();

    $highest_row      = $sheet->getHighestDataRow();
    $highest_column   = $sheet->getHighestDataColumn();
    $highest_col_idx  = Coordinate::columnIndexFromString($highest_column);

    // Карта: нормализованное имя заголовка -> индекс колонки (1-based)
    $header_index = [];

    for ($col = 1; $col <= $highest_col_idx; $col++) {
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $cell_value = $sheet->getCell($col_letter . '1')->getFormattedValue();
        $norm       = $normalize_header($cell_value);

        if ($norm !== '' && !isset($header_index[$norm])) {
            $header_index[$norm] = $col;
        }
    }

    // Проверяем, что все указанные пользователем заголовки реально есть в файле
    $missing = [];
    foreach ($mapping as $key => $header_norm) {
        if (!isset($header_index[$header_norm])) {
            $missing[] = $header_norm;
        }
    }

    if (!empty($missing)) {
        $missing_str = implode(', ', $missing);
        message('Ошибка', 'В Excel не найдены указанные столбцы: ' . $missing_str, 'error', '');
    }

    // --- ЧТЕНИЕ СТРОК В ПАМЯТЬ ---

    $rows = []; // каждая строка — ассоц.массив с ключами, как в $mapping (first_name, last_name, …)

    for ($row = 2; $row <= $highest_row; $row++) {
        $row_data = [];

        foreach ($mapping as $key => $header_norm) {
            $col_index = $header_index[$header_norm];
            $col_letter = Coordinate::stringFromColumnIndex($col_index);
            $value = $sheet->getCell($col_letter . $row)->getFormattedValue();
            $row_data[$key] = is_string($value) ? trim($value) : $value;
        }

        // Проверяем, пустая ли строка
        $is_empty = true;
        foreach (['first_name', 'last_name', 'passport_number', 'phone'] as $check_field) {
            if (!empty($row_data[$check_field] ?? null)) {
                $is_empty = false;
                break;
            }
        }

        if ($is_empty) {
            continue;
        }

        $rows[] = $row_data;
    }

    if (empty($rows)) {
        message('Ошибка', 'В файле не найдено строк для импорта.', 'error', '');
    }

    // --- ГРУППИРОВКА ПО СЕМЕЙНОМУ КОДУ ---

    $single_rows = [];  // без семейного кода
    $family_rows = [];  // ['код' => [row1, row2, ...]]

    foreach ($rows as $row_data) {
        $family_code = trim((string)($row_data['family_code'] ?? ''));
        if ($family_code === '') {
            $single_rows[] = $row_data;
        } else {
            if (!isset($family_rows[$family_code])) {
                $family_rows[$family_code] = [];
            }
            $family_rows[$family_code][] = $row_data;
        }
    }

    // --- ПОДКЛЮЧЕНИЕ К БД ---

    $pdo = db_connect();
    $pdo->beginTransaction();

    // --- ПОДГОТОВКА СПИСКА ГОРОДОВ ---
    // 1. Получаем ID страны текущего ВЦ
    $stmt_center_country = $pdo->prepare("SELECT `country_id` FROM `settings_centers` WHERE `center_id` = :center_id");
    $stmt_center_country->execute([':center_id' => $center_id]);
    $center_country_id = $stmt_center_country->fetchColumn();

    // 2. Загружаем все активные города и категории для этой страны
    // Создаем карту: $cities_map['название города']['название категории'] = city_id
    $cities_map = [];
    if ($center_country_id) {
        $stmt_cities = $pdo->prepare("SELECT `city_id`, `city_name`, `city_category` FROM `settings_cities` WHERE `country_id` = :country_id AND `city_status` = 1");
        $stmt_cities->execute([':country_id' => $center_country_id]);
        $db_cities = $stmt_cities->fetchAll(PDO::FETCH_ASSOC);

        foreach ($db_cities as $c) {
            $c_name = mb_strtolower(trim($c['city_name']));
            $c_cat  = mb_strtolower(trim($c['city_category'] ?? ''));
            $cities_map[$c_name][$c_cat] = $c['city_id'];
        }
    }

    // --- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ---

    // Парсинг даты в формат Y-m-d
    $parse_date = function ($value) {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel-число
        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($value);
                return $dt->format('Y-m-d');
            } catch (Exception $e) {
                return null;
            }
        }

        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $formats = ['d.m.Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'];
        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $value);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    };

    // Парсинг диапазона дат (Даты мониторинга)
    $parse_date_range = function ($value) use ($parse_date) {
        $value = trim((string)$value);
        if ($value === '') {
            return [null, null];
        }

        // возможные разделители: -, –, — (минус и разные тире)
        $parts = preg_split('/[-–—]+/u', $value);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, static function ($v) {
            return $v !== '';
        });

        $start = null;
        $end   = null;

        if (count($parts) === 1) {
            $start = $parse_date($parts[0]);
            $end   = $start;
        } elseif (count($parts) >= 2) {
            $start = $parse_date($parts[0]);
            $end   = $parse_date($parts[1]);
        }

        return [$start, $end];
    };

    // Поиск агента по строке из Excel
    // Поиск агента по строке из Excel
$find_agent_id = function ($agent_name) use ($pdo) {
    $agent_name = trim((string)$agent_name);
    if ($agent_name === '') {
        return null;
    }

    // Пытаемся найти по логину или ФИО (Имя Фамилия / Фамилия Имя)
    $sql = "
        SELECT `user_id`
        FROM `users`
        WHERE `user_group` = 4
          AND (
                `user_login` = :name_login
             OR CONCAT_WS(' ', `user_firstname`, `user_lastname`) = :name_fl
             OR CONCAT_WS(' ', `user_lastname`, `user_firstname`) = :name_lf
          )
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name_login' => $agent_name,
        ':name_fl'    => $agent_name,
        ':name_lf'    => $agent_name,
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ? (int)$user['user_id'] : null;
};

    // Нормализация пола
    $normalize_gender = function ($value) {
        $value = mb_strtolower(trim((string)$value));

        if ($value === '') {
            return null;
        }

        if (in_array($value, ['m', 'м', 'male', 'муж', 'мужской'], true)) {
            return 'male';
        }
        if (in_array($value, ['f', 'ж', 'female', 'жен', 'женский'], true)) {
            return 'female';
        }

        return null;
    };

    // Нормализация телефона: "код;номер" → [код, номер]
    $split_phone = function ($value) {
        $value = (string)$value;
        $value = trim($value);

        if ($value === '') {
            return [null, null];
        }

        $code  = null;
        $phone = null;

        // Если формат "код;номер"
        if (strpos($value, ';') !== false) {
            [$raw_code, $raw_phone] = explode(';', $value, 2);

            $raw_code  = trim($raw_code);
            $raw_phone = trim($raw_phone);

            $code  = preg_replace('/\D+/', '', $raw_code);
            $phone = preg_replace('/\D+/', '', $raw_phone);

            if ($code === '') {
                $code = null;
            }
            if ($phone === '') {
                $phone = null;
            }
        } else {
            // Фолбэк: как раньше — всё в номер
            $digits = preg_replace('/\D+/', '', $value);
            if ($digits === '') {
                return [null, null];
            }
            $code  = null;
            $phone = $digits;
        }

        return [$code, $phone];
    };

    // Подготовленные выражения для вставки
    $stmt_insert_family = $pdo->prepare("INSERT INTO `families` (`created_at`) VALUES (NOW())");

    $stmt_insert_client = $pdo->prepare("
        INSERT INTO `clients` (
            `family_id`, `center_id`, `agent_id`, `creator_id`, `client_name`, `client_status`,
            `first_name`, `last_name`, `middle_name`,
            `gender`, `phone_code`, `phone_number`, `email`, `passport_number`,
            `birth_date`, `passport_expiry_date`,
            `nationality`, `monitoring_date_start`, `monitoring_date_end`,
            `days_until_visit`,
            `notes`, `sale_price`
        ) VALUES (
            :family_id, :center_id, :agent_id, :creator_id, :client_name, :status,
            :first_name, :last_name, :middle_name,
            :gender, :phone_code, :phone_number, :email, :passport_number,
            :birth_date, :passport_expiry_date,
            :nationality, :monitoring_date_start, :monitoring_date_end,
            :days_until_visit,
            :notes, :sale_price
        )
    ");

    $stmt_insert_relative = $pdo->prepare("
        INSERT INTO `client_relatives` (
            `family_id`, `first_name`, `last_name`, `middle_name`, `gender`,
            `phone_code`, `phone_number`, `email`,
            `passport_number`, `birth_date`, `passport_expiry_date`, `nationality`
        ) VALUES (
            :family_id, :first_name, :last_name, :middle_name, :gender,
            :phone_code, :phone_number, :email,
            :passport_number, :birth_date, :passport_expiry_date, :nationality
        )
    ");

    $stmt_insert_client_city = $pdo->prepare("
        INSERT INTO `client_cities` (`client_id`, `city_id`) 
        VALUES (:client_id, :city_id)
    ");

    $created_clients   = 0;
    $created_relatives = 0;

    // --- ФУНКЦИИ СОЗДАНИЯ ЗАПИСЕЙ ---

    $create_client_from_row = function (array $row_data, $family_id) use (
        $center_id,
        $user_data,
        $default_agent_id,
        $find_agent_id,
        $parse_date,
        $parse_date_range,
        $normalize_gender,
        $split_phone,
        $stmt_insert_client,
        $stmt_insert_client_city,
        $cities_map,
        $pdo,
        &$created_clients
    ) {
        // Агент
        $agent_id = null;
        if (!empty($row_data['agent'])) {
            $agent_id = $find_agent_id($row_data['agent']);
        }
        if (empty($agent_id) && !empty($default_agent_id)) {
            $agent_id = (int)$default_agent_id;
        }

        // ФИО
        $first_name  = valid($row_data['first_name'] ?? '');
        $last_name   = valid($row_data['last_name'] ?? '');
        $middle_name = null; // В Excel его нет

        $client_name_parts = array_filter([$last_name, $first_name, $middle_name], static function ($v) {
            return $v !== null && $v !== '';
        });
        $client_name = implode(' ', $client_name_parts);
        if ($client_name === '') {
            $client_name = 'Черновик (импорт Excel)';
        }

        // Пол
        $gender = $normalize_gender($row_data['gender'] ?? null);

        // Телефон
        [$phone_code, $phone_number] = $split_phone($row_data['phone'] ?? null);

        // Паспорт и даты
        $passport_number      = valid($row_data['passport_number'] ?? '');
        $birth_date           = $parse_date($row_data['birth_date'] ?? null);
        $passport_expiry_date = $parse_date($row_data['passport_expiry_date'] ?? null);

        // Национальность
        $nationality = valid($row_data['nationality'] ?? '');

        // Даты мониторинга
        [$monitoring_start, $monitoring_end] = $parse_date_range($row_data['monitoring_dates'] ?? '');

        // Дни на дорогу
        $days_until_visit = null;
        if (isset($row_data['days_until_visit']) && $row_data['days_until_visit'] !== '') {
            $days_until_visit = (int)$row_data['days_until_visit'];
        }

        // Цена (продажа)
        $sale_price = null;
        if (isset($row_data['sale_price']) && $row_data['sale_price'] !== '') {
            $price_str  = str_replace(',', '.', (string)$row_data['sale_price']);
            $sale_price = (float)$price_str;
        }

        // Статус: черновик
        $status = 3;

        // Внимание: здесь ровно 22 параметра, совпадающих с SQL-запросом
        $stmt_insert_client->execute([
            ':family_id'             => $family_id ?: null,
            ':center_id'             => $center_id,
            ':agent_id'              => $agent_id,
            ':creator_id'            => $user_data['user_id'],
            ':client_name'           => $client_name,
            ':status'                => $status,
            ':first_name'            => $first_name !== '' ? $first_name : null,
            ':last_name'             => $last_name !== '' ? $last_name : null,
            ':middle_name'           => $middle_name,
            ':gender'                => $gender,
            ':phone_code'            => $phone_code,
            ':phone_number'          => $phone_number,
            ':email'                 => null,
            ':passport_number'       => $passport_number !== '' ? $passport_number : null,
            ':birth_date'            => $birth_date,
            ':passport_expiry_date'  => $passport_expiry_date,
            ':nationality'           => $nationality !== '' ? $nationality : null,
            ':monitoring_date_start' => $monitoring_start,
            ':monitoring_date_end'   => $monitoring_end,
            ':days_until_visit'      => $days_until_visit,
            ':notes'                 => null,
            ':sale_price'            => $sale_price,
        ]);

        $created_clients++;
        $new_client_id = (int)$pdo->lastInsertId();

        // --- ПРИВЯЗКА ГОРОДА И КАТЕГОРИИ ---
        $excel_city = mb_strtolower(trim((string)($row_data['city'] ?? '')));
        $excel_cat  = mb_strtolower(trim((string)($row_data['category'] ?? '')));

        if ($excel_city !== '' && isset($cities_map[$excel_city])) {
            $found_city_id = null;

            if (isset($cities_map[$excel_city][$excel_cat])) {
                $found_city_id = $cities_map[$excel_city][$excel_cat];
            } elseif ($excel_cat === '' && isset($cities_map[$excel_city][''])) {
                 $found_city_id = $cities_map[$excel_city][''];
            } elseif (!empty($cities_map[$excel_city])) {
                $found_city_id = reset($cities_map[$excel_city]);
            }

            if ($found_city_id) {
                $stmt_insert_client_city->execute([
                    ':client_id' => $new_client_id,
                    ':city_id'   => $found_city_id
                ]);
            }
        }

        return $new_client_id;
    };

    $create_relative_from_row = function (array $row_data, $family_id) use (
        $parse_date,
        $normalize_gender,
        $split_phone,
        $stmt_insert_relative,
        &$created_relatives
    ) {
        $first_name  = valid($row_data['first_name'] ?? '');
        $last_name   = valid($row_data['last_name'] ?? '');
        $middle_name = null;

        $gender = $normalize_gender($row_data['gender'] ?? null);

        [$phone_code, $phone_number] = $split_phone($row_data['phone'] ?? null);

        $passport_number      = valid($row_data['passport_number'] ?? '');
        $birth_date           = $parse_date($row_data['birth_date'] ?? null);
        $passport_expiry_date = $parse_date($row_data['passport_expiry_date'] ?? null);
        $nationality          = valid($row_data['nationality'] ?? '');

        $stmt_insert_relative->execute([
            ':family_id'            => $family_id,
            ':first_name'           => $first_name !== '' ? $first_name : null,
            ':last_name'            => $last_name !== '' ? $last_name : null,
            ':middle_name'          => $middle_name,
            ':gender'               => $gender,
            ':phone_code'           => $phone_code,
            ':phone_number'         => $phone_number,
            ':email'                => null,
            ':passport_number'      => $passport_number !== '' ? $passport_number : null,
            ':birth_date'           => $birth_date,
            ':passport_expiry_date' => $passport_expiry_date,
            ':nationality'          => $nationality !== '' ? $nationality : null,
        ]);

        $created_relatives++;
    };

    // --- ОДИНОЧНЫЕ АНКЕТЫ ---

    foreach ($single_rows as $row_data) {
        $create_client_from_row($row_data, null);
    }

    // --- СЕМЕЙНЫЕ АНКЕТЫ ---

    foreach ($family_rows as $family_code => $rows_in_family) {
        $rows_in_family = array_values($rows_in_family);

        if (count($rows_in_family) === 1) {
            // фактически одиночная анкета
            $create_client_from_row($rows_in_family[0], null);
            continue;
        }

        // создаём запись семьи
        $stmt_insert_family->execute();
        $family_id = (int)$pdo->lastInsertId();

        // первая строка — основная анкета
        $main_row = array_shift($rows_in_family);
        $create_client_from_row($main_row, $family_id);

        // остальные — персоны
        foreach ($rows_in_family as $persona_row) {
            $create_relative_from_row($persona_row, $family_id);
        }
    }

    $pdo->commit();

    $msg = 'Импорт завершён. Создано анкет: ' . $created_clients;
    if ($created_relatives > 0) {
        $msg .= ', дополнительных персон: ' . $created_relatives;
    }

    message('Уведомление', $msg, 'success', 'reload');

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Import clients from Excel error: ' . $e->getMessage());
    // ВРЕМЕННО ПОКАЗЫВАЕМ РЕАЛЬНУЮ ОШИБКУ
    message('Ошибка', 'Детали: ' . $e->getMessage(), 'error', '');
}