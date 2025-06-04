<?php
session_start();
require_once 'conexion.php';
require_once 'verificar_sesion.php';

// Verificar que el usuario sea docente
verificarPermiso('docente');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
        exit;
    }

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        switch ($data['action']) {
            case 'create':
            case 'update':
                // Validar datos requeridos
                $required_fields = [
                    'estudiante_id', 'asignatura_id', 'tareas', 'conducta',
                    'evaluaciones', 'examen'
                ];
                
                foreach ($required_fields as $field) {
                    if (!isset($data[$field]) || !is_numeric($data[$field])) {
                        echo json_encode(['success' => false, 'message' => "El campo $field es requerido y debe ser numérico"]);
                        exit;
                    }
                }

                // Validar rangos de calificaciones
                $campos_calificacion = ['tareas', 'conducta', 'evaluaciones', 'examen'];
                foreach ($campos_calificacion as $campo) {
                    if ($data[$campo] < 0 || $data[$campo] > 10) {
                        echo json_encode(['success' => false, 'message' => "La calificación de $campo debe estar entre 0 y 10"]);
                        exit;
                    }
                }

                // Calcular promedio
                $promedio = ($data['tareas'] + $data['conducta'] + $data['evaluaciones'] + $data['examen']) / 4;
                $promedio = round($promedio, 2);

                if ($data['action'] === 'create') {
                    // Verificar si ya existe una calificación para este estudiante y asignatura
                    $stmt = $db->prepare("
                        SELECT id FROM calificaciones 
                        WHERE estudiante_id = :estudiante_id 
                        AND asignatura_id = :asignatura_id 
                        AND periodo_lectivo = :periodo_lectivo
                    ");
                    
                    $periodo_lectivo = date('Y') . '-' . (date('Y') + 1);
                    
                    $stmt->execute([
                        ':estudiante_id' => $data['estudiante_id'],
                        ':asignatura_id' => $data['asignatura_id'],
                        ':periodo_lectivo' => $periodo_lectivo
                    ]);

                    if ($stmt->rowCount() > 0) {
                        echo json_encode(['success' => false, 'message' => 'Ya existe una calificación para este estudiante en esta asignatura']);
                        exit;
                    }

                    // Insertar nueva calificación
                    $stmt = $db->prepare("
                        INSERT INTO calificaciones (
                            estudiante_id, asignatura_id, docente_id, periodo_lectivo,
                            tareas, conducta, evaluaciones, examen, promedio
                        ) VALUES (
                            :estudiante_id, :asignatura_id, :docente_id, :periodo_lectivo,
                            :tareas, :conducta, :evaluaciones, :examen, :promedio
                        )
                    ");

                    $stmt->execute([
                        ':estudiante_id' => $data['estudiante_id'],
                        ':asignatura_id' => $data['asignatura_id'],
                        ':docente_id' => $_SESSION['usuario_id'],
                        ':periodo_lectivo' => $periodo_lectivo,
                        ':tareas' => $data['tareas'],
                        ':conducta' => $data['conducta'],
                        ':evaluaciones' => $data['evaluaciones'],
                        ':examen' => $data['examen'],
                        ':promedio' => $promedio
                    ]);

                    echo json_encode(['success' => true, 'message' => 'Calificación registrada exitosamente']);
                } else {
                    // Actualizar calificación existente
                    if (!isset($data['id'])) {
                        echo json_encode(['success' => false, 'message' => 'ID de calificación no especificado']);
                        exit;
                    }

                    $stmt = $db->prepare("
                        UPDATE calificaciones SET
                            tareas = :tareas,
                            conducta = :conducta,
                            evaluaciones = :evaluaciones,
                            examen = :examen,
                            promedio = :promedio
                        WHERE id = :id AND docente_id = :docente_id
                    ");

                    $stmt->execute([
                        ':tareas' => $data['tareas'],
                        ':conducta' => $data['conducta'],
                        ':evaluaciones' => $data['evaluaciones'],
                        ':examen' => $data['examen'],
                        ':promedio' => $promedio,
                        ':id' => $data['id'],
                        ':docente_id' => $_SESSION['usuario_id']
                    ]);

                    if ($stmt->rowCount() === 0) {
                        echo json_encode(['success' => false, 'message' => 'No se encontró la calificación o no tiene permiso para modificarla']);
                        exit;
                    }

                    echo json_encode(['success' => true, 'message' => 'Calificación actualizada exitosamente']);
                }
                break;

            case 'get':
                if (!isset($data['estudiante_id']) || !isset($data['asignatura_id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID de estudiante y asignatura son requeridos']);
                    exit;
                }

                $stmt = $db->prepare("
                    SELECT c.*, e.nombres as estudiante_nombres, e.apellidos as estudiante_apellidos,
                           a.nombre as asignatura_nombre
                    FROM calificaciones c
                    JOIN estudiantes e ON c.estudiante_id = e.id
                    JOIN asignaturas a ON c.asignatura_id = a.id
                    WHERE c.estudiante_id = :estudiante_id 
                    AND c.asignatura_id = :asignatura_id
                    AND c.periodo_lectivo = :periodo_lectivo
                ");

                $periodo_lectivo = date('Y') . '-' . (date('Y') + 1);
                
                $stmt->execute([
                    ':estudiante_id' => $data['estudiante_id'],
                    ':asignatura_id' => $data['asignatura_id'],
                    ':periodo_lectivo' => $periodo_lectivo
                ]);

                $calificacion = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($calificacion) {
                    echo json_encode(['success' => true, 'data' => $calificacion]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se encontró la calificación']);
                }
                break;

            case 'list':
                if (!isset($data['curso_id']) || !isset($data['asignatura_id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID de curso y asignatura son requeridos']);
                    exit;
                }

                $stmt = $db->prepare("
                    SELECT c.*, e.nombres as estudiante_nombres, e.apellidos as estudiante_apellidos,
                           e.cedula as estudiante_cedula
                    FROM calificaciones c
                    JOIN estudiantes e ON c.estudiante_id = e.id
                    JOIN matriculas m ON e.id = m.estudiante_id
                    WHERE m.curso_id = :curso_id 
                    AND c.asignatura_id = :asignatura_id
                    AND c.periodo_lectivo = :periodo_lectivo
                    ORDER BY e.apellidos, e.nombres
                ");

                $periodo_lectivo = date('Y') . '-' . (date('Y') + 1);
                
                $stmt->execute([
                    ':curso_id' => $data['curso_id'],
                    ':asignatura_id' => $data['asignatura_id'],
                    ':periodo_lectivo' => $periodo_lectivo
                ]);

                $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $calificaciones]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
                break;
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?> 