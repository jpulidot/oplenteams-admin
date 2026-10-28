<?php
require __DIR__ . '/includes/auth-lite.php';
oplen_auth_session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], isset($params['domain']) ? $params['domain'] : '', !empty($params['secure']), !empty($params['httponly']));
}
session_destroy();
header('Location: login.php');
exit;
