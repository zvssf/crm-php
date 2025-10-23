<?php

$edit_user_id = valid($_GET['id'] ?? '');

if (empty($edit_user_id)) {
    redirect('customers');
}

if (!preg_match('/^[0-9]{1,11}$/u', $edit_user_id)) {
    exit('Недопустимое значение ID!');
}

require_once SYSTEM . '/main-data.php';

$page_title = 'Редактирование сотрудника';
require_once SYSTEM . '/layouts/head.php';

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT * 
        FROM `users` 
        WHERE `user_id` = :user_id
    ");
    $stmt->execute([
        ':user_id' => $edit_user_id
    ]);

    if ($stmt->rowCount() === 0) {
        exit('Аккаунт не найден!');
    }

    $customer_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer_data['user_status'] === '0') {
        exit('Аккаунт удален!');
    }

    $assigned_countries = [];
    if ($customer_data['user_group'] == 4) {
        $stmt_countries = $pdo->prepare("SELECT `country_id` FROM `user_countries` WHERE `user_id` = :user_id");
        $stmt_countries->execute([':user_id' => $edit_user_id]);
        $assigned_countries = array_column($stmt_countries->fetchAll(PDO::FETCH_ASSOC), 'country_id');
    }

    $stmt = $pdo->prepare("
        SELECT * 
        FROM `users` 
        WHERE `user_status` = '1' 
          AND `user_group`  = '2' 
          AND `user_id`     != :current_user_id 
        ORDER BY `user_id` ASC
    ");
    $stmt->execute([
        ':current_user_id' => $user_data['user_id']
    ]);
    $supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT * 
        FROM `users` 
        WHERE `user_status` = '1' 
          AND `user_group`  = '3' 
          AND `user_id`     != :current_user_id 
        ORDER BY `user_id` ASC
    ");
    $stmt->execute([
        ':current_user_id' => $user_data['user_id']
    ]);
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
}

?>

<body>
    <!-- Begin page -->
    <div class="wrapper">

        <?php
        require_once SYSTEM . '/layouts/menu.php';
        ?>

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
                                        <li class="breadcrumb-item"><a href="/?page=customers">Сотрудники</a></li>
                                        <li class="breadcrumb-item active">Редактировать сотрудника</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Редактирование сотрудника</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-xl-7">
                            <div class="card">
                                <div class="card-body">
                                            
                                    <form onsubmit="sendEditInfoCustomerForm('#form-edit-info-customer button[type=submit]')" id="form-edit-info-customer" class="needs-validation" novalidate>

                                        <div class="row">
                                            <div class="col-xl-6">

                                                <h5 class="mb-4 text-uppercase"><i class="mdi mdi-account-circle me-1"></i> Персональная информация</h5>
                                                <div class="mb-3">
                                                    <label for="firstname" class="form-label">Имя</label>
                                                    <input type="text" class="form-control" id="firstname" placeholder="Введите имя" name="user-firstname" value="<?= $customer_data['user_firstname'] ?>" maxlength="25" data-toggle="maxlength" required>
                                                    <div class="invalid-feedback">Введите имя!</div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="lastname" class="form-label">Фамилия</label>
                                                    <input type="text" class="form-control" id="lastname" placeholder="Введите фамилию" name="user-lastname" value="<?= $customer_data['user_lastname'] ?>" maxlength="25" data-toggle="maxlength" required>
                                                    <div class="invalid-feedback">Введите фамилию!</div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="useremail" class="form-label">Email</label>
                                                    <input type="text" class="form-control" id="useremail" placeholder="Введите email" name="user-login" value="<?= $customer_data['user_login'] ?>" maxlength="32" data-toggle="maxlength" required>
                                                    <div class="invalid-feedback">Введите email!</div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="usertel" class="form-label">Номер телефона</label>
                                                    <input type="text" class="form-control" id="usertel" placeholder="Введите номер телефона" name="user-tel" value="<?= $customer_data['user_tel'] ?>" data-toggle="input-mask" data-mask-format="+#" required>
                                                    <div class="invalid-feedback">Введите номер телефона!</div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="usertel2" class="form-label">Второй номер (опционально)</label>
                                                    <input type="text" class="form-control" id="usertel2" placeholder="Введите второй номер" name="user-tel-2" value="<?= $customer_data['user_tel_2'] ?? '' ?>" data-toggle="input-mask" data-mask-format="+#">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="user_address" class="form-label">Адрес проживания</label>
                                                    <input type="text" class="form-control" id="user_address" placeholder="Введите адрес" name="user_address" value="<?= valid($customer_data['user_address'] ?? '') ?>" maxlength="255">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="user_website" class="form-label">Сайт</label>
                                                    <input type="text" class="form-control" id="user_website" placeholder="Введите адрес сайта" name="user_website" value="<?= valid($customer_data['user_website'] ?? '') ?>" maxlength="255">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Мессенджеры</label>
                                                    <div class="d-flex flex-wrap gap-2 mb-2" id="messenger-buttons">
                                                        <button type="button" class="btn btn-light" data-messenger="telegram" title="Telegram">
                                                            <img src="/assets/images/messengers/telegram.svg" height="16">
                                                        </button>
                                                        <button type="button" class="btn btn-light" data-messenger="whatsapp" title="WhatsApp">
                                                            <img src="/assets/images/messengers/whatsapp.svg" height="16">
                                                        </button>
                                                        <button type="button" class="btn btn-light" data-messenger="vk" title="VK">
                                                            <img src="/assets/images/messengers/vk.svg" height="16">
                                                        </button>
                                                        <button type="button" class="btn btn-light" data-messenger="viber" title="Viber">
                                                            <img src="/assets/images/messengers/viber.svg" height="16">
                                                        </button>
                                                        <button type="button" class="btn btn-light" data-messenger="x" title="X (Twitter)">
                                                            <img src="/assets/images/messengers/x.svg" height="16">
                                                        </button>
                                                    </div>
                                                    <div id="messenger-inputs">
                                                        <div class="mb-2" id="input-container-telegram" style="display: none;">
                                                            <label for="messenger_telegram" class="form-label font-13">Telegram</label>
                                                            <input type="text" id="messenger_telegram" name="messengers[telegram]" class="form-control" placeholder="@никнейм">
                                                        </div>
                                                        <div class="mb-2" id="input-container-whatsapp" style="display: none;">
                                                            <label for="messenger_whatsapp" class="form-label font-13">WhatsApp</label>
                                                            <input type="text" id="messenger_whatsapp" name="messengers[whatsapp]" class="form-control" placeholder="Номер телефона">
                                                        </div>
                                                        <div class="mb-2" id="input-container-vk" style="display: none;">
                                                            <label for="messenger_vk" class="form-label font-13">VK</label>
                                                            <input type="text" id="messenger_vk" name="messengers[vk]" class="form-control" placeholder="ID или ник">
                                                        </div>
                                                        <div class="mb-2" id="input-container-viber" style="display: none;">
                                                            <label for="messenger_viber" class="form-label font-13">Viber</label>
                                                            <input type="text" id="messenger_viber" name="messengers[viber]" class="form-control" placeholder="Номер телефона">
                                                        </div>
                                                        <div class="mb-2" id="input-container-x" style="display: none;">
                                                            <label for="messenger_x" class="form-label font-13">X (Twitter)</label>
                                                            <input type="text" id="messenger_x" name="messengers[x]" class="form-control" placeholder="@никнейм">
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="user_comment" class="form-label">Дополнительная информация</label>
                                                    <textarea class="form-control" id="user_comment" name="user_comment" rows="3" placeholder="Любая дополнительная информация..."><?= valid($customer_data['user_comment'] ?? '') ?></textarea>
                                                </div>

                                            </div>

                                            <div class="col-xl-6">

                                                <h5 class="mb-4 text-uppercase"><i class="mdi mdi-account-settings me-1"></i> Настройки</h5>
                                                <div class="mb-3">
                                                    <label for="select-status" class="form-label">Статус сотрудника</label>
                                                    <select class="form-select" id="select-status" name="select-status">
                                                        <option value="1" <?= ($customer_data['user_status'] == '1') ? 'selected' : '' ?>>Активен</option>
                                                        <option value="2" <?= ($customer_data['user_status'] == '2') ? 'selected' : '' ?>>Заблокирован</option>
                                                    </select>
                                                </div>
                                                    
                                                <div class="mb-3">
                                                    <label for="select-group" class="form-label">Группа пользователей</label>
                                                    <select class="form-select" id="select-group" name="select-group">
                                                        <option value="1" <?= ($customer_data['user_group'] == '1') ? 'selected' : '' ?>>Директор</option>
                                                        <option value="2" <?= ($customer_data['user_group'] == '2') ? 'selected' : '' ?>>Руководитель</option>
                                                        <option value="3" <?= ($customer_data['user_group'] == '3') ? 'selected' : '' ?>>Менеджер</option>
                                                        <option value="4" <?= ($customer_data['user_group'] == '4') ? 'selected' : '' ?>>Агент</option>
                                                    </select>
                                                </div>

                                                <div class="block-supervisors <?= ($customer_data['user_group'] != '3') ? 'visually-hidden' : '' ?>">

                                                    <?php if($supervisors): ?>
                                                    <div class="mb-3">
                                                        <label for="select-supervisor" class="form-label">Руководитель</label>
                                                        <select id="select-supervisor" class="form-control select2" data-toggle="select2" name="select-supervisor">
                                                            <option value="hide">Выберите руководителя...</option>
                                                            <?php
                                                            $supervisor_status = false;
                                                            foreach($supervisors as $supervisor): ?>
                                                                <option value="<?= $supervisor['user_id'] ?>" <?= ($supervisor['user_id'] == $customer_data['user_supervisor']) ? 'selected' : '' ?>><?= $supervisor['user_firstname'] ?> <?= $supervisor['user_lastname'] ?></option>
                                                            <?php
                                                            if($supervisor['user_id'] == $customer_data['user_supervisor']) {
                                                                $supervisor_status = true;
                                                            }
                                                            endforeach; ?>
                                                            </select>
                                                    </div>

                                                    <?php if(!$supervisor_status and $customer_data['user_group'] != '1' and $customer_data['user_group'] != '2'): ?>
                                                        <p class="text-danger">Назначенный руководитель не найден!</p>
                                                    <?php endif; ?>

                                                    <?php else: ?>
                                                        <p class="text-danger">Руководителей нет!</p>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="block-managers <?= ($customer_data['user_group'] != '4') ? 'visually-hidden' : '' ?>">

                                                    <?php if($managers): ?>

                                                    <div class="mb-3">
                                                        <label for="user-credit-limit" class="form-label">Кредитный лимит</label>
                                                        <input type="text" class="form-control" id="user-credit-limit" name="user_credit_limit" value="<?= $customer_data['user_credit_limit'] ?? '0.00' ?>" data-toggle="touchspin" data-step="1" data-min="0" data-max="10000000" data-decimals="2" data-bts-prefix="$">
                                                    </div>
                                                        
                                                    <div class="mb-3">
                                                        <label for="select-manager" class="form-label">Менеджер</label>
                                                        <select id="select-manager" class="form-control select2" data-toggle="select2" name="select-manager">
                                                            <option value="hide">Выберите менеджера...</option>
                                                            <?php
                                                            $manager_status = false;
                                                            foreach($managers as $manager): ?>
                                                                <option value="<?= $manager['user_id'] ?>" <?= ($manager['user_id'] == $customer_data['user_supervisor']) ? 'selected' : '' ?>><?= $manager['user_firstname'] ?> <?= $manager['user_lastname'] ?></option>
                                                            <?php
                                                            if($manager['user_id'] == $customer_data['user_supervisor']) {
                                                                $manager_status = true;
                                                            }
                                                            endforeach; ?>
                                                            </select>
                                                    </div>

                                                    <?php if(!$manager_status and $customer_data['user_group'] != '1' and $customer_data['user_group'] != '2'): ?>
                                                        <p class="text-danger">Назначенный менеджер не найден!</p>
                                                    <?php endif; ?>

                                                    <?php else: ?>
                                                        <p class="text-danger">Менеджеров нет!</p>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="block-agent-countries <?= ($customer_data['user_group'] != '4') ? 'visually-hidden' : '' ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Доступные направления</label>
                                                        <div>
                                                            <button type="button" class="btn btn-light" id="btn-configure-countries" data-bs-toggle="modal" data-bs-target="#countries-modal">
                                                                <i class="mdi mdi-cogs me-1"></i> Настроить
                                                            </button>
                                                        </div>
                                                        <div id="hidden-countries-inputs"></div>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>

                                        <div class="text-end">
                                            <button class="btn btn-success mt-2" type="submit">
                                                <span class="spinner-border spinner-border-sm me-1 btn-loader visually-hidden" role="status" aria-hidden="true"></span>
                                                <span class="btn-icon"><i class="mdi mdi-content-save me-1"></i> </span>
                                                <span class="loader-text visually-hidden">Отправка...</span>
                                                <span class="btn-text">Сохранить</span>
                                            </button>
                                        </div>
                                    </form>

                                </div> <!-- end card body -->
                            </div> <!-- end card -->

                        </div> <!-- end col-->

                        <div class="col-xl-5">

                            <div class="card">
                                <div class="card-body">
    
                                    <form onsubmit="sendEditPasswordCustomerForm('#form-edit-password-customer button[type=submit]')" id="form-edit-password-customer" class="needs-validation" novalidate>

                                        <h5 class="mb-4 text-uppercase"><i class="mdi mdi-lock me-1"></i> Установка пароля</h5>
                                        <div class="mb-3">
                                            <label for="new-password" class="form-label">Пароль</label>
                                            <div class="input-group input-group-merge">
                                                <input type="password" id="new-password" class="form-control" placeholder="Введите пароль" name="new-password" maxlength="25" data-toggle="maxlength" required>
                                                <div class="input-group-text" data-password="false">
                                                    <span class="password-eye"></span>
                                                </div>
                                                <div class="invalid-feedback">Введите новый пароль!</div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm-password" class="form-label">Повтор пароля</label>
                                            <div class="input-group input-group-merge">
                                                <input type="password" id="confirm-password" class="form-control" placeholder="Повторите пароль" name="confirm-password" maxlength="25" data-toggle="maxlength" required>
                                                <div class="input-group-text" data-password="false">
                                                    <span class="password-eye"></span>
                                                </div>
                                                <div class="invalid-feedback">Повторите новый пароль!</div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button class="btn btn-success mt-2" type="submit">
                                                <span class="spinner-border spinner-border-sm me-1 btn-loader visually-hidden" role="status" aria-hidden="true"></span>
                                                <span class="btn-icon"><i class="mdi mdi-content-save me-1"></i> </span>
                                                <span class="loader-text visually-hidden">Отправка...</span>
                                                <span class="btn-text">Сохранить</span>
                                            </button>
                                        </div>
                                    </form>
                                
                                </div> <!-- end card body -->
                            </div> <!-- end card -->

                        </div> <!-- end col-->

                    </div>
                    <!-- end row-->

                </div>
                <!-- container -->

            </div>
            <!-- content -->

            <?php require_once SYSTEM . '/layouts/footer.php'; ?>

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>

    <!-- Countries Modal -->
    <div id="countries-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="countries-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="countries-modal-label">Настройка доступных направлений</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <?php
                        $active_countries = array_filter($countries, function ($country) {
                            return $country['country_status'] == 1;
                        });

                        if (!empty($active_countries)):
                            foreach ($active_countries as $country):
                                $is_checked = in_array($country['country_id'], $assigned_countries);
                        ?>
                                <div class="col-lg-6">
                                    <div class="mb-2 form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="country-<?= $country['country_id'] ?>" name="countries[]" value="<?= $country['country_id'] ?>" <?= $is_checked ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="country-<?= $country['country_id'] ?>"><?= $country['country_name'] ?></label>
                                    </div>
                                </div>
                        <?php
                            endforeach;
                        else:
                        ?>
                            <p class="text-muted">Активных стран не найдено.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-success" id="save-countries-btn">Сохранить</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <!-- END wrapper -->

    <script>
        function sendEditInfoCustomerForm(btn) {
            event.preventDefault();
            loaderBTN(btn, 'true');
            jQuery.ajax({
                url:      '/?page=<?= $page ?>&form=edit-info-customer&user-id=<?= $edit_user_id ?>',
                type:     'POST',
                dataType: 'html',
                data:     jQuery('#form-edit-info-customer').serialize(),
                success:  function(response) {
                    loaderBTN(btn, 'false');
                    result = jQuery.parseJSON(response);
                    if(result.success_type == 'message') {
                        message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
                    } else if(result.success_type == 'redirect') {
                        redirect(result.url);
                    }
                },
                error:  function() {
                    loaderBTN(btn, 'false');
                    message('Ошибка', 'Ошибка отправки формы!', 'error', '');
                }
            });
        }
        function sendEditPasswordCustomerForm(btn) {
            event.preventDefault();
            loaderBTN(btn, 'true');
            jQuery.ajax({
                url:      '/?page=<?= $page ?>&form=edit-customer-new-password&user-id=<?= $edit_user_id ?>',
                type:     'POST',
                dataType: 'html',
                data:     jQuery('#form-edit-password-customer').serialize(),
                success:  function(response) {
                    loaderBTN(btn, 'false');
                    result = jQuery.parseJSON(response);
                    if(result.success_type == 'message') {
                        message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
                    } else if(result.success_type == 'redirect') {
                        redirect(result.url);
                    }
                },
                error:  function() {
                    loaderBTN(btn, 'false');
                    message('Ошибка', 'Ошибка отправки формы!', 'error', '');
                }
            });
        }
    </script>

    <?php require_once SYSTEM . '/layouts/scripts.php'; ?>

    <style>
        #messenger-buttons .btn.active {
            border: 2px solid #727cf5;
            background-color: transparent;
        }
    </style>

    <script>
        $('#select-group').on('change', function() {
            let el = $(this).val();
            let blockSupervisors = $('.block-supervisors');
            let blockManagers = $('.block-managers');
            let blockAgentCountries = $('.block-agent-countries');
            let cssClass = 'visually-hidden';

            // Сначала сбрасываем все зависимые блоки
            blockSupervisors.addClass(cssClass);
            blockManagers.addClass(cssClass);
            blockAgentCountries.addClass(cssClass);

            if(el == '3') { // Менеджер
                blockSupervisors.removeClass(cssClass);
            } else if(el == '4') { // Агент
                blockManagers.removeClass(cssClass);
                blockAgentCountries.removeClass(cssClass);
            }
        });
    </script>
    <script>
        $(document).ready(function() {

            // Логика для модального окна стран
            $('#save-countries-btn').on('click', function() {
                const hiddenContainer = $('#hidden-countries-inputs');
                hiddenContainer.empty(); // Очищаем старые значения

                // Находим все отмеченные чекбоксы и создаем для них скрытые инпуты
                $('#countries-modal input[name="countries[]"]:checked').each(function() {
                    const countryId = $(this).val();
                    hiddenContainer.append(`<input type="hidden" name="countries[]" value="${countryId}">`);
                });

                // Добавляем галочку к кнопке
                const configureBtn = $('#btn-configure-countries');
                if (!configureBtn.find('i.text-success').length) {
                    configureBtn.append(' <i class="mdi mdi-check-circle text-success"></i>');
                }

                $('#countries-modal').modal('hide');
            });

            // Показываем галочку при загрузке страницы, если страны уже были назначены
            if ($('#countries-modal input[name="countries[]"]:checked').length > 0) {
                $('#btn-configure-countries').append(' <i class="mdi mdi-check-circle text-success"></i>');
            }

            // Функция для обработки клика по кнопке
            $('#messenger-buttons .btn').on('click', function() {
                $(this).toggleClass('active');
                const messenger = $(this).data('messenger');
                const inputContainer = $('#input-container-' + messenger);
                
                inputContainer.slideToggle(200);

                if (!$(this).hasClass('active')) {
                    inputContainer.find('input').val('');
                }
            });

            // Функция для инициализации полей при загрузке
            function initializeMessengers() {
                const messengersData = '<?= valid($customer_data['user_messengers'] ?? '') ?>';
                if (!messengersData) {
                    return;
                }

                const pairs = messengersData.split('|');
                pairs.forEach(function(pair) {
                    const parts = pair.split(':');
                    if (parts.length === 2) {
                        const key = parts[0];
                        const value = parts[1];
                        
                        // Активируем кнопку
                        $('#messenger-buttons .btn[data-messenger="' + key + '"]').addClass('active');
                        
                        // Заполняем и показываем поле
                        const inputContainer = $('#input-container-' + key);
                        inputContainer.find('input').val(value);
                        inputContainer.show();
                    }
                });
            }

            initializeMessengers();
        });
    </script>
</body>
</html>