<?php
require_once 'php/conexion.php';
require_once 'php/funciones.php';
require_once 'php/verificar_sesion.php';

// Verificar que el usuario tenga permiso de docente o administrador
if (!in_array($_SESSION['usuario_rol_id'], [1, 2])) {
    header('Location: dashboard.php');
    exit;
}

$mensaje = '';
$error = '';

// Obtener el período lectivo actual
$periodo_actual = date('Y') . '-' . (date('Y') + 1);

// Procesar formulario de calificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estudiante_id = $_POST['estudiante_id'] ?? null;
    $curso_id = $_POST['curso_id'] ?? '';
    $periodo_lectivo = $_POST['periodo_lectivo'] ?? '';
    $tareas = $_POST['tareas'] ?? '';
    $conducta = $_POST['conducta'] ?? '';
    $evaluaciones = $_POST['evaluaciones'] ?? '';
    $examen = $_POST['examen'] ?? '';
    
    if (empty($estudiante_id) || empty($curso_id) || empty($periodo_lectivo) || 
        empty($tareas) || empty($conducta) || empty($evaluaciones) || empty($examen)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        try {
            // Verificar si el docente está asignado al curso
            $asignacion = obtenerFila("
                SELECT id FROM asignaciones_docentes 
                WHERE docente_id = ? AND curso_id = ? AND periodo_lectivo = ?
            ", [$_SESSION['usuario_id'], $curso_id, $periodo_lectivo]);
            
            if (!$asignacion && $_SESSION['usuario_rol_id'] != 1) {
                $error = 'No está asignado a este curso para el período seleccionado';
            } else {
                // Calcular el promedio
                $promedio = ($tareas * 0.2) + ($conducta * 0.1) + ($evaluaciones * 0.4) + ($examen * 0.3);
                
                // Verificar si ya existe una calificación para este estudiante y curso
                $existe = obtenerFila("
                    SELECT id FROM calificaciones 
                    WHERE estudiante_id = ? AND curso_id = ? AND periodo_lectivo = ?
                ", [$estudiante_id, $curso_id, $periodo_lectivo]);
                
                if ($existe) {
                    // Actualizar calificación existente
                    $datos = [
                        'tareas' => $tareas,
                        'conducta' => $conducta,
                        'evaluaciones' => $evaluaciones,
                        'examen' => $examen,
                        'promedio' => $promedio
                    ];
                    
                    actualizar('calificaciones', $datos, 'id = ?', [$existe['id']]);
                    $mensaje = 'Calificación actualizada correctamente';
                } else {
                    // Insertar nueva calificación
                    $datos = [
                        'estudiante_id' => $estudiante_id,
                        'curso_id' => $curso_id,
                        'docente_id' => $_SESSION['usuario_id'],
                        'periodo_lectivo' => $periodo_lectivo,
                        'tareas' => $tareas,
                        'conducta' => $conducta,
                        'evaluaciones' => $evaluaciones,
                        'examen' => $examen,
                        'promedio' => $promedio
                    ];
                    
                    insertar('calificaciones', $datos);
                    $mensaje = 'Calificación registrada correctamente';
                }
                
                // Registrar la actividad
                registrarActividad(
                    $_SESSION['usuario_id'],
                    'Registro de Calificación',
                    "Registro de calificación para estudiante ID: $estudiante_id en curso ID: $curso_id"
                );
            }
        } catch (PDOException $e) {
            $error = 'Error al registrar la calificación: ' . $e->getMessage();
        }
    }
}

// Obtener lista de cursos asignados al docente
if ($_SESSION['usuario_rol_id'] == 1) {
    // Si es administrador, mostrar todos los cursos
    $cursos = obtenerCursos();
} else {
    // Si es docente, mostrar solo sus cursos asignados
    $cursos = obtenerFilas("
        SELECT c.*, n.nombre as nivel_nombre
        FROM cursos c
        JOIN niveles_educativos n ON c.nivel_id = n.id
        JOIN asignaciones_docentes a ON c.id = a.curso_id
        WHERE a.docente_id = ? AND a.periodo_lectivo = ?
        ORDER BY c.nombre, c.paralelo
    ", [$_SESSION['usuario_id'], $periodo_actual]);
}

// Obtener lista de estudiantes matriculados
$estudiantes = obtenerEstudiantes();

// Obtener lista de calificaciones
$calificaciones = obtenerCalificacionesDocente($_SESSION['usuario_id'], $periodo_actual);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones - Sistema Escolar</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Registro de Calificaciones</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2>Nueva Calificación</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="calificacionForm">
                    <div class="form-group">
                        <label for="estudiante_id">Estudiante:</label>
                        <select id="estudiante_id" name="estudiante_id" required>
                            <option value="">Seleccione un estudiante</option>
                            <?php foreach ($estudiantes as $estudiante): ?>
                                <option value="<?php echo $estudiante['id']; ?>">
                                    <?php echo htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="curso_id">Curso:</label>
                        <select id="curso_id" name="curso_id" required>
                            <option value="">Seleccione un curso</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?php echo $curso['id']; ?>">
                                    <?php echo htmlspecialchars($curso['nombre'] . ' ' . $curso['paralelo'] . ' - ' . $curso['nivel_nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="periodo_lectivo">Período Lectivo:</label>
                        <input type="text" id="periodo_lectivo" name="periodo_lectivo" 
                               value="<?php echo htmlspecialchars($periodo_actual); ?>" required 
                               pattern="\d{4}-\d{4}" title="Formato: AAAA-AAAA">
                    </div>
                    
                    <div class="form-group">
                        <label for="tareas">Tareas (20%):</label>
                        <input type="number" id="tareas" name="tareas" min="0" max="10" step="0.1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="conducta">Conducta (10%):</label>
                        <input type="number" id="conducta" name="conducta" min="0" max="10" step="0.1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="evaluaciones">Evaluaciones (40%):</label>
                        <input type="number" id="evaluaciones" name="evaluaciones" min="0" max="10" step="0.1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="examen">Examen (30%):</label>
                        <input type="number" id="examen" name="examen" min="0" max="10" step="0.1" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Registrar Calificación</button>
                        <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Calificaciones Registradas</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Tareas</th>
                                <th>Conducta</th>
                                <th>Evaluaciones</th>
                                <th>Examen</th>
                                <th>Promedio</th>
                                <th>Período</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($calificaciones)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No hay calificaciones registradas</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($calificaciones as $calificacion): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($calificacion['estudiante_apellidos'] . ', ' . $calificacion['estudiante_nombres']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($calificacion['curso_nombre'] . ' ' . $calificacion['paralelo']); ?>
                                        </td>
                                        <td><?php echo number_format($calificacion['tareas'], 1); ?></td>
                                        <td><?php echo number_format($calificacion['conducta'], 1); ?></td>
                                        <td><?php echo number_format($calificacion['evaluaciones'], 1); ?></td>
                                        <td><?php echo number_format($calificacion['examen'], 1); ?></td>
                                        <td><?php echo number_format($calificacion['promedio'], 1); ?></td>
                                        <td><?php echo htmlspecialchars($calificacion['periodo_lectivo']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function limpiarFormulario() {
        document.getElementById('calificacionForm').reset();
    }
    </script>
</body>
</html> 