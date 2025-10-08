<?php
$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

// Убедимся, что это действие выполняет только Директор
if ($user_data['user_group'] != 1) {
    message('Ошибка', 'У вас нет прав для выполнения этого действия!', 'error', '');
}

try {
    $pdo = db_connect();
    // Директор одобряет свой черновик, переводя его сразу в статус 1 (В работе)
    $stmt = $pdo->prepare("UPDATE `clients` SET `client_status` = 1 WHERE `client_id` = :client_id AND `client_status` = 3");
    $stmt->execute([':client_id' => $client_id]);

    message('Уведомление', 'Анкета одобрена и переведена в статус "В работе"!', 'success', '');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось одобрить анкету.', 'error', '');
}