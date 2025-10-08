<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Список визовых центров';
require_once SYSTEM . '/layouts/head.php';

// try {
//     $pdo = db_connect();

//     $stmt = $pdo->query("
//     SELECT *
//     FROM `settings_centers`
//     ORDER BY `center_id` ASC
//     ");
//     $centers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// } catch (PDOException $e) {
//     error_log('DB Error: ' . $e->getMessage());
//     message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
//     $centers = [];
// }
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
                        <th>Страна</th>
                        <th>Статус</th>
                        <th style="width: 75px;">Действия</th>
                      </tr>
                    </thead>
                    <tbody>

                      <?php if($centers): foreach($centers as $center):
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
                              <input type="checkbox" class="form-check-input" id="customCheck<?= $center['center_id'] ?>">
                              <label class="form-check-label" for="customCheck<?= $center['center_id'] ?>">&nbsp;</label>
                            </div>
                          </td>
                          <td><span class="text-body fw-semibold"><?= $center['center_name'] ?></span></td>
                          <td><span class="text-body fw-semibold"><?= $arr_countries[$center['country_id']] ?? 'Не указана' ?></span></td>
                          <td><span class="badge badge-<?= $center_status_css ?>-lighten"><?= $center_status_text ?></span></td>

                          <td>
                            <?php if($center['center_status'] === 0): ?>
                              <a href="#" class="font-18 text-warning" onclick="sendRestoreCenterForm('<?= $center['center_id'] ?>')"><i class="mdi mdi-cached"></i></a>
                            <?php else: ?>
                              <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightCenter" aria-controls="offcanvasRight" onclick="modalOnCenter('edit', '<?= $center['center_id'] ?>', '<?= $center['center_name'] ?>', '<?= $center['country_id'] ?>', '<?= $center['center_status'] ?>')" class="font-18 text-info me-2"><i class="uil uil-pen"></i></a>
                              <a href="#" class="font-18 text-danger" data-bs-toggle="modal" data-bs-target="#del-center-modal" onclick="modalDelCenterForm('<?= $center['center_id'] ?>', '<?= $center['center_name'] ?>')"><i class="uil uil-trash"></i></a>
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



    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRightCenter" aria-labelledby="offcanvasRightLabel">
      <div class="offcanvas-header">
        <h5 id="offcanvasRightLabel"></h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div> <!-- end offcanvas-header-->

      <div class="offcanvas-body">




        <form onsubmit="sendCenterForm('#form-center button[type=submit]')" id="form-center" class="needs-validation" novalidate>

          <input type="text" class="visually-hidden" name="center-edit-id" value="">

          <div class="mb-3">
            <label for="center-name" class="form-label">Название визового центра</label>
            <input type="text" class="form-control" id="center-name" placeholder="Введите название визового центра" name="center-name" value="" maxlength="25" data-toggle="maxlength" required>
            <div class="invalid-feedback">Введите название визового центра!</div>
          </div>

          <div class="mb-3">
            <label for="select-country" class="form-label">Страна</label>
            <select id="select-country" class="form-control select2" data-toggle="select2" name="select-country">
              <option value="hide">Выберите страну...</option>
              <?php foreach($countries as $country): if($country['country_status'] == 1): ?>
                <option value="<?= $country['country_id'] ?>"><?= $country['country_name'] ?></option>
              <?php endif; endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="select-center-status" class="form-label">Статус визового центра</label>
            <select class="form-select" id="select-center-status" name="select-center-status">
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

function modalOnCenter(type, id, name, country_id, status) {
  let modalTitle = $('#offcanvasRightCenter .offcanvas-header h5');
  let centerId = $('#form-center input[name="center-edit-id"]');
  let centerName = $('#form-center #center-name');
  let btn = $('#form-center button[type=submit] .btn-text');
  $('#form-center #select-center-status option').prop('selected', false);


  modalTitle.text('');
  centerId.val('');
  centerName.val('');

  if(type == 'new') {
    modalTitle.text('Добавление визового центра');
    $('#form-center #select-center-status option[value="1"]').prop('selected', true);
    btn.text('Добавить');
  } else if(type == 'edit') {
    modalTitle.text('Редактирование визового центра');
    centerId.val(id);
    centerName.val(name);
    $('#form-center #select-country').val(country_id).trigger('change');
    $('#form-center #select-center-status option[value="' + status + '"]').prop('selected', true);
    btn.text('Сохранить');
  }
}


function sendCenterForm(btn) {
  event.preventDefault();
  loaderBTN(btn, 'true');
  let centerId = $('#form-center input[name="center-edit-id"]').val();
  let typeForm;
  if(centerId) {
    typeForm = 'edit-center';
  } else {
    typeForm = 'new-center';
  }
  jQuery.ajax({
    url:      '/?page=<?= $page ?>&form=' + typeForm,
    type:     'POST',
    dataType: 'html',
    data:     jQuery('#form-center').serialize(),
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



function modalDelCenterForm(centerid, centername) {
  $('#del-center-modal button').attr('attr-center-id', centerid);
  $('#del-center-modal .span-center-name').text(centername);
}
function sendDelCenterForm() {
  let centerid = $('#del-center-modal button').attr('attr-center-id');
  jQuery.ajax({
    url:      '/?page=<?= $page ?>&form=del-center',
    type:     'POST',
    dataType: 'html',
    data:     '&center-id=' + centerid,
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
function sendRestoreCenterForm(centerid) {
  jQuery.ajax({
    url:      '/?page=<?= $page ?>&form=restore-center',
    type:     'POST',
    dataType: 'html',
    data:     '&center-id=' + centerid,
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
