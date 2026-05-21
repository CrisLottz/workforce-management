<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Accesos | Control de Asistencia</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .role-cards { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-top: 30px; 
        }
        .role-card { 
            background: #fff; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 30px 20px; 
            text-align: center; 
            transition: transform 0.2s, box-shadow 0.2s; 
            text-decoration: none; 
            color: inherit; 
            display: block; 
        }
        .role-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 15px rgba(0,0,0,0.1); 
            border-color: var(--primary); 
        }
        .role-icon { 
            font-size: 3.5rem; 
            margin-bottom: 15px; 
        }
        .role-title { 
            font-size: 1.3rem; 
            font-weight: bold; 
            margin-bottom: 10px; 
            color: var(--text); 
        }
        .role-desc { 
            font-size: 0.95rem; 
            color: #666; 
            margin-bottom: 25px; 
            min-height: 40px;
        }
    </style>
</head>
<body class="login-bg">
    <div class="container" style="max-width: 900px; margin-top: 40px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1 style="color: #333; font-size: 2rem;">🏢 Workforce Management</h1>
            <p style="color: #666; font-size: 1.1rem;">Sistema de Control de Asistencia y Horarios</p>
            <h2 style="margin-top: 30px; color: var(--primary);">Selecciona tu perfil de acceso</h2>
        </div>
        
        <div class="role-cards">
            <a href="empleado.php" class="role-card">
                <div class="role-icon">🧑‍💻</div>
                <div class="role-title">Empleado</div>
                <div class="role-desc">Registra tus entradas, salidas y visualiza tu horario asignado del día.</div>
                <span class="btn btn-primary btn-block">Ingresar como Empleado</span>
            </a>
            
            <a href="scheduler/login.php" class="role-card">
                <div class="role-icon">📅</div>
                <div class="role-title">Planificador</div>
                <div class="role-desc">Gestiona los horarios semanales y asigna los días libres del equipo.</div>
                <span class="btn btn-success btn-block" style="background-color: #10b981;">Ingresar como Planificador</span>
            </a>
            
            <a href="admin/login.php" class="role-card">
                <div class="role-icon">⚙️</div>
                <div class="role-title">Administrador</div>
                <div class="role-desc">Gestiona usuarios, audita las asistencias y exporta reportes en CSV.</div>
                <span class="btn btn-secondary btn-block">Ingresar como Administrador</span>
            </a>
        </div>
    </div>
</body>
</html>