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

switch($user_data['user_group']) {
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
    switch($user_data['user_group']) {
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
            GROUP_CONCAT(DISTINCT CONCAT(sc.city_name, ' – ', sc.city_category) SEPARATOR '<br>') as client_categories
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
            $group_key = !empty($passport) ? $passport . '_' . $client['client_category_ids'] : '__unique__' . $client['client_id'];
            $groups[$group_key][] = $client;
        }

        // Шаг 2: Формируем финальный список, сохраняя порядок
        $processed_clients = [];
        $handled_groups = [];

        foreach ($clients as $client) {
            $passport = trim($client['passport_number']);
            $group_key = !empty($passport) ? $passport . '_' . $client['client_category_ids'] : '__unique__' . $client['client_id'];

            if (in_array($group_key, $handled_groups)) {
                continue;
            }

            $current_group = $groups[$group_key];
            
            if (count($current_group) > 1) {
                // Если есть дубли, сортируем группу, чтобы найти "главную"
                usort($current_group, function($a, $b) {
                    if ((float)$a['sale_price'] != (float)$b['sale_price']) {
                        return (float)$b['sale_price'] <=> (float)$a['sale_price'];
                    }
                    return $a['client_id'] <=> $b['client_id'];
                });

                // Помечаем главную и дубли
                foreach ($current_group as $index => &$member) {
                    $member['sort_id'] = $current_group[0]['client_id']; // ID для сортировки JS
                    if ($index === 0) {
                        $member['is_main_duplicate'] = true;
                    } else {
                        $member['is_duplicate'] = true;
                    }
                }
                unset($member);
            }

            $processed_clients = array_merge($processed_clients, $current_group);
            $handled_groups[] = $group_key;
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
                                        <li class="breadcrumb-item"><a href="/?page=dashboard"><i class="uil-home-alt me-1"></i> Главная</a></li>
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
                                        <div class="col-sm-4">
                                            <?php if ($user_data['user_group'] != 2 && $can_create_client): // Все, кроме Руководителя, могут добавлять анкеты, если ВЦ и страна активны ?>
                                            <a href="/?page=new-client&center=<?= $center_id ?>" class="btn btn-success"><i class="mdi mdi-plus-circle me-2"></i> Добавить анкету</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Nav tabs -->
                                    <ul class="nav nav-tabs nav-bordered mb-3">
                                        <?php if (in_array(2, $allowed_statuses)): ?>
                                        <li class="nav-item">
                                            <a href="/?page=clients&center=<?= $center_id ?>&status=2" class="nav-link <?= ($current_status == 2) ? 'active' : '' ?>">
                                                Записанные <span class="badge bg-success ms-1"><?= $counts[2] ?></span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array(1, $allowed_statuses)): ?>
                                        <li class="nav-item">
                                            <a href="/?page=clients&center=<?= $center_id ?>&status=1" class="nav-link <?= ($current_status == 1) ? 'active' : '' ?>">
                                                В работе <span class="badge bg-primary ms-1"><?= $counts[1] ?></span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if(in_array(5, $allowed_statuses)): // Только Директор и Менеджер видят эту вкладку ?>
                                        <li class="nav-item">
                                            <?php $review_count = ($user_data['user_group'] == 3) ? $counts[6] : $counts[5]; ?>
                                            <a href="/?page=clients&center=<?= $center_id ?>&status=5" class="nav-link <?= ($current_status == 5 || ($current_status == 6 && $user_data['user_group'] != 1)) ? 'active' : '' ?>">
                                                На рассмотрении <span class="badge bg-info ms-1"><?= $review_count ?></span>
                                            </a>
                                        </li>
                                        <?php endif; ?>

                                        <?php if (in_array(3, $allowed_statuses)): ?>
                                        <li class="nav-item">
                                            <a href="/?page=clients&center=<?= $center_id ?>&status=3" class="nav-link <?= ($current_status == 3) ? 'active' : '' ?>">
                                                Черновики <span class="badge bg-warning ms-1"><?= $counts[3] ?></span>
                                            </a>
                                        </li>
                                        <?php endif; ?>

                                        <?php if (in_array(4, $allowed_statuses)): ?>
                                        <li class="nav-item">
                                            <a href="/?page=clients&center=<?= $center_id ?>&status=4" class="nav-link <?= ($current_status == 4) ? 'active' : '' ?>">
                                                Архив <span class="badge bg-danger ms-1"><?= $counts[4] ?></span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>

                                    <div class="table-responsive">
                                        <table class="table table-centered table-striped dt-responsive nowrap w-100" id="clients-datatable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>ФИО</th>
                                                    <th>Телефон</th>
                                                    <th>Номер паспорта</th>
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
                                                <?php if ($processed_clients): foreach ($processed_clients as $client): ?>
                                                <tr>
                                                    <td>
                                                        <span style="display:none;"><?= $client['sort_id'] ?? $client['client_id'] ?></span>
                                                        <?php if (isset($client['is_duplicate']) && $client['is_duplicate']): ?>
                                                            <span class="duplicate-marker"><?= $client['client_id'] ?></span>
                                                        <?php else: ?>
                                                            <?= $client['client_id'] ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= valid(trim($client['last_name'] . ' ' . $client['first_name'] . ' ' . $client['middle_name'])) ?></td>
                                                    <td><?= valid($client['phone']) ?></td>
                                                    <td><?= valid($client['passport_number']) ?></td>
                                                    <?php if (in_array($user_data['user_group'], [1, 2])): ?>
                                                        <td><?= valid(($client['manager_firstname'] ?? '') . ' ' . ($client['manager_lastname'] ?? '')) ?></td>
                                                        <td><?= valid(($client['agent_firstname'] ?? '') . ' ' . ($client['agent_lastname'] ?? '')) ?></td>
                                                    <?php elseif ($user_data['user_group'] == 3): ?>
                                                        <td><?= valid(($client['agent_firstname'] ?? '') . ' ' . ($client['agent_lastname'] ?? '')) ?></td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <?php if (!empty($client['sale_price'])): ?>
                                                            <span class="text-success fw-semibold">
                                                                <i class="mdi mdi-currency-usd"></i><?= number_format($client['sale_price'], 2, '.', ' ') ?>
                                                            </span>
                                                        <?php else: ?>
                                                            
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php switch((int)$client['client_status']):
                                                            
                                                            case 1: // В работе
                                                            case 2: // Записанные
                                                                if ($user_data['user_group'] == 1) { // Директор - полный доступ
                                                                    if ($client['client_status'] == 1) echo '<a href="#" class="font-18 text-success me-2" title="Записать" onclick="sendConfirmClientForm(\''.$client['client_id'].'\')"><i class="mdi mdi-check-circle-outline"></i></a>';
                                                                    echo '<a href="/?page=edit-client&id='.$client['client_id'].'" class="font-18 text-info me-2" title="Просмотр/редактирование"><i class="uil uil-pen"></i></a>';
                                                                    echo '<a href="#" class="font-18 text-danger" title="В архив" onclick="modalDelClientForm('.$client['client_id'].', \''.valid($client['client_name']).'\')"><i class="uil uil-trash"></i></a>';
                                                                } else { // Остальные - только просмотр
                                                                    echo '<a href="/?page=edit-client&id='.$client['client_id'].'" class="font-18 text-info me-2" title="Просмотр"><i class="mdi mdi-eye-outline"></i></a>';
                                                                }
                                                                break;

                                                            case 3: // Черновик
                                                                if (!empty($client['rejection_reason'])) { // Отклоненный черновик
                                                                    echo '<a href="#" class="font-18 text-danger me-2" title="Посмотреть причину отказа" onclick="showRejectionReason(\''.htmlspecialchars(valid($client['rejection_reason']), ENT_QUOTES).'\')"><i class="mdi mdi-alert-circle-outline"></i></a>';
                                                                    echo '<a href="#" class="font-18 text-warning me-2" title="Вернуть для редактирования" onclick="sendRevertRejectionForm(\''.$client['client_id'].'\')"><i class="mdi mdi-pencil-off-outline"></i></a>';
                                                                    echo '<a href="#" class="font-18 text-danger" title="В архив" onclick="modalDelClientForm('.$client['client_id'].', \''.valid($client['client_name']).'\')"><i class="uil uil-trash"></i></a>';
                                                                } else { // Обычный черновик
                                                                    if ($user_data['user_group'] == 1) { // Директор
                                                                        echo '<a href="#" class="font-18 text-success me-2" title="Одобрить" onclick="sendApproveDraftDirectorForm(\''.$client['client_id'].'\')"><i class="mdi mdi-check-decagram"></i></a>';
                                                                    } else { // Агент или Менеджер
                                                                        echo '<a href="#" class="font-18 text-primary me-2" title="На рассмотрение" onclick="sendReviewClientForm(\''.$client['client_id'].'\')"><i class="mdi mdi-gavel"></i></a>';
                                                                    }
                                                                    echo '<a href="/?page=edit-client&id='.$client['client_id'].'" class="font-18 text-info me-2" title="Редактировать"><i class="uil uil-pen"></i></a>';
                                                                    echo '<a href="#" class="font-18 text-danger" title="В архив" onclick="modalDelClientForm('.$client['client_id'].', \''.valid($client['client_name']).'\')"><i class="uil uil-trash"></i></a>';
                                                                }
                                                                break;

                                                            case 4: // Архив
                                                                if ($user_data['user_group'] != 2) { // Руководитель не может восстанавливать
                                                                    echo '<a href="#" class="font-18 text-warning" title="Восстановить" onclick="sendRestoreClientForm(\''.$client['client_id'].'\')"><i class="mdi mdi-cached"></i></a>';
                                                                }
                                                                break;

                                                            case 5: // На рассмотрении у директора
                                                                if ($user_data['user_group'] == 1) { // Только Директор
                                                                    echo '<a href="#" class="font-18 text-success me-2" title="Одобрить" onclick="sendApproveClientForm(\''.$client['client_id'].'\')"><i class="mdi mdi-check-decagram"></i></a>';
                                                                    echo '<a href="#" class="font-18 text-danger me-2" title="Отклонить" onclick="modalDeclineClientForm(\''.$client['client_id'].'\')"><i class="mdi mdi-close-circle-outline"></i></a>';
                                                                    echo '<a href="/?page=edit-client&id='.$client['client_id'].'" class="font-18 text-info" title="Просмотр/редактирование"><i class="uil uil-pen"></i></a>';
                                                                }
                                                                break;
                                                            
                                                            case 6: // На рассмотрении у менеджера
                                                                if ($user_data['user_group'] == 3) { // Только Менеджер
                                                                    echo '<a href="#" class="font-18 text-success me-2" title="Одобрить" onclick="sendApproveClientManagerForm(\''.$client['client_id'].'\')"><i class="mdi mdi-check-decagram"></i></a>';
                                                                    echo '<a href="#" class="font-18 text-danger me-2" title="Отклонить" onclick="modalDeclineClientForm(\''.$client['client_id'].'\')"><i class="mdi mdi-close-circle-outline"></i></a>';
                                                                    echo '<a href="/?page=edit-client&id='.$client['client_id'].'" class="font-18 text-info" title="Просмотр/редактирование"><i class="uil uil-pen"></i></a>';
                                                                }
                                                                break;
                                                        endswitch; ?>
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
    <div id="modal-decline-client" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modal-decline-client-title" aria-hidden="true">
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
                            <textarea class="form-control" id="rejection-reason" name="rejection-reason" rows="3"></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                            <button type="button" class="btn btn-danger" onclick="sendDeclineClientForm()">Отклонить</button>
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
                        <button type="button" class="btn btn-warning my-2" id="confirm-incomplete-btn">Да, одобрить</button>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.modal -->

    <!-- Modal Confirm Final Category -->
    <div id="modal-final-category" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modal-final-category-title" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-final-category-title">Выбор финальной категории</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-final-category">
                        <input type="hidden" name="client-id" id="final-category-client-id">
                        <p>Пожалуйста, выберите одну категорию, по которой клиент был записан. Остальные будут удалены.</p>
                        <div id="final-category-list" class="mb-3">
                            <!-- Сюда будут загружаться радиокнопки -->
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                            <button type="button" class="btn btn-success" onclick="sendFinalCategoryForm()">Записать</button>
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
                success:  function(response) {
                    message(response.msg_title, response.msg_text, response.msg_type, 'reload');
                },
                error:  function() {
                    message('Ошибка', 'Ошибка отправки формы!', 'error');
                }
            });
        }
        
        function modalDelClientForm(clientId, clientName) {
            $('#del-client-modal .span-client-name').text(clientName);
            $('#del-client-modal button').off('click').on('click', function() {
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
                success: function(categories) {
                    const listContainer = $('#final-category-list');
                    listContainer.empty();
                    
                    if (categories && categories.length > 0) {
                        $('#final-category-client-id').val(clientId);
                        
                        categories.forEach(function(cat, index) {
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
                error: function() {
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
                success: function(response) {
                    $('#modal-final-category').modal('hide');
                    message(response.msg_title, response.msg_text, response.msg_type, 'reload');
                },
                error: function() {
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
                success: function(response) {
                    if (response.is_complete) {
                        // Если анкета полная, одобряем сразу
                        createAjaxRequest('approve-draft-director', clientId);
                    } else {
                        // Если анкета неполная, показываем модальное окно
                        $('#modal-confirm-incomplete').modal('show');
                        // Привязываем действие к кнопке подтверждения
                        $('#confirm-incomplete-btn').off('click').on('click', function() {
                            $('#modal-confirm-incomplete').modal('hide');
                            createAjaxRequest('approve-draft-director', clientId);
                        });
                    }
                },
                error: function() {
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
                success: function(response) {
                    if (response.is_complete) {
                        createAjaxRequest('review-client', clientId);
                    } else {
                        message('Внимание', 'Анкета заполнена не полностью. Пожалуйста, внесите все данные перед отправкой на рассмотрение.', 'warning');
                    }
                },
                error: function() {
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
                success: function(response) {
                    $('#modal-decline-client').modal('hide');
                    message(response.msg_title, response.msg_text, response.msg_type, 'reload');
                },
                error: function() {
                    $('#modal-decline-client').modal('hide');
                    message('Ошибка', 'Произошла ошибка при отправке запроса.', 'error');
                }
            });
        }
        
        function showRejectionReason(reason) {
            Swal.fire({ title: 'Причина отказа', text: reason, icon: 'info' });
        }

        $(document).ready(function() {
            $('#clients-datatable').on('click', 'a[title="В архив"]', function(e) {
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