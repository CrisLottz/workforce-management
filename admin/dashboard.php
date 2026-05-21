<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';
requireAdmin();

$messageEmp = null;
$messageEmpType = 'success';

// --- LÓGICA: AGREGAR NUEVO EMPLEADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_employee') {
    try {
        $name = trim($_POST['emp_name'] ?? '');
        $email = trim($_POST['emp_email'] ?? '');
        $password = $_POST['emp_password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException(t('all_fields_required'));
        }

        // Verificar si el correo ya existe
        $stmtCheck = db()->prepare("SELECT COUNT(*) FROM employees WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new RuntimeException(t('email_already_exists'));
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare("INSERT INTO employees (full_name, email, password_hash, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed, date('Y-m-d H:i:s')]);

        $messageEmp = sprintf(t('employee_created'), $name);
    } catch (Throwable $e) {
        $messageEmp = $e->getMessage();
        $messageEmpType = 'error';
    }
}

// --- LÓGICA: ELIMINAR EMPLEADO ---
if (isset($_GET['delete_emp'])) {
    $id = (int) $_GET['delete_emp'];
    $stmt = db()->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: dashboard.php?deleted_emp=1');
    exit;
}

// --- LÓGICA: ELIMINAR REGISTRO DE ASISTENCIA ---
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = db()->prepare("SELECT photo_path FROM attendance_records WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    if ($record) {
        $filePath = BASE_PATH . '/' . $record['photo_path'];
        if (is_file($filePath)) {
            @unlink($filePath);
        }
        $deleteStmt = db()->prepare("DELETE FROM attendance_records WHERE id = ?");
        $deleteStmt->execute([$id]);
    }
    header('Location: dashboard.php?deleted=1');
    exit;
}

// Extraer datos para las vistas
$date = $_GET['date'] ?? appDate();
$employee = $_GET['employee'] ?? '';
$type = $_GET['type'] ?? '';
$records = getFilteredRecords($date, $employee, $type);
$employees = getEmployees();
$summary = getSummaryByEmployee($records);

// Extraer lista de empleados con acceso al sistema
$dbEmployees = db()->query("SELECT id, full_name, email FROM employees ORDER BY full_name ASC")->fetchAll();

// Estadísticas
$totalEmployees = count(array_unique(array_column($records, 'full_name')));
$presentTodayStmt = db()->prepare("SELECT COUNT(DISTINCT full_name) FROM attendance_records WHERE record_date = ? AND record_type = 'entrada'");
$presentTodayStmt->execute([appDate()]);
$presentToday = (int) $presentTodayStmt->fetchColumn();

$totalMinutes = 0;
$durationCount = 0;
foreach ($summary as $item) {
    if ($item['duration'] !== '--') {
        preg_match('/(\d+)h\s+(\d+)m/', $item['duration'], $match);
        if ($match) {
            $totalMinutes += ((int)$match[1] * 60) + (int)$match[2];
            $durationCount++;
        }
    }
}
$avgHours = $durationCount ? round(($totalMinutes / 60) / $durationCount, 1) . 'h' : '0h';
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin_panel_title') ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header>
    <div class="header-content">
        <div class="logo"><span>📊</span><span><?= t('admin_panel_title') ?></span></div>
        <div class="actions">
            <span class="role-badge"><?= t('user_label') ?> <?= e($_SESSION['admin_username'] ?? 'admin') ?></span>
            <?= langSwitcher() ?>
            <a class="logout-link" href="logout.php"><?= t('logout') ?></a>
        </div>
    </div>
</header>
<div class="container">
    <?php if (!empty($_GET['deleted'])): ?>
        <div class="alert alert-success"><?= t('record_deleted') ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['deleted_emp'])): ?>
        <div class="alert alert-success"><?= t('employee_access_deleted') ?></div>
    <?php endif; ?>

    <div class="top-actions" style="margin-bottom:1rem;">
        <div class="hint"><?= t('timezone_notice') ?> (<strong><?= e(APP_TIMEZONE) ?></strong>).</div>
        <div class="actions">
            <a class="btn btn-secondary" href="export.php?date=<?= urlencode($date) ?>&employee=<?= urlencode($employee) ?>&type=<?= urlencode($type) ?>"><?= t('export_csv') ?></a>
            <a class="btn btn-primary" href="../index.php"><?= t('view_public_portal') ?></a>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid var(--primary);">
        <h2 class="card-title"><?= t('employee_management') ?></h2>
        <?php if ($messageEmp): ?>
            <div class="alert alert-<?= $messageEmpType === 'success' ? 'success' : 'error' ?>"><?= e($messageEmp) ?></div>
        <?php endif; ?>
        
        <div class="grid-2">
            <div>
                <h3 style="margin-bottom: 1rem; font-size: 1rem;"><?= t('add_new_employee') ?></h3>
                <form method="post">
                    <input type="hidden" name="action" value="add_employee">
                    <div class="form-group">
                        <label><?= t('full_name') ?></label>
                        <input type="text" name="emp_name" required placeholder="<?= t('name_placeholder') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= t('email_user') ?></label>
                        <input type="email" name="emp_email" required placeholder="user@example.com">
                    </div>
                    <div class="form-group">
                        <label><?= t('secure_password') ?></label>
                        <input type="text" name="emp_password" required placeholder="<?= t('secure_password') ?>">
                    </div>
                    <button class="btn btn-success btn-block" type="submit"><?= t('create_access') ?></button>
                </form>
            </div>
            <div>
                <h3 style="margin-bottom: 1rem; font-size: 1rem;"><?= t('active_employees') ?></h3>
                <div class="table-container" style="max-height: 280px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th><?= t('name') ?></th>
                                <th><?= t('email_col') ?></th>
                                <th><?= t('action') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$dbEmployees): ?>
                                <tr><td colspan="3" class="text-center"><?= t('no_employees') ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($dbEmployees as $emp): ?>
                                    <tr>
                                        <td><strong><?= e($emp['full_name']) ?></strong></td>
                                        <td class="small"><?= e($emp['email']) ?></td>
                                        <td>
                                            <a class="btn btn-danger small" href="?delete_emp=<?= (int)$emp['id'] ?>" onclick="return confirm('<?= t('confirm_delete_employee') ?>')"><?= t('delete_btn') ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?= $totalEmployees ?></div><div class="stat-label"><?= t('total_filtered') ?></div></div>
        <div class="stat-card"><div class="stat-number"><?= $presentToday ?></div><div class="stat-label"><?= t('present_today') ?></div></div>
        <div class="stat-card"><div class="stat-number"><?= e($avgHours) ?></div><div class="stat-label"><?= t('avg_hours') ?></div></div>
    </div>

    <div class="card">
        <h2 class="card-title"><?= t('attendance_filters') ?></h2>
        <form method="get" class="filters">
            <div>
                <label for="date"><?= t('date') ?></label>
                <input type="date" name="date" id="date" value="<?= e($date) ?>">
            </div>
            <div>
                <label for="employee"><?= t('employee') ?></label>
                <select name="employee" id="employee">
                    <option value=""><?= t('all') ?></option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= e($emp) ?>" <?= $employee === $emp ? 'selected' : '' ?>><?= e($emp) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="type"><?= t('type') ?></label>
                <select name="type" id="type">
                    <option value=""><?= t('all') ?></option>
                    <option value="entrada" <?= $type === 'entrada' ? 'selected' : '' ?>><?= t('clock_in') ?></option>
                    <option value="salida" <?= $type === 'salida' ? 'selected' : '' ?>><?= t('clock_out') ?></option>
                </select>
            </div>
            <div>
                <button class="btn btn-primary btn-block" type="submit"><?= t('apply_filters') ?></button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title"><?= t('daily_summary') ?></h2>
        <?php if (!$summary): ?>
            <p class="hint text-center"><?= t('no_records_date') ?></p>
        <?php else: ?>
            <?php foreach ($summary as $item): ?>
                <div class="employee-card">
                    <div class="employee-avatar"><?= e(mb_strtoupper(mb_substr($item['name'], 0, 1))) ?></div>
                    <div>
                        <strong><?= e($item['name']) ?></strong><br>
                        <span class="hint"><?= e((string)$item['count']) ?> <?= t('records') ?></span>
                    </div>
                    <div class="actions">
                        <div><div class="time-main"><?= e($item['entrada'] ?? '--:--') ?></div><div class="time-label"><?= t('clock_in') ?></div></div>
                        <div><div class="time-main"><?= e($item['salida'] ?? '--:--') ?></div><div class="time-label"><?= t('clock_out') ?></div></div>
                        <div><div class="time-main"><?= e($item['duration']) ?></div><div class="time-label"><?= t('duration') ?></div></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 class="card-title"><?= t('detailed_records') ?></h2>
        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th><?= t('employee') ?></th>
                    <th><?= t('type') ?></th>
                    <th><?= t('date') ?></th>
                    <th><?= t('time') ?></th>
                    <th><?= t('location') ?></th>
                    <th><?= t('photo') ?></th>
                    <th><?= t('actions') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$records): ?>
                    <tr><td colspan="7" class="text-center"><?= t('no_records') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= e($record['full_name']) ?></td>
                            <td><span class="badge badge-<?= e($record['record_type']) ?>"><?= strtoupper(e($record['record_type'])) ?></span></td>
                            <td><?= e($record['record_date']) ?></td>
                            <td><?= e($record['record_time']) ?></td>
                            <td>
                                <?php if ($record['latitude'] && $record['longitude']): ?>
                                    <a href="https://maps.google.com/?q=<?= e($record['latitude']) ?>,<?= e($record['longitude']) ?>" target="_blank"><?= t('view_map') ?></a>
                                <?php else: ?>
                                    <span class="hint"><?= t('no_location') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><a href="../<?= e($record['photo_path']) ?>" target="_blank"><img src="../<?= e($record['photo_path']) ?>" class="photo-thumb" alt="<?= t('photo') ?>"></a></td>
                            <td><a class="btn btn-danger small" href="?delete=<?= (int)$record['id'] ?>" onclick="return confirm('<?= t('confirm_delete_record') ?>')"><?= t('delete') ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>