<?php
// modules/maestros/crear.php - Crear nuevo maestro (VERSIÓN MEJORADA)
session_start();

$page_title = 'Registrar Maestro';
$page_icon = 'user-plus';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$mensaje = '';
$tipo_mensaje = '';

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
        // Verificar email duplicado
        if (!empty($email)) {
            $sql_verificar = "SELECT id FROM maestros WHERE email = ?";
            $existe = obtenerRegistro($sql_verificar, [$email]);
            if ($existe) {
                $mensaje = 'Ya existe un maestro con ese email';
                $tipo_mensaje = 'danger';
            }
        }
        
        if (empty($mensaje)) {
            $sql = "INSERT INTO maestros (nombre, apellido_paterno, apellido_materno, email, telefono, especialidad, disponible, activo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $params = [$nombre, $apellido_paterno, $apellido_materno, $email, $telefono, $especialidad, $disponible];
            $types = "ssssssi";
            
            $resultado = ejecutarUpdate($sql, $params, $types);
            
            if ($resultado) {
                $_SESSION['mensaje'] = 'Maestro registrado exitosamente';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $mensaje = 'Error al registrar el maestro';
                $tipo_mensaje = 'danger';
            }
        }
    }
}

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS PARA REGISTRAR MAESTRO
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

/* Switch personalizado para disponibilidad */
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

/* Badges de ayuda */
.badge-help {
    background: #e3f2fd;
    color: #1565c0;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
}

/* Responsive */
@media (max-width: 768px) {
    .maestro-form .card-header {
        padding: 15px;
    }
    .switch-container {
        padding: 12px 15px;
        flex-wrap: wrap;
    }
}

/* Animación de entrada */
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

.maestro-form .card {
    animation: fadeInUp 0.4s ease;
}
</style>

<div class="maestro-form">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-user-plus text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Registrar Nuevo Maestro</h5>
                            <small>Ingresa los datos del docente del COBAO</small>
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
                    
                    <form method="POST" action="">
                        <!-- SECCION 1: DATOS PERSONALES -->
                        <div class="section-title">
                            <i class="fas fa-user"></i> Datos Personales
                        </div>
                        
                        <div class="row g-3">
                            <!-- Nombre -->
                            <div class="col-md-4">
                                <label for="nombre" class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                                       placeholder="Ej: María" required>
                            </div>
                            
                            <!-- Apellido Paterno -->
                            <div class="col-md-4">
                                <label for="apellido_paterno" class="form-label">
                                    Apellido Paterno <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" 
                                       value="<?php echo htmlspecialchars($apellido_paterno ?? ''); ?>"
                                       placeholder="Ej: García" required>
                            </div>
                            
                            <!-- Apellido Materno -->
                            <div class="col-md-4">
                                <label for="apellido_materno" class="form-label">Apellido Materno</label>
                                <input type="text" class="form-control" id="apellido_materno" name="apellido_materno" 
                                       value="<?php echo htmlspecialchars($apellido_materno ?? ''); ?>"
                                       placeholder="Ej: López">
                            </div>
                        </div>
                        
                        <!-- SECCION 2: DATOS DE CONTACTO -->
                        <div class="section-title mt-4">
                            <i class="fas fa-address-card"></i> Datos de Contacto
                        </div>
                        
                        <div class="row g-3">
                            <!-- Email -->
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                       placeholder="ejemplo@cobao.edu.mx">
                                <div class="form-text">Correo institucional del maestro</div>
                            </div>
                            
                            <!-- Teléfono -->
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" 
                                       value="<?php echo htmlspecialchars($telefono ?? ''); ?>"
                                       placeholder="951-123-4567">
                                <div class="form-text">Número de contacto</div>
                            </div>
                        </div>
                        
                        <!-- SECCION 3: DATOS ACADÉMICOS -->
                        <div class="section-title mt-4">
                            <i class="fas fa-graduation-cap"></i> Datos Académicos
                        </div>
                        
                        <div class="row g-3">
                            <!-- Especialidad -->
                            <div class="col-md-12">
                                <label for="especialidad" class="form-label">Especialidad</label>
                                <input type="text" class="form-control" id="especialidad" name="especialidad" 
                                       value="<?php echo htmlspecialchars($especialidad ?? ''); ?>"
                                       placeholder="Ej: Matemáticas, Física, Informática...">
                                <div class="form-text">Materia o área de especialización del maestro</div>
                            </div>
                        </div>
                        
                        <!-- SECCION 4: DISPONIBILIDAD -->
                        <div class="section-title mt-4">
                            <i class="fas fa-toggle-on"></i> Disponibilidad
                        </div>
                        
                        <!-- Switch para disponibilidad -->
                        <div class="switch-container active" id="switchContainer" onclick="toggleSwitch()">
                            <div class="switch">
                                <input type="checkbox" id="disponible" name="disponible" value="1" checked>
                                <span class="slider"></span>
                            </div>
                            <div class="switch-label">
                                Maestro disponible
                                <small>Si está disponible, podrá recibir asignaciones de horario</small>
                            </div>
                        </div>
                        
                        <!-- BOTONES -->
                        <div class="d-flex gap-3 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="fas fa-save me-2"></i> Registrar Maestro
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                <i class="fas fa-arrow-left me-2"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Ayuda rápida -->
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6><i class="fas fa-info-circle text-info"></i> Información importante</h6>
                            <small class="text-muted">
                                Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                                El email debe ser único en el sistema.
                            </small>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <span class="badge-help">
                                <i class="fas fa-shield-alt"></i> Datos seguros
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// TOGGLE DEL SWITCH DE DISPONIBILIDAD
// ============================================

function toggleSwitch() {
    var checkbox = document.getElementById('disponible');
    var container = document.getElementById('switchContainer');
    
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        container.classList.add('active');
    } else {
        container.classList.remove('active');
    }
}

// ============================================
// SUGERIR EMAIL AUTOMÁTICAMENTE
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    var nombreInput = document.getElementById('nombre');
    var apellidoInput = document.getElementById('apellido_paterno');
    var emailInput = document.getElementById('email');
    
    function sugerirEmail() {
        if (!emailInput.value) {
            var nombre = nombreInput.value.trim().toLowerCase();
            var apellido = apellidoInput.value.trim().toLowerCase();
            if (nombre && apellido) {
                emailInput.placeholder = nombre + '.' + apellido + '@cobao.edu.mx';
            }
        }
    }
    
    nombreInput.addEventListener('blur', sugerirEmail);
    apellidoInput.addEventListener('blur', sugerirEmail);
});
</script>

<?php include '../../includes/footer.php'; ?>