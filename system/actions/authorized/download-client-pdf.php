<?php
// Проверяем права доступа: только Агент может выполнять это действие
if ($user_data['user_group'] != 4) {
    exit('Доступ запрещен.');
}

// 1. Валидация ID анкеты из GET-запроса
$client_id = valid($_GET['id'] ?? '');
if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    exit('Некорректный ID анкеты.');
}

try {
    $pdo = db_connect();

    // 2. Получение данных анкеты из БД
    $stmt = $pdo->prepare("
        SELECT `agent_id`, `client_status`, `payment_status`, `pdf_file_path` 
        FROM `clients` 
        WHERE `client_id` = :client_id
    ");
    $stmt->execute([':client_id' => $client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Проведение всех проверок безопасности
    if (!$client) {
        exit('Анкета не найдена.');
    }

    // Убеждаемся, что текущий агент запрашивает анкету, которая ему принадлежит
    if ($client['agent_id'] != $user_data['user_id']) {
        exit('У вас нет доступа к этой анкете.');
    }

    // Проверяем, что анкета находится в статусе "Записанные"
    if ($client['client_status'] != 2) {
        exit('Скачивание доступно только для записанных анкет.');
    }

    // Проверяем, что анкета оплачена или взята в кредит
    if (!in_array((int)$client['payment_status'], [1, 2])) {
        exit('Скачивание доступно только для оплаченных анкет.');
    }

    // Проверяем, что к анкете привязан файл
    if (empty($client['pdf_file_path'])) {
        exit('К этой анкете не прикреплен PDF-файл.');
    }

    // 4. Формирование пути к файлу и проверка его существования
    $file_path = ROOT . '/private_uploads/client_pdfs/' . $client['pdf_file_path'];
    
    if (!file_exists($file_path)) {
        error_log('File not found for client ' . $client_id . ': ' . $file_path);
        exit('Файл не найден на сервере.');
    }

    // 5. Отправка файла браузеру для скачивания
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    
    // Очищаем буфер вывода перед отправкой файла
    flush(); 
    
    readfile($file_path);
    exit;

} catch (PDOException $e) {
    error_log('DB Error on download-client-pdf: ' . $e->getMessage());
    exit('Ошибка базы данных.');
}