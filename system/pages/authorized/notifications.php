<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Уведомления';
require_once SYSTEM . '/layouts/head.php';

// Получаем список пользователей для модального окна (только для Директора)
$users_list = [];
if ($user_data['user_group'] == 1) {
    try {
        $pdo = db_connect();
        $stmt_users = $pdo->prepare("
            SELECT user_id, user_firstname, user_lastname, user_group 
            FROM users 
            WHERE user_status = 1 AND user_id != :current_id 
            ORDER BY user_group ASC, user_lastname ASC
        ");
        $stmt_users->execute([':current_id' => $user_data['user_id']]);
        $users_list = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Игнорируем ошибку
    }
}

try {
    $pdo = db_connect();
    // Получаем последние 100 уведомлений текущего пользователя
    $stmt_notif = $pdo->prepare("
        SELECT * FROM `notifications` 
        WHERE `user_id` = :uid 
        ORDER BY `id` DESC 
        LIMIT 100
    ");
    $stmt_notif->execute([':uid' => $user_data['user_id']]);
    $notifications = $stmt_notif->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    $notifications = [];
}
?>

<body>
    <!-- Begin page -->
    <div class="wrapper">

        <?php require_once SYSTEM . '/layouts/menu.php'; ?>

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

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
                                        <li class="breadcrumb-item active">Уведомления</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Ваши уведомления</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <!-- Панель инструментов -->
                                    <div class="row mb-2">
                                        <div class="col-xl-8">
                                            <form class="row gy-2 gx-2 align-items-center justify-content-xl-start justify-content-between">
                                                <div class="col-auto">
                                                    <label for="inputPassword2" class="visually-hidden">Поиск</label>
                                                    <input type="search" class="form-control" id="inputPassword2" placeholder="Поиск...">
                                                </div>
                                                <div class="col-auto">
                                                    <div class="d-flex align-items-center">
                                                        <label for="status-select" class="me-2">Показать</label>
                                                        <select class="form-select" id="status-select">
                                                            <option selected>Все</option>
                                                            <option value="1">Непрочитанные</option>
                                                            <option value="2">Прочитанные</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </form>                            
                                        </div>
                                        <div class="col-xl-4">
                                            <div class="text-xl-end mt-xl-0 mt-2">
                                                <!-- Кнопка "Отметить все" с классом btn-mark-all -->
                                                <button type="button" class="btn btn-light mb-2 me-2 btn-mark-all">Отметить все как прочитанные</button>
                                                
                                                <?php if ($user_data['user_group'] == 1): ?>
                                                    <!-- Кнопка создания с вызовом модалки -->
                                                    <button type="button" class="btn btn-success mb-2" data-bs-toggle="modal" data-bs-target="#modal-create-notification">
                                                        <i class="mdi mdi-plus-circle me-2"></i>Создать уведомление
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div><!-- end col-->
                                    </div>

                                    <!-- Таблица уведомлений -->
                                    <div class="table-responsive">
                                        <table class="table table-centered table-hover mb-0" id="notifications-datatable">
                                            <thead>
                                                <tr>
                                                    <!-- Убрали столбец с чекбоксами -->
                                                    <th style="width: 50px;" class="text-center">Тип</th>
                                                    <th>Тема</th>
                                                    <th>Сообщение</th>
                                                    <th style="width: 150px;">Дата</th>
                                                    <th style="width: 75px;">Действия</th>
                                                </tr>
                                            </thead>
                                            <tbody id="notifications-table-body">
                                                <?php if ($notifications): ?>
                                                    <?php foreach ($notifications as $item): 
                                                        [$icon, $color] = match ($item['type']) {
                                                            'success' => ['mdi-check-circle-outline', 'success'],
                                                            'danger'  => ['mdi-alert-circle-outline', 'danger'],
                                                            'warning' => ['mdi-alert-outline', 'warning'],
                                                            default   => ['mdi-information-outline', 'info'],
                                                        };
                                                        
                                                        $font_weight = $item['is_read'] == 0 ? 'fw-bold text-dark' : 'fw-normal text-muted';
                                                    ?>
                                                        <tr class="align-middle">
                                                            <!-- Убрали ячейку с чекбоксом -->
                                                            <td class="text-center">
                                                                <div class="avatar-sm d-inline-block">
                                                                    <span class="avatar-title bg-<?= $color ?>-lighten text-<?= $color ?> rounded-circle font-20">
                                                                        <i class="mdi <?= $icon ?>"></i>
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="<?= $font_weight ?>"><?= valid($item['title']) ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="<?= $item['is_read'] == 0 ? 'text-body' : 'text-muted' ?>">
                                                                    <?= valid($item['message']) ?>
                                                                </span>
                                                                <?php if (!empty($item['link']) && $item['link'] !== '#'): ?>
                                                                    <a href="<?= $item['link'] ?>" class="d-block font-12 mt-1 notification-link" data-id="<?= $item['id'] ?>">
                                                                        Посмотреть детали <i class="mdi mdi-arrow-right"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="font-13"><?= date('d.m.Y', strtotime($item['created_at'])) ?></span>
                                                                <br>
                                                                <small class="text-muted"><?= date('H:i', strtotime($item['created_at'])) ?></small>
                                                            </td>
                                                            <td>
                                                                <!-- Показываем конверт только если НЕ прочитано -->
                                                                <?php if ($item['is_read'] == 0): ?>
                                                                    <a href="javascript:void(0);" class="font-18 text-info me-2 action-mark-read" data-id="<?= $item['id'] ?>" title="Отметить прочитанным"><i class="mdi mdi-email-open-outline"></i></a>
                                                                <?php endif; ?>
                                                                
                                                                <a href="javascript:void(0);" class="font-18 text-danger action-delete-notification" data-id="<?= $item['id'] ?>" title="Удалить"><i class="uil uil-trash"></i></a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <!-- Исправили colspan на 5 -->
                                                        <td colspan="5" class="text-center text-muted p-5">
                                                            <i class="mdi mdi-bell-off-outline h1"></i>
                                                            <p class="mt-3">У вас пока нет новых уведомлений</p>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div> <!-- end table-responsive-->
                                    
                                    <!-- Пагинация -->
                                    <div class="row mt-4">
                                        <div class="col-sm-12 col-md-5">
                                            <div class="dataTables_info" role="status" aria-live="polite">Показано с 1 по 5 из 5 записей</div>
                                        </div>
                                        <div class="col-sm-12 col-md-7">
                                            <div class="dataTables_paginate paging_simple_numbers">
                                                <ul class="pagination pagination-rounded justify-content-end">
                                                    <li class="paginate_button page-item previous disabled"><a href="#" class="page-link"><i class="mdi mdi-chevron-left"></i></a></li>
                                                    <li class="paginate_button page-item active"><a href="#" class="page-link">1</a></li>
                                                    <li class="paginate_button page-item next disabled"><a href="#" class="page-link"><i class="mdi mdi-chevron-right"></i></a></li>
                                                </ul>
                                            </div>
                                        </div>
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

    <!-- Modal Create Notification -->
    <div id="modal-create-notification" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modal-create-notification-label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal-create-notification-label">Создание уведомления</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-create-notification" class="needs-validation" novalidate>
                        
                        <div class="mb-3">
                            <label class="form-label">Получатели</label>
                            
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="send-to-all" name="send_to_all" value="1" checked>
                                <label class="form-check-label" for="send-to-all">Отправить всем активным сотрудникам</label>
                            </div>

                            <!-- Выбор конкретных пользователей (кастомный список) -->
                            <div class="block-select-users visually-hidden mt-3">
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-light border-end-0"><i class="mdi mdi-magnify"></i></span>
                                    <input type="text" class="form-control border-start-0" id="user-search-input" placeholder="Поиск сотрудника...">
                                </div>

                                <div class="user-list-container border rounded" style="max-height: 250px; overflow-y: auto;">
                                    <div class="list-group list-group-flush" id="user-list-group">
                                        <?php foreach ($users_list as $user): 
                                            $role_text = match ($user['user_group']) {
                                                1 => 'Директор',
                                                2 => 'Руководитель',
                                                3 => 'Менеджер',
                                                4 => 'Агент',
                                                default => 'Сотрудник'
                                            };
                                            $role_badge = match ($user['user_group']) {
                                                1 => 'danger',
                                                2 => 'warning',
                                                3 => 'info',
                                                4 => 'success',
                                                default => 'secondary'
                                            };
                                            $initials = mb_substr($user['user_firstname'], 0, 1) . mb_substr($user['user_lastname'], 0, 1);
                                        ?>
                                        <label class="list-group-item list-group-item-action d-flex align-items-center justify-content-between cursor-pointer user-item">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    <span class="avatar-title rounded-circle bg-<?= $role_badge ?>-lighten text-<?= $role_badge ?> font-12">
                                                        <?= $initials ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0 font-13 text-body user-name"><?= valid($user['user_lastname'] . ' ' . $user['user_firstname']) ?></h5>
                                                    <span class="font-11 text-muted user-role"><?= $role_text ?></span>
                                                </div>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input user-checkbox" type="checkbox" name="user_ids[]" value="<?= $user['user_id'] ?>">
                                            </div>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="mt-1">
                                    <small class="text-muted">Выбрано: <span id="selected-count">0</span></small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="notification-title" class="form-label">Заголовок</label>
                                    <input type="text" class="form-control" id="notification-title" name="title" placeholder="Например: Важное обновление" required>
                                    <div class="invalid-feedback">Введите заголовок</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="notification-type" class="form-label">Тип уведомления</label>
                                    <select class="form-select" id="notification-type" name="type">
                                        <option value="info" selected>Информационное (Синий)</option>
                                        <option value="success">Успех (Зеленый)</option>
                                        <option value="warning">Внимание (Желтый)</option>
                                        <option value="danger">Ошибка (Красный)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notification-message" class="form-label">Текст сообщения</label>
                            <textarea class="form-control" id="notification-message" name="message" rows="4" placeholder="Введите текст уведомления..." required></textarea>
                            <div class="invalid-feedback">Введите текст сообщения</div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="btn-create-notification" onclick="sendCreateNotification()">
                        <span class="spinner-border spinner-border-sm me-1 btn-loader visually-hidden" role="status" aria-hidden="true"></span>
                        <span class="btn-icon"><i class="mdi mdi-send me-1"></i></span>
                        <span class="loader-text visually-hidden">Отправка...</span>
                        <span class="btn-text">Отправить</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once SYSTEM . '/layouts/scripts.php'; ?>

    <script>
        $(document).ready(function() {
            
            // --- 1. ЛОГИКА ПОИСКА ПО СПИСКУ ПОЛЬЗОВАТЕЛЕЙ ---
            $('#user-search-input').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                
                // Перебираем все элементы списка с классом .user-item
                $("#user-list-group .user-item").filter(function() {
                    // Получаем текст внутри текущего элемента (Имя + Роль)
                    var text = $(this).text().toLowerCase();
                    // Скрываем/показываем в зависимости от совпадения
                    $(this).toggle(text.indexOf(value) > -1);
                });
            });

            // --- 2. ЛОГИКА ЧЕКБОКСА "ОТПРАВИТЬ ВСЕМ" ---
            $('#send-to-all').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.block-select-users').addClass('visually-hidden');
                    // Снимаем галочки, чтобы визуально не путать (опционально, серверу это не важно при send_to_all=1)
                    $('.user-checkbox').prop('checked', false);
                    updateSelectedCount();
                } else {
                    $('.block-select-users').removeClass('visually-hidden');
                }
            });

            // --- 3. СЧЕТЧИК ВЫБРАННЫХ ---
            $(document).on('change', '.user-checkbox', function() {
                updateSelectedCount();
            });

            function updateSelectedCount() {
                var count = $('.user-checkbox:checked').length;
                $('#selected-count').text(count);
            }


            // --- 4. ЛОГИКА ЧТЕНИЯ/УДАЛЕНИЯ (из предыдущих шагов) ---
            
            // Кнопка "Отметить все"
            $('.btn-mark-all').on('click', function() {
                const unreadItems = $('.fw-bold');
                if (unreadItems.length === 0) return;

                Swal.fire({
                    title: 'Отметить все?',
                    text: "Все уведомления будут помечены как прочитанные.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Да',
                    cancelButtonText: 'Отмена'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('/?form=read-notification', { mode: 'all' }, function(response) {
                            // Обновляем UI без перезагрузки
                            unreadItems.removeClass('fw-bold text-dark').addClass('fw-normal text-muted');
                            $('td span.text-body').removeClass('text-body').addClass('text-muted');
                            message('Успешно', 'Все уведомления помечены прочитанными', 'success');
                        }, 'json');
                    }
                });
            });

            // Иконка конвертика (прочитать одно)
            $(document).on('click', '.action-mark-read', function() {
                const btn = $(this);
                const row = btn.closest('tr');
                const id = btn.data('id');

                $.post('/?form=read-notification', { id: id, mode: 'single' }, function() {
                    row.find('.fw-bold').removeClass('fw-bold text-dark').addClass('fw-normal text-muted');
                    row.find('span.text-body').removeClass('text-body').addClass('text-muted');
                    btn.fadeOut(200);
                });
            });

            // Удаление
            $(document).on('click', '.action-delete-notification', function() {
                const btn = $(this);
                const row = btn.closest('tr');
                const id = btn.data('id');

                Swal.fire({
                    title: 'Удалить?',
                    text: "Это действие нельзя отменить.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Да, удалить',
                    cancelButtonText: 'Отмена'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/?form=delete-notification',
                            type: 'POST',
                            dataType: 'json',
                            data: { id: id },
                            success: function(response) {
                                if (response.status === 'success') {
                                    row.fadeOut(300, function() { 
                                        $(this).remove(); 
                                        if ($('#notifications-datatable tbody tr').length === 0) location.reload(); 
                                    });
                                    message('Успешно', 'Уведомление удалено', 'success');
                                } else {
                                    message('Ошибка', response.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            // Автоматическое прочтение при клике на ссылку
            $(document).on('click', '.notification-link', function(e) {
                e.preventDefault();
                const link = $(this);
                const url = link.attr('href');
                const id = link.data('id');
                const row = link.closest('tr');

                if (!row.find('span').first().hasClass('fw-bold')) {
                    window.location.href = url;
                    return;
                }

                $.post('/?form=read-notification', { id: id, mode: 'single' });
                setTimeout(function() { window.location.href = url; }, 100);
            });

            // --- АВТООБНОВЛЕНИЕ СПИСКА (AJAX Polling) ---
            function loadNotifications() {
                $.ajax({
                    url: '/?form=get-notifications',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Обновляем содержимое таблицы
                            $('#notifications-table-body').html(response.html);
                        }
                    }
                });
            }

            // Запускаем обновление каждые 30 секунд
            // Проверка !document.hidden экономит ресурсы сервера, если вкладка не активна
            setInterval(function() {
                if (!document.hidden) {
                    loadNotifications();
                }
            }, 10000);
        });

        // --- 5. ФУНКЦИЯ ОТПРАВКИ ФОРМЫ СОЗДАНИЯ ---
        function sendCreateNotification() {
            const form = $('#form-create-notification');
            const sendToAll = $('#send-to-all').is(':checked');
            const selectedUsers = $('.user-checkbox:checked').length;
            
            // ИСПРАВЛЕНИЕ: Передаем строку-селектор, а не объект
            const btn = '#btn-create-notification'; 

            // Валидация выбора получателей
            if (!sendToAll && selectedUsers === 0) {
                message('Ошибка', 'Выберите хотя бы одного получателя или отметьте "Отправить всем".', 'error');
                return;
            }

            // Валидация HTML5 (required поля)
            if (form[0].checkValidity()) {
                
                loaderBTN(btn, 'true'); // Теперь здесь передается строка, и ошибки не будет

                $.ajax({
                    url: '/?form=create-notification',
                    type: 'POST',
                    dataType: 'json',
                    data: form.serialize(),
                    success: function(response) {
                        loaderBTN(btn, 'false');
                        
                        if (response.success_type == 'message') {
                            $('#modal-create-notification').modal('hide');
                            message(response.msg_title, response.msg_text, response.msg_type, response.msg_url);
                        }
                    },
                    error: function() {
                        loaderBTN(btn, 'false');
                        message('Ошибка', 'Не удалось отправить данные на сервер.', 'error');
                    }
                });
            } else {
                form.addClass('was-validated');
            }
        }
    </script>

</body>
</html>