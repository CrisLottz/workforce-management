<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();

if (!isset($_SESSION['scheduler_email'])) {
    header('Location: login.php');
    exit;
}

$pdo = db();
$message = '';

// Procesar el guardado del horario semanal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_schedule') {
    $empName = $_POST['employee_name'] ?? '';
    $dates = $_POST['dates'] ?? [];
    $starts = $_POST['starts'] ?? [];
    $ends = $_POST['ends'] ?? [];
    $offs = $_POST['offs'] ?? [];
    $now = date('Y-m-d H:i:s');

    // Usamos REPLACE INTO para insertar o actualizar si ya existe la llave única (nombre + fecha)
    $stmt = $pdo->prepare("REPLACE INTO schedules (employee_name, schedule_date, start_time, end_time, is_off, created_at) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($dates as $i => $date) {
        $isOff = isset($offs[$i]) ? 1 : 0;
        $start = !empty($starts[$i]) ? $starts[$i] : null;
        $end = !empty($ends[$i]) ? $ends[$i] : null;
        
        // Si está marcado como OFF, forzamos null en las horas
        if ($isOff) {
            $start = null;
            $end = null;
        }

        $stmt->execute([$empName, $date, $start, $end, $isOff, $now]);
    }
    $message = "Horarios semanales de $empName actualizados correctamente.";
}

// Filtros de navegación
$selectedDate = $_GET['week_date'] ?? appDate();
$selectedEmp = $_GET['employee_name'] ?? '';

// Calcular el Domingo de la semana seleccionada
$timestamp = strtotime($selectedDate);
$dayOfWeek = date('w', $timestamp);
$sunday = date('Y-m-d', strtotime("-$dayOfWeek days", $timestamp));

// Obtener lista de empleados
$employees = $pdo->query("SELECT full_name FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_COLUMN);
if (!$selectedEmp && count($employees) > 0) {
    $selectedEmp = $employees[0];
}

// Cargar los horarios si ya existen en la base de datos para esa semana
$existingSchedules = [];
if ($selectedEmp) {
    $saturday = date('Y-m-d', strtotime("$sunday +6 days"));
    $stmtEx = $pdo->prepare("SELECT * FROM schedules WHERE employee_name = ? AND schedule_date BETWEEN ? AND ?");
    $stmtEx->execute([$selectedEmp, $sunday, $saturday]);
    foreach ($stmtEx->fetchAll() as $row) {
        $existingSchedules[$row['schedule_date']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Scheduler</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .schedule-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-top: 20px; }
        .day-card { border: 1px solid #ddd; padding: 10px; border-radius: 8px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05);}
        .day-card h4 { margin-top: 0; text-align: center; border-bottom: 2px solid var(--primary); padding-bottom: 5px; font-size: 0.9rem;}
        .off-check { margin-top: 10px; text-align: center; font-weight: bold; color: var(--danger); font-size: 0.9rem;}
    </style>
</head>
<body>
<header>
    <div class="header-content">
        <div class="logo"><span>📅</span><span>Panel Scheduler</span></div>
        <div class="actions">
            <span class="role-badge">Scheduler: <?= e($_SESSION['scheduler_email']) ?></span>
            <a class="logout-link" href="logout.php">Cerrar sesión</a>
        </div>
    </div>
</header>
<div class="container">
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>

    <div class="card" style="border-left: 4px solid var(--primary);">
        <h2>1. Seleccionar Semana y Empleado</h2>
        <form method="get" class="filters" style="display: flex; gap: 15px; align-items: flex-end;">
            <div style="flex: 1;">
                <label>Empleado</label>
                <select name="employee_name" onchange="this.form.submit()">
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= e($emp) ?>" <?= $selectedEmp === $emp ? 'selected' : '' ?>><?= e($emp) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex: 1;">
                <label>Cualquier fecha de la semana a gestionar</label>
                <input type="date" name="week_date" value="<?= e($selectedDate) ?>" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <div class="card">
        <h2>2. Asignar Horarios (Semana del <?= e($sunday) ?>)</h2>
        <form method="post">
            <input type="hidden" name="action" value="save_schedule">
            <input type="hidden" name="employee_name" value="<?= e($selectedEmp) ?>">
            
            <div class="schedule-grid">
                <?php 
                $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                for ($i = 0; $i < 7; $i++): 
                    $currentDate = date('Y-m-d', strtotime("$sunday +$i days"));
                    $data = $existingSchedules[$currentDate] ?? null;
                    $startVal = $data ? $data['start_time'] : '';
                    $endVal = $data ? $data['end_time'] : '';
                    $isOff = $data ? $data['is_off'] : 0;
                ?>
                <div class="day-card <?= $isOff ? 'disabled-card' : '' ?>">
                    <h4><?= $days[$i] ?><br><small style="color:gray;"><?= $currentDate ?></small></h4>
                    <input type="hidden" name="dates[<?= $i ?>]" value="<?= $currentDate ?>">
                    <div class="form-group" style="margin-bottom: 5px;">
                        <label style="font-size: 0.8rem;">Entrada</label>
                        <input type="time" name="starts[<?= $i ?>]" value="<?= e($startVal) ?>" <?= $isOff ? 'disabled' : '' ?>>
                    </div>
                    <div class="form-group" style="margin-bottom: 5px;">
                        <label style="font-size: 0.8rem;">Salida</label>
                        <input type="time" name="ends[<?= $i ?>]" value="<?= e($endVal) ?>" <?= $isOff ? 'disabled' : '' ?>>
                    </div>
                    <div class="off-check">
                        <label><input type="checkbox" class="off-toggle" name="offs[<?= $i ?>]" value="1" <?= $isOff ? 'checked' : '' ?>> Día OFF</label>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button type="submit" class="btn btn-success" style="padding: 10px 30px;">💾 Guardar Horario Semanal</button>
            </div>
        </form>
    </div>
</div>
<script>
    // UX: Si marcan "Día OFF", deshabilitar y limpiar las horas de esa tarjeta
    document.querySelectorAll('.off-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            let card = this.closest('.day-card');
            let inputs = card.querySelectorAll('input[type="time"]');
            inputs.forEach(input => {
                input.disabled = this.checked;
                if (this.checked) input.value = '';
            });
            card.style.opacity = this.checked ? '0.6' : '1';
        });
        
        // Estado inicial
        if(checkbox.checked) {
            checkbox.closest('.day-card').style.opacity = '0.6';
        }
    });
</script>
</body>
</html>