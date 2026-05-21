<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        header('Location: dashboard.php');
        exit;
    }

    $error = t('invalid_user_pass');
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin_login_title') ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header>
    <div class="header-content">
        <div class="logo"><span>🔐</span><span><?= t('admin_access') ?></span></div>
        <div style="display:flex; gap:15px; align-items:center;">
            <div class="role-badge"><?= t('password_required_badge') ?></div>
            <?= langSwitcher() ?>
        </div>
    </div>
</header>
<div class="auth-container">
    <div class="card">
        <h2 class="card-title"><?= t('enter_panel') ?></h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="username"><?= t('username') ?></label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password"><?= t('password') ?></label>
                <input type="password" name="password" id="password" required>
            </div>
            <button class="btn btn-primary btn-block" type="submit"><?= t('enter_panel_btn') ?></button>
        </form>
    </div>
</div>
</body>
</html>
