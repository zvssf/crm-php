<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Список стран';
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
                                            <li class="breadcrumb-item active">Страны</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Список стран</h4>
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
                                            <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightCountry" aria-controls="offcanvasRight" onclick="modalOnCountry('new', '', '', '', '')" class="btn btn-success mb-2 me-1"><i class="mdi mdi-plus-circle me-2"></i> Добавить страну</a>
                                            </div>
                                            <div class="col-sm-7">
                                                <div class="text-sm-end">
                                                <div class="dropdown btn-group">
                                                    <button class="btn btn-light mb-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Действия</button>
                                                    <div class="dropdown-menu dropdown-menu-animated">
                                                    <a class="dropdown-item" href="#">Активировать</a>
                                                    <a class="dropdown-item" href="#">Заблокировать</a>
                                                    <a class="dropdown-item" href="#">Удалить</a>
                                                    </div>
                                                </div>
                                                <!-- <div class="dropdown btn-group">
                                                    <button class="btn btn-info mb-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Фильтр</button>
                                                    <div class="dropdown-menu dropdown-menu-animated">
                                                    <a class="dropdown-item" href="#">Активировать</a>
                                                    <a class="dropdown-item" href="#">Заблокировать</a>
                                                    <a class="dropdown-item" href="#">Удалить</a>
                                                    </div>
                                                </div> -->
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
                                                        <th>Статус</th>
                                                        <th style="width: 75px;">Действия</th>
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                <?php if($countries): foreach($countries as $country):
                                                [$country_status_css, $country_status_text] = match ($country['country_status'] ?? '') {
                                                    0 => ['secondary', 'Удалённый'],
                                                    1 => ['success',   'Активен'],
                                                    2 => ['danger',    'Заблокирован'],
                                                    default => ['secondary', 'Неизвестно']
                                                };
                                                    ?>

                                                <tr>
                                                        <td>
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input" id="customCheck<?= $country['country_id'] ?>">
                                                                <label class="form-check-label" for="customCheck<?= $country['country_id'] ?>">&nbsp;</label>
                                                            </div>
                                                        </td>
                                                        <td><span class="text-body fw-semibold"><?= $country['country_name'] ?></span></td>
                                                        <td><span class="badge badge-<?= $country_status_css ?>-lighten"><?= $country_status_text ?></span></td>
                    
                                                        <td>
                                                            <?php if($country['country_status'] === 0): ?>
                                                                <a href="#" class="font-18 text-warning" onclick="sendRestoreCountryForm('<?= $country['country_id'] ?>')"><i class="mdi mdi-cached"></i></a>
                                                            <?php else: ?>
                                                                <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightCountry" aria-controls="offcanvasRight" onclick="modalOnCountry('edit', '<?= $country['country_id'] ?>', '<?= $country['country_name'] ?>', '<?= $country['country_status'] ?>')" class="font-18 text-info me-2"><i class="uil uil-pen"></i></a>
                                                            <a href="#" class="font-18 text-danger" data-bs-toggle="modal" data-bs-target="#del-country-modal" onclick="modalDelCountryForm('<?= $country['country_id'] ?>', '<?= $country['country_name'] ?>')"><i class="uil uil-trash"></i></a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>

                                                    
                                                    <?php endforeach; endif; ?>

                                                    
                                                    
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





                <!-- Danger Alert Modal -->
                <div id="del-country-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content modal-filled bg-danger">
                            <div class="modal-body p-4">
                                <div class="text-center">
                                    <i class="ri-delete-bin-5-line h1"></i>
                                    <h4 class="mt-2">Удаление</h4>
                                    <p class="mt-3">Вы уверены что хотите удалить страну "<span class="span-country-name"></span>"?</p>
                                    <button type="button" class="btn btn-light my-2" data-bs-dismiss="modal" attr-country-id="" onclick="sendDelCountryForm()">Удалить</button>
                                </div>
                            </div>
                        </div><!-- /.modal-content -->
                    </div><!-- /.modal-dialog -->
                </div><!-- /.modal -->

                          
                <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRightCountry" aria-labelledby="offcanvasRightLabel">
                    <div class="offcanvas-header">
                        <h5 id="offcanvasRightLabel"></h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div> <!-- end offcanvas-header-->

                    <div class="offcanvas-body">
                        
                        <form onsubmit="sendCountryForm('#form-country button[type=submit]')" id="form-country" class="needs-validation" novalidate>

                            <input type="hidden" name="country-edit-id" value="">
                            <input type="hidden" name="field_settings" id="field-settings-json">

                            <div class="mb-3">
                                <label for="country-name" class="form-label">Название страны</label>
                                <input type="text" class="form-control" id="country-name" placeholder="Введите название страны" name="country-name" value="" maxlength="25" data-toggle="maxlength" required>
                                <div class="invalid-feedback">Введите название страны!</div>
                            </div>

                            <div class="mb-3">
                                <label for="select-country-status" class="form-label">Статус страны</label>
                                <select class="form-select" id="select-country-status" name="select-country-status">
                                    <option value="1">Активен</option>
                                    <option value="2">Заблокирован</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Настройка полей анкеты</label>
                                <div>
                                    <button type="button" class="btn btn-light" id="btn-configure-fields" data-bs-toggle="modal" data-bs-target="#modal-country-fields">
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

            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->

        </div>
        <!-- END wrapper -->


        <script>
            function modalOnCountry(type, id, name, status) {
                let modalTitle = $('#offcanvasRightCountry .offcanvas-header h5');
                let countryId = $('#form-country input[name="country-edit-id"]');
                let countryName = $('#form-country #country-name');
                let btn = $('#form-country button[type=submit] .btn-text');
                let fieldSettingsInput = $('#field-settings-json');
                const configureBtn = document.getElementById('btn-configure-fields');
                const fieldsModal = document.getElementById('modal-country-fields');

                // --- НАЧАЛО БЛОКА ПОЛНОГО СБРОСА ---
                modalTitle.text('');
                countryId.val('');
                countryName.val('');
                fieldSettingsInput.val('');
                $('#form-country #select-country-status option').prop('selected', false);
                
                const icon = configureBtn ? configureBtn.querySelector('i.text-success') : null;
                if (icon) {
                    icon.remove();
                }

                // Принудительно сбрасываем все переключатели в модальном окне к состоянию по умолчанию
                if (fieldsModal) {
                    const switches = fieldsModal.querySelectorAll('.form-check-input');
                    switches.forEach(s => {
                        if (s.disabled) return;
                        const requiredSwitch = document.getElementById('switch-required-' + s.dataset.field);
                        if (s.dataset.type === 'visible') {
                            s.checked = true;
                            if (requiredSwitch) requiredSwitch.disabled = false;
                        } else if (s.dataset.type === 'required') {
                            s.checked = false;
                        }
                    });
                }
                // --- КОНЕЦ БЛОКА ПОЛНОГО СБРОСА ---

                if(type == 'new') {
                    modalTitle.text('Добавление страны');
                    $('#form-country #select-country-status option[value="1"]').prop('selected', true);
                    btn.text('Добавить');
                } else if(type == 'edit') {
                    modalTitle.text('Редактирование страны');
                    countryId.val(id);
                    countryName.val(name);
                    $('#form-country #select-country-status option[value="' + status + '"]').prop('selected', true);
                    btn.text('Сохранить');

                    // Загружаем и применяем настройки полей для этой страны
                    $.ajax({
                        url:      '/?form=get-country-fields',
                        type:     'POST',
                        dataType: 'json',
                        data:     { 'country_id': id },
                        success:  function(settings) {
                            if (fieldsModal && settings && Object.keys(settings).length > 0) {
                                const switches = fieldsModal.querySelectorAll('.form-check-input');
                                switches.forEach(s => {
                                    const fieldName = s.dataset.field;
                                    const type = s.dataset.type;
                                    if (settings[fieldName] && !s.disabled) {
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
                        },
                        error: function() {
                            message('Ошибка', 'Не удалось загрузить настройки полей.', 'error', '');
                        }
                    });
                }
            }


            function sendCountryForm(btn) {
                event.preventDefault();
                
                // --- НАЧАЛО НОВОГО БЛОКА СБОРА ДАННЫХ ---
                const settings = {};
                const fieldsModal = document.getElementById('modal-country-fields');
                if (fieldsModal) {
                    const switches = fieldsModal.querySelectorAll('.form-check-input');
                    switches.forEach(s => {
                        const field = s.dataset.field;
                        const type = s.dataset.type;

                        if (!settings[field]) {
                            settings[field] = { is_visible: false, is_required: false };
                        }

                        if (type === 'visible') {
                            settings[field].is_visible = s.checked;
                        } else if (type === 'required') {
                            settings[field].is_required = s.checked;
                        }
                    });
                }
                // Записываем собранные данные в скрытое поле перед отправкой
                document.getElementById('field-settings-json').value = JSON.stringify(settings);
                // --- КОНЕЦ НОВОГО БЛОКА СБОРА ДАННЫХ ---

                loaderBTN(btn, 'true');
                let countryId = $('#form-country input[name="country-edit-id"]').val();
                let typeForm;
                if(countryId) {
                    typeForm = 'edit-country';
                } else {
                    typeForm = 'new-country';
                }
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=' + typeForm,
                    type:     'POST',
                    dataType: 'html',
                    data:     jQuery('#form-country').serialize(),
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

            
            function modalDelCountryForm(countryid, countryname) {
                $('#del-country-modal button').attr('attr-country-id', countryid);
                $('#del-country-modal .span-country-name').text(countryname);
            }
            function sendDelCountryForm() {
                let countryid = $('#del-country-modal button').attr('attr-country-id');
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=del-country',
                    type:     'POST',
                    dataType: 'html',
                    data:     '&country-id=' + countryid,
                    success:  function(response) {
                        result = jQuery.parseJSON(response);
                        if(result.success_type == 'message') {
                            message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
                        } else if(result.success_type == 'redirect') {
                            redirect(result.url);
                        }
                    },
                    error:  function() {
                        message('Ошибка', 'Ошибка отправки формы!', 'error', '');
                    }
                });
            }
            function sendRestoreCountryForm(countryid) {
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=restore-country',
                    type:     'POST',
                    dataType: 'html',
                    data:     '&country-id=' + countryid,
                    success:  function(response) {
                        result = jQuery.parseJSON(response);
                        if(result.success_type == 'message') {
                            message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
                        } else if(result.success_type == 'redirect') {
                            redirect(result.url);
                        }
                    },
                    error:  function() {
                        message('Ошибка', 'Ошибка отправки формы!', 'error', '');
                    }
                });
            }
        </script>


        <?php
        require_once SYSTEM . '/layouts/scripts.php';
        ?>

        <div class="modal fade" id="modal-country-fields" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modal-country-fields-label" aria-hidden="true">
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
                                    <label class="form-label mb-0">Даты визита</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input field-switch" type="checkbox" id="switch-visible-visit_dates" data-field="visit_dates" data-type="visible" checked>
                                            <label class="form-check-label" for="switch-visible-visit_dates">Наличие</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input field-switch" type="checkbox" id="switch-required-visit_dates" data-field="visit_dates" data-type="required">
                                            <label class="form-check-label" for="switch-required-visit_dates">Обязательность</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label mb-0">Дни до визита</label>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fieldsModal = document.getElementById('modal-country-fields');
                const saveSettingsBtn = document.getElementById('save-field-settings-btn');
                const settingsJsonInput = document.getElementById('field-settings-json');
                
                fieldsModal.addEventListener('change', function(e) {
                    // Эта логика должна применяться ТОЛЬКО к изменяемым полям
                    if (e.target.classList.contains('field-switch') && e.target.dataset.type === 'visible') {
                        const fieldName = e.target.dataset.field;
                        const requiredSwitch = document.getElementById('switch-required-' + fieldName);
                        
                        if (requiredSwitch) {
                            if (!e.target.checked) {
                                // Если "Наличие" ВЫКЛЮЧЕНО, деактивируем и снимаем галочку с "Обязательность"
                                requiredSwitch.checked = false;
                                requiredSwitch.disabled = true;
                            } else {
                                // Если "Наличие" ВКЛЮЧЕНО, снова делаем "Обязательность" доступным
                                requiredSwitch.disabled = false;
                            }
                        }
                    }
                });
            });
        </script>
        </body>
</html>