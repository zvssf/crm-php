<!DOCTYPE html>
<html lang="ru">

    <head>
        <meta charset="utf-8" />
        <title><?= $page_title ?> | CRM</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- Plugin css -->
        <link rel="stylesheet" href="assets/vendor/jquery-toast-plugin/jquery.toast.min.css">


        <!-- Bootstrap Touchspin css -->
<link href="assets/vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css" rel="stylesheet" type="text/css" />



        <!-- Icons css -->
<link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />






<!-- Datatable css -->
<link href="assets/vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/vendor/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />



<!-- Select2 css -->
<link href="assets/vendor/select2/css/select2.min.css" rel="stylesheet" type="text/css" />

<!-- Daterangepicker css -->
<link href="assets/vendor/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />

<!-- Sweet Alert2 css -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" type="text/css" />


<!-- App css -->
<link href="assets/css/app-saas.min.css" rel="stylesheet" type="text/css" id="app-style" />

<!-- Theme Config Js -->
<script src="assets/js/config.js"></script>

<style>
    .select2-container.is-invalid .select2-selection, .btn.is-invalid {
        border-color: #fa5c7c !important;
    }

    /* --- СТИЛИ ДЛЯ ЛИНИЙ ДУБЛИКАТОВ (ФИНАЛЬНАЯ ВЕРСИЯ) --- */
    #clients-datatable tbody td:nth-child(2) { /* 2-я колонка (ID) */
        position: relative;
    }

    /* Горизонтальная линия-отвод для всех дочерних анкет (применяется к TD) */
    .table-info-light:not(.main-duplicate-row) > td:nth-child(2)::after {
        content: '';
        position: absolute;
        left: 20px;
        top: 48%;
        width: 13px;
        border-bottom: 2px solid #8391a2;
        transform: translateY(-50%);
    }

    /* Сдвиг вправо для ID вложенных анкет */
    .duplicate-marker {
        padding-left: 25px;
    }

    /* Вертикальная линия для ГЛАВНОЙ анкеты */
    .main-duplicate-row > td:nth-child(2)::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 68%; /* Начинается от середины главной строки */
        bottom: 0;
        border-left: 2px solid #8391a2;
    }
    
    /* Вертикальная линия для ПРОМЕЖУТОЧНЫХ дочерних анкет */
    .table-info-light:not(.main-duplicate-row):not(.is-last-duplicate) > td:nth-child(2)::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 0;
        bottom: 0; /* Растягивается на всю высоту */
        border-left: 2px solid #8391a2;
    }

    /* Вертикальная линия для ПОСЛЕДНЕЙ дочерней анкеты */
    .is-last-duplicate > td:nth-child(2)::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 0;
        height: 50%; /* Заканчивается на середине последней строки */
        border-left: 2px solid #8391a2;
    }

    /* Убираем верхнюю границу у строк дубликатов, чтобы линия была сплошной */
    tr.table-info-light + tr.table-info-light > td {
        border-top: none !important;
    }
    /* --- КОНЕЦ СТИЛЕЙ --- */

    .details-control i {
        cursor: pointer;
        font-size: 16px;
    }
    .table-info-light > td, .table-info-light > th {
        background-color: #414d5f !important; /* Цвет фона для темной темы */
    }
    html[data-theme="light"] .table-info-light > td, html[data-theme="light"] .table-info-light > th {
        background-color: #e8f0fe !important; /* Цвет фона для светлой темы */
    }

    /* --- СТИЛИ ДЛЯ КАЛЕНДАРЯ --- */
    /* Общие стили для селекторов месяца и года */
    .daterangepicker .monthselect, .daterangepicker .yearselect {
        font-size: .8rem;
        padding: .2rem .4rem;
        border-radius: .2rem;
        border: 1px solid #6c757d; /* Серая обводка по умолчанию */
    }

    /* Стили для темной темы */
    .daterangepicker .monthselect, .daterangepicker .yearselect {
        background-color: #414d5f; /* Фон для темной темы */
        color: #eef2f7; /* Цвет текста для темной темы */
    }
    
    /* Стили для светлой темы */
    html[data-theme="light"] .daterangepicker .monthselect, html[data-theme="light"] .daterangepicker .yearselect {
        background-color: #ffffff; /* Фон для светлой темы */
        color: #6c757d; /* Цвет текста для светлой темы */
    }
    /* --- КОНЕЦ СТИЛЕЙ ДЛЯ КАЛЕНДАРЯ --- */
</style>

</head>
