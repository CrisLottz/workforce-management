<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initializeDatabase($pdo);
    }

    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    // 1. Tabla de administradores
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at TEXT NOT NULL
    )");

    // 2. Tabla de registros de asistencia
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        record_type TEXT NOT NULL CHECK(record_type IN ('entrada','salida')),
        record_date TEXT NOT NULL,
        record_time TEXT NOT NULL,
        recorded_at TEXT NOT NULL,
        timezone TEXT NOT NULL,
        latitude TEXT,
        longitude TEXT,
        accuracy TEXT,
        photo_path TEXT NOT NULL,
        created_at TEXT NOT NULL
    )");

    // 3. NUEVA: Tabla de empleados para autenticación
    $pdo->exec("CREATE TABLE IF NOT EXISTS employees (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at TEXT NOT NULL
    )");

    // 4. Sembrar Administrador por defecto
    $countAdmin = (int) $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    if ($countAdmin === 0) {
        $stmtAdmin = $pdo->prepare("INSERT INTO admin_users (username, password_hash, created_at) VALUES (?, ?, ?)");
        $stmtAdmin->execute([
            'admin',
            password_hash('Admin123*', PASSWORD_DEFAULT),
            date('Y-m-d H:i:s')
        ]);
    }

    // 5. Seed default test employees
    $countEmp = (int) $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    if ($countEmp === 0) {
        $now = date('Y-m-d H:i:s');
        
        $employees = [
            ['John Doe', 'john@example.com', 'password123'],
            ['Jane Doe', 'jane@example.com', 'password123'],
        ];

        $stmtEmp = $pdo->prepare("INSERT INTO employees (full_name, email, password_hash, created_at) VALUES (?, ?, ?, ?)");
        
        foreach ($employees as $emp) {
            $hashedPassword = password_hash($emp[2], PASSWORD_DEFAULT);
            $stmtEmp->execute([$emp[0], $emp[1], $hashedPassword, $now]);
        }
    }
}