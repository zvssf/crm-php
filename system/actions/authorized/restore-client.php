<?php
$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare("UPDATE `clients` SET `client_status` = 3 WHERE `client_id` = :client_id AND `client_status` = 4");
    $stmt->execute([':client_id' => $client_id]);

    message('Уведомление', 'Анкета восстановлена из архива!', 'success', '');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось восстановить анкету.', 'error', '');
}