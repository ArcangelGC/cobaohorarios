<?php
// modules/salones/crear.php - Crear nuevo salón (SIN DISPONIBILIDAD)
session_start();

$page_title = 'Registrar Salón';
$page_icon = 'door-open';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? 'aula';
    $capacidad = intval($_POST['capacidad'] ?? 30);
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    
    if (empty($nombre)) {
        $mensaje = 'El nombre del salón es obligatorio';
        $tipo_mensaje = 'danger';
    } else {
        $sql_verificar = "SELECT id FROM salones WHERE nombre = ?";
        $existe = obtenerRegistro($sql_verificar, [$nombre]);
        
        if ($existe) {
            $mensaje = 'Ya existe un salón con ese nombre';
            $tipo_mensaje = 'danger';
        } else {
            $sql = "INSERT INTO salones (nombre, tipo, capacidad, ubicacion, activo) 
                    VALUES (?, ?, ?, ?, 1)";
            $params = [$nombre, $tipo, $capacidad, $ubicacion];
            $types = "ssis";
            
            $resultado = ejecutarUpdate($sql, $params, $types);
            
            if ($resultado) {
                $_SESSION['mensaje'] = 'Salón registrado exitosamente';
                $_SESSION['tipo_mensaje'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                $mensaje = 'Error al registrar el salón';
                $tipo_mensaje = 'danger';
            }
        }
    }
}

include '../../includes/header.php';
?>

<style>
.salon-form .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.salon-form .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.salon-form .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.salon-form .card-header small {
    color: #6c757d;
    font-size: 0.85rem;
}

.salon-form .section-title {
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

.salon-form .section-title i {
    color: #1976d2;
}

.salon-form .form-control,
.salon-form .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 10px 15px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.salon-form .form-control:focus,
.salon-form .form-select:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
}

.salon-form .form-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.salon-form .form-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 4px;
}

.tipo-card {
    padding: 15px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    height: 100%;
}

.tipo-card:hover {
    border-color: #1976d2;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.tipo-card.selected {
    border-color: #1976d2;
    background: #e3f2fd;
}

.tipo-card .tipo-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 8px;
    color: #1976d2;
}

.tipo-card .tipo-nombre {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1a237e;
}

.tipo-card .tipo-desc {
    font-size: 0.7rem;
    color: #6c757d;
}

@media (max-width: 768px) {
    .salon-form .card-header {
        padding: 15px;
    }
    .tipo-card {
        padding: 10px;
    }
    .tipo-card .tipo-icon {
        font-size: 1.5rem;
    }
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

.salon-form .card {
    animation: fadeInUp 0.4s ease;
}
</style>

<div class="salon-form">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-door-open text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Registrar Nuevo Salón</h5>
                            <small>Ingresa los datos del espacio físico del COBAO</small>
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
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i> Datos del Salón
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">
                                    Nombre del Salón <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-tag text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                                           placeholder="Ej: A-101, Lab-1, Taller-2" required>
                                </div>
                                <div class="form-text">Código único para identificar el salón</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="capacidad" class="form-label">Capacidad</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-users text-primary"></i>
                                    </span>
                                    <input type="number" class="form-control" id="capacidad" name="capacidad" 
                                           value="<?php echo $capacidad ?? 30; ?>" min="1" max="100">
                                </div>
                                <div class="form-text">Número máximo de estudiantes</div>
                            </div>
                        </div>
                        
                        <div class="section-title mt-4">
                            <i class="fas fa-building"></i> Tipo de Salón
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <div class="tipo-card <?php echo ($tipo ?? 'aula') == 'aula' ? 'selected' : ''; ?>" onclick="seleccionarTipo('aula')">
                                    <span class="tipo-icon"><i class="fas fa-chalkboard"></i></span>
                                    <div class="tipo-nombre">Aula</div>
                                    <div class="tipo-desc">Clases teóricas</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="tipo-card <?php echo ($tipo ?? '') == 'laboratorio' ? 'selected' : ''; ?>" onclick="seleccionarTipo('laboratorio')">
                                    <span class="tipo-icon"><i class="fas fa-flask"></i></span>
                                    <div class="tipo-nombre">Laboratorio</div>
                                    <div class="tipo-desc">Prácticas</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="tipo-card <?php echo ($tipo ?? '') == 'taller' ? 'selected' : ''; ?>" onclick="seleccionarTipo('taller')">
                                    <span class="tipo-icon"><i class="fas fa-tools"></i></span>
                                    <div class="tipo-nombre">Taller</div>
                                    <div class="tipo-desc">Trabajo manual</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="tipo-card <?php echo ($tipo ?? '') == 'auditorio' ? 'selected' : ''; ?>" onclick="seleccionarTipo('auditorio')">
                                    <span class="tipo-icon"><i class="fas fa-microphone"></i></span>
                                    <div class="tipo-nombre">Auditorio</div>
                                    <div class="tipo-desc">Eventos</div>
                                </div>
                            </div>
                            <input type="hidden" id="tipo" name="tipo" value="<?php echo $tipo ?? 'aula'; ?>">
                        </div>
                        
                        <div class="section-title mt-4">
                            <i class="fas fa-map-pin"></i> Ubicación
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="ubicacion" class="form-label">Ubicación</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-location-dot text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                                           value="<?php echo htmlspecialchars($ubicacion ?? ''); ?>"
                                           placeholder="Ej: Edificio A, Planta Baja">
                                </div>
                                <div class="form-text">Describe la ubicación del salón</div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-3 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="fas fa-save me-2"></i> Registrar Salón
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
function seleccionarTipo(tipo) {
    document.getElementById('tipo').value = tipo;
    
    var cards = document.querySelectorAll('.tipo-card');
    cards.forEach(function(card) {
        card.classList.remove('selected');
    });
    
    var targetCard = document.querySelector('.tipo-card[onclick*="' + tipo + '"]');
    if (targetCard) {
        targetCard.classList.add('selected');
    }
}
</script>

<?php include '../../includes/footer.php'; ?>