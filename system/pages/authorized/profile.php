<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Редактирование профиля';
require_once SYSTEM . '/layouts/head.php';
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
                                            <li class="breadcrumb-item active">Профиль</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Редактирование профиля</h4>
                                </div>
                            </div>
                        </div>
                        <!-- end page title -->

                        <div class="row">
                            <div class="col-xl-7">
                            <div class="card">
                                    <div class="card-body">
    
                                            
                                                <form onsubmit="sendProfileEditForm('#form-profile-edit button[type=submit]')" id="form-profile-edit" class="needs-validation" novalidate>
                                                    <h5 class="mb-4 text-uppercase"><i class="mdi mdi-account-circle me-1"></i> Персональная информация</h5>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="firstname" class="form-label">Имя</label>
                                                                <input type="text" class="form-control" id="firstname" placeholder="Введите имя" name="user-firstname" value="<?= $user_data['user_firstname'] ?>" maxlength="25" data-toggle="maxlength" required>
                                                                <div class="invalid-feedback">Введите имя!</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="lastname" class="form-label">Фамилия</label>
                                                                <input type="text" class="form-control" id="lastname" placeholder="Введите фамилию" name="user-lastname" value="<?= $user_data['user_lastname'] ?>" maxlength="25" data-toggle="maxlength" required>
                                                                <div class="invalid-feedback">Введите фамилию!</div>
                                                            </div>
                                                        </div> <!-- end col -->
                                                    </div> <!-- end row -->
    
                                                    <!-- <div class="row">
                                                        <div class="col-12">
                                                            <div class="mb-3">
                                                                <label for="userbio" class="form-label">Bio</label>
                                                                <textarea class="form-control" id="userbio" rows="4" placeholder="Write something..."></textarea>
                                                            </div>
                                                        </div>
                                                    </div> -->
    
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="useremail" class="form-label">Email</label>
                                                                <input type="text" class="form-control" id="useremail" placeholder="Введите email" name="user-login" value="<?= $user_data['user_login'] ?>" maxlength="32" data-toggle="maxlength" required>
                                                                <div class="invalid-feedback">Введите email!</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="usertel" class="form-label">Номер телефона</label>
                                                                <input type="text" class="form-control" id="usertel" placeholder="Введите номер телефона" name="user-tel" value="<?= $user_data['user_tel'] ?>" data-toggle="input-mask" data-mask-format="+#" required>
                                                                <div class="invalid-feedback">Введите номер телефона!</div>
                                                            </div>
                                                        </div> <!-- end col -->
                                                    </div> <!-- end row -->
                                                    
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

                            <div class="col-xl-5">
                                



                                <div class="card">
                                    <div class="card-body">
    
                                            
                                                <form onsubmit="sendProfileNewPassForm('#form-profile-new-password button[type=submit]')" id="form-profile-new-password" class="needs-validation" novalidate>
                                                    <h5 class="mb-4 text-uppercase"><i class="mdi mdi-lock-reset me-1"></i> Изминение пароля</h5>
                                                        <div class="mb-3">
                                                            <label for="new-password" class="form-label">Новый пароль</label>
                                                            <div class="input-group input-group-merge">
                                                                <input type="password" id="new-password" class="form-control" placeholder="Введите новый пароль" name="new-password" maxlength="25" data-toggle="maxlength" required>
                                                                <div class="input-group-text" data-password="false">
                                                                    <span class="password-eye"></span>
                                                                </div>
                                                                <div class="invalid-feedback">Введите новый пароль!</div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="confirm-password" class="form-label">Повтор нового пароля</label>
                                                            <div class="input-group input-group-merge">
                                                                <input type="password" id="confirm-password" class="form-control" placeholder="Повторите новый пароль" name="confirm-password" maxlength="25" data-toggle="maxlength" required>
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



                            </div> <!-- end col -->
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
            function sendProfileEditForm(btn) {
                event.preventDefault();
                loaderBTN(btn, 'true');
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=profile-edit',
                    type:     'POST',
                    dataType: 'html',
                    data:     jQuery('#form-profile-edit').serialize(),
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
            function sendProfileNewPassForm(btn) {
                event.preventDefault();
                loaderBTN(btn, 'true');
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=profile-new-password',
                    type:     'POST',
                    dataType: 'html',
                    data:     jQuery('#form-profile-new-password').serialize(),
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
        </body>
</html>