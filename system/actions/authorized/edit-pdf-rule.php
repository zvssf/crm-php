<?php

$center_id = valid($_POST['center_id'] ?? '');
$identifier_text = valid($_POST['center_identifier_text'] ?? '');

if (empty($center_id) || !preg_match('/^[0-9]{1,11}$/u', $center_id)) {
    message('Ошибка', 'Некорректный ID визового центра!', 'error', '');
}

if (empty($identifier_text)) {
    message('Ошибка', 'Поле "Уникальный текст" не может быть пустым!', 'error', '');
}

try {
    $pdo = db_connect();

    // Убрали passport_mask из запроса
    $stmt = $pdo->prepare("
        INSERT INTO `pdf_parsing_rules` (center_id, center_identifier_text)
        VALUES (:center_id, :identifier_text)
        ON DUPLICATE KEY UPDATE 
            center_identifier_text = VALUES(center_identifier_text)
    ");

    $stmt->execute([
        ':center_id' => $center_id,
        ':identifier_text' => $identifier_text
    ]);

    message('Уведомление', 'Правило успешно сохранено!', 'success', 'reload');

} catch (PDOException $e) {
    error_log('DB Error on edit-pdf-rule: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сохранить правило. Попробуйте позже.', 'error', '');
}