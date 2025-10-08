<?php 
$page_title = 'Страница не найдена';
require_once SYSTEM . '/layouts/head.php';
?>

<body class="authentication-bg">

        <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xxl-4 col-lg-5">
                        <div class="card">
                            <!-- Logo -->
                            <div class="card-header py-4 text-center bg-primary">
                                <a href="/">
                                    <span><img src="assets/images/logo.png" alt="logo" height="30"></span>
                                </a>
                            </div>

                            <div class="card-body p-4">
                                <div class="text-center">
                                    <h1 class="text-error">4<i class="mdi mdi-emoticon-sad"></i>4</h1>
                                    <h4 class="text-uppercase text-danger mt-3">Страница не найдена</h4>
                                    <p class="text-muted mt-3">Похоже, вы свернули не туда. <br>Не волнуйтесь... это случается с лучшими из нас. <br>Вот небольшой совет, который может помочь вам вернуться на правильный путь.</p>

                                    <a class="btn btn-info mt-3" href="/"><i class="mdi mdi-reply"></i> Вернуться на главную страницу</a>
                                </div>
                            </div> <!-- end card-body-->
                        </div>
                        <!-- end card -->
                    </div> <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end page -->

        
            

        <?php require_once SYSTEM . '/layouts/scripts.php'; ?>
        <script>
            $('html').attr('data-theme', '');


</script>
        </body>
</html>