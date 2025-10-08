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

    .duplicate-marker {
        position: relative;
        padding-left: 25px; /* Отступ для ID дубля */
    }

    .duplicate-marker::before {
        content: '';
        position: absolute;
        left: 10px; /* Положение вертикальной части линии */
        top: -16px; /* Насколько линия уходит вверх */
        height: 40px; /* Высота строки + небольшой запас */
        border-left: 1px solid #727cf5; /* Цвет линии */
    }

    .duplicate-marker::after {
        content: '';
        position: absolute;
        left: 10px; /* Начало горизонтальной линии */
        top: 50%;
        transform: translateY(-50%);
        width: 10px; /* Длина горизонтальной линии */
        border-bottom: 1px solid #727cf5; /* Цвет линии */
    }
</style>

</head>
