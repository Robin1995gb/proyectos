<?php
require_once 'php/conexion.php';
require_once 'php/funciones.php';
require_once 'php/verificar_sesion.php';

// Obtener estadísticas según el rol
$estadisticas = [];

switch ($_SESSION['usuario_rol_id']) {
    case 1: // Administrador
        $estadisticas['total_usuarios'] = obtenerFila("SELECT COUNT(*) as total FROM usuarios")['total'];
        $estadisticas['total_estudiantes'] = obtenerFila("SELECT COUNT(*) as total FROM estudiantes")['total'];
        $estadisticas['total_docentes'] = obtenerFila("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2")['total'];
        $estadisticas['total_cursos'] = obtenerFila("SELECT COUNT(*) as total FROM cursos")['total'];
        break;
        
    case 2: // Docente
        $estadisticas['total_estudiantes'] = obtenerFila("
            SELECT COUNT(DISTINCT m.estudiante_id) as total 
            FROM matriculas m 
            JOIN asignacion_docentes ad ON m.curso_id = ad.curso_id 
            WHERE ad.docente_id = ?
        ", [$_SESSION['usuario_id']])['total'];
        
        $estadisticas['total_cursos'] = obtenerFila("
            SELECT COUNT(DISTINCT curso_id) as total 
            FROM asignacion_docentes 
            WHERE docente_id = ?
        ", [$_SESSION['usuario_id']])['total'];
        
        $estadisticas['total_asignaturas'] = obtenerFila("
            SELECT COUNT(DISTINCT asignatura_id) as total 
            FROM asignacion_docentes 
            WHERE docente_id = ?
        ", [$_SESSION['usuario_id']])['total'];
        break;
        
    case 3: // Secretaria
        $estadisticas['total_estudiantes'] = obtenerFila("SELECT COUNT(*) as total FROM estudiantes")['total'];
        $estadisticas['total_matriculas'] = obtenerFila("SELECT COUNT(*) as total FROM matriculas")['total'];
        $estadisticas['total_cursos'] = obtenerFila("SELECT COUNT(*) as total FROM cursos")['total'];
        break;
        
    case 4: // Rector
    case 5: // Vicerrector
        $estadisticas['total_estudiantes'] = obtenerFila("SELECT COUNT(*) as total FROM estudiantes")['total'];
        $estadisticas['total_docentes'] = obtenerFila("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2")['total'];
        $estadisticas['total_cursos'] = obtenerFila("SELECT COUNT(*) as total FROM cursos")['total'];
        break;
}

// Obtener últimas actividades
$actividades = obtenerActividades(null, 5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Escolar</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Dashboard</h1>
        
        <div class="dashboard-stats">
            <?php foreach ($estadisticas as $key => $value): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <?php
                        switch ($key) {
                            case 'total_usuarios':
                                echo '<i class="fas fa-users"></i>';
                                $label = 'Usuarios';
                                break;
                            case 'total_estudiantes':
                                echo '<i class="fas fa-user-graduate"></i>';
                                $label = 'Estudiantes';
                                break;
                            case 'total_docentes':
                                echo '<i class="fas fa-chalkboard-teacher"></i>';
                                $label = 'Docentes';
                                break;
                            case 'total_cursos':
                                echo '<i class="fas fa-book"></i>';
                                $label = 'Cursos';
                                break;
                            case 'total_matriculas':
                                echo '<i class="fas fa-file-alt"></i>';
                                $label = 'Matrículas';
                                break;
                            case 'total_asignaturas':
                                echo '<i class="fas fa-book-open"></i>';
                                $label = 'Asignaturas';
                                break;
                        }
                        ?>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $value; ?></h3>
                        <p><?php echo $label; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Últimas Actividades</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Detalles</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actividades as $actividad): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($actividad['nombres'] . ' ' . $actividad['apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($actividad['accion']); ?></td>
                                    <td><?php echo htmlspecialchars($actividad['detalles']); ?></td>
                                    <td><?php echo formatearFecha($actividad['fecha']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 