<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Финансы';
require_once SYSTEM . '/layouts/head.php';

try {
  $pdo = db_connect();
  
  $stmt = $pdo->query("
  SELECT * 
  FROM `fin_cashes` 
  ORDER BY `id` ASC
  ");
  
  $fin_cashes = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  $stmt = $pdo->query("
  SELECT * 
  FROM `fin_suppliers` 
  ORDER BY `id` ASC
  ");
  
  $fin_suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  
  $stmt = $pdo->query("
  SELECT
  t.id,
  t.transaction_date,
  t.operation_type,
  t.amount,
  t.cash_id,
  t.agent_id,
  t.supplier_id,
  t.comment,
  c.name AS cash_name,
  s.name AS supplier_name,
  u.user_id,
  u.user_firstname AS agent_firstname,
  u.user_lastname AS agent_lastname
  FROM fin_transactions t
  JOIN fin_cashes c ON t.cash_id = c.id
  LEFT JOIN users u ON t.agent_id = u.user_id
  LEFT JOIN fin_suppliers s ON t.supplier_id = s.id
  ORDER BY t.id ASC
  ");
  
  $fin_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  
  $stmt = $pdo->query("
  SELECT * 
  FROM `users` 
  WHERE `user_group` = '4' 
  AND `user_status` = '1' 
  ORDER BY `user_id` ASC
  ");
  
  $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
} catch (PDOException $e) {
  // $fin_cashes = [];
  // $fin_transactions = [];
  // $agents = [];
  // $fin_suppliers = [];
  error_log('DB Error: ' . $e->getMessage());
  exit('Произошла ошибка. Попробуйте позже.');
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
                    <li class="breadcrumb-item active">Финансы</li>
                  </ol>
                </div>
                <h4 class="page-title">Финансы</h4>
              </div>
            </div>
          </div>
          <!-- end page title -->
          
          <div class="row">
            <div class="col-xl-7">
              <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center pb-0">
                  <h5 class="text-uppercase"><i class="mdi mdi-cash me-1"></i> Транзакции</h5>
                  <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightTransaction" aria-controls="offcanvasRight" onclick="modalOnTransaction('new', '', '', '', '', '')" class="btn btn-sm btn-success"><i class="mdi mdi-plus"></i></a>
                </div>
                <div class="card-body pt-2">
                  
                  <div class="table-responsive">
                    <table class="table table-centered table-striped dt-responsive nowrap w-100" id="transactions-datatable">
                      <thead>
                        <tr>
                          <th>№</th>
                          <th>Транзакция</th>
                          <th>Дата</th>
                          <th>№ кассы</th>
                          <th>Тип</th>
                          <th>Сумма</th>
                          <th style="width: 75px;">Действия</th>
                        </tr>
                      </thead>
                      <tbody>
                        
                        <?php if($fin_transactions): foreach($fin_transactions as $transaction):
                          
                          [$raw_display_name] = match (true) {
                              !empty($transaction['supplier_id']) => [!empty($transaction['supplier_name']) ? $transaction['supplier_name'] : 'Поставщик #' . $transaction['supplier_id']],
                              !empty($transaction['agent_id']) => [($transaction['agent_firstname'] ?? '') . ' ' . ($transaction['agent_lastname'] ?? '')],
                              $transaction['operation_type'] == 2 => ['Нужды компании'],
                              default => [$transaction['cash_name'] ?? '']
                          };
                          
                          $transaction_display_name = valid($raw_display_name);
                          
                          [$transaction_amount_css, $transaction_amount_plus] = match (true) {
                            $transaction['amount'] < 0  => ['danger', ''],
                            $transaction['amount'] > 0  => ['success', '+'],
                            default        => ['secondary', '']
                          };
                          
                          [$operation_type_css, $operation_type_text] = match ($transaction['operation_type'] ?? '') {
                            0 => ['secondary',    'Отмена'],
                            1 => ['success', 'Приход'],
                            2 => ['danger',   'Расход'],
                            default => ['secondary', 'Неизвестно']
                          };
                          
                          $datetime = new DateTime($transaction['transaction_date']);
                          $datetime = $datetime->format('d.m.Y H:i:s');
                          $datetime = str_replace(' ', '<br>', $datetime);
                          ?>
                          
                          <tr>
                            <td><?= $transaction['id'] ?></td>
                            <td>
                              <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                  <?php
                                  $transaction_icon_class = match (true) {
                                      !empty($transaction['supplier_id']) => 'mdi mdi-robot',
                                      !empty($transaction['agent_id']) => 'ri-user-line',
                                      default => 'ri-money-dollar-circle-line'
                                  };
                                  ?>
                                  <i class="<?= $transaction_icon_class ?> font-18"></i>
                                  
                                </div>
                                <div class="flex-grow-1 ms-2">
                                  <?= $transaction_display_name ?>
                                </div>
                              </div>
                              <?php
                              if (!empty($transaction['comment'])) {
                                  $comment = valid($transaction['comment']);
                                  $short_comment = mb_strlen($comment) > 30 ? mb_substr($comment, 0, 30) . '...' : $comment;
                                  echo '<div class="text-muted" title="' . $comment . '"><em>' . $short_comment . '</em></div>';
                              }
                              ?>
                            </td>
                            <td><?= $datetime ?></td>
                            <td><?= $transaction['cash_name'] ?></td>
                            <td><span class="badge badge-<?= $operation_type_css ?>-lighten"><?= $operation_type_text ?></span></td>
                            <td><span class="text-<?= $transaction_amount_css ?> fw-semibold"><i class="mdi mdi-currency-usd"></i><?= $transaction_amount_plus . number_format($transaction['amount'], 2, '.', ' ') ?></span></td>
                            
                            <td>
                              <?php if($transaction['operation_type'] === 0): ?>
                                <a href="#" class="font-18 text-warning" onclick="sendRestoreTransactionForm('<?= $transaction['id'] ?>')"><i class="mdi mdi-cached"></i></a>
                              <?php else: ?>
                                <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightTransaction" aria-controls="offcanvasRight" onclick="modalOnTransaction('edit', '<?= $transaction['id'] ?>', '<?= $transaction['operation_type'] ?>', '<?= str_replace(['-', '+'], '', $transaction['amount']) ?>', '<?= $transaction['cash_id'] ?>', '<?= $transaction['agent_id'] ?>', '<?= valid($transaction['comment'] ?? '') ?>')" class="font-18 text-info me-2"><i class="uil uil-pen"></i></a>
                                <a href="#" class="font-18 text-danger" data-bs-toggle="modal" data-bs-target="#del-transaction-modal" onclick="modalDelTransactionForm('<?= $transaction['id'] ?>', '<?= $transaction_display_name ?>')"><i class="uil uil-trash"></i></a>
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
            
            

            <div class="col-xl-5">
              <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center pb-0">
                  <h5 class="text-uppercase"><i class="mdi mdi-cash-register me-1"></i> Кассы</h5>
                  <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightCash" aria-controls="offcanvasRight" onclick="modalOnCash('new', '', '', '')" class="btn btn-sm btn-success"><i class="mdi mdi-plus"></i></a>
                </div>
                <div class="card-body pt-2">
                  
                  <div class="table-responsive">
                    <table class="table table-centered table-striped dt-responsive nowrap w-100" id="cashes-datatable">
                      <thead>
                        <tr>
                          <th>№</th>
                          <th>Название</th>
                          <th>Баланс</th>
                          <th>Статус</th>
                          <th style="width: 75px;">Действия</th>
                        </tr>
                      </thead>
                      <tbody>
                        
                        <?php if($fin_cashes): foreach($fin_cashes as $cash):
                          [$cash_status_css, $cash_status_text] = match ($cash['status'] ?? '') {
                            0 => ['secondary', 'Удалённый'],
                            1 => ['success',   'Активен'],
                            2 => ['danger',    'Заблокирован'],
                            default => ['secondary', 'Неизвестно']
                          };
                          [$cash_balance_css] = match (true) {
                            $cash['balance'] < 0  => ['danger'],
                            $cash['balance'] > 0  => ['success'],
                            default        => ['secondary']
                          };
                          
                          [$balance_css] = match ($cash['status'] ?? '') {
                            0  => ['badge badge-secondary-lighten'],
                            default        => ['text-' . $cash_balance_css . ' fw-semibold']
                          };
                          ?>
                          
                          <tr>
                            <td><?= $cash['id'] ?></td>
                            <td><?= $cash['name'] ?></td>
                            <td><span class="<?= $balance_css ?>"><i class="mdi mdi-currency-usd"></i><?= number_format($cash['balance'], 2, '.', ' ') ?></span></td>
                            <td><span class="badge badge-<?= $cash_status_css ?>-lighten"><?= $cash_status_text ?></span></td>
                            
                            <td>
                              <?php if($cash['status'] === 0): ?>
                                <a href="#" class="font-18 text-warning" onclick="sendRestoreCashForm('<?= $cash['id'] ?>')"><i class="mdi mdi-cached"></i></a>
                              <?php else: ?>
                                <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightCash" aria-controls="offcanvasRight" onclick="modalOnCash('edit', '<?= $cash['id'] ?>', '<?= $cash['name'] ?>', '<?= $cash['status'] ?>')" class="font-18 text-info me-2"><i class="uil uil-pen"></i></a>
                                <a href="#" class="font-18 text-danger" data-bs-toggle="modal" data-bs-target="#del-cash-modal" onclick="modalDelCashForm('<?= $cash['id'] ?>', '<?= $cash['name'] ?>')"><i class="uil uil-trash"></i></a>
                              <?php endif; ?>
                            </td>
                          </tr>
                          
                        <?php endforeach; endif; ?>
                        
                      </tbody>
                    </table>
                  </div>
                </div> <!-- end card-body-->
              </div> <!-- end card-->
              
              
              
              <!-- ПОСТАВЩИКИ -->
              <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center pb-0">
                  <h5 class="text-uppercase"><i class="mdi mdi-robot"></i> Поставщики</h5>
                  <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightSupplier" aria-controls="offcanvasRight" onclick="modalOnSupplier('new', '', '', '')" class="btn btn-sm btn-success"><i class="mdi mdi-plus"></i></a>
                </div>
                <div class="card-body pt-2">
                  <div class="table-responsive">
                    <table class="table table-centered table-striped dt-responsive nowrap w-100" id="suppliers-datatable">
                      <thead>
                        <tr>
                          <th>№</th>
                          <th>Название</th>
                          <th>Баланс</th>
                          <th>Статус</th>
                          <th style="width: 75px;">Действия</th>
                        </tr>
                      </thead>
                      <tbody>
                        
                        <?php if($fin_suppliers): foreach($fin_suppliers as $supplier):
                          [$supplier_status_css, $supplier_status_text] = match ($supplier['status'] ?? '') {
                            0 => ['secondary', 'Удалённый'],
                            1 => ['success',   'Активен'],
                            2 => ['danger',    'Заблокирован'],
                            default => ['secondary', 'Неизвестно']
                          };
                          [$supplier_balance_css] = match (true) {
                            $supplier['balance'] < 0  => ['danger'],
                            $supplier['balance'] > 0  => ['success'],
                            default        => ['secondary']
                          };
                          
                          [$balance_css] = match ($supplier['status'] ?? '') {
                            0  => ['badge badge-secondary-lighten'],
                            default        => ['text-' . $supplier_balance_css . ' fw-semibold']
                          };
                          ?>
                          
                          <tr>
                            <td><?= $supplier['id'] ?></td>
                            <td><?= $supplier['name'] ?></td>
                            <td><span class="<?= $balance_css ?>"><i class="mdi mdi-currency-usd"></i><?= number_format($supplier['balance'], 2, '.', ' ') ?></span></td>
                            <td><span class="badge badge-<?= $supplier_status_css ?>-lighten"><?= $supplier_status_text ?></span></td>
                            
                            <td>
                              <?php if($supplier['status'] === 0): ?>
                                <a href="#" class="font-18 text-warning" onclick="sendRestoreSupplierForm('<?= $supplier['id'] ?>')"><i class="mdi mdi-cached"></i></a>
                              <?php else: ?>
                                <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightSupplier" aria-controls="offcanvasRight" onclick="modalOnSupplier('edit', '<?= $supplier['id'] ?>', '<?= $supplier['name'] ?>', '<?= $supplier['status'] ?>')" class="font-18 text-info me-2"><i class="uil uil-pen"></i></a>
                                <a href="#" class="font-18 text-danger" data-bs-toggle="modal" data-bs-target="#del-supplier-modal" onclick="modalDelSupplierForm('<?= $supplier['id'] ?>', '<?= $supplier['name'] ?>')"><i class="uil uil-trash"></i></a>
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
      <div id="del-transaction-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm">
          <div class="modal-content modal-filled bg-danger">
            <div class="modal-body p-4">
              <div class="text-center">
                <i class="ri-delete-bin-5-line h1"></i>
                <h4 class="mt-2">Удаление</h4>
                <p class="mt-3">Вы уверены что хотите удалить транзакцию "<span class="span-transaction-name"></span>"?</p>
                <button type="button" class="btn btn-light my-2" data-bs-dismiss="modal" attr-transaction-id="" onclick="sendDelTransactionForm()">Удалить</button>
              </div>
            </div>
          </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
      </div><!-- /.modal -->
      
      
      
      
      
      <!-- Danger Alert Modal -->
      <div id="del-cash-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm">
          <div class="modal-content modal-filled bg-danger">
            <div class="modal-body p-4">
              <div class="text-center">
                <i class="ri-delete-bin-5-line h1"></i>
                <h4 class="mt-2">Удаление</h4>
                <p class="mt-3">Вы уверены что хотите удалить кассу "<span class="span-cash-name"></span>"?</p>
                <button type="button" class="btn btn-light my-2" data-bs-dismiss="modal" attr-cash-id="" onclick="sendDelCashForm()">Удалить</button>
              </div>
            </div>
          </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
      </div><!-- /.modal -->
      
      <!-- Danger Alert Modal -->
      <div id="del-supplier-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm">
          <div class="modal-content modal-filled bg-danger">
            <div class="modal-body p-4">
              <div class="text-center">
                <i class="ri-delete-bin-5-line h1"></i>
                <h4 class="mt-2">Удаление</h4>
                <p class="mt-3">Вы уверены что хотите удалить поставщика "<span class="span-supplier-name"></span>"?</p>
                <button type="button" class="btn btn-light my-2" data-bs-dismiss="modal" attr-supplier-id="" onclick="sendDelSupplierForm()">Удалить</button>
              </div>
            </div>
          </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
      </div><!-- /.modal -->
      
      
      
      
      
      
      
      <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRightTransaction" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header">
          <h5 id="offcanvasRightLabel"></h5>
          <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div> <!-- end offcanvas-header-->
        
        <div class="offcanvas-body">
          
          <form onsubmit="sendTransactionForm('#form-transaction button[type=submit]')" id="form-transaction" class="needs-validation" novalidate>
            
            <input type="text" class="visually-hidden" name="transaction-edit-id" value="">
            
            <div class="block-select-operation-type">
              <div class="mb-3">
                <label for="select-operation-type" class="form-label">Тип транзакции</label>
                <select class="form-select" id="select-operation-type" name="select-operation-type">
                  <option value="1">Приход</option>
                  <option value="2">Расход</option>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label for="amount" class="form-label">Сумма транзакции</label>
              <input type="text" class="form-control" id="amount" placeholder="Введите сумму транзакции" name="amount" value="" data-toggle="touchspin" data-step="0.1" data-min="0" data-max="1000000" data-decimals="2" data-bts-prefix="$" required>
              <div class="invalid-feedback">Введите сумму транзакции!</div>
            </div>
            
            <div class="block-select-cash">
              <?php if($fin_cashes): ?>
                <div class="mb-3">
                  <label for="select-cash" class="form-label">Касса</label>
                  <select id="select-cash" class="form-control select2" data-toggle="select2" name="select-cash">
                    <option value="hide">Выберите кассу...</option>
                    <?php foreach($fin_cashes as $cash): if($cash['status'] === 1): ?>
                      <option value="<?= $cash['id'] ?>"><?= $cash['name'] ?></option>
                    <?php endif; endforeach; ?>
                  </select>
                </div>
                
              <?php else: ?>
                <p class="text-danger">Касс нет!</p>
              <?php endif; ?>
            </div>
            <div class="block-select-agents">
              
              <?php if($agents): ?>
                <div class="mb-3">
                  <label for="select-agent" class="form-label">Агент</label>
                  <select id="select-agent" class="form-control select2" data-toggle="select2" name="select-agent">
                    <option value="hide">Выберите агента...</option>
                    <?php foreach($agents as $agent): ?>
                      <option value="<?= $agent['user_id'] ?>"><?= $agent['user_firstname'] ?> <?= $agent['user_lastname'] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
              <?php else: ?>
                <p class="text-danger">Агентов нет!</p>
              <?php endif; ?>
            </div>
            
            <div class="block-select-suppliers visually-hidden">
              
              <?php if($fin_suppliers): ?>
                <div class="mb-3">
                  <label for="select-supplier" class="form-label">Поставщик</label>
                  <select id="select-supplier" class="form-control select2" data-toggle="select2" name="select-supplier">
                    <option value="hide">Выберите поставщика...</option>
                    <?php foreach($fin_suppliers as $supplier): if($supplier['status'] === 1): ?>
                      <option value="<?= $supplier['id'] ?>"><?= $supplier['name'] ?></option>
                    <?php endif; endforeach; ?>
                  </select>
                </div>
                
              <?php else: ?>
                <p class="text-danger">Поставщиков нет!</p>
              <?php endif; ?>
            </div>                                  
            
            <div class="mb-3">
              <label for="transaction-comment" class="form-label">Комментарий</label>
              <textarea class="form-control" id="transaction-comment" name="transaction-comment" rows="3" placeholder="Введите комментарий..." maxlength="256" data-toggle="maxlength"></textarea>
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
      
      
      
      
      
      
      <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRightCash" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header">
          <h5 id="offcanvasRightLabel"></h5>
          <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div> <!-- end offcanvas-header-->
        
        <div class="offcanvas-body">
          
          
          
          
          <form onsubmit="sendCashForm('#form-cash button[type=submit]')" id="form-cash" class="needs-validation" novalidate>
            
            <input type="text" class="visually-hidden" name="cash-edit-id" value="">
            
            <div class="mb-3">
              <label for="cash-name" class="form-label">Название кассы</label>
              <input type="text" class="form-control" id="cash-name" placeholder="Введите название кассы" name="cash-name" value="" maxlength="25" data-toggle="maxlength" required>
              <div class="invalid-feedback">Введите название кассы!</div>
            </div>
            <div class="mb-3">
              <label for="select-cash-status" class="form-label">Статус кассы</label>
              <select class="form-select" id="select-cash-status" name="select-cash-status">
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
      
      
      
      
      
      
      
      
      <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRightSupplier" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header">
          <h5 id="offcanvasRightLabel"></h5>
          <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div> <!-- end offcanvas-header-->
        
        <div class="offcanvas-body">
          <form onsubmit="sendSupplierForm('#form-supplier button[type=submit]')" id="form-supplier" class="needs-validation" novalidate>
            
            <input type="text" class="visually-hidden" name="supplier-edit-id" value="">
            
            <div class="mb-3">
              <label for="supplier-name" class="form-label">Название поставщика</label>
              <input type="text" class="form-control" id="supplier-name" placeholder="Введите название поставщика" name="supplier-name" value="" maxlength="25" data-toggle="maxlength" required>
              <div class="invalid-feedback">Введите название поставщика!</div>
            </div>
            <div class="mb-3">
              <label for="select-supplier-status" class="form-label">Статус поставщика</label>
              <select class="form-select" id="select-supplier-status" name="select-supplier-status">
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
    
    
    
    
    
    
    function modalDelTransactionForm(transactionid, transactionname) {
      $('#del-transaction-modal button').attr('attr-transaction-id', transactionid);
      $('#del-transaction-modal .span-transaction-name').text(transactionname);
    }
    function sendDelTransactionForm() {
      let transactionid = $('#del-transaction-modal button').attr('attr-transaction-id');
      jQuery.ajax({
        url:      '/?page=<?= $page ?>&form=del-transaction',
        type:     'POST',
        dataType: 'html',
        data:     '&transaction-id=' + transactionid,
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
    function sendRestoreTransactionForm(transactionid) {
      jQuery.ajax({
        url:      '/?page=<?= $page ?>&form=restore-transaction',
        type:     'POST',
        dataType: 'html',
        data:     '&transaction-id=' + transactionid,
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
    
    
    function modalOnTransaction(type, id, operation_type, amount, cash_id, agent_id, comment) {
      let modalTitle = $('#offcanvasRightTransaction .offcanvas-header h5');
      let transactionId = $('#form-transaction input[name="transaction-edit-id"]');
      let transactionAmount = $('#form-transaction #amount');
      let transactionComment = $('#form-transaction #transaction-comment');
      let btn = $('#form-transaction button[type=submit] .btn-text');
      let cssClass = 'visually-hidden';

      // Уничтожаем старые экземпляры Select2 и инициализируем новые
      $('#form-transaction .select2').each(function() {
          if ($(this).data('select2')) {
              $(this).select2('destroy');
          }
          $(this).select2({
              dropdownParent: $('#offcanvasRightTransaction')
          });
      });
      
      let blockOperationType = $('.block-select-operation-type');
      let blockCash = $('.block-select-cash');
      let blockAgents = $('.block-select-agents');
      let blockSuppliers = $('.block-select-suppliers');
      
      modalTitle.text('');
      transactionId.val('');
      transactionAmount.val('');
      transactionComment.val('');
      $('#form-transaction #select-operation-type option, #form-transaction #select-cash-status option').prop('selected', false);
      $('#form-transaction #select-cash, #form-transaction #select-agent, #form-transaction #select-supplier').val('hide').trigger('change');
      
      if (type == 'new') {
        modalTitle.text('Добавление транзакции');
        btn.text('Добавить');
        $('#form-transaction #select-operation-type option[value="1"]').prop('selected', true).trigger('change');
        
        blockOperationType.removeClass(cssClass);
        blockCash.removeClass(cssClass);
        blockAgents.removeClass(cssClass);
        
        if (!blockSuppliers.hasClass(cssClass)) {
          blockSuppliers.addClass(cssClass);
        }
        
      } else if (type == 'edit') {
        modalTitle.text('Редактирование транзакции');
        btn.text('Сохранить');
        
        transactionId.val(id);
        transactionAmount.val(amount);
        transactionComment.val(comment);
        
        blockOperationType.addClass(cssClass);
        blockCash.addClass(cssClass);
        blockAgents.addClass(cssClass);
        blockSuppliers.addClass(cssClass);
      }
    }
    
    
    
    
    
    
    
    function sendTransactionForm(btn) {
      event.preventDefault();
      loaderBTN(btn, 'true');
      let transactionId = $('#form-transaction input[name="transaction-edit-id"]').val();
      let typeForm;
      if(transactionId) {
        typeForm = 'edit-transaction';
      } else {
        typeForm = 'new-transaction';
      }
      jQuery.ajax({
        url:      '/?page=<?= $page ?>&form=' + typeForm,
        type:     'POST',
        dataType: 'html',
        data:     jQuery('#form-transaction').serialize(),
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
    
    
    function modalDelCashForm(cashid, cashname) {
      $('#del-cash-modal button').attr('attr-cash-id', cashid);
      $('#del-cash-modal .span-cash-name').text(cashname);
    }
    function sendDelCashForm() {
      let cashid = $('#del-cash-modal button').attr('attr-cash-id');
      jQuery.ajax({
        url:      '/?page=<?= $page ?>&form=del-cash',
        type:     'POST',
        dataType: 'html',
        data:     '&cash-id=' + cashid,
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
    function sendRestoreCashForm(cashid) {
      jQuery.ajax({
        url:      '/?page=<?= $page ?>&form=restore-cash',
        type:     'POST',
        dataType: 'html',
        data:     '&cash-id=' + cashid,
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
    
    
    function modalOnCash(type, id, name, status) {
      let modalTitle = $('#offcanvasRightCash .offcanvas-header h5');
      let cashId = $('#form-cash input[name="cash-edit-id"]');
      let cashName = $('#form-cash #cash-name');
      let btn = $('#form-cash button[type=submit] .btn-text');
      $('#form-cash #select-cash-status option').prop('selected', false);
      
      
      modalTitle.text('');
      cashId.val('');
      cashName.val('');
      
      if(type == 'new') {
        modalTitle.text('Добавление кассы');
        $('#form-cash #select-cash-status option[value="1"]').prop('selected', true);
        btn.text('Добавить');
      } else if(type == 'edit') {
        modalTitle.text('Редактирование кассы');
        cashId.val(id);
        cashName.val(name);
        $('#form-cash #select-cash-status option[value="' + status + '"]').prop('selected', true);
        btn.text('Сохранить');
      }
    }
    
    
    
    function sendCashForm(btn) {
      event.preventDefault();
      loaderBTN(btn, 'true');
      let cashId = $('#form-cash input[name="cash-edit-id"]').val();
      let typeForm;
      if(cashId) {
        typeForm = 'edit-cash';
      } else {
        typeForm = 'new-cash';
      }
      jQuery.ajax({
        url:      '/?page=<?= $page ?>&form=' + typeForm,
        type:     'POST',
        dataType: 'html',
        data:     jQuery('#form-cash').serialize(),
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
    
    function modalDelSupplierForm(supplierid, suppliername) {
      $('#del-supplier-modal button').attr('attr-supplier-id', supplierid);
      $('#del-supplier-modal .span-supplier-name').text(suppliername);
    }
    function sendDelSupplierForm() {
      let supplierid = $('#del-supplier-modal button').attr('attr-supplier-id');
      jQuery.ajax({
        url:      '/?page=<?= $page ?>&form=del-supplier',
        type:     'POST',
        dataType: 'html',
        data:     '&supplier-id=' + supplierid,
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
    function sendRestoreSupplierForm(supplierid) {
      jQuery.ajax({
        url:      '/?page=<?= $page ?>&form=restore-supplier',
        type:     'POST',
        dataType: 'html',
        data:     '&supplier-id=' + supplierid,
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
    
    
    function modalOnSupplier(type, id, name, status) {
      let modalTitle = $('#offcanvasRightSupplier .offcanvas-header h5');
      let supplierId = $('#form-supplier input[name="supplier-edit-id"]');
      let supplierName = $('#form-supplier #supplier-name');
      let btn = $('#form-supplier button[type=submit] .btn-text');
      $('#form-supplier #select-supplier-status option').prop('selected', false);
      
      modalTitle.text('');
      supplierId.val('');
      supplierName.val('');
      
      if(type == 'new') {
        modalTitle.text('Добавление поставщика');
        $('#form-supplier #select-supplier-status option[value="1"]').prop('selected', true);
        btn.text('Добавить');
      } else if(type == 'edit') {
        modalTitle.text('Редактирование поставщика');
        supplierId.val(id);
        supplierName.val(name);
        $('#form-supplier #select-supplier-status option[value="' + status + '"]').prop('selected', true);
        btn.text('Сохранить');
      }
    }
    
    
    function sendSupplierForm(btn) {
      event.preventDefault();
      loaderBTN(btn, 'true');
      let supplierId = $('#form-supplier input[name="supplier-edit-id"]').val();
      let typeForm;
      if(supplierId) {
        typeForm = 'edit-supplier';
      } else {
        typeForm = 'new-supplier';
      }
      jQuery.ajax({
        url:      '/?page=<?= $page ?>&form=' + typeForm,
        type:     'POST',
        dataType: 'html',
        data:     jQuery('#form-supplier').serialize(),
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
  </script>
  
  
  <?php
  require_once SYSTEM . '/layouts/scripts.php';
  ?>
  <script>
    $('#form-transaction #select-operation-type').on('change', function() {
      let el = $(this).val();
      let blockAgents = $('.block-select-agents');
      let blockSuppliers = $('.block-select-suppliers'); 
      let cssClass = 'visually-hidden';
      if(el == '2') {
        if(!blockAgents.hasClass(cssClass)) {
          blockAgents.addClass(cssClass);
        }
        if(blockSuppliers.hasClass(cssClass)) {
          blockSuppliers.removeClass(cssClass);
        }
      } else {
        if(blockAgents.hasClass(cssClass)) {
          blockAgents.removeClass(cssClass);
        }
        if(!blockSuppliers.hasClass(cssClass)) {
          blockSuppliers.addClass(cssClass);
        }
      }
    });
    
    document.getElementById('amount').addEventListener('keydown', function(event) {
      if (event.key === '-' || event.key === '+') {
        event.preventDefault();
      }
    });
  </script>
</body>
</html>