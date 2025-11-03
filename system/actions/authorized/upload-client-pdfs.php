<?php
// --- НАЧАЛО БЛОКА ДИАГНОСТИКИ ---
// Записываем в лог, что скрипт вообще был запущен.
error_log("--- upload-client-pdfs.php: Script started ---");

// Проверяем наличие константы ROOT, это критически важно.
if (!defined('ROOT')) {
    error_log("FATAL ERROR: The ROOT constant is not defined!");
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Критическая ошибка конфигурации сервера (ROOT).']);
    exit;
}
error_log("DEBUG: ROOT constant is defined: " . ROOT);
// --- КОНЕЦ БЛОКА ДИАГНОСТИКИ ---

// Устанавливаем заголовки для JSON-ответа
header('Content-Type: application/json');

if (empty($_FILES['client_pdfs'])) {
    error_log("ERROR: _FILES['client_pdfs'] is empty.");
    echo json_encode(['status' => 'error', 'message' => 'Файлы не были отправлены.']);
    exit;
}

$file = $_FILES['client_pdfs'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    error_log("ERROR: File upload error code: " . $file['error']);
    echo json_encode(['status' => 'error', 'message' => 'Ошибка загрузки файла. Код: ' . $file['error']]);
    exit;
}

if ($file['type'] !== 'application/pdf') {
    error_log("ERROR: Invalid file type: " . $file['type']);
    echo json_encode(['status' => 'error', 'message' => 'Файл не является PDF.']);
    exit;
}

$tmp_path = $file['tmp_name'];
error_log("DEBUG: File successfully uploaded to temporary path: " . $tmp_path);

try {
    error_log("DEBUG: Entered try block.");

    $pdo = db_connect();
    error_log("DEBUG: Database connected.");

    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($tmp_path);
    $text = $pdf->getText();
    error_log("DEBUG: PDF parsed. Text length: " . strlen($text));

    $stmt_rules = $pdo->query("SELECT * FROM `pdf_parsing_rules` WHERE `rule_status` = 1");
    $rules = $stmt_rules->fetchAll(PDO::FETCH_ASSOC);
    error_log("DEBUG: Fetched " . count($rules) . " active rules from DB.");

    $matched_rule = null;
    foreach ($rules as $rule) {
        if (strpos($text, $rule['center_identifier_text']) !== false) {
            $matched_rule = $rule;
            break;
        }
    }

    if (!$matched_rule) {
        error_log("ERROR: No matching rule found for the PDF content.");
        echo json_encode(['status' => 'error', 'message' => 'Не удалось определить ВЦ.']);
        exit;
    }
    error_log("DEBUG: Matched rule for center_id: " . $matched_rule['center_id']);

    $center_id = $matched_rule['center_id'];
    $passport_mask = $matched_rule['passport_mask'];
    $client_id = null;

    if (!empty($passport_mask)) {
        // Ищем позицию фразы "Passport Number", без учета регистра
        $position = stripos($text, 'Passport Number');

        if ($position !== false) {
            // Берем небольшой "срез" текста (50 символов) сразу после найденной фразы
            $search_area = substr($text, $position, 50);
            error_log("DEBUG: Found context for 'Passport Number'. Search area: " . $search_area);

            // Преобразуем нашу маску (напр. NNxxxxxxNN) в регулярное выражение
            $mask_pattern = str_replace(['N', 'x'], ['[a-zA-Z0-9]', '\S'], $passport_mask);
            $mask_pattern = '/\b' . $mask_pattern . '\b/i';

            // Теперь ищем по маске только в этом небольшом срезе текста
            if (preg_match($mask_pattern, $search_area, $passport_matches)) {
                $found_passport = $passport_matches[0];
                error_log("DEBUG: Found passport text using mask in context: " . $found_passport);

                // --- Дальнейший код остается без изменений ---
                $prefix = '';
                $suffix = '';
                
                if (preg_match('/^N+/i', $passport_mask, $prefix_match)) {
                    $prefix_len = strlen($prefix_match[0]);
                    $prefix = substr($found_passport, 0, $prefix_len);
                }
                
                if (preg_match('/N+$/i', $passport_mask, $suffix_match)) {
                    $suffix_len = strlen($suffix_match[0]);
                    $suffix = substr($found_passport, -$suffix_len);
                }
                
                $sql_passport_pattern = $prefix . '%' . $suffix;
                error_log("DEBUG: Searching for client with center_id=" . $center_id . " and passport LIKE '" . $sql_passport_pattern . "'");
                
                $stmt_client = $pdo->prepare("SELECT `client_id` FROM `clients` WHERE `center_id` = :center_id AND `passport_number` LIKE :passport_pattern");
                $stmt_client->execute([':center_id' => $center_id, ':passport_pattern' => $sql_passport_pattern]);
                $found_clients = $stmt_client->fetchAll(PDO::FETCH_ASSOC);

                if (count($found_clients) === 1) {
                    $client_id = $found_clients[0]['client_id'];
                } elseif (count($found_clients) > 1) {
                    error_log("ERROR: Found multiple clients (" . count($found_clients) . ").");
                    echo json_encode(['status' => 'error', 'message' => 'Найдено несколько анкет.']);
                    exit;
                }
                // --- Конец неизменного кода ---
            } else {
                error_log("DEBUG: Passport text not found with mask '" . $passport_mask . "' in the context area.");
            }
        } else {
            error_log("DEBUG: Context phrase 'Passport Number' not found in PDF text.");
        }
    }

    if (!$client_id) {
        error_log("ERROR: Client not found in database.");
        echo json_encode(['status' => 'error', 'message' => 'Анкета не найдена.']);
        exit;
    }
    error_log("DEBUG: Client found: client_id = " . $client_id);

    $new_filename = $client_id . '_' . uniqid() . '.pdf';
    $save_path = ROOT . '/private_uploads/client_pdfs/' . $new_filename;
    error_log("DEBUG: Preparing to save file to: " . $save_path);

    $save_dir = dirname($save_path);
    if (!is_writable($save_dir)) {
        error_log("FATAL ERROR: Directory is not writable: " . $save_dir);
        echo json_encode(['status' => 'error', 'message' => 'Ошибка прав доступа на сервере.']);
        exit;
    }
    error_log("DEBUG: Directory is writable: " . $save_dir);

    if (move_uploaded_file($tmp_path, $save_path)) {
        error_log("SUCCESS: File moved successfully.");
        $stmt_update = $pdo->prepare("UPDATE `clients` SET `pdf_file_path` = :pdf_path WHERE `client_id` = :client_id");
        $stmt_update->execute([':pdf_path' => $new_filename, ':client_id' => $client_id]);
        echo json_encode(['status' => 'success', 'message' => 'Файл успешно привязан.', 'client_id' => $client_id]);
    } else {
        error_log("FATAL ERROR: move_uploaded_file() FAILED.");
        echo json_encode(['status' => 'error', 'message' => 'Не удалось сохранить файл на сервере.']);
    }

} catch (Exception $e) {
    error_log("!!! EXCEPTION CAUGHT: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode(['status' => 'error', 'message' => 'Внутренняя ошибка сервера.']);
}

exit;