<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Загрузка PDF-файлов';
require_once SYSTEM . '/layouts/head.php';
?>

<!-- Dropzone css -->
<link href="assets/vendor/dropzone/min/dropzone.min.css" rel="stylesheet" type="text/css" />

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
                                        <li class="breadcrumb-item"><a href="/?page=dashboard"><i class="uil-home-alt me-1"></i> Главная</a></li>
                                        <li class="breadcrumb-item active">Загрузка PDF</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Загрузка PDF-файлов анкет</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Левая колонка - Загрузка -->
                        <div class="col-lg-5 d-flex flex-column">
                            <div class="card flex-grow-1">
                                <div class="card-body d-flex flex-column">
                                    <h4 class="header-title">Загрузка файлов</h4>
                                    <p class="text-muted font-14">
                                        Перетащите файлы в область ниже или кликните для выбора.
                                    </p>
                                    <form action="/?form=upload-client-pdfs" class="dropzone flex-grow-1" id="my-awesome-dropzone">
                                        <div class="fallback">
                                            <input name="client_pdfs" type="file" multiple />
                                        </div>
                                        <div class="dz-message needsclick">
                                            <i class="h1 text-muted ri-upload-cloud-2-line"></i>
                                            <h3>Перетащите файлы сюда или кликните для загрузки.</h3>
                                            <span class="text-muted font-13">(Файлы будут обработаны автоматически после загрузки)</span>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Правая колонка - Результаты -->
                        <div class="col-lg-7 d-flex flex-column">
                            <div class="card flex-grow-1">
                                <div class="card-body d-flex flex-column">
                                    <h4 class="header-title">Результаты обработки</h4>
                                    <p class="text-muted font-14">
                                        Статус привязки каждого загруженного файла будет отображен здесь.
                                    </p>
                                    <div class="table-responsive flex-grow-1">
                                        <table class="table table-centered table-striped dt-responsive nowrap w-100" id="upload-results-datatable">
                                            <thead>
                                                <tr>
                                                    <th>Имя файла</th>
                                                    <th>Размер</th>
                                                    <th>Статус</th>
                                                    <th style="width: 150px;">Действия</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Строки будут добавляться через JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end row -->

                    <!-- Скрытый шаблон для превью в Dropzone (если понадобится) -->
                    <div class="d-none" id="file-previews"></div>
                    <div class="d-none" id="uploadPreviewTemplate"></div>
                </div>
            </div>
            <?php require_once SYSTEM . '/layouts/footer.php'; ?>
        </div>
    </div>

    <!-- Dropzone js -->
    <script src="assets/vendor/dropzone/min/dropzone.min.js"></script>

    <?php require_once SYSTEM . '/layouts/scripts.php'; ?>

</body>
</html>