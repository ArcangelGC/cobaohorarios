<?php
// modules/salones/index.php - Listado de salones (SIN ESTADO NI HORAS)
session_start();

$page_title = 'Gestión de Salones';
$page_icon = 'door-open';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Obtener todos los salones
$sql = "SELECT 
            s.*
        FROM salones s
        WHERE s.activo = 1
        ORDER BY s.nombre ASC";

$salones = obtenerRegistros($sql);
$total_salones = count($salones);

include '../../includes/header.php';
?>

<style>
.salones-container .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.salones-container .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.salones-container .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.salones-container .card-header h5 i {
    color: #1976d2;
}

.filter-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px 20px;
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
}

.filter-section .form-control {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 10px 15px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.filter-section .form-control:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
}

.filter-section .form-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
}

.table-salones {
    border-radius: 12px;
    overflow: hidden;
    width: 100%;
}

.table-salones thead {
    background: #1a237e;
    color: white;
}

.table-salones thead th {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 10px 12px;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.table-salones tbody td {
    padding: 8px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.85rem;
}

.table-salones tbody tr:hover {
    background: #f8f9fa;
    transition: all 0.2s ease;
}

.table-salones tbody tr {
    transition: all 0.2s ease;
}

.badge-tipo {
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.badge-tipo.aula {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-tipo.laboratorio {
    background: #fff3e0;
    color: #e65100;
}

.badge-tipo.taller {
    background: #f3e5f5;
    color: #6a1b9a;
}

.badge-tipo.auditorio {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-capacidad {
    background: #f1f3f5;
    color: #495057;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-action {
    width: 28px;
    height: 28px;
    padding: 0;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.75rem;
    cursor: pointer;
    text-decoration: none;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-action.edit {
    background: #e3f2fd;
    color: #1565c0;
}

.btn-action.edit:hover {
    background: #1565c0;
    color: white;
}

.btn-action.delete {
    background: #ffebee;
    color: #c62828;
}

.btn-action.delete:hover {
    background: #c62828;
    color: white;
}

.salon-nombre {
    font-weight: 700;
    color: #1a237e;
    font-size: 0.9rem;
}

.salon-ubicacion {
    font-size: 0.75rem;
    color: #6c757d;
}

.total-badge {
    background: #1a237e;
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-block;
}

.total-badge i {
    margin-right: 8px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-state i {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 15px;
}

.empty-state h5 {
    color: #495057;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6c757d;
}

@media (max-width: 992px) {
    .table-salones {
        font-size: 0.75rem;
    }
    .table-salones thead th,
    .table-salones tbody td {
        padding: 6px 8px;
    }
}

@media (max-width: 768px) {
    .filter-section .row {
        gap: 10px;
    }
    .btn-action {
        width: 24px;
        height: 24px;
        font-size: 0.65rem;
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

.salones-container .card {
    animation: fadeInUp 0.4s ease;
}

.table-responsive {
    overflow-x: auto;
}
</style>

<div class="salones-container">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 p-2 rounded-circle">
                    <i class="fas fa-door-open text-primary fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0">Listado de Salones</h5>
                    <small class="text-muted">Gestiona todos los salones del COBAO</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="crear.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Nuevo Salón
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <div class="filter-section">
                <div class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-search me-1"></i> Buscar
                        </label>
                        <input type="text" class="form-control" id="filtro_busqueda" 
                               placeholder="Buscar por nombre o ubicación..." onkeyup="filtrarSalones()">
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="total-badge">
                            <i class="fas fa-door-open"></i>
                            <span id="total_salones"><?php echo $total_salones; ?></span> salones
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-salones" id="tabla_salones">
                    <thead>
                        <tr>
                            <th style="width: 4%; text-align: center;">#</th>
                            <th style="width: 18%;">Salón</th>
                            <th style="width: 15%;">Tipo</th>
                            <th style="width: 12%; text-align: center;">Capacidad</th>
                            <th style="width: 28%;">Ubicación</th>
                            <th style="width: 12%; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_salones > 0): ?>
                            <?php foreach ($salones as $index => $salon): ?>
                                <tr>
                                    <td style="text-align: center; font-weight: 600; color: #6c757d; font-size: 0.75rem;">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td>
                                        <div class="salon-nombre">
                                            <?php echo htmlspecialchars($salon['nombre']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-tipo <?php echo $salon['tipo']; ?>">
                                            <i class="fas fa-<?php 
                                                echo $salon['tipo'] == 'aula' ? 'chalkboard' : 
                                                    ($salon['tipo'] == 'laboratorio' ? 'flask' : 
                                                    ($salon['tipo'] == 'taller' ? 'tools' : 'microphone')); 
                                            ?>"></i>
                                            <?php echo ucfirst($salon['tipo']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-capacidad">
                                            <i class="fas fa-users"></i>
                                            <?php echo $salon['capacidad']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($salon['ubicacion']): ?>
                                            <span class="salon-ubicacion">
                                                <i class="fas fa-location-dot me-1" style="color: #6c757d;"></i>
                                                <?php echo htmlspecialchars($salon['ubicacion']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.75rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="editar.php?id=<?php echo $salon['id']; ?>" 
                                               class="btn-action edit" title="Editar salón">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="confirmarEliminar(<?php echo $salon['id']; ?>, '<?php echo htmlspecialchars($salon['nombre']); ?>')" 
                                                    class="btn-action delete" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-door-open"></i>
                                        <h5>No hay salones registrados</h5>
                                        <p>Comienza registrando tu primer salón</p>
                                        <a href="crear.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Registrar salón
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Resumen -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-chart-bar text-primary fs-5"></i>
                        <h5 class="mb-0">Resumen de Salones</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-light rounded-3">
                                <div class="display-6 text-primary"><?php echo $total_salones; ?></div>
                                <small class="text-muted">Total Salones</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-light rounded-3">
                                <div class="display-6 text-warning">
                                    <?php 
                                    $aulas = array_filter($salones, function($s) { return $s['tipo'] == 'aula'; });
                                    echo count($aulas);
                                    ?>
                                </div>
                                <small class="text-muted">Aulas</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-light rounded-3">
                                <div class="display-6 text-info">
                                    <?php 
                                    $laboratorios = array_filter($salones, function($s) { 
                                        return $s['tipo'] == 'laboratorio' || $s['tipo'] == 'taller'; 
                                    });
                                    echo count($laboratorios);
                                    ?>
                                </div>
                                <small class="text-muted">Laboratorios / Talleres</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filtrarSalones() {
    const busqueda = document.getElementById('filtro_busqueda').value.toLowerCase();
    const rows = document.querySelectorAll('#tabla_salones tbody tr');
    let visibles = 0;
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        
        const nombre = row.querySelector('td:nth-child(2) .salon-nombre')?.textContent?.toLowerCase() || '';
        const ubicacion = row.querySelector('td:nth-child(5)')?.textContent?.toLowerCase() || '';
        
        let mostrar = true;
        
        if (busqueda && !nombre.includes(busqueda) && !ubicacion.includes(busqueda)) {
            mostrar = false;
        }
        
        row.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    
    document.getElementById('total_salones').textContent = visibles;
}

function confirmarEliminar(id, nombre) {
    if (confirm('¿Estas seguro de eliminar el salon "' + nombre + '"?\nEsta accion no se puede deshacer.')) {
        window.location.href = 'eliminar.php?id=' + id;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtro_busqueda').addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            filtrarSalones();
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>