<?php
session_start();
require_once 'conexion.php';
require_once 'verificar_sesion.php';

// Verificar que el usuario sea administrador
verificarPermiso('admin');

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
                $required_fields = ['cedula', 'nombres', 'apellidos', 'correo', 'usuario', 'password', 'rol_id'];
                foreach ($required_fields as $field) {
                    if (!isset($data[$field]) || empty($data[$field])) {
                        echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
                        exit;
                    }
                }

                // Verificar si el usuario ya existe
                $stmt = $db->prepare("SELECT id FROM usuarios WHERE usuario = :usuario OR cedula = :cedula OR correo = :correo");
                $stmt->execute([
                    ':usuario' => $data['usuario'],
                    ':cedula' => $data['cedula'],
                    ':correo' => $data['correo']
                ]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'El usuario, cédula o correo ya existe']);
                    exit;
                }

                // Crear nuevo usuario
                $stmt = $db->prepare("INSERT INTO usuarios (cedula, nombres, apellidos, correo, usuario, password, rol_id) 
                                    VALUES (:cedula, :nombres, :apellidos, :correo, :usuario, :password, :rol_id)");
                
                $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
                
                $stmt->execute([
                    ':cedula' => $data['cedula'],
                    ':nombres' => $data['nombres'],
                    ':apellidos' => $data['apellidos'],
                    ':correo' => $data['correo'],
                    ':usuario' => $data['usuario'],
                    ':password' => $password_hash,
                    ':rol_id' => $data['rol_id']
                ]);

                echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
                break;

            case 'update':
                if (!isset($data['id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID de usuario no especificado']);
                    exit;
                }

                // Construir query de actualización
                $updates = [];
                $params = [':id' => $data['id']];

                $fields = ['cedula', 'nombres', 'apellidos', 'correo', 'usuario', 'rol_id'];
                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $updates[] = "$field = :$field";
                        $params[":$field"] = $data[$field];
                    }
                }

                // Si se proporciona una nueva contraseña
                if (isset($data['password']) && !empty($data['password'])) {
                    $updates[] = "password = :password";
                    $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }

                if (empty($updates)) {
                    echo json_encode(['success' => false, 'message' => 'No hay datos para actualizar']);
                    exit;
                }

                $query = "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute($params);

                echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
                break;

            case 'delete':
                if (!isset($data['id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID de usuario no especificado']);
                    exit;
                }

                $stmt = $db->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt->execute([':id' => $data['id']]);

                echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
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