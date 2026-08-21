<?php
// insertar_maestros_salones_corregido.php - Datos de prueba (VERSIÓN CORREGIDA)
require_once 'config/database.php';

echo "<h1>📚 Insertando Maestros y Salones de Prueba</h1>";
echo "<hr>";

// ========== MAESTROS ==========
$maestros = [
    ['nombre' => 'María', 'apellido_paterno' => 'García', 'apellido_materno' => 'López', 'email' => 'maria.garcia@cobao.edu.mx', 'especialidad' => 'Matemáticas'],
    ['nombre' => 'Juan', 'apellido_paterno' => 'Pérez', 'apellido_materno' => 'Sánchez', 'email' => 'juan.perez@cobao.edu.mx', 'especialidad' => 'Física'],
    ['nombre' => 'Ana', 'apellido_paterno' => 'Martínez', 'apellido_materno' => 'Ramírez', 'email' => 'ana.martinez@cobao.edu.mx', 'especialidad' => 'Química'],
    ['nombre' => 'Carlos', 'apellido_paterno' => 'González', 'apellido_materno' => 'Torres', 'email' => 'carlos.gonzalez@cobao.edu.mx', 'especialidad' => 'Biología'],
    ['nombre' => 'Laura', 'apellido_paterno' => 'Rodríguez', 'apellido_materno' => 'Flores', 'email' => 'laura.rodriguez@cobao.edu.mx', 'especialidad' => 'Historia'],
    ['nombre' => 'Pedro', 'apellido_paterno' => 'Sánchez', 'apellido_materno' => 'Mendoza', 'email' => 'pedro.sanchez@cobao.edu.mx', 'especialidad' => 'Literatura'],
    ['nombre' => 'Martha', 'apellido_paterno' => 'Díaz', 'apellido_materno' => 'Cruz', 'email' => 'martha.diaz@cobao.edu.mx', 'especialidad' => 'Inglés'],
    ['nombre' => 'Roberto', 'apellido_paterno' => 'Mora', 'apellido_materno' => 'Vega', 'email' => 'roberto.mora@cobao.edu.mx', 'especialidad' => 'Informática'],
];

echo "<h3>👨‍🏫 Maestros</h3>";
echo "<ul>";

$maestros_insertados = 0;
foreach ($maestros as $m) {
    $sql_verificar = "SELECT id FROM maestros WHERE email = ?";
    $existe = obtenerRegistro($sql_verificar, [$m['email']]);
    
    if (!$existe) {
        $sql = "INSERT INTO maestros (nombre, apellido_paterno, apellido_materno, email, especialidad, disponible, activo) 
                VALUES (?, ?, ?, ?, ?, 1, 1)";
        $params = [$m['nombre'], $m['apellido_paterno'], $m['apellido_materno'], $m['email'], $m['especialidad']];
        $types = "sssss";
        
        $resultado = ejecutarUpdate($sql, $params, $types);
        if ($resultado) {
            echo "<li style='color: green;'>✅ {$m['nombre']} {$m['apellido_paterno']} - {$m['especialidad']}</li>";
            $maestros_insertados++;
        }
    } else {
        echo "<li style='color: orange;'>⚠️ {$m['nombre']} {$m['apellido_paterno']} ya existe</li>";
    }
}
echo "</ul>";

// ========== SALONES ==========
$salones = [
    ['nombre' => 'A-101', 'tipo' => 'aula', 'capacidad' => 30, 'ubicacion' => 'Edificio A, Planta Baja'],
    ['nombre' => 'A-102', 'tipo' => 'aula', 'capacidad' => 30, 'ubicacion' => 'Edificio A, Planta Baja'],
    ['nombre' => 'A-201', 'tipo' => 'aula', 'capacidad' => 35, 'ubicacion' => 'Edificio A, Planta Alta'],
    ['nombre' => 'A-202', 'tipo' => 'aula', 'capacidad' => 35, 'ubicacion' => 'Edificio A, Planta Alta'],
    ['nombre' => 'Lab-1', 'tipo' => 'laboratorio', 'capacidad' => 25, 'ubicacion' => 'Edificio B, Planta Baja'],
    ['nombre' => 'Lab-2', 'tipo' => 'laboratorio', 'capacidad' => 25, 'ubicacion' => 'Edificio B, Planta Baja'],
    ['nombre' => 'Taller-1', 'tipo' => 'taller', 'capacidad' => 20, 'ubicacion' => 'Edificio C'],
    ['nombre' => 'Auditorio', 'tipo' => 'auditorio', 'capacidad' => 80, 'ubicacion' => 'Edificio Principal'],
];

echo "<h3>🏛️ Salones</h3>";
echo "<ul>";

$salones_insertados = 0;
foreach ($salones as $s) {
    $sql_verificar = "SELECT id FROM salones WHERE nombre = ?";
    $existe = obtenerRegistro($sql_verificar, [$s['nombre']]);
    
    if (!$existe) {
        // CORREGIDO: Eliminamos 'activo' de la consulta
        $sql = "INSERT INTO salones (nombre, tipo, capacidad, ubicacion, disponible) 
                VALUES (?, ?, ?, ?, 1)";
        $params = [$s['nombre'], $s['tipo'], $s['capacidad'], $s['ubicacion']];
        $types = "ssis";
        
        $resultado = ejecutarUpdate($sql, $params, $types);
        if ($resultado) {
            echo "<li style='color: green;'>✅ {$s['nombre']} - {$s['tipo']} (Cap: {$s['capacidad']})</li>";
            $salones_insertados++;
        } else {
            echo "<li style='color: red;'>❌ Error al insertar {$s['nombre']}</li>";
        }
    } else {
        echo "<li style='color: orange;'>⚠️ {$s['nombre']} ya existe</li>";
    }
}
echo "</ul>";

// ========== RESUMEN ==========
echo "<hr>";
echo "<h3>📊 Resumen:</h3>";
echo "<ul>";
echo "<li>✅ Maestros insertados: $maestros_insertados</li>";
echo "<li>✅ Salones insertados: $salones_insertados</li>";
echo "<li>📌 Total de registros: " . ($maestros_insertados + $salones_insertados) . "</li>";
echo "</ul>";

// Mostrar todos los salones registrados
echo "<hr>";
echo "<h3>📋 Salones registrados:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #17a2b8; color: white;'>";
echo "<th>ID</th><th>Nombre</th><th>Tipo</th><th>Capacidad</th><th>Ubicación</th><th>Disponible</th>";
echo "</tr>";

$sql = "SELECT * FROM salones WHERE activo = 1 ORDER BY nombre";
$result = ejecutarConsulta($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td><strong>{$row['nombre']}</strong></td>";
        echo "<td>{$row['tipo']}</td>";
        echo "<td>{$row['capacidad']}</td>";
        echo "<td>{$row['ubicacion']}</td>";
        echo "<td>" . ($row['disponible'] ? '✅ Disponible' : '❌ Ocupado') . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6' style='text-align: center;'>No hay salones registrados</td></tr>";
}
echo "</table>";

echo "<br>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='index.php' class='btn' style='padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px;'>🏠 Dashboard</a>";
echo "<a href='modules/maestros/' class='btn' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>👨‍🏫 Ver Maestros</a>";
echo "<a href='modules/salones/' class='btn' style='padding: 10px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px;'>🏛️ Ver Salones</a>";
echo "<a href='modules/horarios/crear.php' class='btn' style='padding: 10px 20px; background: #ffc107; color: #333; text-decoration: none; border-radius: 5px;'>📅 Asignar Horario</a>";
echo "</div>";
?>