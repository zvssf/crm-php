<?php

// Проверка прав: только Директор (1)
if ($user_data['user_group'] != 1) {
    message('Ошибка', 'У вас нет прав для выполнения этого действия!', 'error', '');
}

$title = valid($_POST['title'] ?? '');
$message_text = valid($_POST['message'] ?? '');
$type = valid($_POST['type'] ?? 'info');
$send_to_all = isset($_POST['send_to_all']) ? 1 : 0;
$user_ids = $_POST['user_ids'] ?? [];

// Валидация
if (empty($title)) {
    message('Ошибка', 'Введите заголовок уведомления!', 'error', '');
}
if (empty($message_text)) {
    message('Ошибка', 'Введите текст сообщения!', 'error', '');
}

try {
    $pdo = db_connect();
    $recipients = [];

    // Определяем список получателей
    if ($send_to_all) {
        // Выбираем всех активных пользователей, кроме самого себя
        $stmt_all = $pdo->prepare("SELECT `user_id` FROM `users` WHERE `user_status` = 1 AND `user_id` != :my_id");
        $stmt_all->execute([':my_id' => $user_data['user_id']]);
        $recipients = $stmt_all->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // Фильтруем пришедший массив ID
        if (empty($user_ids) || !is_array($user_ids)) {
            message('Ошибка', 'Выберите хотя бы одного получателя!', 'error', '');
        }
        foreach ($user_ids as $uid) {
            if (is_numeric($uid)) {
                $recipients[] = (int)$uid;
            }
        }
    }

    if (empty($recipients)) {
        message('Ошибка', 'Нет получателей для отправки уведомления.', 'error', '');
    }

    // Массовая вставка (транзакция для скорости и надежности)
    $pdo->beginTransaction();

    $sql = "INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES (:user_id, :type, :title, :message, 0, NOW())";
    $stmt = $pdo->prepare($sql);

    foreach ($recipients as $recipient_id) {
        $stmt->execute([
            ':user_id' => $recipient_id,
            ':type'    => $type,
            ':title'   => $title,
            ':message' => $message_text
        ]);
    }

    $pdo->commit();

    message('Успешно', 'Уведомление отправлено ' . count($recipients) . ' пользователям.', 'success', 'reload');

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Notification Create Error: ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка при отправке. Попробуйте позже.', 'error', '');
}