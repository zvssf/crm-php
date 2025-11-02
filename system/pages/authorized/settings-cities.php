<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Список городов';
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
                                            <li class="breadcrumb-item active">Города</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Список городов</h4>
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
                                            <a href="/?page=new-city" class="btn btn-success mb-2 me-1"><i class="mdi mdi-plus-circle me-2"></i> Добавить город</a>
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
                                                        <th>Категория</th>
                                                        <th>Страна</th>
                                                        <th>Себестоимость</th>
                                                        <th>Мин. цена</th>
                                                        <th>Статус</th>
                                                        <th style="width: 75px;">Действия</th>
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                <?php if($cities): foreach($cities as $city):
                                                [$city_status_css, $city_status_text] = match ($city['city_status'] ?? '') {
                                                    0 => ['secondary', 'Удалённый'],
                                                    1 => ['success',   'Активен'],
                                                    2 => ['danger',    'Заблокирован'],
                                                    default => ['secondary', 'Неизвестно']
                                                };
                                                ?>

                                                <?php
                                                [$cost_price_css] = match (true) {
                                                    $city['cost_price'] < 0  => ['danger'],
                                                    $city['cost_price'] > 0  => ['success'],
                                                    default                  => ['secondary']
                                                };
                                                
                                                [$min_sale_price_css] = match (true) {
                                                    $city['min_sale_price'] < 0  => ['danger'],
                                                    $city['min_sale_price'] > 0  => ['success'],
                                                    default                      => ['secondary']
                                                };
                                                ?>

                                                <tr>
                                                        <td>
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input dt-checkboxes" id="customCheck<?= $city['city_id'] ?>">
                                                                <label class="form-check-label" for="customCheck<?= $city['city_id'] ?>">&nbsp;</label>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span style="display:none;"><?= $city['city_id'] ?></span>
                                                            <span class="text-body fw-semibold"><?= $city['city_name'] ?></span>
                                                        </td>
                                                        <td><span class="text-body"><?= $city['city_category'] ?></span></td>
                                                        <td><span class="text-body fw-semibold"><?= $arr_countries[$city['country_id']] ?? 'Неизвестно' ?></span></td>
                                                        <td><span class="text-<?= $cost_price_css ?> fw-semibold"><i class="mdi mdi-currency-usd"></i><?= number_format($city['cost_price'], 2, '.', ' ') ?></span></td>
                                                        <td><span class="text-<?= $min_sale_price_css ?> fw-semibold"><i class="mdi mdi-currency-usd"></i><?= number_format($city['min_sale_price'], 2, '.', ' ') ?></span></td>
                                                        <td><span class="badge badge-<?= $city_status_css ?>-lighten"><?= $city_status_text ?></span></td>
                    
                                                        <td>
                                                            <?php if($city['city_status'] === 0): ?>
                                                                <a href="#" class="font-18 text-warning" onclick="sendRestoreCityForm('<?= $city['city_id'] ?>')"><i class="mdi mdi-cached"></i></a>
                                                            <?php else: ?>
                                                                <a href="/?page=edit-city&id=<?= $city['city_id'] ?>" class="font-18 text-info me-2"><i class="uil uil-pen"></i></a>
                                                            <a href="#" class="font-18 text-danger" data-bs-toggle="modal" data-bs-target="#del-city-modal" onclick="modalDelCityForm('<?= $city['city_id'] ?>', '<?= $city['city_name'] ?>')"><i class="uil uil-trash"></i></a>
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
<div id="del-city-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content modal-filled bg-danger">
            <div class="modal-body p-4">
                <div class="text-center">
                    <i class="ri-delete-bin-5-line h1"></i>
                    <h4 class="mt-2">Удаление</h4>
                    <p class="mt-3">Вы уверены что хотите удалить город "<span class="span-city-name"></span>"?</p>
                    <button type="button" class="btn btn-light my-2" data-bs-dismiss="modal" attr-city-id="" onclick="sendDelCityForm()">Удалить</button>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

                

            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->

        </div>
        <!-- END wrapper -->


        <script>
            function modalDelCityForm(cityid, cityname) {
                $('#del-city-modal button').attr('attr-city-id', cityid);
                $('#del-city-modal .span-city-name').text(cityname);
            }
            function sendDelCityForm() {
                let cityid = $('#del-city-modal button').attr('attr-city-id');
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=del-city',
                    type:     'POST',
                    dataType: 'html',
                    data:     '&city-id=' + cityid,
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
            function sendRestoreCityForm(cityid) {
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=restore-city',
                    type:     'POST',
                    dataType: 'html',
                    data:     '&city-id=' + cityid,
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
                    message('Внимание', 'Пожалуйста, выберите хотя бы один город.', 'warning');
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
                            url: '/?form=mass-city-action',
                            type: 'POST',
                            dataType: 'json',
                            data: 'action=' + action + '&' + $.param({ 'city_ids': selectedIds }),
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