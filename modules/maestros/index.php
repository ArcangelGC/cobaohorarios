<?php
// modules/maestros/index.php - Listado de maestros (VERSIÓN CORREGIDA)
session_start();

$page_title = 'Gestión de Maestros';
$page_icon = 'chalkboard-teacher';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Obtener todos los maestros con estadísticas
$sql = "SELECT 
            m.*,
            COUNT(DISTINCT h.id) as total_horarios,
            COUNT(DISTINCT mg.materia_id) as total_materias,
            GROUP_CONCAT(DISTINCT h.dia_semana ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes')) as dias
        FROM maestros m
        LEFT JOIN horarios h ON m.id = h.maestro_id AND h.activo = 1
        LEFT JOIN materias_grupos mg ON m.id = mg.maestro_id
        WHERE m.activo = 1
        GROUP BY m.id
        ORDER BY m.nombre ASC";

$maestros = obtenerRegistros($sql);

// Obtener estadísticas
$total_maestros = count($maestros);
$total_horas = array_sum(array_column($maestros, 'total_horarios'));

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS MEJORADOS - ROJO COBAO
   ============================================ */

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

@keyframes glow {
    0%, 100% { box-shadow: 0 0 5px rgba(139, 0, 0, 0.2); }
    50% { box-shadow: 0 0 20px rgba(139, 0, 0, 0.4); }
}

.maestros-container .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeInUp 0.6s ease;
}

.maestros-container .card:hover {
    box-shadow: 0 8px 40px rgba(139, 0, 0, 0.12);
    transform: translateY(-2px);
}

.maestros-container .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
    transition: all 0.4s ease;
}

.maestros-container .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.maestros-container .card-header h5 i {
    color: #8B0000;
    transition: all 0.3s ease;
}

.maestros-container .card-header h5 i:hover {
    transform: rotate(15deg) scale(1.1);
}

/* Filtros */
.filter-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px 20px;
    margin-bottom: 20px;
    border: 2px solid #e9ecef;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.filter-section:hover {
    border-color: #8B0000;
    box-shadow: 0 4px 20px rgba(139, 0, 0, 0.08);
    transform: translateY(-2px);
}

.filter-section .form-control {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 10px 15px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.filter-section .form-control:focus {
    border-color: #8B0000;
    box-shadow: 0 0 0 4px rgba(139, 0, 0, 0.1);
    transform: scale(1.02);
}

.filter-section .form-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
}

/* Tabla */
.table-maestros {
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.table-maestros thead {
    background: linear-gradient(135deg, #8B0000, #5C0000);
    color: white;
    position: relative;
    overflow: hidden;
}

.table-maestros thead::after {
    content: '';
    position: absolute;
    top: 0;
    left: -200%;
    width: 200%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    animation: shimmer 3s infinite;
}

.table-maestros thead th {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 10px 12px;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    position: relative;
    z-index: 1;
}

.table-maestros tbody td {
    padding: 8px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.table-maestros tbody tr {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.table-maestros tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01) translateY(-2px);
    box-shadow: 0 4px 20px rgba(139, 0, 0, 0.08);
    z-index: 2;
}

/* Badges */
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
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge-horarios:hover {
    background: #8B0000;
    color: white;
    transform: scale(1.1) rotate(5deg);
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
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge-materias:hover {
    background: #1565c0;
    color: white;
    transform: scale(1.1) rotate(-5deg);
}

/* Botones de acción */
.btn-action {
    width: 28px;
    height: 28px;
    padding: 0;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    font-size: 0.75rem;
    cursor: pointer;
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.btn-action::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transition: all 0.5s ease;
    transform: translate(-50%, -50%);
}

.btn-action:active::after {
    width: 100px;
    height: 100px;
}

.btn-action:hover {
    transform: translateY(-3px) scale(1.1);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.btn-action.edit {
    background: #e3f2fd;
    color: #1565c0;
}

.btn-action.edit:hover {
    background: #1565c0;
    color: white;
    animation: glow 1.5s infinite;
}

.btn-action.view {
    background: #e8f5e9;
    color: #2e7d32;
}

.btn-action.view:hover {
    background: #2e7d32;
    color: white;
    animation: glow 1.5s infinite;
}

.btn-action.delete {
    background: #ffebee;
    color: #c62828;
}

.btn-action.delete:hover {
    background: #c62828;
    color: white;
    animation: glow 1.5s infinite;
}

/* Nombre del maestro */
.maestro-nombre {
    font-weight: 600;
    color: #1a237e;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    display: inline-block;
}

.maestro-nombre:hover {
    color: #8B0000;
    transform: scale(1.02);
}

.maestro-email {
    font-size: 0.75rem;
    color: #6c757d;
    transition: all 0.3s ease;
}

.maestro-email:hover {
    color: #8B0000;
}

.maestro-especialidad {
    display: inline-block;
    padding: 2px 10px;
    background: #f1f3f5;
    border-radius: 12px;
    font-size: 0.7rem;
    color: #495057;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.maestro-especialidad:hover {
    background: #8B0000;
    color: white;
    transform: scale(1.05);
}

/* Total badge */
.total-badge {
    background: linear-gradient(135deg, #8B0000, #5C0000);
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-block;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.total-badge::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
    opacity: 0;
    transition: all 0.6s ease;
}

.total-badge:hover {
    transform: scale(1.05) translateY(-2px);
    box-shadow: 0 4px 20px rgba(139, 0, 0, 0.4);
}

.total-badge:hover::before {
    opacity: 1;
}

.total-badge i {
    margin-right: 8px;
}

/* Botón Nuevo Maestro */
.btn-primary-cobao {
    background: #8B0000;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    position: relative;
    overflow: hidden;
}

.btn-primary-cobao::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    transition: all 0.6s ease;
    transform: translate(-50%, -50%);
}

.btn-primary-cobao:hover {
    background: #5C0000;
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 4px 20px rgba(139, 0, 0, 0.4);
    color: white;
}

.btn-primary-cobao:active::after {
    width: 200px;
    height: 200px;
}

/* Tarjetas de resumen */
.summary-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 15px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    text-align: center;
    background: white;
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(139,0,0,0.05), transparent);
    opacity: 0;
    transition: all 0.6s ease;
}

.summary-card:hover {
    border-color: #8B0000;
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 40px rgba(139, 0, 0, 0.15);
}

.summary-card:hover::before {
    opacity: 1;
}

.summary-card .summary-number {
    font-size: 2rem;
    font-weight: 700;
    color: #8B0000;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
}

.summary-card:hover .summary-number {
    transform: scale(1.15);
    animation: pulse 1s infinite;
}

.summary-card .summary-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    z-index: 1;
}

/* Estado vacío */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    transition: all 0.4s ease;
}

.empty-state i {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 15px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.empty-state:hover i {
    color: #8B0000;
    transform: scale(1.2) rotate(10deg);
}

.empty-state h5 {
    color: #495057;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6c757d;
}

/* Scrollbar */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #8B0000;
    border-radius: 10px;
    transition: all 0.3s ease;
}

::-webkit-scrollbar-thumb:hover {
    background: #5C0000;
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
    .summary-number {
        font-size: 1.5rem;
    }
}

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
                <a href="crear.php" class="btn-primary-cobao">
                    <i class="fas fa-plus"></i> Nuevo Maestro
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filtros -->
            <div class="filter-section">
                <div class="row g-3 align-items-end">
                    <div class="col-md-9">
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
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-maestros" id="tabla_maestros">
                    <thead>
                        <tr>
                            <th style="width: 4%; text-align: center;">#</th>
                            <th style="width: 25%;">Nombre</th>
                            <th style="width: 18%;">Email</th>
                            <th style="width: 10%;">Teléfono</th>
                            <th style="width: 15%;">Especialidad</th>
                            <th style="width: 8%; text-align: center;">Horas</th>
                            <th style="width: 8%; text-align: center;">Materias</th>
                            <th style="width: 12%; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_maestros > 0): ?>
                            <?php foreach ($maestros as $index => $maestro): ?>
                                <tr>
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
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <h5>No hay maestros registrados</h5>
                                        <p>Comienza registrando tu primer maestro</p>
                                        <a href="crear.php" class="btn-primary-cobao">
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
                        <div class="col-md-4 col-sm-6">
                            <div class="summary-card">
                                <div class="summary-number"><?php echo $total_maestros; ?></div>
                                <div class="summary-label">Total Maestros</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="summary-card">
                                <div class="summary-number"><?php echo $total_horas; ?></div>
                                <div class="summary-label">Horas Asignadas</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="summary-card">
                                <div class="summary-number"><?php 
                                    $total_materias_asignadas = array_sum(array_column($maestros, 'total_materias'));
                                    echo $total_materias_asignadas;
                                ?></div>
                                <div class="summary-label">Materias Asignadas</div>
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
    const busqueda = document.getElementById('filtro_busqueda').value.toLowerCase();
    const rows = document.querySelectorAll('#tabla_maestros tbody tr');
    let visibles = 0;
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        
        const nombre = row.querySelector('td:nth-child(2) .maestro-nombre')?.textContent?.toLowerCase() || '';
        const especialidad = row.querySelector('td:nth-child(5) .maestro-especialidad')?.textContent?.toLowerCase() || '';
        
        let mostrar = true;
        
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