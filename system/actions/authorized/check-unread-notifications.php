<?php
// Проверка прав не нужна, доступно всем авторизованным

try {
    $pdo = db_connect();

    // Считаем количество непрочитанных (is_read = 0)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `notifications` WHERE `user_id` = :uid AND `is_read` = 0");
    $stmt->execute([':uid' => $user_data['user_id']]);
    $count = (int)$stmt->fetchColumn();

    echo json_encode(['status' => 'success', 'count' => $count]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error']);
}