<?php
require_once 'php/conexion.php';
require_once 'php/funciones.php';
require_once 'php/verificar_sesion.php';

// Verificar que el usuario tenga permiso de secretaria o administrador
if (!in_array($_SESSION['usuario_rol_id'], [1, 3])) {
    header('Location: dashboard.php');
    exit;
}

$mensaje = '';
$error = '';

// Procesar formulario de nuevo estudiante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'nuevo_estudiante') {
    $cedula = $_POST['cedula'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $nombre_representante = $_POST['nombre_representante'] ?? '';
    $celular_representante = $_POST['celular_representante'] ?? '';
    $correo_representante = $_POST['correo_representante'] ?? '';
    
    if (empty($cedula) || empty($nombres) || empty($apellidos) || empty($sexo) || 
        empty($fecha_nacimiento) || empty($direccion) || empty($nombre_representante) || 
        empty($celular_representante) || empty($correo_representante)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        try {
            // Verificar si la cédula ya existe
            $existe = obtenerFila("SELECT id FROM estudiantes WHERE cedula = ?", [$cedula]);
            
            if ($existe) {
                $error = 'Ya existe un estudiante con esta cédula';
            } else {
                // Insertar nuevo estudiante
                $datos = [
                    'cedula' => $cedula,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'sexo' => $sexo,
                    'fecha_nacimiento' => $fecha_nacimiento,
                    'direccion' => $direccion,
                    'nombre_representante' => $nombre_representante,
                    'celular_representante' => $celular_representante,
                    'correo_representante' => $correo_representante
                ];
                
                insertar('estudiantes', $datos);
                
                // Registrar la actividad
                registrarActividad(
                    $_SESSION['usuario_id'],
                    'Nuevo Estudiante',
                    "Registro de estudiante: $nombres $apellidos"
                );
                
                $mensaje = 'Estudiante registrado correctamente';
            }
        } catch (PDOException $e) {
            $error = 'Error al registrar el estudiante: ' . $e->getMessage();
        }
    }
}

// Procesar eliminación de matrícula
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    try {
        // Obtener información de la matrícula antes de eliminarla
        $matricula = obtenerFila("
            SELECT estudiante_id, curso_id 
            FROM matriculas 
            WHERE id = ?
        ", [$id]);
        
        if ($matricula) {
            eliminar('matriculas', 'id = ?', [$id]);
            
            // Registrar la actividad
            registrarActividad(
                $_SESSION['usuario_id'],
                'Eliminación de Matrícula',
                "Eliminación de matrícula ID: $id (Estudiante ID: {$matricula['estudiante_id']}, Curso ID: {$matricula['curso_id']})"
            );
            
            $mensaje = 'Matrícula eliminada correctamente';
        } else {
            $error = 'La matrícula no existe';
        }
    } catch (PDOException $e) {
        $error = 'Error al eliminar la matrícula: ' . $e->getMessage();
    }
}

// Procesar formulario de matrícula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'nueva_matricula') {
    $estudiante_id = $_POST['estudiante_id'] ?? null;
    $curso_id = $_POST['curso_id'] ?? '';
    $periodo_lectivo = $_POST['periodo_lectivo'] ?? '';
    
    if (empty($estudiante_id) || empty($curso_id) || empty($periodo_lectivo)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        try {
            // Verificar si el estudiante ya está matriculado en el mismo curso y período
            $existe = obtenerFila("
                SELECT id FROM matriculas 
                WHERE estudiante_id = ? AND curso_id = ? AND periodo_lectivo = ?
            ", [$estudiante_id, $curso_id, $periodo_lectivo]);
            
            if ($existe) {
                $error = 'El estudiante ya está matriculado en este curso para el período seleccionado';
            } else {
                // Verificar capacidad del curso
                $curso = obtenerFila("
                    SELECT c.capacidad_maxima, COUNT(m.id) as matriculados
                    FROM cursos c
                    LEFT JOIN matriculas m ON c.id = m.curso_id AND m.periodo_lectivo = ?
                    WHERE c.id = ?
                    GROUP BY c.id
                ", [$periodo_lectivo, $curso_id]);
                
                if ($curso['matriculados'] >= $curso['capacidad_maxima']) {
                    $error = 'El curso ha alcanzado su capacidad máxima';
                } else {
                    // Realizar la matrícula
                    $datos = [
                        'estudiante_id' => $estudiante_id,
                        'curso_id' => $curso_id,
                        'periodo_lectivo' => $periodo_lectivo
                    ];
                    
                    insertar('matriculas', $datos);
                    
                    // Registrar la actividad
                    registrarActividad(
                        $_SESSION['usuario_id'],
                        'Matrícula',
                        "Matrícula de estudiante ID: $estudiante_id en curso ID: $curso_id"
                    );
                    
                    $mensaje = 'Matrícula realizada correctamente';
                }
            }
        } catch (PDOException $e) {
            $error = 'Error al realizar la matrícula: ' . $e->getMessage();
        }
    }
}

// Obtener lista de estudiantes
$estudiantes = obtenerEstudiantes();

// Obtener lista de cursos
$cursos = obtenerCursos();

// Obtener lista de matrículas
$matriculas = obtenerMatriculas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrícula - Sistema Escolar</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Gestión de Matrículas</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2>Nuevo Estudiante</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="estudianteForm">
                    <input type="hidden" name="accion" value="nuevo_estudiante">
                    
                    <div class="form-group">
                        <label for="cedula">Cédula:</label>
                        <input type="text" id="cedula" name="cedula" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombres">Nombres:</label>
                        <input type="text" id="nombres" name="nombres" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="apellidos">Apellidos:</label>
                        <input type="text" id="apellidos" name="apellidos" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sexo">Sexo:</label>
                        <select id="sexo" name="sexo" required>
                            <option value="">Seleccione...</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="direccion">Dirección:</label>
                        <textarea id="direccion" name="direccion" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre_representante">Nombres y Apellidos del Representante:</label>
                        <input type="text" id="nombre_representante" name="nombre_representante" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="celular_representante">Celular del Representante:</label>
                        <input type="tel" id="celular_representante" name="celular_representante" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="correo_representante">Correo Electrónico del Representante:</label>
                        <input type="email" id="correo_representante" name="correo_representante" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Registrar Estudiante</button>
                        <button type="button" class="btn btn-secondary" onclick="limpiarFormularioEstudiante()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Nueva Matrícula</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="matriculaForm">
                    <input type="hidden" name="accion" value="nueva_matricula">
                    
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
                               placeholder="Ej: 2023-2024" required 
                               pattern="\d{4}-\d{4}" title="Formato: AAAA-AAAA">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Registrar Matrícula</button>
                        <button type="button" class="btn btn-secondary" onclick="limpiarFormularioMatricula()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Matrículas Registradas</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Período</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matriculas as $matricula): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($matricula['estudiante_apellidos'] . ', ' . $matricula['estudiante_nombres']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($matricula['curso_nombre'] . ' ' . $matricula['paralelo']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($matricula['periodo_lectivo']); ?></td>
                                    <td><?php echo formatearFecha($matricula['fecha_matricula']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="eliminarMatricula(<?php echo $matricula['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function limpiarFormularioEstudiante() {
        document.getElementById('estudianteForm').reset();
    }
    
    function limpiarFormularioMatricula() {
        document.getElementById('matriculaForm').reset();
    }
    
    function eliminarMatricula(id) {
        if (confirm('¿Está seguro de eliminar esta matrícula?')) {
            window.location.href = 'matricula.php?eliminar=' + id;
        }
    }
    </script>
</body>
</html> 