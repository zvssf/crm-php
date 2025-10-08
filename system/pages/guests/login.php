<?php
$page_title = 'Вход в систему';
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
                    <h4 class="mt-0">Вход в систему</h4>
                    <p class="text-muted mb-4">Для входа введите email и пароль.</p>

                    <!-- form -->
                    <form onsubmit="sendLoginForm('#form-login button[type=submit]')" id="form-login" class="needs-validation" novalidate>
                    <div class="mb-3">
                                        <label for="emailaddress" class="form-label">Логин</label>
                                        <input class="form-control" type="text" id="emailaddress" placeholder="Введите ваш email" name="login" required>
                                        <div class="invalid-feedback">Введите логин!</div>
                                    </div>

                                    <div class="mb-3">
                                        <a href="/?page=recovery" class="text-muted float-end"><small>Забыли пароль?</small></a>
                                        <label for="password" class="form-label">Пароль</label>
                                        <div class="input-group input-group-merge">
                                            <input type="password" id="password" class="form-control" placeholder="Введите ваш пароль" name="password" required>
                                            <div class="input-group-text" data-password="false">
                                                <span class="password-eye"></span>
                                            </div>
                                            <div class="invalid-feedback">Введите пароль!</div>
                                        </div>
                                    </div>
                        <div class="d-grid mb-0 text-center">
                            <button class="btn btn-primary" type="submit">
                                <span class="spinner-border spinner-border-sm me-1 btn-loader visually-hidden" role="status" aria-hidden="true"></span>
                                <span class="btn-icon"><i class="mdi mdi-login me-1"></i> </span>
                                <span class="loader-text visually-hidden">Отправка...</span>
                                <span class="btn-text">Войти</span>
                            </button>
                        </div>

                    </form>
                    <!-- end form-->
                </div>

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
            function sendLoginForm(btn) {
                event.preventDefault();
                loaderBTN(btn, 'true');
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=login',
                    type:     'POST',
                    dataType: 'html',
                    data:     jQuery('#form-login').serialize(),
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