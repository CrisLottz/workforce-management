<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';
requireAdmin();

$date = $_GET['date'] ?? '';
$employee = $_GET['employee'] ?? '';
$type = $_GET['type'] ?? '';
$records = getFilteredRecords($date, $employee, $type);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance_export_' . date('Ymd_His') . '.csv');

$headerRow = [
    t('csv_name'),
    t('csv_type'),
    t('csv_date'),
    t('csv_time'),
    t('csv_latitude'),
    t('csv_longitude'),
    t('csv_accuracy'),
    t('csv_timezone'),
];
echo implode(',', array_map(function($val) { return '"' . str_replace('"', '""', $val) . '"'; }, $headerRow)) . "\n";
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
