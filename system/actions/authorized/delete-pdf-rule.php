<?php

$center_id = valid($_POST['center_id'] ?? '');

if (empty($center_id) || !preg_match('/^[0-9]{1,11}$/u', $center_id)) {
    message('Ошибка', 'Некорректный ID визового центра!', 'error', '');
}

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare("DELETE FROM `pdf_parsing_rules` WHERE `center_id` = :center_id");
    $stmt->execute([':center_id' => $center_id]);

    message('Уведомление', 'Правило успешно сброшено!', 'success', 'reload');

} catch (PDOException $e) {
    error_log('DB Error on delete-pdf-rule: ' . $e->getMessage());
    message('Ошибка', 'Не удалось сбросить правило. Попробуйте позже.', 'error', '');
}