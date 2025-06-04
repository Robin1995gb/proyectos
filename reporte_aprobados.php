<?php
require_once 'php/conexion.php';
require_once 'php/funciones.php';
require_once 'php/verificar_sesion.php';

// Verificar que el usuario tenga permiso de administrador, rector o vicerrector
if (!in_array($_SESSION['usuario_rol_id'], [1, 4, 5])) {
    header('Location: dashboard.php');
    exit;
}

$mensaje = '';
$error = '';

// Obtener el período lectivo actual
$periodo_actual = date('Y') . '-' . (date('Y') + 1);

// Obtener lista de cursos
$cursos = obtenerCursos();

// Obtener lista de niveles educativos
$niveles = obtenerNivelesEducativos();

// Procesar filtros
$curso_id = $_GET['curso_id'] ?? '';
$nivel_id = $_GET['nivel_id'] ?? '';
$periodo_lectivo = $_GET['periodo_lectivo'] ?? $periodo_actual;

// Construir la consulta base
$sql = "
    SELECT 
        c.id,
        c.estudiante_id,
        c.curso_id,
        c.periodo_lectivo,
        c.promedio,
        e.nombres as estudiante_nombres,
        e.apellidos as estudiante_apellidos,
        cur.nombre as curso_nombre,
        cur.paralelo,
        n.nombre as nivel_nombre
    FROM calificaciones c
    JOIN estudiantes e ON c.estudiante_id = e.id
    JOIN cursos cur ON c.curso_id = cur.id
    JOIN niveles_educativos n ON cur.nivel_id = n.id
    WHERE 1=1
";

$params = [];

if ($curso_id) {
    $sql .= " AND c.curso_id = ?";
    $params[] = $curso_id;
}

if ($nivel_id) {
    $sql .= " AND cur.nivel_id = ?";
    $params[] = $nivel_id;
}

if ($periodo_lectivo) {
    $sql .= " AND c.periodo_lectivo = ?";
    $params[] = $periodo_lectivo;
}

$sql .= " ORDER BY n.nombre, cur.nombre, cur.paralelo, e.apellidos, e.nombres";

// Obtener calificaciones
$calificaciones = obtenerFilas($sql, $params);

// Separar aprobados y reprobados
$aprobados = [];
$reprobados = [];

foreach ($calificaciones as $calificacion) {
    if ($calificacion['promedio'] >= 7) {
        $aprobados[] = $calificacion;
    } else {
        $reprobados[] = $calificacion;
    }
}

// Calcular estadísticas
$total_estudiantes = count($calificaciones);
$total_aprobados = count($aprobados);
$total_reprobados = count($reprobados);
$porcentaje_aprobados = $total_estudiantes > 0 ? ($total_aprobados / $total_estudiantes) * 100 : 0;
$porcentaje_reprobados = $total_estudiantes > 0 ? ($total_reprobados / $total_estudiantes) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Aprobados y Reprobados - Sistema Escolar</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Reporte de Aprobados y Reprobados</h1>
        
        <div class="card">
            <div class="card-header">
                <h2>Filtros</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="form-inline">
                    <div class="form-group">
                        <label for="nivel_id">Nivel Educativo:</label>
                        <select id="nivel_id" name="nivel_id" class="form-control">
                            <option value="">Todos los niveles</option>
                            <?php foreach ($niveles as $nivel): ?>
                                <option value="<?php echo $nivel['id']; ?>" <?php echo $nivel_id == $nivel['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($nivel['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="curso_id">Curso:</label>
                        <select id="curso_id" name="curso_id" class="form-control">
                            <option value="">Todos los cursos</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?php echo $curso['id']; ?>" <?php echo $curso_id == $curso['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($curso['nombre'] . ' ' . $curso['paralelo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="periodo_lectivo">Período Lectivo:</label>
                        <input type="text" id="periodo_lectivo" name="periodo_lectivo" 
                               value="<?php echo htmlspecialchars($periodo_lectivo); ?>" 
                               class="form-control" pattern="\d{4}-\d{4}" title="Formato: AAAA-AAAA">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Estadísticas Generales</h2>
            </div>
            <div class="card-body">
                <div class="stats-container">
                    <div class="stat-box">
                        <h3>Total de Estudiantes</h3>
                        <p class="stat-number"><?php echo $total_estudiantes; ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Aprobados</h3>
                        <p class="stat-number"><?php echo $total_aprobados; ?></p>
                        <p class="stat-percentage"><?php echo number_format($porcentaje_aprobados, 1); ?>%</p>
                    </div>
                    <div class="stat-box">
                        <h3>Reprobados</h3>
                        <p class="stat-number"><?php echo $total_reprobados; ?></p>
                        <p class="stat-percentage"><?php echo number_format($porcentaje_reprobados, 1); ?>%</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Estudiantes Aprobados</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Nivel</th>
                                <th>Promedio</th>
                                <th>Período</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($aprobados)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay estudiantes aprobados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($aprobados as $estudiante): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($estudiante['estudiante_apellidos'] . ', ' . $estudiante['estudiante_nombres']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($estudiante['curso_nombre'] . ' ' . $estudiante['paralelo']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($estudiante['nivel_nombre']); ?></td>
                                        <td><?php echo number_format($estudiante['promedio'], 1); ?></td>
                                        <td><?php echo htmlspecialchars($estudiante['periodo_lectivo']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Estudiantes Reprobados</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Nivel</th>
                                <th>Promedio</th>
                                <th>Período</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reprobados)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay estudiantes reprobados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reprobados as $estudiante): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($estudiante['estudiante_apellidos'] . ', ' . $estudiante['estudiante_nombres']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($estudiante['curso_nombre'] . ' ' . $estudiante['paralelo']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($estudiante['nivel_nombre']); ?></td>
                                        <td><?php echo number_format($estudiante['promedio'], 1); ?></td>
                                        <td><?php echo htmlspecialchars($estudiante['periodo_lectivo']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .stats-container {
        display: flex;
        justify-content: space-around;
        margin: 20px 0;
    }
    
    .stat-box {
        text-align: center;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 5px;
        min-width: 200px;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #007bff;
        margin: 10px 0;
    }
    
    .stat-percentage {
        font-size: 18px;
        color: #28a745;
    }
    
    .form-inline {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    
    .form-group {
        margin-bottom: 0;
    }
    </style>
</body>
</html> 