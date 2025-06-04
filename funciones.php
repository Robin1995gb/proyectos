<?php
require_once 'conexion.php';

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validarNumero($numero, $min = null, $max = null) {
    if (!is_numeric($numero)) {
        return false;
    }
    if ($min !== null && $numero < $min) {
        return false;
    }
    if ($max !== null && $numero > $max) {
        return false;
    }
    return true;
}

function validarFecha($fecha) {
    $fecha = str_replace('/', '-', $fecha);
    return date('Y-m-d', strtotime($fecha)) === $fecha;
}

function formatearFecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

function formatearNumero($numero, $decimales = 2) {
    return number_format($numero, $decimales, '.', ',');
}

function generarPassword($longitud = 8) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $longitud; $i++) {
        $password .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $password;
}

function encriptarPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verificarPassword($password, $hash) {
    return password_verify($password, $hash);
}

function obtenerRoles() {
    return obtenerFilas("SELECT * FROM roles ORDER BY nombre");
}

function obtenerNivelesEducativos() {
    return obtenerFilas("SELECT * FROM niveles_educativos ORDER BY nombre");
}

function obtenerCursos() {
    return obtenerFilas("
        SELECT c.*, n.nombre as nivel_nombre 
        FROM cursos c 
        JOIN niveles_educativos n ON c.nivel_id = n.id 
        ORDER BY c.nombre, c.paralelo
    ");
}

function obtenerAsignaturas() {
    return obtenerFilas("SELECT * FROM asignaturas ORDER BY nombre");
}

function obtenerDocentes() {
    return obtenerFilas("
        SELECT u.id, u.nombres, u.apellidos 
        FROM usuarios u 
        JOIN roles r ON u.rol_id = r.id 
        WHERE r.nombre = 'Docente' 
        ORDER BY u.apellidos, u.nombres
    ");
}

function obtenerEstudiantes() {
    return obtenerFilas("
        SELECT e.*, m.curso_id, c.nombre as curso_nombre, c.paralelo
        FROM estudiantes e
        LEFT JOIN matriculas m ON e.id = m.estudiante_id
        LEFT JOIN cursos c ON m.curso_id = c.id
        ORDER BY e.apellidos, e.nombres
    ");
}

function obtenerMatriculas() {
    return obtenerFilas("
        SELECT m.*, 
               e.nombres as estudiante_nombres,
               e.apellidos as estudiante_apellidos,
               c.nombre as curso_nombre,
               c.paralelo
        FROM matriculas m
        JOIN estudiantes e ON m.estudiante_id = e.id
        JOIN cursos c ON m.curso_id = c.id
        ORDER BY m.fecha_matricula DESC
    ");
}

function obtenerCalificaciones($estudiante_id, $periodo_lectivo) {
    return obtenerFilas("
        SELECT c.*, a.nombre as asignatura_nombre,
               u.nombres as docente_nombres,
               u.apellidos as docente_apellidos
        FROM calificaciones c
        JOIN asignaturas a ON c.asignatura_id = a.id
        JOIN usuarios u ON c.docente_id = u.id
        WHERE c.estudiante_id = ? AND c.periodo_lectivo = ?
        ORDER BY a.nombre
    ", [$estudiante_id, $periodo_lectivo]);
}

function obtenerCalificacionesDocente($docente_id, $periodo_lectivo) {
    return obtenerFilas("
        SELECT c.*, 
               e.nombres as estudiante_nombres,
               e.apellidos as estudiante_apellidos,
               cur.nombre as curso_nombre,
               cur.paralelo
        FROM calificaciones c
        JOIN estudiantes e ON c.estudiante_id = e.id
        JOIN cursos cur ON c.curso_id = cur.id
        WHERE c.docente_id = ? AND c.periodo_lectivo = ?
        ORDER BY e.apellidos, e.nombres
    ", [$docente_id, $periodo_lectivo]);
}

function obtenerAsignaciones($docente_id, $periodo_lectivo) {
    return obtenerFilas("
        SELECT ad.*, 
               c.nombre as curso_nombre,
               c.paralelo,
               a.nombre as asignatura_nombre
        FROM asignacion_docentes ad
        JOIN cursos c ON ad.curso_id = c.id
        JOIN asignaturas a ON ad.asignatura_id = a.id
        WHERE ad.docente_id = ? AND ad.periodo_lectivo = ?
        ORDER BY c.nombre, c.paralelo, a.nombre
    ", [$docente_id, $periodo_lectivo]);
}

function obtenerAsignacionesDocentes() {
    return obtenerFilas("
        SELECT a.id, a.periodo_lectivo, a.fecha_asignacion,
               u.nombres as docente_nombres, u.apellidos as docente_apellidos,
               c.nombre as curso_nombre, c.paralelo, 
               n.nombre as nivel_nombre,
               asig.nombre as asignatura_nombre
        FROM asignaciones_docentes a
        JOIN usuarios u ON a.docente_id = u.id
        JOIN cursos c ON a.curso_id = c.id
        JOIN niveles_educativos n ON c.nivel_id = n.id
        JOIN asignaturas asig ON a.asignatura_id = asig.id
        ORDER BY a.fecha_asignacion DESC
    ");
}

function validarAcceso($rol_id, $pagina) {
    $accesos = [
        1 => ['dashboard', 'usuarios', 'matricula', 'asignacion_docentes', 'calificaciones', 'reportes'],
        2 => ['dashboard', 'calificaciones'],
        3 => ['dashboard', 'matricula', 'asignacion_docentes'],
        4 => ['dashboard', 'reportes'],
        5 => ['dashboard', 'reportes']
    ];
    
    return isset($accesos[$rol_id]) && in_array($pagina, $accesos[$rol_id]);
}

function registrarActividad($usuario_id, $accion, $detalles = '') {
    $datos = [
        'usuario_id' => $usuario_id,
        'accion' => $accion,
        'detalles' => $detalles,
        'fecha' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR']
    ];
    
    return insertar('actividades', $datos);
}

function obtenerActividades($usuario_id = null, $limite = 10) {
    $sql = "
        SELECT a.*, u.nombres, u.apellidos
        FROM actividades a
        JOIN usuarios u ON a.usuario_id = u.id
    ";
    
    $params = [];
    if ($usuario_id) {
        $sql .= " WHERE a.usuario_id = ?";
        $params[] = $usuario_id;
    }
    
    $sql .= " ORDER BY a.fecha DESC LIMIT " . intval($limite);
    
    return obtenerFilas($sql, $params);
}

function generarReporte($tipo, $filtros = []) {
    switch ($tipo) {
        case 'matriculas':
            return generarReporteMatriculas($filtros);
        case 'calificaciones':
            return generarReporteCalificaciones($filtros);
        case 'asignaciones':
            return generarReporteAsignaciones($filtros);
        default:
            throw new Exception("Tipo de reporte no válido");
    }
}

function generarReporteMatriculas($filtros) {
    $sql = "
        SELECT m.*, 
               e.nombres as estudiante_nombres,
               e.apellidos as estudiante_apellidos,
               c.nombre as curso_nombre,
               c.paralelo
        FROM matriculas m
        JOIN estudiantes e ON m.estudiante_id = e.id
        JOIN cursos c ON m.curso_id = c.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($filtros['periodo_lectivo'])) {
        $sql .= " AND m.periodo_lectivo = ?";
        $params[] = $filtros['periodo_lectivo'];
    }
    
    if (!empty($filtros['curso_id'])) {
        $sql .= " AND m.curso_id = ?";
        $params[] = $filtros['curso_id'];
    }
    
    $sql .= " ORDER BY e.apellidos, e.nombres";
    
    return obtenerFilas($sql, $params);
}

function generarReporteCalificaciones($filtros) {
    $sql = "
        SELECT c.*, 
               e.nombres as estudiante_nombres,
               e.apellidos as estudiante_apellidos,
               a.nombre as asignatura_nombre,
               u.nombres as docente_nombres,
               u.apellidos as docente_apellidos
        FROM calificaciones c
        JOIN estudiantes e ON c.estudiante_id = e.id
        JOIN asignaturas a ON c.asignatura_id = a.id
        JOIN usuarios u ON c.docente_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($filtros['periodo_lectivo'])) {
        $sql .= " AND c.periodo_lectivo = ?";
        $params[] = $filtros['periodo_lectivo'];
    }
    
    if (!empty($filtros['asignatura_id'])) {
        $sql .= " AND c.asignatura_id = ?";
        $params[] = $filtros['asignatura_id'];
    }
    
    if (!empty($filtros['estudiante_id'])) {
        $sql .= " AND c.estudiante_id = ?";
        $params[] = $filtros['estudiante_id'];
    }
    
    $sql .= " ORDER BY e.apellidos, e.nombres, a.nombre";
    
    return obtenerFilas($sql, $params);
}

function generarReporteAsignaciones($filtros) {
    $sql = "
        SELECT ad.*, 
               u.nombres as docente_nombres,
               u.apellidos as docente_apellidos,
               c.nombre as curso_nombre,
               c.paralelo,
               a.nombre as asignatura_nombre
        FROM asignacion_docentes ad
        JOIN usuarios u ON ad.docente_id = u.id
        JOIN cursos c ON ad.curso_id = c.id
        JOIN asignaturas a ON ad.asignatura_id = a.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($filtros['periodo_lectivo'])) {
        $sql .= " AND ad.periodo_lectivo = ?";
        $params[] = $filtros['periodo_lectivo'];
    }
    
    if (!empty($filtros['docente_id'])) {
        $sql .= " AND ad.docente_id = ?";
        $params[] = $filtros['docente_id'];
    }
    
    if (!empty($filtros['curso_id'])) {
        $sql .= " AND ad.curso_id = ?";
        $params[] = $filtros['curso_id'];
    }
    
    $sql .= " ORDER BY u.apellidos, u.nombres, c.nombre, c.paralelo";
    
    return obtenerFilas($sql, $params);
}
?> 