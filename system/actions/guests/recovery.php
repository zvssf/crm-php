<?php

$login = valid($_POST['login'] ?? '') ?? '';
    
if (empty($login)) {
    message('Ошибка', 'Введите логин!', 'error', '');
  }
  if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
    message('Ошибка', 'Недопустимое значение email!', 'error', '');
  }




  try {
    $pdo = db_connect();

    // 1. Ищем пользователя по логину (email)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_login = :login");
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Даже если пользователя нет, мы говорим "Заявка отправлена" для безопасности (чтобы нельзя было перебирать email-ы)
    // Но уведомление шлем только если пользователь реально существует.
    if ($user) {
        // 2. Находим всех директоров (Группа 1)
        $stmt_directors = $pdo->query("SELECT user_id FROM users WHERE user_group = 1 AND user_status = 1");
        $directors = $stmt_directors->fetchAll(PDO::FETCH_COLUMN);

        // 3. Отправляем уведомление каждому директору
        $msg_title = 'Запрос восстановления пароля';
        $msg_body = "Пользователь {$user['user_firstname']} {$user['user_lastname']} ({$user['user_login']}) запрашивает восстановление пароля.";
        $link = "/?page=edit-customer&id={$user['user_id']}";

        foreach ($directors as $director_id) {
            send_notification($pdo, $director_id, $msg_title, $msg_body, 'warning', $link);
        }
    }

    // Имитация успешной отправки для пользователя
    message('Заявка принята', 'Если аккаунт существует, заявка на восстановление отправлена администрации.', 'success', 'login');

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
}