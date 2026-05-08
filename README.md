# 🕒 TimeTrack Pro PHP (Sistema Punch & Zoho Sync)

Aplicación web integral desarrollada en PHP para el control operativo de la firma. El sistema unifica el control de asistencia de empleados, la captura rápida de prospectos (leads) y la gestión inteligente de citas (Legal Intakes) con sincronización bidireccional en tiempo real con la API de Zoho CRM.

## 🚀 Características Principales

El sistema se divide en tres módulos fundamentales:

1. **Módulo de Asistencia (Reloj Checador):**
   - Registro de entrada y salida forzado a la zona horaria de Utah (`America/Denver`).
   - Captura obligatoria de evidencia fotográfica y geolocalización (GPS).
   - **Modo de Emergencia (Fallback):** Permite el registro manual con justificación en caso de pérdida de conexión, problemas con la cámara o fallos del GPS, marcando el registro para auditoría.
   - Panel de "Scheduler" para la planificación de horarios semanales por empleado.

2. **Módulo de Gestión de Leads:**
   - Formulario rápido para registro de prospectos.
   - Sincronización instantánea con Zoho CRM, asignando automáticamente el lead al promotor que inició sesión.
   - Flujo continuo: Permite agendar una cita inmediatamente después de registrar al lead.

3. **Módulo de Agendamiento (Legal Intake):**
   - Lectura de calendarios en tiempo real desde Zoho CRM mediante consultas `COQL`.
   - Prevención de colisiones: Bloquea matemáticamente los horarios (9 AM a 4 PM) que ya están ocupados en el calendario del asesor seleccionado.
   - Creación de eventos en Zoho CRM vinculando al Lead y al Asesor, con envío de invitaciones automáticas.

## 🛠️ Stack Tecnológico

- **Backend:** PHP 7.4+
- **Base de Datos:** SQLite 3 (Archivo local `.sqlite` auto-generado para baja latencia).
- **Frontend:** HTML5, CSS3 (Diseño responsivo), JavaScript (Vanilla JS, Fetch API).
- **Integraciones:** Zoho CRM API v2.0 (OAuth 2.0 vía Refresh Token).

## 🗄️ Estructura de la Base de Datos

La base de datos se autogenera en el primer inicio a través de `db.php` con las siguientes tablas principales:

- `admin_users`: Accesos para el panel de administración.
- `employees`: Credenciales y roles (ventas, IT, planificador) de los usuarios del sistema.
- `attendance_records`: Bitácora inmutable de entradas y salidas (incluye latitud, longitud, ruta de foto y justificaciones).
- `advisors`: Lista pública sincronizada de asesores de Zoho disponibles para agendar citas (Modalidad Presencial/Virtual).
- `schedules`: Horarios semanales asignados a cada empleado.

## ⚙️ Instalación y Despliegue (cPanel / Hosting)

1. **Clonar/Subir el repositorio** en la carpeta `public_html/tracking` (o la ruta deseada).
2. **Permisos de carpetas:** Es CRÍTICO asegurar que las siguientes carpetas tengan permisos de escritura (`755` o `775`):
   - `data/` (Aquí se crea la base de datos SQLite).
   - `uploads/` (Aquí se guardan las fotos de evidencia).
3. **Configuración de Zoho:**
   - Asegúrese de que el archivo de configuración (ej. `includes/config.php` o `functions.php`) contenga el `CLIENT_ID`, `CLIENT_SECRET` y el `REFRESH_TOKEN` de Zoho CRM.
4. **Inicialización:**
   - Al acceder a cualquier ruta por primera vez, `db.php` creará el esquema y sembrará los usuarios por defecto.

## 🔐 Credenciales por Defecto

En el primer despliegue, el sistema siembra automáticamente las siguientes credenciales:

**Panel Administrador:**
- **Usuario:** `admin`
- **Contraseña:** `Admin123*`

*(La plantilla también siembra a tu equipo de ventas actual. Puedes modificar estas contraseñas editando la función `initializeDatabase` en `db.php` o directamente desde el panel IT).*

## 📡 Sobre la API de Zoho

El sistema no requiere re-autenticación manual constante. Utiliza un flujo de **Refresh Token** para solicitar un `access_token` temporal en cada ejecución (o según se requiera). Los _scopes_ necesarios en la consola de Zoho para que el sistema funcione correctamente son:
- `ZohoCRM.modules.ALL`
- `ZohoCRM.users.READ`
- `ZohoCRM.coql.READ`
