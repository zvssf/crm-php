<?php
$client_id = valid($_POST['client-id'] ?? '');
$reason = valid($_POST['rejection-reason'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

try {
    $pdo = db_connect();
    // Анкета отклоняется и возвращается в черновики (статус 3) с указанием причины.
    // Обновление происходит только если текущий статус 5 (На рассмотрении у директора)
    $stmt = $pdo->prepare("UPDATE `clients` SET `client_status` = 3, `rejection_reason` = :reason WHERE `client_id` = :client_id AND `client_status` IN (5, 6)");
    $stmt->execute([':client_id' => $client_id, ':reason' => $reason]);

    message('Уведомление', 'Анкета была отклонена и возвращена агенту в черновики!', 'success', '');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось отклонить анкету.', 'error', '');
}
