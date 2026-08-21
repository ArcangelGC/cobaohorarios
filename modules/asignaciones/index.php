<?php
// modules/asignaciones/index.php - Gestión de asignaciones (VERSIÓN MEJORADA)
session_start();

$page_title = 'Asignaciones de Materias';
$page_icon = 'tasks';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Obtener asignaciones agrupadas por materia
$sql = "SELECT 
            m.id as materia_id,
            m.nombre as materia_nombre,
            m.clave as materia_clave,
            m.semestre_id,
            s.numero as semestre_numero,
            s.tipo as semestre_tipo,
            m.tipo_materia,
            COUNT(DISTINCT mg.grupo_id) as total_grupos,
            GROUP_CONCAT(DISTINCT 
                CONCAT(
                    g.id, '||', 
                    g.nombre, '||', 
                    g.semestre_id, '||',
                    ma.id, '||',
                    ma.nombre, ' ', ma.apellido_paterno
                ) SEPARATOR ';;'
            ) as grupos_detalle
        FROM materias m
        JOIN semestres s ON m.semestre_id = s.id
        JOIN materias_grupos mg ON m.id = mg.materia_id
        JOIN grupos g ON mg.grupo_id = g.id
        JOIN maestros ma ON mg.maestro_id = ma.id
        WHERE m.activo = 1 AND g.activo = 1
        GROUP BY m.id
        ORDER BY s.numero ASC, m.nombre ASC";

$asignaciones = obtenerRegistros($sql);

// Obtener datos para filtros
$semestres = obtenerRegistros("SELECT * FROM semestres WHERE activo = 1 ORDER BY numero ASC");

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS MEJORADOS - ROJO COBAO
   ============================================ */
.asignaciones-container .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.asignaciones-container .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.asignaciones-container .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.asignaciones-container .card-header h5 i {
    color: #8B0000;
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
    border-color: #8B0000;
    box-shadow: 0 0 0 4px rgba(139, 0, 0, 0.1);
}

/* Tabla mejorada */
.table-asignaciones {
    border-radius: 12px;
    overflow: hidden;
    width: 100%;
}

.table-asignaciones thead {
    background: linear-gradient(135deg, #8B0000, #5C0000);
    color: white;
}

.table-asignaciones thead th {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 12px 15px;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-asignaciones tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.85rem;
}

.table-asignaciones tbody tr:hover {
    background: #f8f9fa;
}

/* Badges */
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

.badge-grupo {
    background: #e9ecef;
    color: #495057;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    display: inline-block;
    margin: 2px 3px;
}

.badge-grupo .maestro {
    font-weight: 400;
    color: #6c757d;
}

/* Botones */
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

.btn-action.delete {
    background: #ffebee;
    color: #c62828;
}

.btn-action.delete:hover {
    background: #c62828;
    color: white;
}

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

/* Sub-tabla de grupos */
.grupos-subtable {
    margin: 0;
    padding: 0;
    list-style: none;
}

.grupos-subtable li {
    padding: 3px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.grupos-subtable .grupo-nombre {
    font-weight: 600;
    color: #1a237e;
    min-width: 40px;
}

.grupos-subtable .maestro-nombre {
    color: #495057;
    font-size: 0.8rem;
}

.grupos-subtable .separator {
    color: #dee2e6;
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
}

/* Responsive */
@media (max-width: 768px) {
    .table-asignaciones {
        font-size: 0.75rem;
    }
    .table-asignaciones thead th,
    .table-asignaciones tbody td {
        padding: 6px 10px;
    }
    .grupos-subtable li {
        font-size: 0.7rem;
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

.asignaciones-container .card {
    animation: fadeInUp 0.4s ease;
}
</style>

<div class="asignaciones-container">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 p-2 rounded-circle">
                    <i class="fas fa-tasks text-primary fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0">Asignaciones de Materias</h5>
                    <small class="text-muted">Gestiona qué maestros imparten cada materia en cada grupo</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="crear.php" class="btn" style="background: #8B0000; color: white; border: none;">
                    <i class="fas fa-plus me-1"></i> Nueva Asignación
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
                        <select class="form-select" id="filtro_semestre" onchange="filtrarAsignaciones()">
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
                               placeholder="Buscar por materia, grupo o maestro..." onkeyup="filtrarAsignaciones()">
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="total-badge">
                            <i class="fas fa-list"></i>
                            <span id="total_asignaciones"><?php echo count($asignaciones); ?></span>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-asignaciones" id="tabla_asignaciones">
                    <thead>
                        <tr>
                            <th style="width: 4%; text-align: center;">#</th>
                            <th style="width: 25%;">Materia</th>
                            <th style="width: 12%;">Clave</th>
                            <th style="width: 10%;">Semestre</th>
                            <th style="width: 8%;">Tipo</th>
                            <th style="width: 28%;">Grupos / Maestros</th>
                            <th style="width: 13%; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($asignaciones) > 0): ?>
                            <?php foreach ($asignaciones as $index => $asig): 
                                // Procesar grupos
                                $grupos = [];
                                if (!empty($asig['grupos_detalle'])) {
                                    $items = explode(';;', $asig['grupos_detalle']);
                                    foreach ($items as $item) {
                                        $parts = explode('||', $item);
                                        if (count($parts) >= 5) {
                                            $grupos[] = [
                                                'id' => $parts[0],
                                                'nombre' => $parts[1],
                                                'semestre_id' => $parts[2],
                                                'maestro_id' => $parts[3],
                                                'maestro_nombre' => $parts[4]
                                            ];
                                        }
                                    }
                                }
                                
                                $clase_sem = 'basico';
                                if ($asig['semestre_numero'] >= 3 && $asig['semestre_numero'] <= 4) {
                                    $clase_sem = 'especialidad';
                                } elseif ($asig['semestre_numero'] >= 5) {
                                    $clase_sem = 'nucleo';
                                }
                            ?>
                                <tr data-semestre="<?php echo $asig['semestre_id']; ?>" 
                                    data-materia="<?php echo strtolower($asig['materia_nombre']); ?>"
                                    data-grupos="<?php echo strtolower(implode(' ', array_column($grupos, 'nombre'))); ?>"
                                    data-maestros="<?php echo strtolower(implode(' ', array_column($grupos, 'maestro_nombre'))); ?>">
                                    <td style="text-align: center; font-weight: 600; color: #6c757d; font-size: 0.75rem;">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($asig['materia_nombre']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark" style="font-size: 0.7rem;">
                                            <?php echo htmlspecialchars($asig['materia_clave']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-semestre <?php echo $clase_sem; ?>" style="font-size: 0.65rem;">
                                            <?php echo $asig['semestre_numero']; ?>°
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-tipo <?php echo $asig['tipo_materia']; ?>" style="font-size: 0.6rem;">
                                            <?php 
                                            $tipo_label = $asig['tipo_materia'];
                                            if ($tipo_label == 'tronco') echo 'Tronco';
                                            elseif ($tipo_label == 'especialidad') echo 'Esp.';
                                            else echo 'Núcleo';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (count($grupos) > 0): ?>
                                            <ul class="grupos-subtable">
                                                <?php foreach ($grupos as $g): ?>
                                                    <li>
                                                        <span class="grupo-nombre"><?php echo htmlspecialchars($g['nombre']); ?></span>
                                                        <span class="separator">→</span>
                                                        <span class="maestro-nombre">
                                                            <i class="fas fa-user-tie me-1" style="font-size: 0.6rem;"></i>
                                                            <?php echo htmlspecialchars($g['maestro_nombre']); ?>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="text-muted">Sin grupos asignados</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="editar.php?materia_id=<?php echo $asig['materia_id']; ?>" 
                                               class="btn-action edit" title="Editar asignación (agregar/quitar maestros)">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="confirmarEliminarMateria(<?php echo $asig['materia_id']; ?>, '<?php echo htmlspecialchars($asig['materia_nombre']); ?>')" 
                                                    class="btn-action delete" title="Eliminar todas las asignaciones de esta materia">
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
                                        <i class="fas fa-tasks"></i>
                                        <h5>No hay asignaciones registradas</h5>
                                        <p>Comienza asignando materias a los grupos</p>
                                        <a href="crear.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Nueva Asignación
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
</div>

<script>
// ============================================
// FUNCIONES DE FILTRADO
// ============================================

function filtrarAsignaciones() {
    const semestre = document.getElementById('filtro_semestre').value;
    const busqueda = document.getElementById('filtro_busqueda').value.toLowerCase();
    const rows = document.querySelectorAll('#tabla_asignaciones tbody tr');
    let visibles = 0;
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        
        const semestreRow = row.getAttribute('data-semestre') || '';
        const materiaRow = row.getAttribute('data-materia') || '';
        const gruposRow = row.getAttribute('data-grupos') || '';
        const maestrosRow = row.getAttribute('data-maestros') || '';
        
        let mostrar = true;
        
        if (semestre && semestreRow !== semestre) {
            mostrar = false;
        }
        
        if (busqueda && !materiaRow.includes(busqueda) && !gruposRow.includes(busqueda) && !maestrosRow.includes(busqueda)) {
            mostrar = false;
        }
        
        row.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    
    document.getElementById('total_asignaciones').textContent = visibles;
}

// ============================================
// ELIMINAR TODAS LAS ASIGNACIONES DE UNA MATERIA
// ============================================

function confirmarEliminarMateria(materia_id, materia) {
    if (confirm('¿Estas seguro de eliminar TODAS las asignaciones de la materia "' + materia + '"?\nSe eliminarán todos los maestros y grupos asignados a esta materia.')) {
        window.location.href = 'eliminar_materia.php?materia_id=' + materia_id;
    }
}

// ============================================
// TECLA ESCAPE PARA LIMPIAR BÚSQUEDA
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtro_busqueda').addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            filtrarAsignaciones();
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>