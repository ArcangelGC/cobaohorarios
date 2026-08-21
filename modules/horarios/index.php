<?php
// modules/horarios/index.php - Listado de horarios agrupados
session_start();

$page_title = 'Gestión de Horarios';
$page_icon = 'calendar-alt';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar si existe la tabla bloques_horarios
$sql_check = "SHOW TABLES LIKE 'bloques_horarios'";
$result_check = ejecutarConsulta($sql_check);

if ($result_check->num_rows == 0) {
    // Crear la tabla si no existe
    $sql_create = "CREATE TABLE IF NOT EXISTS bloques_horarios (
        id INT PRIMARY KEY AUTO_INCREMENT,
        grupo_id INT NOT NULL,
        materia_id INT NOT NULL,
        nombre VARCHAR(50),
        semanas INT DEFAULT 16,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
        FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
    )";
    ejecutarConsulta($sql_create);
}

// Obtener horarios agrupados por bloque
$sql = "SELECT 
            b.id as bloque_id,
            b.grupo_id,
            b.materia_id,
            g.nombre as grupo_nombre,
            m.nombre as materia_nombre,
            m.clave as materia_clave,
            COUNT(h.id) as total_dias,
            GROUP_CONCAT(DISTINCT h.dia_semana ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado')) as dias,
            MIN(h.hora_inicio) as hora_inicio,
            MAX(h.hora_fin) as hora_fin,
            h.maestro_id,
            CONCAT(ma.nombre, ' ', ma.apellido_paterno) as maestro_nombre,
            h.salon_id,
            s.nombre as salon_nombre,
            h.semana_inicio,
            h.semana_fin
        FROM bloques_horarios b
        JOIN horarios h ON b.id = h.bloque_id
        JOIN grupos g ON b.grupo_id = g.id
        JOIN materias m ON b.materia_id = m.id
        JOIN maestros ma ON h.maestro_id = ma.id
        JOIN salones s ON h.salon_id = s.id
        WHERE h.activo = 1
        GROUP BY b.id
        ORDER BY g.nombre, m.nombre";

$horarios = obtenerRegistros($sql);

include '../../includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0">
            <i class="fas fa-calendar-alt text-primary"></i> Horarios Asignados
        </h5>
        <div>
            <a href="crear.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Asignar Horario
            </a>
            <a href="generar.php" class="btn btn-success btn-sm">
                <i class="fas fa-magic"></i> Generar Horarios
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Grupo</th>
                        <th>Materia</th>
                        <th>Maestro</th>
                        <th>Salón</th>
                        <th>Días</th>
                        <th>Horario</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($horarios) > 0): ?>
                        <?php foreach ($horarios as $index => $h): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($h['grupo_nombre']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($h['materia_nombre']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo $h['materia_clave']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($h['maestro_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($h['salon_nombre']); ?></td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo $h['total_dias']; ?> días
                                    </span>
                                    <br>
                                    <small><?php echo $h['dias']; ?></small>
                                </td>
                                <td>
                                    <?php echo substr($h['hora_inicio'], 0, 5); ?> - 
                                    <?php echo substr($h['hora_fin'], 0, 5); ?>
                                    <br>
                                    <small class="text-muted">
                                        Semana <?php echo $h['semana_inicio']; ?>-<?php echo $h['semana_fin']; ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="editar.php?id=<?php echo $h['bloque_id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmarEliminar(<?php echo $h['bloque_id']; ?>, '<?php echo htmlspecialchars($h['materia_nombre']); ?>')" 
                                                class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-calendar-alt fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">No hay horarios asignados</p>
                                <a href="crear.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Asignar primer horario
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmarEliminar(id, nombre) {
    if (confirm(`¿Estás seguro de eliminar el horario de "${nombre}"?\nSe eliminarán todas las repeticiones.`)) {
        window.location.href = `eliminar.php?id=${id}`;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>