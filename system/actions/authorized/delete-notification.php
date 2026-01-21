<?php
$id = valid($_POST['id'] ?? '');

if (empty($id) || !is_numeric($id)) {
    exit(json_encode(['status' => 'error', 'message' => 'Некорректный ID']));
}

try {
    $pdo = db_connect();

    // Удаляем уведомление, только если оно принадлежит текущему пользователю
    // Это важная проверка безопасности, чтобы нельзя было удалить чужое
    $stmt = $pdo->prepare("DELETE FROM `notifications` WHERE `id` = :id AND `user_id` = :uid");
    $stmt->execute([
        ':id' => $id,
        ':uid' => $user_data['user_id']
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Уведомление не найдено или доступ запрещен']);
    }

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных']);
}