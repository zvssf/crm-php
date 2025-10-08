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
                                            
                                            <!-- end settings content-->
    
                                        
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

<script>
$('#select-group').on('change', function() {
    let el = $(this).val();
                let blockSupervisors = $('.block-supervisors');
                let blockManagers = $('.block-managers');
                let cssClass = 'visually-hidden';
                if(el == '3') {
                    blockSupervisors.removeClass(cssClass);
                    blockManagers.addClass(cssClass);
                } else if(el == '4') {
                    blockManagers.removeClass(cssClass);
                    blockSupervisors.addClass(cssClass);
                } else {
                    if(!blockSupervisors.hasClass(cssClass)) {
                        blockSupervisors.addClass(cssClass);
                    }
                    if(!blockManagers.hasClass(cssClass)) {
                        blockManagers.addClass(cssClass);
                    }
                }
                
            });
            </script>
        </body>
</html>