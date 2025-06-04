<?php
session_start();

function verificarSesion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: index.php');
        exit();
    }
}

function verificarRol($roles_permitidos) {
    if (!in_array($_SESSION['usuario_rol_id'], $roles_permitidos)) {
        header('Location: dashboard.php');
        exit();
    }
}

function obtenerUsuarioActual() {
    return [
        'id' => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'rol_id' => $_SESSION['usuario_rol_id'],
        'rol' => $_SESSION['usuario_rol']
    ];
}

// Función para verificar si el usuario tiene permiso para acceder a una página específica
function verificarPermiso($pagina) {
    $permisos = [
        'admin' => [1], // Solo administrador
        'secretaria' => [1, 3], // Administrador y secretaria
        'docente' => [1, 2], // Administrador y docente
        'rector' => [1, 4], // Administrador y rector
        'vicerrector' => [1, 5], // Administrador y vicerrector
        'inspector' => [1, 6] // Administrador e inspector
    ];

    if (!isset($permisos[$pagina]) || !in_array($_SESSION['usuario_rol_id'], $permisos[$pagina])) {
        header('Location: dashboard.php');
        exit;
    }
}
?> 