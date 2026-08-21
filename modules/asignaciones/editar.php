<?php
// modules/asignaciones/editar.php - Editar asignaciones de una materia
session_start();

$page_title = 'Editar Asignación de Materia';
$page_icon = 'edit';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$materia_id = intval($_GET['materia_id'] ?? 0);

if ($materia_id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener información de la materia
$sql_materia = "SELECT m.*, s.numero as semestre_numero, s.tipo as semestre_tipo
                FROM materias m
                JOIN semestres s ON m.semestre_id = s.id
                WHERE m.id = ? AND m.activo = 1";
$materia = obtenerRegistro($sql_materia, [$materia_id]);

if (!$materia) {
    header('Location: index.php');
    exit;
}

// Obtener asignaciones actuales (grupos y maestros)
$asignaciones = obtenerRegistros("
    SELECT 
        mg.id as asignacion_id,
        mg.grupo_id,
        mg.maestro_id,
        g.nombre as grupo_nombre,
        g.semestre_id,
        CONCAT(ma.nombre, ' ', ma.apellido_paterno) as maestro_nombre
    FROM materias_grupos mg
    JOIN grupos g ON mg.grupo_id = g.id
    JOIN maestros ma ON mg.maestro_id = ma.id
    WHERE mg.materia_id = ?
    ORDER BY g.nombre
", [$materia_id]);

// Obtener todos los grupos
$grupos = obtenerRegistros("SELECT * FROM grupos WHERE activo = 1 ORDER BY semestre_id, nombre");

// Obtener todos los maestros
$maestros = obtenerRegistros("SELECT * FROM maestros WHERE activo = 1 AND disponible = 1 ORDER BY nombre, apellido_paterno");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $grupo_id = intval($_POST['grupo_id'] ?? 0);
    $maestro_id = intval($_POST['maestro_id'] ?? 0);
    $asignacion_id = intval($_POST['asignacion_id'] ?? 0);
    
    if ($accion === 'agregar' && $grupo_id > 0 && $maestro_id > 0) {
        // Verificar si ya existe
        $sql_verificar = "SELECT id FROM materias_grupos WHERE materia_id = ? AND grupo_id = ? AND maestro_id = ?";
        $existe = obtenerRegistro($sql_verificar, [$materia_id, $grupo_id, $maestro_id]);
        
        if ($existe) {
            $mensaje = 'Esta asignación ya existe (misma materia, mismo grupo, mismo maestro)';
            $tipo_mensaje = 'warning';
        } else {
            $sql = "INSERT INTO materias_grupos (materia_id, grupo_id, maestro_id) VALUES (?, ?, ?)";
            $resultado = ejecutarUpdate($sql, [$materia_id, $grupo_id, $maestro_id]);
            
            if ($resultado) {
                $_SESSION['mensaje'] = 'Asignación agregada correctamente';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: editar.php?materia_id=' . $materia_id);
                exit;
            }
        }
    } elseif ($accion === 'quitar' && $asignacion_id > 0) {
        $sql = "DELETE FROM materias_grupos WHERE id = ? AND materia_id = ?";
        $resultado = ejecutarUpdate($sql, [$asignacion_id, $materia_id]);
        
        if ($resultado) {
            $_SESSION['mensaje'] = 'Asignación removida correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: editar.php?materia_id=' . $materia_id);
            exit;
        }
    }
}

// Recuperar mensaje de sesión
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Recargar asignaciones
$asignaciones = obtenerRegistros("
    SELECT 
        mg.id as asignacion_id,
        mg.grupo_id,
        mg.maestro_id,
        g.nombre as grupo_nombre,
        g.semestre_id,
        CONCAT(ma.nombre, ' ', ma.apellido_paterno) as maestro_nombre
    FROM materias_grupos mg
    JOIN grupos g ON mg.grupo_id = g.id
    JOIN maestros ma ON mg.maestro_id = ma.id
    WHERE mg.materia_id = ?
    ORDER BY g.nombre
", [$materia_id]);

include '../../includes/header.php';
?>

<style>
.editar-asignacion .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.editar-asignacion .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.editar-asignacion .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.asignacion-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    margin-bottom: 8px;
    background: white;
    transition: all 0.3s ease;
}

.asignacion-item:hover {
    border-color: #8B0000;
    box-shadow: 0 2px 10px rgba(139, 0, 0, 0.08);
}

.asignacion-item .info {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.asignacion-item .info .grupo {
    font-weight: 700;
    color: #1a237e;
}

.asignacion-item .info .maestro {
    color: #495057;
}

.asignacion-item .info .separator {
    color: #dee2e6;
}

.btn-danger-cobao {
    background: #c62828;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 5px 12px;
    transition: all 0.3s ease;
    font-size: 0.75rem;
}

.btn-danger-cobao:hover {
    background: #b71c1c;
    transform: translateY(-1px);
    color: white;
}

.btn-success-cobao {
    background: #2e7d32;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-success-cobao:hover {
    background: #1b5e20;
    transform: translateY(-2px);
    color: white;
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

.info-box {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px 20px;
    border: 1px solid #e9ecef;
}

.info-box .label {
    color: #6c757d;
    font-size: 0.8rem;
}

.info-box .value {
    font-weight: 600;
    color: #1a237e;
    font-size: 1rem;
}

.badge-semestre {
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
}

.badge-semestre.basico { background: #e3f2fd; color: #1565c0; }
.badge-semestre.especialidad { background: #fff3e0; color: #e65100; }
.badge-semestre.nucleo { background: #f3e5f5; color: #6a1b9a; }

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

.editar-asignacion .card {
    animation: fadeInUp 0.4s ease;
}
</style>

<div class="editar-asignacion">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-edit text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Editar Asignación</h5>
                            <small><?php echo htmlspecialchars($materia['nombre']); ?> (<?php echo htmlspecialchars($materia['clave']); ?>)</small>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <?php if (isset($mensaje) && $mensaje): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Información de la materia -->
                    <div class="info-box mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="label">Materia</div>
                                <div class="value"><?php echo htmlspecialchars($materia['nombre']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($materia['clave']); ?></small>
                            </div>
                            <div class="col-md-4">
                                <div class="label">Semestre</div>
                                <div class="value">
                                    <?php 
                                    $clase_sem = 'basico';
                                    if ($materia['semestre_numero'] >= 3 && $materia['semestre_numero'] <= 4) {
                                        $clase_sem = 'especialidad';
                                    } elseif ($materia['semestre_numero'] >= 5) {
                                        $clase_sem = 'nucleo';
                                    }
                                    ?>
                                    <span class="badge-semestre <?php echo $clase_sem; ?>">
                                        <?php echo $materia['semestre_numero']; ?>°
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="label">Total Asignaciones</div>
                                <div class="value"><?php echo count($asignaciones); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Asignaciones actuales -->
                    <h6 class="mb-3">
                        <i class="fas fa-users text-primary"></i>
                        Asignaciones Actuales
                        <span class="badge bg-secondary"><?php echo count($asignaciones); ?></span>
                    </h6>
                    
                    <?php if (count($asignaciones) > 0): ?>
                        <?php foreach ($asignaciones as $a): ?>
                            <div class="asignacion-item">
                                <div class="info">
                                    <span class="grupo">
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo htmlspecialchars($a['grupo_nombre']); ?>
                                    </span>
                                    <span class="separator">→</span>
                                    <span class="maestro">
                                        <i class="fas fa-user-tie me-1"></i>
                                        <?php echo htmlspecialchars($a['maestro_nombre']); ?>
                                    </span>
                                </div>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="accion" value="quitar">
                                    <input type="hidden" name="asignacion_id" value="<?php echo $a['asignacion_id']; ?>">
                                    <button type="submit" class="btn-danger-cobao" onclick="return confirm('¿Quitar esta asignación?')">
                                        <i class="fas fa-times me-1"></i> Quitar
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay asignaciones para esta materia.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Agregar nueva asignación -->
                    <hr class="my-4">
                    
                    <h6 class="mb-3">
                        <i class="fas fa-plus-circle text-success"></i>
                        Agregar Asignación
                    </h6>
                    
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <select class="form-select" name="grupo_id" required>
                                    <option value="">Seleccionar grupo</option>
                                    <?php 
                                    $grupos_asignados = array_column($asignaciones, 'grupo_id');
                                    foreach ($grupos as $g): 
                                        $ya_asignado = in_array($g['id'], $grupos_asignados);
                                    ?>
                                        <option value="<?php echo $g['id']; ?>">
                                            <?php echo htmlspecialchars($g['nombre']); ?> 
                                            (<?php echo $g['semestre_id']; ?>°)
                                            <?php echo $ya_asignado ? '(ya asignado)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <select class="form-select" name="maestro_id" required>
                                    <option value="">Seleccionar maestro</option>
                                    <?php foreach ($maestros as $m): ?>
                                        <option value="<?php echo $m['id']; ?>">
                                            <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido_paterno']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="hidden" name="accion" value="agregar">
                                <button type="submit" class="btn-success-cobao w-100">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Botones -->
                    <div class="d-flex gap-3 mt-4 pt-3 border-top">
                        <a href="index.php" class="btn-secondary-cobao">
                            <i class="fas fa-arrow-left me-2"></i> Volver
                        </a>
                        <a href="../materias/ver.php?id=<?php echo $materia_id; ?>" class="btn-primary-cobao">
                            <i class="fas fa-eye me-2"></i> Ver Materia
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>