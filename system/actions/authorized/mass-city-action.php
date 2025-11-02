<?php

$action = valid($_POST['action'] ?? '');
$city_ids = $_POST['city_ids'] ?? [];

// --- НАЧАЛО БЛОКА ВАЛИДАЦИИ ---
if (empty($action) || !in_array($action, ['delete', 'restore'])) {
    message('Ошибка', 'Действие не указано или некорректно!', 'error', '');
}

if (empty($city_ids) || !is_array($city_ids)) {
    message('Ошибка', 'Не выбраны города!', 'error', '');
}

$validated_ids = [];
foreach ($city_ids as $id) {
    if (is_numeric($id) && $id > 0) {
        $validated_ids[] = (int)$id;
    }
}

if (empty($validated_ids)) {
    message('Ошибка', 'Некорректные ID городов!', 'error', '');
}
// --- КОНЕЦ БЛОКА ВАЛИДАЦИИ ---

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($validated_ids), '?'));
    $success_verb = ''; 
    $noun = 'город';

    switch ($action) {
        case 'delete':
            $success_verb = 'удалён';
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM `settings_cities` WHERE `city_id` IN ($placeholders) AND `city_status` = 0");
            $stmt_check->execute($validated_ids);
            if ($stmt_check->fetchColumn() > 0) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые из выбранных элементов уже удалены!', 'error', '');
            }

            $stmt_update = $pdo->prepare("UPDATE `settings_cities` SET `city_status` = 0 WHERE `city_id` IN ($placeholders)");
            $stmt_update->execute($validated_ids);
            break;

        case 'restore':
            $success_verb = 'восстановлен';
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM `settings_cities` WHERE `city_id` IN ($placeholders) AND `city_status` > 0");
            $stmt_check->execute($validated_ids);
            if ($stmt_check->fetchColumn() > 0) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые из выбранных элементов не требуют восстановления!', 'error', '');
            }

            $stmt_update = $pdo->prepare("UPDATE `settings_cities` SET `city_status` = 1 WHERE `city_id` IN ($placeholders)");
            $stmt_update->execute($validated_ids);
            break;
    }

    $pdo->commit();

    // --- БЛОК ФОРМИРОВАНИЯ СООБЩЕНИЯ ---
    $count = count($validated_ids);
    $verb_ending = '';

    if ($count > 1) {
        $verb_ending = 'о'; // УдаленО, ВосстановленО
        $noun = 'городов';
        if ($count % 10 >= 2 && $count % 10 <= 4 && !($count % 100 >= 12 && $count % 100 <= 14)) {
            $noun = 'города';
        }
    }

    $message_text = "Успешно {$success_verb}{$verb_ending} {$count} {$noun}.";
    message('Уведомление', $message_text, 'success', 'reload');
    // --- КОНЕЦ БЛОКА ---

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error (mass-city-action): ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка базы данных. Попробуйте позже.', 'error', '');
}