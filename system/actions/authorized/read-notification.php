<?php
$id = valid($_POST['id'] ?? '');
$mode = valid($_POST['mode'] ?? 'single'); // 'single' или 'all'

try {
    $pdo = db_connect();

    if ($mode === 'all') {
        $stmt = $pdo->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `user_id` = :uid");
        $stmt->execute([':uid' => $user_data['user_id']]);
    } else {
        if (empty($id) || !is_numeric($id)) {
            exit('Error ID');
        }
        // Помечаем только если это уведомление принадлежит текущему пользователю
        $stmt = $pdo->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `id` = :id AND `user_id` = :uid");
        $stmt->execute([':id' => $id, ':uid' => $user_data['user_id']]);
    }

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error']);
}