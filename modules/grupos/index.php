<?php
// modules/grupos/index.php - Listado de grupos (VERSIÓN MEJORADA)
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
   ESTILOS MEJORADOS - ROJO COBAO CON ANIMACIONES
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

.grupos-container .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeInUp 0.6s ease;
}

.grupos-container .card:hover {
    box-shadow: 0 8px 40px rgba(139, 0, 0, 0.12);
    transform: translateY(-2px);
}

.grupos-container .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
    transition: all 0.4s ease;
}

.grupos-container .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.grupos-container .card-header h5 i {
    color: #8B0000;
    transition: all 0.3s ease;
}

.grupos-container .card-header h5 i:hover {
    transform: rotate(15deg) scale(1.1);
}

/* Filtros con animación */
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
    transform: scale(1.02);
}

/* Tabla mejorada con animaciones */
.table-grupos {
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.table-grupos thead {
    background: linear-gradient(135deg, #8B0000, #5C0000);
    color: white;
    position: relative;
    overflow: hidden;
}

.table-grupos thead::after {
    content: '';
    position: absolute;
    top: 0;
    left: -200%;
    width: 200%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    animation: shimmer 3s infinite;
}

.table-grupos thead th {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 12px 15px;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    z-index: 1;
}

.table-grupos tbody td {
    padding: 10px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    transition: all 0.3s ease;
}

.table-grupos tbody tr {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.table-grupos tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01) translateY(-2px);
    box-shadow: 0 4px 20px rgba(139, 0, 0, 0.08);
    z-index: 2;
}

.table-grupos tbody tr:active {
    transform: scale(0.99);
}

/* Badges con animaciones */
.badge-semestre {
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    display: inline-block;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge-semestre:hover {
    transform: scale(1.1) rotate(-2deg);
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
    font-size: 0.65rem;
    padding: 3px 10px;
    border-radius: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-block;
}

.badge-especialidad:hover {
    background: #1565c0;
    color: white;
    transform: scale(1.05);
}

.badge-nucleo {
    background: #f3e5f5;
    color: #6a1b9a;
    font-size: 0.65rem;
    padding: 3px 10px;
    border-radius: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-block;
}

.badge-nucleo:hover {
    background: #6a1b9a;
    color: white;
    transform: scale(1.05);
}

.badge-grupo {
    font-weight: 700;
    font-size: 1rem;
    color: #1a237e;
    transition: all 0.3s ease;
    display: inline-block;
}

.badge-grupo:hover {
    color: #8B0000;
    transform: scale(1.05);
}

.badge-horarios {
    background: #e9ecef;
    color: #495057;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-block;
}

.badge-horarios:hover {
    background: #8B0000;
    color: white;
    transform: scale(1.1) rotate(5deg);
}

.badge-materias {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-block;
}

.badge-materias:hover {
    background: #2e7d32;
    color: white;
    transform: scale(1.1) rotate(-5deg);
}

/* Botones de acción con animaciones mejoradas */
.btn-action {
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    font-size: 0.85rem;
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

.btn-action.horarios {
    background: #fff3e0;
    color: #e65100;
}

.btn-action.horarios:hover {
    background: #e65100;
    color: white;
    animation: glow 1.5s infinite;
}

/* Total badge con animación */
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

/* Tarjetas de semestres con animaciones mejoradas */
.semestre-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 15px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
    background: white;
    text-align: center;
    animation: slideIn 0.5s ease;
    position: relative;
    overflow: hidden;
}

.semestre-card::before {
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

.semestre-card:hover {
    border-color: #8B0000;
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 40px rgba(139, 0, 0, 0.15);
}

.semestre-card:hover::before {
    opacity: 1;
}

.semestre-card .semestre-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a237e;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
}

.semestre-card:hover .semestre-number {
    color: #8B0000;
    transform: scale(1.2);
}

.semestre-card .semestre-type {
    font-size: 0.7rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    z-index: 1;
}

.semestre-card .semestre-count {
    font-size: 2rem;
    font-weight: 700;
    color: #8B0000;
    margin: 5px 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
}

.semestre-card:hover .semestre-count {
    transform: scale(1.2);
    animation: pulse 1s infinite;
}

.semestre-card .semestre-label {
    font-size: 0.75rem;
    color: #6c757d;
    position: relative;
    z-index: 1;
}

/* Botón Nuevo Grupo */
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

/* Scrollbar personalizada */
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
@media (max-width: 768px) {
    .filter-section .row {
        gap: 10px;
    }
    .table-grupos {
        font-size: 0.75rem;
    }
    .table-grupos thead th,
    .table-grupos tbody td {
        padding: 6px 8px;
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

/* Delay de animación para tarjetas de semestre */
.semestre-card:nth-child(1) { animation-delay: 0.05s; }
.semestre-card:nth-child(2) { animation-delay: 0.1s; }
.semestre-card:nth-child(3) { animation-delay: 0.15s; }
.semestre-card:nth-child(4) { animation-delay: 0.2s; }
.semestre-card:nth-child(5) { animation-delay: 0.25s; }
.semestre-card:nth-child(6) { animation-delay: 0.3s; }
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
                <a href="crear.php" class="btn-primary-cobao">
                    <i class="fas fa-plus"></i> Nuevo Grupo
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
                                    <td style="text-align: center; font-weight: 600; color: #6c757d; font-size: 0.75rem;">
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
                                        <small class="text-muted" style="font-size: 0.6rem;">
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
                                            <span class="text-muted" style="font-size: 0.75rem;">-</span>
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
                                        <a href="crear.php" class="btn-primary-cobao">
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
    
    // Animación de entrada para las tarjetas de semestre
    const cards = document.querySelectorAll('.semestre-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.1) + 's';
    });
});
</script>

<?php include '../../includes/footer.php'; ?>