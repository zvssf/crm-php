<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Правила обработки PDF';
require_once SYSTEM . '/layouts/head.php';

try {
    $pdo = db_connect();

    // Получаем все визовые центры и объединяем их с существующими правилами
    $stmt = $pdo->query("
        SELECT
            sc.center_id,
            sc.center_name,
            sc.country_id,
            sc.center_status,
            pr.rule_id,
            pr.center_identifier_text,
            pr.passport_mask,
            pr.rule_status
        FROM `settings_centers` sc
        LEFT JOIN `pdf_parsing_rules` pr ON sc.center_id = pr.center_id
        WHERE sc.center_status > 0
        ORDER BY sc.center_name ASC
    ");
    $centers_with_rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    exit('Произошла ошибка при загрузке данных. Попробуйте позже.');
}
?>

<body>
    <div class="wrapper">
        <?php require_once SYSTEM . '/layouts/menu.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="/?page=dashboard"><i class="uil-home-alt me-1"></i> Главная</a></li>
                                        <li class="breadcrumb-item active">Правила обработки PDF</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Правила обработки PDF</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-centered table-striped dt-responsive nowrap w-100" id="pdf-rules-datatable">
                                            <thead>
                                                <tr>
                                                    <th>Визовый центр</th>
                                                    <th>Страна</th>
                                                    <th>Статус правила</th>
                                                    <th style="width: 120px;">Действия</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($centers_with_rules) : foreach ($centers_with_rules as $item) :
                                                    $has_rule = !empty($item['rule_id']);
                                                    $rule_status_css = $has_rule ? 'success' : 'secondary';
                                                    $rule_status_text = $has_rule ? 'Настроено' : 'Не настроено';
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <span class="text-body fw-semibold"><?= valid($item['center_name']) ?></span>
                                                        </td>
                                                        <td><span class="text-body fw-semibold"><?= $arr_countries[$item['country_id']] ?? 'Не указана' ?></span></td>
                                                        <td><span class="badge badge-<?= $rule_status_css ?>-lighten"><?= $rule_status_text ?></span></td>
                                                        <td>
                                                            <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRightPdfRule" aria-controls="offcanvasRight" 
                                                            onclick="modalOnPdfRule('<?= $item['center_id'] ?>', '<?= valid($item['center_name']) ?>', '<?= htmlspecialchars(valid($item['center_identifier_text'] ?? ''), ENT_QUOTES) ?>', '<?= htmlspecialchars(valid($item['passport_mask'] ?? ''), ENT_QUOTES) ?>')" 
                                                            class="font-18 text-info me-2" title="Настроить"><i class="uil-cog"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once SYSTEM . '/layouts/footer.php'; ?>
        </div>
    </div>

    <!-- Offcanvas для настройки правил -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRightPdfRule" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header">
            <h5 id="offcanvasRightLabel">Настройка правила</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form onsubmit="sendPdfRuleForm('#form-pdf-rule button[type=submit]')" id="form-pdf-rule" class="needs-validation" novalidate>
                <input type="hidden" name="center_id" value="">
                <h5 class="mb-3" id="rule-center-name"></h5>

                <div class="mb-3">
                    <label for="center-identifier-text" class="form-label">Уникальный текст для определения ВЦ</label>
                    <input type="text" class="form-control" id="center-identifier-text" placeholder="Напр: Group URN - FRR" name="center_identifier_text" required>
                    <div class="form-text">Скопируйте из PDF-файла фразу, которая всегда присутствует в документах этого ВЦ.</div>
                    <div class="invalid-feedback">Это поле обязательно!</div>
                </div>

                <div class="mb-3">
                    <label for="passport-mask" class="form-label">Маска для поиска номера паспорта</label>
                    <input type="text" class="form-control" id="passport-mask" placeholder="Напр: NNxxxxxxNN" name="passport_mask" oninput="this.value = this.value.replace(/[^Nx]/ig, '')">
                    <div class="form-text">Используйте 'N' для буквы/цифры и 'x' для пропущенного символа. Например, для `U8xxxxxx61` маска будет `NNxxxxxxNN`.</div>
                </div>

                <div class="d-flex justify-content-end">
                    <button class="btn btn-danger mt-2 me-2" type="button" onclick="sendDeletePdfRuleForm()">
                        <span class="btn-icon"><i class="mdi mdi-trash-can-outline me-1"></i></span>
                        <span class="btn-text">Сбросить</span>
                    </button>
                    <button class="btn btn-success mt-2" type="submit">
                        <span class="spinner-border spinner-border-sm me-1 btn-loader visually-hidden" role="status" aria-hidden="true"></span>
                        <span class="btn-icon"><i class="mdi mdi-content-save me-1"></i></span>
                        <span class="loader-text visually-hidden">Отправка...</span>
                        <span class="btn-text">Сохранить</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php require_once SYSTEM . '/layouts/scripts.php'; ?>
    
    <script>
        // Инициализация DataTable
        $(document).ready(function() {
            "use strict";
            $("#pdf-rules-datatable").DataTable({
                language: {
                    paginate: { previous: "<i class='mdi mdi-chevron-left'>", next: "<i class='mdi mdi-chevron-right'>" },
                    info: "Отображение правил с _START_ по _END_ из _TOTAL_.",
                    lengthMenu: 'Показывать <select class=\'form-select form-select-sm ms-1 me-1\'><option value="10">по 10</option><option value="20">по 20</option><option value="-1">Все</option></select>'
                },
                pageLength: 10,
                columns: [{ orderable: true }, { orderable: true }, { orderable: true }, { orderable: false }],
                order: [[0, "asc"]],
                drawCallback: function() {
                    $(".dataTables_paginate > .pagination").addClass("pagination-sm");
                    $("#pdf-rules-datatable_length label").addClass("form-label");
                }
            });
        });

        function modalOnPdfRule(centerId, centerName, identifier, mask) {
            $('#form-pdf-rule input[name="center_id"]').val(centerId);
            $('#rule-center-name').text('ВЦ: ' + centerName);
            $('#form-pdf-rule #center-identifier-text').val(identifier);
            $('#form-pdf-rule #passport-mask').val(mask);
        }

        function sendPdfRuleForm(btn) {
            event.preventDefault();
            loaderBTN(btn, 'true');
            jQuery.ajax({
                url: '/?page=settings-pdf-rules&form=edit-pdf-rule',
                type: 'POST',
                dataType: 'html',
                data: jQuery('#form-pdf-rule').serialize(),
                success: function(response) {
                    loaderBTN(btn, 'false');
                    result = jQuery.parseJSON(response);
                    if (result.success_type == 'message') {
                        message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
                    }
                },
                error: function() {
                    loaderBTN(btn, 'false');
                    message('Ошибка', 'Ошибка отправки формы!', 'error', '');
                }
            });
        }

        function sendDeletePdfRuleForm() {
            const centerId = $('#form-pdf-rule input[name="center_id"]').val();

            if (!centerId) {
                message('Ошибка', 'Не удалось определить ID визового центра.', 'error', '');
                return;
            }

            Swal.fire({
                title: 'Сбросить правило?',
                text: "Настройки для этого визового центра будут удалены. Вы уверены?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Да, сбросить!',
                cancelButtonText: 'Отмена'
            }).then((result) => {
                if (result.isConfirmed) {
                    jQuery.ajax({
                        url: '/?page=settings-pdf-rules&form=delete-pdf-rule',
                        type: 'POST',
                        dataType: 'html',
                        data: { center_id: centerId },
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
            });
        }
    </script>
</body>
</html>