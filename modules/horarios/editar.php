<?php
// modules/horarios/editar.php - Editar horario (VERSIÓN CORREGIDA)
session_start();

$page_title = 'Editar Horario';
$page_icon = 'edit';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$mensaje = '';
$tipo_mensaje = '';

// Obtener el ID del bloque
$bloque_id = intval($_GET['id'] ?? 0);

if ($bloque_id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener información del bloque y sus horarios
$sql_bloque = "SELECT 
                    b.id as bloque_id,
                    b.grupo_id,
                    b.materia_id,
                    b.semanas,
                    g.nombre as grupo_nombre,
                    m.nombre as materia_nombre,
                    m.clave as materia_clave,
                    g.semestre_id
                FROM bloques_horarios b
                JOIN grupos g ON b.grupo_id = g.id
                JOIN materias m ON b.materia_id = m.id
                WHERE b.id = ?";
$bloque = obtenerRegistro($sql_bloque, [$bloque_id]);

if (!$bloque) {
    header('Location: index.php');
    exit;
}

// Obtener los horarios del bloque
$horarios = obtenerRegistros("
    SELECT * FROM horarios 
    WHERE bloque_id = ? AND activo = 1
    ORDER BY FIELD(dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'), hora_inicio
", [$bloque_id]);

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
    $semana_inicio = intval($_POST['semana_inicio'] ?? 1);
    $semana_fin = intval($_POST['semana_fin'] ?? 16);
    
    // Obtener los días y horas del formulario
    $dias = $_POST['dias'] ?? [];
    $horas_inicio = $_POST['horas_inicio'] ?? [];
    $horas_fin = $_POST['horas_fin'] ?? [];
    
    if (empty($dias)) {
        $mensaje = '❌ Debes seleccionar al menos un día para la materia';
        $tipo_mensaje = 'danger';
    } else {
        // Validar que cada día tenga hora
        $errores = false;
        $datos_validos = [];
        
        foreach ($dias as $dia) {
            if (empty($horas_inicio[$dia]) || empty($horas_fin[$dia])) {
                $mensaje = "❌ El día $dia no tiene horario definido";
                $tipo_mensaje = 'danger';
                $errores = true;
                break;
            }
            if ($horas_inicio[$dia] >= $horas_fin[$dia]) {
                $mensaje = "❌ La hora de inicio debe ser menor que la hora de fin en $dia";
                $tipo_mensaje = 'danger';
                $errores = true;
                break;
            }
            $datos_validos[$dia] = [
                'inicio' => $horas_inicio[$dia],
                'fin' => $horas_fin[$dia]
            ];
        }
        
        if (!$errores) {
            // Verificar conflictos (excluyendo el bloque actual)
            $conflictos = [];
            
            foreach ($dias as $dia) {
                $hora_inicio = $horas_inicio[$dia];
                $hora_fin = $horas_fin[$dia];
                
                // Verificar conflicto de maestro
                $sql_maestro = "SELECT COUNT(*) as total FROM horarios 
                               WHERE maestro_id = ? AND dia_semana = ? 
                               AND activo = 1 AND bloque_id != ?
                               AND (
                                   (hora_inicio <= ? AND hora_fin > ?) OR
                                   (hora_inicio < ? AND hora_fin >= ?) OR
                                   (hora_inicio >= ? AND hora_fin <= ?)
                               )";
                $params_maestro = [$maestro_id, $dia, $bloque_id, $hora_inicio, $hora_inicio, $hora_fin, $hora_fin, $hora_inicio, $hora_fin];
                $result = ejecutarConsulta($sql_maestro, $params_maestro);
                $row = $result->fetch_assoc();
                
                if ($row['total'] > 0) {
                    $conflictos[] = "El maestro ya tiene clase en $dia a esa hora";
                }
                
                // Verificar conflicto de salón
                $sql_salon = "SELECT COUNT(*) as total FROM horarios 
                             WHERE salon_id = ? AND dia_semana = ? 
                             AND activo = 1 AND bloque_id != ?
                             AND (
                                 (hora_inicio <= ? AND hora_fin > ?) OR
                                 (hora_inicio < ? AND hora_fin >= ?) OR
                                 (hora_inicio >= ? AND hora_fin <= ?)
                             )";
                $params_salon = [$salon_id, $dia, $bloque_id, $hora_inicio, $hora_inicio, $hora_fin, $hora_fin, $hora_inicio, $hora_fin];
                $result = ejecutarConsulta($sql_salon, $params_salon);
                $row = $result->fetch_assoc();
                
                if ($row['total'] > 0) {
                    $conflictos[] = "El salón ya está ocupado en $dia a esa hora";
                }
                
                // Verificar conflicto de grupo
                $sql_grupo = "SELECT COUNT(*) as total FROM horarios 
                             WHERE grupo_id = ? AND dia_semana = ? 
                             AND activo = 1 AND bloque_id != ?
                             AND (
                                 (hora_inicio <= ? AND hora_fin > ?) OR
                                 (hora_inicio < ? AND hora_fin >= ?) OR
                                 (hora_inicio >= ? AND hora_fin <= ?)
                             )";
                $params_grupo = [$grupo_id, $dia, $bloque_id, $hora_inicio, $hora_inicio, $hora_fin, $hora_fin, $hora_inicio, $hora_fin];
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
                // ========================================
                // INICIO DE ACTUALIZACIÓN (CON TRANSACCIÓN)
                // ========================================
                
                // Obtener conexión para transacción
                $conn = conectarDB();
                $conn->begin_transaction();
                
                try {
                    // 1. Actualizar el bloque
                    $sql_update_bloque = "UPDATE bloques_horarios SET 
                                         grupo_id = ?, 
                                         materia_id = ?,
                                         semanas = ?
                                         WHERE id = ?";
                    $stmt = $conn->prepare($sql_update_bloque);
                    $semanas = $semana_fin - $semana_inicio + 1;
                    $stmt->bind_param("iiii", $grupo_id, $materia_id, $semanas, $bloque_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 2. ELIMINAR horarios actuales (activar = 0)
                    $sql_delete = "UPDATE horarios SET activo = 0 WHERE bloque_id = ?";
                    $stmt = $conn->prepare($sql_delete);
                    $stmt->bind_param("i", $bloque_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 3. INSERTAR nuevos horarios
                    $insertados = 0;
                    foreach ($dias as $dia) {
                        $hora_inicio = $horas_inicio[$dia];
                        $hora_fin = $horas_fin[$dia];
                        
                        $sql_insert = "INSERT INTO horarios 
                                       (bloque_id, grupo_id, materia_id, maestro_id, salon_id, 
                                        dia_semana, hora_inicio, hora_fin, semana_inicio, semana_fin, activo) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                        $stmt = $conn->prepare($sql_insert);
                        $stmt->bind_param("iiiiisssii", 
                            $bloque_id, $grupo_id, $materia_id, $maestro_id, $salon_id, 
                            $dia, $hora_inicio, $hora_fin, $semana_inicio, $semana_fin
                        );
                        $stmt->execute();
                        if ($stmt->affected_rows > 0) $insertados++;
                        $stmt->close();
                    }
                    
                    // Confirmar transacción
                    $conn->commit();
                    
                    $_SESSION['mensaje'] = "✅ Horario actualizado correctamente ($insertados días)";
                    $_SESSION['tipo_mensaje'] = 'success';
                    header('Location: index.php');
                    exit;
                    
                } catch (Exception $e) {
                    // Revertir cambios en caso de error
                    $conn->rollback();
                    $mensaje = '❌ Error al actualizar: ' . $e->getMessage();
                    $tipo_mensaje = 'danger';
                }
                
                $conn->close();
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-11">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-edit text-primary"></i> Editar Horario
                </h5>
                <small class="text-muted">
                    Editando: <?php echo htmlspecialchars($bloque['grupo_nombre']); ?> - 
                    <?php echo htmlspecialchars($bloque['materia_nombre']); ?>
                    (ID: <?php echo $bloque_id; ?>)
                </small>
            </div>
            <div class="card-body">
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Mensaje de advertencia -->
                <?php if (count($horarios) == 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>¡Atención!</strong> Este bloque no tiene horarios asignados. 
                        Configura los días y horarios para guardar.
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
                                    <option value="<?php echo $g['id']; ?>" 
                                        <?php echo $bloque['grupo_id'] == $g['id'] ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $m['id']; ?>" 
                                        <?php echo $bloque['materia_id'] == $m['id'] ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $m['id']; ?>" 
                                        <?php echo (count($horarios) > 0 && $horarios[0]['maestro_id'] == $m['id']) ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $s['id']; ?>" 
                                        <?php echo (count($horarios) > 0 && $horarios[0]['salon_id'] == $s['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['nombre']); ?> 
                                        (Cap: <?php echo $s['capacidad']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Semanas -->
                        <div class="col-md-6">
                            <label for="semana_inicio" class="form-label">Semana de Inicio</label>
                            <input type="number" class="form-control" id="semana_inicio" name="semana_inicio" 
                                   value="<?php echo count($horarios) > 0 ? $horarios[0]['semana_inicio'] : 1; ?>" 
                                   min="1" max="16">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="semana_fin" class="form-label">Semana de Fin</label>
                            <input type="number" class="form-control" id="semana_fin" name="semana_fin" 
                                   value="<?php echo count($horarios) > 0 ? $horarios[0]['semana_fin'] : 16; ?>" 
                                   min="1" max="16">
                        </div>
                        
                        <!-- Días y Horarios -->
                        <div class="col-12">
                            <hr>
                            <h6 class="mb-3">
                                <i class="fas fa-clock text-primary"></i> Días y Horarios
                                <small class="text-muted">(Configura cada día con su horario específico)</small>
                            </h6>
                            
                            <?php 
                            $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                            // Crear un array con los horarios existentes por día
                            $horarios_por_dia = [];
                            foreach ($horarios as $h) {
                                $horarios_por_dia[$h['dia_semana']] = [
                                    'hora_inicio' => $h['hora_inicio'],
                                    'hora_fin' => $h['hora_fin']
                                ];
                            }
                            ?>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width: 15%;">Día</th>
                                            <th style="width: 15%;">Seleccionar</th>
                                            <th style="width: 35%;">Hora de Inicio</th>
                                            <th style="width: 35%;">Hora de Fin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dias_semana as $dia): 
                                            $checked = isset($horarios_por_dia[$dia]) ? 'checked' : '';
                                            $hora_inicio = isset($horarios_por_dia[$dia]) ? $horarios_por_dia[$dia]['hora_inicio'] : '';
                                            $hora_fin = isset($horarios_por_dia[$dia]) ? $horarios_por_dia[$dia]['hora_fin'] : '';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $dia; ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="dias[]" value="<?php echo $dia; ?>" 
                                                       <?php echo $checked; ?> 
                                                       onchange="toggleHoras(this, '<?php echo $dia; ?>')">
                                            </td>
                                            <td>
                                                <input type="time" class="form-control" 
                                                       name="horas_inicio[<?php echo $dia; ?>]" 
                                                       id="inicio_<?php echo $dia; ?>"
                                                       value="<?php echo $hora_inicio; ?>"
                                                       <?php echo $checked ? '' : 'disabled'; ?>>
                                            </td>
                                            <td>
                                                <input type="time" class="form-control" 
                                                       name="horas_fin[<?php echo $dia; ?>]" 
                                                       id="fin_<?php echo $dia; ?>"
                                                       value="<?php echo $hora_fin; ?>"
                                                       <?php echo $checked ? '' : 'disabled'; ?>>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div id="contador_dias" class="mt-2">
                                <span class="badge bg-secondary">
                                    <?php echo count($horarios); ?> días seleccionados
                                </span>
                            </div>
                            
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Selecciona los días y asigna el horario para cada uno. 
                                    Puedes tener diferentes horarios en diferentes días.
                                </small>
                            </div>
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Horario
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                            <button type="button" class="btn btn-danger float-end" 
                                    onclick="confirmarEliminar(<?php echo $bloque_id; ?>)">
                                <i class="fas fa-trash"></i> Eliminar Bloque
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Vista previa del horario -->
        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h6><i class="fas fa-eye text-info"></i> Vista Previa del Horario</h6>
                <div id="vista_previa" class="mt-2">
                    <?php if (count($horarios) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Día</th>
                                        <th>Hora Inicio</th>
                                        <th>Hora Fin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($horarios as $h): ?>
                                        <tr>
                                            <td><strong><?php echo $h['dia_semana']; ?></strong></td>
                                            <td><?php echo substr($h['hora_inicio'], 0, 5); ?></td>
                                            <td><?php echo substr($h['hora_fin'], 0, 5); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No hay horarios configurados. Selecciona los días para ver la vista previa.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Función para habilitar/deshabilitar campos de hora
function toggleHoras(checkbox, dia) {
    const inicio = document.getElementById('inicio_' + dia);
    const fin = document.getElementById('fin_' + dia);
    
    if (checkbox.checked) {
        inicio.disabled = false;
        fin.disabled = false;
        if (!inicio.value) inicio.value = '07:00';
        if (!fin.value) fin.value = '08:00';
    } else {
        inicio.disabled = true;
        fin.disabled = true;
        inicio.value = '';
        fin.value = '';
    }
    
    actualizarContador();
    actualizarVistaPrevia();
}

// Actualizar contador de días seleccionados
function actualizarContador() {
    const seleccionados = document.querySelectorAll('input[name="dias[]"]:checked');
    document.querySelector('#contador_dias .badge').textContent = 
        `${seleccionados.length} días seleccionados`;
}

// Actualizar vista previa
function actualizarVistaPrevia() {
    const seleccionados = document.querySelectorAll('input[name="dias[]"]:checked');
    const preview = document.getElementById('vista_previa');
    
    if (seleccionados.length === 0) {
        preview.innerHTML = '<p class="text-muted">Selecciona los días para ver el horario completo</p>';
        return;
    }
    
    const grupo = document.getElementById('grupo_id');
    const materia = document.getElementById('materia_id');
    const maestro = document.getElementById('maestro_id');
    const salon = document.getElementById('salon_id');
    
    let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
    html += '<thead><tr><th>Día</th><th>Hora Inicio</th><th>Hora Fin</th></tr></thead>';
    html += '<tbody>';
    
    seleccionados.forEach(cb => {
        const dia = cb.value;
        const inicio = document.getElementById('inicio_' + dia);
        const fin = document.getElementById('fin_' + dia);
        html += `<tr>
                    <td><strong>${dia}</strong></td>
                    <td>${inicio.value || '--:--'}</td>
                    <td>${fin.value || '--:--'}</td>
                </tr>`;
    });
    
    html += '</tbody></table></div>';
    
    // Agregar información adicional
    let info = '<div class="mt-2">';
    if (grupo.value) {
        const grupoText = grupo.options[grupo.selectedIndex]?.text || '';
        info += `<span class="badge bg-primary me-1">Grupo: ${grupoText}</span>`;
    }
    if (materia.value) {
        const materiaText = materia.options[materia.selectedIndex]?.text || '';
        info += `<span class="badge bg-success me-1">Materia: ${materiaText}</span>`;
    }
    if (maestro.value) {
        const maestroText = maestro.options[maestro.selectedIndex]?.text || '';
        info += `<span class="badge bg-warning me-1">Maestro: ${maestroText}</span>`;
    }
    if (salon.value) {
        const salonText = salon.options[salon.selectedIndex]?.text || '';
        info += `<span class="badge bg-info">Salón: ${salonText}</span>`;
    }
    info += '</div>';
    
    preview.innerHTML = html + info;
}

// Actualizar vista previa cuando cambian los selects
document.querySelectorAll('#grupo_id, #materia_id, #maestro_id, #salon_id').forEach(select => {
    select.addEventListener('change', actualizarVistaPrevia);
});

// Inicializar vista previa
document.addEventListener('DOMContentLoaded', function() {
    actualizarContador();
    actualizarVistaPrevia();
});

// Función para eliminar el bloque
function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de eliminar este bloque de horario?\nSe eliminarán todas las repeticiones.')) {
        window.location.href = `eliminar.php?id=${id}`;
    }
}

// Validar formulario antes de enviar
document.getElementById('formHorario').addEventListener('submit', function(e) {
    const seleccionados = document.querySelectorAll('input[name="dias[]"]:checked');
    
    if (seleccionados.length === 0) {
        e.preventDefault();
        alert('❌ Debes seleccionar al menos un día para la materia');
        return false;
    }
    
    let error = false;
    seleccionados.forEach(cb => {
        const dia = cb.value;
        const inicio = document.getElementById('inicio_' + dia);
        const fin = document.getElementById('fin_' + dia);
        
        if (!inicio.value || !fin.value) {
            alert(`❌ El día ${dia} no tiene horario definido`);
            error = true;
        }
        
        if (inicio.value >= fin.value) {
            alert(`❌ La hora de inicio debe ser menor que la hora de fin en ${dia}`);
            error = true;
        }
    });
    
    if (error) {
        e.preventDefault();
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