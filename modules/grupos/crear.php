<?php
// modules/grupos/crear.php - Crear nuevo grupo (VERSIÓN MEJORADA)
session_start();

$page_title = 'Crear Nuevo Grupo';
$page_icon = 'user-plus';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$mensaje = '';
$tipo_mensaje = '';

// Obtener semestres para el select
$semestres = obtenerRegistros("SELECT * FROM semestres WHERE activo = 1 ORDER BY numero ASC");

// Obtener especialidades para el select
$especialidades = obtenerRegistros("SELECT * FROM especialidades WHERE activo = 1 ORDER BY nombre ASC");

// Obtener núcleos para el select
$nucleos = obtenerRegistros("SELECT * FROM nucleos WHERE activo = 1 ORDER BY orden ASC");

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $semestre_id = intval($_POST['semestre_id'] ?? 0);
    $especialidad_id = !empty($_POST['especialidad_id']) ? intval($_POST['especialidad_id']) : null;
    $nucleo_actual = !empty($_POST['nucleo_actual']) ? intval($_POST['nucleo_actual']) : null;
    $capacidad = intval($_POST['capacidad'] ?? 30);
    
    // Validaciones
    if (empty($nombre)) {
        $mensaje = 'El nombre del grupo es obligatorio';
        $tipo_mensaje = 'danger';
    } elseif ($semestre_id <= 0) {
        $mensaje = 'Debes seleccionar un semestre';
        $tipo_mensaje = 'danger';
    } else {
        // Verificar que no exista un grupo con el mismo nombre y semestre
        $sql_verificar = "SELECT id FROM grupos WHERE nombre = ? AND semestre_id = ?";
        $existe = obtenerRegistro($sql_verificar, [$nombre, $semestre_id]);
        
        if ($existe) {
            $mensaje = 'Ya existe un grupo con ese nombre en el semestre seleccionado';
            $tipo_mensaje = 'danger';
        } else {
            // Insertar el nuevo grupo
            $sql = "INSERT INTO grupos (nombre, semestre_id, especialidad_id, nucleo_actual, capacidad) 
                    VALUES (?, ?, ?, ?, ?)";
            $params = [$nombre, $semestre_id, $especialidad_id, $nucleo_actual, $capacidad];
            $types = "siiii";
            
            $resultado = ejecutarUpdate($sql, $params, $types);
            
            if ($resultado) {
                $_SESSION['mensaje'] = 'Grupo creado exitosamente';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $mensaje = 'Error al crear el grupo';
                $tipo_mensaje = 'danger';
            }
        }
    }
}

// Verificar si hay mensaje en sesión
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus text-primary"></i> Nuevo Grupo
                </h5>
                <small class="text-muted">Registra los grupos del COBAO</small>
            </div>
            <div class="card-body">
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row g-3">
                        <!-- Nombre del grupo -->
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre del Grupo *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                                   placeholder="Ej: 101, 201, 3A" 
                                   required>
                            <small class="text-muted">Ejemplo: 101, 102, 201, 3A, etc.</small>
                        </div>
                        
                        <!-- Capacidad -->
                        <div class="col-md-6">
                            <label for="capacidad" class="form-label">Capacidad</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="capacidad" 
                                   name="capacidad" 
                                   value="<?php echo $capacidad ?? 30; ?>"
                                   min="1"
                                   max="60">
                            <small class="text-muted">Número de estudiantes por grupo</small>
                        </div>
                        
                        <!-- Semestre -->
                        <div class="col-md-6">
                            <label for="semestre_id" class="form-label">Semestre *</label>
                            <select class="form-select" id="semestre_id" name="semestre_id" required>
                                <option value="">Seleccionar semestre</option>
                                <?php foreach ($semestres as $sem): ?>
                                    <option value="<?php echo $sem['id']; ?>" 
                                        <?php echo ($semestre_id ?? 0) == $sem['id'] ? 'selected' : ''; ?>>
                                        <?php echo $sem['numero']; ?>° Semestre - <?php echo ucfirst($sem['tipo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted" id="info_semestre">Selecciona el semestre del grupo</small>
                        </div>
                        
                        <!-- Especialidad (solo para semestres 3-4) -->
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
                            <small class="text-muted">Obligatorio para semestres 3 y 4</small>
                        </div>
                        
                        <!-- Núcleo actual (solo para semestres 5-6) -->
                        <div class="col-md-6" id="div_nucleo" style="display: none;">
                            <label for="nucleo_actual" class="form-label">Núcleo Actual</label>
                            <select class="form-select" id="nucleo_actual" name="nucleo_actual">
                                <option value="">Seleccionar núcleo</option>
                                <?php foreach ($nucleos as $nuc): ?>
                                    <option value="<?php echo $nuc['id']; ?>">
                                        <?php echo htmlspecialchars($nuc['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Obligatorio para semestres 5 y 6</small>
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Grupo
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
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
                        <span class="badge bg-info">1° Semestre</span>
                        <small class="d-block text-muted">101, 102, 103, 104</small>
                    </div>
                    <div class="col-md-4">
                        <span class="badge bg-info">2° Semestre</span>
                        <small class="d-block text-muted">201, 202, 203, 204</small>
                    </div>
                    <div class="col-md-4">
                        <span class="badge bg-warning">3°-4° Semestre</span>
                        <small class="d-block text-muted">Con especialidad</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar campos según el semestre seleccionado
document.getElementById('semestre_id').addEventListener('change', function() {
    const semestreId = parseInt(this.value);
    const divEspecialidad = document.getElementById('div_especialidad');
    const divNucleo = document.getElementById('div_nucleo');
    const selectEspecialidad = document.getElementById('especialidad_id');
    const selectNucleo = document.getElementById('nucleo_actual');
    const infoSemestre = document.getElementById('info_semestre');
    
    // Obtener el texto del semestre seleccionado
    const selectedOption = this.options[this.selectedIndex];
    const text = selectedOption.text.toLowerCase();
    
    // Ocultar todo primero
    divEspecialidad.style.display = 'none';
    divNucleo.style.display = 'none';
    selectEspecialidad.required = false;
    selectNucleo.required = false;
    
    // Mostrar según el tipo
    if (text.includes('especialidad')) {
        divEspecialidad.style.display = 'block';
        selectEspecialidad.required = true;
        infoSemestre.textContent = '📌 Semestre con especialidad - Selecciona la especialidad del grupo';
    } else if (text.includes('nucleo')) {
        divNucleo.style.display = 'block';
        selectNucleo.required = true;
        infoSemestre.textContent = '🔄 Semestre con núcleos - Selecciona el núcleo actual';
    } else {
        infoSemestre.textContent = '📚 Semestre básico - Materias comunes';
    }
});

// Sugerir nombre según el semestre seleccionado
document.getElementById('semestre_id').addEventListener('change', function() {
    const semestreId = parseInt(this.value);
    const nombreInput = document.getElementById('nombre');
    
    // Si el campo está vacío, sugerir un nombre
    if (!nombreInput.value) {
        const semestre = this.options[this.selectedIndex].text;
        const numero = semestre.match(/\d+/);
        if (numero) {
            nombreInput.placeholder = `Ej: ${numero[0]}01, ${numero[0]}02, ${numero[0]}A`;
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>