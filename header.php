<?php
session_start();
require_once 'php/verificar_sesion.php';
verificarSesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Sistema Escolar</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body data-requires-auth="true" data-user-role="<?php echo $_SESSION['usuario_rol']; ?>">
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="img/logo.png" alt="Logo" onerror="this.src='https://via.placeholder.com/40'">
                    <h1>Sistema Escolar</h1>
                </div>
                <div class="user-info">
                    <span><?php echo $_SESSION['usuario_nombre']; ?></span>
                    <a href="php/logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <nav class="sidebar">
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <?php if ($_SESSION['usuario_rol_id'] == 1): // Administrador ?>
            <li>
                <a href="usuarios.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'usuarios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    Usuarios
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array($_SESSION['usuario_rol_id'], [1, 3])): // Administrador o Secretaria ?>
            <li>
                <a href="matricula.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'matricula.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    Matrícula
                </a>
            </li>
            <li>
                <a href="asignar_docente.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'asignar_docente.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Asignación de Docentes
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array($_SESSION['usuario_rol_id'], [1, 2])): // Administrador o Docente ?>
            <li>
                <a href="calificaciones.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'calificaciones.php' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i>
                    Calificaciones
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array($_SESSION['usuario_rol_id'], [1, 4, 5])): // Administrador, Rector o Vicerrector ?>
            <li>
                <a href="reportes.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'reportes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    Reportes
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="main-content">
        <div class="container"> 