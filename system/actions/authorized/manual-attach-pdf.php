<?php
// Проверяем права доступа, только Директор (группа 1) может выполнять это действие
if ($user_data['user_group'] != 1) {
    message('Ошибка', 'У вас нет прав для выполнения этого действия!', 'error', '');
}

// --- НАЧАЛО БЛОКА ВАЛИДАЦИИ ---
$client_id = valid($_POST['client_id'] ?? '');
if (empty($client_id) || !preg_match('/^[0-9]{1,11}$/u', $client_id)) {
    message('Ошибка', 'Некорректный ID анкеты!', 'error', '');
}

if (empty($_FILES['manual_pdf_file'])) {
    message('Ошибка', 'Файл не был отправлен.', 'error', '');
}

$file = $_FILES['manual_pdf_file'];
if ($file['error'] !== UPLOAD_ERR_OK || $file['type'] !== 'application/pdf') {
    message('Ошибка', 'Ошибка загрузки или файл не является PDF.', 'error', '');
}
// --- КОНЕЦ БЛОКА ВАЛИДАЦИИ ---

try {
    $pdo = db_connect();

    // Проверяем, существует ли анкета и подходит ли ее статус
    $stmt_check = $pdo->prepare("SELECT `pdf_file_path`, `client_status` FROM `clients` WHERE `client_id` = :client_id");
    $stmt_check->execute([':client_id' => $client_id]);
    $client = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        message('Ошибка', 'Анкета с указанным ID не найдена.', 'error', '');
    }
    if (!in_array((int)$client['client_status'], [1, 2])) {
        message('Ошибка', 'Прикреплять файлы можно только к анкетам со статусом "В работе" или "Записанные".', 'error', '');
    }
    if (!empty($client['pdf_file_path'])) {
        message('Ошибка', 'К этой анкете уже прикреплен PDF-файл.', 'error', '');
    }

    // --- ЛОГИКА СОХРАНЕНИЯ ФАЙЛА ---
    $tmp_path = $file['tmp_name'];
    $new_filename = $client_id . '_' . uniqid() . '.pdf';
    $save_dir = ROOT . '/private_uploads/client_pdfs/';
    if (!is_dir($save_dir)) {
        mkdir($save_dir, 0755, true);
    }
    $save_path = $save_dir . $new_filename;

    if (move_uploaded_file($tmp_path, $save_path)) {
        // Файл успешно сохранен, теперь обновляем запись в БД
        $stmt_update = $pdo->prepare("UPDATE `clients` SET `pdf_file_path` = :pdf_path WHERE `client_id` = :client_id");
        $stmt_update->execute([':pdf_path' => $new_filename, ':client_id' => $client_id]);
        
        message('Уведомление', 'Файл успешно прикреплен к анкете №' . $client_id, 'success', '');
    } else {
        message('Ошибка', 'Не удалось сохранить файл на сервере.', 'error', '');
    }

} catch (PDOException $e) {
    error_log('DB Error on manual-attach-pdf: ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка базы данных.', 'error', '');
}