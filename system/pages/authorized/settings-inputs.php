<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Список дополнительных полей';
require_once SYSTEM . '/layouts/head.php';

try {
  $pdo = db_connect();

  $stmt = $pdo->query("
  SELECT * 
  FROM `settings_inputs` 
  ORDER BY `input_id` ASC
  ");
  $inputs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  error_log('DB Error: ' . $e->getMessage());
  message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');;
  $inputs = [];
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
                    <li class="breadcrumb-item active">Дополнительные поля</li>
                  </ol>
                </div>
                <h4 class="page-title">Список дополнительных полей</h4>
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
                      <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightInput" aria-controls="offcanvasRight" onclick="modalOnInput('new', '', '', '', '')" class="btn btn-success mb-2 me-1"><i class="mdi mdi-plus-circle me-2"></i> Добавить дополнительное поле</a>
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
                        <th>Тип поля</th>
                        <th>Статус</th>
                        <th style="width: 75px;">Действия</th>
                      </tr>
                    </thead>
                    <tbody>
                      
                      <?php if($inputs): foreach($inputs as $input):
                        [$input_status_css, $input_status_text] = match ($input['input_status'] ?? '') {
                          0 => ['secondary', 'Удалённый'],
                          1 => ['success',   'Активен'],
                          default => ['secondary', 'Неизвестно']
                      };
                      
                      $input_type_text = match ($input['input_type'] ?? '') {
                          1 => 'Текстовое поле',
                          2 => 'Выпадающий список',
                          3 => 'Выбор значения',
                          default => 'Неизвестный тип'
                      };
                        ?>
                        
                        <tr>
                          <td>
                            <div class="form-check">
                              <input type="checkbox" class="form-check-input dt-checkboxes" id="customCheck<?= $input['input_id'] ?>">
                              <label class="form-check-label" for="customCheck<?= $input['input_id'] ?>">&nbsp;</label>
                            </div>
                          </td>
                          <td>
                            <span style="display:none;"><?= $input['input_id'] ?></span>
                            <span class="text-body fw-semibold"><?= $input['input_name'] ?></span>
                        </td>
                          <td><span class="text-body fw-semibold"><?= $input_type_text ?></span></td>
                          <td><span class="badge badge-<?= $input_status_css ?>-lighten"><?= $input_status_text ?></span></td>
                          
                          <td>
                            <?php if($input['input_status'] === 0): ?>
                              <a href="#" class="font-18 text-warning" onclick="sendRestoreInputForm('<?= $input['input_id'] ?>')"><i class="mdi mdi-cached"></i></a>
                            <?php else: ?>
                              <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightInput" aria-controls="offcanvasRight" onclick="modalOnInput('edit', '<?= $input['input_id'] ?>', '<?= $input['input_name'] ?>', '<?= $input['input_type'] ?>', '<?= $input['input_select_data'] ?>')" class="font-18 text-info me-2"><i class="uil uil-pen"></i></a>
                              <a href="#" class="font-18 text-danger" data-bs-toggle="modal" data-bs-target="#del-input-modal" onclick="modalDelInputForm('<?= $input['input_id'] ?>', '<?= $input['input_name'] ?>')"><i class="uil uil-trash"></i></a>
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
    <div id="del-input-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-sm">
        <div class="modal-content modal-filled bg-danger">
          <div class="modal-body p-4">
            <div class="text-center">
              <i class="ri-delete-bin-5-line h1"></i>
              <h4 class="mt-2">Удаление</h4>
              <p class="mt-3">Вы уверены что хотите удалить дополнительное поле "<span class="span-input-name"></span>"?</p>
              <button type="button" class="btn btn-light my-2" data-bs-dismiss="modal" attr-input-id="" onclick="sendDelInputForm()">Удалить</button>
            </div>
          </div>
        </div><!-- /.modal-content -->
      </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    
    
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRightInput" aria-labelledby="offcanvasRightLabel">
      <div class="offcanvas-header">
        <h5 id="offcanvasRightLabel"></h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div> <!-- end offcanvas-header-->
      
      <div class="offcanvas-body">
        
        
        
        
        <form onsubmit="sendInputForm('#form-input button[type=submit]')" id="form-input" class="needs-validation" novalidate>
          
          <input type="text" class="visually-hidden" name="input-edit-id" value="">
          
          <div class="mb-3">
            <label for="input-name" class="form-label">Название дополнительного поля</label>
            <input type="text" class="form-control" id="input-name" placeholder="Введите название дополнительного поля" name="input-name" value="" maxlength="25" data-toggle="maxlength" required>
            <div class="invalid-feedback">Введите название дополнительного поля!</div>
          </div>
          
          
          
          
          <div class="mb-3">
            <label for="select-input-type" class="form-label">Тип дополнительного поля</label>
            <select class="form-select" id="select-input-type" name="select-input-type">
              <option value="1">Текстовое поле</option>
              <option value="2">Выпадающий список</option>
              <option value="3">Выбор значения</option>
            </select>
          </div>
          
          
          
          <div class="mb-3 block-select-data visually-hidden">
            <label for="input-select-data" class="form-label">Значения</label>
            <textarea class="form-control" id="input-select-data" placeholder="Введите значения" name="input-select-data" value="" data-toggle="maxlength" maxlength="128" required></textarea>
            <p class="text-info font-11 mt-1 mb-0">Формат строки (значения разделять символом "|"): <br>Значение 1|Значение 2</p>
            <div class="invalid-feedback">Введите значения!</div>
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
    function modalOnInput(type, id, name, inputType, selectData) {
        let modalTitle = $('#offcanvasRightInput .offcanvas-header h5');
        let inputId = $('#form-input input[name="input-edit-id"]');
        let inputName = $('#form-input #input-name');
        $('#form-input #select-input-type option').prop('selected', false);
        let inputSelectData = $('#form-input #input-select-data');
        
        modalTitle.text('');
        inputId.val('');
        inputName.val('');
        inputSelectData.val('');
        
        let blockSelectData = $('.block-select-data');
        let cssClass = 'visually-hidden';
        if(!blockSelectData.hasClass(cssClass)) {
            blockSelectData.addClass(cssClass);
        }
        
        let btn = $('#form-input button[type=submit] .btn-text');
        if(type == 'new') {
            modalTitle.text('Добавление дополнительного поля');
            $('#form-input #select-input-type option[value="1"]').prop('selected', true);
            btn.text('Добавить');
        } else if(type == 'edit') {
            modalTitle.text('Редактирование дополнительного поля');
            inputId.val(id);
            inputName.val(name);
            $('#form-input #select-input-type option[value="' + inputType + '"]').prop('selected', true);
            if(inputType == '2' || inputType == '3') {
            blockSelectData.removeClass(cssClass);
            }
            inputSelectData.val(selectData);
            btn.text('Сохранить');
        }
    }

    function sendInputForm(btn) {
        event.preventDefault();
        loaderBTN(btn, 'true');
        let inputId = $('#form-input input[name="input-edit-id"]').val();
        let typeForm = inputId ? 'edit-input' : 'new-input';

        jQuery.ajax({
            url:      '/?page=<?= $page ?>&form=' + typeForm,
            type:     'POST',
            dataType: 'html',
            data:     jQuery('#form-input').serialize(),
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

    function modalDelInputForm(inputid, inputname) {
        $('#del-input-modal button').attr('attr-input-id', inputid);
        $('#del-input-modal .span-input-name').text(inputname);
    }

    function sendDelInputForm() {
        let inputid = $('#del-input-modal button').attr('attr-input-id');
        jQuery.ajax({
            url:      '/?page=<?= $page ?>&form=del-input',
            type:     'POST',
            dataType: 'html',
            data:     '&input-id=' + inputid,
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

    function sendRestoreInputForm(inputid) {
        jQuery.ajax({
            url:      '/?page=<?= $page ?>&form=restore-input',
            type:     'POST',
            dataType: 'html',
            data:     '&input-id=' + inputid,
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
            message('Внимание', 'Пожалуйста, выберите хотя бы одно дополнительное поле.', 'warning');
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
                    url: '/?form=mass-input-action',
                    type: 'POST',
                    dataType: 'json',
                    data: 'action=' + action + '&' + $.param({ 'input_ids': selectedIds }),
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

<script>
$('#select-input-type').on('change', function() {
    let el = $(this).val();
    let blockSelectData = $('.block-select-data');
    let label = blockSelectData.find('label');
    let textarea = blockSelectData.find('textarea');
    let helperText = blockSelectData.find('p');
    let invalidFeedback = blockSelectData.find('.invalid-feedback');
    let cssClass = 'visually-hidden';

    if (el == '2') { // Выпадающий список
        label.text('Выпадающие пункты');
        textarea.attr('placeholder', 'Введите выпадающие пункты');
        helperText.html('Формат строки (пункты разделять символом "|"): <br>Пункт 1|Пункт 2|Пункт 3');
        invalidFeedback.text('Введите выпадающие пункты!');
        blockSelectData.removeClass(cssClass);
    } else if (el == '3') { // Выбор значения
        label.text('Значения');
        textarea.attr('placeholder', 'Введите значения');
        helperText.html('Формат строки (значения разделять символом "|"): <br>Да|Нет');
        invalidFeedback.text('Введите значения!');
        blockSelectData.removeClass(cssClass);
    } else {
        if (!blockSelectData.hasClass(cssClass)) {
            blockSelectData.addClass(cssClass);
        }
    }
});

// Вызываем событие change при загрузке страницы, чтобы установить правильные подписи при редактировании
$('#select-input-type').trigger('change');
</script>
</body>
</html>