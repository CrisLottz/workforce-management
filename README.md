# 🏢 Workforce Management

An open-source, self-hosted employee attendance tracking system built with PHP and SQLite. Track clock-in/clock-out with photo evidence and GPS geolocation, manage weekly schedules, and export reports — all without external dependencies or API integrations.

Ready to deploy on any PHP hosting (shared hosting, VPS, cPanel, XAMPP, Laragon, etc.).

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

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 7.4+ |
| **Database** | SQLite 3 (auto-generated local file) |
| **Frontend** | HTML5, CSS3 (responsive), Vanilla JavaScript |
| **Auth** | PHP Sessions + `password_hash` / `password_verify` (bcrypt) |

> **Zero external dependencies.** No frameworks, no Composer packages, no third-party APIs. Just PHP + SQLite.

## 📁 Project Structure

```
workforce-management/
├── index.php                  # Landing portal — role selector (Employee / Scheduler / Admin)
├── empleado.php               # Employee panel: login, clock in/out, schedule view, today's records
├── assets/
│   └── style.css              # Global stylesheet (responsive design)
├── includes/
│   ├── config.php             # App constants: timezone, paths. Auto-creates data/ and uploads/
│   ├── db.php                 # PDO/SQLite connection, schema initialization, seed data
│   └── functions.php          # Helpers: HTML escaping, auth guards, photo upload, query builders
├── admin/
│   ├── login.php              # Admin login
│   ├── dashboard.php          # Admin dashboard: stats, employee CRUD, filters, records
│   ├── export.php             # CSV export of filtered attendance records
│   └── logout.php             # Admin session teardown
├── scheduler/
│   ├── login.php              # Scheduler login (uses scheduler_users table)
│   ├── index.php              # Scheduler panel: weekly schedule assignment per employee
│   └── logout.php             # Scheduler session teardown
├── data/                      # (auto-generated, gitignored) SQLite database
└── uploads/                   # (auto-generated, gitignored) Photo evidence files
```

## 🗄️ Database Schema

The SQLite database is auto-generated at `data/attendance.sqlite` on first access. The schema is initialized from `includes/db.php`:

| Table | Purpose |
|-------|---------|
| `admin_users` | Admin credentials (username + bcrypt hash) |
| `employees` | Employee accounts (full name, email, bcrypt hash) |
| `attendance_records` | Clock-in/out log (name, type, date, time, GPS coords, photo path) |
| `schedules`* | Weekly schedules assigned by the scheduler (employee, date, start/end time, is_off) |
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
   See the full list of [supported timezones](https://www.php.net/manual/en/timezones.php).

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

## 🔧 Configuration

All configuration lives in [`includes/config.php`](includes/config.php):

| Constant | Default | Description |
|----------|---------|-------------|
| `APP_NAME` | `TimeTrack Pro PHP` | Application display name |
| `APP_TIMEZONE` | `America/Denver` | Timezone for all timestamps |
| `DATA_PATH` | `<project>/data` | SQLite database directory |
| `UPLOAD_PATH` | `<project>/uploads` | Photo evidence directory |
| `DB_PATH` | `<DATA_PATH>/attendance.sqlite` | Full path to the database file |

## 🌐 User Flow

```
┌──────────────────────────────────────────────────┐
│              index.php (Landing Portal)          │
│   ┌──────────┐  ┌───────────┐  ┌──────────────┐ │
│   │ Employee │  │ Scheduler │  │ Admin        │ │
│   └────┬─────┘  └─────┬─────┘  └──────┬───────┘ │
└────────┼──────────────┼───────────────┼──────────┘
         ▼              ▼               ▼
   empleado.php   scheduler/       admin/
   ┌───────────┐  ┌────────────┐  ┌──────────────┐
   │ Login     │  │ Login      │  │ Login        │
   │ Clock     │  │ Select     │  │ Dashboard    │
   │ In / Out  │  │ week &     │  │ Stats &      │
   │ Schedule  │  │ employee   │  │ Employee     │
   │ view      │  │ Assign     │  │ CRUD         │
   │ Today's   │  │ shifts &   │  │ Filters &    │
   │ records   │  │ days off   │  │ CSV Export   │
   └───────────┘  └────────────┘  └──────────────┘
```

## 🤝 Contributing

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add my feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Open a Pull Request.

## 📝 License

This project is open source and available under the [MIT License](LICENSE).
