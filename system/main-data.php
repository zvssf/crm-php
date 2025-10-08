<?php
try {
    $pdo = db_connect();

    $stmt = $pdo->query("SELECT * FROM `settings_centers` ORDER BY `center_id` ASC");
    $centers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM `settings_countries` ORDER BY `country_id` ASC");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

   $stmt = $pdo->query("
        SELECT 
            sc.city_id, sc.city_name, sc.city_category, sc.country_id, sc.city_status, sc.cost_price, sc.min_sale_price
        FROM `settings_cities` sc
        ORDER BY sc.city_id ASC
    ");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = []; 

    $stmt = $pdo->query("SELECT * FROM `settings_inputs` ORDER BY `input_id` ASC");
    $inputs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT * FROM `fin_suppliers` WHERE `status` = '1' ORDER BY `id` ASC");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $arr_centers = array_column($centers, 'center_name', 'center_id');
    $arr_countries = array_column($countries, 'country_name', 'country_id');

    $grouped_menu_data = [];

    foreach ($countries as $country) {
        if ($country['country_status'] > 0) {
            $grouped_menu_data[$country['country_id']] = [
                'country_name' => $country['country_name'],
                'centers' => []
            ];
        }
    }

    foreach ($centers as $center) {
        if ($center['center_status'] > 0 && isset($grouped_menu_data[$center['country_id']])) {
            $grouped_menu_data[$center['country_id']]['centers'][] = $center;
        }
    }
    
    $current_city_data = null;
    if (isset($_GET['city'])) {
        $current_city_id = valid($_GET['city']);
        if (preg_match('/^[0-9]{1,11}$/u', $current_city_id)) {
            foreach ($cities as $city) {
                if ($city['city_id'] == $current_city_id) {
                    $current_city_data = $city;
                    break;
                }
            }
        }
    }

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    $centers = $countries = $cities = $inputs = $arr_centers = $arr_countries = $grouped_menu_data = [];
    $categories = [];
    $current_city_data = null;
}