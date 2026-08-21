<?php
// modules/materias/index.php - Listado de materias (VERSIÓN MEJORADA - ROJO COBAO)
session_start();

$page_title = 'Gestión de Materias';
$page_icon = 'book';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Obtener todas las materias con su semestre
$sql = "SELECT 
            m.*, 
            s.numero as semestre_numero,
            s.tipo as semestre_tipo,
            e.nombre as especialidad_nombre,
            n.nombre as nucleo_nombre
        FROM materias m
        JOIN semestres s ON m.semestre_id = s.id
        LEFT JOIN especialidades e ON m.especialidad_id = e.id
        LEFT JOIN nucleos n ON m.nucleo_id = n.id
        WHERE m.activo = 1
        ORDER BY s.numero ASC, m.nombre ASC";

$materias = obtenerRegistros($sql);

// Obtener semestres para el filtro
$semestres = obtenerRegistros("SELECT * FROM semestres WHERE activo = 1 ORDER BY numero ASC");

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS MEJORADOS PARA GESTIÓN DE MATERIAS
   ============================================ */
.materias-container .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.materias-container .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.materias-container .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.materias-container .card-header h5 i {
    color: #8B0000;
}

/* Filtros mejorados */
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
    border-color: #8B0000;
    box-shadow: 0 0 0 4px rgba(139, 0, 0, 0.1);
}

/* Tabla mejorada - ROJO COBAO */
.table-materias {
    border-radius: 12px;
    overflow: hidden;
    width: 100%;
}

.table-materias thead {
    background: linear-gradient(135deg, #8B0000, #5C0000);
    color: white;
}

.table-materias thead th {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 12px 15px;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-materias tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.85rem;
}

.table-materias tbody tr:hover {
    background: #f8f9fa;
    transition: all 0.2s ease;
}

.table-materias tbody tr {
    transition: all 0.2s ease;
}

/* Badges */
.badge-clave {
    background: #2d3436 !important;
    color: white !important;
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
}

.badge-semestre {
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    display: inline-block;
}

.badge-semestre.basico {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-semestre.especialidad {
    background: #fff3e0;
    color: #e65100;
}

.badge-semestre.nucleo {
    background: #f3e5f5;
    color: #6a1b9a;
}

.badge-tipo {
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    display: inline-block;
}

.badge-tipo.tronco {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-tipo.especialidad {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-tipo.nucleo {
    background: #f3e5f5;
    color: #6a1b9a;
}

.badge-especialidad {
    background: #e3f2fd;
    color: #1565c0;
    font-size: 0.65rem;
    padding: 3px 10px;
    border-radius: 20px;
}

.badge-nucleo {
    background: #f3e5f5;
    color: #6a1b9a;
    font-size: 0.65rem;
    padding: 3px 10px;
    border-radius: 20px;
}

/* Botones de acción */
.btn-action {
    width: 30px;
    height: 30px;
    padding: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.8rem;
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

/* Contador total - ROJO COBAO */
.total-badge {
    background: linear-gradient(135deg, #8B0000, #5C0000);
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

/* Tarjetas de semestres */
.semestre-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 15px;
    transition: all 0.3s ease;
    height: 100%;
    background: white;
}

.semestre-card:hover {
    border-color: #8B0000;
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(139, 0, 0, 0.08);
}

.semestre-card .semestre-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.semestre-card .semestre-header h6 {
    font-weight: 700;
    margin: 0;
    color: #1a237e;
    font-size: 0.9rem;
}

.semestre-card .semestre-header .badge-count {
    background: #8B0000;
    color: white;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.semestre-card .materia-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.semestre-card .materia-list li {
    padding: 3px 0;
    font-size: 0.8rem;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 6px;
    border-bottom: 1px solid #f1f3f5;
}

.semestre-card .materia-list li:last-child {
    border-bottom: none;
}

.semestre-card .materia-list li i {
    color: #8B0000;
    font-size: 0.5rem;
}

.semestre-card .materia-list .text-muted {
    font-size: 0.75rem;
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
    .table-materias {
        font-size: 0.75rem;
    }
    .table-materias thead th,
    .table-materias tbody td {
        padding: 6px 10px;
    }
}

@media (max-width: 768px) {
    .filter-section .row {
        gap: 10px;
    }
    .btn-action {
        width: 26px;
        height: 26px;
        font-size: 0.7rem;
    }
    .semestre-card {
        margin-bottom: 10px;
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

.materias-container .card {
    animation: fadeInUp 0.4s ease;
}

/* Scroll horizontal en móvil */
.table-responsive {
    overflow-x: auto;
}
</style>

<div class="materias-container">
    <!-- Tarjeta principal -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 p-2 rounded-circle">
                    <i class="fas fa-book text-primary fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0">Listado de Materias</h5>
                    <small class="text-muted">Gestiona todas las materias del COBAO</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="crear.php" class="btn" style="background: #8B0000; color: white; border: none;">
                    <i class="fas fa-plus me-1"></i> Nueva Materia
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filtros -->
            <div class="filter-section">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-filter me-1"></i> Semestre
                        </label>
                        <select class="form-select" id="filtro_semestre" onchange="filtrarMaterias()">
                            <option value="">Todos los semestres</option>
                            <?php foreach ($semestres as $sem): ?>
                                <option value="<?php echo $sem['id']; ?>">
                                    <?php echo $sem['numero']; ?>° <?php echo ucfirst($sem['tipo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-search me-1"></i> Buscar
                        </label>
                        <input type="text" class="form-control" id="filtro_busqueda" 
                               placeholder="Buscar por nombre o clave..." onkeyup="filtrarMaterias()">
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="total-badge">
                            <i class="fas fa-list"></i>
                            <span id="total_materias"><?php echo count($materias); ?></span> materias
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-materias" id="tabla_materias">
                    <thead>
                        <tr>
                            <th style="width: 4%; text-align: center;">#</th>
                            <th style="width: 12%;">Clave</th>
                            <th style="width: 32%;">Materia</th>
                            <th style="width: 14%;">Semestre</th>
                            <th style="width: 10%;">Tipo</th>
                            <th style="width: 16%;">Especialidad/Núcleo</th>
                            <th style="width: 12%; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($materias) > 0): ?>
                            <?php foreach ($materias as $index => $materia): ?>
                                <tr data-semestre="<?php echo $materia['semestre_id']; ?>">
                                    <td style="text-align: center; font-weight: 600; color: #6c757d; font-size: 0.75rem;">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td>
                                        <span class="badge-clave">
                                            <?php echo htmlspecialchars($materia['clave']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($materia['nombre']); ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                        $clase_semestre = 'basico';
                                        if ($materia['semestre_numero'] >= 3 && $materia['semestre_numero'] <= 4) {
                                            $clase_semestre = 'especialidad';
                                        } elseif ($materia['semestre_numero'] >= 5) {
                                            $clase_semestre = 'nucleo';
                                        }
                                        ?>
                                        <span class="badge-semestre <?php echo $clase_semestre; ?>">
                                            <?php echo $materia['semestre_numero']; ?>°
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $clase_tipo = $materia['tipo_materia'];
                                        $tipo_label = $materia['tipo_materia'];
                                        if ($tipo_label == 'tronco') {
                                            $tipo_mostrar = 'Tronco';
                                        } elseif ($tipo_label == 'especialidad') {
                                            $tipo_mostrar = 'Esp.';
                                        } else {
                                            $tipo_mostrar = 'Núcleo';
                                        }
                                        ?>
                                        <span class="badge-tipo <?php echo $clase_tipo; ?>">
                                            <?php echo $tipo_mostrar; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($materia['especialidad_nombre']): ?>
                                            <span class="badge-especialidad">
                                                <i class="fas fa-tag me-1"></i>
                                                <?php echo htmlspecialchars($materia['especialidad_nombre']); ?>
                                            </span>
                                        <?php elseif ($materia['nucleo_nombre']): ?>
                                            <span class="badge-nucleo">
                                                <i class="fas fa-layer-group me-1"></i>
                                                <?php echo htmlspecialchars($materia['nucleo_nombre']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.75rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="editar.php?id=<?php echo $materia['id']; ?>" 
                                               class="btn-action edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="ver.php?id=<?php echo $materia['id']; ?>" 
                                               class="btn-action view" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="confirmarEliminar(<?php echo $materia['id']; ?>, '<?php echo htmlspecialchars($materia['nombre']); ?>')" 
                                                    class="btn-action delete" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-book"></i>
                                        <h5>No hay materias registradas</h5>
                                        <p>Comienza creando tu primera materia</p>
                                        <a href="crear.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Crear materia
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
    
    <!-- Materias por Semestre -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-layer-group text-primary fs-5"></i>
                        <h5 class="mb-0">Materias por Semestre</h5>
                        <span class="badge bg-secondary ms-2"><?php echo count($materias); ?> total</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <?php
                            $materias_semestre = array_filter($materias, function($m) use ($i) {
                                return $m['semestre_numero'] == $i;
                            });
                            $total = count($materias_semestre);
                            
                            $color_semestre = 'primary';
                            if ($i >= 3 && $i <= 4) {
                                $color_semestre = 'warning';
                            } elseif ($i >= 5) {
                                $color_semestre = 'purple';
                            }
                            ?>
                            <div class="col-lg-2 col-md-4 col-sm-6 col-6">
                                <div class="semestre-card">
                                    <div class="semestre-header">
                                        <h6>
                                            <i class="fas fa-graduation-cap text-<?php echo $color_semestre; ?> me-1"></i>
                                            <?php echo $i; ?>° Semestre
                                        </h6>
                                        <span class="badge-count"><?php echo $total; ?></span>
                                    </div>
                                    <?php if ($total > 0): ?>
                                        <ul class="materia-list">
                                            <?php 
                                            $contador = 0;
                                            foreach ($materias_semestre as $m):
                                                if ($contador >= 4) break;
                                                $contador++;
                                            ?>
                                                <li>
                                                    <i class="fas fa-circle"></i>
                                                    <?php echo htmlspecialchars($m['nombre']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                            <?php if ($total > 4): ?>
                                                <li class="text-muted">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                    y <?php echo $total - 4; ?> más
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted small mb-0">Sin materias</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
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

function filtrarMaterias() {
    const semestre = document.getElementById('filtro_semestre').value;
    const busqueda = document.getElementById('filtro_busqueda').value.toLowerCase();
    const rows = document.querySelectorAll('#tabla_materias tbody tr');
    let visibles = 0;
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        
        const semestreRow = row.getAttribute('data-semestre');
        const nombre = row.querySelector('td:nth-child(3)')?.textContent?.toLowerCase() || '';
        const clave = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
        
        let mostrar = true;
        
        if (semestre && semestreRow !== semestre) {
            mostrar = false;
        }
        
        if (busqueda && !nombre.includes(busqueda) && !clave.includes(busqueda)) {
            mostrar = false;
        }
        
        row.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    
    document.getElementById('total_materias').textContent = visibles;
}

// ============================================
// ELIMINAR MATERIA
// ============================================

function confirmarEliminar(id, nombre) {
    if (confirm('¿Estas seguro de eliminar la materia "' + nombre + '"?\nEsta accion no se puede deshacer.')) {
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
            filtrarMaterias();
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>