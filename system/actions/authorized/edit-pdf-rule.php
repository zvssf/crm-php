<?php

$center_id = valid($_POST['center_id'] ?? '');
$identifier_text = valid($_POST['center_identifier_text'] ?? '');
$passport_mask = valid($_POST['passport_mask'] ?? null);

if (empty($center_id) || !preg_match('/^[0-9]{1,11}$/u', $center_id)) {
    message('Ошибка', 'Некорректный ID визового центра!', 'error', '');
}

if (empty($identifier_text)) {
    message('Ошибка', 'Поле "Уникальный текст" не может быть пустым!', 'error', '');
}

try {
    $pdo = db_connect();

    // Используем INSERT ... ON DUPLICATE KEY UPDATE для атомарного добавления/обновления
    $stmt = $pdo->prepare("
        INSERT INTO `pdf_parsing_rules` (center_id, center_identifier_text, passport_mask)
        VALUES (:center_id, :identifier_text, :passport_mask)
        ON DUPLICATE KEY UPDATE 
            center_identifier_text = VALUES(center_identifier_text),
            passport_mask = VALUES(passport_mask)
    ");

    $stmt->execute([
        ':center_id' => $center_id,
        ':identifier_text' => $identifier_text,
        ':passport_mask' => !empty($passport_mask) ? $passport_mask : null
    ]);

    message('Уведомление', 'Правило успешно сохранено!', 'success', 'reload');

} catch (PDOException $e) {
    error_log('DB Error on edit-pdf-rule: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сохранить правило. Попробуйте позже.', 'error', '');
}