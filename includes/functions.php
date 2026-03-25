<?php
require_once __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_user_id']);
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function utahNow(): DateTime
{
    return new DateTime('now', new DateTimeZone(APP_TIMEZONE));
}

function utahDate(): string
{
    return utahNow()->format('Y-m-d');
}

function utahTime(): string
{
    return utahNow()->format('H:i:s');
}

function saveUploadedPhoto(array $file): string
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Debe subir una fotografía válida.');
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Hubo un problema al subir la fotografía.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('La fotografía supera el límite de 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Solo se permiten imágenes JPG, PNG o WEBP.');
    }

    $filename = 'photo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    $destination = UPLOAD_PATH . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('No se pudo guardar la fotografía.');
    }

    return 'uploads/' . $filename;
}

function getEmployees(): array
{
    $stmt = db()->query("SELECT DISTINCT full_name FROM attendance_records ORDER BY full_name ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function getFilteredRecords(?string $date, ?string $employee, ?string $type): array
{
    $sql = "SELECT * FROM attendance_records WHERE 1=1";
    $params = [];

    if (!empty($date)) {
        $sql .= " AND record_date = ?";
        $params[] = $date;
    }
    if (!empty($employee)) {
        $sql .= " AND full_name = ?";
        $params[] = $employee;
    }
    if (!empty($type)) {
        $sql .= " AND record_type = ?";
        $params[] = $type;
    }

    $sql .= " ORDER BY recorded_at DESC";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getSummaryByEmployee(array $records): array
{
    $summary = [];

    foreach ($records as $record) {
        $name = $record['full_name'];
        if (!isset($summary[$name])) {
            $summary[$name] = [
                'name' => $name,
                'entrada' => null,
                'salida' => null,
                'duration' => '--',
                'count' => 0
            ];
        }

        $summary[$name]['count']++;
        if ($record['record_type'] === 'entrada' && $summary[$name]['entrada'] === null) {
            $summary[$name]['entrada'] = $record['record_time'];
            $summary[$name]['entrada_at'] = $record['recorded_at'];
        }
        if ($record['record_type'] === 'salida' && $summary[$name]['salida'] === null) {
            $summary[$name]['salida'] = $record['record_time'];
            $summary[$name]['salida_at'] = $record['recorded_at'];
        }
    }

    foreach ($summary as &$item) {
        if (!empty($item['entrada_at']) && !empty($item['salida_at'])) {
            $start = new DateTime($item['entrada_at']);
            $end = new DateTime($item['salida_at']);
            if ($end >= $start) {
                $diff = $start->diff($end);
                $item['duration'] = sprintf('%02dh %02dm', ($diff->days * 24) + $diff->h, $diff->i);
            }
        }
    }

    return array_values($summary);
}
