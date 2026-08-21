<?php
// modules/grupos/index.php - Listado de grupos (VERSIÓN CORREGIDA)
session_start();

$page_title = 'Gestión de Grupos';
$page_icon = 'users';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Obtener todos los grupos con su semestre y estadísticas
$sql = "SELECT 
            g.*, 
            s.numero as semestre_numero,
            s.tipo as semestre_tipo,
            e.nombre as especialidad_nombre,
            e.clave as especialidad_clave,
            n.nombre as nucleo_nombre,
            n.clave as nucleo_clave,
            (SELECT COUNT(*) FROM horarios WHERE grupo_id = g.id AND activo = 1) as total_horarios,
            (SELECT COUNT(DISTINCT mg.materia_id) FROM materias_grupos mg WHERE mg.grupo_id = g.id) as total_materias
        FROM grupos g
        JOIN semestres s ON g.semestre_id = s.id
        LEFT JOIN especialidades e ON g.especialidad_id = e.id
        LEFT JOIN nucleos n ON g.nucleo_actual = n.id
        WHERE g.activo = 1
        ORDER BY s.numero ASC, g.nombre ASC";

$grupos = obtenerRegistros($sql);

// Obtener semestres para el filtro
$semestres = obtenerRegistros("SELECT * FROM semestres WHERE activo = 1 ORDER BY numero ASC");

// Obtener estadísticas de grupos
$total_grupos = count($grupos);
$grupos_por_semestre = [];
for ($i = 1; $i <= 6; $i++) {
    $grupos_por_semestre[$i] = array_filter($grupos, function($g) use ($i) {
        return $g['semestre_numero'] == $i;
    });
}

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS MEJORADOS PARA GESTIÓN DE GRUPOS
   ============================================ */
.grupos-container .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.grupos-container .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.grupos-container .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.grupos-container .card-header h5 i {
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

/* Tabla mejorada */
.table-grupos {
    border-radius: 12px;
    overflow: hidden;
}

.table-grupos thead {
    background: #1a237e;
    color: white;
}

.table-grupos thead th {
    font-weight: 600;
    font-size: 0.8rem;
    padding: 12px 15px;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-grupos tbody td {
    padding: 10px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
}

.table-grupos tbody tr:hover {
    background: #f8f9fa;
    transition: all 0.2s ease;
}

.table-grupos tbody tr {
    transition: all 0.2s ease;
}

/* Badges */
.badge-semestre {
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
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

.badge-especialidad {
    background: #e3f2fd;
    color: #1565c0;
    font-size: 0.7rem;
    padding: 3px 10px;
    border-radius: 20px;
}

.badge-nucleo {
    background: #f3e5f5;
    color: #6a1b9a;
    font-size: 0.7rem;
    padding: 3px 10px;
    border-radius: 20px;
}

.badge-grupo {
    font-weight: 700;
    font-size: 1rem;
    color: #1a237e;
}

.badge-horarios {
    background: #e9ecef;
    color: #495057;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-materias {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Botones de acción */
.btn-action {
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.85rem;
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

.btn-action.horarios {
    background: #fff3e0;
    color: #e65100;
}

.btn-action.horarios:hover {
    background: #e65100;
    color: white;
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

/* Tarjetas de semestres */
.semestre-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 15px;
    transition: all 0.3s ease;
    height: 100%;
    background: white;
    text-align: center;
}

.semestre-card:hover {
    border-color: #1976d2;
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.semestre-card .semestre-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a237e;
}

.semestre-card .semestre-type {
    font-size: 0.7rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.semestre-card .semestre-count {
    font-size: 2rem;
    font-weight: 700;
    color: #1976d2;
    margin: 5px 0;
}

.semestre-card .semestre-label {
    font-size: 0.75rem;
    color: #6c757d;
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
@media (max-width: 768px) {
    .filter-section .row {
        gap: 10px;
    }
    .table-grupos {
        font-size: 0.8rem;
    }
    .table-grupos thead th,
    .table-grupos tbody td {
        padding: 6px 10px;
    }
    .btn-action {
        width: 26px;
        height: 26px;
        font-size: 0.7rem;
    }
    .semestre-card {
        padding: 10px;
    }
    .semestre-card .semestre-number {
        font-size: 1.2rem;
    }
    .semestre-card .semestre-count {
        font-size: 1.5rem;
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

.grupos-container .card {
    animation: fadeInUp 0.4s ease;
}
</style>

<div class="grupos-container">
    <!-- Tarjeta principal -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 p-2 rounded-circle">
                    <i class="fas fa-users text-primary fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0">Listado de Grupos</h5>
                    <small class="text-muted">Gestiona todos los grupos del COBAO</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="crear.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Nuevo Grupo
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
                        <select class="form-select" id="filtro_semestre" onchange="filtrarGrupos()">
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
                               placeholder="Buscar por nombre..." onkeyup="filtrarGrupos()">
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="total-badge">
                            <i class="fas fa-list"></i>
                            <span id="total_grupos"><?php echo $total_grupos; ?></span> grupos
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-grupos" id="tabla_grupos">
                    <thead>
                        <tr>
                            <th style="width: 4%; text-align: center;">#</th>
                            <th style="width: 12%;">Grupo</th>
                            <th style="width: 12%;">Semestre</th>
                            <th style="width: 18%;">Especialidad/Núcleo</th>
                            <th style="width: 10%; text-align: center;">Materias</th>
                            <th style="width: 10%; text-align: center;">Horarios</th>
                            <th style="width: 20%; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_grupos > 0): ?>
                            <?php foreach ($grupos as $index => $grupo): ?>
                                <tr data-semestre="<?php echo $grupo['semestre_id']; ?>">
                                    <td style="text-align: center; font-weight: 600; color: #6c757d;">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td>
                                        <span class="badge-grupo">
                                            <?php echo htmlspecialchars($grupo['nombre']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $clase_semestre = 'basico';
                                        if ($grupo['semestre_numero'] >= 3 && $grupo['semestre_numero'] <= 4) {
                                            $clase_semestre = 'especialidad';
                                        } elseif ($grupo['semestre_numero'] >= 5) {
                                            $clase_semestre = 'nucleo';
                                        }
                                        ?>
                                        <span class="badge-semestre <?php echo $clase_semestre; ?>">
                                            <?php echo $grupo['semestre_numero']; ?>°
                                        </span>
                                        <br>
                                        <small class="text-muted" style="font-size: 0.65rem;">
                                            <?php echo ucfirst($grupo['semestre_tipo']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($grupo['especialidad_nombre']): ?>
                                            <span class="badge-especialidad">
                                                <i class="fas fa-tag me-1"></i>
                                                <?php echo htmlspecialchars($grupo['especialidad_nombre']); ?>
                                            </span>
                                        <?php elseif ($grupo['nucleo_nombre']): ?>
                                            <span class="badge-nucleo">
                                                <i class="fas fa-layer-group me-1"></i>
                                                <?php echo htmlspecialchars($grupo['nucleo_nombre']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-materias">
                                            <i class="fas fa-book me-1"></i>
                                            <?php echo $grupo['total_materias'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-horarios">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo $grupo['total_horarios'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="editar.php?id=<?php echo $grupo['id']; ?>" 
                                               class="btn-action edit" title="Editar grupo">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="ver.php?id=<?php echo $grupo['id']; ?>" 
                                               class="btn-action view" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="../horarios/crear.php?grupo_id=<?php echo $grupo['id']; ?>" 
                                               class="btn-action horarios" title="Asignar horarios">
                                                <i class="fas fa-calendar-plus"></i>
                                            </a>
                                            <button onclick="confirmarEliminar(<?php echo $grupo['id']; ?>, '<?php echo htmlspecialchars($grupo['nombre']); ?>')" 
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
                                        <i class="fas fa-users"></i>
                                        <h5>No hay grupos registrados</h5>
                                        <p>Comienza creando tu primer grupo</p>
                                        <a href="crear.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Crear grupo
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
    
    <!-- Grupos por Semestre -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-layer-group text-primary fs-5"></i>
                        <h5 class="mb-0">Distribución por Semestre</h5>
                        <span class="badge bg-secondary ms-2"><?php echo $total_grupos; ?> total</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <?php 
                            $count = count($grupos_por_semestre[$i] ?? []);
                            $color = 'primary';
                            if ($i >= 3 && $i <= 4) {
                                $color = 'warning';
                            } elseif ($i >= 5) {
                                $color = 'purple';
                            }
                            ?>
                            <div class="col-lg-2 col-md-4 col-sm-4 col-6">
                                <div class="semestre-card">
                                    <div class="semestre-number"><?php echo $i; ?>°</div>
                                    <div class="semestre-type">
                                        <?php 
                                        if ($i <= 2) echo 'Básico';
                                        elseif ($i <= 4) echo 'Especialidad';
                                        else echo 'Núcleo';
                                        ?>
                                    </div>
                                    <div class="semestre-count"><?php echo $count; ?></div>
                                    <div class="semestre-label">grupos</div>
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

function filtrarGrupos() {
    const semestre = document.getElementById('filtro_semestre').value;
    const busqueda = document.getElementById('filtro_busqueda').value.toLowerCase();
    const rows = document.querySelectorAll('#tabla_grupos tbody tr');
    let visibles = 0;
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        
        const semestreRow = row.getAttribute('data-semestre');
        const nombre = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
        
        let mostrar = true;
        
        if (semestre && semestreRow !== semestre) {
            mostrar = false;
        }
        
        if (busqueda && !nombre.includes(busqueda)) {
            mostrar = false;
        }
        
        row.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    
    document.getElementById('total_grupos').textContent = visibles;
}

// ============================================
// ELIMINAR GRUPO
// ============================================

function confirmarEliminar(id, nombre) {
    if (confirm('¿Estas seguro de eliminar el grupo "' + nombre + '"?\nEsta accion no se puede deshacer.')) {
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
            filtrarGrupos();
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>