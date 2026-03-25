# TimeTrack Pro PHP

Sistema de asistencia en PHP con:

- Registro público desde `index.php`
- Hora obligatoria de Utah (`America/Denver`)
- Captura de nombre, foto y ubicación
- Panel administrador protegido por contraseña
- Exportación CSV
- Base de datos SQLite automática

## Credenciales por defecto

- Usuario: `admin`
- Contraseña: `Admin123*`

## Cómo usar

1. Suba la carpeta completa al hosting.
2. Asegúrese de que las carpetas `data/` y `uploads/` tengan permiso de escritura.
3. Abra `index.php` para el formulario público.
4. Abra `admin/login.php` para el panel.

## Estructura

- `index.php`: formulario público
- `admin/login.php`: acceso protegido
- `admin/dashboard.php`: panel administrador
- `admin/export.php`: exportación CSV
- `includes/db.php`: conexión y creación automática de tablas
- `uploads/`: fotos
- `data/attendance.sqlite`: base SQLite

## Nota

Si desea cambiar la contraseña por defecto, entre al archivo `includes/db.php` y cambie la parte de creación del usuario inicial, o actualice el hash directamente en SQLite.
