<?php
$client_id = valid($_POST['client-id'] ?? '');

if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Недопустимое значение ID анкеты!', 'error', '');
}

try {
    $pdo = db_connect();
    // Возвращаем анкету к обычному виду черновика, убирая причину отклонения
    $stmt = $pdo->prepare("UPDATE `clients` SET `rejection_reason` = NULL WHERE `client_id` = :client_id AND `client_status` = 3");
    $stmt->execute([':client_id' => $client_id]);

    message('Уведомление', 'Анкета возвращена для редактирования!', 'success', '');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось вернуть анкету.', 'error', '');
}