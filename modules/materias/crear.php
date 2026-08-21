<?php
// modules/materias/crear.php - Crear nueva materia
session_start();

$page_title = 'Crear Nueva Materia';
$page_icon = 'book-plus';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$mensaje = '';
$tipo_mensaje = '';

// Obtener semestres
$semestres = obtenerRegistros("SELECT * FROM semestres WHERE activo = 1 ORDER BY numero ASC");

// Obtener especialidades
$especialidades = obtenerRegistros("SELECT * FROM especialidades WHERE activo = 1 ORDER BY nombre ASC");

// Obtener nucleos
$nucleos = obtenerRegistros("SELECT * FROM nucleos WHERE activo = 1 ORDER BY orden ASC");

// Obtener maestros y salones
$maestros = obtenerRegistros("SELECT * FROM maestros WHERE activo = 1 AND disponible = 1 ORDER BY nombre, apellido_paterno");
$salones = obtenerRegistros("SELECT * FROM salones WHERE activo = 1 AND disponible = 1 ORDER BY nombre");

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $clave = trim($_POST['clave'] ?? '');
    $semestre_id = intval($_POST['semestre_id'] ?? 0);
    $tipo_materia = $_POST['tipo_materia'] ?? 'tronco';
    $especialidad_id = !empty($_POST['especialidad_id']) ? intval($_POST['especialidad_id']) : null;
    $nucleo_id = !empty($_POST['nucleo_id']) ? intval($_POST['nucleo_id']) : null;
    $creditos = intval($_POST['creditos'] ?? 5);
    
    // Datos de horario
    $dias = $_POST['dias'] ?? [];
    $horas_inicio = $_POST['horas_inicio'] ?? [];
    $horas_fin = $_POST['horas_fin'] ?? [];
    $maestro_id = intval($_POST['maestro_id'] ?? 0);
    $salon_id = intval($_POST['salon_id'] ?? 0);
    $crear_horario = isset($_POST['crear_horario']) ? 1 : 0;
    
    // Validaciones
    if (empty($nombre)) {
        $mensaje = 'El nombre de la materia es obligatorio';
        $tipo_mensaje = 'danger';
    } elseif (empty($clave)) {
        $mensaje = 'La clave de la materia es obligatoria';
        $tipo_mensaje = 'danger';
    } elseif ($semestre_id <= 0) {
        $mensaje = 'Debes seleccionar un semestre';
        $tipo_mensaje = 'danger';
    } else {
        // Verificar que no exista una materia con la misma clave
        $sql_verificar = "SELECT id FROM materias WHERE clave = ?";
        $existe = obtenerRegistro($sql_verificar, [$clave]);
        
        if ($existe) {
            $mensaje = 'Ya existe una materia con esa clave';
            $tipo_mensaje = 'danger';
        } else {
            // Insertar la nueva materia (SIN horas_semana)
            $sql = "INSERT INTO materias (nombre, clave, semestre_id, tipo_materia, especialidad_id, nucleo_id, creditos) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [$nombre, $clave, $semestre_id, $tipo_materia, $especialidad_id, $nucleo_id, $creditos];
            $types = "ssisiii";
            
            $materia_id = ejecutarUpdate($sql, $params, $types);
            
            if ($materia_id) {
                // ==========================================
                // CREAR BLOQUE DE HORARIO
                // ==========================================
                if ($crear_horario && !empty($dias) && $maestro_id > 0 && $salon_id > 0) {
                    $dias_validos = [];
                    $error_horario = false;
                    
                    foreach ($dias as $dia) {
                        if (empty($horas_inicio[$dia]) || empty($horas_fin[$dia])) {
                            $mensaje = "El dia $dia no tiene horario definido";
                            $tipo_mensaje = 'danger';
                            $error_horario = true;
                            break;
                        }
                        if ($horas_inicio[$dia] >= $horas_fin[$dia]) {
                            $mensaje = "La hora de inicio debe ser menor que la hora de fin en $dia";
                            $tipo_mensaje = 'danger';
                            $error_horario = true;
                            break;
                        }
                        $dias_validos[] = $dia;
                    }
                    
                    if (!$error_horario && count($dias_validos) > 0) {
                        // Insertar bloque de horario con grupo_id = NULL
                        $sql_bloque = "INSERT INTO bloques_horarios (grupo_id, materia_id, semanas) VALUES (NULL, ?, 16)";
                        $bloque_id = ejecutarUpdate($sql_bloque, [$materia_id]);
                        
                        if ($bloque_id) {
                            $insertados = 0;
                            foreach ($dias_validos as $dia) {
                                $sql_horario = "INSERT INTO horarios 
                                               (bloque_id, grupo_id, materia_id, maestro_id, salon_id, 
                                                dia_semana, hora_inicio, hora_fin, semana_inicio, semana_fin, activo) 
                                               VALUES (?, NULL, ?, ?, ?, ?, ?, ?, 1, 16, 1)";
                                
                                $params_horario = [$bloque_id, $materia_id, $maestro_id, $salon_id, 
                                                   $dia, $horas_inicio[$dia], $horas_fin[$dia]];
                                
                                $resultado = ejecutarUpdate($sql_horario, $params_horario);
                                if ($resultado) $insertados++;
                            }
                            
                            if ($insertados > 0) {
                                $_SESSION['mensaje'] = "Materia creada con $insertados dias de horario";
                                $_SESSION['tipo_mensaje'] = 'success';
                                header('Location: index.php');
                                exit;
                            }
                        }
                    }
                }
                
                if (empty($_SESSION['mensaje'])) {
                    $_SESSION['mensaje'] = 'Materia creada exitosamente';
                    $_SESSION['tipo_mensaje'] = 'success';
                    header('Location: index.php');
                    exit;
                }
            } else {
                $mensaje = 'Error al crear la materia';
                $tipo_mensaje = 'danger';
            }
        }
    }
}

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS PARA EL FORMULARIO
   ============================================ */
.materia-form .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.materia-form .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.materia-form .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.materia-form .card-header small {
    color: #6c757d;
    font-size: 0.85rem;
}

.materia-form .section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    padding-bottom: 10px;
    margin-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 8px;
}

.materia-form .section-title i {
    color: #1976d2;
}

.materia-form .form-control,
.materia-form .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 10px 15px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.materia-form .form-control:focus,
.materia-form .form-select:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
}

.materia-form .form-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.materia-form .form-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 4px;
}

.switch-container {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    cursor: pointer;
}

.switch-container:hover {
    border-color: #1976d2;
    background: #f0f4ff;
}

.switch-container.active {
    border-color: #28a745;
    background: #f0fff4;
}

.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 28px;
    flex-shrink: 0;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #28a745;
}

input:checked + .slider:before {
    transform: translateX(22px);
}

.switch-label {
    font-weight: 600;
    font-size: 1rem;
    color: #495057;
}

.switch-label small {
    display: block;
    font-weight: 400;
    font-size: 0.8rem;
    color: #6c757d;
}

.horario-table {
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e9ecef;
}

.horario-table thead {
    background: #f8f9fa;
}

.horario-table thead th {
    font-weight: 600;
    color: #495057;
    font-size: 0.85rem;
    padding: 12px 15px;
    border-bottom: 2px solid #dee2e6;
}

.horario-table tbody td {
    padding: 10px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
}

.horario-table tbody tr:hover {
    background: #f8f9fa;
}

.horario-table .day-name {
    font-weight: 600;
    color: #1a237e;
}

.horario-table .form-check-input {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.horario-table .form-check-input:checked {
    background-color: #1976d2;
    border-color: #1976d2;
}

.horario-table .form-control {
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.9rem;
    border: 2px solid #e9ecef;
}

.horario-table .form-control:disabled {
    background: #f8f9fa;
    opacity: 0.5;
}

.dias-counter {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #e9ecef;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
}

.dias-counter .badge {
    font-size: 0.85rem;
    padding: 4px 12px;
}

.preview-container {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px 20px;
    border: 2px dashed #dee2e6;
    transition: all 0.3s ease;
}

.preview-container.active {
    border-color: #28a745;
    background: #f0fff4;
}

.preview-container .preview-title {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.preview-container .preview-title i {
    color: #1976d2;
}

.preview-table {
    font-size: 0.9rem;
}

.preview-table th {
    font-weight: 600;
    color: #495057;
}

.badge-laboral {
    background: #e8f5e9;
    color: #2e7d32;
    font-weight: 500;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.horario-section {
    animation: slideDown 0.3s ease;
}

@media (max-width: 768px) {
    .materia-form .card-header {
        padding: 15px;
    }
    .horario-table td,
    .horario-table th {
        padding: 8px 10px;
        font-size: 0.8rem;
    }
    .switch-container {
        padding: 12px 15px;
        flex-wrap: wrap;
    }
}
</style>

<div class="materia-form">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-book-plus text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Nueva Materia</h5>
                            <small>Registra materias con sus horarios de clase <span class="badge-laboral badge ms-2">Lunes a Viernes</span></small>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="formMateria">
                        <!-- SECCION 1: DATOS BASICOS -->
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i> Datos de la Materia
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="nombre" class="form-label">
                                    Nombre de la Materia <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                                       placeholder="Ej: Matematicas I" 
                                       required>
                            </div>
                            
                            <div class="col-md-5">
                                <label for="clave" class="form-label">
                                    Clave <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="clave" 
                                       name="clave" 
                                       value="<?php echo htmlspecialchars($clave ?? ''); ?>"
                                       placeholder="Ej: MAT101" 
                                       required>
                                <div class="form-text">Codigo unico de la materia</div>
                            </div>
                        </div>
                        
                        <!-- SECCION 1 CONTINUACION: Semestre, Tipo y Creditos -->
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label for="semestre_id" class="form-label">
                                    Semestre <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="semestre_id" name="semestre_id" required>
                                    <option value="">Seleccionar semestre</option>
                                    <?php foreach ($semestres as $sem): ?>
                                        <option value="<?php echo $sem['id']; ?>" 
                                            <?php echo ($semestre_id ?? 0) == $sem['id'] ? 'selected' : ''; ?>>
                                            <?php echo $sem['numero']; ?>° <?php echo ucfirst($sem['tipo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="tipo_materia" class="form-label">
                                    Tipo <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="tipo_materia" name="tipo_materia" required>
                                    <option value="tronco" <?php echo ($tipo_materia ?? '') == 'tronco' ? 'selected' : ''; ?>>Tronco Comun</option>
                                    <option value="especialidad" <?php echo ($tipo_materia ?? '') == 'especialidad' ? 'selected' : ''; ?>>Especialidad</option>
                                    <option value="nucleo" <?php echo ($tipo_materia ?? '') == 'nucleo' ? 'selected' : ''; ?>>Nucleo</option>
                                </select>
                                <div class="form-text" id="info_tipo">Materias del tronco comun</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="creditos" class="form-label">Creditos</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="creditos" 
                                       name="creditos" 
                                       value="<?php echo $creditos ?? 5; ?>"
                                       min="1"
                                       max="10">
                            </div>
                        </div>
                        
                        <!-- Campos condicionales -->
                        <div class="row g-3 mt-1">
                            <div class="col-md-6" id="div_especialidad" style="display: none;">
                                <label for="especialidad_id" class="form-label">Especialidad</label>
                                <select class="form-select" id="especialidad_id" name="especialidad_id">
                                    <option value="">Seleccionar especialidad</option>
                                    <?php foreach ($especialidades as $esp): ?>
                                        <option value="<?php echo $esp['id']; ?>">
                                            <?php echo htmlspecialchars($esp['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6" id="div_nucleo" style="display: none;">
                                <label for="nucleo_id" class="form-label">Nucleo</label>
                                <select class="form-select" id="nucleo_id" name="nucleo_id">
                                    <option value="">Seleccionar nucleo</option>
                                    <?php foreach ($nucleos as $nuc): ?>
                                        <option value="<?php echo $nuc['id']; ?>">
                                            <?php echo htmlspecialchars($nuc['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- SECCION 2: CONFIGURACION DE HORARIO -->
                        <div class="section-title mt-4">
                            <i class="fas fa-clock"></i> Configuracion de Horario
                            <small class="text-muted ms-2 fw-normal">(Lunes a Viernes)</small>
                        </div>
                        
                        <!-- Switch -->
                        <div class="switch-container" id="switchContainer" onclick="toggleSwitch()">
                            <div class="switch">
                                <input type="checkbox" id="crear_horario" name="crear_horario" value="1">
                                <span class="slider"></span>
                            </div>
                            <div class="switch-label">
                                Asignar horario a esta materia
                                <small>Define los dias y horas en que se imparte</small>
                            </div>
                        </div>
                        
                        <!-- Seccion de horario -->
                        <div id="seccion_horario" style="display: none;" class="horario-section mt-3">
                            <!-- Maestro y Salon -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="maestro_id" class="form-label">
                                        Maestro <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="maestro_id" name="maestro_id">
                                        <option value="">Seleccionar maestro</option>
                                        <?php foreach ($maestros as $m): ?>
                                            <option value="<?php echo $m['id']; ?>">
                                                <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido_paterno']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="salon_id" class="form-label">
                                        Salon <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="salon_id" name="salon_id">
                                        <option value="">Seleccionar salon</option>
                                        <?php foreach ($salones as $s): ?>
                                            <option value="<?php echo $s['id']; ?>">
                                                <?php echo htmlspecialchars($s['nombre']); ?>
                                                <span class="text-muted">(Cap: <?php echo $s['capacidad']; ?>)</span>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Tabla de dias -->
                            <div class="table-responsive">
                                <table class="table horario-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 18%;">Dia</th>
                                            <th style="width: 15%;">Activo</th>
                                            <th style="width: 33%;">Hora de Inicio</th>
                                            <th style="width: 34%;">Hora de Fin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
                                        foreach ($dias_semana as $dia):
                                        ?>
                                        <tr>
                                            <td class="day-name">
                                                <i class="fas fa-calendar-day me-2 text-primary"></i>
                                                <?php echo $dia; ?>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="dias[]" value="<?php echo $dia; ?>" 
                                                       id="dia_<?php echo $dia; ?>"
                                                       class="form-check-input"
                                                       onchange="toggleHorasDia(this, '<?php echo $dia; ?>')">
                                            </td>
                                            <td>
                                                <input type="time" class="form-control" 
                                                       name="horas_inicio[<?php echo $dia; ?>]" 
                                                       id="inicio_<?php echo $dia; ?>"
                                                       disabled>
                                            </td>
                                            <td>
                                                <input type="time" class="form-control" 
                                                       name="horas_fin[<?php echo $dia; ?>]" 
                                                       id="fin_<?php echo $dia; ?>"
                                                       disabled>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Contador -->
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="dias-counter">
                                        <i class="fas fa-calendar-check"></i>
                                        Dias seleccionados: 
                                        <span class="badge bg-primary" id="contador_dias">0</span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> 
                                        Selecciona los dias y asigna horarios
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Vista previa -->
                            <div id="vista_previa_container" style="display: none;" class="mt-3">
                                <div class="preview-container active">
                                    <div class="preview-title">
                                        <i class="fas fa-eye"></i> Vista Previa del Horario
                                    </div>
                                    <div id="resumen_horario"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- BOTONES -->
                        <div class="d-flex gap-3 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="fas fa-save me-2"></i> Guardar Materia
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                <i class="fas fa-arrow-left me-2"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// FUNCIONES
// ============================================

// Toggle del switch
function toggleSwitch() {
    var checkbox = document.getElementById('crear_horario');
    var container = document.getElementById('switchContainer');
    var seccion = document.getElementById('seccion_horario');
    
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        container.classList.add('active');
        seccion.style.display = 'block';
        var selects = document.querySelectorAll('#seccion_horario select');
        for (var i = 0; i < selects.length; i++) {
            selects[i].required = true;
        }
    } else {
        container.classList.remove('active');
        seccion.style.display = 'none';
        var selects = document.querySelectorAll('#seccion_horario select');
        for (var i = 0; i < selects.length; i++) {
            selects[i].required = false;
        }
        var checkboxes = document.querySelectorAll('input[name="dias[]"]');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = false;
            var dia = checkboxes[i].value;
            document.getElementById('inicio_' + dia).disabled = true;
            document.getElementById('fin_' + dia).disabled = true;
            document.getElementById('inicio_' + dia).value = '';
            document.getElementById('fin_' + dia).value = '';
        }
        actualizarContador();
        document.getElementById('vista_previa_container').style.display = 'none';
    }
}

// Habilitar/deshabilitar campos de hora
function toggleHorasDia(checkbox, dia) {
    var inicio = document.getElementById('inicio_' + dia);
    var fin = document.getElementById('fin_' + dia);
    
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

// Actualizar contador
function actualizarContador() {
    var seleccionados = document.querySelectorAll('input[name="dias[]"]:checked');
    document.getElementById('contador_dias').textContent = seleccionados.length;
}

// Actualizar vista previa
function actualizarVistaPrevia() {
    var seleccionados = document.querySelectorAll('input[name="dias[]"]:checked');
    var container = document.getElementById('vista_previa_container');
    var resumen = document.getElementById('resumen_horario');
    
    if (seleccionados.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    
    var maestro = document.getElementById('maestro_id');
    var salon = document.getElementById('salon_id');
    var ordenDias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
    
    var html = '<div class="table-responsive"><table class="table preview-table">';
    html += '<thead><tr><th>Dia</th><th>Hora Inicio</th><th>Hora Fin</th></tr></thead>';
    html += '<tbody>';
    
    for (var i = 0; i < ordenDias.length; i++) {
        var dia = ordenDias[i];
        var seleccionado = false;
        for (var j = 0; j < seleccionados.length; j++) {
            if (seleccionados[j].value === dia) {
                seleccionado = true;
                break;
            }
        }
        
        if (seleccionado) {
            var inicio = document.getElementById('inicio_' + dia);
            var fin = document.getElementById('fin_' + dia);
            html += '<tr><td><strong>' + dia + '</strong></td>';
            html += '<td>' + (inicio.value || '--:--') + '</td>';
            html += '<td>' + (fin.value || '--:--') + '</td></tr>';
        }
    }
    
    html += '</tbody></table></div>';
    
    var info = '<div class="mt-2 d-flex gap-2 flex-wrap">';
    if (maestro.value) {
        var maestroText = maestro.options[maestro.selectedIndex]?.text || '';
        info += '<span class="badge bg-warning text-dark"><i class="fas fa-user-tie me-1"></i> ' + maestroText + '</span>';
    }
    if (salon.value) {
        var salonText = salon.options[salon.selectedIndex]?.text || '';
        info += '<span class="badge bg-info"><i class="fas fa-door-open me-1"></i> ' + salonText + '</span>';
    }
    info += '<span class="badge bg-secondary"><i class="fas fa-calendar-day me-1"></i> ' + seleccionados.length + ' dias/semana</span>';
    info += '</div>';
    
    resumen.innerHTML = html + info;
}

// Eventos para actualizar vista previa en tiempo real
document.querySelectorAll('input[name^="horas_inicio"]').forEach(function(input) {
    input.addEventListener('change', actualizarVistaPrevia);
    input.addEventListener('input', actualizarVistaPrevia);
});

document.querySelectorAll('input[name^="horas_fin"]').forEach(function(input) {
    input.addEventListener('change', actualizarVistaPrevia);
    input.addEventListener('input', actualizarVistaPrevia);
});

document.querySelectorAll('#maestro_id, #salon_id').forEach(function(select) {
    select.addEventListener('change', actualizarVistaPrevia);
});

// Mostrar/ocultar campos segun tipo
document.getElementById('tipo_materia').addEventListener('change', function() {
    var tipo = this.value;
    var divEspecialidad = document.getElementById('div_especialidad');
    var divNucleo = document.getElementById('div_nucleo');
    var infoTipo = document.getElementById('info_tipo');
    
    divEspecialidad.style.display = 'none';
    divNucleo.style.display = 'none';
    
    if (tipo === 'especialidad') {
        divEspecialidad.style.display = 'block';
        infoTipo.textContent = 'Materia de especialidad';
    } else if (tipo === 'nucleo') {
        divNucleo.style.display = 'block';
        infoTipo.textContent = 'Materia de nucleo';
    } else {
        infoTipo.textContent = 'Materia del tronco comun';
    }
});

// Sugerir clave
document.getElementById('nombre').addEventListener('input', function() {
    var claveInput = document.getElementById('clave');
    if (!claveInput.value) {
        var nombre = this.value;
        var palabras = nombre.split(' ');
        if (palabras.length >= 2) {
            var iniciales = '';
            for (var i = 0; i < palabras.length; i++) {
                iniciales += palabras[i].charAt(0).toUpperCase();
            }
            var semestre = document.getElementById('semestre_id');
            var semestreText = semestre.options[semestre.selectedIndex]?.text || '';
            var numero = semestreText.match(/\d+/);
            if (numero) {
                claveInput.placeholder = 'Ej: ' + iniciales + numero[0] + '01';
            }
        }
    }
});

// Validar formulario
document.getElementById('formMateria').addEventListener('submit', function(e) {
    var crearHorario = document.getElementById('crear_horario').checked;
    
    if (crearHorario) {
        var seleccionados = document.querySelectorAll('input[name="dias[]"]:checked');
        var maestro = document.getElementById('maestro_id');
        var salon = document.getElementById('salon_id');
        
        if (seleccionados.length === 0) {
            e.preventDefault();
            alert('Debes seleccionar al menos un dia para el horario');
            return false;
        }
        
        if (!maestro.value) {
            e.preventDefault();
            alert('Debes seleccionar un maestro');
            return false;
        }
        
        if (!salon.value) {
            e.preventDefault();
            alert('Debes seleccionar un salon');
            return false;
        }
        
        var error = false;
        for (var i = 0; i < seleccionados.length; i++) {
            var dia = seleccionados[i].value;
            var inicio = document.getElementById('inicio_' + dia);
            var fin = document.getElementById('fin_' + dia);
            
            if (!inicio.value || !fin.value) {
                alert('El dia ' + dia + ' no tiene horario definido');
                error = true;
            }
            
            if (inicio.value >= fin.value) {
                alert('La hora de inicio debe ser menor que la hora de fin en ' + dia);
                error = true;
            }
        }
        
        if (error) {
            e.preventDefault();
            return false;
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>