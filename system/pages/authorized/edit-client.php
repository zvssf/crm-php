<?php
if (isset($_POST['action']) && $_POST['action'] === 'get_agents_by_manager') {
    header('Content-Type: application/json');
    require_once SYSTEM . '/config.php';
    require_once SYSTEM . '/functions.php';

    $manager_id = valid($_POST['manager_id'] ?? '');

    if (empty($manager_id) || !preg_match('/^[0-9]{1,11}$/u', $manager_id)) {
        echo json_encode([]);
        exit;
    }

    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare("
            SELECT `user_id`, `user_firstname`, `user_lastname` 
            FROM `users` 
            WHERE `user_supervisor` = :manager_id AND `user_group` = 4 AND `user_status` = 1
            ORDER BY `user_lastname` ASC
        ");
        $stmt->execute([':manager_id' => $manager_id]);
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($agents);
    } catch (PDOException $e) {
        error_log('DB Error on get_agents_by_manager: ' . $e->getMessage());
        echo json_encode([]);
    }
    exit;
}
require_once SYSTEM . '/helpers/nationalities.php';
$client_id = valid($_GET['id'] ?? '');
if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    redirect('dashboard');
}

require_once SYSTEM . '/main-data.php';

try {
    $current_center = null;
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM `clients` WHERE `client_id` = :client_id");
    $stmt->execute([':client_id' => $client_id]);
    $client_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client_data) {
        exit('Анкета не найдена!');
    }
} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    exit('Ошибка при загрузке данных анкеты.');
}

$center_id = $client_data['center_id'];
$current_center_name = $arr_centers[$center_id] ?? 'Неизвестный ВЦ';
// Находим country_id для заголовка страницы, но не для настроек
$country_id_for_title = null;
foreach ($centers as $center) {
    if ($center['center_id'] == $center_id) {
        $country_id_for_title = $center['country_id'];
        break;
    }
}
$country_name = $arr_countries[$country_id_for_title] ?? 'Неизвестная страна';

$page_title = 'Редактирование анкеты';

// --- НАЧАЛО БЛОКА ЗАГРУЗКИ НАСТРОЕК ПОЛЕЙ ---

// Настройки по умолчанию
$field_settings = [
    'first_name' => ['is_visible' => true, 'is_required' => true],
    'last_name' => ['is_visible' => true, 'is_required' => true],
    'middle_name' => ['is_visible' => true, 'is_required' => false],
    'phone' => ['is_visible' => true, 'is_required' => true],
    'gender' => ['is_visible' => true, 'is_required' => false],
    'email' => ['is_visible' => true, 'is_required' => false],
    'passport_number' => ['is_visible' => true, 'is_required' => true],
    'birth_date' => ['is_visible' => true, 'is_required' => false],
    'passport_expiry_date' => ['is_visible' => true, 'is_required' => false],
    'nationality' => ['is_visible' => true, 'is_required' => false],
    'agent_id' => ['is_visible' => true, 'is_required' => true],
    'city_ids' => ['is_visible' => true, 'is_required' => true],
    'sale_price' => ['is_visible' => true, 'is_required' => true],
    'visit_dates' => ['is_visible' => true, 'is_required' => false],
    'days_until_visit' => ['is_visible' => true, 'is_required' => false],
    'notes' => ['is_visible' => true, 'is_required' => false],
];

try {
    // $pdo уже был создан ранее в этом файле
    $stmt_fields = $pdo->prepare("
        SELECT `field_name`, `is_visible`, `is_required` 
        FROM `settings_center_fields` 
        WHERE `center_id` = :center_id
    ");
    $stmt_fields->execute([':center_id' => $center_id]);
    $db_settings = $stmt_fields->fetchAll(PDO::FETCH_ASSOC);
    
    if ($db_settings) {
        foreach ($db_settings as $row) {
            if (isset($field_settings[$row['field_name']])) {
                if (!in_array($row['field_name'], ['first_name', 'last_name', 'passport_number', 'agent_id', 'city_ids', 'sale_price', 'phone'])) {
                     $field_settings[$row['field_name']]['is_visible'] = (bool)$row['is_visible'];
                     $field_settings[$row['field_name']]['is_required'] = (bool)$row['is_required'];
                }
            }
        }
    }

} catch (PDOException $e) {
    error_log('DB Error fetching field settings: ' . $e->getMessage());
}

// --- КОНЕЦ БЛОКА ЗАГРУЗКИ НАСТРОЕК ПОЛЕЙ ---

$current_center = null;
foreach ($centers as $center) {
    if ($center['center_id'] == $center_id) {
        $current_center = $center;
        break;
    }
}

$is_readonly = false;
$user_group = $user_data['user_group'];
$client_status = (int) $client_data['client_status'];

// Анкеты в статусе "Отменён" всегда только для чтения
if ($client_status === 7) {
    $is_readonly = true;
}

// Блокируем форму для всех, кроме Директора, если статус "В работе" или "Записанные"
if (in_array($client_status, [1, 2]) && $user_group != 1) {
    $is_readonly = true;
}

// Блокируем форму для Руководителя всегда
if ($user_group == 2) {
    $is_readonly = true;
}

$cities_json = '[]';
$grouped_cities = [];
if (!empty($cities)) {
    foreach ($cities as $city) {
        if ($city['city_status'] == 1) {
            $grouped_cities[$city['city_name']][] = [
                'id' => $city['city_id'],
                'category' => $city['city_category']
            ];
        }
    }
    $cities_json = json_encode($grouped_cities);
}
$unique_city_names = array_keys($grouped_cities);

$saved_cities_json = '[]';
try {
    $stmt_saved = $pdo->prepare("
        SELECT sc.city_id, sc.city_name, sc.city_category
        FROM `client_cities` cc
        JOIN `settings_cities` sc ON cc.city_id = sc.city_id
        WHERE cc.client_id = :client_id
    ");
    $stmt_saved->execute([':client_id' => $client_id]);
    $saved_cities_data = $stmt_saved->fetchAll(PDO::FETCH_ASSOC);

    $saved_cities_for_js = [];
    if ($saved_cities_data) {
        foreach ($saved_cities_data as $row) {
            $saved_cities_for_js[] = [
                'id' => $row['city_id'],
                'name' => $row['city_name'] . ' – ' . $row['city_category'],
                'cityName' => $row['city_name']
            ];
        }
    }
    $saved_cities_json = json_encode($saved_cities_for_js);

} catch (PDOException $e) {
    error_log('DB Error fetching saved cities: ' . $e->getMessage());
}

$managers = [];
$agents = [];
$current_manager_id = null;

try {
    $pdo_users = db_connect();

    if ($client_data['agent_id']) {
        $stmt_manager = $pdo_users->prepare("SELECT `user_supervisor` FROM `users` WHERE `user_id` = :agent_id");
        $stmt_manager->execute([':agent_id' => $client_data['agent_id']]);
        $current_manager_id = $stmt_manager->fetchColumn();
    }

    if ($user_data['user_group'] == 1) {
        $stmt = $pdo_users->query("
            SELECT user_id, user_firstname, user_lastname 
            FROM users 
            WHERE user_group = 3 AND user_status = 1 
            ORDER BY user_lastname ASC
        ");
        $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($user_data['user_group'] == 2) {
        $stmt = $pdo_users->prepare("
            SELECT user_id, user_firstname, user_lastname 
            FROM users 
            WHERE user_group = 3 AND user_status = 1 AND user_supervisor = :supervisor_id
            ORDER BY user_lastname ASC
        ");
        $stmt->execute([':supervisor_id' => $user_data['user_id']]);
        $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($user_data['user_group'] == 3) {
        $stmt = $pdo_users->prepare("
            SELECT user_id, user_firstname, user_lastname 
            FROM users 
            WHERE user_group = 4 AND user_status = 1 AND user_supervisor = :manager_id
            ORDER BY user_lastname ASC
        ");
        $stmt->execute([':manager_id' => $user_data['user_id']]);
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log('DB Error fetching users for edit-client page: ' . $e->getMessage());
}

require_once SYSTEM . '/layouts/head.php';
?>

<body>
    <div class="wrapper">
        <?php require_once SYSTEM . '/layouts/menu.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="/?page=dashboard">Главная</a></li>
                                        <li class="breadcrumb-item"><a
                                                href="/?page=clients&center=<?= $center_id ?>">Анкеты</a></li>
                                        <li class="breadcrumb-item active">Редактирование анкеты</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Редактирование анкеты №<?= $client_id ?>: <?= $country_name ?> -
                                    <?= $current_center_name ?></h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="form-edit-client" class="needs-validation" novalidate autocomplete="off">
                                        <input type="hidden" name="client_id" value="<?= $client_id ?>">
                                        <div class="row">
                                            <!-- Блок Основная информация -->
                                            <div class="col-xl-4">
                                                <h5 class="mb-4 text-uppercase"><i
                                                        class="mdi mdi-account-circle me-1"></i> Основная информация
                                                </h5>
                                                <div class="mb-3">
                                                    <label for="last_name" class="form-label">Фамилия</label>
                                                    <input type="text" class="form-control" id="last_name"
                                                        name="last_name" placeholder="Введите фамилию"
                                                        value="<?= valid($client_data['last_name']) ?>" required
                                                        <?= $is_readonly ? 'disabled' : '' ?>>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="first_name" class="form-label">Имя</label>
                                                    <input type="text" class="form-control" id="first_name"
                                                        name="first_name" placeholder="Введите имя"
                                                        value="<?= valid($client_data['first_name']) ?>" required
                                                        <?= $is_readonly ? 'disabled' : '' ?>>
                                                </div>

                                                <?php if ($field_settings['middle_name']['is_visible']): ?>
                                                <div class="mb-3">
                                                    <label for="middle_name" class="form-label">Отчество</label>
                                                    <input type="text" class="form-control" id="middle_name"
                                                        name="middle_name" placeholder="Введите отчество"
                                                        value="<?= valid($client_data['middle_name']) ?>"
                                                        <?php if ($field_settings['middle_name']['is_required'] && !$is_readonly): ?>required<?php endif; ?>
                                                        <?= $is_readonly ? 'disabled' : '' ?>>
                                                </div>
                                                <?php endif; ?>

                                                <?php if ($field_settings['phone']['is_visible']): ?>
                                                <div class="mb-3">
                                                    <label for="phone_number" class="form-label">Мобильный
                                                        телефон</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">+</span>
                                                        <input type="text" class="form-control" placeholder="Код"
                                                            name="phone_code" id="phone_code" value="<?= valid($client_data['phone_code']) ?>"
                                                            style="max-width: 80px;" oninput="this.value = this.value.replace(/[^0-9]/g, '')" <?php if ($field_settings['phone']['is_required'] && !$is_readonly): ?>required<?php endif; ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                                        <input type="text" class="form-control"
                                                            placeholder="Номер телефона" name="phone_number"
                                                            id="phone_number" value="<?= valid($client_data['phone_number']) ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '')" <?php if ($field_settings['phone']['is_required'] && !$is_readonly): ?>required<?php endif; ?>
                                                            <?= $is_readonly ? 'disabled' : '' ?>>
                                                    </div>
                                                </div>
                                                <?php endif; ?>

                                                <?php if ($field_settings['gender']['is_visible']): ?>
                                                <div class="mb-3">
                                                    <label for="gender" class="form-label">Пол</label>
                                                    <select class="form-select" id="gender" name="gender"
                                                        <?php if ($field_settings['gender']['is_required'] && !$is_readonly): ?>required<?php endif; ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                                        <option value="male" <?= ($client_data['gender'] == 'male') ? 'selected' : '' ?>>Мужской</option>
                                                        <option value="female" <?= ($client_data['gender'] == 'female') ? 'selected' : '' ?>>Женский</option>
                                                    </select>
                                                </div>
                                                <?php endif; ?>

                                                <?php if ($field_settings['email']['is_visible']): ?>
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="email" name="email"
                                                        placeholder="Введите email"
                                                        value="<?= valid($client_data['email']) ?>" <?php if ($field_settings['email']['is_required'] && !$is_readonly): ?>required<?php endif; ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                                </div>
                                                <?php endif; ?>

                                                <div id="additional-fields-container">
                                                    <hr>
                                                    <h5 class="mb-3 text-uppercase"><i
                                                            class="mdi mdi-plus-box-outline me-1"></i> Дополнительные
                                                        поля</h5>
                                                    <p class="text-muted">Выберите категории, чтобы увидеть доступные
                                                        поля.</p>
                                                </div>
                                            </div>

                                            <!-- Блок Документы -->
                                            <div class="col-xl-4">
                                                <h5 class="mb-4 text-uppercase"><i
                                                        class="mdi mdi-card-account-details-outline me-1"></i> Документы
                                                </h5>
                                                <div class="mb-3">
                                                    <label for="passport_number" class="form-label">Номер
                                                        паспорта</label>
                                                    <input type="text" class="form-control" id="passport_number"
                                                        name="passport_number" placeholder="Введите номер паспорта"
                                                        value="<?= valid($client_data['passport_number']) ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required
                                                        <?= $is_readonly ? 'disabled' : '' ?>>
                                                </div>

                                                <?php if ($field_settings['birth_date']['is_visible']): ?>
                                                <div class="mb-3">
                                                    <label for="birth_date" class="form-label">Дата рождения</label>
                                                    <input type="text" class="form-control" id="birth_date" name="birth_date"
                                                        value="<?= !empty($client_data['birth_date']) ? date('d.m.Y', strtotime($client_data['birth_date'])) : '' ?>"
                                                        placeholder="ДД.ММ.ГГГГ" <?php if ($field_settings['birth_date']['is_required'] && !$is_readonly): ?>required<?php endif; ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                                </div>
                                                <?php endif; ?>

                                                <?php if ($field_settings['passport_expiry_date']['is_visible']): ?>
                                                <div class="mb-3">
                                                    <label for="passport_expiry_date" class="form-label">Срок действия
                                                        паспорта</label>
                                                    <input type="text" class="form-control" id="passport_expiry_date"
                                                        name="passport_expiry_date"
                                                        value="<?= !empty($client_data['passport_expiry_date']) ? date('d.m.Y', strtotime($client_data['passport_expiry_date'])) : '' ?>"
                                                        placeholder="ДД.ММ.ГГГГ" <?php if ($field_settings['passport_expiry_date']['is_required'] && !$is_readonly): ?>required<?php endif; ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                                </div>
                                                <?php endif; ?>

                                                <?php if ($field_settings['nationality']['is_visible']): ?>
                                                <div class="mb-3">
                                                    <label for="nationality" class="form-label">Национальность</label>
                                                    <select id="nationality" class="form-control select2"
                                                        data-toggle="select2" name="nationality" <?php if ($field_settings['nationality']['is_required'] && !$is_readonly): ?>required<?php endif; ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                                        <option value="">Выберите национальность...</option>
                                                        <?php foreach ($nationalities_list as $nationality): ?>
                                                            <option value="<?= $nationality ?>"
                                                                <?= ($nationality == valid($client_data['nationality'])) ? 'selected' : '' ?>><?= $nationality ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Блок Информация -->
                                            <div class="col-xl-4">
                                                <h5 class="mb-4 text-uppercase"><i
                                                        class="mdi mdi-information-outline me-1"></i> Информация</h5>
                                                <?php if (in_array($user_data['user_group'], [1, 2])): // Директор и Руководитель ?>
                                                    <div class="mb-3">
                                                        <label for="select-manager" class="form-label">Менеджер</label>
                                                        <select id="select-manager" class="form-control select2"
                                                            data-toggle="select2" name="manager_id" <?= $is_readonly ? 'disabled' : '' ?> required>
                                                            <option value="">Выберите менеджера...</option>
                                                            <?php foreach ($managers as $manager): ?>
                                                                <option value="<?= $manager['user_id'] ?>"
                                                                    <?= ($manager['user_id'] == $current_manager_id) ? 'selected' : '' ?>>
                                                                    <?= valid($manager['user_firstname'] . ' ' . $manager['user_lastname']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="invalid-feedback">Выберите менеджера!</div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="select-agent" class="form-label">Агент</label>
                                                        <select id="select-agent" class="form-control select2"
                                                            data-toggle="select2" name="agent_id" disabled required
                                                            <?= $is_readonly ? 'disabled' : '' ?>>
                                                            <option value="">Сначала выберите менеджера...</option>
                                                        </select>
                                                        <div class="invalid-feedback">Выберите агента!</div>
                                                    </div>
                                                <?php elseif ($user_data['user_group'] == 3): // Менеджер ?>
                                                    <div class="mb-3">
                                                        <label for="select-agent" class="form-label">Агент</label>
                                                        <select id="select-agent" class="form-control select2"
                                                            data-toggle="select2" name="agent_id" required <?= $is_readonly ? 'disabled' : '' ?>>
                                                            <option value="">Выберите агента...</option>
                                                            <?php foreach ($agents as $agent): ?>
                                                                <option value="<?= $agent['user_id'] ?>"
                                                                    <?= ($agent['user_id'] == $client_data['agent_id']) ? 'selected' : '' ?>>
                                                                    <?= valid($agent['user_firstname'] . ' ' . $agent['user_lastname']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="invalid-feedback">Выберите агента!</div>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="mb-3">
                                                    <label class="form-label">Категории</label>
                                                    <div>
                                                        <button type="button" class="btn btn-primary"
                                                            data-bs-toggle="modal" data-bs-target="#cities-modal"
                                                            <?= $is_readonly ? 'disabled' : '' ?>>
                                                            <i class="mdi mdi-pencil me-1"></i> Настроить
                                                        </button>
                                                    </div>
                                                    <div id="selected-cities-list" class="mt-2">
                                                        <!-- Список выбранных городов будет здесь -->
                                                    </div>
                                                    <div id="hidden-city-inputs">
                                                        <!-- Скрытые input для отправки на сервер -->
                                                    </div>
                                                </div>

                                                <div class="mb-3" id="sale-price-wrapper">
                                                    <label for="sale_price" class="form-label">Стоимость</label>
                                                    <input type="text" class="form-control" id="sale_price" name="sale_price" value="<?= valid($client_data['sale_price'] ?? '') ?>" placeholder="Введите стоимость" data-toggle="touchspin" data-step="0.01" data-min="0" data-max="10000000" data-decimals="2" data-bts-prefix="$" <?= $is_readonly ? 'disabled' : '' ?> required>
                                                    <div class="invalid-feedback">Некорректная стоимость</div>
                                                </div>
                                                
                                                <?php if ($field_settings['visit_dates']['is_visible']): ?>
                                                <div class="mb-3">
                                                    <label for="visit_dates" class="form-label">Даты визита</label>
                                                    <?php
                                                    $visit_dates_value = '';
                                                    if (!empty($client_data['visit_date_start']) && !empty($client_data['visit_date_end'])) {
                                                        $visit_dates_value = date('d.m.Y', strtotime($client_data['visit_date_start'])) . ' - ' . date('d.m.Y', strtotime($client_data['visit_date_end']));
                                                    }
                                                    ?>
                                                    <input type="text" class="form-control" id="visit_dates"
                                                        name="visit_dates" placeholder="Выберите даты"
                                                        value="<?= $visit_dates_value ?>" <?php if ($field_settings['visit_dates']['is_required'] && !$is_readonly): ?>required<?php endif; ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($field_settings['days_until_visit']['is_visible']): ?>
                                                <div class="mb-3">
                                                    <label for="days_until_visit" class="form-label">Дни до
                                                        визита</label>
                                                    <input type="text" class="form-control" id="days_until_visit"
                                                        name="days_until_visit" placeholder="Введите дни до визита"
                                                        value="<?= valid($client_data['days_until_visit']) ?>"
                                                        data-toggle="touchspin" data-max="9999" <?php if ($field_settings['days_until_visit']['is_required'] && !$is_readonly): ?>required<?php endif; ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                                </div>
                                                <?php endif; ?>

                                                <?php if ($field_settings['notes']['is_visible']): ?>
                                                <div class="mb-3">
                                                    <label for="notes" class="form-label">Ваши пометки</label>
                                                    <textarea class="form-control" id="notes" name="notes" rows="3"
                                                        <?php if ($field_settings['notes']['is_required'] && !$is_readonly): ?>required<?php endif; ?> <?= $is_readonly ? 'disabled' : '' ?>><?= valid($client_data['notes']) ?></textarea>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="text-end mt-2">
                                                    <?php if (!$is_readonly): ?>
                                                        <button class="btn btn-success" type="submit" id="btn-save">
                                                            <span
                                                                class="spinner-border spinner-border-sm me-1 btn-loader visually-hidden"
                                                                role="status" aria-hidden="true"></span>
                                                            <span class="btn-icon"><i
                                                                    class="mdi mdi-content-save me-1"></i></span>
                                                            <span class="loader-text visually-hidden">Отправка...</span>
                                                            <span class="btn-text">Сохранить</span>
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="/?page=clients&center=<?= $center_id ?>&status=<?= $client_status ?>"
                                                            class="btn btn-secondary">
                                                            <i class="mdi mdi-close me-1"></i> Закрыть
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once SYSTEM . '/layouts/footer.php'; ?>
        </div>
    </div>

    <!-- Модальное окно для выбора категорий -->
    <div id="cities-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="cities-modal-label"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="cities-modal-label">Настройка категорий</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-cities-container"></div>
                </div>
                <div class="modal-footer">
                    <div class="w-100 d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-primary" id="modal-add-city-btn"><i
                                    class="mdi mdi-plus-circle me-1"></i> Добавить</button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                            <button type="button" class="btn btn-success" id="save-cities-btn">Сохранить</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once SYSTEM . '/layouts/scripts.php'; ?>

    <script>
        $(document).ready(function () {
            const isReadonly = <?= $is_readonly ? 'true' : 'false' ?>;
            "use strict";

            function updateCategoryOptions() {
                // Шаг 1: Собрать все уже выбранные ID категорий.
                const selectedValues = $('.category-select').map(function() {
                    return $(this).val();
                }).get().filter(Boolean).map(String);

                // Шаг 2: Обновить каждый выпадающий список КАТЕГОРИЙ.
                $('.category-select').each(function() {
                    const currentSelect = $(this);
                    const itsOwnValue = currentSelect.val() ? String(currentSelect.val()) : null;
                    const selectedCityName = currentSelect.closest('.city-row').find('.city-select').val();

                    if (currentSelect.hasClass("select2-hidden-accessible")) {
                        currentSelect.select2('destroy');
                    }

                    currentSelect.find('option:not([value=""])').remove();
                    
                    if (selectedCityName && allCitiesData[selectedCityName]) {
                        currentSelect.prop('disabled', false);
                        allCitiesData[selectedCityName].forEach(function(cat) {
                            const catId = String(cat.id);
                            if (!selectedValues.includes(catId) || catId === itsOwnValue) {
                                currentSelect.append(`<option value="${cat.id}">${cat.category}</option>`);
                            }
                        });
                    } else {
                        currentSelect.prop('disabled', true);
                    }
                    
                    currentSelect.val(itsOwnValue);
                    currentSelect.select2({ dropdownParent: $('#cities-modal') });
                });

                // Шаг 3: Определить, какие города "исчерпаны".
                const exhaustedCities = uniqueCityNames.filter(cityName => {
                    const categoriesForCity = allCitiesData[cityName] || [];
                    if (categoriesForCity.length === 0) return true;
                    const categoryIdsForCity = categoriesForCity.map(cat => String(cat.id));
                    return categoryIdsForCity.every(id => selectedValues.includes(id));
                });

                // Шаг 4: Обновить каждый выпадающий список ГОРОДОВ.
                $('.city-select').each(function() {
                    const currentCitySelect = $(this);
                    const itsOwnCity = currentCitySelect.val();

                    if (currentCitySelect.hasClass("select2-hidden-accessible")) {
                        currentCitySelect.select2('destroy');
                    }

                    currentCitySelect.find('option:not([value=""])').remove();

                    uniqueCityNames.forEach(cityName => {
                        if (!exhaustedCities.includes(cityName) || cityName === itsOwnCity) {
                            currentCitySelect.append(`<option value="${cityName}">${cityName}</option>`);
                        }
                    });
                    
                    currentCitySelect.val(itsOwnCity);
                    currentCitySelect.select2({ dropdownParent: $('#cities-modal') });
                });
            }

            // При изменении выбора в select2 убираем подсветку ошибки
            $('#select-agent, #select-manager, #nationality').on('change', function() {
                $(this).next('.select2-container').removeClass('is-invalid');
            });

            $('#visit_dates').daterangepicker({
                autoUpdateInput: false,
                locale: { "format": "DD.MM.YYYY", "separator": " - ", "applyLabel": "Применить", "cancelLabel": "Отмена", "fromLabel": "С", "toLabel": "По", "weekLabel": "Н", "daysOfWeek": ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"], "monthNames": ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"], "firstDay": 1 }
            });
            $('#visit_dates').on('apply.daterangepicker', function (ev, picker) { 
                $(this).val(picker.startDate.format('DD.MM.YYYY') + ' - ' + picker.endDate.format('DD.MM.YYYY')); 
                
                // Автоматический расчет дней
                const startDate = picker.startDate;
                const today = moment().startOf('day');
                const diffDays = startDate.diff(today, 'days');
                $('#days_until_visit').val(diffDays >= 0 ? diffDays : 0).trigger("change");
            });
            $('#visit_dates').on('cancel.daterangepicker', function (ev, picker) { 
                $(this).val(''); 
                $('#days_until_visit').val(0).trigger("change");
            });

            $('#nationality, #select-manager, #select-agent').on('select2:open', function (e) {
                document.querySelector('.select2-container--open .select2-search__field').setAttribute('placeholder', 'Поиск...');
            });

            const datepickerOptions = {
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: false,
                locale: { "format": "DD.MM.YYYY", "applyLabel": "Применить", "cancelLabel": "Отмена", "fromLabel": "С", "toLabel": "По", "weekLabel": "Н", "daysOfWeek": ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"], "monthNames": ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"], "firstDay": 1 }
            };

            $('#birth_date, #passport_expiry_date').daterangepicker(datepickerOptions);

            $('#birth_date, #passport_expiry_date').on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('DD.MM.YYYY'));
            });

            $('#birth_date, #passport_expiry_date').on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
            });

            function loadAgentsForManager(managerId, selectedAgentId = null) {
                const agentSelect = $('#select-agent');

                agentSelect.empty().prop('disabled', true);

                if (managerId) {
                    agentSelect.append($('<option>', { value: '', text: 'Загрузка...' })).trigger('change');

                    $.ajax({
                        url: '/?page=edit-client',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'get_agents_by_manager',
                            manager_id: managerId
                        },
                        success: function (agents) {
                            agentSelect.empty();
                            if (agents.length > 0) {
                                agentSelect.append($('<option>', { value: '', text: 'Выберите агента...' }));
                                $.each(agents, function (i, agent) {
                                    agentSelect.append($('<option>', {
                                        value: agent.user_id,
                                        text: agent.user_firstname + ' ' + agent.user_lastname
                                    }));
                                });
                                if (!isReadonly) {
                                    agentSelect.prop('disabled', false);
                                }
                                if (selectedAgentId) {
                                    agentSelect.val(selectedAgentId);
                                }
                            } else {
                                agentSelect.append($('<option>', { value: '', text: 'У этого менеджера нет агентов' }));
                            }
                            agentSelect.trigger('change');
                        },
                        error: function () {
                            agentSelect.empty().append($('<option>', { value: '', text: 'Ошибка загрузки' }));
                            agentSelect.trigger('change');
                        }
                    });
                } else {
                    agentSelect.append($('<option>', { value: '', text: 'Сначала выберите менеджера...' }));
                    agentSelect.trigger('change');
                }
            }

            $('#select-manager').on('change', function () {
                loadAgentsForManager($(this).val());
            });

            // Первоначальная загрузка агентов, если менеджер уже выбран
            const initialManagerId = $('#select-manager').val();
            if (initialManagerId) {
                loadAgentsForManager(initialManagerId, <?= $client_data['agent_id'] ?? 'null' ?>);
            }

            const allCitiesData = <?= $cities_json ?>;
            const uniqueCityNames = <?= json_encode($unique_city_names) ?>;
            let selectedCategories = <?= $saved_cities_json ?>;

            function getModalRowTemplate(rowIndex) {
                let cityOptions = uniqueCityNames.map(city => `<option value="${city}">${city}</option>`).join('');
                return `
                <div class="row align-items-center mb-3 city-row" data-index="${rowIndex}">
                    <div class="col-5">
                        <select class="form-control city-select">
                            <option value="">Выберите город...</option>
                            ${cityOptions}
                        </select>
                    </div>
                    <div class="col-5">
                        <select class="form-control category-select" disabled>
                            <option value="">Выберите категорию...</option>
                        </select>
                    </div>
                    <div class="col-2 text-end">
                        <button type="button" class="btn btn-danger btn-sm remove-city-btn"><i class="mdi mdi-trash-can-outline"></i></button>
                    </div>
                </div>`;
            }

            let modalRowIndex = 0;
            function addNewModalRow() {
                $('#modal-cities-container').append(getModalRowTemplate(modalRowIndex));
                const newRow = $(`#modal-cities-container .city-row[data-index=${modalRowIndex}]`);
                newRow.find('.city-select').select2({ dropdownParent: $('#cities-modal') });
                modalRowIndex++;
                updateCategoryOptions();
            }

            $('#modal-add-city-btn').on('click', addNewModalRow);
            $('#modal-cities-container').on('click', '.remove-city-btn', function() {
                $(this).closest('.city-row').remove();
                updateCategoryOptions();
            });

            $('#modal-cities-container').on('change', '.city-select', function () {
                // При смене города, просто сбрасываем выбор категории и вызываем событие 'change'
                // Это событие, в свою очередь, вызовет нашу главную функцию updateCategoryOptions
                $(this).closest('.city-row').find('.category-select').val('').trigger('change');
            });

            $('#modal-cities-container').on('change', '.category-select', function() {
                updateCategoryOptions();
            });

            $('#save-cities-btn').on('click', function () {
                selectedCategories = [];
                $('#modal-cities-container .city-row').each(function () {
                    const cityName = $(this).find('.city-select').val();
                    const categoryId = $(this).find('.category-select').val();
                    if (cityName && categoryId) {
                        const categoryName = $(this).find('.category-select option:selected').text();
                        selectedCategories.push({
                            id: categoryId,
                            name: `${cityName} – ${categoryName}`,
                            cityName: cityName
                        });
                    }
                });
                updateSelectedCategoriesDisplay();
                $('#cities-modal').modal('hide');
            });

            function updateSelectedCategoriesDisplay() {
                const listContainer = $('#selected-cities-list');
                const hiddenContainer = $('#hidden-city-inputs');
                listContainer.empty();
                hiddenContainer.empty();

                let cityIds = [];

                if (selectedCategories.length > 0) {
                    const list = $('<ul>').addClass('list-unstyled mb-0');
                    selectedCategories.forEach(function (cat) {
                        list.append(`<li><i class="mdi mdi-check text-success me-1"></i>${cat.name}</li>`);
                        hiddenContainer.append(`<input type="hidden" name="city_ids[]" value="${cat.id}">`);
                        cityIds.push(cat.id);
                    });
                    listContainer.append(list);
                } else {
                    listContainer.append('<p class="text-muted">Категории не выбраны.</p>');
                }

                loadAdditionalFields(cityIds);
                loadSalePrice(cityIds);
            }

            $('#cities-modal').on('show.bs.modal', function () {
                $('#modal-cities-container').empty();
                modalRowIndex = 0;
                if (selectedCategories.length > 0) {
                    selectedCategories.forEach(function(cat) {
                        $('#modal-cities-container').append(getModalRowTemplate(modalRowIndex));
                        const newRow = $(`#modal-cities-container .city-row[data-index=${modalRowIndex}]`);
                        
                        newRow.find('.city-select').select2({ dropdownParent: $('#cities-modal') });
                        newRow.find('.city-select').val(cat.cityName).trigger('change');

                        setTimeout(function() {
                            const categorySelect = newRow.find('.category-select');
                            categorySelect.val(cat.id).trigger('change');
                        }, 100);

                        modalRowIndex++;
                    });
                } else {
                    addNewModalRow();
                }
                // Добавляем короткую задержку, чтобы все select2 успели отрисоваться
                setTimeout(updateCategoryOptions, 200);
            });

            updateSelectedCategoriesDisplay();

            // Отключаем отправку формы по нажатию Enter
            $('#form-edit-client').on('keydown', 'input', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            $('#form-edit-client').on('submit', function (event) {
                event.preventDefault();
                const form = $(this);
                form.addClass('was-validated');

                // Сбрасываем кастомные ошибки
                $('#btn-configure-cities').removeClass('is-invalid');
                $('#sale_price').removeClass('is-invalid');
                form.find('.select2-container.is-invalid').removeClass('is-invalid');

                // Проверка обязательных select2
                let isSelect2Invalid = false;
                form.find('select.select2-hidden-accessible:required').each(function () {
                    if (!$(this).val()) {
                        $(this).next('.select2-container').addClass('is-invalid');
                        isSelect2Invalid = true;
                    }
                });

                // Проверка категорий
                const hasCategories = ($('input[name="city_ids[]"]').length > 0);
                if (!hasCategories) {
                    $('#btn-configure-cities').addClass('is-invalid');
                }

                // Проверка стандартных полей
                if (!form[0].checkValidity() || isSelect2Invalid || !hasCategories) {
                    message('Ошибка', 'Пожалуйста, заполните все обязательные поля!', 'error');
                    return;
                }

                // Проверка стоимости (только если все остальное в порядке)
                const priceInput = $('#sale_price');
                const salePriceVal = priceInput.val();
                const minPrice = parseFloat(priceInput.data('min-price') || 0);

                if (salePriceVal === '' || parseFloat(salePriceVal) < minPrice) {
                    priceInput.addClass('is-invalid');
                    message('Ошибка', 'Некорректная стоимость!', 'error');
                    return;
                }

                loaderBTN('#btn-save', 'true');

                $.ajax({
                    url: '/?form=edit-client',
                    type: 'POST',
                    dataType: 'json',
                    data: form.serialize(),
                    success: function (result) {
                        loaderBTN('#btn-save', 'false');
                        if (result.success_type == 'message') {
                            let redirectUrl = result.msg_url;
                            if (result.new_status) {
                                redirectUrl = 'clients&center=<?= $center_id ?>&status=' + result.new_status;
                            }
                            message(result.msg_title, result.msg_text, result.msg_type, redirectUrl);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        loaderBTN('#btn-save', 'false');
                        console.log('AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
                        message('Ошибка', 'Произошла ошибка на сервере. Пожалуйста, проверьте консоль браузера для деталей.', 'error', '');
                    }
                });
            });

            function loadAdditionalFields(cityIds) {
                const container = $('#additional-fields-container');
                const clientId = $('input[name="client_id"]').val();
                const defaultText = '<hr><h5 class="mb-3 text-uppercase"><i class="mdi mdi-plus-box-outline me-1"></i> Дополнительные поля</h5><p class="text-muted">Выберите категории, чтобы увидеть доступные поля.</p>';

                if (cityIds.length === 0) {
                    container.html(defaultText);
                    return;
                }

                $.ajax({
                    url: '/?form=get-additional-fields',
                    type: 'POST',
                    data: {
                        city_ids: cityIds,
                        client_id: clientId,
                        is_readonly: isReadonly
                    },
                    success: function (response) {
                        if (response.trim() !== '') {
                            container.html(response);
                        } else {
                            container.html(defaultText);
                        }
                    },
                    error: function () {
                        container.html('<hr><h5 class="mb-3 text-uppercase"><i class="mdi mdi-plus-box-outline me-1"></i> Дополнительные поля</h5><p class="text-danger">Ошибка загрузки дополнительных полей.</p>');
                    }
                });
            }

            function loadSalePrice(cityIds) {
                // Функция оставлена для обратной совместимости вызовов,
                // но поле "Стоимость" теперь всегда активно.
                // Мы просто гарантируем, что min-price всегда 0 для валидации.
                $('#sale_price').data('min-price', 0);
            }
        });
    </script>
</body>

</html>