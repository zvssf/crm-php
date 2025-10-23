<?php
// Отключаем лимит времени выполнения
set_time_limit(0);

// Валидация входных данных
$center_id = valid($_POST['center_id'] ?? '');
$status_id = valid($_POST['status_id'] ?? '');
$manager_id = valid($_POST['manager_id'] ?? '');
$agent_id = valid($_POST['agent_id'] ?? '');
$fields = $_POST['fields'] ?? [];

if (empty($center_id) || empty($status_id) || empty($fields)) {
    exit('Ошибка: Недостаточно данных для экспорта.');
}

// Словарь для перевода ключей полей в человекочитаемые названия
$field_labels = [
    'c.client_id' => 'ID', 'c.client_name' => 'ФИО', 'c.first_name' => 'Имя', 'c.last_name' => 'Фамилия', 'c.middle_name' => 'Отчество',
    'c.phone' => 'Телефон', 'c.email' => 'Email', 'c.gender' => 'Пол', 'c.passport_number' => 'Номер паспорта',
    'c.birth_date' => 'Дата рождения', 'c.passport_expiry_date' => 'Срок действия паспорта', 'c.nationality' => 'Национальность',
    'c.visit_date_start' => 'Дата визита (начало)', 'c.visit_date_end' => 'Дата визита (конец)', 'c.days_until_visit' => 'Дней до визита',
    'c.sale_price' => 'Стоимость', 'manager_name' => 'Менеджер', 'agent_name' => 'Агент', 'client_cities_list' => 'Города',
    'client_categories_list' => 'Категории', 'c.notes' => 'Пометки'
];

// Динамические поля
$select_fields = [];
$header_row = [];
$additional_input_ids = [];

foreach ($fields as $field_key) {
    if (strpos($field_key, 'input_') === 0) {
        $input_id = (int)str_replace('input_', '', $field_key);
        if ($input_id > 0) {
            $additional_input_ids[] = $input_id;
        }
    } else {
        $select_fields[] = $field_key;
        if (isset($field_labels[$field_key])) {
            $header_row[] = $field_labels[$field_key];
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
        foreach($additional_input_ids as $id) {
            $header_row[] = $input_names[$id] ?? 'Доп. поле #' . $id;
        }
    }

    // --- ФОРМИРОВАНИЕ ОСНОВНОГО ЗАПРОСА ---
    $sql_select = implode(', ', $select_fields);

    $sql = "
        SELECT 
            {$sql_select},
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

    // Фильтрация по ролям
    switch ($user_data['user_group']) {
        case 2: // Руководитель
            $where[] = "manager.user_supervisor = :user_id";
            $params[':user_id'] = $user_data['user_id'];
            break;
        case 3: // Менеджер
            $where[] = "(manager.user_id = :user_id OR c.agent_id = :user_id)";
            $params[':user_id'] = $user_data['user_id'];
            break;
        case 4: // Агент
            $where[] = "c.agent_id = :user_id";
            $params[':user_id'] = $user_data['user_id'];
            break;
    }

    // Фильтрация из модального окна
    if (!empty($manager_id)) {
        $where[] = "manager.user_id = :manager_id";
        $params[':manager_id'] = $manager_id;
    }
    if (!empty($agent_id)) {
        $where[] = "agent.user_id = :agent_id";
        $params[':agent_id'] = $agent_id;
    }

    $sql .= " WHERE " . implode(" AND ", $where) . " GROUP BY c.client_id ORDER BY c.client_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем значения доп. полей для найденных анкет
    if (!empty($additional_input_ids) && !empty($clients)) {
        $client_ids = array_column($clients, 'client_id');
        $placeholders_clients = implode(',', array_fill(0, count($client_ids), '?'));
        $placeholders_inputs = implode(',', array_fill(0, count($additional_input_ids), '?'));
        
        $stmt_values = $pdo->prepare("
            SELECT `client_id`, `input_id`, `value` 
            FROM `client_input_values` 
            WHERE `client_id` IN ($placeholders_clients) AND `input_id` IN ($placeholders_inputs)
        ");
        $stmt_values->execute(array_merge($client_ids, $additional_input_ids));
        $values_raw = $stmt_values->fetchAll(PDO::FETCH_ASSOC);

        // Группируем значения по client_id
        $client_input_values = [];
        foreach ($values_raw as $val) {
            $client_input_values[$val['client_id']][$val['input_id']] = $val['value'];
        }

        // Добавляем значения в основной массив
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

// --- ГЕНЕРАЦИЯ CSV ФАЙЛА ---
$filename = "export_clients_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Добавляем BOM для корректного отображения UTF-8 в Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Записываем заголовки
fputcsv($output, $header_row);

// Записываем данные
foreach ($clients as $client) {
    $row_data = [];
    foreach($fields as $field_key) {
         // Сопоставляем ключ поля с данными из запроса
        $db_key = str_replace('c.', '', $field_key);
        if (isset($client[$db_key])) {
            $row_data[] = $client[$db_key];
        } else {
            $row_data[] = ''; // Пустая ячейка, если ключ не найден
        }
    }
    fputcsv($output, $row_data);
}

fclose($output);
exit;

?>