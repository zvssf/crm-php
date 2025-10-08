<?php

require_once SYSTEM . '/main-data.php';
$page_title = 'Добавление города';
require_once SYSTEM . '/layouts/head.php';

$active_categories = array_filter($categories, function ($category) {
  return $category['category_status'] == 1;
});

$active_inputs = array_filter($inputs, function ($input) {
  return $input['input_status'] == 1;
});

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
                    <li class="breadcrumb-item"><a href="/?page=dashboard"><i class="uil-home-alt me-1"></i> Главная</a>
                    </li>
                    <li class="breadcrumb-item"><a href="/?page=settings-cities">Города</a></li>
                    <li class="breadcrumb-item active">Добавить город</li>
                  </ol>
                </div>
                <h4 class="page-title">Добавление города</h4>
              </div>
            </div>
          </div>
          <!-- end page title -->

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">


                  <form onsubmit="sendNewCityForm('#form-new-city button[type=submit]')" id="form-new-city"
                    class="needs-validation" novalidate>

                    <div class="row">
                      <div class="col-xl-4">
                        <h5 class="mb-4 text-uppercase"><i class="mdi mdi-city-variant-outline me-1"></i> Основная
                          информация</h5>
                        <div class="mb-3">
                          <label for="city-name" class="form-label">Название города</label>
                          <input type="text" class="form-control" id="city-name" placeholder="Введите название города"
                            name="city-name" maxlength="100" data-toggle="maxlength" required>
                          <div class="invalid-feedback">Введите название города!</div>
                        </div>

                        <div class="mb-3">
                          <label for="city-category" class="form-label">Категория</label>
                          <input type="text" class="form-control" id="city-category"
                            placeholder="Например: Туристическая" name="city-category" maxlength="100"
                            data-toggle="maxlength">
                        </div>

                        <?php if ($countries): ?>
                          <div class="mb-3">
                            <label for="select-country" class="form-label">Страна</label>
                            <select id="select-country" class="form-control select2" data-toggle="select2"
                              name="select-country">
                              <option value="hide">Выберите страну...</option>
                              <?php foreach ($countries as $country):
                                if ($country['country_status'] == 1): ?>
                                  <option value="<?= $country['country_id'] ?>"><?= $country['country_name'] ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                          </div>
                        <?php else: ?>
                          <p class="text-danger">Стран нет!</p>
                        <?php endif; ?>

                        <div class="mb-3">
                          <label for="select-status" class="form-label">Статус города</label>
                          <select class="form-select" id="select-status" name="select-status">
                            <option value="1">Активен</option>
                            <option value="2">Заблокирован</option>
                          </select>
                        </div>
                      </div>

                      <div class="col-xl-4">
                        <h5 class="mb-4 text-uppercase"><i class="mdi mdi-sale me-1"></i> Прайс</h5>
                        <div class="mb-3">
                          <label for="cost-price" class="form-label">Себестоимость</label>
                          <input type="text" class="form-control" id="cost-price" name="cost_price" value="0.00"
                            data-toggle="touchspin" data-step="0.01" data-min="0" data-max="10000000" data-decimals="2"
                            data-bts-prefix="$">
                        </div>
                        <div class="mb-3">
                          <label for="min-sale-price" class="form-label">Мин. цена продажи</label>
                          <input type="text" class="form-control" id="min-sale-price" name="min_sale_price" value="0.00"
                            data-toggle="touchspin" data-step="0.01" data-min="0" data-max="10000000" data-decimals="2"
                            data-bts-prefix="$">
                        </div>
                      </div>

                      <div class="col-xl-4">
                        <h5 class="mb-4 text-uppercase"><i class="mdi mdi-view-list me-1"></i> Дополнительные поля</h5>
                        <div class="row">
                          <?php
                          if (!empty($active_inputs)):
                            foreach ($active_inputs as $input):
                              ?>
                              <div class="col-xl-6">
                                <div class="mb-3 form-check form-switch">
                                  <input type="checkbox" class="form-check-input" id="input-<?= $input['input_id'] ?>"
                                    name="inputs[]" value="<?= $input['input_id'] ?>">
                                  <label class="form-check-label"
                                    for="input-<?= $input['input_id'] ?>"><?= $input['input_name'] ?></label>
                                </div>
                              </div>
                              <?php
                            endforeach;
                          else:
                            ?>
                            <p class="text-muted">Активных дополнительных полей не найдено. Сначала <a
                                href="/?page=settings-inputs">добавьте поле</a>.</p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>

                    <div class="row mt-3">
                      <div class="col-12">
                        <h5 class="mb-4 text-uppercase"><i class="mdi mdi-truck-delivery-outline me-1"></i> Поставщики
                        </h5>
                        <div class="row">
                          <?php if (!empty($suppliers)): ?>
                            <?php foreach ($suppliers as $supplier): ?>
                              <div class="col-xl-3">
                                <div class="mb-3 form-check form-switch">
                                  <input type="checkbox" class="form-check-input" id="supplier-<?= $supplier['id'] ?>"
                                    name="suppliers[]" value="<?= $supplier['id'] ?>">
                                  <label class="form-check-label"
                                    for="supplier-<?= $supplier['id'] ?>"><?= $supplier['name'] ?></label>
                                </div>
                              </div>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <p class="text-muted">Активных поставщиков не найдено. Сначала <a
                                href="/?page=finance">добавьте поставщика</a>.</p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>

                    <div class="text-end">
                      <button class="btn btn-success mt-2" type="submit">
                        <span class="spinner-border spinner-border-sm me-1 btn-loader visually-hidden" role="status"
                          aria-hidden="true"></span>
                        <span class="btn-icon"><i class="mdi mdi-content-save me-1"></i> </span>
                        <span class="loader-text visually-hidden">Отправка...</span>
                        <span class="btn-text">Добавить</span>
                      </button>
                    </div>
                  </form>

                  <!-- end settings content-->


                </div> <!-- end card body -->
              </div> <!-- end card -->

            </div> <!-- end col-->


          </div>
          <!-- end row-->

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
  <!-- END wrapper -->


  <script>
    function sendNewCityForm(btn) {
      event.preventDefault();
      loaderBTN(btn, 'true');
      jQuery.ajax({
        url: '/?page=<?= $page ?>&form=new-city',
        type: 'POST',
        dataType: 'html',
        data: jQuery('#form-new-city').serialize(),
        success: function (response) {
          loaderBTN(btn, 'false');
          result = jQuery.parseJSON(response);
          if (result.success_type == 'message') {
            message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
          } else if (result.success_type == 'redirect') {
            redirect(result.url);
          }
        },
        error: function () {
          loaderBTN(btn, 'false');
          message('Ошибка', 'Ошибка отправки формы!', 'error', '');
        }
      });
    }
  </script>


  <?php require_once SYSTEM . '/layouts/scripts.php'; ?>


</body>

</html>