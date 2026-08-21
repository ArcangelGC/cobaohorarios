<?php
// modules/maestros/editar.php - Editar maestro existente
session_start();

$page_title = 'Editar Maestro';
$page_icon = 'edit';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$mensaje = '';
$tipo_mensaje = '';

// Obtener el ID del maestro
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener datos del maestro
$sql_maestro = "SELECT * FROM maestros WHERE id = ? AND activo = 1";
$maestro = obtenerRegistro($sql_maestro, [$id]);

if (!$maestro) {
    header('Location: index.php');
    exit;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
    $apellido_materno = trim($_POST['apellido_materno'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    
    // Validaciones
    if (empty($nombre)) {
        $mensaje = 'El nombre es obligatorio';
        $tipo_mensaje = 'danger';
    } elseif (empty($apellido_paterno)) {
        $mensaje = 'El apellido paterno es obligatorio';
        $tipo_mensaje = 'danger';
    } else {
        // Verificar email duplicado (excepto el propio)
        if (!empty($email)) {
            $sql_verificar = "SELECT id FROM maestros WHERE email = ? AND id != ?";
            $existe = obtenerRegistro($sql_verificar, [$email, $id]);
            if ($existe) {
                $mensaje = 'Ya existe otro maestro con ese email';
                $tipo_mensaje = 'danger';
            }
        }
        
        if (empty($mensaje)) {
            $sql = "UPDATE maestros SET 
                    nombre = ?, 
                    apellido_paterno = ?, 
                    apellido_materno = ?, 
                    email = ?, 
                    telefono = ?, 
                    especialidad = ?, 
                    disponible = ? 
                    WHERE id = ?";
            $params = [$nombre, $apellido_paterno, $apellido_materno, $email, $telefono, $especialidad, $disponible, $id];
            $types = "ssssssii";
            
            $resultado = ejecutarUpdate($sql, $params, $types);
            
            if ($resultado !== false) {
                $_SESSION['mensaje'] = 'Maestro actualizado exitosamente';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $mensaje = 'Error al actualizar el maestro';
                $tipo_mensaje = 'danger';
            }
        }
    }
}

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS PARA EDITAR MAESTRO
   ============================================ */
.maestro-form .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.maestro-form .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.maestro-form .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.maestro-form .card-header small {
    color: #6c757d;
    font-size: 0.85rem;
}

.maestro-form .section-title {
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

.maestro-form .section-title i {
    color: #1976d2;
}

.maestro-form .form-control,
.maestro-form .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 10px 15px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.maestro-form .form-control:focus,
.maestro-form .form-select:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
}

.maestro-form .form-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.maestro-form .form-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 4px;
}

/* Badge de estado */
.badge-info-maestro {
    background: #e3f2fd;
    color: #1565c0;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 0.85rem;
}

.badge-info-maestro i {
    margin-right: 8px;
}

/* Estado actual */
.estado-actual {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.estado-actual.activo {
    background: #e8f5e9;
    color: #2e7d32;
}

.estado-actual.inactivo {
    background: #ffebee;
    color: #c62828;
}

/* Responsive */
@media (max-width: 768px) {
    .maestro-form .card-header {
        padding: 15px;
    }
}
</style>

<div class="maestro-form">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-user-edit text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Editar Maestro</h5>
                            <small>Modifica los datos del maestro <strong><?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno']); ?></strong></small>
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
                    
                    <!-- Información del maestro -->
                    <div class="mb-4">
                        <span class="badge-info-maestro">
                            <i class="fas fa-info-circle"></i>
                            Estado actual: 
                            <?php if ($maestro['disponible']): ?>
                                <span class="estado-actual activo">
                                    <i class="fas fa-check-circle"></i> Activo
                                </span>
                            <?php else: ?>
                                <span class="estado-actual inactivo">
                                    <i class="fas fa-times-circle"></i> Inactivo
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="row g-3">
                            <!-- Nombre -->
                            <div class="col-md-4">
                                <label for="nombre" class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($maestro['nombre']); ?>" required>
                            </div>
                            
                            <!-- Apellido Paterno -->
                            <div class="col-md-4">
                                <label for="apellido_paterno" class="form-label">
                                    Apellido Paterno <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" 
                                       value="<?php echo htmlspecialchars($maestro['apellido_paterno']); ?>" required>
                            </div>
                            
                            <!-- Apellido Materno -->
                            <div class="col-md-4">
                                <label for="apellido_materno" class="form-label">Apellido Materno</label>
                                <input type="text" class="form-control" id="apellido_materno" name="apellido_materno" 
                                       value="<?php echo htmlspecialchars($maestro['apellido_materno']); ?>">
                            </div>
                            
                            <!-- Email -->
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($maestro['email']); ?>"
                                       placeholder="ejemplo@cobao.edu.mx">
                            </div>
                            
                            <!-- Teléfono -->
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" 
                                       value="<?php echo htmlspecialchars($maestro['telefono']); ?>"
                                       placeholder="951-123-4567">
                            </div>
                            
                            <!-- Especialidad -->
                            <div class="col-md-6">
                                <label for="especialidad" class="form-label">Especialidad</label>
                                <input type="text" class="form-control" id="especialidad" name="especialidad" 
                                       value="<?php echo htmlspecialchars($maestro['especialidad']); ?>"
                                       placeholder="Matemáticas, Física, etc.">
                            </div>
                            
                            <!-- Disponible -->
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="disponible" name="disponible" 
                                           <?php echo $maestro['disponible'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="disponible">
                                        <i class="fas fa-check-circle text-success"></i>
                                        <strong>Disponible</strong> para asignar horarios
                                    </label>
                                    <div class="form-text">Si está disponible, podrá recibir asignaciones de horario</div>
                                </div>
                            </div>
                            
                            <!-- Botones -->
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary px-4 py-2">
                                    <i class="fas fa-save me-2"></i> Actualizar Maestro
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
                    <h6><i class="fas fa-info-circle text-info"></i> Información del Maestro</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Registrado: <?php echo date('d/m/Y', strtotime($maestro['created_at'])); ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">
                                <i class="fas fa-id-card me-1"></i>
                                ID: <?php echo $maestro['id']; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>