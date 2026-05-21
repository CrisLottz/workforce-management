<?php
/**
 * Internationalization (i18n) module
 *
 * Supports: English (en), Spanish (es)
 * Default:  English
 * Detection priority: URL ?lang= → Session → Browser Accept-Language → 'en'
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------------------
// Language detection
// ---------------------------------------------------------------------------
function detectLanguage(): string
{
    // 1. Explicit switch via URL parameter
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'es'], true)) {
        $_SESSION['lang'] = $_GET['lang'];
        return $_GET['lang'];
    }

    // 2. Previously selected (stored in session)
    if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'es'], true)) {
        return $_SESSION['lang'];
    }

    // 3. Browser Accept-Language header
    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if (preg_match('/^es/i', $accept)) {
        $_SESSION['lang'] = 'es';
        return 'es';
    }

    // 4. Default
    $_SESSION['lang'] = 'en';
    return 'en';
}

$GLOBALS['_app_lang'] = detectLanguage();

function currentLang(): string
{
    return $GLOBALS['_app_lang'];
}

// ---------------------------------------------------------------------------
// Translation helper
// ---------------------------------------------------------------------------
function t(string $key): string
{
    global $_translations;
    $lang = currentLang();
    return $_translations[$lang][$key] ?? $_translations['en'][$key] ?? $key;
}

// ---------------------------------------------------------------------------
// Language-switcher widget (HTML)
// ---------------------------------------------------------------------------
function langSwitcher(string $variant = ''): string
{
    $current = currentLang();
    $params  = $_GET;

    $params['lang'] = 'en';
    $enUrl = '?' . http_build_query($params);
    $params['lang'] = 'es';
    $esUrl = '?' . http_build_query($params);

    $enActive = $current === 'en' ? ' active' : '';
    $esActive = $current === 'es' ? ' active' : '';
    $extra    = $variant ? ' lang-switcher--' . $variant : '';

    return '<div class="lang-switcher' . $extra . '">'
         . '<a href="' . htmlspecialchars($enUrl, ENT_QUOTES, 'UTF-8') . '" class="lang-option' . $enActive . '">EN</a>'
         . '<span class="lang-sep">|</span>'
         . '<a href="' . htmlspecialchars($esUrl, ENT_QUOTES, 'UTF-8') . '" class="lang-option' . $esActive . '">ES</a>'
         . '</div>';
}

// ---------------------------------------------------------------------------
// Translations dictionary
// ---------------------------------------------------------------------------
$_translations = [

// =========================================================================
// ENGLISH
// =========================================================================
'en' => [

    // ---- General / Shared ------------------------------------------------
    'app_name'              => 'Workforce Management',
    'email'                 => 'Email',
    'password'              => 'Password',
    'login'                 => 'Log In',
    'logout'                => 'Log Out',
    'employee'              => 'Employee',
    'type'                  => 'Type',
    'date'                  => 'Date',
    'time'                  => 'Time',
    'location'              => 'Location',
    'photo'                 => 'Photo',
    'actions'               => 'Actions',
    'all'                   => 'All',
    'clock_in'              => 'Clock In',
    'clock_out'             => 'Clock Out',
    'view_map'              => '📍 View map',
    'no_location'           => 'No location',
    'name'                  => 'Name',
    'delete'                => 'Delete',
    'records'               => 'records',

    // ---- Portal (index.php) ----------------------------------------------
    'portal_title'          => 'Access Portal | Attendance Control',
    'portal_subtitle'       => 'Attendance & Schedule Control System',
    'select_role'           => 'Select your access profile',
    'role_employee'         => 'Employee',
    'role_employee_desc'    => 'Clock in/out and view your assigned schedule for today.',
    'enter_as_employee'     => 'Enter as Employee',
    'role_scheduler'        => 'Scheduler',
    'role_scheduler_desc'   => 'Manage weekly schedules and assign days off for the team.',
    'enter_as_scheduler'    => 'Enter as Scheduler',
    'role_admin'            => 'Administrator',
    'role_admin_desc'       => 'Manage users, audit attendance records and export CSV reports.',
    'enter_as_admin'        => 'Enter as Administrator',

    // ---- Employee Panel (empleado.php) -----------------------------------
    'invalid_credentials'   => 'Invalid credentials.',
    'invalid_record_type'   => 'Invalid record type.',
    'clock_in_success'      => 'Clock-in recorded successfully.',
    'clock_out_success'     => 'Clock-out recorded successfully.',
    'not_registered'        => 'Not registered',
    'working'               => 'Working',
    'out_of_office'         => 'Out of office',
    'employee_mode'         => 'Employee Mode',
    'identify_hint'         => 'Log in to record your attendance.',
    'go_admin_panel'        => '🔐 Go to admin panel',
    'employee_access'       => 'Employee Access',
    'logged_in_as'          => 'Logged in as:',
    'mandatory_timezone'    => 'Mandatory timezone:',
    'admin_panel_link'      => '🔐 Admin panel',
    'loading_date'          => 'Loading date...',
    'my_schedule_today'     => '📅 My Schedule (Today)',
    'no_schedule_today'     => 'You have no schedule assigned for today.',
    'day_off'               => 'DAY OFF',
    'late_alert'            => '⚠️ YOU HAVE NOT CLOCKED IN AND YOUR SHIFT HAS STARTED.',
    'your_current_status'   => 'Your Current Status',
    'your_last_record'      => 'Your Last Record',
    'record_attendance'     => '📝 Record Attendance',
    'photo_evidence'        => 'Photo evidence *',
    'photo_hint'            => 'You can take the photo from your phone or upload it from your computer.',
    'user_location'         => 'User location',
    'getting_location'      => 'Getting location...',
    'btn_clock_in'          => '🌅 Clock In',
    'btn_clock_out'         => '🌙 Clock Out',
    'today_records'         => '📋 Today\'s Records (General)',
    'no_records_today'      => 'No records for today.',
    'geo_captured'          => 'Location captured successfully.',
    'geo_error'             => 'Could not get location: ',
    'geo_unsupported'       => 'Browser does not support geolocation.',

    // ---- Admin Login -----------------------------------------------------
    'admin_login_title'     => 'Admin Login',
    'admin_access'          => 'Admin Access',
    'password_required_badge' => 'Password required',
    'enter_panel'           => 'Enter panel',
    'invalid_user_pass'     => 'Incorrect username or password.',
    'username'              => 'Username',
    'enter_panel_btn'       => 'Enter panel',

    // ---- Admin Dashboard -------------------------------------------------
    'admin_panel_title'     => 'Admin Panel',
    'user_label'            => 'User:',
    'record_deleted'        => 'Attendance record deleted successfully.',
    'employee_access_deleted' => 'Employee access revoked and deleted.',
    'timezone_notice'       => 'All records are stored with the configured timezone',
    'export_csv'            => '📥 Export CSV',
    'view_public_portal'    => '👤 View public portal',
    'employee_management'   => '👥 Employee Management (Access)',
    'add_new_employee'      => 'Add New Employee',
    'full_name'             => 'Full name',
    'name_placeholder'      => 'E.g. John Smith',
    'email_user'            => 'Email (Username)',
    'secure_password'       => 'Secure password',
    'create_access'         => '➕ Create Access',
    'active_employees'      => 'Active Employees',
    'email_col'             => 'Email',
    'action'                => 'Action',
    'no_employees'          => 'No employees registered.',
    'delete_btn'            => 'Delete',
    'confirm_delete_employee' => 'Are you sure you want to delete this employee\'s access? They will no longer be able to record attendance.',
    'total_filtered'        => 'Total filtered employees',
    'present_today'         => 'Present today',
    'avg_hours'             => 'Average hours',
    'attendance_filters'    => '🔍 Attendance Filters',
    'apply_filters'         => 'Apply filters',
    'daily_summary'         => '📅 Daily Summary',
    'no_records_date'       => 'No records for this date.',
    'duration'              => 'Duration',
    'detailed_records'      => '📊 Detailed Records',
    'no_records'            => 'No records.',
    'confirm_delete_record' => 'Delete this record?',
    'all_fields_required'   => 'All fields are required to create the employee.',
    'email_already_exists'  => 'That email is already registered in the system.',
    'employee_created'      => 'Employee \'%s\' created successfully. They can now log in.',

    // ---- CSV Export ------------------------------------------------------
    'csv_name'              => 'Name',
    'csv_type'              => 'Type',
    'csv_date'              => 'Date',
    'csv_time'              => 'Time',
    'csv_latitude'          => 'Latitude',
    'csv_longitude'         => 'Longitude',
    'csv_accuracy'          => 'Accuracy',
    'csv_timezone'          => 'Timezone',

    // ---- Scheduler Login -------------------------------------------------
    'scheduler_access'      => '📅 Scheduler Access',
    'incorrect_credentials' => 'Incorrect credentials.',
    'enter_btn'             => 'Log In',

    // ---- Scheduler Panel -------------------------------------------------
    'scheduler_panel'       => 'Scheduler Panel',
    'select_week_employee'  => '1. Select Week and Employee',
    'any_date_in_week'      => 'Any date in the week to manage',
    'assign_schedules'      => '2. Assign Schedules (Week of %s)',
    'day_sunday'            => 'Sunday',
    'day_monday'            => 'Monday',
    'day_tuesday'           => 'Tuesday',
    'day_wednesday'         => 'Wednesday',
    'day_thursday'          => 'Thursday',
    'day_friday'            => 'Friday',
    'day_saturday'          => 'Saturday',
    'save_weekly_schedule'  => '💾 Save Weekly Schedule',
    'schedule_updated'      => 'Weekly schedules for %s updated successfully.',

    // ---- functions.php error messages ------------------------------------
    'photo_required'        => 'You must upload a valid photo.',
    'photo_upload_error'    => 'There was a problem uploading the photo.',
    'photo_size_limit'      => 'The photo exceeds the 5 MB limit.',
    'photo_format'          => 'Only JPG, PNG or WEBP images are allowed.',
    'photo_save_error'      => 'Could not save the photo.',
],

// =========================================================================
// SPANISH
// =========================================================================
'es' => [

    // ---- General / Shared ------------------------------------------------
    'app_name'              => 'Gestión de Personal',
    'email'                 => 'Correo electrónico',
    'password'              => 'Contraseña',
    'login'                 => 'Iniciar Sesión',
    'logout'                => 'Cerrar Sesión',
    'employee'              => 'Empleado',
    'type'                  => 'Tipo',
    'date'                  => 'Fecha',
    'time'                  => 'Hora',
    'location'              => 'Ubicación',
    'photo'                 => 'Foto',
    'actions'               => 'Acciones',
    'all'                   => 'Todos',
    'clock_in'              => 'Entrada',
    'clock_out'             => 'Salida',
    'view_map'              => '📍 Ver mapa',
    'no_location'           => 'Sin ubicación',
    'name'                  => 'Nombre',
    'delete'                => 'Eliminar',
    'records'               => 'registros',

    // ---- Portal (index.php) ----------------------------------------------
    'portal_title'          => 'Portal de Accesos | Control de Asistencia',
    'portal_subtitle'       => 'Sistema de Control de Asistencia y Horarios',
    'select_role'           => 'Selecciona tu perfil de acceso',
    'role_employee'         => 'Empleado',
    'role_employee_desc'    => 'Registra tus entradas, salidas y visualiza tu horario asignado del día.',
    'enter_as_employee'     => 'Ingresar como Empleado',
    'role_scheduler'        => 'Planificador',
    'role_scheduler_desc'   => 'Gestiona los horarios semanales y asigna los días libres del equipo.',
    'enter_as_scheduler'    => 'Ingresar como Planificador',
    'role_admin'            => 'Administrador',
    'role_admin_desc'       => 'Gestiona usuarios, audita las asistencias y exporta reportes en CSV.',
    'enter_as_admin'        => 'Ingresar como Administrador',

    // ---- Employee Panel (empleado.php) -----------------------------------
    'invalid_credentials'   => 'Credenciales inválidas.',
    'invalid_record_type'   => 'Tipo de registro inválido.',
    'clock_in_success'      => 'Entrada registrada correctamente.',
    'clock_out_success'     => 'Salida registrada correctamente.',
    'not_registered'        => 'No registrado',
    'working'               => 'Trabajando',
    'out_of_office'         => 'Fuera de oficina',
    'employee_mode'         => 'Modo Empleado',
    'identify_hint'         => 'Identifícate para registrar tu asistencia.',
    'go_admin_panel'        => '🔐 Ir al panel administrador',
    'employee_access'       => 'Acceso de Empleados',
    'logged_in_as'          => 'Sesión iniciada como:',
    'mandatory_timezone'    => 'Hora obligatoria:',
    'admin_panel_link'      => '🔐 Panel admin',
    'loading_date'          => 'Cargando fecha...',
    'my_schedule_today'     => '📅 Mi Horario (Hoy)',
    'no_schedule_today'     => 'No tienes un horario asignado para hoy.',
    'day_off'               => 'DÍA OFF',
    'late_alert'            => '⚠️ NO HAS REGISTRADO ENTRADA Y YA PASÓ TU HORA.',
    'your_current_status'   => 'Tu Estado Actual',
    'your_last_record'      => 'Tu Último Registro',
    'record_attendance'     => '📝 Registrar asistencia',
    'photo_evidence'        => 'Foto evidencia *',
    'photo_hint'            => 'Puede tomar la foto desde el celular o subirla desde el equipo.',
    'user_location'         => 'Ubicación del usuario',
    'getting_location'      => 'Intentando obtener ubicación...',
    'btn_clock_in'          => '🌅 Registrar entrada',
    'btn_clock_out'         => '🌙 Registrar salida',
    'today_records'         => '📋 Registros de hoy (General)',
    'no_records_today'      => 'No hay registros para hoy.',
    'geo_captured'          => 'Ubicación capturada correctamente.',
    'geo_error'             => 'No se pudo obtener la ubicación: ',
    'geo_unsupported'       => 'El navegador no soporta geolocalización.',

    // ---- Admin Login -----------------------------------------------------
    'admin_login_title'     => 'Login Administrador',
    'admin_access'          => 'Acceso Administrador',
    'password_required_badge' => 'Contraseña obligatoria',
    'enter_panel'           => 'Ingresar al panel',
    'invalid_user_pass'     => 'Usuario o contraseña incorrectos.',
    'username'              => 'Usuario',
    'enter_panel_btn'       => 'Entrar al panel',

    // ---- Admin Dashboard -------------------------------------------------
    'admin_panel_title'     => 'Panel Administrador',
    'user_label'            => 'Usuario:',
    'record_deleted'        => 'Registro de asistencia eliminado correctamente.',
    'employee_access_deleted' => 'Acceso de empleado revocado y eliminado.',
    'timezone_notice'       => 'Todos los registros están guardados con la zona horaria configurada',
    'export_csv'            => '📥 Exportar CSV',
    'view_public_portal'    => '👤 Ver portal público',
    'employee_management'   => '👥 Gestión de Empleados (Accesos)',
    'add_new_employee'      => 'Añadir Nuevo Empleado',
    'full_name'             => 'Nombre completo',
    'name_placeholder'      => 'Ej. Carlos Perez',
    'email_user'            => 'Correo electrónico (Usuario)',
    'secure_password'       => 'Contraseña segura',
    'create_access'         => '➕ Crear Acceso',
    'active_employees'      => 'Empleados Activos',
    'email_col'             => 'Correo',
    'action'                => 'Acción',
    'no_employees'          => 'No hay empleados registrados.',
    'delete_btn'            => 'Borrar',
    'confirm_delete_employee' => '¿Seguro que deseas eliminar el acceso de este empleado? Ya no podrá registrar asistencia.',
    'total_filtered'        => 'Total empleados filtrados',
    'present_today'         => 'Presentes hoy',
    'avg_hours'             => 'Promedio de horas',
    'attendance_filters'    => '🔍 Filtros de Asistencia',
    'apply_filters'         => 'Aplicar filtros',
    'daily_summary'         => '📅 Resumen del día',
    'no_records_date'       => 'No hay registros para esta fecha.',
    'duration'              => 'Duración',
    'detailed_records'      => '📊 Registros detallados',
    'no_records'            => 'No hay registros.',
    'confirm_delete_record' => '¿Desea eliminar este registro?',
    'all_fields_required'   => 'Todos los campos son obligatorios para crear el empleado.',
    'email_already_exists'  => 'Ese correo ya está registrado en el sistema.',
    'employee_created'      => 'Empleado \'%s\' creado correctamente. Ya puede iniciar sesión.',

    // ---- CSV Export ------------------------------------------------------
    'csv_name'              => 'Nombre',
    'csv_type'              => 'Tipo',
    'csv_date'              => 'Fecha',
    'csv_time'              => 'Hora',
    'csv_latitude'          => 'Latitud',
    'csv_longitude'         => 'Longitud',
    'csv_accuracy'          => 'Exactitud',
    'csv_timezone'          => 'Zona Horaria',

    // ---- Scheduler Login -------------------------------------------------
    'scheduler_access'      => '📅 Acceso Scheduler',
    'incorrect_credentials' => 'Credenciales incorrectas.',
    'enter_btn'             => 'Ingresar',

    // ---- Scheduler Panel -------------------------------------------------
    'scheduler_panel'       => 'Panel Scheduler',
    'select_week_employee'  => '1. Seleccionar Semana y Empleado',
    'any_date_in_week'      => 'Cualquier fecha de la semana a gestionar',
    'assign_schedules'      => '2. Asignar Horarios (Semana del %s)',
    'day_sunday'            => 'Domingo',
    'day_monday'            => 'Lunes',
    'day_tuesday'           => 'Martes',
    'day_wednesday'         => 'Miércoles',
    'day_thursday'          => 'Jueves',
    'day_friday'            => 'Viernes',
    'day_saturday'          => 'Sábado',
    'save_weekly_schedule'  => '💾 Guardar Horario Semanal',
    'schedule_updated'      => 'Horarios semanales de %s actualizados correctamente.',

    // ---- functions.php error messages ------------------------------------
    'photo_required'        => 'Debe subir una fotografía válida.',
    'photo_upload_error'    => 'Hubo un problema al subir la fotografía.',
    'photo_size_limit'      => 'La fotografía supera el límite de 5 MB.',
    'photo_format'          => 'Solo se permiten imágenes JPG, PNG o WEBP.',
    'photo_save_error'      => 'No se pudo guardar la fotografía.',
],

];
