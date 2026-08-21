<?php
// modules/maestros/index.php - Listado de maestros (VERSIÓN MEJORADA)
session_start();

$page_title = 'Gestión de Maestros';
$page_icon = 'chalkboard-teacher';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Obtener todos los maestros con estadísticas
$sql = "SELECT 
            m.*,
            COUNT(h.id) as total_horarios,
            COUNT(DISTINCT h.materia_id) as total_materias,
            GROUP_CONCAT(DISTINCT h.dia_semana ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes')) as dias
        FROM maestros m
        LEFT JOIN horarios h ON m.id = h.maestro_id AND h.activo = 1
        WHERE m.activo = 1
        GROUP BY m.id
        ORDER BY m.nombre ASC";

$maestros = obtenerRegistros($sql);

// Obtener estadísticas
$total_maestros = count($maestros);
$disponibles = array_filter($maestros, function($m) { return $m['disponible'] == 1; });
$total_disponibles = count($disponibles);

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS MEJORADOS PARA GESTIÓN DE MAESTROS
   ============================================ */
.maestros-container .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.maestros-container .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.maestros-container .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.maestros-container .card-header h5 i {
    color: #1976d2;
}

/* Filtros */
.filter-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px 20px;
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
}

.filter-section .form-control,
.filter-section .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 10px 15px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.filter-section .form-control:focus,
.filter-section .form-select:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
}

/* Tabla mejorada - MEJOR DISTRIBUCIÓN */
.table-maestros {
    border-radius: 12px;
    overflow: hidden;
    width: 100%;
}

.table-maestros thead {
    background: #1a237e;
    color: white;
}

.table-maestros thead th {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 10px 12px;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.table-maestros tbody td {
    padding: 8px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.85rem;
}

.table-maestros tbody tr:hover {
    background: #f8f9fa;
    transition: all 0.2s ease;
}

.table-maestros tbody tr {
    transition: all 0.2s ease;
}

/* Badges */
.badge-estado {
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.badge-estado.disponible {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-estado.no-disponible {
    background: #ffebee;
    color: #c62828;
}

.badge-horarios {
    background: #e9ecef;
    color: #495057;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.badge-materias {
    background: #e3f2fd;
    color: #1565c0;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Botones de acción - MÁS COMPACTOS */
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

.btn-action.view {
    background: #e8f5e9;
    color: #2e7d32;
}

.btn-action.view:hover {
    background: #2e7d32;
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

/* Nombre del maestro */
.maestro-nombre {
    font-weight: 600;
    color: #1a237e;
    font-size: 0.85rem;
}

.maestro-email {
    font-size: 0.75rem;
    color: #6c757d;
}

.maestro-especialidad {
    display: inline-block;
    padding: 2px 10px;
    background: #f1f3f5;
    border-radius: 12px;
    font-size: 0.7rem;
    color: #495057;
}

/* Total badge */
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

.total-badge .badge-disp {
    background: #4caf50;
    color: white;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    margin-left: 5px;
}

/* Estado vacío */
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

/* Responsive */
@media (max-width: 992px) {
    .table-maestros {
        font-size: 0.75rem;
    }
    .table-maestros thead th,
    .table-maestros tbody td {
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

.maestros-container .card {
    animation: fadeInUp 0.4s ease;
}

/* Scroll horizontal en móvil */
.table-responsive {
    overflow-x: auto;
}
</style>

<div class="maestros-container">
    <!-- Tarjeta principal -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 p-2 rounded-circle">
                    <i class="fas fa-chalkboard-teacher text-primary fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0">Listado de Maestros</h5>
                    <small class="text-muted">Gestiona todos los maestros del COBAO</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="crear.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Nuevo Maestro
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filtros -->
            <div class="filter-section">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-filter me-1"></i> Estado
                        </label>
                        <select class="form-select" id="filtro_estado" onchange="filtrarMaestros()">
                            <option value="">Todos</option>
                            <option value="1">Disponibles</option>
                            <option value="0">No disponibles</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-search me-1"></i> Buscar
                        </label>
                        <input type="text" class="form-control" id="filtro_busqueda" 
                               placeholder="Buscar por nombre o especialidad..." onkeyup="filtrarMaestros()">
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="total-badge">
                            <i class="fas fa-users"></i>
                            <span id="total_maestros"><?php echo $total_maestros; ?></span> maestros
                            <span class="badge-disp"><?php echo $total_disponibles; ?> disp.</span>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Tabla con mejor distribución -->
            <div class="table-responsive">
                <table class="table table-maestros" id="tabla_maestros">
                    <thead>
                        <tr>
                            <th style="width: 4%; text-align: center;">#</th>
                            <th style="width: 18%;">Nombre</th>
                            <th style="width: 18%;">Email</th>
                            <th style="width: 10%;">Teléfono</th>
                            <th style="width: 13%;">Especialidad</th>
                            <th style="width: 9%; text-align: center;">Estado</th>
                            <th style="width: 8%; text-align: center;">Horas</th>
                            <th style="width: 8%; text-align: center;">Materias</th>
                            <th style="width: 12%; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_maestros > 0): ?>
                            <?php foreach ($maestros as $index => $maestro): ?>
                                <tr data-disponible="<?php echo $maestro['disponible']; ?>">
                                    <td style="text-align: center; font-weight: 600; color: #6c757d; font-size: 0.75rem;">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td>
                                        <div class="maestro-nombre">
                                            <?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno'] . ' ' . $maestro['apellido_materno']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($maestro['email']): ?>
                                            <span class="maestro-email">
                                                <i class="fas fa-envelope me-1" style="font-size: 0.6rem;"></i>
                                                <?php echo htmlspecialchars($maestro['email']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.7rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($maestro['telefono']): ?>
                                            <span class="maestro-email">
                                                <i class="fas fa-phone me-1" style="font-size: 0.6rem;"></i>
                                                <?php echo htmlspecialchars($maestro['telefono']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.7rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($maestro['especialidad']): ?>
                                            <span class="maestro-especialidad">
                                                <?php echo htmlspecialchars($maestro['especialidad']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.7rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($maestro['disponible']): ?>
                                            <span class="badge-estado disponible">
                                                <i class="fas fa-circle" style="font-size: 0.4rem;"></i> Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-estado no-disponible">
                                                <i class="fas fa-circle" style="font-size: 0.4rem;"></i> Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-horarios">
                                            <i class="fas fa-clock"></i>
                                            <?php echo $maestro['total_horarios'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-materias">
                                            <i class="fas fa-book"></i>
                                            <?php echo $maestro['total_materias'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="editar.php?id=<?php echo $maestro['id']; ?>" 
                                               class="btn-action edit" title="Editar maestro">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="ver.php?id=<?php echo $maestro['id']; ?>" 
                                               class="btn-action view" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="confirmarEliminar(<?php echo $maestro['id']; ?>, '<?php echo htmlspecialchars($maestro['nombre'] . ' ' . $maestro['apellido_paterno']); ?>')" 
                                                    class="btn-action delete" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <h5>No hay maestros registrados</h5>
                                        <p>Comienza registrando tu primer maestro</p>
                                        <a href="crear.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Registrar maestro
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
    
    <!-- Resumen rápido -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-chart-bar text-primary fs-5"></i>
                        <h5 class="mb-0">Resumen de Maestros</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center p-3 bg-light rounded-3">
                                <div class="display-6 text-primary"><?php echo $total_maestros; ?></div>
                                <small class="text-muted">Total Maestros</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center p-3 bg-light rounded-3">
                                <div class="display-6 text-success"><?php echo $total_disponibles; ?></div>
                                <small class="text-muted">Disponibles</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center p-3 bg-light rounded-3">
                                <div class="display-6 text-danger"><?php echo $total_maestros - $total_disponibles; ?></div>
                                <small class="text-muted">No disponibles</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center p-3 bg-light rounded-3">
                                <div class="display-6 text-warning"><?php 
                                    $total_horas = array_sum(array_column($maestros, 'total_horarios'));
                                    echo $total_horas;
                                ?></div>
                                <small class="text-muted">Horas asignadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// FUNCIONES DE FILTRADO
// ============================================

function filtrarMaestros() {
    const estado = document.getElementById('filtro_estado').value;
    const busqueda = document.getElementById('filtro_busqueda').value.toLowerCase();
    const rows = document.querySelectorAll('#tabla_maestros tbody tr');
    let visibles = 0;
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        
        const disponible = row.getAttribute('data-disponible');
        const nombre = row.querySelector('td:nth-child(2) .maestro-nombre')?.textContent?.toLowerCase() || '';
        const especialidad = row.querySelector('td:nth-child(5) .maestro-especialidad')?.textContent?.toLowerCase() || '';
        
        let mostrar = true;
        
        if (estado && disponible !== estado) {
            mostrar = false;
        }
        
        if (busqueda && !nombre.includes(busqueda) && !especialidad.includes(busqueda)) {
            mostrar = false;
        }
        
        row.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    
    document.getElementById('total_maestros').textContent = visibles;
}

// ============================================
// ELIMINAR MAESTRO
// ============================================

function confirmarEliminar(id, nombre) {
    if (confirm('¿Estas seguro de eliminar al maestro "' + nombre + '"?\nEsta accion no se puede deshacer.')) {
        window.location.href = 'eliminar.php?id=' + id;
    }
}

// ============================================
// TECLA ESCAPE PARA LIMPIAR BÚSQUEDA
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtro_busqueda').addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            filtrarMaestros();
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>