<?php 
$page_title = 'Восстановление пароля';
require_once SYSTEM . '/layouts/head.php';
?>

<body class="authentication-bg pb-0">

<div class="auth-fluid">
        <!--Auth fluid left content -->
        <div class="auth-fluid-form-box">
            <div class="card-body d-flex flex-column h-100 gap-3">

                <!-- Logo -->
                <div class="auth-brand text-center text-lg-start">
                    <a href="index.html" class="logo-dark">
                        <span><img src="assets/images/logo-dark.png" alt="dark logo" height="22"></span>
                    </a>
                    <a href="index.html" class="logo-light">
                        <span><img src="assets/images/logo.png" alt="logo" height="22"></span>
                    </a>
                </div>

                <div class="my-auto">
                    <!-- title-->
                    <h4>Восстановление пароля</h4>
                    <p class="text-muted mb-4">Введите свой email (логин) для отправки заявки на восстановление пароля.</p>

                    <!-- form -->
                    <form onsubmit="sendRecoveryForm('#form-recovery button[type=submit]')" id="form-recovery" class="needs-validation" novalidate>
                                
                                    <div class="mb-3">
                                        <label for="emailaddress" class="form-label">Логин</label>
                                        <input class="form-control" type="text" id="emailaddress" placeholder="Введите ваш email" name="login" required>
                                        <div class="invalid-feedback">Введите логин!</div>
                                    </div>

                                    <div class="mb-0 text-center">
                                        <button class="btn btn-primary" type="submit">
                                            <span class="spinner-border spinner-border-sm me-1 btn-loader visually-hidden" role="status" aria-hidden="true"></span>
                                            <span class="btn-icon"><i class="mdi mdi-lock-reset me-1"></i> </span>
                                            <span class="loader-text visually-hidden">Отправка...</span>
                                            <span class="btn-text">Восстановить</span>
                                        </button>
                                    </div>
                                </form>
                    <!-- end form-->
                </div>

                <!-- Footer-->
                <footer class="footer footer-alt">
                    <p class="text-muted">Вернуться на страницу <a href="/" class="text-muted ms-1"><b>Вход</b></a></p>
                </footer>

            </div> <!-- end .card-body -->
        </div>
        <!-- end auth-fluid-form-box-->

        <!-- Auth fluid right content -->
        <div class="auth-fluid-right text-center">
        </div>
        <!-- end Auth fluid right content -->
    </div>
    <!-- end auth-fluid-->



    <script>
            function sendRecoveryForm(btn) {
                event.preventDefault();
                loaderBTN(btn, 'true');
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=recovery',
                    type:     'POST',
                    dataType: 'html',
                    data:     jQuery('#form-recovery').serialize(),
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


    <?php
        require_once SYSTEM . '/layouts/scripts.php';
        ?>

</body>

</html>