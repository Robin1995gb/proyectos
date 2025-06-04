<?php
$host = 'localhost';
$dbname = 'sistema_escolar';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

function ejecutarConsulta($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        die("Error en la consulta: " . $e->getMessage());
    }
}

function obtenerFila($sql, $params = []) {
    $stmt = ejecutarConsulta($sql, $params);
    return $stmt->fetch();
}

function obtenerFilas($sql, $params = []) {
    $stmt = ejecutarConsulta($sql, $params);
    return $stmt->fetchAll();
}

function insertar($tabla, $datos) {
    global $pdo;
    try {
        $campos = implode(', ', array_keys($datos));
        $valores = implode(', ', array_fill(0, count($datos), '?'));
        $sql = "INSERT INTO $tabla ($campos) VALUES ($valores)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($datos));
        return $pdo->lastInsertId();
    } catch(PDOException $e) {
        die("Error al insertar: " . $e->getMessage());
    }
}

function actualizar($tabla, $datos, $where, $whereParams = []) {
    global $pdo;
    try {
        $campos = implode(' = ?, ', array_keys($datos)) . ' = ?';
        $sql = "UPDATE $tabla SET $campos WHERE $where";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($datos), $whereParams));
        return $stmt->rowCount();
    } catch(PDOException $e) {
        die("Error al actualizar: " . $e->getMessage());
    }
}

function eliminar($tabla, $where, $params = []) {
    global $pdo;
    try {
        $sql = "DELETE FROM $tabla WHERE $where";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch(PDOException $e) {
        die("Error al eliminar: " . $e->getMessage());
    }
}
?> 