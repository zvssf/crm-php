<?php
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
mb_http_output('UTF-8');
mb_language('uni');
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Europe/Moscow');
setlocale(LC_ALL, 'ru_RU.UTF-8', 'ru_RU', 'Russian');

define('ROOT', dirname(__DIR__));
define('SYSTEM', ROOT . '/system');

require_once SYSTEM . '/config.php';
require_once SYSTEM . '/functions.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '';

if ($requestUri === '/') {
    redirect('login');
}

$parts = parse_url($requestUri);
$path = isset($parts['path']) ? trim($parts['path'], '/') : '';
$query = $_GET;

if (!empty($path) && !preg_match('/^[a-z0-9_-]+$/i', $path)) {
    redirect('notfound');
}

$page = $query['page'] ?? '';
$form = $query['form'] ?? '';

$isValidIdentifier = fn($str) => preg_match('/^[a-z0-9_-]+$/i', $str);

if (!empty($page) && !$isValidIdentifier($page)) {
    redirect('notfound');
}
if (!empty($form) && !$isValidIdentifier($form)) {
    redirect('notfound');
}

$sessionStatus  = false;
$user_data      = null;

$sessionKey = valid($_COOKIE['session_key'] ?? '');
if (!empty($sessionKey)) {
    $sessionKey = rawurldecode(urlencode($sessionKey));
    try {
        $pdo = db_connect();
        if ($page === 'logout') {
            $stmt = $pdo->prepare("
            UPDATE `users` 
            SET `user_session_key`      = '' 
            WHERE `user_session_key`    = :session_key
            ");
        } else {
            $stmt = $pdo->prepare("
            SELECT * 
            FROM `users` 
            WHERE `user_session_key`    = :session_key
            ");
        }
        $stmt->execute([
            ':session_key' => $sessionKey
        ]);

        if ($page === 'logout') {
            setrawcookie('session_key', '', time() - 2592000, '/', '', false, true);
            redirect('login');
        }

        if ($stmt->rowCount() === 1) {
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user_data['user_password'] === decryptSTR($sessionKey)) {
                $sessionStatus = true;
            }
        }
    } catch (PDOException $e) {
        error_log('DB Error: ' . $e->getMessage());
        message('Ошибка', 'Произошла ошибка. Попробуйте позже.', 'error', '');
    }
}

$user_group_text = '';
$userGoupPrivatPages = [];
$userGoupPrivatForms = [];

$permissions = require_once SYSTEM . '/permissions.php';

$roleMap = [
    1 => 'director',
    2 => 'supervisor',
    3 => 'manager',
    4 => 'agent',
];

if ($sessionStatus && isset($user_data['user_group'])) {
    $role = $roleMap[$user_data['user_group']] ?? null;

    if ($role && isset($permissions[$role])) {
        $user_group_text        = $permissions[$role]['text'];
        $userGoupPrivatPages    = $permissions[$role]['pages'];
        $userGoupPrivatForms    = $permissions[$role]['forms'];
    }

    if (in_array($user_data['user_status'], [0, 2], true)) {
        redirect('logout');
    }

    if (!empty($page) && !in_array($page, $userGoupPrivatPages, true)) {
        redirect('logout');
    }
}



if (!empty($form)) {
    $formPath = $sessionStatus
        ? SYSTEM . '/actions/authorized/'   . $form . '.php'
        : SYSTEM . '/actions/guests/'       . $form . '.php';

    if (!in_array($form, $userGoupPrivatForms, true) && $sessionStatus) {
        redirect('logout');
    }

    if (file_exists($formPath)) {
        require_once $formPath;
        exit;
    } else {
        redirect('notfound');
    }
}

$paths = [
    'all'        => SYSTEM . '/pages/all/'          . $page . '.php',
    'authorized' => SYSTEM . '/pages/authorized/'   . $page . '.php',
    'guests'     => SYSTEM . '/pages/guests/'       . $page . '.php',
];

if (file_exists($paths['all'])) {
    require_once $paths['all'];
} elseif ($sessionStatus && file_exists($paths['authorized'])) {
    require_once $paths['authorized'];
} elseif (!$sessionStatus && file_exists($paths['guests'])) {
    require_once $paths['guests'];
} elseif (in_array($page, ['login', 'recovery'], true) && $sessionStatus) {
    redirect('dashboard');
} else {
    redirect('notfound');
}