<?php
// Проверка прав не нужна, так как файл лежит в authorized и доступен всем авторизованным

try {
    $pdo = db_connect();
    
    // Получаем последние 100 уведомлений
    $stmt_notif = $pdo->prepare("
        SELECT * FROM `notifications` 
        WHERE `user_id` = :uid 
        ORDER BY `id` DESC 
        LIMIT 100
    ");
    $stmt_notif->execute([':uid' => $user_data['user_id']]);
    $notifications = $stmt_notif->fetchAll(PDO::FETCH_ASSOC);

    // Начинаем буферизацию вывода, чтобы сохранить HTML в переменную
    ob_start();

    if ($notifications) {
        foreach ($notifications as $item) {
            // Логика цветов и иконок (1 в 1 как на странице)
            [$icon, $color] = match ($item['type']) {
                'success' => ['mdi-check-circle-outline', 'success'],
                'danger'  => ['mdi-alert-circle-outline', 'danger'],
                'warning' => ['mdi-alert-outline', 'warning'],
                default   => ['mdi-information-outline', 'info'],
            };
            
            $font_weight = $item['is_read'] == 0 ? 'fw-bold text-dark' : 'fw-normal text-muted';
            ?>
            <tr class="align-middle">
                <td class="text-center">
                    <div class="avatar-sm d-inline-block">
                        <span class="avatar-title bg-<?= $color ?>-lighten text-<?= $color ?> rounded-circle font-20">
                            <i class="mdi <?= $icon ?>"></i>
                        </span>
                    </div>
                </td>
                <td>
                    <span class="<?= $font_weight ?>"><?= valid($item['title']) ?></span>
                </td>
                <td>
                    <span class="<?= $item['is_read'] == 0 ? 'text-body' : 'text-muted' ?>">
                        <?= valid($item['message']) ?>
                    </span>
                    <?php if (!empty($item['link']) && $item['link'] !== '#'): ?>
                        <a href="<?= $item['link'] ?>" class="d-block font-12 mt-1 notification-link" data-id="<?= $item['id'] ?>">
                            Посмотреть детали <i class="mdi mdi-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="font-13"><?= date('d.m.Y', strtotime($item['created_at'])) ?></span>
                    <br>
                    <small class="text-muted"><?= date('H:i', strtotime($item['created_at'])) ?></small>
                </td>
                <td>
                    <?php if ($item['is_read'] == 0): ?>
                        <a href="javascript:void(0);" class="font-18 text-info me-2 action-mark-read" data-id="<?= $item['id'] ?>" title="Отметить прочитанным"><i class="mdi mdi-email-open-outline"></i></a>
                    <?php endif; ?>
                    
                    <a href="javascript:void(0);" class="font-18 text-danger action-delete-notification" data-id="<?= $item['id'] ?>" title="Удалить"><i class="uil uil-trash"></i></a>
                </td>
            </tr>
            <?php
        }
    } else {
        ?>
        <tr>
            <td colspan="5" class="text-center text-muted p-5">
                <i class="mdi mdi-bell-off-outline h1"></i>
                <p class="mt-3">У вас пока нет новых уведомлений</p>
            </td>
        </tr>
        <?php
    }

    $html = ob_get_clean(); // Получаем HTML из буфера

    echo json_encode(['status' => 'success', 'html' => $html]);

} catch (PDOException $e) {
    // В случае ошибки тихо возвращаем статус, чтобы JS не ломался
    echo json_encode(['status' => 'error']);
}