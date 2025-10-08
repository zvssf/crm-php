<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Список сотрудников';
require_once SYSTEM . '/layouts/head.php';

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("
        SELECT * 
        FROM `users` 
        WHERE `user_id` != :current_user_id 
        ORDER BY `user_id` ASC
    ");
    
    $stmt->execute([
        ':current_user_id' => $user_data['user_id']
    ]);

    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
    $customers = [];
}
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
                                            <li class="breadcrumb-item active">Сотрудники</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Список сотрудников</h4>
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
                                                <a href="/?page=new-customer" class="btn btn-success mb-2 me-1"><i class="mdi mdi-plus-circle me-2"></i> Добавить сотрудника</a>
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
                                                        <th>Имя, Фамилия</th>
                                                        <th>Email</th>
                                                        <th>Номер телефона</th>
                                                        <th>Должность</th>
                                                        <th>Баланс</th>
                                                        <th>Статус</th>
                                                        <th style="width: 75px;">Действия</th>
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                <?php if($customers): foreach($customers as $customer):
                                                $customer_group_text = match ($customer['user_group'] ?? '') {
                                                    1 => 'Директор',
                                                    2 => 'Руководитель',
                                                    3 => 'Менеджер',
                                                    4 => 'Агент',
                                                    default => 'Неизвестно'
                                                };

                                                [$customer_balance_css, $customer_balance_plus] = match (true) {
                                                    $customer['user_balance'] < 0  => ['danger', ''],
                                                    $customer['user_balance'] > 0  => ['success', ''],
                                                    default        => ['secondary', '']
                                                };
                                                
                                                [$customer_status_css, $customer_status_text] = match ($customer['user_status'] ?? '') {
                                                    0 => ['secondary', 'Удалённый'],
                                                    1 => ['success',   'Активен'],
                                                    2 => ['danger',    'Заблокирован'],
                                                    default => ['secondary', 'Неизвестно']
                                                };
                                                    ?>

                                                <tr>
                                                        <td>
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input" id="customCheck<?= $customer['user_id'] ?>">
                                                                <label class="form-check-label" for="customCheck<?= $customer['user_id'] ?>">&nbsp;</label>
                                                            </div>
                                                        </td>
                                                        <td><span class="text-body fw-semibold"><?= $customer['user_firstname'] ?> <?= $customer['user_lastname'] ?></span></td>
                                                        <td><?= $customer['user_login'] ?></td>
                                                        <td><?= $customer['user_tel'] ?></td>
                                                        <td><?= $customer_group_text ?></td>
                                                        <td>
                                                        <?php if($customer['user_group'] === 4): ?>
                                                            <span class="text-<?= $customer_balance_css ?> fw-semibold"><i class="mdi mdi-currency-usd"></i><?= $customer_balance_plus . number_format($customer['user_balance'], 2, '.', ' ') ?></span>
                                                            <?php else: ?>
                                                                -
                                                                <?php endif; ?>
                                                        </td>
                                                        <td><span class="badge badge-<?= $customer_status_css ?>-lighten"><?= $customer_status_text ?></span></td>
                    
                                                        <td>
                                                            <?php if($customer['user_status'] === 0): ?>
                                                                <a href="#" class="font-18 text-warning" onclick="sendRestoreCustomerForm('<?= $customer['user_id'] ?>')"><i class="mdi mdi-cached"></i></a>
                                                            <?php else: ?>
                                                            <a href="/?page=edit-customer&id=<?= $customer['user_id'] ?>" class="font-18 text-info me-2"><i class="uil uil-pen"></i></a>
                                                            <a href="#" class="font-18 text-danger" data-bs-toggle="modal" data-bs-target="#del-customer-modal" onclick="modalDelCustomerForm('<?= $customer['user_id'] ?>', '<?= $customer['user_firstname'] ?> <?= $customer['user_lastname'] ?>')"><i class="uil uil-trash"></i></a>
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
<div id="del-customer-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content modal-filled bg-danger">
            <div class="modal-body p-4">
                <div class="text-center">
                    <i class="ri-delete-bin-5-line h1"></i>
                    <h4 class="mt-2">Удаление</h4>
                    <p class="mt-3">Вы уверены что хотите удалить сотрудника "<span class="span-user-name"></span>"?</p>
                    <button type="button" class="btn btn-light my-2" data-bs-dismiss="modal" attr-user-id="" onclick="sendDelCustomerForm()">Удалить</button>
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





            
            function modalDelCustomerForm(userid, username) {
                $('#del-customer-modal button').attr('attr-user-id', userid);
                $('#del-customer-modal .span-user-name').text(username);
            }
            function sendDelCustomerForm() {
                let userid = $('#del-customer-modal button').attr('attr-user-id');
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=del-customer',
                    type:     'POST',
                    dataType: 'html',
                    data:     '&user-id=' + userid,
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
            function sendRestoreCustomerForm(userid) {
                jQuery.ajax({
                    url:      '/?page=<?= $page ?>&form=restore-customer',
                    type:     'POST',
                    dataType: 'html',
                    data:     '&user-id=' + userid,
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
            // function sendProfileNewPassForm(btn) {
            //     loaderBTN(btn, 'true');
            //     jQuery.ajax({
            //         url:      'form',
            //         type:     'POST',
            //         dataType: 'html',
            //         data:     jQuery('#form-profile-new-password').serialize(),
            //         success:  function(response) {
            //             loaderBTN(btn, 'false');
            //             result = jQuery.parseJSON(response);
            //             if(result.success_type == 'message') {
            //                 message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
            //             } else if(result.success_type == 'redirect') {
            //                 redirect(result.url);
            //             }
            //         },
            //         error:  function() {
            //             loaderBTN(btn, 'false');
            //             message('Ошибка', 'Ошибка отправки формы!', 'error');
            //         }
            //     });
            // }
        </script>


        <?php
        require_once SYSTEM . '/layouts/scripts.php';
        ?>
        </body>
</html>