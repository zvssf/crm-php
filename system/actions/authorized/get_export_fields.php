<?php
header('Content-Type: text/html; charset=utf-8');

$country_id = valid($_POST['country_id'] ?? 0);

if (empty($country_id) || !is_numeric($country_id)) {
    exit;
}

$fields = [
    'c.client_id' => 'ID',
    'c.first_name' => 'Имя',
    'c.last_name' => 'Фамилия',
    'c.middle_name' => 'Отчество',
    'phone_combined' => 'Телефон', // Используем виртуальный ключ
    'c.email' => 'Email',
    'c.gender' => 'Пол',
    'c.passport_number' => 'Номер паспорта',
    'c.birth_date' => 'Дата рождения',
    'c.passport_expiry_date' => 'Срок действия паспорта',
    'c.nationality' => 'Национальность',
    'c.visit_date_start' => 'Дата визита (начало)',
    'c.visit_date_end' => 'Дата визита (конец)',
    'c.days_until_visit' => 'Дней до визита',
    'c.sale_price' => 'Стоимость',
    'manager_name' => 'Менеджер',
    'agent_name' => 'Агент',
    'client_cities_list' => 'Города',
    'client_categories_list' => 'Категории',
    'c.notes' => 'Пометки'
];

try {
    $pdo = db_connect();

    // Получаем динамические настройки видимости полей для страны
    $stmt_settings = $pdo->prepare("SELECT `field_name` FROM `settings_country_fields` WHERE `country_id` = :country_id AND `is_visible` = 1");
    $stmt_settings->execute([':country_id' => $country_id]);
    $visible_fields = array_column($stmt_settings->fetchAll(PDO::FETCH_ASSOC), 'field_name');

    // Получаем дополнительные поля, привязанные к городам этой страны
    $stmt_inputs = $pdo->prepare("
        SELECT DISTINCT si.input_id, si.input_name
        FROM `settings_inputs` si
        JOIN `settings_city_inputs` sci ON si.input_id = sci.input_id
        JOIN `settings_cities` sc ON sci.city_id = sc.city_id
        WHERE sc.country_id = :country_id AND si.input_status = 1
    ");
    $stmt_inputs->execute([':country_id' => $country_id]);
    $additional_inputs = $stmt_inputs->fetchAll(PDO::FETCH_KEY_PAIR);

    // Добавляем доп. поля в общий список
    foreach($additional_inputs as $id => $name) {
        $fields['input_' . $id] = $name;
    }

} catch (PDOException $e) {
    error_log('DB Error get_export_fields: ' . $e->getMessage());
    exit;
}

// Функция для генерации одного переключателя
function render_switch($key, $label, $is_checked, $is_disabled) {
    $disabled_attr = $is_disabled ? 'disabled' : '';
    $checked_attr = $is_checked ? 'checked' : '';
    $clean_key = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);

    echo '
    <div class="d-flex justify-content-between align-items-center mb-2 export-field-row ' . ($is_disabled ? 'p-2 bg-light rounded' : '') . '">
        <label class="form-label mb-0 ' . ($is_disabled ? 'fw-bold' : '') . '" for="field-' . $clean_key . '">' . $label . '</label>
        <div class="d-flex align-items-center">
            <div class="input-group input-group-sm me-2" style="width: 85px;">
                <span class="input-group-text">№</span>
                <input type="text" 
                       class="form-control export-order-input" 
                       name="field_order[' . $key . ']" 
                       placeholder="-"
                       oninput="this.value = this.value.replace(/[^0-9]/g, \'\')"
                       maxlength="2"
                       ' . ($is_checked ? '' : 'disabled') . '>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input export-toggle-switch" 
                       type="checkbox" 
                       id="field-' . $clean_key . '" 
                       name="fields[]" 
                       value="' . $key . '" 
                       data-target-order-input="field_order[' . $key . ']"
                       ' . $checked_attr . ' 
                       ' . $disabled_attr . '>
            </div>
        </div>
    </div>';
}

// Отображаем поля
echo '<div class="row">';

// Колонка 1
echo '<div class="col-xl-4">';
echo '<h5 class="mb-3 text-uppercase"><i class="mdi mdi-account-circle me-1"></i> Основная информация</h5>';
render_switch('c.client_id', 'ID', true, true);
render_switch('c.last_name', 'Фамилия', true, true);
render_switch('c.first_name', 'Имя', true, true);
if (in_array('middle_name', $visible_fields)) render_switch('c.middle_name', 'Отчество', true, false);
render_switch('phone_combined', 'Телефон', true, false);
if (in_array('gender', $visible_fields)) render_switch('c.gender', 'Пол', true, false);
if (in_array('email', $visible_fields)) render_switch('c.email', 'Email', true, false);
echo '</div>';

// Колонка 2
echo '<div class="col-xl-4">';
echo '<h5 class="mb-3 text-uppercase"><i class="mdi mdi-card-account-details-outline me-1"></i> Документы</h5>';
render_switch('c.passport_number', 'Номер паспорта', true, true);
if (in_array('birth_date', $visible_fields)) render_switch('c.birth_date', 'Дата рождения', true, false);
if (in_array('passport_expiry_date', $visible_fields)) render_switch('c.passport_expiry_date', 'Срок действия паспорта', true, false);
if (in_array('nationality', $visible_fields)) render_switch('c.nationality', 'Национальность', true, false);
echo '</div>';


// Колонка 3
echo '<div class="col-xl-4">';
echo '<h5 class="mb-3 text-uppercase"><i class="mdi mdi-information-outline me-1"></i> Информация</h5>';
render_switch('manager_name', 'Менеджер', true, false);
render_switch('agent_name', 'Агент', true, false);
render_switch('client_cities_list', 'Города', true, false);
render_switch('client_categories_list', 'Категории', true, false);
render_switch('c.sale_price', 'Стоимость', true, false);
if (in_array('visit_dates', $visible_fields)) {
    render_switch('c.visit_date_start', 'Дата визита (начало)', true, false);
    render_switch('c.visit_date_end', 'Дата визита (конец)', true, false);
}
if (in_array('days_until_visit', $visible_fields)) render_switch('c.days_until_visit', 'Дней до визита', true, false);
if (in_array('notes', $visible_fields)) render_switch('c.notes', 'Ваши пометки', true, false);

if (!empty($additional_inputs)) {
    echo '<h5 class="mt-4 mb-3 text-uppercase"><i class="mdi mdi-plus-box-outline me-1"></i> Дополнительные поля</h5>';
    foreach($additional_inputs as $id => $name) {
        render_switch('input_' . $id, $name, true, false);
    }
}
echo '</div>';

echo '</div>';
?>