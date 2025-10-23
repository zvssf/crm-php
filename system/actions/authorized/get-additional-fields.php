<?php
header('Content-Type: text/html; charset=utf-8');

$is_readonly = isset($_POST['is_readonly']) && $_POST['is_readonly'] === 'true';
$disabled_attr = $is_readonly ? 'disabled' : '';

$city_ids = $_POST['city_ids'] ?? [];
$client_id = valid($_POST['client_id'] ?? null);

if (empty($city_ids) || !is_array($city_ids)) {
    exit;
}

$params = [];
foreach ($city_ids as $id) {
    if (is_numeric($id)) {
        $params[] = (int)$id;
    }
}

if (empty($params)) {
    exit;
}
$placeholders = implode(',', array_fill(0, count($params), '?'));

try {
    $pdo = db_connect();
    
    $sql_inputs = "
        SELECT DISTINCT si.input_id, si.input_name, si.input_type, si.input_select_data
        FROM `settings_inputs` si
        JOIN `settings_city_inputs` sci ON si.input_id = sci.input_id
        WHERE si.input_status = 1 AND sci.city_id IN ($placeholders)
        ORDER BY si.input_id ASC
    ";
    $stmt_inputs = $pdo->prepare($sql_inputs);
    $stmt_inputs->execute($params);
    $inputs = $stmt_inputs->fetchAll(PDO::FETCH_ASSOC);

    $saved_values = [];
    if (!empty($client_id)) {
        $stmt_values = $pdo->prepare("SELECT `input_id`, `value` FROM `client_input_values` WHERE `client_id` = ?");
        $stmt_values->execute([$client_id]);
        $saved_values = $stmt_values->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    if ($inputs) {
        echo '<hr><h5 class="mb-3 text-uppercase"><i class="mdi mdi-plus-box-outline me-1"></i> Дополнительные поля</h5>';
        
        foreach ($inputs as $input) {
            $input_id = $input['input_id'];
            $value = $saved_values[$input_id] ?? '';
            $required_attr = ''; // Поля пока не обязательные
            $required_label = ''; // Поля пока не обязательные
            $field_name = 'additional_fields['.$input_id.']';
            $field_id = 'additional_field_'.$input_id;

            echo '<div class="mb-3">';
            echo '<label for="'.$field_id.'" class="form-label">'.valid($input['input_name']).$required_label.'</label>';

            switch ($input['input_type']) {
                case 2: // Выпадающий список
                    echo '<select class="form-select" id="'.$field_id.'" name="'.$field_name.'" '.$required_attr.' '.$disabled_attr.'>';
                    echo '<option value="">Выберите...</option>';
                    $options = explode('|', $input['input_select_data']);
                    foreach ($options as $option) {
                        $option = trim($option);
                        $selected = ($option == $value) ? 'selected' : '';
                        echo '<option value="'.valid($option).'" '.$selected.'>'.valid($option).'</option>';
                    }
                    echo '</select>';
                    break;

                case 3: // Выбор значения
                    echo '<div>';
                    $options = explode('|', $input['input_select_data']);
                    foreach ($options as $index => $option) {
                        $option = trim($option);
                        if (empty($option)) continue;
                        $option_id = $field_id . '_' . $index;
                        $checked = ($option == $value) ? 'checked' : '';
                        
                        echo '<div class="form-check form-check-inline">';
                        echo '<input class="form-check-input" type="radio" name="'.$field_name.'" id="'.$option_id.'" value="'.valid($option).'" '.$checked.' '.$required_attr.' '.$disabled_attr.'>';
                        echo '<label class="form-check-label" for="'.$option_id.'">'.valid($option).'</label>';
                        echo '</div>';
                    }
                    echo '</div>';
                    break;

                case 1: // Текстовое поле
                default:
                    echo '<input type="text" class="form-control" id="'.$field_id.'" name="'.$field_name.'" placeholder="'.valid($input['input_name']).'" value="'.valid($value).'" '.$required_attr.' '.$disabled_attr.'>';
                    break;
            }
            echo '</div>';
        }
    }

} catch (PDOException $e) {
    error_log('DB Error get-additional-fields: ' . $e->getMessage());
}
exit;