<?php
require_once 'php/conexion.php';
require_once 'php/funciones.php';
require_once 'php/verificar_sesion.php';

// Verificar que el usuario tenga permiso de administrador
if ($_SESSION['usuario_rol_id'] != 1) {
    header('Location: dashboard.php');
    exit;
}

$mensaje = '';
$error = '';

// Obtener el período lectivo actual
$periodo_actual = date('Y') . '-' . (date('Y') + 1);

// Procesar eliminación de asignación
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    try {
        // Obtener información de la asignación antes de eliminarla
        $asignacion = obtenerFila("
            SELECT a.*, u.nombres as docente_nombres, u.apellidos as docente_apellidos,
                   c.nombre as curso_nombre, c.paralelo, asig.nombre as asignatura_nombre
            FROM asignaciones_docentes a
            JOIN usuarios u ON a.docente_id = u.id
            JOIN cursos c ON a.curso_id = c.id
            JOIN asignaturas asig ON a.asignatura_id = asig.id
            WHERE a.id = ?
        ", [$id]);
        
        if ($asignacion) {
            // Eliminar la asignación
            eliminar('asignaciones_docentes', 'id = ?', [$id]);
            
            // Registrar la actividad
            registrarActividad(
                $_SESSION['usuario_id'],
                'Eliminación de Asignación',
                "Se eliminó la asignación del docente {$asignacion['docente_nombres']} {$asignacion['docente_apellidos']} " .
                "al curso {$asignacion['curso_nombre']} {$asignacion['paralelo']} " .
                "en la asignatura {$asignacion['asignatura_nombre']} " .
                "para el período {$asignacion['periodo_lectivo']}"
            );
            
            $mensaje = 'Asignación eliminada correctamente';
        }
    } catch (PDOException $e) {
        $error = 'Error al eliminar la asignación: ' . $e->getMessage();
    }
}

// Procesar formulario de asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $docente_id = $_POST['docente_id'] ?? '';
    $curso_id = $_POST['curso_id'] ?? '';
    $asignatura_id = $_POST['asignatura_id'] ?? '';
    $periodo_lectivo = $_POST['periodo_lectivo'] ?? '';
    
    if (empty($docente_id) || empty($curso_id) || empty($asignatura_id) || empty($periodo_lectivo)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        try {
            // Verificar si ya existe la asignación
            $existe = obtenerFila("
                SELECT id FROM asignaciones_docentes 
                WHERE docente_id = ? AND curso_id = ? AND asignatura_id = ? AND periodo_lectivo = ?
            ", [$docente_id, $curso_id, $asignatura_id, $periodo_lectivo]);
            
            if ($existe) {
                $error = 'Esta asignación ya existe para el período seleccionado';
            } else {
                // Insertar la asignación
                $datos = [
                    'docente_id' => $docente_id,
                    'curso_id' => $curso_id,
                    'asignatura_id' => $asignatura_id,
                    'periodo_lectivo' => $periodo_lectivo
                ];
                
                insertar('asignaciones_docentes', $datos);
                
                // Registrar la actividad
                $docente = obtenerFila("SELECT nombres, apellidos FROM usuarios WHERE id = ?", [$docente_id]);
                $curso = obtenerFila("SELECT nombre, paralelo FROM cursos WHERE id = ?", [$curso_id]);
                $asignatura = obtenerFila("SELECT nombre FROM asignaturas WHERE id = ?", [$asignatura_id]);
                
                registrarActividad(
                    $_SESSION['usuario_id'],
                    'Asignación de Docente',
                    "Se asignó al docente {$docente['nombres']} {$docente['apellidos']} " .
                    "al curso {$curso['nombre']} {$curso['paralelo']} " .
                    "en la asignatura {$asignatura['nombre']} " .
                    "para el período $periodo_lectivo"
                );
                
                $mensaje = 'Docente asignado correctamente';
            }
        } catch (PDOException $e) {
            $error = 'Error al asignar el docente: ' . $e->getMessage();
        }
    }
}

// Obtener lista de docentes
$docentes = obtenerDocentes();

// Obtener lista de cursos
$cursos = obtenerCursos();

// Obtener lista de asignaturas
$asignaturas = obtenerAsignaturas();

// Obtener lista de asignaciones
$asignaciones = obtenerAsignacionesDocentes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de Docentes - Sistema Escolar</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Asignación de Docentes</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2>Nueva Asignación</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="asignacionForm">
                    <div class="form-group">
                        <label for="docente_id">Docente:</label>
                        <select id="docente_id" name="docente_id" required>
                            <option value="">Seleccione un docente</option>
                            <?php foreach ($docentes as $docente): ?>
                                <option value="<?php echo $docente['id']; ?>">
                                    <?php echo htmlspecialchars($docente['apellidos'] . ', ' . $docente['nombres']); ?>
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
                        <label for="asignatura_id">Asignatura:</label>
                        <select id="asignatura_id" name="asignatura_id" required>
                            <option value="">Seleccione una asignatura</option>
                            <?php foreach ($asignaturas as $asignatura): ?>
                                <option value="<?php echo $asignatura['id']; ?>">
                                    <?php echo htmlspecialchars($asignatura['nombre']); ?>
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
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Asignar Docente</button>
                        <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Asignaciones Registradas</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Docente</th>
                                <th>Curso</th>
                                <th>Asignatura</th>
                                <th>Período</th>
                                <th>Fecha Asignación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($asignaciones)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay asignaciones registradas</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($asignaciones as $asignacion): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($asignacion['docente_apellidos'] . ', ' . $asignacion['docente_nombres']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($asignacion['curso_nombre'] . ' ' . $asignacion['paralelo']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($asignacion['asignatura_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($asignacion['periodo_lectivo']); ?></td>
                                        <td><?php echo formatearFecha($asignacion['fecha_asignacion']); ?></td>
                                        <td>
                                            <a href="?eliminar=<?php echo $asignacion['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('¿Está seguro de eliminar esta asignación?')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        </td>
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
        document.getElementById('asignacionForm').reset();
    }
    </script>
</body>
</html> 