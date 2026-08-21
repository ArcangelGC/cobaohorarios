<?php
// modules/horarios/crear.php - Asignar horario con repetición
session_start();

$page_title = 'Asignar Horario';
$page_icon = 'calendar-plus';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$mensaje = '';
$tipo_mensaje = '';

// Verificar si existe la tabla bloques_horarios
$sql_check = "SHOW TABLES LIKE 'bloques_horarios'";
$result_check = ejecutarConsulta($sql_check);

if ($result_check->num_rows == 0) {
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

// Obtener datos para los selects
$grupos = obtenerRegistros("SELECT * FROM grupos WHERE activo = 1 ORDER BY semestre_id, nombre");
$materias = obtenerRegistros("SELECT * FROM materias WHERE activo = 1 ORDER BY semestre_id, nombre");
$maestros = obtenerRegistros("SELECT * FROM maestros WHERE activo = 1 AND disponible = 1 ORDER BY nombre, apellido_paterno");
$salones = obtenerRegistros("SELECT * FROM salones WHERE activo = 1 AND disponible = 1 ORDER BY nombre");

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grupo_id = intval($_POST['grupo_id'] ?? 0);
    $materia_id = intval($_POST['materia_id'] ?? 0);
    $maestro_id = intval($_POST['maestro_id'] ?? 0);
    $salon_id = intval($_POST['salon_id'] ?? 0);
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fin = $_POST['hora_fin'] ?? '';
    $dias_semana = $_POST['dias_semana'] ?? [];
    $semana_inicio = intval($_POST['semana_inicio'] ?? 1);
    $semana_fin = intval($_POST['semana_fin'] ?? 16);
    
    if (empty($dias_semana)) {
        $mensaje = '❌ Debes seleccionar al menos un día para la materia';
        $tipo_mensaje = 'danger';
    } elseif (empty($hora_inicio) || empty($hora_fin)) {
        $mensaje = '❌ Debes seleccionar un horario';
        $tipo_mensaje = 'danger';
    } elseif ($hora_inicio >= $hora_fin) {
        $mensaje = '❌ La hora de inicio debe ser menor que la hora de fin';
        $tipo_mensaje = 'danger';
    } else {
        // Verificar conflictos antes de insertar
        $conflictos = [];
        
        foreach ($dias_semana as $dia) {
            // Verificar conflicto de maestro
            $sql_maestro = "SELECT COUNT(*) as total FROM horarios 
                           WHERE maestro_id = ? AND dia_semana = ? 
                           AND activo = 1
                           AND (
                               (hora_inicio <= ? AND hora_fin > ?) OR
                               (hora_inicio < ? AND hora_fin >= ?) OR
                               (hora_inicio >= ? AND hora_fin <= ?)
                           )";
            $params_maestro = [$maestro_id, $dia, $hora_inicio, $hora_inicio, $hora_fin, $hora_fin, $hora_inicio, $hora_fin];
            $result = ejecutarConsulta($sql_maestro, $params_maestro);
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                $conflictos[] = "El maestro ya tiene clase en $dia a esa hora";
            }
            
            // Verificar conflicto de salón
            $sql_salon = "SELECT COUNT(*) as total FROM horarios 
                         WHERE salon_id = ? AND dia_semana = ? 
                         AND activo = 1
                         AND (
                             (hora_inicio <= ? AND hora_fin > ?) OR
                             (hora_inicio < ? AND hora_fin >= ?) OR
                             (hora_inicio >= ? AND hora_fin <= ?)
                         )";
            $params_salon = [$salon_id, $dia, $hora_inicio, $hora_inicio, $hora_fin, $hora_fin, $hora_inicio, $hora_fin];
            $result = ejecutarConsulta($sql_salon, $params_salon);
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                $conflictos[] = "El salón ya está ocupado en $dia a esa hora";
            }
            
            // Verificar conflicto de grupo
            $sql_grupo = "SELECT COUNT(*) as total FROM horarios 
                         WHERE grupo_id = ? AND dia_semana = ? 
                         AND activo = 1
                         AND (
                             (hora_inicio <= ? AND hora_fin > ?) OR
                             (hora_inicio < ? AND hora_fin >= ?) OR
                             (hora_inicio >= ? AND hora_fin <= ?)
                         )";
            $params_grupo = [$grupo_id, $dia, $hora_inicio, $hora_inicio, $hora_fin, $hora_fin, $hora_inicio, $hora_fin];
            $result = ejecutarConsulta($sql_grupo, $params_grupo);
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                $conflictos[] = "El grupo ya tiene clase en $dia a esa hora";
            }
        }
        
        if (count($conflictos) > 0) {
            $mensaje = '❌ Conflictos detectados:<br>' . implode('<br>', $conflictos);
            $tipo_mensaje = 'danger';
        } else {
            // Crear bloque de horario
            $sql_bloque = "INSERT INTO bloques_horarios (grupo_id, materia_id, semanas) VALUES (?, ?, ?)";
            $bloque_id = ejecutarUpdate($sql_bloque, [$grupo_id, $materia_id, $semana_fin - $semana_inicio + 1]);
            
            if ($bloque_id) {
                $insertados = 0;
                foreach ($dias_semana as $dia) {
                    $sql = "INSERT INTO horarios 
                           (bloque_id, grupo_id, materia_id, maestro_id, salon_id, 
                            dia_semana, hora_inicio, hora_fin, semana_inicio, semana_fin, activo) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                    $params = [$bloque_id, $grupo_id, $materia_id, $maestro_id, $salon_id, 
                               $dia, $hora_inicio, $hora_fin, $semana_inicio, $semana_fin];
                    $types = "iiiiisssii";
                    
                    $resultado = ejecutarUpdate($sql, $params, $types);
                    if ($resultado) $insertados++;
                }
                
                if ($insertados > 0) {
                    $_SESSION['mensaje'] = "✅ Horario asignado correctamente ($insertados días)";
                    $_SESSION['tipo_mensaje'] = 'success';
                    header('Location: index.php');
                    exit;
                } else {
                    $mensaje = '❌ Error al guardar los horarios';
                    $tipo_mensaje = 'danger';
                }
            } else {
                $mensaje = '❌ Error al crear el bloque de horario';
                $tipo_mensaje = 'danger';
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-plus text-primary"></i> Asignar Horario
                </h5>
                <small class="text-muted">Asigna una materia a un grupo en múltiples días</small>
            </div>
            <div class="card-body">
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="formHorario">
                    <div class="row g-3">
                        <!-- Selección de grupo -->
                        <div class="col-md-6">
                            <label for="grupo_id" class="form-label">Grupo *</label>
                            <select class="form-select" id="grupo_id" name="grupo_id" required>
                                <option value="">Seleccionar grupo</option>
                                <?php foreach ($grupos as $g): ?>
                                    <option value="<?php echo $g['id']; ?>">
                                        <?php echo htmlspecialchars($g['nombre']); ?> 
                                        (Semestre <?php echo obtenerSemestreNombre($g['semestre_id']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Selección de materia -->
                        <div class="col-md-6">
                            <label for="materia_id" class="form-label">Materia *</label>
                            <select class="form-select" id="materia_id" name="materia_id" required>
                                <option value="">Seleccionar materia</option>
                                <?php foreach ($materias as $m): ?>
                                    <option value="<?php echo $m['id']; ?>">
                                        <?php echo htmlspecialchars($m['nombre']); ?> 
                                        (<?php echo $m['clave']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Selección de maestro -->
                        <div class="col-md-6">
                            <label for="maestro_id" class="form-label">Maestro *</label>
                            <select class="form-select" id="maestro_id" name="maestro_id" required>
                                <option value="">Seleccionar maestro</option>
                                <?php foreach ($maestros as $m): ?>
                                    <option value="<?php echo $m['id']; ?>">
                                        <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido_paterno']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Selección de salón -->
                        <div class="col-md-6">
                            <label for="salon_id" class="form-label">Salón *</label>
                            <select class="form-select" id="salon_id" name="salon_id" required>
                                <option value="">Seleccionar salón</option>
                                <?php foreach ($salones as $s): ?>
                                    <option value="<?php echo $s['id']; ?>">
                                        <?php echo htmlspecialchars($s['nombre']); ?> 
                                        (Cap: <?php echo $s['capacidad']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Horario -->
                        <div class="col-md-6">
                            <label for="hora_inicio" class="form-label">Hora de Inicio *</label>
                            <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="hora_fin" class="form-label">Hora de Fin *</label>
                            <input type="time" class="form-control" id="hora_fin" name="hora_fin" required>
                        </div>
                        
                        <!-- Días de la semana -->
                        <div class="col-12">
                            <label class="form-label">Días de la Semana *</label>
                            <small class="text-muted d-block">Selecciona todos los días en que se imparte la materia</small>
                            <div class="row g-2 mt-2">
                                <?php 
                                $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                foreach ($dias as $dia):
                                ?>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="dias_semana[]" value="<?php echo $dia; ?>" 
                                               id="dia_<?php echo $dia; ?>">
                                        <label class="form-check-label" for="dia_<?php echo $dia; ?>">
                                            <?php echo $dia; ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="contador_dias" class="mt-2">
                                <span class="badge bg-secondary">0 días seleccionados</span>
                            </div>
                        </div>
                        
                        <!-- Semanas -->
                        <div class="col-md-6">
                            <label for="semana_inicio" class="form-label">Semana de Inicio</label>
                            <input type="number" class="form-control" id="semana_inicio" name="semana_inicio" 
                                   value="1" min="1" max="16">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="semana_fin" class="form-label">Semana de Fin</label>
                            <input type="number" class="form-control" id="semana_fin" name="semana_fin" 
                                   value="16" min="1" max="16">
                        </div>
                        
                        <!-- Resumen -->
                        <div class="col-12">
                            <div class="alert alert-info" id="resumen_horario" style="display: none;">
                                <h6><i class="fas fa-info-circle"></i> Resumen del Horario</h6>
                                <div id="resumen_contenido"></div>
                            </div>
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Horario
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Ejemplo de uso -->
        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h6><i class="fas fa-lightbulb text-warning"></i> Ejemplo: Matemáticas 3 veces por semana</h6>
                <div class="row g-2">
                    <div class="col-md-3">
                        <span class="badge bg-primary">Lunes 7:00 - 8:00</span>
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-primary">Miércoles 7:00 - 8:00</span>
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-primary">Viernes 7:00 - 8:00</span>
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-success">✅ Misma materia, 3 días</span>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    Selecciona los 3 días, define el horario y el sistema asignará automáticamente las 3 clases.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
// Contador de días seleccionados
document.querySelectorAll('input[name="dias_semana[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const seleccionados = document.querySelectorAll('input[name="dias_semana[]"]:checked');
        document.querySelector('#contador_dias .badge').textContent = 
            `${seleccionados.length} días seleccionados`;
        
        // Mostrar resumen
        const resumen = document.getElementById('resumen_horario');
        const contenido = document.getElementById('resumen_contenido');
        
        if (seleccionados.length > 0) {
            resumen.style.display = 'block';
            const grupo = document.getElementById('grupo_id');
            const materia = document.getElementById('materia_id');
            const maestro = document.getElementById('maestro_id');
            const salon = document.getElementById('salon_id');
            const inicio = document.getElementById('hora_inicio');
            const fin = document.getElementById('hora_fin');
            
            let html = '';
            if (grupo.value) {
                const grupoText = grupo.options[grupo.selectedIndex]?.text || '';
                html += `<strong>Grupo:</strong> ${grupoText}<br>`;
            }
            if (materia.value) {
                const materiaText = materia.options[materia.selectedIndex]?.text || '';
                html += `<strong>Materia:</strong> ${materiaText}<br>`;
            }
            if (maestro.value) {
                const maestroText = maestro.options[maestro.selectedIndex]?.text || '';
                html += `<strong>Maestro:</strong> ${maestroText}<br>`;
            }
            if (salon.value) {
                const salonText = salon.options[salon.selectedIndex]?.text || '';
                html += `<strong>Salón:</strong> ${salonText}<br>`;
            }
            if (inicio.value && fin.value) {
                html += `<strong>Horario:</strong> ${inicio.value} - ${fin.value}<br>`;
            }
            
            const dias = Array.from(seleccionados).map(cb => cb.value).join(', ');
            html += `<strong>Días:</strong> ${dias}`;
            
            contenido.innerHTML = html;
        } else {
            resumen.style.display = 'none';
        }
    });
});

// Validar que hora_inicio < hora_fin
document.getElementById('formHorario').addEventListener('submit', function(e) {
    const inicio = document.getElementById('hora_inicio').value;
    const fin = document.getElementById('hora_fin').value;
    const seleccionados = document.querySelectorAll('input[name="dias_semana[]"]:checked');
    
    if (seleccionados.length === 0) {
        e.preventDefault();
        alert('❌ Debes seleccionar al menos un día para la materia');
        return false;
    }
    
    if (inicio >= fin) {
        e.preventDefault();
        alert('❌ La hora de inicio debe ser menor que la hora de fin');
        return false;
    }
});
</script>

<?php
// Función auxiliar para obtener nombre del semestre
function obtenerSemestreNombre($id) {
    $semestres = [
        1 => '1°',
        2 => '2°',
        3 => '3°',
        4 => '4°',
        5 => '5°',
        6 => '6°'
    ];
    return $semestres[$id] ?? 'Desconocido';
}
?>

<?php include '../../includes/footer.php'; ?>