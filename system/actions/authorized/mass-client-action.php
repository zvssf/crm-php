<?php

$action = valid($_POST['action'] ?? '');
$client_ids = $_POST['client_ids'] ?? [];

// --- НАЧАЛО БЛОКА ВАЛИДАЦИИ ---
if (empty($action)) {
    message('Ошибка', 'Действие не указано!', 'error', '');
}

if (empty($client_ids) || !is_array($client_ids)) {
    message('Ошибка', 'Не выбраны анкеты!', 'error', '');
}

$validated_ids = [];
foreach ($client_ids as $id) {
    // Используем более простую и надежную проверку для ID
    if (is_numeric($id) && $id > 0) {
        $validated_ids[] = (int)$id;
    }
}

if (empty($validated_ids)) {
    message('Ошибка', 'Некорректные ID анкет!', 'error', '');
}
// --- КОНЕЦ БЛОКА ВАЛИДАЦИИ ---

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    // Готовим плейсхолдеры для IN() запроса (?,?,?)
    $placeholders = implode(',', array_fill(0, count($validated_ids), '?'));
    $success_verb = ''; // Переменная для текста сообщения

    switch ($action) {
        case 'restore':
            $success_verb = 'восстановлен';
            $sql_check = "SELECT COUNT(*) FROM `clients` WHERE `client_id` IN ($placeholders) AND `client_status` = 4";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute($validated_ids);
            if ($stmt_check->fetchColumn() != count($validated_ids)) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые анкеты не находятся в архиве!', 'error', '');
            }

            $stmt_update = $pdo->prepare("UPDATE `clients` SET `client_status` = 3, `creator_id` = ? WHERE `client_id` IN ($placeholders)");
            $stmt_update->execute(array_merge([$user_data['user_id']], $validated_ids));
            break;

        case 'archive':
            $success_verb = 'заархивирован';
            $sql_check = "SELECT COUNT(*) FROM `clients` WHERE `client_id` IN ($placeholders) AND `client_status` IN (1, 2, 3)";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute($validated_ids);
            if ($stmt_check->fetchColumn() != count($validated_ids)) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые анкеты не могут быть заархивированы из их текущего статуса!', 'error', '');
            }

            $stmt_update = $pdo->prepare("UPDATE `clients` SET `client_status` = 4 WHERE `client_id` IN ($placeholders)");
            $stmt_update->execute($validated_ids);
            break;

        case 'review':
            $success_verb = 'отправлен';
            $sql_check = "SELECT COUNT(*) FROM `clients` WHERE `client_id` IN ($placeholders) AND `client_status` = 3 AND (`rejection_reason` IS NULL OR `rejection_reason` = '')";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute($validated_ids);
            if ($stmt_check->fetchColumn() != count($validated_ids)) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые анкеты не являются черновиками или были отклонены. Сначала верните их в работу.', 'error', '');
            }

            $next_status = match ((int)$user_data['user_group']) {
                3 => 5, // от Менеджера -> к Директору
                4 => 6, // от Агента -> к Менеджеру
                default => 5,
            };

            $stmt_update = $pdo->prepare("UPDATE `clients` SET `client_status` = ? WHERE `client_id` IN ($placeholders)");
            $stmt_update->execute(array_merge([$next_status], $validated_ids));
            break;

        case 'approve_draft':
        case 'approve':
        case 'approve_manager':
            $success_verb = 'одобрен';
            $source_status = match($action) {
                'approve_draft' => 3,
                'approve' => 5,
                'approve_manager' => 6,
            };
            $next_status = match($action) {
                'approve_draft', 'approve' => 1,
                'approve_manager' => 5,
            };

            $sql_check = "SELECT COUNT(*) FROM `clients` WHERE `client_id` IN ($placeholders) AND `client_status` = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute(array_merge($validated_ids, [$source_status]));
            if ($stmt_check->fetchColumn() != count($validated_ids)) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые анкеты не могут быть одобрены из их текущего статуса!', 'error', '');
            }

            $stmt_update = $pdo->prepare("UPDATE `clients` SET `client_status` = ? WHERE `client_id` IN ($placeholders)");
            $stmt_update->execute(array_merge([$next_status], $validated_ids));
            break;

        case 'decline':
            $success_verb = 'отклонен';
            $user_group = (int)$user_data['user_group'];

            // Определяем, из какого статуса можно отклонять в зависимости от роли
            $source_status = 0;
            if ($user_group === 1) { // Директор
                $source_status = 5;
            } elseif ($user_group === 3) { // Менеджер
                $source_status = 6;
            } else {
                $pdo->rollBack();
                message('Ошибка', 'У вас нет прав для выполнения этого действия!', 'error', '');
            }

            // Проверка, что все анкеты в нужном статусе
            $sql_check = "SELECT COUNT(*) FROM `clients` WHERE `client_id` IN ($placeholders) AND `client_status` = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute(array_merge($validated_ids, [$source_status]));
            if ($stmt_check->fetchColumn() != count($validated_ids)) {
                $pdo->rollBack();
                message('Ошибка', 'Некоторые анкеты не могут быть отклонены из их текущего статуса!', 'error', '');
            }

            // Действие: Устанавливаем статус 3 (Черновики) и причину отказа "-"
            $stmt_update = $pdo->prepare("
                UPDATE `clients` 
                SET `client_status` = 3, `rejection_reason` = '-' 
                WHERE `client_id` IN ($placeholders)
            ");
            $stmt_update->execute($validated_ids);
            break;

        case 'pay_credit':
            $success_ids = [];
            $failed_ids = [];

            // 1. Получаем все необходимые данные по анкетам и их агентам
            $sql_clients = "
                SELECT 
                    c.client_id, c.agent_id, c.sale_price, c.payment_status, c.client_status,
                    u.user_balance, u.user_credit_limit
                FROM `clients` c
                LEFT JOIN `users` u ON c.agent_id = u.user_id
                WHERE c.client_id IN ($placeholders)
            ";
            $stmt_clients = $pdo->prepare($sql_clients);
            $stmt_clients->execute($validated_ids);
            $clients_data = $stmt_clients->fetchAll(PDO::FETCH_ASSOC);

            // 2. Проверяем каждую анкету и группируем по агентам
            $debits_by_agent = [];
            foreach ($clients_data as $client) {
                // Предварительная проверка статуса анкеты
                if ($client['client_status'] != 2 || $client['payment_status'] != 0) {
                    $failed_ids[] = $client['client_id'] . ' (неверный статус)';
                    continue;
                }
                if (!$client['agent_id']) {
                    $failed_ids[] = $client['client_id'] . ' (не назначен агент)';
                    continue;
                }

                // Проверка кредитного лимита агента
                $agent_id = $client['agent_id'];
                $sale_price = (float)$client['sale_price'];
                $agent_balance = (float)($debits_by_agent[$agent_id]['new_balance'] ?? $client['user_balance']);
                $agent_credit_limit = (float)$client['user_credit_limit'];

                if (($agent_balance - $sale_price) < -$agent_credit_limit) {
                    $failed_ids[] = $client['client_id'] . ' (недостаточно лимита у агента)';
                    continue;
                }

                // Если проверка пройдена, обновляем данные и выполняем оплату
                $paid_from_balance = max(0, $agent_balance);
                $paid_from_credit = $sale_price - $paid_from_balance;

                $stmt_update_client = $pdo->prepare(
                    "UPDATE `clients` SET 
                        `payment_status` = 2, 
                        `paid_from_balance` = :paid_from_balance, 
                        `paid_from_credit` = :paid_from_credit 
                     WHERE `client_id` = :client_id"
                );
                $stmt_update_client->execute([
                    ':paid_from_balance' => $paid_from_balance,
                    ':paid_from_credit'  => $paid_from_credit,
                    ':client_id'         => $client['client_id']
                ]);

                // Обновляем баланс агента в PHP для следующей итерации (если есть еще анкеты этого же агента)
                if (!isset($debits_by_agent[$agent_id])) {
                    $debits_by_agent[$agent_id] = [
                        'total_debit' => 0,
                        'new_balance' => $client['user_balance']
                    ];
                }
                $debits_by_agent[$agent_id]['total_debit'] += $sale_price;
                $debits_by_agent[$agent_id]['new_balance'] -= $sale_price;

                $success_ids[] = $client['client_id'];
            }

            // 3. Обновляем балансы всех затронутых агентов одним запросом на каждого
            if (!empty($debits_by_agent)) {
                $stmt_update_agent = $pdo->prepare("UPDATE `users` SET `user_balance` = `user_balance` - ? WHERE `user_id` = ?");
                foreach ($debits_by_agent as $agent_id => $data) {
                    $stmt_update_agent->execute([$data['total_debit'], $agent_id]);
                }
            }

            $pdo->commit();

            // 4. Формируем детальный отчет
            $success_count = count($success_ids);
            $fail_count = count($failed_ids);
            $message_text = '';

            if ($success_count > 0) {
                $message_text .= "Успешно оплачено в кредит {$success_count} анкет.<br>";
            }
            if ($fail_count > 0) {
                $message_text .= "Не удалось оплатить {$fail_count} анкет:<br>" . implode('<br>', $failed_ids);
            }

            message('Результат операции', $message_text, 'info', 'reload');
            exit; // Выходим, чтобы не сработало стандартное сообщение об успехе

        default:
            $pdo->rollBack();
            message('Ошибка', 'Неизвестное действие!', 'error', '');
            break;
    }

    $pdo->commit();

    // --- НАЧАЛО ИСПРАВЛЕННОГО БЛОКА ФОРМИРОВАНИЯ СООБЩЕНИЯ ---
    $count = count($validated_ids);
    $noun = 'анкета';
    $verb_ending = 'а'; // Окончание для единственного числа женского рода (восстановленА)

    if ($count > 1) {
        $verb_ending = 'ы'; // Окончание для множественного числа (восстановленЫ)
        if ($count % 10 >= 2 && $count % 10 <= 4 && !($count % 100 >= 12 && $count % 100 <= 14)) {
            $noun = 'анкеты';
        } else {
            $noun = 'анкет';
        }
    }

    $message_text = "Успешно {$success_verb}{$verb_ending} {$count} {$noun}.";
    message('Уведомление', $message_text, 'success', 'reload');
    // --- КОНЕЦ ИСПРАВЛЕННОГО БЛОКА ---

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('DB Error (mass-client-action): ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка базы данных. Попробуйте позже.', 'error', '');
}