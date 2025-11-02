<?php

$action = valid($_POST['action'] ?? '');
$input_ids = $_POST['input_ids'] ?? [];

// --- НАЧАЛО БЛОКА ВАЛИДАЦИИ ---
if (empty($action) || !in_array($action, ['delete', 'restore'])) {
    message('Ошибка', 'Действие не указано или некорректно!', 'error', '');
}

if (empty($input_ids) || !is_array($input_ids)) {
    message('Ошибка', 'Не выбраны дополнительные поля!', 'error', '');
}

$validated_ids = [];
foreach ($input_ids as $id) {
    if (is_numeric($id) && $id > 0) {
        $validated_ids[] = (int)$id;
    }
}

if (empty($validated_ids)) {
    message('Ошибка', 'Некорректные ID дополнительных полей!', 'error', '');
}
// --- КОНЕЦ БЛОКА ВАЛИДАЦИИ ---

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($validated_ids), '?'));
    $success_verb = ''; 
    $noun = 'дополнительное поле';

    switch ($action) {
        case 'delete':
            $success_verb = 'удалено';
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM `settings_inputs` WHERE `input_id` IN ($placeholders) AND `input_status` = 0");
            $stmt_check->execute($validated_ids);
            if ($stmt_check->fetchColumn() > 0) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые из выбранных элементов уже удалены!', 'error', '');
            }

            $stmt_update = $pdo->prepare("UPDATE `settings_inputs` SET `input_status` = 0 WHERE `input_id` IN ($placeholders)");
            $stmt_update->execute($validated_ids);
            break;

        case 'restore':
            $success_verb = 'восстановлено';
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM `settings_inputs` WHERE `input_id` IN ($placeholders) AND `input_status` > 0");
            $stmt_check->execute($validated_ids);
            if ($stmt_check->fetchColumn() > 0) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые из выбранных элементов не требуют восстановления!', 'error', '');
            }

            $stmt_update = $pdo->prepare("UPDATE `settings_inputs` SET `input_status` = 1 WHERE `input_id` IN ($placeholders)");
            $stmt_update->execute($validated_ids);
            break;
    }

    $pdo->commit();

    // --- БЛОК ФОРМИРОВАНИЯ СООБЩЕНИЯ ---
    $count = count($validated_ids);
    $verb_ending = 'о'; // УдаленО, ВосстановленО

    if ($count > 1) {
        if ($count % 10 >= 2 && $count % 10 <= 4 && !($count % 100 >= 12 && $count % 100 <= 14)) {
            $noun = 'дополнительных поля';
        } else {
            $noun = 'дополнительных полей';
        }
    }

    $message_text = "Успешно {$success_verb} {$count} {$noun}.";
    message('Уведомление', $message_text, 'success', 'reload');
    // --- КОНЕЦ БЛОКА ---

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error (mass-input-action): ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка базы данных. Попробуйте позже.', 'error', '');
}