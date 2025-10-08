<?php
$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

try {
    $pdo = db_connect();
    // Менеджер одобряет анкету, переводя ее из статуса 6 (у менеджера) в 5 (у директора)
    $stmt = $pdo->prepare("UPDATE `clients` SET `client_status` = 5 WHERE `client_id` = :client_id AND `client_status` = 6");
    $stmt->execute([':client_id' => $client_id]);

    message('Уведомление', 'Анкета одобрена и отправлена на рассмотрение директору!', 'success', '');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось одобрить анкету.', 'error', '');
}