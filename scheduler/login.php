<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/lang.php';
session_start();

if (isset($_SESSION['scheduler_email'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT * FROM scheduler_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['scheduler_email'] = $user['email'];
        header('Location: index.php');
        exit;
    } else {
        $error = t('incorrect_credentials');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('scheduler_access') ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-bg">
    <div style="position: absolute; top: 15px; right: 20px;">
        <?= langSwitcher('light') ?>
    </div>
    <div class="login-container">
        <h2 style="text-align:center;">📅 <?= t('scheduler_access') ?></h2>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label><?= t('email') ?></label>
                <input type="email" name="email" required placeholder="scheduler@example.com">
            </div>
            <div class="form-group">
                <label><?= t('password') ?></label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= t('enter_btn') ?></button>
        </form>
    </div>
</body>
</html>