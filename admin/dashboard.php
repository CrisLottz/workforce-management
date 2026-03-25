<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

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

$date = $_GET['date'] ?? utahDate();
$employee = $_GET['employee'] ?? '';
$type = $_GET['type'] ?? '';
$records = getFilteredRecords($date, $employee, $type);
$employees = getEmployees();
$summary = getSummaryByEmployee($records);

$totalEmployees = count(array_unique(array_column($records, 'full_name')));
$presentTodayStmt = db()->prepare("SELECT COUNT(DISTINCT full_name) FROM attendance_records WHERE record_date = ? AND record_type = 'entrada'");
$presentTodayStmt->execute([utahDate()]);
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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header>
    <div class="header-content">
        <div class="logo"><span>📊</span><span>Panel Administrador</span></div>
        <div class="actions">
            <span class="role-badge">Usuario: <?= e($_SESSION['admin_username'] ?? 'admin') ?></span>
            <a class="logout-link" href="logout.php">Cerrar sesión</a>
        </div>
    </div>
</header>
<div class="container">
    <?php if (!empty($_GET['deleted'])): ?>
        <div class="alert alert-success">Registro eliminado correctamente.</div>
    <?php endif; ?>

    <div class="top-actions" style="margin-bottom:1rem;">
        <div class="hint">Todos los registros están guardados con hora oficial de Utah (<strong><?= e(APP_TIMEZONE) ?></strong>).</div>
        <div class="actions">
            <a class="btn btn-secondary" href="export.php?date=<?= urlencode($date) ?>&employee=<?= urlencode($employee) ?>&type=<?= urlencode($type) ?>">📥 Exportar CSV</a>
            <a class="btn btn-primary" href="../index.php">👤 Ver formulario público</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?= $totalEmployees ?></div><div class="stat-label">Total empleados filtrados</div></div>
        <div class="stat-card"><div class="stat-number"><?= $presentToday ?></div><div class="stat-label">Presentes hoy</div></div>
        <div class="stat-card"><div class="stat-number"><?= e($avgHours) ?></div><div class="stat-label">Promedio de horas</div></div>
    </div>

    <div class="card">
        <h2 class="card-title">🔍 Filtros</h2>
        <form method="get" class="filters">
            <div>
                <label for="date">Fecha</label>
                <input type="date" name="date" id="date" value="<?= e($date) ?>">
            </div>
            <div>
                <label for="employee">Empleado</label>
                <select name="employee" id="employee">
                    <option value="">Todos</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= e($emp) ?>" <?= $employee === $emp ? 'selected' : '' ?>><?= e($emp) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="type">Tipo</label>
                <select name="type" id="type">
                    <option value="">Todos</option>
                    <option value="entrada" <?= $type === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                    <option value="salida" <?= $type === 'salida' ? 'selected' : '' ?>>Salida</option>
                </select>
            </div>
            <div>
                <button class="btn btn-primary btn-block" type="submit">Aplicar filtros</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title">📅 Resumen del día</h2>
        <?php if (!$summary): ?>
            <p class="hint text-center">No hay registros para esta fecha.</p>
        <?php else: ?>
            <?php foreach ($summary as $item): ?>
                <div class="employee-card">
                    <div class="employee-avatar"><?= e(mb_strtoupper(mb_substr($item['name'], 0, 1))) ?></div>
                    <div>
                        <strong><?= e($item['name']) ?></strong><br>
                        <span class="hint"><?= e((string)$item['count']) ?> registros</span>
                    </div>
                    <div class="actions">
                        <div><div class="time-main"><?= e($item['entrada'] ?? '--:--') ?></div><div class="time-label">Entrada</div></div>
                        <div><div class="time-main"><?= e($item['salida'] ?? '--:--') ?></div><div class="time-label">Salida</div></div>
                        <div><div class="time-main"><?= e($item['duration']) ?></div><div class="time-label">Duración</div></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 class="card-title">📊 Registros detallados</h2>
        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Hora Utah</th>
                    <th>Ubicación</th>
                    <th>Foto</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$records): ?>
                    <tr><td colspan="7" class="text-center">No hay registros.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= e($record['full_name']) ?></td>
                            <td><span class="badge badge-<?= e($record['record_type']) ?>"><?= strtoupper(e($record['record_type'])) ?></span></td>
                            <td><?= e($record['record_date']) ?></td>
                            <td><?= e($record['record_time']) ?></td>
                            <td>
                                <?php if ($record['latitude'] && $record['longitude']): ?>
                                    <a href="https://maps.google.com/?q=<?= e($record['latitude']) ?>,<?= e($record['longitude']) ?>" target="_blank">📍 Ver mapa</a>
                                <?php else: ?>
                                    <span class="hint">Sin ubicación</span>
                                <?php endif; ?>
                            </td>
                            <td><a href="../<?= e($record['photo_path']) ?>" target="_blank"><img src="../<?= e($record['photo_path']) ?>" class="photo-thumb" alt="Foto"></a></td>
                            <td><a class="btn btn-danger small" href="?delete=<?= (int)$record['id'] ?>" onclick="return confirm('¿Desea eliminar este registro?')">Eliminar</a></td>
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
