<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['usuario']) || !isset($data['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario y contraseña son requeridos'
        ]);
        exit;
    }

    $conexion = new Conexion();
    $db = $conexion->getConnection();

    try {
        $query = "SELECT u.*, r.nombre as rol_nombre 
                 FROM usuarios u 
                 JOIN roles r ON u.rol_id = r.id 
                 WHERE u.usuario = :usuario";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario', $data['usuario']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($data['password'], $usuario['password'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombres'] . ' ' . $usuario['apellidos'];
                $_SESSION['usuario_rol'] = $usuario['rol_nombre'];
                $_SESSION['usuario_rol_id'] = $usuario['rol_id'];

                echo json_encode([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'redirect' => 'dashboard.php'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Contraseña incorrecta'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ]);
        }
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error en el servidor: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?> 