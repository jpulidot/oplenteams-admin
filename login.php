<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth-lite.php';

oplen_auth_session_start();
if (oplen_auth_user()) {
    header('Location: index.php');
    exit;
}

$_SESSION['login_csrf']??=bin2hex(random_bytes(24));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim(isset($_POST['email']) ? (string) $_POST['email'] : ''));
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if (!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['login_csrf'],$_POST['csrf'])) {
        $error = 'Reload the page before signing in.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email and password.';
    } else {
        try {
            $pdo = oplen_pdo();
            $q = $pdo->prepare("SELECT a.id,a.password_hash,a.status,t.status AS team_status FROM admin_accounts a JOIN team_members t ON t.id=a.team_member_id WHERE a.email=? LIMIT 1");
            $q->execute([$email]);
            $row = $q->fetch();

            if ($row && $row['status'] === 'active' && $row['team_status'] === 'active' && password_verify($password, $row['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $row['id'];
                $u = $pdo->prepare('UPDATE admin_accounts SET last_login_at=NOW() WHERE id=?');
                $u->execute([$row['id']]);
                header('Location: index.php');
                exit;
            }
            $error = 'Invalid email or password.';
        } catch (Throwable $e) {
            error_log('Oplen login error: ' . $e->getMessage());
            $error = 'Authentication is temporarily unavailable. Please try again.';
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#050506">
<title>Sign in · Oplen Teams Admin</title>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/css/auth.css?v=<?= rawurlencode(OPLEN_ADMIN_VERSION) ?>">
</head>
<body>
<main class="auth-shell">
<section class="auth-card">
<img src="/assets/img/logo-white.svg" alt="Oplen" class="auth-logo">
<span class="auth-label">ADMIN</span>
<h1>Welcome back.</h1>
<p>Sign in to the Oplen control center.</p>
<?php if ($error !== ''): ?><div class="auth-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" autocomplete="on"><input type="hidden" name="csrf" value="<?=e($_SESSION['login_csrf'])?>">
<label>Email<input type="email" name="email" autocomplete="username" required autofocus></label>
<label>Password<input type="password" name="password" autocomplete="current-password" required></label>
<button type="submit">Sign in</button>
</form>
</section>
</main>
</body>
</html>
