<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$message = null;
$messageType = 'success';

// Cerrar sesión del empleado
if (isset($_GET['logout'])) {
    unset($_SESSION['employee_id'], $_SESSION['employee_name']);
    header('Location: empleado.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. LÓGICA DE LOGIN
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $stmt = db()->prepare("SELECT * FROM employees WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $emp = $stmt->fetch();
        
        if ($emp && password_verify($password, $emp['password_hash'])) {
            $_SESSION['employee_id'] = $emp['id'];
            $_SESSION['employee_name'] = $emp['full_name'];
            header('Location: empleado.php');
            exit;
        } else {
            $message = t('invalid_credentials');
            $messageType = 'error';
        }
    } 
    // B. LÓGICA DE REGISTRO (PUNCH)
    elseif (isset($_POST['record_type']) && !empty($_SESSION['employee_id'])) {
        try {
            $fullName = $_SESSION['employee_name'];
            $recordType = $_POST['record_type'] ?? '';
            $latitude = trim($_POST['latitude'] ?? '');
            $longitude = trim($_POST['longitude'] ?? '');
            $accuracy = trim($_POST['accuracy'] ?? '');

            if (!in_array($recordType, ['entrada', 'salida'], true)) {
                throw new RuntimeException(t('invalid_record_type'));
            }

            $photoPath = saveUploadedPhoto($_FILES['photo'] ?? []);

            $stmt = db()->prepare("INSERT INTO attendance_records (
                full_name, record_type, record_date, record_time, recorded_at, timezone,
                latitude, longitude, accuracy, photo_path, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $now = appNow();
            $stmt->execute([
                $fullName,
                $recordType,
                $now->format('Y-m-d'),
                $now->format('H:i:s'),
                $now->format('Y-m-d H:i:s'),
                APP_TIMEZONE,
                $latitude !== '' ? $latitude : null,
                $longitude !== '' ? $longitude : null,
                $accuracy !== '' ? $accuracy : null,
                $photoPath,
                $now->format('Y-m-d H:i:s')
            ]);

            $message = $recordType === 'entrada'
                ? t('clock_in_success')
                : t('clock_out_success');
        } catch (Throwable $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

$today = appDate();
$stmt = db()->prepare("SELECT * FROM attendance_records WHERE record_date = ? ORDER BY recorded_at DESC");
$stmt->execute([$today]);
$todayRecords = $stmt->fetchAll();

// Determinar el último registro exclusivamente del usuario logueado
$lastRecord = null;
$currentStatus = t('not_registered');
$statusClass = '';

// --- NUEVAS VARIABLES DE HORARIO ---
$scheduleToday = null;
$lateAlert = false;
$hasPunchedIn = false;

if (!empty($_SESSION['employee_id'])) {
    // 1. Obtener el estado actual (Entrada/Salida)
    $stmtUser = db()->prepare("SELECT * FROM attendance_records WHERE record_date = ? AND full_name = ? ORDER BY recorded_at DESC LIMIT 1");
    $stmtUser->execute([$today, $_SESSION['employee_name']]);
    $lastRecord = $stmtUser->fetch();

    if ($lastRecord) {
        $currentStatus = $lastRecord['record_type'] === 'entrada' ? t('working') : t('out_of_office');
        $statusClass = $lastRecord['record_type'] === 'entrada' ? 'success' : 'warning';
    }

    // 2. Revisar si ya registró entrada el día de hoy (para la alerta)
    $stmtPunch = db()->prepare("SELECT COUNT(*) FROM attendance_records WHERE record_date = ? AND full_name = ? AND record_type = 'entrada'");
    $stmtPunch->execute([$today, $_SESSION['employee_name']]);
    $hasPunchedIn = $stmtPunch->fetchColumn() > 0;

    // 3. Obtener el horario agendado para hoy
    $stmtSched = db()->prepare("SELECT * FROM schedules WHERE employee_name = ? AND schedule_date = ?");
    $stmtSched->execute([$_SESSION['employee_name'], $today]);
    $scheduleToday = $stmtSched->fetch();

    // 4. Calcular "Alerta Roja" si está tarde
    if ($scheduleToday && !$scheduleToday['is_off'] && !empty($scheduleToday['start_time']) && !$hasPunchedIn) {
        $nowTime = appNow(); 
        $startTimeObj = new DateTime($today . ' ' . $scheduleToday['start_time'], new DateTimeZone(APP_TIMEZONE));
        
        if ($nowTime > $startTimeObj) {
            $lateAlert = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('app_name')) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
    <div class="header-content">
        <div class="logo"><span>⏱️</span><span><?= e(t('app_name')) ?></span></div>
        <div style="display:flex; gap:15px; align-items:center;">
            <div class="role-badge"><?= t('employee_mode') ?></div>
            <?= langSwitcher() ?>
        </div>
    </div>
</header>
<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if (empty($_SESSION['employee_id'])): ?>
        <div class="top-actions" style="margin-bottom:1rem;">
            <div class="hint"><?= t('identify_hint') ?></div>
            <a class="btn btn-primary" href="admin/login.php"><?= t('go_admin_panel') ?></a>
        </div>

        <div class="auth-container">
            <div class="card">
                <h2 class="card-title"><?= t('employee_access') ?></h2>
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="email"><?= t('email') ?></label>
                        <input type="email" name="email" id="email" required placeholder="user@example.com">
                    </div>
                    <div class="form-group">
                        <label for="password"><?= t('password') ?></label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit"><?= t('login') ?></button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <div class="top-actions" style="margin-bottom:1rem;">
            <div class="hint"><?= t('logged_in_as') ?> <strong><?= e($_SESSION['employee_name']) ?></strong> | <?= t('mandatory_timezone') ?> <strong><?= e(APP_TIMEZONE) ?></strong></div>
            <div class="actions">
                <a class="btn btn-secondary" href="?logout=1"><?= t('logout') ?></a>
                <a class="btn btn-primary" href="admin/login.php"><?= t('admin_panel_link') ?></a>
            </div>
        </div>

        <div class="clock-display">
            <div class="clock-time" id="clock">--:--:--</div>
            <div class="clock-date" id="date"><?= t('loading_date') ?></div>
        </div>

        <div class="card" style="border: 1px solid #ddd; margin-bottom: 1rem; text-align: center; background: #fdfdfd; border-left: 4px solid var(--primary);">
            <h3 style="margin-top:0; font-size: 1.1rem; color: #555;"><?= t('my_schedule_today') ?></h3>
            
            <?php if (!$scheduleToday): ?>
                <p style="color: gray; margin: 0;"><?= t('no_schedule_today') ?></p>
            <?php elseif ($scheduleToday['is_off']): ?>
                <p style="color: var(--primary); font-weight: bold; margin: 0; font-size: 1.2rem;"><?= t('day_off') ?></p>
            <?php else: ?>
                <?php 
                    $start12h = date("g:i A", strtotime($scheduleToday['start_time']));
                    $end12h = date("g:i A", strtotime($scheduleToday['end_time']));
                ?>
                <p style="font-size: 1.3rem; font-weight: bold; margin: 5px 0;">
                    <?= e($start12h) ?> - <?= e($end12h) ?>
                </p>
                
                <?php if ($lateAlert): ?>
                    <div style="background-color: #fee2e2; color: #b91c1c; padding: 8px; border-radius: 4px; font-weight: bold; margin-top: 10px; border: 1px solid #f87171;">
                        <?= t('late_alert') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="status-grid">
            <div class="status-card <?= e($statusClass) ?>">
                <div class="status-label"><?= t('your_current_status') ?></div>
                <div class="status-value"><?= e($currentStatus) ?></div>
            </div>
            <div class="status-card">
                <div class="status-label"><?= t('your_last_record') ?></div>
                <div class="status-value"><?= e($lastRecord['record_time'] ?? '--:--') ?></div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title"><?= t('record_attendance') ?></h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="photo"><?= t('photo_evidence') ?></label>
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment" required>
                    <div class="hint" style="margin-top:.5rem;"><?= t('photo_hint') ?></div>
                </div>

                <div class="card" style="background:#f8fafc; margin-top:1rem;">
                    <strong><?= t('user_location') ?></strong>
                    <p class="hint" id="location_status" style="margin-top:.35rem;"><?= t('getting_location') ?></p>
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    <input type="hidden" name="accuracy" id="accuracy">
                </div>

                <div class="grid-2" style="margin-top:1rem;">
                    <button class="btn btn-success" type="submit" name="record_type" value="entrada"><?= t('btn_clock_in') ?></button>
                    <button class="btn btn-danger" type="submit" name="record_type" value="salida"><?= t('btn_clock_out') ?></button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title"><?= t('today_records') ?></h2>
            <?php if (!$todayRecords): ?>
                <p class="hint text-center"><?= t('no_records_today') ?></p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                        <tr>
                            <th><?= t('employee') ?></th>
                            <th><?= t('type') ?></th>
                            <th><?= t('time') ?></th>
                            <th><?= t('location') ?></th>
                            <th><?= t('photo') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($todayRecords as $record): ?>
                            <tr>
                                <td><?= e($record['full_name']) ?></td>
                                <td><span class="badge badge-<?= e($record['record_type']) ?>"><?= strtoupper(e($record['record_type'])) ?></span></td>
                                <td><?= e($record['record_time']) ?></td>
                                <td>
                                    <?php if ($record['latitude'] && $record['longitude']): ?>
                                        <a href="https://maps.google.com/?q=<?= e($record['latitude']) ?>,<?= e($record['longitude']) ?>" target="_blank"><?= t('view_map') ?></a>
                                    <?php else: ?>
                                        <span class="hint"><?= t('no_location') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><img src="<?= e($record['photo_path']) ?>" class="photo-thumb" alt="<?= t('photo') ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<script>
const locale = <?= json_encode(currentLang() === 'es' ? 'es-ES' : 'en-US') ?>;
const appFormatterTime = new Intl.DateTimeFormat(locale, {timeZone: '<?= APP_TIMEZONE ?>', hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false});
const appFormatterDate = new Intl.DateTimeFormat(locale, {timeZone: '<?= APP_TIMEZONE ?>', weekday:'long', year:'numeric', month:'long', day:'numeric'});
function updateClock(){
    let clockEl = document.getElementById('clock');
    let dateEl = document.getElementById('date');
    if(!clockEl || !dateEl) return;
    
    const now = new Date();
    clockEl.textContent = appFormatterTime.format(now);
    dateEl.textContent = appFormatterDate.format(now);
}
setInterval(updateClock, 1000); updateClock();

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position){
        let latEl = document.getElementById('latitude');
        let lonEl = document.getElementById('longitude');
        let accEl = document.getElementById('accuracy');
        let locStatus = document.getElementById('location_status');
        
        if(latEl) latEl.value = position.coords.latitude;
        if(lonEl) lonEl.value = position.coords.longitude;
        if(accEl) accEl.value = position.coords.accuracy;
        if(locStatus) locStatus.textContent = <?= json_encode(t('geo_captured')) ?>;
    }, function(error){
        let locStatus = document.getElementById('location_status');
        if(locStatus) locStatus.textContent = <?= json_encode(t('geo_error')) ?> + error.message;
    });
} else {
    let locStatus = document.getElementById('location_status');
    if(locStatus) locStatus.textContent = <?= json_encode(t('geo_unsupported')) ?>;
}
</script>
</body>
</html>