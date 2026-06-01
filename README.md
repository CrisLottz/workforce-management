<div align="center">
  
# 🏢 Workforce Management

### Attendance, Scheduling & Workforce Control Platform

**A self-hosted, zero-dependency workforce management system for SMBs that need reliable clock-in/out tracking, shift scheduling, and audit-ready CSV reporting — without the overhead of cloud subscriptions or complex infrastructure.**

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=for-the-badge&logo=sqlite&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

</div>

---

## 📑 Table of Contents

- [Overview](#-overview)
- [Architecture & Design Decisions](#-architecture--design-decisions)
- [Security Standards & Compliance](#-security-standards--compliance)
- [Prerequisites & Environment](#-prerequisites--environment)
- [Installation & Local Deployment](#-installation--local-deployment)
- [Project Structure](#-project-structure)
- [Default Credentials](#-default-credentials)
- [API / Data Export Reference](#-api--data-export-reference)

---

## 📋 Overview

**Workforce Management** is a server-rendered PHP application purpose-built for small-to-medium businesses that require:

| Capability | Description |
|---|---|
| **Clock-In / Clock-Out** | Employees authenticate and punch attendance with mandatory photo evidence, GPS geolocation capture, and configurable timezone enforcement. |
| **Shift Scheduling** | A dedicated Scheduler role assigns weekly shift start/end times per employee with day-off management across a 7-day grid (Sunday–Saturday). |
| **Admin Dashboard** | Full attendance audit trail with multi-axis filters (date, employee, record type), per-employee daily summaries with calculated worked hours, and one-click CSV export. |
| **Employee Management** | CRUD operations for employee access credentials — create accounts, revoke access, list active personnel. |
| **Bilingual i18n** | Complete English / Spanish internationalization with automatic browser `Accept-Language` detection, session persistence, and URL-based manual switching. |

The system is designed to be operationally simple: a single SQLite file serves as the database, the application auto-initializes its schema on first request, and zero external services or API keys are required.

---

## 🏗 Architecture & Design Decisions

### Why Server-Rendered PHP (No SPA / No Framework)?

The architecture deliberately avoids JavaScript frameworks (React, Vue) and PHP frameworks (Laravel, Symfony). The rationale:

- **Deployment target**: Shared hosting environments with limited control (cPanel, Plesk, basic LAMP stacks). A vanilla PHP app runs on any `php >= 8.1` host without Composer, Node.js, or build pipelines.
- **Operational simplicity**: Zero build step. Clone → configure timezone → serve. No `npm install`, no `composer install`, no asset compilation.
- **Total codebase visibility**: ~18 files, ~1,200 LOC. Any PHP developer can audit the entire system in under an hour.

### Why SQLite over MySQL / PostgreSQL?

| Trade-off | Justification |
|---|---|
| **Zero-configuration persistence** | No database server to install, configure, or maintain. The `data/attendance.sqlite` file is the entire database. |
| **Portability** | Backup = copy one file. Migrate = move one file. |
| **Concurrency model** | SQLite's WAL mode handles low-to-moderate concurrent writes typical of attendance systems (< 100 simultaneous punches). This is an appropriate fit for SMB workforce sizes. |
| **Trade-off acknowledged** | Not suitable for high-concurrency (> 500 employees punching simultaneously) or multi-server deployments. Scale-up path: swap `db.php` to a PDO MySQL/PostgreSQL driver. |

### Role-Based Access Control (RBAC) — Three-Tier Model

The system implements three isolated access domains, each with its own authentication flow and session namespace:

```
┌─────────────────────────────────────────────────────────┐
│                    index.php (Portal)                    │
│              Role Selection Landing Page                 │
├───────────────┬──────────────────┬────────────────────────┤
│  🧑‍💻 Employee   │  📅 Scheduler     │  ⚙️ Administrator      │
│  empleado.php │  scheduler/*     │  admin/*               │
│               │                  │                        │
│  Session key: │  Session key:    │  Session key:          │
│  employee_id  │  scheduler_email │  admin_user_id         │
│               │                  │                        │
│  DB table:    │  DB table:       │  DB table:             │
│  employees    │  scheduler_users │  admin_users           │
│               │                  │                        │
│  Can:         │  Can:            │  Can:                  │
│  • Clock in   │  • Assign weekly │  • CRUD employees      │
│  • Clock out  │    schedules     │  • Audit records       │
│  • View own   │  • Mark days off │  • Delete records      │
│    schedule   │  • View employee │  • Export CSV           │
│  • View today │    list          │  • View stats          │
│    records    │                  │                        │
└───────────────┴──────────────────┴────────────────────────┘
```

> **Design choice**: Session namespaces are intentionally isolated (`employee_id` vs `admin_user_id` vs `scheduler_email`) so that an administrator can simultaneously be logged in as an employee in a different tab without session collision.

### Auto-Initializing Database Schema

The schema is created lazily on first connection via `initializeDatabase()` in `includes/db.php`. This eliminates migration scripts and ensures the application is immediately functional after deployment.

**Tables created automatically:**

| Table | Purpose |
|---|---|
| `admin_users` | Administrator credentials (username + bcrypt hash) |
| `employees` | Employee credentials (name + email + bcrypt hash) |
| `attendance_records` | Punch log (name, type, date, time, GPS coords, photo path, timezone) |
| `schedules` | Weekly shift assignments (employee, date, start/end times, day-off flag) |
| `scheduler_users` | Scheduler role credentials |

### Internationalization (i18n) Strategy

The `includes/lang.php` module implements a dictionary-based i18n system with a four-tier language detection priority:

```
URL ?lang=xx  →  Session  →  Browser Accept-Language  →  Default (en)
```

All UI strings (150+ keys per language) are centralized in a single `$_translations` array. This avoids the overhead of `.po` / `.mo` files or third-party i18n libraries while maintaining full bilingual coverage.

---

## 🔒 Security Standards & Compliance

The following security measures are implemented in the codebase and mapped to industry standards:

| Measure | Implementation | OWASP Top 10 Alignment |
|---|---|---|
| **Password Hashing** | `password_hash()` with `PASSWORD_DEFAULT` (bcrypt, cost 10+). Passwords are never stored in plaintext. | A02:2021 – Cryptographic Failures |
| **XSS Mitigation** | All user-facing output is escaped via `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` through the `e()` helper function. Applied consistently across all `.php` templates. | A03:2021 – Injection |
| **SQL Injection Prevention** | All database queries use PDO prepared statements with parameterized binding. No string concatenation in SQL. | A03:2021 – Injection |
| **Session-Based Auth** | PHP native sessions with `session_start()` and server-side session ID management. Session state checked via guard functions (`requireAdmin()`, session key verification). | A07:2021 – Identification & Auth Failures |
| **File Upload Validation** | Multi-layer validation: MIME type verification via `finfo` (not relying on `$_FILES['type']`), allowlist of `image/jpeg`, `image/png`, `image/webp`, 5 MB size limit, randomized filenames with `random_bytes(6)`. | A04:2021 – Insecure Design |
| **Access Control Guards** | Each protected module validates its corresponding session key before executing. `requireAdmin()` redirects unauthenticated requests to the login page. | A01:2021 – Broken Access Control |
| **Output Encoding** | HTML entity encoding on all dynamic content prevents stored XSS via attendance records, employee names, and filter parameters. | A03:2021 – Injection |

> [!WARNING]
> **Not implemented (known limitations for production hardening):**
> - CSRF token validation on forms
> - Rate limiting on login attempts
> - Password complexity enforcement
> - HTTPS enforcement at the application layer (should be handled by the web server / reverse proxy)
> - Content Security Policy (CSP) headers

---

## ⚙ Prerequisites & Environment

### Required Software

| Tool | Minimum Version | Purpose |
|---|---|---|
| **PHP** | 8.1+ | Runtime. Must have `pdo_sqlite`, `fileinfo`, `session`, and `mbstring` extensions enabled. |
| **Web Server** | Apache 2.4+ / Nginx 1.18+ | Serve PHP files. Apache with `mod_rewrite` recommended for clean URL support. |
| **SQLite** | 3.x | Bundled with PHP's `pdo_sqlite` — no separate installation needed. |
| **Git** | 2.x | Clone the repository. |

> [!TIP]
> On **Windows**, [XAMPP](https://www.apachefriends.org/) or [Laragon](https://laragon.org/) provide a one-click PHP + Apache + SQLite environment.
> On **macOS**, PHP is available via `brew install php`.
> On **Linux**, `sudo apt install php php-sqlite3 php-mbstring` covers all dependencies.

### Environment Configuration

The application uses a single configuration file (`includes/config.php`) instead of a `.env` file:

```php
define('APP_NAME', 'TimeTrack Pro PHP');
define('APP_TIMEZONE', 'America/Denver');  // ← Change to your local timezone
```

| Constant | Description | Example |
|---|---|---|
| `APP_NAME` | Application display name | `'My Company Attendance'` |
| `APP_TIMEZONE` | IANA timezone for all recorded timestamps | `'America/Mexico_City'`, `'US/Eastern'`, `'Europe/London'` |
| `DB_PATH` | Auto-configured. Points to `data/attendance.sqlite` | — |
| `UPLOAD_PATH` | Auto-configured. Points to `uploads/` | — |

> [!IMPORTANT]
> Set `APP_TIMEZONE` **before** the first clock-in. Changing it later will not retroactively update existing records, causing mixed-timezone data.

---

## 🚀 Installation & Local Deployment

### 1. Clone the repository

```bash
git clone https://github.com/CrisLottz/workforce-management.git
cd workforce-management
```

### 2. Configure timezone

Edit `includes/config.php` and set your timezone:

```php
define('APP_TIMEZONE', 'America/Mexico_City'); // Your IANA timezone
```

### 3. Ensure directory permissions

The application auto-creates `data/` and `uploads/` directories. Ensure the web server process has write access:

```bash
# Linux / macOS
chmod -R 775 data/ uploads/
chown -R www-data:www-data data/ uploads/

# Windows (XAMPP): No action needed — Apache runs as the current user.
```

### 4. Start the development server

**Option A — PHP built-in server (fastest for local development):**

```bash
php -S localhost:8000
```

**Option B — Apache / Nginx:**

Point your virtual host's `DocumentRoot` to the project root directory.

### 5. Open in browser

```
http://localhost:8000
```

The database schema and default seed data (admin + test employees) are created automatically on first access.

---

## 📂 Project Structure

```
workforce-management/
│
├── index.php                  # 🏠 Landing portal — role selection (Employee / Scheduler / Admin)
├── empleado.php               # 🧑‍💻 Employee panel — login, clock-in/out, schedule view, today's records
│
├── admin/
│   ├── login.php              # 🔐 Admin authentication gate
│   ├── dashboard.php          # 📊 Admin dashboard — filters, stats, employee CRUD, record management
│   ├── export.php             # 📥 CSV export endpoint (admin-only, filtered by date/employee/type)
│   └── logout.php             # 🚪 Session teardown
│
├── scheduler/
│   ├── login.php              # 📅 Scheduler authentication gate
│   ├── index.php              # 📅 Schedule management — weekly grid, shift assignment, day-off toggling
│   └── logout.php             # 🚪 Session teardown
│
├── includes/
│   ├── config.php             # ⚙️ App constants (name, timezone, paths) + auto-directory creation
│   ├── db.php                 # 🗄️ PDO singleton + auto-schema initialization + seed data
│   ├── functions.php          # 🔧 Shared helpers (auth guards, date utils, photo upload, query builders)
│   └── lang.php               # 🌐 i18n engine — language detection, translation dictionary (EN/ES)
│
├── assets/
│   └── style.css              # 🎨 Complete design system — CSS custom properties, responsive grid, components
│
├── data/
│   └── attendance.sqlite      # 💾 SQLite database (auto-created, .gitignored)
│
├── uploads/                   # 📸 Photo evidence storage (auto-created, .gitignored)
│
├── .gitignore                 # Git exclusions (data/, uploads/, OS files)
└── README.md                  # 📖 This file
```

---

## 🔑 Default Credentials

The system seeds the following accounts on first initialization:

| Role | Username / Email | Password |
|---|---|---|
| **Administrator** | `admin` | `Admin123*` |
| **Employee (Test)** | `john@example.com` | `password123` |
| **Employee (Test)** | `jane@example.com` | `password123` |

> [!CAUTION]
> **Change these credentials immediately in production.** The admin password can be updated directly in the `admin_users` SQLite table. Employee passwords can be managed through the Admin Dashboard.

---

## 📡 API / Data Export Reference

### CSV Export Endpoint

```
GET /admin/export.php?date={YYYY-MM-DD}&employee={name}&type={entrada|salida}
```

**Authentication:** Requires active admin session (cookie-based).

**Query Parameters:**

| Parameter | Required | Description |
|---|---|---|
| `date` | No | Filter by date (`YYYY-MM-DD` format). Defaults to all dates. |
| `employee` | No | Filter by exact employee full name. Defaults to all employees. |
| `type` | No | Filter by record type: `entrada` (clock-in) or `salida` (clock-out). |

**Response:** `Content-Type: text/csv` file download with columns:

```
Name, Type, Date, Time, Latitude, Longitude, Accuracy, Timezone
```

### Language Switching

Append `?lang=en` or `?lang=es` to any URL to switch the interface language. The selection persists across the session.

---

---

---

<div align="center">

# 🏢 Workforce Management

### Plataforma de Control de Asistencia, Horarios y Gestión de Personal

**Un sistema de gestión de personal autoalojado y sin dependencias externas para PyMEs que necesitan control confiable de entradas/salidas, planificación de turnos y reportes CSV listos para auditoría — sin el costo de suscripciones en la nube ni infraestructura compleja.**

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=for-the-badge&logo=sqlite&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![License](https://img.shields.io/badge/Licencia-MIT-green?style=for-the-badge)

</div>

---

## 📑 Tabla de Contenidos

- [Resumen Ejecutivo](#-resumen-ejecutivo)
- [Arquitectura y Decisiones de Diseño](#-arquitectura-y-decisiones-de-diseño)
- [Estándares de Seguridad y Cumplimiento](#-estándares-de-seguridad-y-cumplimiento)
- [Prerrequisitos y Entorno](#-prerrequisitos-y-entorno)
- [Instalación y Despliegue Local](#-instalación-y-despliegue-local)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Credenciales por Defecto](#-credenciales-por-defecto)
- [Referencia de API / Exportación de Datos](#-referencia-de-api--exportación-de-datos)

---

## 📋 Resumen Ejecutivo

**Workforce Management** es una aplicación PHP renderizada en servidor, diseñada específicamente para pequeñas y medianas empresas que requieren:

| Capacidad | Descripción |
|---|---|
| **Registro de Entrada / Salida** | Los empleados se autentican y registran su asistencia con foto obligatoria como evidencia, captura de geolocalización GPS y zona horaria configurable. |
| **Planificación de Turnos** | Un rol dedicado de Planificador asigna horarios semanales de inicio/fin por empleado con gestión de días libres en una cuadrícula de 7 días (Domingo–Sábado). |
| **Panel de Administración** | Trazabilidad completa de asistencia con filtros multi-eje (fecha, empleado, tipo de registro), resúmenes diarios por empleado con cálculo de horas trabajadas y exportación CSV con un clic. |
| **Gestión de Empleados** | Operaciones CRUD para credenciales de acceso de empleados — crear cuentas, revocar accesos, listar personal activo. |
| **i18n Bilingüe** | Internacionalización completa Inglés / Español con detección automática del header `Accept-Language` del navegador, persistencia en sesión y cambio manual por URL. |

El sistema está diseñado para ser operativamente simple: un único archivo SQLite sirve como base de datos, la aplicación auto-inicializa su esquema en la primera petición y no se requieren servicios externos ni claves de API.

---

## 🏗 Arquitectura y Decisiones de Diseño

### ¿Por qué PHP renderizado en servidor (sin SPA / sin Framework)?

La arquitectura evita deliberadamente frameworks de JavaScript (React, Vue) y frameworks de PHP (Laravel, Symfony). La justificación:

- **Entorno de despliegue objetivo**: Hostings compartidos con control limitado (cPanel, Plesk, stacks LAMP básicos). Una aplicación PHP vanilla se ejecuta en cualquier host con `php >= 8.1` sin necesidad de Composer, Node.js ni pipelines de compilación.
- **Simplicidad operativa**: Cero pasos de build. Clonar → configurar zona horaria → servir. Sin `npm install`, sin `composer install`, sin compilación de assets.
- **Visibilidad total del código**: ~18 archivos, ~1,200 líneas de código. Cualquier desarrollador PHP puede auditar el sistema completo en menos de una hora.

### ¿Por qué SQLite en lugar de MySQL / PostgreSQL?

| Trade-off | Justificación |
|---|---|
| **Persistencia sin configuración** | No hay que instalar, configurar ni mantener un servidor de base de datos. El archivo `data/attendance.sqlite` es toda la base de datos. |
| **Portabilidad** | Respaldo = copiar un archivo. Migrar = mover un archivo. |
| **Modelo de concurrencia** | El modo WAL de SQLite maneja escrituras concurrentes bajas-a-moderadas típicas de sistemas de asistencia (< 100 registros simultáneos). Es un ajuste apropiado para tamaños de PyMEs. |
| **Trade-off reconocido** | No es adecuado para alta concurrencia (> 500 empleados registrando simultáneamente) o despliegues multi-servidor. Ruta de escalamiento: cambiar `db.php` a un driver PDO MySQL/PostgreSQL. |

### Control de Acceso Basado en Roles (RBAC) — Modelo de Tres Niveles

El sistema implementa tres dominios de acceso aislados, cada uno con su propio flujo de autenticación y espacio de nombres de sesión:

```
┌─────────────────────────────────────────────────────────┐
│                    index.php (Portal)                    │
│            Página de Selección de Rol                    │
├───────────────┬──────────────────┬────────────────────────┤
│  🧑‍💻 Empleado   │  📅 Planificador  │  ⚙️ Administrador      │
│  empleado.php │  scheduler/*     │  admin/*               │
│               │                  │                        │
│  Clave sesión:│  Clave sesión:   │  Clave sesión:         │
│  employee_id  │  scheduler_email │  admin_user_id         │
│               │                  │                        │
│  Tabla BD:    │  Tabla BD:       │  Tabla BD:             │
│  employees    │  scheduler_users │  admin_users           │
│               │                  │                        │
│  Puede:       │  Puede:          │  Puede:                │
│  • Entrada    │  • Asignar       │  • CRUD empleados      │
│  • Salida     │    horarios      │  • Auditar registros   │
│  • Ver su     │    semanales     │  • Eliminar registros  │
│    horario    │  • Marcar días   │  • Exportar CSV        │
│  • Ver regis- │    libres        │  • Ver estadísticas    │
│    tros hoy   │  • Ver lista de  │                        │
│               │    empleados     │                        │
└───────────────┴──────────────────┴────────────────────────┘
```

> **Decisión de diseño**: Los espacios de nombres de sesión están intencionalmente aislados (`employee_id` vs `admin_user_id` vs `scheduler_email`) para que un administrador pueda estar conectado simultáneamente como empleado en otra pestaña sin colisión de sesiones.

### Esquema de Base de Datos con Auto-Inicialización

El esquema se crea de forma perezosa (lazy) en la primera conexión mediante `initializeDatabase()` en `includes/db.php`. Esto elimina scripts de migración y garantiza que la aplicación sea funcional inmediatamente después del despliegue.

**Tablas creadas automáticamente:**

| Tabla | Propósito |
|---|---|
| `admin_users` | Credenciales de administradores (usuario + hash bcrypt) |
| `employees` | Credenciales de empleados (nombre + email + hash bcrypt) |
| `attendance_records` | Registro de asistencia (nombre, tipo, fecha, hora, coordenadas GPS, ruta de foto, zona horaria) |
| `schedules` | Asignaciones de turnos semanales (empleado, fecha, hora inicio/fin, bandera de día libre) |
| `scheduler_users` | Credenciales del rol Planificador |

### Estrategia de Internacionalización (i18n)

El módulo `includes/lang.php` implementa un sistema de i18n basado en diccionario con una prioridad de detección de idioma de cuatro niveles:

```
URL ?lang=xx  →  Sesión  →  Accept-Language del navegador  →  Por defecto (en)
```

Todas las cadenas de UI (150+ claves por idioma) están centralizadas en un único array `$_translations`. Esto evita la sobrecarga de archivos `.po` / `.mo` o bibliotecas de i18n de terceros, manteniendo cobertura bilingüe completa.

---

## 🔒 Estándares de Seguridad y Cumplimiento

Las siguientes medidas de seguridad están implementadas en el código fuente y mapeadas a estándares de la industria:

| Medida | Implementación | Alineación OWASP Top 10 |
|---|---|---|
| **Hashing de Contraseñas** | `password_hash()` con `PASSWORD_DEFAULT` (bcrypt, costo 10+). Las contraseñas jamás se almacenan en texto plano. | A02:2021 – Fallos Criptográficos |
| **Mitigación XSS** | Toda salida hacia el usuario se escapa mediante `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` a través de la función helper `e()`. Aplicada consistentemente en todas las plantillas `.php`. | A03:2021 – Inyección |
| **Prevención de Inyección SQL** | Todas las consultas usan sentencias preparadas PDO con binding parametrizado. No hay concatenación de strings en SQL. | A03:2021 – Inyección |
| **Autenticación Basada en Sesión** | Sesiones nativas de PHP con `session_start()` y gestión de ID de sesión del lado del servidor. El estado de sesión se verifica mediante funciones guardia (`requireAdmin()`, verificación de claves de sesión). | A07:2021 – Fallos de Identificación y Autenticación |
| **Validación de Carga de Archivos** | Validación multicapa: verificación de tipo MIME vía `finfo` (sin depender de `$_FILES['type']`), lista blanca de `image/jpeg`, `image/png`, `image/webp`, límite de 5 MB, nombres de archivo aleatorios con `random_bytes(6)`. | A04:2021 – Diseño Inseguro |
| **Guardias de Control de Acceso** | Cada módulo protegido valida su clave de sesión correspondiente antes de ejecutar. `requireAdmin()` redirige peticiones no autenticadas a la página de login. | A01:2021 – Control de Acceso Roto |
| **Codificación de Salida** | La codificación de entidades HTML en todo contenido dinámico previene XSS almacenado vía registros de asistencia, nombres de empleados y parámetros de filtros. | A03:2021 – Inyección |

> [!WARNING]
> **No implementado (limitaciones conocidas para endurecimiento en producción):**
> - Validación de tokens CSRF en formularios
> - Limitación de tasa en intentos de login
> - Imposición de complejidad de contraseñas
> - Forzado de HTTPS a nivel de aplicación (debe manejarse en el servidor web / proxy reverso)
> - Headers de Content Security Policy (CSP)

---

## ⚙ Prerrequisitos y Entorno

### Software Requerido

| Herramienta | Versión Mínima | Propósito |
|---|---|---|
| **PHP** | 8.1+ | Entorno de ejecución. Debe tener habilitadas las extensiones `pdo_sqlite`, `fileinfo`, `session` y `mbstring`. |
| **Servidor Web** | Apache 2.4+ / Nginx 1.18+ | Servir archivos PHP. Se recomienda Apache con `mod_rewrite` para soporte de URLs limpias. |
| **SQLite** | 3.x | Incluido con `pdo_sqlite` de PHP — no requiere instalación separada. |
| **Git** | 2.x | Clonar el repositorio. |

> [!TIP]
> En **Windows**, [XAMPP](https://www.apachefriends.org/) o [Laragon](https://laragon.org/) proporcionan un entorno PHP + Apache + SQLite con un solo clic.
> En **macOS**, PHP está disponible vía `brew install php`.
> En **Linux**, `sudo apt install php php-sqlite3 php-mbstring` cubre todas las dependencias.

### Configuración del Entorno

La aplicación utiliza un único archivo de configuración (`includes/config.php`) en lugar de un archivo `.env`:

```php
define('APP_NAME', 'TimeTrack Pro PHP');
define('APP_TIMEZONE', 'America/Denver');  // ← Cambie a su zona horaria local
```

| Constante | Descripción | Ejemplo |
|---|---|---|
| `APP_NAME` | Nombre visible de la aplicación | `'Mi Empresa Asistencia'` |
| `APP_TIMEZONE` | Zona horaria IANA para todos los timestamps registrados | `'America/Mexico_City'`, `'US/Eastern'`, `'Europe/London'` |
| `DB_PATH` | Auto-configurado. Apunta a `data/attendance.sqlite` | — |
| `UPLOAD_PATH` | Auto-configurado. Apunta a `uploads/` | — |

> [!IMPORTANT]
> Configure `APP_TIMEZONE` **antes** del primer registro de asistencia. Cambiarlo después no actualizará retroactivamente los registros existentes, causando datos con zonas horarias mixtas.

---

## 🚀 Instalación y Despliegue Local

### 1. Clonar el repositorio

```bash
git clone https://github.com/CrisLottz/workforce-management.git
cd workforce-management
```

### 2. Configurar zona horaria

Edite `includes/config.php` y establezca su zona horaria:

```php
define('APP_TIMEZONE', 'America/Mexico_City'); // Su zona horaria IANA
```

### 3. Asegurar permisos de directorios

La aplicación auto-crea los directorios `data/` y `uploads/`. Asegúrese de que el proceso del servidor web tenga permisos de escritura:

```bash
# Linux / macOS
chmod -R 775 data/ uploads/
chown -R www-data:www-data data/ uploads/

# Windows (XAMPP): No se requiere acción — Apache se ejecuta como el usuario actual.
```

### 4. Iniciar el servidor de desarrollo

**Opción A — Servidor integrado de PHP (lo más rápido para desarrollo local):**

```bash
php -S localhost:8000
```

**Opción B — Apache / Nginx:**

Apunte el `DocumentRoot` de su virtual host al directorio raíz del proyecto.

### 5. Abrir en el navegador

```
http://localhost:8000
```

El esquema de la base de datos y los datos semilla por defecto (admin + empleados de prueba) se crean automáticamente en el primer acceso.

---

## 📂 Estructura del Proyecto

```
workforce-management/
│
├── index.php                  # 🏠 Portal de inicio — selección de rol (Empleado / Planificador / Admin)
├── empleado.php               # 🧑‍💻 Panel del empleado — login, entrada/salida, vista de horario, registros del día
│
├── admin/
│   ├── login.php              # 🔐 Puerta de autenticación del administrador
│   ├── dashboard.php          # 📊 Panel admin — filtros, estadísticas, CRUD de empleados, gestión de registros
│   ├── export.php             # 📥 Endpoint de exportación CSV (solo admin, filtrado por fecha/empleado/tipo)
│   └── logout.php             # 🚪 Cierre de sesión
│
├── scheduler/
│   ├── login.php              # 📅 Puerta de autenticación del planificador
│   ├── index.php              # 📅 Gestión de horarios — cuadrícula semanal, asignación de turnos, días libres
│   └── logout.php             # 🚪 Cierre de sesión
│
├── includes/
│   ├── config.php             # ⚙️ Constantes de la app (nombre, zona horaria, rutas) + creación auto de directorios
│   ├── db.php                 # 🗄️ Singleton PDO + auto-inicialización del esquema + datos semilla
│   ├── functions.php          # 🔧 Helpers compartidos (guardias de auth, utilidades de fecha, carga de fotos, constructores de consultas)
│   └── lang.php               # 🌐 Motor i18n — detección de idioma, diccionario de traducciones (EN/ES)
│
├── assets/
│   └── style.css              # 🎨 Sistema de diseño completo — CSS custom properties, grid responsive, componentes
│
├── data/
│   └── attendance.sqlite      # 💾 Base de datos SQLite (auto-creada, en .gitignore)
│
├── uploads/                   # 📸 Almacenamiento de fotos de evidencia (auto-creado, en .gitignore)
│
├── .gitignore                 # Exclusiones de Git (data/, uploads/, archivos del SO)
└── README.md                  # 📖 Este archivo
```

---

## 🔑 Credenciales por Defecto

El sistema genera las siguientes cuentas en la primera inicialización:

| Rol | Usuario / Email | Contraseña |
|---|---|---|
| **Administrador** | `admin` | `Admin123*` |
| **Empleado (Prueba)** | `john@example.com` | `password123` |
| **Empleado (Prueba)** | `jane@example.com` | `password123` |

> [!CAUTION]
> **Cambie estas credenciales inmediatamente en producción.** La contraseña del administrador puede actualizarse directamente en la tabla `admin_users` de SQLite. Las contraseñas de empleados pueden gestionarse a través del Panel de Administración.

---

## 📡 Referencia de API / Exportación de Datos

### Endpoint de Exportación CSV

```
GET /admin/export.php?date={YYYY-MM-DD}&employee={nombre}&type={entrada|salida}
```

**Autenticación:** Requiere sesión de administrador activa (basada en cookies).

**Parámetros de consulta:**

| Parámetro | Requerido | Descripción |
|---|---|---|
| `date` | No | Filtrar por fecha (formato `YYYY-MM-DD`). Por defecto muestra todas las fechas. |
| `employee` | No | Filtrar por nombre completo exacto del empleado. Por defecto muestra todos. |
| `type` | No | Filtrar por tipo de registro: `entrada` o `salida`. |

**Respuesta:** Descarga de archivo `Content-Type: text/csv` con columnas:

```
Nombre, Tipo, Fecha, Hora, Latitud, Longitud, Exactitud, Zona Horaria
```

### Cambio de Idioma

Agregue `?lang=en` o `?lang=es` a cualquier URL para cambiar el idioma de la interfaz. La selección persiste durante toda la sesión.
]]>
