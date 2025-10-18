<?php
$edit_city_id = valid($_GET['id'] ?? '');

if (empty($edit_city_id) || !preg_match('/^[0-9]{1,11}$/u', $edit_city_id)) {
    redirect('settings-cities');
}

require_once SYSTEM . '/main-data.php';
$page_title = 'Редактирование города';
require_once SYSTEM . '/layouts/head.php';

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("SELECT * FROM `settings_cities` WHERE `city_id` = :city_id");
    $stmt->execute([':city_id' => $edit_city_id]);
    $city_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$city_data) {
        exit('Город не найден!');
    }

    $stmt = $pdo->prepare("SELECT `input_id` FROM `settings_city_inputs` WHERE `city_id` = ?");
    $stmt->execute([$edit_city_id]);
    $saved_input_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'input_id');

    $active_inputs = array_filter($inputs, function ($input) {
        return $input['input_status'] == 1;
    });

    $stmt_suppliers = $pdo->prepare("SELECT `supplier_id` FROM `city_suppliers` WHERE `city_id` = ?");
    $stmt_suppliers->execute([$edit_city_id]);
    $saved_supplier_ids = array_column($stmt_suppliers->fetchAll(PDO::FETCH_ASSOC), 'supplier_id');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    exit('Ошибка при загрузке данных.');
}
?>

<body>
    <div class="wrapper">
        <?php require_once SYSTEM . '/layouts/menu.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="/?page=dashboard"><i
                                                    class="uil-home-alt me-1"></i> Главная</a></li>
                                        <li class="breadcrumb-item"><a href="/?page=settings-cities">Города</a></li>
                                        <li class="breadcrumb-item active">Редактировать город</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Редактирование города</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form onsubmit="sendEditCityForm('#form-edit-city button[type=submit]')"
                                        id="form-edit-city" class="needs-validation" novalidate>
                                        <input type="hidden" name="city-edit-id" value="<?= $city_data['city_id'] ?>">
                                        <div class="row">
                                            <div class="col-xl-4">
                                                <h5 class="mb-4 text-uppercase"><i
                                                        class="mdi mdi-city-variant-outline me-1"></i> Основная
                                                    информация</h5>
                                                <div class="mb-3">
                                                    <label for="city-name" class="form-label">Название города</label>
                                                    <input type="text" class="form-control" id="city-name"
                                                        name="city-name" value="<?= valid($city_data['city_name']) ?>"
                                                        required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="city-category" class="form-label">Категория</label>
                                                    <input type="text" class="form-control" id="city-category"
                                                        placeholder="Например: Туристическая" name="city-category"
                                                        value="<?= valid($city_data['city_category']) ?>"
                                                        maxlength="100" data-toggle="maxlength">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="select-country" class="form-label">Страна</label>
                                                    <select id="select-country" class="form-control select2"
                                                        data-toggle="select2" name="select-country">
                                                        <option value="hide">Выберите страну...</option>
                                                        <?php foreach ($countries as $country):
                                                            if ($country['country_status'] == 1): ?>
                                                                <option value="<?= $country['country_id'] ?>"
                                                                    <?= (isset($city_data) && $country['country_id'] == $city_data['country_id']) ? 'selected' : '' ?>><?= $country['country_name'] ?></option>
                                                            <?php endif; endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="select-status" class="form-label">Статус города</label>
                                                    <select class="form-select" id="select-status" name="select-status">
                                                        <option value="1" <?= ($city_data['city_status'] == 1) ? 'selected' : '' ?>>Активен</option>
                                                        <option value="2" <?= ($city_data['city_status'] == 2) ? 'selected' : '' ?>>Заблокирован</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-xl-4">
                                                <h5 class="mb-4 text-uppercase"><i class="mdi mdi-sale me-1"></i> Прайс
                                                </h5>
                                                <div class="mb-3">
                                                    <label for="cost-price" class="form-label">Себестоимость</label>
                                                    <input type="text" class="form-control" id="cost-price"
                                                        name="cost_price" value="<?= valid($city_data['cost_price']) ?>"
                                                        data-toggle="touchspin" data-step="0.01" data-min="0"
                                                        data-max="10000000" data-decimals="2" data-bts-prefix="$">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="min-sale-price" class="form-label">Мин. цена
                                                        продажи</label>
                                                    <input type="text" class="form-control" id="min-sale-price"
                                                        name="min_sale_price"
                                                        value="<?= valid($city_data['min_sale_price']) ?>"
                                                        data-toggle="touchspin" data-step="0.01" data-min="0"
                                                        data-max="10000000" data-decimals="2" data-bts-prefix="$">
                                                </div>
                                            </div>

                                            <div class="col-xl-4">
                                                <h5 class="mb-4 text-uppercase"><i class="mdi mdi-view-list me-1"></i>
                                                    Дополнительные поля</h5>
                                                <div class="row">
                                                    <?php
                                                    if (!empty($active_inputs)):
                                                        foreach ($active_inputs as $input):
                                                            $is_checked = in_array($input['input_id'], $saved_input_ids);
                                                            ?>
                                                            <div class="col-xl-6">
                                                                <div class="mb-3 form-check form-switch">
                                                                    <input type="checkbox" class="form-check-input"
                                                                        id="input-<?= $input['input_id'] ?>" name="inputs[]"
                                                                        value="<?= $input['input_id'] ?>" <?= $is_checked ? 'checked' : '' ?>>
                                                                    <label class="form-check-label"
                                                                        for="input-<?= $input['input_id'] ?>"><?= $input['input_name'] ?></label>
                                                                </div>
                                                            </div>
                                                            <?php
                                                        endforeach;
                                                    else:
                                                        ?>
                                                        <p class="text-muted">Активных дополнительных полей не найдено.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-xl-4">
                                                <h5 class="mb-4 text-uppercase"><i
                                                        class="mdi mdi-truck-delivery-outline me-1"></i> Поставщики</h5>
                                                <div class="row">
                                                    <?php if (!empty($suppliers)): ?>
                                                        <?php foreach ($suppliers as $supplier):
                                                            $is_checked = in_array($supplier['id'], $saved_supplier_ids);
                                                            ?>
                                                            <div class="col-xl-6">
                                                                <div class="mb-3 form-check form-switch">
                                                                    <input type="checkbox" class="form-check-input"
                                                                        id="supplier-<?= $supplier['id'] ?>" name="suppliers[]"
                                                                        value="<?= $supplier['id'] ?>" <?= $is_checked ? 'checked' : '' ?>>
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

                                <div class="row">
                                            <div class="col-12">
                                                <div class="text-end mt-3">
                                                    <button class="btn btn-success" type="submit">
                                                        <span class="btn-icon"><i class="mdi mdi-content-save me-1"></i> </span>
                                                        <span class="btn-text">Сохранить</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
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

    <script>
        function sendEditCityForm(btn) {
            event.preventDefault();
            loaderBTN(btn, 'true');
            jQuery.ajax({
                url: '/?page=<?= $page ?>&form=edit-city',
                type: 'POST',
                dataType: 'html',
                data: jQuery('#form-edit-city').serialize(),
                success: function (response) {
                    loaderBTN(btn, 'false');
                    result = jQuery.parseJSON(response);
                    if (result.success_type == 'message') {
                        message(result.msg_title, result.msg_text, result.msg_type, result.msg_url);
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