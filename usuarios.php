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

// Procesar formulario de creación/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $cedula = $_POST['cedula'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    $rol_id = $_POST['rol_id'] ?? '';
    
    if (empty($cedula) || empty($nombres) || empty($apellidos) || empty($correo) || empty($usuario) || empty($rol_id)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        try {
            if ($id) { // Actualizar usuario existente
                $datos = [
                    'cedula' => $cedula,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'correo' => $correo,
                    'usuario' => $usuario,
                    'rol_id' => $rol_id
                ];
                
                // Si se proporcionó una nueva contraseña, actualizarla
                if (!empty($password)) {
                    $datos['password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                
                actualizar('usuarios', $datos, 'id = ?', [$id]);
                $mensaje = 'Usuario actualizado correctamente';
            } else { // Crear nuevo usuario
                if (empty($password)) {
                    $error = 'La contraseña es obligatoria para nuevos usuarios';
                } else {
                    $datos = [
                        'cedula' => $cedula,
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'correo' => $correo,
                        'usuario' => $usuario,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'rol_id' => $rol_id
                    ];
                    
                    insertar('usuarios', $datos);
                    $mensaje = 'Usuario creado correctamente';
                }
            }
        } catch (PDOException $e) {
            $error = 'Error al guardar el usuario: ' . $e->getMessage();
        }
    }
}

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    try {
        // Verificar que no sea el último administrador
        $admin_count = obtenerFila("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 1");
        $usuario = obtenerFila("SELECT rol_id FROM usuarios WHERE id = ?", [$id]);
        
        if ($admin_count['total'] <= 1 && $usuario['rol_id'] == 1) {
            $error = 'No se puede eliminar el último administrador';
        } else {
            eliminar('usuarios', 'id = ?', [$id]);
            $mensaje = 'Usuario eliminado correctamente';
        }
    } catch (PDOException $e) {
        $error = 'Error al eliminar el usuario: ' . $e->getMessage();
    }
}

// Obtener lista de usuarios
$usuarios = obtenerFilas("
    SELECT u.*, r.nombre as rol_nombre 
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id 
    ORDER BY u.apellidos, u.nombres
");

// Obtener roles para el formulario
$roles = obtenerRoles();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema Escolar</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Gestión de Usuarios</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2>Nuevo Usuario</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="usuarioForm">
                    <input type="hidden" name="id" id="id">
                    
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
                        <label for="correo">Correo Electrónico:</label>
                        <input type="email" id="correo" name="correo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="usuario">Usuario:</label>
                        <input type="text" id="usuario" name="usuario" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password">
                        <small>Dejar en blanco para mantener la contraseña actual (solo en edición)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="rol_id">Rol:</label>
                        <select id="rol_id" name="rol_id" required>
                            <option value="">Seleccione un rol</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['id']; ?>">
                                    <?php echo htmlspecialchars($rol['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Lista de Usuarios</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>Correo</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nombres']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['rol_nombre']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="eliminarUsuario(<?php echo $usuario['id']; ?>)">
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
    function editarUsuario(usuario) {
        document.getElementById('id').value = usuario.id;
        document.getElementById('cedula').value = usuario.cedula;
        document.getElementById('nombres').value = usuario.nombres;
        document.getElementById('apellidos').value = usuario.apellidos;
        document.getElementById('correo').value = usuario.correo;
        document.getElementById('usuario').value = usuario.usuario;
        document.getElementById('password').value = '';
        document.getElementById('rol_id').value = usuario.rol_id;
        
        // Scroll al formulario
        document.getElementById('usuarioForm').scrollIntoView({ behavior: 'smooth' });
    }
    
    function limpiarFormulario() {
        document.getElementById('usuarioForm').reset();
        document.getElementById('id').value = '';
    }
    
    function eliminarUsuario(id) {
        if (confirm('¿Está seguro de eliminar este usuario?')) {
            window.location.href = 'usuarios.php?eliminar=' + id;
        }
    }
    </script>
</body>
</html> 