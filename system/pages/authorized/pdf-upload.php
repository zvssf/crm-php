<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Загрузка PDF-файлов';
require_once SYSTEM . '/layouts/head.php';
?>

<!-- Dropzone css -->
<link href="assets/vendor/dropzone/min/dropzone.min.css" rel="stylesheet" type="text/css" />

<style>
    /* Фиксируем высоту основной строки контента */
    .full-height-content {
        height: calc(100vh - 260px);
        min-height: 500px;
    }

    /* Карточки на всю высоту */
    .card-full-height {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .card-body-full-height {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding-bottom: 10px;
    }

    /* --- Стили для левой колонки (Dropzone) --- */
    .scrollable-area-left {
        flex: 1;
        overflow-y: auto;
        padding-right: 5px; 
        display: flex;
        flex-direction: column;
    }

    .dropzone-custom-height {
        flex: 1;
        min-height: 200px;
        border: 2px dashed #dee2e6;
        background: #fff;
        border-radius: 6px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    /* --- Стили для правой колонки (DataTables Full Height) --- */
    
    /* Контейнер для таблицы */
    .table-container-full {
        flex: 1;
        overflow: hidden; /* Скрываем скролл родителя */
        position: relative;
        min-height: 0; 
        display: flex;
        flex-direction: column;
    }

    /* Обертка DataTables становится Flex-контейнером */
    #upload-results-datatable_wrapper {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    /* 1. Верхняя панель (Поиск) */
    #upload-results-datatable_wrapper > .row:first-child {
        flex-shrink: 0;
        margin-bottom: 10px;
        margin-right: 0;
        margin-left: 0;
    }

    /* 3. Нижняя панель (Пагинация) */
    #upload-results-datatable_wrapper > .row:last-child {
        flex-shrink: 0;
        margin-top: auto;
        margin-right: 0;
        margin-left: 0;
        padding-top: 10px;
    }

    /* 2. Центральная часть (Таблица) - ЭТОТ БЛОК БУДЕТ СКРОЛЛИТЬСЯ */
    #upload-results-datatable_wrapper > .row:nth-child(2) {
        flex-grow: 1;
        margin: 0;
        overflow-y: auto; /* Включаем вертикальный скролл здесь */
        overflow-x: hidden;
        min-height: 0; /* Firefox fix */
        padding-right: 2px; /* Отступ для скроллбара */
    }

    /* Колонка внутри центральной части */
    #upload-results-datatable_wrapper > .row:nth-child(2) > .col-sm-12 {
        height: 100%;
        padding: 0;
    }

    /* Корректировка для темной темы */
    html[data-theme="dark"] .dropzone-custom-height {
        background-color: #404954;
        border-color: #4f5b6d;
    }

    /* Фон шапки для темной темы */
    html[data-theme="dark"] table.dataTable thead th {
        background-color: #3a444e; /* Цвет фона шапки в темной теме */
        color: #e3eaef;
        border-bottom-color: #4f5b6d;
    }

    /* Стилизация скроллбара */
    html[data-theme="dark"] .scrollable-area-left,
    html[data-theme="dark"] #upload-results-datatable_wrapper > .row:nth-child(2) {
        scrollbar-color: #6c757d transparent;
        scrollbar-width: auto;
    }
    
    html[data-theme="dark"] .scrollable-area-left::-webkit-scrollbar,
    html[data-theme="dark"] #upload-results-datatable_wrapper > .row:nth-child(2)::-webkit-scrollbar {
        width: 16px;
        background-color: transparent;
    }

    html[data-theme="dark"] .scrollable-area-left::-webkit-scrollbar-thumb,
    html[data-theme="dark"] #upload-results-datatable_wrapper > .row:nth-child(2)::-webkit-scrollbar-thumb {
        background-color: #6c757d;
        border: 4px solid transparent;
        background-clip: content-box;
        border-radius: 8px;
    }

    /* Важно: Таблица должна вести себя нормально */
    table.dataTable {
        width: 100% !important;
        margin-bottom: 0 !important;
        border-collapse: collapse !important;
    }
    
    /* Перенос слов */
    table.dataTable td, table.dataTable th {
        white-space: normal !important;
        word-wrap: break-word;
        overflow-wrap: break-word;
        vertical-align: middle;
    }

    /* --- СТИЛИ ДЛЯ ЛИНИЙ ДУБЛИКАТОВ (Копия из clients.php) --- */
    table.dataTable tbody td.duplicate-id-col { 
        position: relative; 
    }

    .table-info-light:not(.main-duplicate-row) > td.duplicate-id-col::after {
        content: '';
        position: absolute;
        left: 20px;
        top: 48%;
        width: 13px;
        border-bottom: 2px solid #8391a2;
        transform: translateY(-50%);
    }

    .duplicate-marker {
        padding-left: 25px;
    }

    .main-duplicate-row > td.duplicate-id-col::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 60%; 
        bottom: 0;
        border-left: 2px solid #8391a2;
    }
    
    .table-info-light:not(.main-duplicate-row):not(.is-last-duplicate) > td.duplicate-id-col::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 0;
        bottom: 0; 
        border-left: 2px solid #8391a2;
    }

    .is-last-duplicate > td.duplicate-id-col::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 0;
        height: 50%; 
        border-left: 2px solid #8391a2;
    }
    
    .table-info-light > td {
        background-color: rgba(var(--ct-info-rgb), 0.15) !important; 
    }
</style>

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

                    <div class="row full-height-content">
                        <!-- Левая колонка - Загрузка -->
                        <div class="col-lg-5 mb-3 mb-lg-0 h-100">
                            <div class="card card-full-height">
                                <div class="card-body card-body-full-height">
                                    <h4 class="header-title mb-3">Загрузка файлов</h4>
                                    <p class="text-muted font-14">
                                        Перетащите файлы в область ниже или кликните для выбора.
                                    </p>
                                    
                                    <div class="scrollable-area-left">
                                        <form action="/?form=upload-client-pdfs" class="dropzone dropzone-custom-height" id="my-awesome-dropzone">
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
                        </div>

                        <!-- Правая колонка - Результаты -->
                        <div class="col-lg-7 h-100">
                            <div class="card card-full-height">
                                <div class="card-body card-body-full-height">
                                    <h4 class="header-title mb-3">Результаты обработки</h4>
                                    <p class="text-muted font-14">
                                        Статус привязки каждого загруженного файла будет отображен здесь.
                                    </p>
                                    
                                    <div class="table-container-full">
                                        <!-- Убрали лишние обертки, DataTables создаст свои -->
                                        <table class="table table-centered table-striped nowrap w-100" id="upload-results-datatable">
                                            <thead>
                                                <tr>
                                                    <th>Имя файла</th>
                                                    <th>Размер</th>
                                                    <th>Статус</th>
                                                    <th>Действия</th>
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

                    <!-- Скрытый шаблон для превью -->
                    <div class="d-none" id="file-previews"></div>
                    <div class="d-none" id="uploadPreviewTemplate"></div>
                </div>
            </div>
            <?php require_once SYSTEM . '/layouts/footer.php'; ?>
        </div>
    </div>

    <!-- Modal Resolve Duplicates -->
    <div id="modal-resolve-duplicates" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-full-width" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Выбор анкеты для прикрепления PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted font-14">
                        Найдено несколько анкет с совпадающими данными. Пожалуйста, выберите нужную анкету.
                    </p>

                    <!-- Скрытые поля для передачи данных -->
                    <input type="hidden" id="duplicates-temp-file" value="">
                    <input type="hidden" id="duplicates-pdf-data" value="">

                    <!-- Скрытые поля, сохраним сюда данные о временном файле и PDF -->
                    <input type="hidden" id="duplicates-temp-file" value="">
                    <input type="hidden" id="duplicates-pdf-data" value="">

                    <div class="table-responsive">
                        <table class="table table-centered table-striped w-100" id="duplicates-clients-datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ФИО</th>
                                    <th>Телефон</th>
                                    <th>Номер паспорта</th>
                                    <th>Города</th>
                                    <th>Категории</th>
                                    <th>Семья</th>
                                    <th>Менеджер</th>
                                    <th>Агент</th>
                                    <th>Стоимость</th>
                                    <th style="width: 120px;">Действия</th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Строки будут добавляться через JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
    <!-- /Modal Resolve Duplicates -->

    <!-- Dropzone js -->
    <script src="assets/vendor/dropzone/min/dropzone.min.js"></script>

    <?php require_once SYSTEM . '/layouts/scripts.php'; ?>

</body>
</html>