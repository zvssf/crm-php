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
                                                        <a class="dropdown-item" href="#" onclick="handleMassAction('restore')">Восстановить</a>
                                                        <a class="dropdown-item text-danger" href="#" onclick="handleMassAction('delete')">Удалить</a>
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
                                                        <td>
                                                            <span style="display:none;"><?= $country['country_id'] ?></span>
                                                            <span class="text-body fw-semibold"><?= $country['country_name'] ?></span>
                                                        </td>
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
                
                modalTitle.text('');
                countryId.val('');
                countryName.val('');
                $('#form-country #select-country-status option').prop('selected', false);

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
                }
            }

            function sendCountryForm(btn) {
                event.preventDefault();
                loaderBTN(btn, 'true');
                let countryId = $('#form-country input[name="country-edit-id"]').val();
                let typeForm = countryId ? 'edit-country' : 'new-country';
                
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
                        }
                    },
                    error:  function() {
                        message('Ошибка', 'Ошибка отправки формы!', 'error', '');
                    }
                });
            }

            function handleMassAction(action) {
                const table = $('#products-datatable').DataTable();
                const selectedIds = [];

                const all_rows_nodes = table.rows({ page: 'all' }).nodes();

                $(all_rows_nodes).each(function() {
                    const row_node = this;
                    const checkbox = $(row_node).find('td:first .form-check-input');

                    if (checkbox.is(':checked') && !checkbox.is('#customCheck0')) {
                        const id_cell = $(row_node).find('td').eq(1);
                        const id = id_cell.find('span:first').text().trim();
                        if (id) {
                            selectedIds.push(id);
                        }
                    }
                });

                if (selectedIds.length === 0) {
                    message('Внимание', 'Пожалуйста, выберите хотя бы одну страну.', 'warning');
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
                            url: '/?form=mass-country-action',
                            type: 'POST',
                            dataType: 'json',
                            data: 'action=' + action + '&' + $.param({ 'country_ids': selectedIds }),
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
        </script>


        <?php
        require_once SYSTEM . '/layouts/scripts.php';
        ?>
        </body>
</html>