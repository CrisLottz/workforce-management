# 🏢 Workforce Management

An open-source, self-hosted employee attendance tracking system built with PHP and SQLite. Track clock-in/clock-out with photo evidence and GPS geolocation, manage weekly schedules, and export reports — all without external dependencies or API integrations.

Now with **multi-language support** (English and Spanish) via a lightweight i18n system!

*See below for the Spanish version. / Ver abajo para la versión en español.*

---

## ✨ Features

### 👤 Employee Panel
- **Clock In / Clock Out** with mandatory photo evidence (JPG, PNG, WEBP — max 5 MB).
- **Automatic GPS capture** (latitude, longitude, accuracy) via the browser.
- **Configurable timezone** — all timestamps are forced to a single timezone defined in `config.php`.
- **Today's schedule view** — employees see their assigned shift or "DAY OFF" upon login.
- **Late alert** — a red warning is shown if the employee hasn't clocked in past their scheduled start time.
- **Live clock** — a real-time digital clock displaying the configured timezone.
- **Today's records** — a table showing all attendance records for the day across the organization.

### 📅 Scheduler Panel
- **Weekly schedule assignment** — select an employee and a week, then define start/end times for each day (Sunday through Saturday).
- **Day OFF toggle** — mark any day as off; time fields are automatically disabled.
- **Week navigation** — jump to any week by selecting a date within it.
- **Upsert logic** — uses `REPLACE INTO` to create or update schedules without duplicates.

### ⚙️ Admin Panel
- **Dashboard stats** — total filtered employees, present today, average hours worked.
- **Employee management** — create new employee accounts (name, email, password) and delete existing ones.
- **Attendance filters** — filter records by date, employee name, and type (entry/exit).
- **Daily summary cards** — per-employee view with clock-in time, clock-out time, and total duration.
- **Detailed records table** — full log with name, type, date, time, GPS location (Google Maps link), and photo thumbnail.
- **Record deletion** — admins can delete individual records (associated photo is also removed from the server).
- **CSV export** — download filtered attendance records as a CSV file.

### 🌍 Internationalization (i18n)
- **Automatic Language Detection** — Detects user browser preference (`Accept-Language`).
- **Language Switcher** — Easily toggle between English and Spanish on any page.
- **State Persistence** — Selected language is remembered via PHP Sessions.
- **Extensible Dictionary** — Add new languages by editing `includes/lang.php`.

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 7.4+ |
| **Database** | SQLite 3 (auto-generated local file) |
| **Frontend** | HTML5, CSS3 (responsive), Vanilla JavaScript |
| **Auth** | PHP Sessions + `password_hash` / `password_verify` (bcrypt) |

> **Zero external dependencies.** No frameworks, no Composer packages, no third-party APIs. Just PHP + SQLite.

## 🗄️ Database Schema

The SQLite database is auto-generated at `data/attendance.sqlite` on first access. The schema is initialized from `includes/db.php`:

| Table | Purpose |
|-------|---------|
| `admin_users` | Admin credentials (username + bcrypt hash) |
| `employees` | Employee accounts (full name, email, bcrypt hash) |
| `attendance_records` | Clock-in/out log (name, type, date, time, GPS coords, photo path) |
| `schedules`* | Weekly schedules assigned by the scheduler |
| `scheduler_users`* | Scheduler role credentials (email + bcrypt hash) |

> \* The `schedules` and `scheduler_users` tables are used by the Scheduler module but **are not auto-created** by `db.php`. See the installation section below for the SQL to create them.

## ⚙️ Installation

### Prerequisites
- PHP 7.4+ with `pdo_sqlite` and `fileinfo` extensions enabled.
- A web server (Apache, Nginx) or local dev environment (XAMPP, Laragon, WAMP, etc.).

### Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-username/workforce-management.git
   ```

2. **Place in your web server's document root** (e.g., `htdocs/`, `public_html/`, or a subdirectory).

3. **Ensure write permissions** on the project root — the app auto-creates:
   - `data/` — SQLite database storage.
   - `uploads/` — Photo evidence storage.

   On Linux/Mac:
   ```bash
   chmod -R 775 data/ uploads/
   ```

4. **(Optional) Set your timezone** in `includes/config.php`:
   ```php
   define('APP_TIMEZONE', 'America/New_York'); // Change to your timezone
   ```

5. **(Optional) Set up the Scheduler module.** If you want to use the schedule planner, create the required tables by running this SQL against your SQLite database:
   ```sql
   CREATE TABLE IF NOT EXISTS scheduler_users (
       id INTEGER PRIMARY KEY AUTOINCREMENT,
       email TEXT NOT NULL UNIQUE,
       password_hash TEXT NOT NULL,
       created_at TEXT NOT NULL
   );

   CREATE TABLE IF NOT EXISTS schedules (
       id INTEGER PRIMARY KEY AUTOINCREMENT,
       employee_name TEXT NOT NULL,
       schedule_date TEXT NOT NULL,
       start_time TEXT,
       end_time TEXT,
       is_off INTEGER DEFAULT 0,
       created_at TEXT NOT NULL,
       UNIQUE(employee_name, schedule_date)
   );
   ```

6. **Open the app** in your browser. The database and seed users are created automatically on the first visit.

## 🔐 Default Credentials

On first launch, the system automatically seeds the following test accounts:

### Admin
| Username | Password |
|----------|----------|
| `admin` | `Admin123*` |

### Employees
| Name | Email | Password |
|------|-------|----------|
| John Doe | `john@example.com` | `password123` |
| Jane Doe | `jane@example.com` | `password123` |

> ⚠️ **Change all default passwords** before using in production. You can modify the seed data in `includes/db.php` → `initializeDatabase()`, or manage employees directly from the Admin panel.

---

# 🏢 Gestión de Personal (Español)

Un sistema de control de asistencia de empleados de código abierto, autoalojado, creado con PHP y SQLite. Registra las entradas/salidas con evidencia fotográfica y geolocalización GPS, gestiona los horarios semanales y exporta informes, todo sin dependencias externas ni integraciones de API.

¡Ahora con **soporte multilingüe** (Inglés y Español) mediante un sistema i18n ligero!

## ✨ Características

### 👤 Panel de Empleados
- **Entrada / Salida** con evidencia fotográfica obligatoria (JPG, PNG, WEBP — máx 5 MB).
- **Captura automática de GPS** (latitud, longitud, exactitud) a través del navegador.
- **Zona horaria configurable** — todos los registros forzados a una zona horaria definida en `config.php`.
- **Vista del horario de hoy** — el empleado ve su turno asignado o "DÍA OFF".
- **Alerta de retraso** — advertencia en rojo si la hora de entrada ya pasó.
- **Reloj en vivo** — un reloj digital en tiempo real con la zona horaria configurada.
- **Registros de hoy** — tabla con todos los registros de la organización en el día actual.

### 📅 Panel Planificador (Scheduler)
- **Asignación de horarios semanales** — selecciona empleado y semana, luego define hora de entrada/salida para cada día.
- **Día OFF** — marca cualquier día como libre (los campos de hora se desactivan automáticamente).
- **Navegación por semana** — salta a cualquier semana seleccionando una fecha.
- **Lógica Upsert** — usa `REPLACE INTO` para crear o actualizar horarios sin duplicados.

### ⚙️ Panel Administrador
- **Estadísticas del panel** — total de empleados, presentes hoy, promedio de horas.
- **Gestión de empleados** — crear accesos (nombre, email, contraseña) y borrar existentes.
- **Filtros de asistencia** — por fecha, nombre del empleado y tipo (entrada/salida).
- **Tarjetas de resumen diario** — por empleado: hora de entrada, salida y duración.
- **Registros detallados** — log completo con nombre, tipo, fecha, hora, GPS (link de Google Maps) y foto.
- **Eliminación de registros** — admins pueden borrar un registro (elimina también la foto del servidor).
- **Exportación CSV** — descarga los registros filtrados en formato CSV.

### 🌍 Internacionalización (i18n)
- **Detección automática** — Detecta la preferencia del navegador (`Accept-Language`).
- **Selector de idioma** — Alterna fácilmente entre Inglés y Español en cualquier página.
- **Persistencia de sesión** — El idioma seleccionado se recuerda mediante sesiones de PHP.
- **Diccionario extensible** — Añade nuevos idiomas editando `includes/lang.php`.

## 🛠️ Stack Tecnológico

| Capa | Tecnología |
|-------|-----------|
| **Backend** | PHP 7.4+ |
| **Base de Datos** | SQLite 3 (archivo local autogenerado) |
| **Frontend** | HTML5, CSS3 (responsivo), Vanilla JavaScript |
| **Autenticación** | PHP Sessions + `password_hash` / `password_verify` (bcrypt) |

## ⚙️ Instalación

### Requisitos Previos
- PHP 7.4+ con `pdo_sqlite` y `fileinfo` habilitados.
- Servidor web (Apache, Nginx) o entorno local (XAMPP, Laragon, etc.).

### Pasos
1. **Clona el repositorio** o descárgalo en tu carpeta pública (ej. `htdocs/`).
2. **Otorga permisos de escritura** a la carpeta principal (el sistema creará las carpetas `data/` y `uploads/` automáticamente).
3. **Configura la zona horaria** en `includes/config.php` (ej. `America/Mexico_City`).
4. (Opcional) Ejecuta las sentencias SQL (ver la versión en inglés) si deseas utilizar el módulo de horarios semanales (*Scheduler*).
5. Abre la aplicación en tu navegador. 

## 🔐 Credenciales por Defecto (Pruebas)

Al iniciar por primera vez, el sistema genera automáticamente usuarios de prueba:

### Administrador
| Usuario | Contraseña |
|----------|----------|
| `admin` | `Admin123*` |

### Empleados
| Nombre | Correo | Contraseña |
|------|-------|----------|
| John Doe | `john@example.com` | `password123` |
| Jane Doe | `jane@example.com` | `password123` |

> ⚠️ **Cambia todas las contraseñas** antes de utilizar en producción.
