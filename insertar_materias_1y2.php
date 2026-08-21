<?php
// insertar_materias_1y2.php - Materias para 1° y 2° semestre

require_once 'config/database.php';

echo "<h1>📚 Insertando Materias de 1° y 2° Semestre</h1>";
echo "<hr>";

$materias = [
    // ========== 1° SEMESTRE ==========
    ['nombre' => 'Matemáticas I', 'clave' => 'MAT101', 'semestre_id' => 1, 'tipo' => 'tronco'],
    ['nombre' => 'Física I', 'clave' => 'FIS101', 'semestre_id' => 1, 'tipo' => 'tronco'],
    ['nombre' => 'Química I', 'clave' => 'QUI101', 'semestre_id' => 1, 'tipo' => 'tronco'],
    ['nombre' => 'Biología I', 'clave' => 'BIO101', 'semestre_id' => 1, 'tipo' => 'tronco'],
    ['nombre' => 'Historia de México I', 'clave' => 'HIS101', 'semestre_id' => 1, 'tipo' => 'tronco'],
    ['nombre' => 'Literatura I', 'clave' => 'LIT101', 'semestre_id' => 1, 'tipo' => 'tronco'],
    ['nombre' => 'Inglés I', 'clave' => 'ING101', 'semestre_id' => 1, 'tipo' => 'tronco'],
    ['nombre' => 'Informática I', 'clave' => 'INF101', 'semestre_id' => 1, 'tipo' => 'tronco'],
    
    // ========== 2° SEMESTRE ==========
    ['nombre' => 'Matemáticas II', 'clave' => 'MAT201', 'semestre_id' => 2, 'tipo' => 'tronco'],
    ['nombre' => 'Física II', 'clave' => 'FIS201', 'semestre_id' => 2, 'tipo' => 'tronco'],
    ['nombre' => 'Química II', 'clave' => 'QUI201', 'semestre_id' => 2, 'tipo' => 'tronco'],
    ['nombre' => 'Biología II', 'clave' => 'BIO201', 'semestre_id' => 2, 'tipo' => 'tronco'],
    ['nombre' => 'Historia de México II', 'clave' => 'HIS201', 'semestre_id' => 2, 'tipo' => 'tronco'],
    ['nombre' => 'Literatura II', 'clave' => 'LIT201', 'semestre_id' => 2, 'tipo' => 'tronco'],
    ['nombre' => 'Inglés II', 'clave' => 'ING201', 'semestre_id' => 2, 'tipo' => 'tronco'],
    ['nombre' => 'Informática II', 'clave' => 'INF201', 'semestre_id' => 2, 'tipo' => 'tronco'],
];

$insertados = 0;
$existentes = 0;
$errores = 0;

echo "<ul>";

foreach ($materias as $materia) {
    // Verificar si ya existe
    $sql_verificar = "SELECT id FROM materias WHERE clave = ?";
    $existe = obtenerRegistro($sql_verificar, [$materia['clave']]);
    
    if (!$existe) {
        $sql = "INSERT INTO materias (nombre, clave, semestre_id, tipo_materia, horas_semana, creditos, activo) 
                VALUES (?, ?, ?, ?, 3, 5, 1)";
        $params = [$materia['nombre'], $materia['clave'], $materia['semestre_id'], $materia['tipo']];
        $types = "ssis";
        
        $resultado = ejecutarUpdate($sql, $params, $types);
        
        if ($resultado) {
            echo "<li style='color: green;'>✅ {$materia['nombre']} ({$materia['clave']}) - {$materia['semestre_id']}° Semestre</li>";
            $insertados++;
        } else {
            echo "<li style='color: red;'>❌ Error al crear {$materia['nombre']}</li>";
            $errores++;
        }
    } else {
        echo "<li style='color: orange;'>⚠️ {$materia['nombre']} ya existe</li>";
        $existentes++;
    }
}

echo "</ul>";

echo "<hr>";
echo "<h3>📊 Resumen:</h3>";
echo "<ul>";
echo "<li>✅ Materias insertadas: $insertados</li>";
echo "<li>⚠️ Ya existían: $existentes</li>";
echo "<li>❌ Errores: $errores</li>";
echo "</ul>";

echo "<br>";
echo "<div style='display: flex; gap: 10px;'>";
echo "<a href='index.php' class='btn btn-primary' style='padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px;'>🏠 Dashboard</a>";
echo "<a href='modules/materias/' class='btn btn-success' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>📚 Ver Materias</a>";
echo "</div>";

// Mostrar materias creadas
echo "<hr>";
echo "<h3>📋 Materias registradas:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #1976d2; color: white;'>";
echo "<th>ID</th><th>Clave</th><th>Materia</th><th>Semestre</th><th>Tipo</th>";
echo "</tr>";

$sql = "SELECT m.*, s.numero as semestre_numero 
        FROM materias m 
        JOIN semestres s ON m.semestre_id = s.id 
        WHERE m.semestre_id IN (1, 2) AND m.activo = 1
        ORDER BY s.numero, m.nombre";
$result = ejecutarConsulta($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td><strong>{$row['clave']}</strong></td>";
        echo "<td>{$row['nombre']}</td>";
        echo "<td>{$row['semestre_numero']}° Semestre</td>";
        echo "<td><span style='color: blue;'>{$row['tipo_materia']}</span></td>";
        echo "</tr>";
    }
}
echo "</table>";
?>