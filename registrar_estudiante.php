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
                $required_fields = [
                    'cedula', 'nombres', 'apellidos', 'sexo', 'fecha_nacimiento',
                    'direccion', 'nombre_representante', 'celular_representante',
                    'correo_representante', 'curso_id'
                ];
                
                foreach ($required_fields as $field) {
                    if (!isset($data[$field]) || empty($data[$field])) {
                        echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
                        exit;
                    }
                }

                // Verificar si el estudiante ya existe
                $stmt = $db->prepare("SELECT id FROM estudiantes WHERE cedula = :cedula");
                $stmt->execute([':cedula' => $data['cedula']]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'El estudiante ya está registrado']);
                    exit;
                }

                // Verificar capacidad del curso
                $stmt = $db->prepare("
                    SELECT c.capacidad_maxima, COUNT(m.id) as estudiantes_actuales
                    FROM cursos c
                    LEFT JOIN matriculas m ON c.id = m.curso_id
                    WHERE c.id = :curso_id
                    GROUP BY c.id
                ");
                $stmt->execute([':curso_id' => $data['curso_id']]);
                $curso = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($curso['estudiantes_actuales'] >= $curso['capacidad_maxima']) {
                    echo json_encode(['success' => false, 'message' => 'El curso ha alcanzado su capacidad máxima']);
                    exit;
                }

                // Iniciar transacción
                $db->beginTransaction();

                try {
                    // Insertar estudiante
                    $stmt = $db->prepare("
                        INSERT INTO estudiantes (
                            cedula, nombres, apellidos, sexo, fecha_nacimiento,
                            direccion, nombre_representante, celular_representante,
                            correo_representante
                        ) VALUES (
                            :cedula, :nombres, :apellidos, :sexo, :fecha_nacimiento,
                            :direccion, :nombre_representante, :celular_representante,
                            :correo_representante
                        )
                    ");

                    $stmt->execute([
                        ':cedula' => $data['cedula'],
                        ':nombres' => $data['nombres'],
                        ':apellidos' => $data['apellidos'],
                        ':sexo' => $data['sexo'],
                        ':fecha_nacimiento' => $data['fecha_nacimiento'],
                        ':direccion' => $data['direccion'],
                        ':nombre_representante' => $data['nombre_representante'],
                        ':celular_representante' => $data['celular_representante'],
                        ':correo_representante' => $data['correo_representante']
                    ]);

                    $estudiante_id = $db->lastInsertId();

                    // Registrar matrícula
                    $stmt = $db->prepare("
                        INSERT INTO matriculas (
                            estudiante_id, curso_id, periodo_lectivo
                        ) VALUES (
                            :estudiante_id, :curso_id, :periodo_lectivo
                        )
                    ");

                    $stmt->execute([
                        ':estudiante_id' => $estudiante_id,
                        ':curso_id' => $data['curso_id'],
                        ':periodo_lectivo' => date('Y') . '-' . (date('Y') + 1)
                    ]);

                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Estudiante registrado exitosamente']);
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                break;

            case 'update':
                if (!isset($data['id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID de estudiante no especificado']);
                    exit;
                }

                // Construir query de actualización
                $updates = [];
                $params = [':id' => $data['id']];

                $fields = [
                    'cedula', 'nombres', 'apellidos', 'sexo', 'fecha_nacimiento',
                    'direccion', 'nombre_representante', 'celular_representante',
                    'correo_representante'
                ];

                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $updates[] = "$field = :$field";
                        $params[":$field"] = $data[$field];
                    }
                }

                if (empty($updates)) {
                    echo json_encode(['success' => false, 'message' => 'No hay datos para actualizar']);
                    exit;
                }

                $query = "UPDATE estudiantes SET " . implode(', ', $updates) . " WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute($params);

                echo json_encode(['success' => true, 'message' => 'Estudiante actualizado exitosamente']);
                break;

            case 'delete':
                if (!isset($data['id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID de estudiante no especificado']);
                    exit;
                }

                // Iniciar transacción
                $db->beginTransaction();

                try {
                    // Eliminar matrículas
                    $stmt = $db->prepare("DELETE FROM matriculas WHERE estudiante_id = :id");
                    $stmt->execute([':id' => $data['id']]);

                    // Eliminar estudiante
                    $stmt = $db->prepare("DELETE FROM estudiantes WHERE id = :id");
                    $stmt->execute([':id' => $data['id']]);

                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Estudiante eliminado exitosamente']);
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
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