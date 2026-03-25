<?php
require_once __DIR__ . '/includes/functions.php';

$message = null;
$messageType = 'success';

// Cerrar sesión del empleado
if (isset($_GET['logout'])) {
    unset($_SESSION['employee_id'], $_SESSION['employee_name']);
    header('Location: index.php');
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
            header('Location: index.php');
            exit;
        } else {
            $message = 'Credenciales inválidas.';
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
                throw new RuntimeException('Tipo de registro inválido.');
            }

            $photoPath = saveUploadedPhoto($_FILES['photo'] ?? []);

            $stmt = db()->prepare("INSERT INTO attendance_records (
                full_name, record_type, record_date, record_time, recorded_at, timezone,
                latitude, longitude, accuracy, photo_path, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $now = utahNow();
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
                ? 'Entrada registrada correctamente con hora de Utah.'
                : 'Salida registrada correctamente con hora de Utah.';
        } catch (Throwable $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

$today = utahDate();
$stmt = db()->prepare("SELECT * FROM attendance_records WHERE record_date = ? ORDER BY recorded_at DESC");
$stmt->execute([$today]);
$todayRecords = $stmt->fetchAll();

// Determinar el último registro exclusivamente del usuario logueado
$lastRecord = null;
$currentStatus = 'No registrado';
$statusClass = '';

if (!empty($_SESSION['employee_id'])) {
    $stmtUser = db()->prepare("SELECT * FROM attendance_records WHERE record_date = ? AND full_name = ? ORDER BY recorded_at DESC LIMIT 1");
    $stmtUser->execute([$today, $_SESSION['employee_name']]);
    $lastRecord = $stmtUser->fetch();

    if ($lastRecord) {
        $currentStatus = $lastRecord['record_type'] === 'entrada' ? 'Trabajando' : 'Fuera de oficina';
        $statusClass = $lastRecord['record_type'] === 'entrada' ? 'success' : 'warning';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
    <div class="header-content">
        <div class="logo"><span>⏱️</span><span><?= e(APP_NAME) ?></span></div>
        <div class="role-badge">Modo Empleado</div>
    </div>
</header>
<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if (empty($_SESSION['employee_id'])): ?>
        <div class="top-actions" style="margin-bottom:1rem;">
            <div class="hint">Identifícate para registrar tu asistencia.</div>
            <a class="btn btn-primary" href="admin/login.php">🔐 Ir al panel administrador</a>
        </div>

        <div class="auth-container">
            <div class="card">
                <h2 class="card-title">Acceso de Empleados</h2>
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" name="email" id="email" required placeholder="ejemplo@camposlawfirm.com">
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Iniciar Sesión</button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <div class="top-actions" style="margin-bottom:1rem;">
            <div class="hint">Sesión iniciada como: <strong><?= e($_SESSION['employee_name']) ?></strong> | Hora obligatoria: <strong><?= e(APP_TIMEZONE) ?></strong></div>
            <div class="actions">
                <a class="btn btn-secondary" href="?logout=1">Cerrar Sesión</a>
                <a class="btn btn-primary" href="admin/login.php">🔐 Panel admin</a>
            </div>
        </div>

        <div class="clock-display">
            <div class="clock-time" id="clock">--:--:--</div>
            <div class="clock-date" id="date">Cargando fecha de Utah...</div>
        </div>

        <div class="status-grid">
            <div class="status-card <?= e($statusClass) ?>">
                <div class="status-label">Tu Estado Actual</div>
                <div class="status-value"><?= e($currentStatus) ?></div>
            </div>
            <div class="status-card">
                <div class="status-label">Tu Último Registro</div>
                <div class="status-value"><?= e($lastRecord['record_time'] ?? '--:--') ?></div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">📝 Registrar asistencia</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="photo">Foto evidencia *</label>
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment" required>
                    <div class="hint" style="margin-top:.5rem;">Puede tomar la foto desde el celular o subirla desde el equipo.</div>
                </div>

                <div class="card" style="background:#f8fafc; margin-top:1rem;">
                    <strong>Ubicación del usuario</strong>
                    <p class="hint" id="location_status" style="margin-top:.35rem;">Intentando obtener ubicación...</p>
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    <input type="hidden" name="accuracy" id="accuracy">
                </div>

                <div class="grid-2" style="margin-top:1rem;">
                    <button class="btn btn-success" type="submit" name="record_type" value="entrada">🌅 Registrar entrada</button>
                    <button class="btn btn-danger" type="submit" name="record_type" value="salida">🌙 Registrar salida</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title">📋 Registros de hoy (General)</h2>
            <?php if (!$todayRecords): ?>
                <p class="hint text-center">No hay registros para hoy.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Tipo</th>
                            <th>Hora Utah</th>
                            <th>Ubicación</th>
                            <th>Foto</th>
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
                                        <a href="https://maps.google.com/?q=<?= e($record['latitude']) ?>,<?= e($record['longitude']) ?>" target="_blank">📍 Ver mapa</a>
                                    <?php else: ?>
                                        <span class="hint">Sin ubicación</span>
                                    <?php endif; ?>
                                </td>
                                <td><img src="<?= e($record['photo_path']) ?>" class="photo-thumb" alt="Foto"></td>
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
const utahFormatterTime = new Intl.DateTimeFormat('es-ES', {timeZone: 'America/Denver', hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false});
const utahFormatterDate = new Intl.DateTimeFormat('es-ES', {timeZone: 'America/Denver', weekday:'long', year:'numeric', month:'long', day:'numeric'});
function updateClock(){
    let clockEl = document.getElementById('clock');
    let dateEl = document.getElementById('date');
    if(!clockEl || !dateEl) return;
    
    const now = new Date();
    clockEl.textContent = utahFormatterTime.format(now);
    dateEl.textContent = utahFormatterDate.format(now) + ' · Hora de Utah';
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
        if(locStatus) locStatus.textContent = 'Ubicación capturada correctamente.';
    }, function(error){
        let locStatus = document.getElementById('location_status');
        if(locStatus) locStatus.textContent = 'No se pudo obtener la ubicación: ' + error.message;
    });
} else {
    let locStatus = document.getElementById('location_status');
    if(locStatus) locStatus.textContent = 'El navegador no soporta geolocalización.';
}
</script>
</body>
</html>