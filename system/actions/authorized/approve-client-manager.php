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

    // Получаем ID центра для ссылки
    $stmt_info = $pdo->prepare("SELECT center_id FROM clients WHERE client_id = :id");
    $stmt_info->execute([':id' => $client_id]);
    $center_id = $stmt_info->fetchColumn();

    $stmt_directors = $pdo->query("SELECT user_id FROM users WHERE user_group = 1 AND user_status = 1");
    $msg_body = "Менеджер {$user_data['user_firstname']} {$user_data['user_lastname']} одобрил анкету №{$client_id}.";
    
    while ($dir_id = $stmt_directors->fetchColumn()) {
        send_notification(
            $pdo, 
            $dir_id, 
            'Анкета на утверждение', 
            $msg_body, 
            'warning', 
            "/?page=clients&center={$center_id}&status=5"
        );
    }

    message('Уведомление', 'Анкета одобрена и отправлена на рассмотрение директору!', 'success', '');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось одобрить анкету.', 'error', '');
}