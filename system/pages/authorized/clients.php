<?php
$center_id = valid($_GET['center'] ?? '');

if (empty($center_id) || !preg_match('/^[0-9]{1,11}$/u', $center_id)) {
    redirect('dashboard');
}

require_once SYSTEM . '/main-data.php';

$current_center = null;
foreach ($centers as $center) {
    if ($center['center_id'] == $center_id) {
        $current_center = $center;
        break;
    }
}

if (!$current_center) {
    exit('Визовый центр не найден!');
}

$country_name = $arr_countries[$current_center['country_id']] ?? 'Неизвестная страна';
$center_name = $current_center['center_name'];

$current_country = null;
foreach ($countries as $country) {
    if ($country['country_id'] == $current_center['country_id']) {
        $current_country = $country;
        break;
    }
}

$can_create_client = true;
if (
    !$current_country ||
    $current_country['country_status'] != 1 ||
    $current_center['center_status'] != 1
) {
    $can_create_client = false;
}

// Определяем доступные статусы и статус по умолчанию для каждой роли
$allowed_statuses = [];
$default_status = 2; // По умолчанию для Агента, Менеджера, Директора

switch ($user_data['user_group']) {
    case 2: // Руководитель
        $allowed_statuses = [1, 2];
        $default_status = 1;
        break;
    case 1: // Директор
    case 3: // Менеджер
        $allowed_statuses = [1, 2, 3, 4, 5, 6];
        $default_status = 2;
        break;
    case 4: // Агент
        $allowed_statuses = [1, 2, 3, 4];
        $default_status = 2;
        break;
}

$current_status = valid($_GET['status'] ?? $default_status);
if (!in_array($current_status, $allowed_statuses)) {
    $current_status = $default_status;
}


try {
    $pdo = db_connect();

    $base_sql_from = "
        FROM `clients` c
        LEFT JOIN `users` agent ON c.agent_id = agent.user_id
        LEFT JOIN `users` manager ON agent.user_supervisor = manager.user_id
    ";

    $where_conditions = "WHERE c.center_id = :center_id";
    $params = [':center_id' => $center_id];

    // Формируем базовые WHERE условия для каждой роли
    switch ($user_data['user_group']) {
        case 2: // Руководитель
            $where_conditions .= " AND manager.user_supervisor = :user_id";
            $params[':user_id'] = $user_data['user_id'];
            break;
        case 3: // Менеджер
            // Для вкладки "Черновики" менеджер видит ТОЛЬКО свои анкеты
            if ($current_status == 3) {
                $where_conditions .= " AND c.agent_id = :user_id";
                $params[':user_id'] = $user_data['user_id'];
            } else {
                // Для всех остальных вкладок он видит анкеты своих агентов И свои собственные
                $where_conditions .= " AND (manager.user_id = :manager_user_id OR c.agent_id = :agent_user_id)";
                $params[':manager_user_id'] = $user_data['user_id'];
                $params[':agent_user_id'] = $user_data['user_id'];
            }
            break;
        case 4: // Агент
            $where_conditions .= " AND c.agent_id = :user_id";
            $params[':user_id'] = $user_data['user_id'];
            break;
    }

    // Запрос для получения счетчиков
    $counts = array_fill(1, 6, 0);
    $sql_counts = "SELECT c.client_status, COUNT(*) as count " . $base_sql_from . $where_conditions . " AND c.client_status IN (1, 2, 3, 4, 5, 6) GROUP BY c.client_status";
    $stmt_counts = $pdo->prepare($sql_counts);
    $stmt_counts->execute($params);
    $status_counts = $stmt_counts->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($status_counts as $status => $count) {
        if (isset($counts[$status])) {
            $counts[$status] = $count;
        }
    }

    // Динамическая фильтрация для вкладки "На рассмотрении"
    $final_where_conditions = $where_conditions;
    if ($current_status == 5 && $user_data['user_group'] == 3) {
        $final_where_conditions .= " AND c.client_status = 6"; // Менеджер на этой вкладке видит анкеты от своих агентов (статус 6)
    } else {
        $final_where_conditions .= " AND c.client_status = :status";
        $params[':status'] = $current_status;
    }

    $sql_clients = "
        SELECT 
            c.*,
            agent.user_firstname as agent_firstname,
            agent.user_lastname as agent_lastname,
            agent.user_group as agent_group,
            manager.user_firstname as manager_firstname,
            manager.user_lastname as manager_lastname,
            GROUP_CONCAT(DISTINCT sc.city_id ORDER BY sc.city_id SEPARATOR ',') as client_category_ids,
            GROUP_CONCAT(DISTINCT sc.city_name ORDER BY sc.city_name SEPARATOR ', ') as client_cities_list,
            GROUP_CONCAT(DISTINCT sc.city_category ORDER BY sc.city_category SEPARATOR ', ') as client_categories_list
        " . $base_sql_from . "
        LEFT JOIN `client_cities` cc ON c.client_id = cc.client_id
        LEFT JOIN `settings_cities` sc ON cc.city_id = sc.city_id
        " . $final_where_conditions . "
        GROUP BY c.client_id
        ORDER BY c.client_id DESC
    ";

    $stmt_clients = $pdo->prepare($sql_clients);
    $stmt_clients->execute($params);
    $clients = $stmt_clients->fetchAll(PDO::FETCH_ASSOC);

    // Группировку и вывод категорий применяем только для вкладок "В работе" и "Записанные"
    if (in_array($current_status, [1, 2]) && !empty($clients)) {
        // Шаг 1: Группируем анкеты по ключу "паспорт + ID категорий"
        $groups = [];
        foreach ($clients as $client) {
            $passport = trim($client['passport_number']);
            // Создаем уникальный ключ группы. Анкеты без паспорта не группируются.
            $group_key = !empty($passport) ? $passport : '__unique__' . $client['client_id'];
            $groups[$group_key][] = $client;
        }

        // Шаг 2: Обрабатываем сгруппированные анкеты
        $processed_clients = [];
        foreach ($groups as $current_group) {
            if (count($current_group) > 1) {
                // Сортируем группу, чтобы найти главную анкету
                usort($current_group, function ($a, $b) {
                    if ((float) $a['sale_price'] != (float) $b['sale_price']) {
                        return (float) $b['sale_price'] <=> (float) $a['sale_price']; // Сначала по убыванию цены
                    }
                    return $a['client_id'] <=> $b['client_id']; // Затем по возрастанию ID
                });

                // Первая анкета после сортировки - главная
                $main_client_data = $current_group[0];

                // Присваиваем всем анкетам в группе данные для сортировки от главной анкеты
                $group_size = count($current_group);
                foreach ($current_group as $index => &$member) {
                    // Основной ключ сортировки, чтобы группа держалась вместе
                    $member['sort_group_id'] = $main_client_data['client_id'];
                    // Вторичный ключ, инвертированный для правильной DESC сортировки
                    $member['sort_in_group_order'] = $group_size - $index;

                    // Поля для сортировки, взятые из главной анкеты
                    $member['sort_client_name'] = trim($main_client_data['last_name'] . ' ' . $main_client_data['first_name'] . ' ' . $main_client_data['middle_name']);
                    $member['sort_phone'] = $main_client_data['phone'];
                    $member['sort_passport'] = $main_client_data['passport_number'];
                    $member['sort_cities'] = $main_client_data['client_cities_list'];
                    $member['sort_categories'] = $main_client_data['client_categories_list'];
                    $member['sort_manager'] = ($main_client_data['manager_firstname'] ?? '') . ' ' . ($main_client_data['manager_lastname'] ?? '');
                    $member['sort_agent'] = ($main_client_data['agent_firstname'] ?? '') . ' ' . ($main_client_data['agent_lastname'] ?? '');
                    $member['sort_price'] = (float) $main_client_data['sale_price'];

                    if ($index === 0) {
                        $member['is_main_duplicate'] = true;
                    } else {
                        $member['is_duplicate'] = true;
                        // Если это последний элемент в группе, добавляем ему метку
                        if ($index === $group_size - 1) {
                            $member['is_last_duplicate'] = true;
                        }
                    }
                }
                unset($member);
            }

            // Добавляем обработанную группу (или одиночную анкету) в финальный массив
            $processed_clients = array_merge($processed_clients, $current_group);
        }
    } else {
        $processed_clients = $clients;
    }

    $pdo = null;

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    exit('Ошибка при загрузке данных. Попробуйте позже.');
}

$page_title = 'Анкеты: ' . $country_name . ' - ' . $center_name;
require_once SYSTEM . '/layouts/head.php';
?>


<body>
    <!-- Begin page -->
    <div class="wrapper">

        <?php require_once SYSTEM . '/layouts/menu.php'; ?>

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="/?page=dashboard"><i
                                                    class="uil-home-alt me-1"></i> Главная</a></li>
                                        <li class="breadcrumb-item active">Анкеты</li>
                                    </ol>
                                </div>
                                <h4 class="page-title"><?= $page_title ?></h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-sm-5">
                                            <?php if ($user_data['user_group'] != 2 && $can_create_client): // Все, кроме Руководителя, могут добавлять анкеты, если ВЦ и страна активны ?>
                                                <a href="/?page=new-client&center=<?= $center_id ?>"
                                                    class="btn btn-success"><i class="mdi mdi-plus-circle me-2"></i>
                                                    Добавить анкету</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-sm-7">
                                            <div class="text-sm-end">
                                                <div class="dropdown btn-group">
                                                    <button class="btn btn-light mb-2 dropdown-toggle" type="button"
                                                        data-bs-toggle="dropdown" aria-haspopup="true"
                                                        aria-expanded="false">Действия</button>
                                                    <div class="dropdown-menu dropdown-menu-animated">
                                                        <?php
                                                        $user_group = (int) $user_data['user_group'];
                                                        switch ((int) $current_status) {
                                                            case 1: // В работе
                                                                if ($user_group === 1) {
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'confirm\')">Записать</a>';
                                                                }
                                                                if (in_array($user_group, [1, 3, 4])) {
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'archive\')">В архив</a>';
                                                                }
                                                                break;
                                                            case 2: // Записанные
                                                                if ($user_group === 1) {
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'archive\')">В архив</a>';
                                                                }
                                                                break;
                                                            case 3: // Черновики
                                                                if ($user_group === 1) {
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'approve_draft\')">Одобрить</a>';
                                                                }
                                                                if (in_array($user_group, [3, 4])) {
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'review\')">На рассмотрение</a>';
                                                                }
                                                                if (in_array($user_group, [1, 3, 4])) {
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'archive\')">В архив</a>';
                                                                }
                                                                break;
                                                            case 4: // Архив
                                                                if (in_array($user_group, [1, 3, 4])) {
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'restore\')">Восстановить</a>';
                                                                }
                                                                break;
                                                            case 5: // На рассмотрении у Директора
                                                                if ($user_group === 1) {
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'approve\')">Одобрить</a>';
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'decline\')">Отклонить</a>';
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'archive\')">В архив</a>';
                                                                }
                                                                break;
                                                            case 6: // На рассмотрении у Менеджера
                                                                if ($user_group === 3) {
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'approve_manager\')">Одобрить</a>';
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'decline\')">Отклонить</a>';
                                                                    echo '<a class="dropdown-item" href="#" onclick="handleMassAction(\'archive\')">В архив</a>';
                                                                }
                                                                break;
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div><!-- end col-->
                                    </div>

                                    <!-- Nav tabs -->
                                    <ul class="nav nav-tabs nav-bordered mb-3">
                                        <?php if (in_array(2, $allowed_statuses)): ?>
                                            <li class="nav-item">
                                                <a href="/?page=clients&center=<?= $center_id ?>&status=2"
                                                    class="nav-link <?= ($current_status == 2) ? 'active' : '' ?>">
                                                    Записанные <span class="badge bg-success ms-1"><?= $counts[2] ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (in_array(1, $allowed_statuses)): ?>
                                            <li class="nav-item">
                                                <a href="/?page=clients&center=<?= $center_id ?>&status=1"
                                                    class="nav-link <?= ($current_status == 1) ? 'active' : '' ?>">
                                                    В работе <span class="badge bg-primary ms-1"><?= $counts[1] ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (in_array(5, $allowed_statuses)): // Только Директор и Менеджер видят эту вкладку ?>
                                            <li class="nav-item">
                                                <?php $review_count = ($user_data['user_group'] == 3) ? $counts[6] : $counts[5]; ?>
                                                <a href="/?page=clients&center=<?= $center_id ?>&status=5"
                                                    class="nav-link <?= ($current_status == 5 || ($current_status == 6 && $user_data['user_group'] != 1)) ? 'active' : '' ?>">
                                                    На рассмотрении <span
                                                        class="badge bg-info ms-1"><?= $review_count ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (in_array(3, $allowed_statuses)): ?>
                                            <li class="nav-item">
                                                <a href="/?page=clients&center=<?= $center_id ?>&status=3"
                                                    class="nav-link <?= ($current_status == 3) ? 'active' : '' ?>">
                                                    Черновики <span class="badge bg-warning ms-1"><?= $counts[3] ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (in_array(4, $allowed_statuses)): ?>
                                            <li class="nav-item">
                                                <a href="/?page=clients&center=<?= $center_id ?>&status=4"
                                                    class="nav-link <?= ($current_status == 4) ? 'active' : '' ?>">
                                                    Архив <span class="badge bg-danger ms-1"><?= $counts[4] ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>

                                    <div class="table-responsive">
                                        <table class="table table-centered dt-responsive nowrap w-100"
                                            id="clients-datatable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 20px;">
                                                        <div class="form-check">
                                                            <input type="checkbox" class="form-check-input"
                                                                id="customCheck0">
                                                            <label class="form-check-label"
                                                                for="customCheck0">&nbsp;</label>
                                                        </div>
                                                    </th>
                                                    <th>ID</th>
                                                    <th>ФИО</th>
                                                    <th>Телефон</th>
                                                    <th>Номер паспорта</th>
                                                    <th>Города</th>
                                                    <th>Категории</th>
                                                    <?php if (in_array($user_data['user_group'], [1, 2])): ?>
                                                        <th>Менеджер</th>
                                                        <th>Агент</th>
                                                    <?php elseif ($user_data['user_group'] == 3): ?>
                                                        <th>Агент</th>
                                                    <?php endif; ?>
                                                    <th>Стоимость</th>
                                                    <th style="width: 120px;">Действия</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($processed_clients):
                                                    foreach ($processed_clients as $client): ?>
                                                        <?php
                                                        $row_classes = '';
                                                        $span_classes = '';

                                                        if (isset($client['is_main_duplicate'])) {
                                                            $row_classes .= ' table-info-light main-duplicate-row';
                                                        }
                                                        if (isset($client['is_duplicate'])) {
                                                            $row_classes .= ' table-info-light';
                                                            $span_classes .= ' duplicate-marker';
                                                            if (isset($client['is_last_duplicate'])) {
                                                                $row_classes .= ' is-last-duplicate'; // Класс для TR
                                                                $span_classes .= ' is-last-duplicate'; // Класс для SPAN
                                                            }
                                                        }

                                                        // Определяем базовые значения для сортировки (из главной анкеты для дублей, или из своей для одиночных)
                                                        $base_group_id = $client['sort_group_id'] ?? $client['client_id'];
                                                        $base_in_group_order = $client['sort_in_group_order'] ?? 0;

                                                        $base_client_name = valid($client['sort_client_name'] ?? trim($client['last_name'] . ' ' . $client['first_name'] . ' ' . $client['middle_name']));
                                                        $base_phone = valid($client['sort_phone'] ?? $client['phone']);
                                                        $base_passport = valid($client['sort_passport'] ?? $client['passport_number']);
                                                        $base_cities = valid($client['sort_cities'] ?? $client['client_cities_list']);
                                                        $base_categories = valid($client['sort_categories'] ?? $client['client_categories_list']);
                                                        $base_manager = valid($client['sort_manager'] ?? (($client['manager_firstname'] ?? '') . ' ' . ($client['manager_lastname'] ?? '')));
                                                        $base_agent = valid($client['sort_agent'] ?? (($client['agent_firstname'] ?? '') . ' ' . ($client['agent_lastname'] ?? '')));
                                                        $base_price = $client['sort_price'] ?? (float) $client['sale_price'];

                                                        // Создаем ключ для стабильной сортировки, который гарантирует порядок внутри группы
                                                        $stable_sort_key = sprintf('%011d', $base_group_id) . '-' . sprintf('%03d', $base_in_group_order);
                                                        ?>
                                                        <tr class="<?= trim($row_classes) ?>">
                                                            <td>
                                                                <div class="form-check">
                                                                    <input type="checkbox" class="form-check-input"
                                                                        id="customCheck<?= $client['client_id'] ?>">
                                                                    <label class="form-check-label"
                                                                        for="customCheck<?= $client['client_id'] ?>">&nbsp;</label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span style="display:none;"><?= $stable_sort_key ?></span>
                                                                <span
                                                                    class="<?= trim($span_classes) ?>"><?= $client['client_id'] ?></span>
                                                            </td>
                                                            <td><span
                                                                    style="display:none;"><?= $base_client_name ?></span><?= valid(trim($client['last_name'] . ' ' . $client['first_name'] . ' ' . $client['middle_name'])) ?>
                                                            </td>
                                                            <td><span
                                                                    style="display:none;"><?= $base_phone ?></span><?= valid($client['phone']) ?>
                                                            </td>
                                                            <td><span
                                                                    style="display:none;"><?= $base_passport ?></span><?= valid($client['passport_number']) ?>
                                                            </td>
                                                            <td><span
                                                                    style="display:none;"><?= $base_cities ?></span><?= valid($client['client_cities_list']) ?>
                                                            </td>
                                                            <td><span
                                                                    style="display:none;"><?= $base_categories ?></span><?= valid($client['client_categories_list']) ?>
                                                            </td>
                                                            <?php if (in_array($user_data['user_group'], [1, 2])): ?>
                                                                <td><span
                                                                        style="display:none;"><?= $base_manager ?></span><?= valid(($client['manager_firstname'] ?? '') . ' ' . ($client['manager_lastname'] ?? '')) ?>
                                                                </td>
                                                                <td><span
                                                                        style="display:none;"><?= $base_agent ?></span><?= valid(($client['agent_firstname'] ?? '') . ' ' . ($client['agent_lastname'] ?? '')) ?>
                                                                </td>
                                                            <?php elseif ($user_data['user_group'] == 3): ?>
                                                                <td><span
                                                                        style="display:none;"><?= $base_agent ?></span><?= valid(($client['agent_firstname'] ?? '') . ' ' . ($client['agent_lastname'] ?? '')) ?>
                                                                </td>
                                                            <?php endif; ?>
                                                            <td>
                                                                <span
                                                                    style="display:none;"><?= sprintf('%020.2f', $base_price) ?></span>
                                                                <?php if (!empty($client['sale_price'])): ?>
                                                                    <span class="text-success fw-semibold">
                                                                        <i
                                                                            class="mdi mdi-currency-usd"></i><?= number_format($client['sale_price'], 2, '.', ' ') ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $client_status = (int) $client['client_status'];
                                                                $user_group = (int) $user_data['user_group'];
                                                                $client_name_js = htmlspecialchars(valid(trim($client['last_name'] . ' ' . $client['first_name'])), ENT_QUOTES);

                                                                switch ($client_status) {
                                                                    case 1: // В работе
                                                                        echo '<a href="/?page=edit-client&id=' . $client['client_id'] . '" class="font-18 text-info me-2" title="Редактировать"><i class="uil uil-pen"></i></a>';
                                                                        if ($user_group === 1) { // Только Директор
                                                                            echo '<a href="#" class="font-18 text-success me-2" onclick="sendConfirmClientForm(' . $client['client_id'] . ')" title="Записать"><i class="uil uil-check-circle"></i></a>';
                                                                        }
                                                                        if (in_array($user_group, [1, 3, 4])) { // Директор, Менеджер, Агент
                                                                            echo '<a href="#" class="font-18 text-danger" title="В архив" onclick="modalDelClientForm(' . $client['client_id'] . ', \'' . $client_name_js . '\')"><i class="uil uil-trash"></i></a>';
                                                                        }
                                                                        break;

                                                                    case 2: // Записанные
                                                                        echo '<a href="/?page=edit-client&id=' . $client['client_id'] . '" class="font-18 text-info me-2" title="Просмотр"><i class="uil uil-eye"></i></a>';
                                                                        if ($user_group === 1) { // Только Директор
                                                                            echo '<a href="#" class="font-18 text-danger" title="В архив" onclick="modalDelClientForm(' . $client['client_id'] . ', \'' . $client_name_js . '\')"><i class="uil uil-trash"></i></a>';
                                                                        }
                                                                        break;

                                                                    case 3: // Черновики
                                                                        if (!empty($client['rejection_reason'])) {
                                                                            echo '<a href="#" class="font-18 text-danger me-2" onclick="showRejectionReason(\'' . htmlspecialchars(valid($client['rejection_reason']), ENT_QUOTES) . '\')" title="Причина отказа"><i class="uil uil-comment-info"></i></a>';
                                                                            if (in_array($user_group, [3, 4])) { // Менеджер и Агент
                                                                                echo '<a href="#" class="font-18 text-warning me-2" onclick="sendRevertRejectionForm(' . $client['client_id'] . ')" title="Вернуть в работу"><i class="uil uil-redo"></i></a>';
                                                                            }
                                                                        } else {
                                                                            echo '<a href="/?page=edit-client&id=' . $client['client_id'] . '" class="font-18 text-info me-2" title="Редактировать"><i class="uil uil-pen"></i></a>';
                                                                            if ($user_group === 1 && $client['agent_group'] == 1) { // Директор одобряет свой черновик
                                                                                echo '<a href="#" class="font-18 text-success me-2" onclick="sendApproveDraftDirectorForm(' . $client['client_id'] . ')" title="Одобрить"><i class="uil uil-check-circle"></i></a>';
                                                                            } elseif (in_array($user_group, [3, 4]) || ($user_group === 1 && $client['agent_group'] != 1)) { // Менеджер/Агент/Директор отправляют чужой черновик
                                                                                echo '<a href="#" class="font-18 text-primary me-2" onclick="sendReviewClientForm(' . $client['client_id'] . ')" title="На рассмотрение"><i class="uil uil-message"></i></a>';
                                                                            }
                                                                        }
                                                                        if (in_array($user_group, [1, 3, 4])) {
                                                                            echo '<a href="#" class="font-18 text-danger" title="В архив" onclick="modalDelClientForm(' . $client['client_id'] . ', \'' . $client_name_js . '\')"><i class="uil uil-trash"></i></a>';
                                                                        }
                                                                        break;

                                                                    case 4: // Архив
                                                                        if (in_array($user_group, [1, 3, 4])) {
                                                                            echo '<a href="#" class="font-18 text-warning" onclick="sendRestoreClientForm(' . $client['client_id'] . ')" title="Восстановить"><i class="mdi mdi-cached"></i></a>';
                                                                        }
                                                                        break;

                                                                    case 5: // На рассмотрении у Директора
                                                                        echo '<a href="/?page=edit-client&id=' . $client['client_id'] . '" class="font-18 text-info me-2" title="Просмотр"><i class="uil uil-eye"></i></a>';
                                                                        if ($user_group === 1) {
                                                                            echo '<a href="#" class="font-18 text-success me-2" onclick="sendApproveClientForm(' . $client['client_id'] . ')" title="Одобрить"><i class="uil uil-check-circle"></i></a>';
                                                                            echo '<a href="#" class="font-18 text-danger me-2" onclick="modalDeclineClientForm(' . $client['client_id'] . ')" title="Отклонить"><i class="uil uil-times-circle"></i></a>';
                                                                            echo '<a href="#" class="font-18 text-danger" title="В архив" onclick="modalDelClientForm(' . $client['client_id'] . ', \'' . $client_name_js . '\')"><i class="uil uil-trash"></i></a>';
                                                                        }
                                                                        break;

                                                                    case 6: // На рассмотрении у Менеджера
                                                                        echo '<a href="/?page=edit-client&id=' . $client['client_id'] . '" class="font-18 text-info me-2" title="Просмотр"><i class="uil uil-eye"></i></a>';
                                                                        if ($user_group === 3) {
                                                                            echo '<a href="#" class="font-18 text-success me-2" onclick="sendApproveClientManagerForm(' . $client['client_id'] . ')" title="Одобрить"><i class="uil uil-check-circle"></i></a>';
                                                                            echo '<a href="#" class="font-18 text-danger me-2" onclick="modalDeclineClientForm(' . $client['client_id'] . ')" title="Отклонить"><i class="uil uil-times-circle"></i></a>';
                                                                            echo '<a href="#" class="font-18 text-danger" title="В архив" onclick="modalDelClientForm(' . $client['client_id'] . ', \'' . $client_name_js . '\')"><i class="uil uil-trash"></i></a>';
                                                                        }
                                                                        break;
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div> <!-- end card-body-->
                            </div> <!-- end card-->
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->

                </div>
                <!-- container -->

            </div>
            <!-- content -->

            <?php require_once SYSTEM . '/layouts/footer.php'; ?>

        </div>

    </div>
    <!-- END wrapper -->

    <div id="del-client-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content modal-filled bg-danger">
                <div class="modal-body p-4">
                    <div class="text-center">
                        <i class="ri-delete-bin-5-line h1"></i>
                        <h4 class="mt-2">Отправка в Архив</h4>
                        <p class="mt-3">Отправить в архив анкету <span class="span-client-name"></span>?</p>
                        <button type="button" class="btn btn-light my-2" data-bs-dismiss="modal">Отправить</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Decline Client -->
    <div id="modal-decline-client" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="modal-decline-client-title" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-decline-client-title">Отклонить анкету</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-decline-client">
                        <input type="hidden" name="client-id" id="decline-client-id">
                        <div class="mb-3">
                            <label for="rejection-reason" class="form-label">Причина отклонения (необязательно)</label>
                            <textarea class="form-control" id="rejection-reason" name="rejection-reason"
                                rows="3"></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                            <button type="button" class="btn btn-danger"
                                onclick="sendDeclineClientForm()">Отклонить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div><!-- /.modal -->

    <!-- Modal Confirm Incomplete Draft Approval -->
    <div id="modal-confirm-incomplete" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body p-4">
                    <div class="text-center">
                        <i class="ri-information-line h1 text-warning"></i>
                        <h4 class="mt-2">Анкета не заполнена</h4>
                        <p class="mt-3">Вы уверены, что хотите одобрить неполностью заполненную анкету?</p>
                        <button type="button" class="btn btn-light my-2" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-warning my-2" id="confirm-incomplete-btn">Да,
                            одобрить</button>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.modal -->

    <!-- Modal Confirm Final Category -->
    <div id="modal-final-category" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="modal-final-category-title" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-final-category-title">Выбор финальной категории</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-final-category">
                        <input type="hidden" name="client-id" id="final-category-client-id">
                        <p>Пожалуйста, выберите одну категорию, по которой клиент был записан. Остальные будут удалены.
                        </p>
                        <div id="final-category-list" class="mb-3">
                            <!-- Сюда будут загружаться радиокнопки -->
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                            <button type="button" class="btn btn-success"
                                onclick="sendFinalCategoryForm()">Записать</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div><!-- /.modal -->

    <?php require_once SYSTEM . '/layouts/scripts.php'; ?>

    <script>
        const userGroup = <?= $user_data['user_group'] ?>;

        function createAjaxRequest(formAction, clientId) {
            $.ajax({
                url: '/?form=' + formAction,
                type: 'POST',
                dataType: 'json',
                data: { 'client-id': clientId },
                success: function (response) {
                    message(response.msg_title, response.msg_text, response.msg_type, 'reload');
                },
                error: function () {
                    message('Ошибка', 'Ошибка отправки формы!', 'error');
                }
            });
        }

        function modalDelClientForm(clientId, clientName) {
            $('#del-client-modal .span-client-name').text(clientName);
            $('#del-client-modal button').off('click').on('click', function () {
                createAjaxRequest('del-client', clientId);
                $('#del-client-modal').modal('hide');
            });
        }

        function sendConfirmClientForm(clientId) {
            // Загружаем категории для анкеты
            $.ajax({
                url: '/?form=get-client-categories',
                type: 'POST',
                dataType: 'json',
                data: { 'client-id': clientId },
                success: function (categories) {
                    const listContainer = $('#final-category-list');
                    listContainer.empty();

                    if (categories && categories.length > 0) {
                        $('#final-category-client-id').val(clientId);

                        categories.forEach(function (cat, index) {
                            const radioId = `final-cat-${cat.city_id}`;
                            const isChecked = index === 0 ? 'checked' : '';
                            listContainer.append(
                                `<div class="form-check">
                                    <input class="form-check-input" type="radio" name="final-city-id" id="${radioId}" value="${cat.city_id}" ${isChecked}>
                                    <label class="form-check-label" for="${radioId}">
                                        ${cat.city_name} – ${cat.city_category}
                                    </label>
                                </div>`
                            );
                        });

                        $('#modal-final-category').modal('show');
                    } else if (categories.length === 0) {
                        message('Ошибка', 'У анкеты нет категорий. Сначала добавьте их в режиме редактирования.', 'error');
                    } else {
                        message('Ошибка', 'Не удалось загрузить список категорий.', 'error');
                    }
                },
                error: function () {
                    message('Ошибка', 'Произошла ошибка при запросе категорий.', 'error');
                }
            });
        }

        function sendFinalCategoryForm() {
            const clientId = $('#final-category-client-id').val();
            const finalCityId = $('input[name="final-city-id"]:checked').val();

            if (!finalCityId) {
                message('Внимание', 'Пожалуйста, выберите одну категорию.', 'warning');
                return;
            }

            $.ajax({
                url: '/?form=confirm-client',
                type: 'POST',
                dataType: 'json',
                data: {
                    'client-id': clientId,
                    'final-city-id': finalCityId
                },
                success: function (response) {
                    $('#modal-final-category').modal('hide');
                    message(response.msg_title, response.msg_text, response.msg_type, 'reload');
                },
                error: function () {
                    $('#modal-final-category').modal('hide');
                    message('Ошибка', 'Произошла ошибка при отправке формы.', 'error');
                }
            });
        }
        function sendRestoreClientForm(clientId) { createAjaxRequest('restore-client', clientId); }
        function sendApproveClientForm(clientId) { createAjaxRequest('approve-client', clientId); }
        function sendApproveClientManagerForm(clientId) { createAjaxRequest('approve-client-manager', clientId); }
        function sendApproveDraftDirectorForm(clientId) {
            $.ajax({
                url: '/?form=check-client-completeness',
                type: 'POST',
                dataType: 'json',
                data: { 'client-id': clientId },
                success: function (response) {
                    if (response.is_complete) {
                        // Если анкета полная, одобряем сразу
                        createAjaxRequest('approve-draft-director', clientId);
                    } else {
                        // Если анкета неполная, показываем модальное окно
                        $('#modal-confirm-incomplete').modal('show');
                        // Привязываем действие к кнопке подтверждения
                        $('#confirm-incomplete-btn').off('click').on('click', function () {
                            $('#modal-confirm-incomplete').modal('hide');
                            createAjaxRequest('approve-draft-director', clientId);
                        });
                    }
                },
                error: function () {
                    message('Ошибка', 'Не удалось проверить анкету. Попробуйте позже.', 'error');
                }
            });
        }
        function sendRevertRejectionForm(clientId) { createAjaxRequest('revert-rejection-client', clientId); }

        function sendReviewClientForm(clientId) {
            $.ajax({
                url: '/?form=check-client-completeness',
                type: 'POST',
                dataType: 'json',
                data: { 'client-id': clientId },
                success: function (response) {
                    if (response.is_complete) {
                        createAjaxRequest('review-client', clientId);
                    } else {
                        message('Внимание', 'Анкета заполнена не полностью. Пожалуйста, внесите все данные перед отправкой на рассмотрение.', 'warning');
                    }
                },
                error: function () {
                    message('Ошибка', 'Не удалось проверить анкету. Попробуйте позже.', 'error');
                }
            });
        }

        function modalDeclineClientForm(clientId) {
            $('#decline-client-id').val(clientId);
            $('#rejection-reason').val('');
            $('#modal-decline-client').modal('show');
        }

        function sendDeclineClientForm() {
            const formData = $('#form-decline-client').serialize();
            $.ajax({
                url: '/?form=decline-client',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    $('#modal-decline-client').modal('hide');
                    message(response.msg_title, response.msg_text, response.msg_type, 'reload');
                },
                error: function () {
                    $('#modal-decline-client').modal('hide');
                    message('Ошибка', 'Произошла ошибка при отправке запроса.', 'error');
                }
            });
        }

        function showRejectionReason(reason) {
            Swal.fire({ title: 'Причина отказа', text: reason, icon: 'info' });
        }

        $(document).ready(function () {
            $('#clients-datatable').on('click', 'a[title="В архив"]', function (e) {
                e.preventDefault();
                let row = $(this).closest('tr');
                let clientId = row.find('td:first').text().trim();
                let clientName = row.find('td:nth-child(2)').text().trim().split('\n')[0];
                modalDelClientForm(clientId, clientName);
                $('#del-client-modal').modal('show');
            });
        });
    </script>
</body>

</html>