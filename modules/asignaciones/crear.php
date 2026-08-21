<?php
// modules/asignaciones/crear.php - Crear nueva asignación
session_start();

$page_title = 'Nueva Asignación';
$page_icon = 'plus-circle';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$mensaje = '';
$tipo_mensaje = '';

// Obtener datos para los selects
$grupos = obtenerRegistros("SELECT * FROM grupos WHERE activo = 1 ORDER BY semestre_id, nombre");
$materias = obtenerRegistros("SELECT * FROM materias WHERE activo = 1 ORDER BY semestre_id, nombre");
$maestros = obtenerRegistros("SELECT * FROM maestros WHERE activo = 1 AND disponible = 1 ORDER BY nombre, apellido_paterno");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materia_id = intval($_POST['materia_id'] ?? 0);
    $grupo_id = intval($_POST['grupo_id'] ?? 0);
    $maestro_id = intval($_POST['maestro_id'] ?? 0);
    
    if ($materia_id <= 0) {
        $mensaje = 'Debes seleccionar una materia';
        $tipo_mensaje = 'danger';
    } elseif ($grupo_id <= 0) {
        $mensaje = 'Debes seleccionar un grupo';
        $tipo_mensaje = 'danger';
    } elseif ($maestro_id <= 0) {
        $mensaje = 'Debes seleccionar un maestro';
        $tipo_mensaje = 'danger';
    } else {
        // Verificar que no exista duplicado exacto
        $sql_verificar = "SELECT id FROM materias_grupos WHERE materia_id = ? AND grupo_id = ? AND maestro_id = ?";
        $existe = obtenerRegistro($sql_verificar, [$materia_id, $grupo_id, $maestro_id]);
        
        if ($existe) {
            $mensaje = 'Esta asignación ya existe (misma materia, mismo grupo, mismo maestro)';
            $tipo_mensaje = 'warning';
        } else {
            $sql = "INSERT INTO materias_grupos (materia_id, grupo_id, maestro_id) VALUES (?, ?, ?)";
            $resultado = ejecutarUpdate($sql, [$materia_id, $grupo_id, $maestro_id]);
            
            if ($resultado) {
                $_SESSION['mensaje'] = 'Asignación creada exitosamente';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $mensaje = 'Error al crear la asignación';
                $tipo_mensaje = 'danger';
            }
        }
    }
}

include '../../includes/header.php';
?>

<style>
.asignacion-form .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.asignacion-form .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.asignacion-form .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.asignacion-form .form-control,
.asignacion-form .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 10px 15px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.asignacion-form .form-control:focus,
.asignacion-form .form-select:focus {
    border-color: #8B0000;
    box-shadow: 0 0 0 4px rgba(139, 0, 0, 0.1);
}

.asignacion-form .form-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.asignacion-form .form-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 4px;
}

.btn-primary-cobao {
    background: #8B0000;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 25px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-primary-cobao:hover {
    background: #5C0000;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 0, 0, 0.3);
    color: white;
}

.btn-secondary-cobao {
    background: #e9ecef;
    color: #495057;
    border: none;
    border-radius: 10px;
    padding: 10px 25px;
    transition: all 0.3s ease;
    font-weight: 500;
    text-decoration: none;
}

.btn-secondary-cobao:hover {
    background: #dee2e6;
    transform: translateY(-2px);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.asignacion-form .card {
    animation: fadeInUp 0.4s ease;
}
</style>

<div class="asignacion-form">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-plus-circle text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Nueva Asignación</h5>
                            <small>Asigna una materia a un grupo con un maestro específico</small>
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
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> Puedes asignar la misma materia a un grupo con diferentes maestros. 
                        Esto creará múltiples registros en la asignación.
                    </div>
                    
                    <form method="POST" action="">
                        <div class="row g-3">
                            <!-- Selección de materia -->
                            <div class="col-md-12">
                                <label for="materia_id" class="form-label">
                                    Materia <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="materia_id" name="materia_id" required>
                                    <option value="">Seleccionar materia</option>
                                    <?php foreach ($materias as $m): ?>
                                        <option value="<?php echo $m['id']; ?>">
                                            <?php echo htmlspecialchars($m['clave']); ?> - <?php echo htmlspecialchars($m['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Selección de grupo -->
                            <div class="col-md-6">
                                <label for="grupo_id" class="form-label">
                                    Grupo <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="grupo_id" name="grupo_id" required>
                                    <option value="">Seleccionar grupo</option>
                                    <?php foreach ($grupos as $g): ?>
                                        <option value="<?php echo $g['id']; ?>">
                                            <?php echo htmlspecialchars($g['nombre']); ?> 
                                            (<?php echo obtenerNombreSemestre($g['semestre_id']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Selección de maestro -->
                            <div class="col-md-6">
                                <label for="maestro_id" class="form-label">
                                    Maestro <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="maestro_id" name="maestro_id" required>
                                    <option value="">Seleccionar maestro</option>
                                    <?php foreach ($maestros as $m): ?>
                                        <option value="<?php echo $m['id']; ?>">
                                            <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido_paterno']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Puede ser el mismo maestro o diferente para la misma materia</div>
                            </div>
                            
                            <!-- Botones -->
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn-primary-cobao">
                                    <i class="fas fa-save me-2"></i> Crear Asignación
                                </button>
                                <a href="index.php" class="btn-secondary-cobao">
                                    <i class="fas fa-arrow-left me-2"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Ejemplo -->
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-lightbulb text-warning"></i> Ejemplo de uso</h6>
                    <small class="text-muted">
                        Si necesitas que <strong>dos maestros</strong> impartan la <strong>misma materia</strong> en el <strong>mismo grupo</strong>:
                        <br>
                        <span class="badge bg-primary">Matemáticas I</span>
                        <span class="badge bg-success">Grupo 101</span>
                        <span class="badge bg-warning text-dark">Maestro A</span>
                        <span class="badge bg-info">y</span>
                        <span class="badge bg-warning text-dark">Maestro B</span>
                        <br>
                        <span class="text-muted">Simplemente crea dos asignaciones diferentes con los mismos datos pero diferente maestro.</span>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function obtenerNombreSemestre($id) {
    $semestres = [
        1 => '1° Semestre',
        2 => '2° Semestre',
        3 => '3° Semestre',
        4 => '4° Semestre',
        5 => '5° Semestre',
        6 => '6° Semestre'
    ];
    return $semestres[$id] ?? 'Desconocido';
}
?>

<?php include '../../includes/footer.php'; ?>