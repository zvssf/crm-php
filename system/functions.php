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