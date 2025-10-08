<?php
$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id)) {
    redirectAJAX('dashboard');
}

if (!preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare("UPDATE `clients` SET `client_status` = 4 WHERE `client_id` = :client_id");
    $stmt->execute([':client_id' => $client_id]);

    message('Уведомление', 'Анкета отправлена в архив!', 'success', '');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось удалить анкету. Попробуйте позже.', 'error', '');
}
$pdo = null;