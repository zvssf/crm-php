<?php

$action = valid($_POST['action'] ?? '');
$center_ids = $_POST['center_ids'] ?? [];

// --- НАЧАЛО БЛОКА ВАЛИДАЦИИ ---
if (empty($action) || !in_array($action, ['delete', 'restore'])) {
    message('Ошибка', 'Действие не указано или некорректно!', 'error', '');
}

if (empty($center_ids) || !is_array($center_ids)) {
    message('Ошибка', 'Не выбраны визовые центры!', 'error', '');
}

$validated_ids = [];
foreach ($center_ids as $id) {
    if (is_numeric($id) && $id > 0) {
        $validated_ids[] = (int)$id;
    }
}

if (empty($validated_ids)) {
    message('Ошибка', 'Некорректные ID визовых центров!', 'error', '');
}
// --- КОНЕЦ БЛОКА ВАЛИДАЦИИ ---

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($validated_ids), '?'));
    $success_verb = ''; 
    $noun = 'визовый центр';

    switch ($action) {
        case 'delete':
            $success_verb = 'удалён';
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM `settings_centers` WHERE `center_id` IN ($placeholders) AND `center_status` = 0");
            $stmt_check->execute($validated_ids);
            if ($stmt_check->fetchColumn() > 0) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые из выбранных элементов уже удалены!', 'error', '');
            }

            $stmt_update = $pdo->prepare("UPDATE `settings_centers` SET `center_status` = 0 WHERE `center_id` IN ($placeholders)");
            $stmt_update->execute($validated_ids);
            break;

        case 'restore':
            $success_verb = 'восстановлен';
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM `settings_centers` WHERE `center_id` IN ($placeholders) AND `center_status` > 0");
            $stmt_check->execute($validated_ids);
            if ($stmt_check->fetchColumn() > 0) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые из выбранных элементов не требуют восстановления!', 'error', '');
            }

            $stmt_update = $pdo->prepare("UPDATE `settings_centers` SET `center_status` = 1 WHERE `center_id` IN ($placeholders)");
            $stmt_update->execute($validated_ids);
            break;
    }

    $pdo->commit();

    // --- БЛОК ФОРМИРОВАНИЯ СООБЩЕНИЯ ---
    $count = count($validated_ids);
    $verb_ending = '';

    if ($count > 1) {
        $verb_ending = 'ы';
        if ($count % 10 >= 2 && $count % 10 <= 4 && !($count % 100 >= 12 && $count % 100 <= 14)) {
            $noun = 'визовых центра';
        } else {
            $noun = 'визовых центров';
        }
    } else {
        $verb_ending = '';
    }

    $message_text = "Успешно {$success_verb}{$verb_ending} {$count} {$noun}.";
    message('Уведомление', $message_text, 'success', 'reload');
    // --- КОНЕЦ БЛОКА ---

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error (mass-center-action): ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка базы данных. Попробуйте позже.', 'error', '');
}