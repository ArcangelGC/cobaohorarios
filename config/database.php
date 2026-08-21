<?php
// config/database.php
// Configuración de la base de datos

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cobaohorarios');

// Función para conectar a la base de datos
function conectarDB() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Error de conexion: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Error en la base de datos: " . $e->getMessage());
    }
}

// Función para ejecutar consultas
function ejecutarConsulta($sql, $params = [], $types = "") {
    $conn = conectarDB();
    
    if (empty($params)) {
        $result = $conn->query($sql);
        $conn->close();
        return $result;
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error en la preparacion: " . $conn->error);
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            $types = str_repeat('s', count($params));
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Función para obtener un solo registro
function obtenerRegistro($sql, $params = [], $types = "") {
    $result = ejecutarConsulta($sql, $params, $types);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Función para obtener múltiples registros
function obtenerRegistros($sql, $params = [], $types = "") {
    $result = ejecutarConsulta($sql, $params, $types);
    $datos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }
    }
    return $datos;
}

// ============================================
// FUNCIÓN CORREGIDA - CON SOPORTE PARA NULL
// ============================================
function ejecutarUpdate($sql, $params = [], $types = "") {
    $conn = conectarDB();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error en la preparacion: " . $conn->error);
    }
    
    // Si hay parámetros, bindearlos
    if (!empty($params)) {
        // Si no se especificaron types, detectar automáticamente
        if (empty($types)) {
            $types = "";
            foreach ($params as $param) {
                if (is_null($param)) {
                    $types .= "s"; // NULL se maneja como string
                } elseif (is_int($param)) {
                    $types .= "i";
                } elseif (is_float($param)) {
                    $types .= "d";
                } else {
                    $types .= "s";
                }
            }
        }
        
        // Bindear parámetros
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    return $result ? $id : false;
}
?>