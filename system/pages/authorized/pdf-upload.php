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
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Загрузка файлов</h4>
                                    <p class="text-muted font-14">
                                        Перетащите файлы в область ниже или кликните для выбора. Система автоматически попытается распознать и прикрепить каждый файл к соответствующей анкете на основе настроенных правил.
                                    </p>

                                    <form action="/?form=upload-client-pdfs" class="dropzone" id="my-awesome-dropzone" data-plugin="dropzone" data-previews-container="#file-previews" data-upload-preview-template="#uploadPreviewTemplate">
                                        <div class="fallback">
                                            <input name="client_pdfs" type="file" multiple />
                                        </div>
                                        <div class="dz-message needsclick">
                                            <i class="h1 text-muted ri-upload-cloud-2-line"></i>
                                            <h3>Перетащите файлы сюда или кликните для загрузки.</h3>
                                            <span class="text-muted font-13">(Файлы будут обработаны автоматически после загрузки)</span>
                                        </div>
                                    </form>

                                    <!-- Preview -->
                                    <div class="dropzone-previews mt-3" id="file-previews"></div>

                                    <!-- File Preview Template -->
                                    <div class="d-none" id="uploadPreviewTemplate">
                                        <div class="card mt-1 mb-0 shadow-none border">
                                            <div class="p-2">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <img data-dz-thumbnail class="avatar-sm rounded bg-light" alt="">
                                                    </div>
                                                    <div class="col ps-0">
                                                        <a href="javascript:void(0);" class="text-muted fw-bold" data-dz-name></a>
                                                        <p class="mb-0" data-dz-size></p>
                                                    </div>
                                                    <div class="col-auto">
                                                        <!-- "Дополнительно" кнопка -->
                                                        <a href="" class="btn btn-link btn-lg text-muted" data-dz-remove></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div> <!-- end card-body-->
                            </div> <!-- end card-->
                        </div> <!-- end col-->
                    </div> <!-- end row -->
                </div>
            </div>
            <?php require_once SYSTEM . '/layouts/footer.php'; ?>
        </div>
    </div>

    <?php require_once SYSTEM . '/layouts/scripts.php'; ?>
    <!-- Dropzone js -->
    <script src="assets/vendor/dropzone/min/dropzone.min.js"></script>

    <script>
        // Отключаем автообнаружение Dropzone, чтобы настроить его вручную
        Dropzone.autoDiscover = false;

        $(document).ready(function() {
            // Инициализация Dropzone с нашими параметрами
            var myDropzone = new Dropzone("#my-awesome-dropzone", {
                paramName: "client_pdfs", // Имя, под которым файлы будут отправлены на сервер
                acceptedFiles: "application/pdf", // Принимать только PDF
                autoProcessQueue: true, // Начинать загрузку сразу после добавления
                parallelUploads: 5, // Загружать по 5 файлов одновременно
                dictDefaultMessage: "Перетащите файлы сюда для загрузки",
                dictInvalidFileType: "Разрешены только PDF-файлы.",
                
                // Обработка ответа от сервера после загрузки каждого файла
                init: function() {
                    this.on("success", function(file, response) {
                        try {
                            var result = JSON.parse(response);
                            // Обновляем внешний вид файла в списке в зависимости от ответа
                            if (result.status === 'success') {
                                $(file.previewElement).find('.col-auto a').html('<i class="mdi mdi-check-circle text-success"></i>');
                                $(file.previewElement).find('.col-ps-0').append('<p class="mb-0 text-success">Привязан к анкете #' + result.client_id + '</p>');
                            } else {
                                $(file.previewElement).find('.col-auto a').html('<i class="mdi mdi-close-circle text-danger"></i>');
                                $(file.previewElement).find('.col-ps-0').append('<p class="mb-0 text-danger">' + result.message + '</p>');
                            }
                        } catch (e) {
                            $(file.previewElement).find('.col-auto a').html('<i class="mdi mdi-close-circle text-danger"></i>');
                            $(file.previewElement).find('.col-ps-0').append('<p class="mb-0 text-danger">Ошибка ответа сервера.</p>');
                        }
                    });

                    this.on("error", function(file, errorMessage) {
                        $(file.previewElement).find('.col-auto a').html('<i class="mdi mdi-close-circle text-danger"></i>');
                        $(file.previewElement).find('.col-ps-0').append('<p class="mb-0 text-danger">' + (errorMessage.error || errorMessage) + '</p>');
                    });
                }
            });
        });
    </script>
</body>
</html>