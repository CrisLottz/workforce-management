<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$date = $_GET['date'] ?? '';
$employee = $_GET['employee'] ?? '';
$type = $_GET['type'] ?? '';
$records = getFilteredRecords($date, $employee, $type);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance_export_' . date('Ymd_His') . '.csv');

echo "Nombre,Tipo,Fecha,Hora Utah,Latitud,Longitud,Exactitud,Zona Horaria\n";
foreach ($records as $record) {
    $row = [
        $record['full_name'],
        $record['record_type'],
        $record['record_date'],
        $record['record_time'],
        $record['latitude'],
        $record['longitude'],
        $record['accuracy'],
        $record['timezone'],
    ];

    $escaped = array_map(function ($value) {
        $value = (string) $value;
        return '"' . str_replace('"', '""', $value) . '"';
    }, $row);

    echo implode(',', $escaped) . "\n";
}
