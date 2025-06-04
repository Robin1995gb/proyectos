<?php
session_start();
require_once 'conexion.php';
require_once 'verificar_sesion.php';

// Verificar que el usuario sea administrador o secretaria
verificarPermiso('secretaria');

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
                // Validar datos requeridos
                $required_fields = ['docente_id', 'curso_id', 'asignatura_id'];
                foreach ($required_fields as $field) {
                    if (!isset($data[$field]) || empty($data[$field])) {
                        echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
                        exit;
                    }
                }

                // Verificar si el docente ya está asignado a esta asignatura en el curso
                $stmt = $db->prepare("
                    SELECT id FROM asignacion_docentes 
                    WHERE docente_id = :docente_id 
                    AND curso_id = :curso_id 
                    AND asignatura_id = :asignatura_id
                    AND periodo_lectivo = :periodo_lectivo
                ");
                
                $periodo_lectivo = date('Y') . '-' . (date('Y') + 1);
                
                $stmt->execute([
                    ':docente_id' => $data['docente_id'],
                    ':curso_id' => $data['curso_id'],
                    ':asignatura_id' => $data['asignatura_id'],
                    ':periodo_lectivo' => $periodo_lectivo
                ]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'El docente ya está asignado a esta asignatura en el curso']);
                    exit;
                }

                // Insertar asignación
                $stmt = $db->prepare("
                    INSERT INTO asignacion_docentes (
                        docente_id, curso_id, asignatura_id, periodo_lectivo
                    ) VALUES (
                        :docente_id, :curso_id, :asignatura_id, :periodo_lectivo
                    )
                ");

                $stmt->execute([
                    ':docente_id' => $data['docente_id'],
                    ':curso_id' => $data['curso_id'],
                    ':asignatura_id' => $data['asignatura_id'],
                    ':periodo_lectivo' => $periodo_lectivo
                ]);

                echo json_encode(['success' => true, 'message' => 'Docente asignado exitosamente']);
                break;

            case 'delete':
                if (!isset($data['id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID de asignación no especificado']);
                    exit;
                }

                $stmt = $db->prepare("DELETE FROM asignacion_docentes WHERE id = :id");
                $stmt->execute([':id' => $data['id']]);

                echo json_encode(['success' => true, 'message' => 'Asignación eliminada exitosamente']);
                break;

            case 'list':
                if (!isset($data['curso_id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID de curso es requerido']);
                    exit;
                }

                $stmt = $db->prepare("
                    SELECT ad.*, 
                           u.nombres as docente_nombres, 
                           u.apellidos as docente_apellidos,
                           a.nombre as asignatura_nombre
                    FROM asignacion_docentes ad
                    JOIN usuarios u ON ad.docente_id = u.id
                    JOIN asignaturas a ON ad.asignatura_id = a.id
                    WHERE ad.curso_id = :curso_id
                    AND ad.periodo_lectivo = :periodo_lectivo
                    ORDER BY a.nombre
                ");

                $periodo_lectivo = date('Y') . '-' . (date('Y') + 1);
                
                $stmt->execute([
                    ':curso_id' => $data['curso_id'],
                    ':periodo_lectivo' => $periodo_lectivo
                ]);

                $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $asignaciones]);
                break;

            case 'get_docentes':
                // Obtener lista de docentes
                $stmt = $db->prepare("
                    SELECT u.id, u.nombres, u.apellidos, u.cedula
                    FROM usuarios u
                    JOIN roles r ON u.rol_id = r.id
                    WHERE r.nombre = 'Docente'
                    ORDER BY u.apellidos, u.nombres
                ");
                $stmt->execute();
                $docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $docentes]);
                break;

            case 'get_asignaturas':
                // Obtener lista de asignaturas
                $stmt = $db->prepare("SELECT id, nombre FROM asignaturas ORDER BY nombre");
                $stmt->execute();
                $asignaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $asignaturas]);
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