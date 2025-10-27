<?php
// Отключаем лимит времени выполнения и увеличиваем память
set_time_limit(0);
ini_set('memory_limit', '256M');

// Подключаем автозагрузчик Composer
require_once ROOT . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Валидация входных данных
$center_id = valid($_POST['center_id'] ?? '');
$status_id = valid($_POST['status_id'] ?? '');
$manager_id = valid($_POST['manager_id'] ?? '');
$agent_id = valid($_POST['agent_id'] ?? '');
$fields = $_POST['fields'] ?? [];
$field_order = $_POST['field_order'] ?? [];

// Вручную добавляем поля, которые всегда включены, но не отправляются формой, так как они disabled
$always_on_fields = ['c.client_id', 'c.first_name', 'c.last_name', 'c.passport_number'];
$fields = array_merge($always_on_fields, $fields);
$fields = array_unique($fields); // На случай, если что-то пойдет не так

if (empty($center_id) || empty($status_id) || empty($fields)) {
    exit('Ошибка: Недостаточно данных для экспорта.');
}


// Словарь для перевода ключей полей в человекочитаемые названия
$field_labels = [
    'c.client_id' => 'ID', 'c.first_name' => 'Имя', 'c.last_name' => 'Фамилия', 'c.middle_name' => 'Отчество',
    'phone_combined' => 'Телефон', 'c.email' => 'Email', 'c.gender' => 'Пол', 'c.passport_number' => 'Номер паспорта',
    'c.birth_date' => 'Дата рождения', 'c.passport_expiry_date' => 'Срок действия паспорта', 'c.nationality' => 'Национальность',
    'c.visit_date_start' => 'Дата визита (начало)', 'c.visit_date_end' => 'Дата визита (конец)', 'c.days_until_visit' => 'Дней до визита',
    'c.sale_price' => 'Стоимость', 'manager_name' => 'Менеджер', 'agent_name' => 'Агент', 'client_cities_list' => 'Города',
    'client_categories_list' => 'Категории', 'c.notes' => 'Пометки'
];

$additional_input_ids = [];
$input_names = [];
foreach ($fields as $field_key) {
    if (strpos($field_key, 'input_') === 0) {
        $input_id = (int)str_replace('input_', '', $field_key);
        if ($input_id > 0) {
            $additional_input_ids[] = $input_id;
        }
    }
}

try {
    $pdo = db_connect();

    // Получаем названия для доп. полей
    if (!empty($additional_input_ids)) {
        $placeholders = implode(',', array_fill(0, count($additional_input_ids), '?'));
        $stmt_inputs = $pdo->prepare("SELECT `input_id`, `input_name` FROM `settings_inputs` WHERE `input_id` IN ($placeholders)");
        $stmt_inputs->execute($additional_input_ids);
        $input_names = $stmt_inputs->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // --- ФОРМИРОВАНИЕ ОСНОВНОГО ЗАПРОСА ---
    // (Этот блок остается без изменений, так как он корректно получает данные)
    $sql = "
        SELECT 
            c.*,
            CONCAT(manager.user_firstname, ' ', manager.user_lastname) as manager_name,
            CONCAT(agent.user_firstname, ' ', agent.user_lastname) as agent_name,
            GROUP_CONCAT(DISTINCT sc.city_name SEPARATOR ', ') as client_cities_list,
            GROUP_CONCAT(DISTINCT sc.city_category SEPARATOR ', ') as client_categories_list
        FROM `clients` c
        LEFT JOIN `users` agent ON c.agent_id = agent.user_id
        LEFT JOIN `users` manager ON agent.user_supervisor = manager.user_id
        LEFT JOIN `client_cities` cc ON c.client_id = cc.client_id
        LEFT JOIN `settings_cities` sc ON cc.city_id = sc.city_id
    ";

    $params = [':center_id' => $center_id];
    $where = ["c.center_id = :center_id"];

    if ($status_id != 'all') {
        $where[] = "c.client_status = :status_id";
        $params[':status_id'] = $status_id;
    }

    switch ($user_data['user_group']) {
        case 2: $where[] = "manager.user_supervisor = :user_id"; $params[':user_id'] = $user_data['user_id']; break;
        case 3: $where[] = "(manager.user_id = :user_id OR c.agent_id = :user_id)"; $params[':user_id'] = $user_data['user_id']; break;
        case 4: $where[] = "c.agent_id = :user_id"; $params[':user_id'] = $user_data['user_id']; break;
    }

    if (!empty($manager_id)) { $where[] = "manager.user_id = :manager_id"; $params[':manager_id'] = $manager_id; }
    if (!empty($agent_id)) { $where[] = "agent.user_id = :agent_id"; $params[':agent_id'] = $agent_id; }

    $sql .= " WHERE " . implode(" AND ", $where) . " GROUP BY c.client_id ORDER BY c.client_id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем значения доп. полей
    if (!empty($additional_input_ids) && !empty($clients)) {
        $client_ids = array_column($clients, 'client_id');
        $placeholders_clients = implode(',', array_fill(0, count($client_ids), '?'));
        $placeholders_inputs = implode(',', array_fill(0, count($additional_input_ids), '?'));
        
        $stmt_values = $pdo->prepare("SELECT `client_id`, `input_id`, `value` FROM `client_input_values` WHERE `client_id` IN ($placeholders_clients) AND `input_id` IN ($placeholders_inputs)");
        $stmt_values->execute(array_merge($client_ids, $additional_input_ids));
        $values_raw = $stmt_values->fetchAll(PDO::FETCH_ASSOC);

        $client_input_values = [];
        foreach ($values_raw as $val) { $client_input_values[$val['client_id']][$val['input_id']] = $val['value']; }

        foreach($clients as &$client) {
            foreach($additional_input_ids as $id) {
                $client['input_' . $id] = $client_input_values[$client['client_id']][$id] ?? '';
            }
        }
        unset($client);
    }

} catch (PDOException $e) {
    error_log('DB Error export_clients_excel: ' . $e->getMessage());
    exit("Ошибка базы данных при экспорте.");
}

// --- ОБРАБОТКА И СОРТИРОВКА ПОЛЕЙ ДЛЯ ЭКСПОРТА ---
$all_possible_fields = [
    'c.client_id', 'c.last_name', 'c.first_name', 'c.middle_name', 'phone_combined', 'c.gender', 'c.email', 'c.passport_number', 
    'c.birth_date', 'c.passport_expiry_date', 'c.nationality', 'manager_name', 'agent_name', 'client_cities_list', 
    'client_categories_list', 'c.sale_price', 'c.visit_date_start', 'c.visit_date_end', 'c.days_until_visit', 'c.notes'
];
if (!empty($additional_input_ids)) {
    foreach ($additional_input_ids as $id) { $all_possible_fields[] = 'input_' . $id; }
}

$final_fields = [];
$numbered_fields = [];
$unnumbered_fields_keys = [];

foreach ($fields as $field_key) {
    $order = $field_order[$field_key] ?? '';
    if ($order !== '' && is_numeric($order)) {
        $order_num = (int)$order;
        while(isset($numbered_fields[$order_num])) { $order_num += 0.01; }
        $numbered_fields[$order_num] = $field_key;
    } else {
        $unnumbered_fields_keys[] = $field_key;
    }
}
ksort($numbered_fields);

foreach ($numbered_fields as $field_key) { $final_fields[] = $field_key; }
foreach ($all_possible_fields as $possible_key) {
    if (in_array($possible_key, $unnumbered_fields_keys)) { $final_fields[] = $possible_key; }
}

// --- НАЧАЛО БЛОКА СОХРАНЕНИЯ НАСТРОЕК (ФИНАЛЬНАЯ ВЕРСИЯ) ---
try {
    // Собираем для сохранения только те данные, что пришли из формы.
    $settings_to_save = [
        'fields'      => $_POST['fields'] ?? [],
        'field_order' => $field_order,
    ];

    $settings_json = json_encode($settings_to_save);

    if ($settings_json) {
        // $pdo уже определен в этом месте
        $sql_save = "
            INSERT INTO `user_export_settings` (`user_id`, `center_id`, `settings`)
            VALUES (:user_id, :center_id, :settings_insert)
            ON DUPLICATE KEY UPDATE `settings` = :settings_update
        ";
        $stmt_save = $pdo->prepare($sql_save);
        $stmt_save->execute([
            ':user_id'          => $user_data['user_id'],
            ':center_id'        => $center_id,
            ':settings_insert'  => $settings_json,
            ':settings_update'  => $settings_json
        ]);
    }
} catch (PDOException $e) {
    // Не прерываем скачивание файла, просто логируем ошибку для анализа
    error_log('DB Error on saving user export settings: ' . $e->getMessage());
}
// --- КОНЕЦ БЛОКА СОХРАНЕНИЯ НАСТРОЕК ---

// --- НОВАЯ ЛОГИКА ГЕНЕРАЦИИ XLSX ФАЙЛА ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$rowIndex = 1;

// Формируем и записываем заголовки
$colIndex = 1;
foreach ($final_fields as $field_key) {
    $label = '';
    if (isset($field_labels[$field_key])) {
        $label = $field_labels[$field_key];
    } elseif (strpos($field_key, 'input_') === 0) {
        $input_id = (int)str_replace('input_', '', $field_key);
        $label = $input_names[$input_id] ?? 'Доп. поле #' . $input_id;
    }
    
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex) . $rowIndex, $label);
    $colIndex++;
}

// Записываем данные
$rowIndex++;
foreach ($clients as $client) {
    $colIndex = 1;
    foreach ($final_fields as $field_key) {
        $db_key = str_replace('c.', '', $field_key);
        $value = $client[$db_key] ?? '';

        // --- Специальная обработка для отдельных полей ---
        $date_fields = ['birth_date', 'passport_expiry_date', 'visit_date_start', 'visit_date_end'];
        if (in_array($db_key, $date_fields) && !empty($value)) {
            try {
                $date = new DateTime($value);
                $value = $date->format('d.m.Y');
            } catch (Exception $e) {}
        }
        if ($field_key === 'phone_combined') {
            $value = (!empty($client['phone_code'])) ? '+' . $client['phone_code'] . ' ' . $client['phone_number'] : '';
        }
        if ($db_key === 'gender') {
            if ($value === 'male') $value = 'Мужской';
            if ($value === 'female') $value = 'Женский';
        }
        
        // Устанавливаем значение и формат ячейки
        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($colIndex) . $rowIndex);
        if ($field_key === 'c.passport_number') {
            // Принудительно устанавливаем текстовый формат для номера паспорта
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
        } else {
            $cell->setValue($value);
        }
        
        $colIndex++;
    }
    $rowIndex++;
}

// Устанавливаем ширину для всех колонок
$lastColumn = $sheet->getHighestColumn();
for ($col = 'A'; $col <= $lastColumn; $col++) {
    $sheet->getColumnDimension($col)->setWidth(18);
}

// Отправляем файл в браузер
$filename = "export_clients_" . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');



$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;