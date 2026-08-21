<?php
// modules/grupos/editar.php - Editar grupo existente
session_start();

$page_title = 'Editar Grupo';
$page_icon = 'edit';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$mensaje = '';
$tipo_mensaje = '';

// Obtener el ID del grupo
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener datos del grupo
$sql_grupo = "SELECT 
                g.*, 
                s.numero as semestre_numero,
                s.tipo as semestre_tipo
            FROM grupos g
            JOIN semestres s ON g.semestre_id = s.id
            WHERE g.id = ? AND g.activo = 1";

$grupo = obtenerRegistro($sql_grupo, [$id]);

if (!$grupo) {
    header('Location: index.php');
    exit;
}

// Obtener semestres
$semestres = obtenerRegistros("SELECT * FROM semestres WHERE activo = 1 ORDER BY numero ASC");

// Obtener especialidades
$especialidades = obtenerRegistros("SELECT * FROM especialidades WHERE activo = 1 ORDER BY nombre ASC");

// Obtener núcleos
$nucleos = obtenerRegistros("SELECT * FROM nucleos WHERE activo = 1 ORDER BY orden ASC");

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $semestre_id = intval($_POST['semestre_id'] ?? 0);
    $especialidad_id = !empty($_POST['especialidad_id']) ? intval($_POST['especialidad_id']) : null;
    $nucleo_actual = !empty($_POST['nucleo_actual']) ? intval($_POST['nucleo_actual']) : null;
    $capacidad = intval($_POST['capacidad'] ?? 30);
    
    if (empty($nombre)) {
        $mensaje = 'El nombre del grupo es obligatorio';
        $tipo_mensaje = 'danger';
    } elseif ($semestre_id <= 0) {
        $mensaje = 'Debes seleccionar un semestre';
        $tipo_mensaje = 'danger';
    } else {
        // Verificar que no exista otro grupo con el mismo nombre y semestre
        $sql_verificar = "SELECT id FROM grupos WHERE nombre = ? AND semestre_id = ? AND id != ?";
        $existe = obtenerRegistro($sql_verificar, [$nombre, $semestre_id, $id]);
        
        if ($existe) {
            $mensaje = 'Ya existe otro grupo con ese nombre en el semestre seleccionado';
            $tipo_mensaje = 'danger';
        } else {
            $sql = "UPDATE grupos SET 
                    nombre = ?, 
                    semestre_id = ?, 
                    especialidad_id = ?, 
                    nucleo_actual = ?, 
                    capacidad = ? 
                    WHERE id = ?";
            $params = [$nombre, $semestre_id, $especialidad_id, $nucleo_actual, $capacidad, $id];
            $types = "siiiii";
            
            $resultado = ejecutarUpdate($sql, $params, $types);
            
            if ($resultado !== false) {
                $_SESSION['mensaje'] = 'Grupo actualizado exitosamente';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $mensaje = 'Error al actualizar el grupo';
                $tipo_mensaje = 'danger';
            }
        }
    }
}

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS PARA EDITAR GRUPO
   ============================================ */
.grupo-form .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.grupo-form .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.grupo-form .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.grupo-form .card-header small {
    color: #6c757d;
    font-size: 0.85rem;
}

.grupo-form .section-title {
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

.grupo-form .section-title i {
    color: #1976d2;
}

.grupo-form .form-control,
.grupo-form .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 10px 15px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.grupo-form .form-control:focus,
.grupo-form .form-select:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
}

.grupo-form .form-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.grupo-form .form-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 4px;
}

/* Badge de estado */
.badge-info-grupo {
    background: #e3f2fd;
    color: #1565c0;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 0.85rem;
}

.badge-info-grupo i {
    margin-right: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .grupo-form .card-header {
        padding: 15px;
    }
}
</style>

<div class="grupo-form">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-edit text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Editar Grupo</h5>
                            <small>Modifica los datos del grupo <strong><?php echo htmlspecialchars($grupo['nombre']); ?></strong></small>
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
                    
                    <!-- Información del grupo -->
                    <div class="mb-4">
                        <span class="badge-info-grupo">
                            <i class="fas fa-info-circle"></i>
                            Semestre <?php echo $grupo['semestre_numero']; ?>° - 
                            <?php echo ucfirst($grupo['semestre_tipo']); ?>
                        </span>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="row g-3">
                            <!-- Nombre del grupo -->
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">
                                    Nombre del Grupo <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?php echo htmlspecialchars($grupo['nombre']); ?>"
                                       placeholder="Ej: 101, 201, 3A" 
                                       required>
                                <div class="form-text">Código único del grupo</div>
                            </div>
                            
                            <!-- Capacidad -->
                            <div class="col-md-6">
                                <label for="capacidad" class="form-label">Capacidad</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="capacidad" 
                                       name="capacidad" 
                                       value="<?php echo $grupo['capacidad']; ?>"
                                       min="1"
                                       max="60">
                                <div class="form-text">Número de estudiantes</div>
                            </div>
                            
                            <!-- Semestre -->
                            <div class="col-md-6">
                                <label for="semestre_id" class="form-label">
                                    Semestre <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="semestre_id" name="semestre_id" required>
                                    <option value="">Seleccionar semestre</option>
                                    <?php foreach ($semestres as $sem): ?>
                                        <option value="<?php echo $sem['id']; ?>" 
                                            <?php echo $grupo['semestre_id'] == $sem['id'] ? 'selected' : ''; ?>>
                                            <?php echo $sem['numero']; ?>° <?php echo ucfirst($sem['tipo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Especialidad (solo para semestres 3-4) -->
                            <div class="col-md-6" id="div_especialidad" 
                                 style="display: <?php echo in_array($grupo['semestre_id'], [3,4]) ? 'block' : 'none'; ?>;">
                                <label for="especialidad_id" class="form-label">Especialidad</label>
                                <select class="form-select" id="especialidad_id" name="especialidad_id">
                                    <option value="">Sin especialidad</option>
                                    <?php foreach ($especialidades as $esp): ?>
                                        <option value="<?php echo $esp['id']; ?>" 
                                            <?php echo $grupo['especialidad_id'] == $esp['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($esp['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Obligatorio para semestres 3 y 4</div>
                            </div>
                            
                            <!-- Núcleo actual (solo para semestres 5-6) -->
                            <div class="col-md-6" id="div_nucleo" 
                                 style="display: <?php echo in_array($grupo['semestre_id'], [5,6]) ? 'block' : 'none'; ?>;">
                                <label for="nucleo_actual" class="form-label">Núcleo Actual</label>
                                <select class="form-select" id="nucleo_actual" name="nucleo_actual">
                                    <option value="">Sin núcleo</option>
                                    <?php foreach ($nucleos as $nuc): ?>
                                        <option value="<?php echo $nuc['id']; ?>" 
                                            <?php echo $grupo['nucleo_actual'] == $nuc['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($nuc['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Obligatorio para semestres 5 y 6</div>
                            </div>
                            
                            <!-- Botones -->
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary px-4 py-2">
                                    <i class="fas fa-save me-2"></i> Actualizar Grupo
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                    <i class="fas fa-arrow-left me-2"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Ayuda rápida -->
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle text-info"></i> Estructura de Grupos COBAO</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <span class="badge bg-info">1° - 2° Semestre</span>
                            <small class="d-block text-muted">Básico - Sin especialidad</small>
                        </div>
                        <div class="col-md-4">
                            <span class="badge bg-warning">3° - 4° Semestre</span>
                            <small class="d-block text-muted">Especialidad seleccionada</small>
                        </div>
                        <div class="col-md-4">
                            <span class="badge bg-purple">5° - 6° Semestre</span>
                            <small class="d-block text-muted">Núcleo asignado</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// MOSTRAR/OCULTAR CAMPOS SEGÚN SEMESTRE
// ============================================

document.getElementById('semestre_id').addEventListener('change', function() {
    const semestreId = parseInt(this.value);
    const divEspecialidad = document.getElementById('div_especialidad');
    const divNucleo = document.getElementById('div_nucleo');
    const selectedOption = this.options[this.selectedIndex];
    const text = selectedOption.text.toLowerCase();
    
    divEspecialidad.style.display = 'none';
    divNucleo.style.display = 'none';
    
    if (text.includes('especialidad') || semestreId === 3 || semestreId === 4) {
        divEspecialidad.style.display = 'block';
    } else if (text.includes('nucleo') || semestreId === 5 || semestreId === 6) {
        divNucleo.style.display = 'block';
    }
});
</script>

<style>
.bg-purple {
    background-color: #9c27b0 !important;
    color: white !important;
}
</style>

<?php include '../../includes/footer.php'; ?>