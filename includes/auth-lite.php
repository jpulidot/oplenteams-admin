<?php
require_once __DIR__ . '/database.php';

function oplen_auth_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode','1');
    ini_set('session.use_only_cookies','1');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_name('oplen_admin');
    session_start();
}

function oplen_auth_user() {
    oplen_auth_session_start();
    $adminId = isset($_SESSION['admin_id']) ? (string) $_SESSION['admin_id'] : '';
    if ($adminId === '') {
        return null;
    }

    try {
        $pdo = oplen_pdo();
        $q = $pdo->prepare("SELECT a.id,a.email,a.status,a.last_login_at,r.name AS role_name,t.first_name,t.last_name,t.status AS team_status FROM admin_accounts a JOIN admin_roles r ON r.id=a.role_id JOIN team_members t ON t.id=a.team_member_id WHERE a.id=? LIMIT 1");
        $q->execute([$adminId]);
        $user = $q->fetch();
        if (!$user || $user['status'] !== 'active' || $user['team_status'] !== 'active') {
            unset($_SESSION['admin_id']);
            return null;
        }
        return $user;
    } catch (Throwable $e) {
        error_log('Oplen auth lookup: ' . $e->getMessage());
        return null;
    }
}

function oplen_auth_require_login() {
    $user = oplen_auth_user();
    if ($user) {
        return $user;
    }
    header('Location: login.php');
    exit;
}
