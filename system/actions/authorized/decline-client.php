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

    // --- УВЕДОМЛЕНИЕ АГЕНТУ ---
    // Узнаем, чья это была анкета
    $stmt_agent = $pdo->prepare("SELECT agent_id, client_name, center_id FROM clients WHERE client_id = :id");
    $stmt_agent->execute([':id' => $client_id]);
    $client_data = $stmt_agent->fetch(PDO::FETCH_ASSOC);

    if ($client_data && $client_data['agent_id']) {
        $sender_role = ($user_data['user_group'] == 1) ? 'Директором' : 'Менеджером';
        send_notification(
            $pdo, 
            $client_data['agent_id'], 
            'Анкета отклонена', 
            "Ваша анкета '{$client_data['client_name']}' (ID: {$client_id}) была отклонена {$sender_role}. Причина: {$reason}", 
            'danger', 
            "/?page=clients&center={$client_data['center_id']}&status=3"
        );
    }

    message('Уведомление', 'Анкета была отклонена и возвращена агенту в черновики!', 'success', '');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Не удалось отклонить анкету.', 'error', '');
}