<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Список визовых центров';
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
                                        <li class="breadcrumb-item active">Визовые центры</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Список визовых центров</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-5">
                                            <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightCenter" aria-controls="offcanvasRight" onclick="modalOnCenter('new', '', '', '')" class="btn btn-success mb-2 me-1"><i class="mdi mdi-plus-circle me-2"></i> Добавить визовый центр</a>
                                        </div>
                                        <div class="col-sm-7">
                                            <div class="text-sm-end">
                                                <div class="dropdown btn-group">
                                                    <button class="btn btn-light mb-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Действия</button>
                                                    <div class="dropdown-menu dropdown-menu-animated">
                                                        <a class="dropdown-item" href="#" onclick="handleMassAction('restore')">Восстановить</a>
                                                        <a class="dropdown-item text-danger" href="#" onclick="handleMassAction('delete')">Удалить</a>
                                                    </div>
                                                </div>
                                            </div><!-- end col-->
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-centered table-striped dt-responsive nowrap w-100" id="products-datatable">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 20px;">
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input" id="customCheck0">
                                                                <label class="form-check-label" for="customCheck0">&nbsp;</label>
                                                            </div>
                                                        </th>
                                                        <th>Название</th>
                                                        <th>Страна</th>
                                                        <th>Статус</th>
                                                        <th style="width: 75px;">Действия</th>
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                    <?php if ($centers) : foreach ($centers as $center) :
                                                            [$center_status_css, $center_status_text] = match ($center['center_status'] ?? '') {
                                                                0 => ['secondary', 'Удалённый'],
                                                                1 => ['success',   'Активен'],
                                                                2 => ['danger',    'Заблокирован'],
                                                                default => ['secondary', 'Неизвестно']
                                                            };
                                                        ?>

                                                            <tr>
                                                                <td>
                                                                    <div class="form-check">
                                                                        <input type="checkbox" class="form-check-input dt-checkboxes" id="customCheck<?= $center['center_id'] ?>">
                                                                        <label class="form-check-label" for="customCheck<?= $center['center_id'] ?>">&nbsp;</label>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span style="display:none;"><?= $center['center_id'] ?></span>
                                                                    <span class="text-body fw-semibold"><?= $center['center_name'] ?></span>
                                                                </td>
                                                                <td><span class="text-body fw-semibold"><?= $arr_countries[$center['country_id']] ?? 'Не указана' ?></span></td>
                                                                <td><span class="badge badge-<?= $center_status_css ?>-lighten"><?= $center_status_text ?></span></td>

                                                                <td>
                                                                    <?php if ($center['center_status'] === 0) : ?>
                                                                        <a href="#" class="font-18 text-warning" onclick="sendRestoreCenterForm('<?= $center['center_id'] ?>')"><i class="mdi mdi-cached"></i></a>
                                                                    <?php else : ?>
                                                                        <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightCenter" aria-controls="offcanvasRight" onclick="modalOnCenter('edit', '<?= $center['center_id'] ?>', '<?= $center['center_name'] ?>', '<?= $center['country_id'] ?>', '<?= $center['center_status'] ?>')" class="font-18 text-info me-2"><i class="uil uil-pen"></i></a>
                                                                        <a href="#" class="font-18 text-danger" data-bs-toggle="modal" data-bs-target="#del-center-modal" onclick="modalDelCenterForm('<?= $center['center_id'] ?>', '<?= $center['center_name'] ?>')"><i class="uil uil-trash"></i></a>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>


                                                    <?php endforeach;
                                                    endif; ?>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div> <!-- end card-body-->
                                </div> <!-- end card-->
                            </div> <!-- end col -->
                        </div>
                        <!-- end row -->

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
    </div>
    <!-- END wrapper -->


    <!-- Modals & Offcanvas -->

    <!-- Danger Alert Modal -->
    <div id="del-center-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content modal-filled bg-danger">
                <div class="modal-body p-4">
                    <div class="text-center">
                        <i class="ri-delete-bin-5-line h1"></i>
                        <h4 class="mt-2">Удаление</h4>
                        <p class="mt-3">Вы уверены что хотите удалить визовый центр "<span class="span-center-name"></span>"?</p>
                        <button type="button" class="btn btn-light my-2" data-bs-dismiss="modal" attr-center-id="" onclick="sendDelCenterForm()">Удалить</button>
                    </div>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->


    <!-- Add/Edit Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRightCenter" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header">
            <h5 id="offcanvasRightLabel"></h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div> <!-- end offcanvas-header-->

        <div class="offcanvas-body">
            <form onsubmit="sendCenterForm('#form-center button[type=submit]')" id="form-center" class="needs-validation" novalidate>

                <input type="hidden" name="center-edit-id" value="">

                <div class="mb-3">
                    <label for="center-name" class="form-label">Название визового центра</label>
                    <input type="text" class="form-control" id="center-name" placeholder="Введите название визового центра" name="center-name" value="" maxlength="25" data-toggle="maxlength" required>
                    <div class="invalid-feedback">Введите название визового центра!</div>
                </div>

                <div class="mb-3">
                    <label for="select-country" class="form-label">Страна</label>
                    <select id="select-country" class="form-control select2" data-toggle="select2" name="select-country">
                        <option value="hide">Выберите страну...</option>
                        <?php foreach ($countries as $country) : if ($country['country_status'] == 1) : ?>
                            <option value="<?= $country['country_id'] ?>"><?= $country['country_name'] ?></option>
                        <?php endif;
                        endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="select-center-status" class="form-label">Статус визового центра</label>
                    <select class="form-select" id="select-center-status" name="select-center-status">
                        <option value="1">Активен</option>
                        <option value="2">Заблокирован</option>
                    </select>
                </div>

                <input type="hidden" name="field_settings" id="field-settings-json">

                <div class="mb-3">
                    <label class="form-label">Настройка полей анкеты</label>
                    <div>
                        <button type="button" class="btn btn-light" id="btn-configure-fields" data-bs-toggle="modal" data-bs-target="#modal-center-fields">
                            <i class="mdi mdi-cogs me-1"></i> Настроить поля
                        </button>
                    </div>
                </div>

                <div class="text-end">
                    <button class="btn btn-success mt-2" type="submit">
                        <span class="spinner-border spinner-border-sm me-1 btn-loader visually-hidden" role="status" aria-hidden="true"></span>
                        <span class="btn-icon"><i class="mdi mdi-content-save me-1"></i> </span>
                        <span class="loader-text visually-hidden">Отправка...</span>
                        <span class="btn-text"></span>
                    </button>
                </div>
            </form>
        </div> <!-- end offcanvas-body-->
    </div> <!-- end offcanvas-->


    <!-- Fields Configuration Modal -->
    <div class="modal fade" id="modal-center-fields" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modal-country-fields-label" aria-hidden="true">
        <div class="modal-dialog modal-full-width">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-country-fields-label">Настройка полей для анкеты</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xl-4">
                            <h5 class="mb-3 text-uppercase"><i class="mdi mdi-account-circle me-1"></i> Основная информация</h5>

                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                <label class="form-label mb-0 fw-bold">Фамилия</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-visible-last_name" data-field="last_name" data-type="visible" checked disabled>
                                        <label class="form-check-label" for="switch-visible-last_name">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-required-last_name" data-field="last_name" data-type="required" checked disabled>
                                        <label class="form-check-label" for="switch-required-last_name">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                <label class="form-label mb-0 fw-bold">Имя</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-visible-first_name" data-field="first_name" data-type="visible" checked disabled>
                                        <label class="form-check-label" for="switch-visible-first_name">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-required-first_name" data-field="first_name" data-type="required" checked disabled>
                                        <label class="form-check-label" for="switch-required-first_name">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Отчество</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-visible-middle_name" data-field="middle_name" data-type="visible" checked>
                                        <label class="form-check-label" for="switch-visible-middle_name">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-required-middle_name" data-field="middle_name" data-type="required">
                                        <label class="form-check-label" for="switch-required-middle_name">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Мобильный телефон</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-visible-phone" data-field="phone" data-type="visible" checked>
                                        <label class="form-check-label" for="switch-visible-phone">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-required-phone" data-field="phone" data-type="required">
                                        <label class="form-check-label" for="switch-required-phone">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Пол</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-visible-gender" data-field="gender" data-type="visible" checked>
                                        <label class="form-check-label" for="switch-visible-gender">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-required-gender" data-field="gender" data-type="required">
                                        <label class="form-check-label" for="switch-required-gender">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Email</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-visible-email" data-field="email" data-type="visible" checked>
                                        <label class="form-check-label" for="switch-visible-email">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-required-email" data-field="email" data-type="required">
                                        <label class="form-check-label" for="switch-required-email">Обязательность</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4">
                            <h5 class="mb-3 text-uppercase"><i class="mdi mdi-card-account-details-outline me-1"></i> Документы</h5>

                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                <label class="form-label mb-0 fw-bold">Номер паспорта</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-visible-passport_number" data-field="passport_number" data-type="visible" checked disabled>
                                        <label class="form-check-label" for="switch-visible-passport_number">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-required-passport_number" data-field="passport_number" data-type="required" checked disabled>
                                        <label class="form-check-label" for="switch-required-passport_number">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Дата рождения</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-visible-birth_date" data-field="birth_date" data-type="visible" checked>
                                        <label class="form-check-label" for="switch-visible-birth_date">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-required-birth_date" data-field="birth_date" data-type="required">
                                        <label class="form-check-label" for="switch-required-birth_date">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Срок действия паспорта</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-visible-passport_expiry_date" data-field="passport_expiry_date" data-type="visible" checked>
                                        <label class="form-check-label" for="switch-visible-passport_expiry_date">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-required-passport_expiry_date" data-field="passport_expiry_date" data-type="required">
                                        <label class="form-check-label" for="switch-required-passport_expiry_date">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Национальность</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-visible-nationality" data-field="nationality" data-type="visible" checked>
                                        <label class="form-check-label" for="switch-visible-nationality">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-required-nationality" data-field="nationality" data-type="required">
                                        <label class="form-check-label" for="switch-required-nationality">Обязательность</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4">
                            <h5 class="mb-3 text-uppercase"><i class="mdi mdi-information-outline me-1"></i> Информация</h5>

                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                <label class="form-label mb-0 fw-bold">Агент</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-visible-agent_id" data-field="agent_id" data-type="visible" checked disabled>
                                        <label class="form-check-label" for="switch-visible-agent_id">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-required-agent_id" data-field="agent_id" data-type="required" checked disabled>
                                        <label class="form-check-label" for="switch-required-agent_id">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                <label class="form-label mb-0 fw-bold">Категории</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-visible-city_ids" data-field="city_ids" data-type="visible" checked disabled>
                                        <label class="form-check-label" for="switch-visible-city_ids">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-required-city_ids" data-field="city_ids" data-type="required" checked disabled>
                                        <label class="form-check-label" for="switch-required-city_ids">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                <label class="form-label mb-0 fw-bold">Стоимость</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-visible-sale_price" data-field="sale_price" data-type="visible" checked disabled>
                                        <label class="form-check-label" for="switch-visible-sale_price">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="switch-required-sale_price" data-field="sale_price" data-type="required" checked disabled>
                                        <label class="form-check-label" for="switch-required-sale_price">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Даты мониторинга</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-visible-monitoring_dates" data-field="monitoring_dates" data-type="visible" checked>
                                        <label class="form-check-label" for="switch-visible-monitoring_dates">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-required-monitoring_dates" data-field="monitoring_dates" data-type="required">
                                        <label class="form-check-label" for="switch-required-monitoring_dates">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Дни на дорогу до визита</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-visible-days_until_visit" data-field="days_until_visit" data-type="visible" checked>
                                        <label class="form-check-label" for="switch-visible-days_until_visit">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-required-days_until_visit" data-field="days_until_visit" data-type="required">
                                        <label class="form-check-label" for="switch-required-days_until_visit">Обязательность</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">Ваши пометки</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-visible-notes" data-field="notes" data-type="visible" checked>
                                        <label class="form-check-label" for="switch-visible-notes">Наличие</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input field-switch" type="checkbox" id="switch-required-notes" data-field="notes" data-type="required">
                                        <label class="form-check-label" for="switch-required-notes">Обязательность</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Готово</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once SYSTEM . '/layouts/scripts.php'; ?>

    <script>
    function modalOnCenter(type, id, name, country_id, status) {
        let modalTitle = $('#offcanvasRightCenter .offcanvas-header h5');
        let centerId = $('#form-center input[name="center-edit-id"]');
        let centerName = $('#form-center #center-name');
        let btn = $('#form-center button[type=submit] .btn-text');
        $('#form-center #select-center-status option').prop('selected', false);
        $('#field-settings-json').val('');
        const configureBtn = document.getElementById('btn-configure-fields');
        const icon = configureBtn ? configureBtn.querySelector('i.text-success') : null;
        if (icon) {
            icon.remove();
        }
        const fieldsModal = document.getElementById('modal-center-fields');
        if (fieldsModal) {
            const switches = fieldsModal.querySelectorAll('.field-switch');
            switches.forEach(s => {
                const requiredSwitch = document.getElementById('switch-required-' + s.dataset.field);
                if (s.dataset.type === 'visible') {
                    s.checked = true;
                    if (requiredSwitch) requiredSwitch.disabled = false;
                } else if (s.dataset.type === 'required') {
                    s.checked = false;
                }
            });
        }
        modalTitle.text('');
        centerId.val('');
        centerName.val('');
        if (type == 'new') {
            modalTitle.text('Добавление визового центра');
            $('#form-center #select-center-status option[value="1"]').prop('selected', true);
            $('#form-center #select-country').val('hide').trigger('change');
            btn.text('Добавить');
        } else if (type == 'edit') {
            modalTitle.text('Редактирование визового центра');
            centerId.val(id);
            centerName.val(name);
            $('#form-center #select-country').val(country_id).trigger('change');
            $('#form-center #select-center-status option[value="' + status + '"]').prop('selected', true);
            btn.text('Сохранить');
            $.ajax({
                url: '/?form=get-center-fields',
                type: 'POST',
                dataType: 'json',
                data: { 'center_id': id },
                success: function(settings) {
                    if (fieldsModal && settings && Object.keys(settings).length > 0) {
                        const switches = fieldsModal.querySelectorAll('.field-switch');
                        switches.forEach(s => {
                            const fieldName = s.dataset.field;
                            const type = s.dataset.type;
                            if (settings[fieldName]) {
                                s.checked = settings[fieldName][type === 'visible' ? 'is_visible' : 'is_required'];
                                const event = new Event('change', { bubbles: true });
                                s.dispatchEvent(event);
                            }
                        });
                        if (configureBtn && !configureBtn.querySelector('i.text-success')) {
                            const checkIcon = document.createElement('i');
                            checkIcon.className = 'mdi mdi-check-circle text-success ms-1';
                            configureBtn.appendChild(checkIcon);
                        }
                    }
                }
            });
        }
    }

    function sendCenterForm(btn) {
      event.preventDefault();
      const fieldsModal = $('#modal-center-fields');
      if (fieldsModal.length) {
          const settings = {};
          const allFields = new Set();
          fieldsModal.find('.field-switch').each(function() {
              allFields.add($(this).data('field'));
          });
          allFields.forEach(field => {
              const visibleSwitch = $('#switch-visible-' + field);
              const requiredSwitch = $('#switch-required-' + field);
              settings[field] = {
                  is_visible: visibleSwitch.is(':checked'),
                  is_required: requiredSwitch.is(':checked')
              };
          });
          $('#field-settings-json').val(JSON.stringify(settings));
      }
      loaderBTN(btn, 'true');
      let centerId = $('#form-center input[name="center-edit-id"]').val();
      let typeForm = centerId ? 'edit-center' : 'new-center';
      jQuery.ajax({
          url: '/?page=<?= $page ?>&form=' + typeForm,
          type: 'POST',
          dataType: 'html',
          data: jQuery('#form-center').serialize(),
          success: function(response) {
              loaderBTN(btn, 'false');
              result = jQuery.parseJSON(response);
              if (result.success_type == 'message') {
                  message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
              } else if (result.success_type == 'redirect') {
                  redirect(result.url);
              }
          },
          error: function() {
              loaderBTN(btn, 'false');
              message('Ошибка', 'Ошибка отправки формы!', 'error', '');
          }
      });
    }

    function modalDelCenterForm(centerid, centername) {
        $('#del-center-modal button').attr('attr-center-id', centerid);
        $('#del-center-modal .span-center-name').text(centername);
    }

    function sendDelCenterForm() {
        let centerid = $('#del-center-modal button').attr('attr-center-id');
        jQuery.ajax({
            url: '/?page=<?= $page ?>&form=del-center',
            type: 'POST',
            dataType: 'html',
            data: '&center-id=' + centerid,
            success: function(response) {
                result = jQuery.parseJSON(response);
                if (result.success_type == 'message') {
                    message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
                }
            },
            error: function() {
                message('Ошибка', 'Ошибка отправки формы!', 'error', '');
            }
        });
    }

    function sendRestoreCenterForm(centerid) {
        jQuery.ajax({
            url: '/?page=<?= $page ?>&form=restore-center',
            type: 'POST',
            dataType: 'html',
            data: '&center-id=' + centerid,
            success: function(response) {
                result = jQuery.parseJSON(response);
                if (result.success_type == 'message') {
                    message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
                }
            },
            error: function() {
                message('Ошибка', 'Ошибка отправки формы!', 'error', '');
            }
        });
    }
    
    // --- ФИНАЛЬНАЯ РАБОЧАЯ ВЕРСИЯ ---
    function handleMassAction(action) {
        const table = $('#products-datatable').DataTable();
        const selectedIds = [];

        const all_rows_nodes = table.rows({ page: 'all' }).nodes();

        $(all_rows_nodes).each(function() {
            const row_node = this;
            const checkbox = $(row_node).find('td:first .form-check-input');

            if (checkbox.is(':checked') && !checkbox.is('#customCheck0')) {
                // Имитируем логику clients.php: берем ID из второй колонки
                const id_cell = $(row_node).find('td').eq(1);
                const id = id_cell.find('span:first').text().trim();
                if (id) {
                    selectedIds.push(id);
                }
            }
        });

        if (selectedIds.length === 0) {
            message('Внимание', 'Пожалуйста, выберите хотя бы один визовый центр.', 'warning');
            return;
        }

        let confirmationTitle = 'Вы уверены?';
        let confirmationText = 'Вы действительно хотите выполнить это действие для ' + selectedIds.length + ' элементов?';
        let confirmButtonText = 'Да, выполнить!';

        if (action === 'restore') {
            confirmationTitle = 'Восстановить выбранное?';
            confirmButtonText = 'Да, восстановить!';
        } else if (action === 'delete') {
            confirmationTitle = 'Удалить выбранное?';
            confirmButtonText = 'Да, удалить!';
        }

        Swal.fire({
            title: confirmationTitle,
            text: confirmationText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Отмена'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/?form=mass-center-action',
                    type: 'POST',
                    dataType: 'json',
                    data: 'action=' + action + '&' + $.param({ 'center_ids': selectedIds }),
                    success: function(response) {
                        if (response.success_type == 'message') {
                            message(response.msg_title, response.msg_text, response.msg_type, response.msg_url);
                        }
                    },
                    error: function() {
                        message('Ошибка', 'Произошла ошибка при отправке запроса.', 'error');
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        const fieldsModal = $('#modal-center-fields');
        if (!fieldsModal.length) return;

        fieldsModal.on('change', '.field-switch', function() {
            const fieldName = $(this).data('field');
            const type = $(this).data('type');
            if (type === 'visible') {
                const requiredSwitch = $('#switch-required-' + fieldName);
                if ($(this).is(':checked')) {
                    requiredSwitch.prop('disabled', false);
                } else {
                    requiredSwitch.prop('checked', false).prop('disabled', true);
                }
            } else if (type === 'required') {
                const visibleSwitch = $('#switch-visible-' + fieldName);
                if ($(this).is(':checked') && !visibleSwitch.is(':checked')) {
                    visibleSwitch.prop('checked', true).trigger('change');
                }
            }
        });

        fieldsModal.find('.modal-footer .btn-primary').on('click', function() {
            const settings = {};
            const allFields = new Set();
            fieldsModal.find('.field-switch').each(function() {
                allFields.add($(this).data('field'));
            });
            allFields.forEach(field => {
                const visibleSwitch = $('#switch-visible-' + field);
                const requiredSwitch = $('#switch-required-' + field);
                settings[field] = {
                    is_visible: visibleSwitch.is(':checked'),
                    is_required: requiredSwitch.is(':checked')
                };
            });
            $('#field-settings-json').val(JSON.stringify(settings));
            const configureBtn = $('#btn-configure-fields');
            if (configureBtn.length && !configureBtn.find('i.text-success').length) {
                configureBtn.append(' <i class="mdi mdi-check-circle text-success ms-1"></i>');
            }
        });
    });
</script>

</body>

</html>