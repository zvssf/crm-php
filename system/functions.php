<?php
function db_connect() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        
        return $pdo;

        // $db = db_connect();
        // $stmt = $db->query("SELECT * FROM users");
        // $users = $stmt->fetchAll();
        
    } catch (PDOException $e) {
      error_log('DB Error: ' . $e->getMessage());
        exit('{
          "success_type":     "message",
          "msg_title":        "Ошибка",
          "msg_text":         "Ошибка подключения к базе данных!",
          "msg_type":         "error"
        }');
    }
}

function redirect($url) {
  exit(header('location: /?page=' . $url));
}

function redirectAJAX($url) {
  exit('{
    "success_type":     "redirect",
    "url":              "' . $url . '"
  }');
}

function valid($data) {
  if (is_array($data)) {
      return array_map('valid', $data);
  }
  if (!is_string($data) && !is_numeric($data)) {
      return '';
  }
  $data = (string) $data;
  $data = trim($data);
  $data = rawurldecode($data);
  $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  return $data;
}

function message($title, $text, $type, $url, $new_status = null) {
    $response = [
      "success_type" => "message",
      "msg_title"    => $title,
      "msg_text"     => $text,
      "msg_type"     => $type,
      "msg_url"      => $url ?: ''
    ];

    if ($new_status !== null) {
        $response["new_status"] = $new_status;
    }

    exit(json_encode($response));
  }

  function encryptSTR($plaintext) {
    $plaintext = (string) $plaintext;
    $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($plaintext, $cipher, ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext_raw === false) {
        return false;
    }
    $hmac = hash_hmac('sha256', $ciphertext_raw, ENCRYPTION_KEY, true);
    $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
    return $ciphertext;
}

function decryptSTR($ciphertext) {
    if (empty($ciphertext)) {
        return false;
    }
    $c = base64_decode($ciphertext, true);
    if ($c === false) {
        return false;
    }
    $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
    if (strlen($c) < ($ivlen + 32)) {
        return false;
    }
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, 32);
    $ciphertext_raw = substr($c, $ivlen + 32);
    $plaintext = openssl_decrypt($ciphertext_raw, $cipher, ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    if ($plaintext === false) {
        return false;
    }
    $calcmac = hash_hmac('sha256', $ciphertext_raw, ENCRYPTION_KEY, true);
    if (hash_equals($hmac, $calcmac)) {
        return $plaintext;
    }
    return false;
}

function incrementLoginAttempt($pdo, $login, $ip) {
  try {
      $stmt = $pdo->prepare("
          SELECT attempts, user_login 
          FROM login_attempts 
          WHERE user_login  = :login 
          OR ip_address     = :ip
          LIMIT 1
      ");
      $stmt->execute([
        ':login'  => $login,
        ':ip'     => $ip
      ]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($row) {
          $newAttempts = $row['attempts'] + 1;

          if ($newAttempts >= 5) {
              $pdo->prepare("
              UPDATE users 
              SET user_status   = '2' 
              WHERE user_login  = :login
              ")->execute([
                ':login' => $login
              ]);

              $pdo->prepare("
              UPDATE login_attempts 
              SET attempts      = :attempts 
              WHERE user_login  = :login
              ")->execute([
                ':attempts' => $newAttempts,
                ':login'    => $login
              ]);
          } else {
              $pdo->prepare("
              UPDATE login_attempts 
              SET attempts      = :attempts 
              WHERE user_login  = :login 
              OR ip_address     = :ip
              ")->execute([
                ':attempts' => $newAttempts,
                ':login'    => $login,
                ':ip'       => $ip
              ]);
          }
      } else {
          $pdo->prepare("
              INSERT INTO login_attempts (user_login, ip_address, attempts)
              VALUES (:login, :ip, 1)
          ")->execute([
            ':login'  => $login,
            ':ip'     => $ip
          ]);
      }
  } catch (PDOException $e) {
      error_log('Login attempt log error: ' . $e->getMessage());
  }
}

function getIP() {
  foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
      if (!empty($_SERVER[$key])) {
          $ip = trim(explode(',', $_SERVER[$key])[0]);
          if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
              return $ip;
          }
      }
  }
  return '127.0.0.1';
}

function process_agent_repayments($pdo, $agent_id, $transaction_amount) {
    // Шаг 1: Получаем текущий баланс агента (до транзакции)
    $stmt_agent = $pdo->prepare("SELECT `user_balance` FROM `users` WHERE `user_id` = :agent_id FOR UPDATE");
    $stmt_agent->execute([':agent_id' => $agent_id]);
    $current_balance = (float) $stmt_agent->fetchColumn();

    // Шаг 2: Рассчитываем "Пул для погашения"
    $repayment_pool = (float) $transaction_amount;
    if ($current_balance > 0) {
        $repayment_pool += $current_balance;
    }

    $spent_on_credit = 0;

    // Шаг 3: Фаза 1 — Расходование "Пула" на Кредитные анкеты (payment_status = 2)
    $stmt_credits = $pdo->prepare(
        "SELECT `client_id`, `paid_from_credit` FROM `clients` 
            WHERE `agent_id` = :agent_id AND `payment_status` = 2 
            ORDER BY `client_id` ASC FOR UPDATE"
    );
    $stmt_credits->execute([':agent_id' => $agent_id]);
    $credit_clients = $stmt_credits->fetchAll(PDO::FETCH_ASSOC);

    foreach ($credit_clients as $client) {
        if ($repayment_pool <= 0) break;

        $credit_amount = (float) $client['paid_from_credit'];
        $payment_amount = min($repayment_pool, $credit_amount);

        $stmt_update_credit = $pdo->prepare(
            "UPDATE `clients` SET 
                `paid_from_balance` = IFNULL(`paid_from_balance`, 0) + :payment_add, 
                `paid_from_credit` = IFNULL(`paid_from_credit`, 0) - :payment_subtract
                WHERE `client_id` = :client_id"
        );
        $stmt_update_credit->execute([
            ':payment_add'      => $payment_amount, 
            ':payment_subtract' => $payment_amount, 
            ':client_id'        => $client['client_id']
        ]);
        
        $repayment_pool -= $payment_amount;
        $spent_on_credit += $payment_amount;

        if (abs($credit_amount - $payment_amount) < 0.01) {
            $stmt_update_status = $pdo->prepare("UPDATE `clients` SET `payment_status` = 1 WHERE `client_id` = :client_id");
            $stmt_update_status->execute([':client_id' => $client['client_id']]);
        }
    }

    // Шаг 4: Фаза 2 — Расходование остатка "Пула" на Неоплаченные анкеты (payment_status = 0)
    $spent_on_unpaid = 0;
    if ($repayment_pool > 0) {
        $stmt_unpaid = $pdo->prepare(
            "SELECT `client_id`, `sale_price` FROM `clients` 
                WHERE `agent_id` = :agent_id AND `payment_status` = 0 
                ORDER BY `client_id` ASC FOR UPDATE"
        );
        $stmt_unpaid->execute([':agent_id' => $agent_id]);
        $unpaid_clients = $stmt_unpaid->fetchAll(PDO::FETCH_ASSOC);

        foreach ($unpaid_clients as $client) {
            if ($repayment_pool <= 0) break;

            $sale_price = (float) $client['sale_price'];
            if (round($repayment_pool, 2) >= round($sale_price, 2)) {
                $repayment_pool -= $sale_price;
                $spent_on_unpaid += $sale_price;
                
                $stmt_update_unpaid = $pdo->prepare(
                    "UPDATE `clients` SET 
                        `payment_status` = 1, 
                        `paid_from_balance` = :sale_price,
                        `paid_from_credit` = 0
                        WHERE `client_id` = :client_id"
                );
                $stmt_update_unpaid->execute([':sale_price' => $sale_price, ':client_id' => $client['client_id']]);
            } else {
                // Если на текущую анкету не хватает, просто пропускаем ее и ищем следующую, более дешевую
                continue; 
            }
        }
    }

    // Шаг 5: Финальное обновление баланса агента
    $final_balance = $current_balance + $transaction_amount - $spent_on_unpaid - $spent_on_credit;
    $stmt_update_balance = $pdo->prepare("UPDATE `users` SET `user_balance` = :final_balance WHERE `user_id` = :agent_id");
    $stmt_update_balance->execute([':final_balance' => $final_balance, ':agent_id' => $agent_id]);
}