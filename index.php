<?php
// index.php - Dashboard Principal Mejorado
session_start();

// Configuración de página
$page_title = 'Panel de Control';
$page_icon = 'chart-line';

// Incluir configuraciones y funciones
require_once 'config/database.php';
require_once 'includes/functions.php';

// Obtener estadísticas
$stats = obtenerEstadisticasCompletas();

// Obtener horarios de hoy
$horarios_hoy = obtenerHorariosHoy();

// Obtener alertas del sistema
$alertas = obtenerAlertasSistema();

// Obtener grupos por semestre
$grupos_por_semestre = obtenerGruposPorSemestre();

// Obtener últimas materias agregadas
$ultimas_materias = obtenerRegistros("
    SELECT nombre, clave, created_at 
    FROM materias 
    WHERE activo = 1 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Obtener maestros con más horas
$maestros_top = obtenerRegistros("
    SELECT 
        CONCAT(ma.nombre, ' ', ma.apellido_paterno) as nombre,
        COUNT(h.id) as total_horas
    FROM maestros ma
    LEFT JOIN horarios h ON ma.id = h.maestro_id AND h.activo = 1
    WHERE ma.activo = 1
    GROUP BY ma.id
    ORDER BY total_horas DESC
    LIMIT 5
");

include 'includes/header.php';
?>

<style>
/* ============================================
   ESTILOS MEJORADOS PARA EL DASHBOARD
   ============================================ */

/* Tarjetas de estadísticas */
.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    border: 1px solid #f1f3f5;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.stat-card .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 12px;
}

.stat-card .stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #1a237e;
    line-height: 1.2;
}

.stat-card .stat-label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 500;
    margin-top: 4px;
}

.stat-card .stat-change {
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 10px;
    border-radius: 20px;
}

.stat-card .stat-change.up {
    color: #2e7d32;
    background: #e8f5e9;
}

.stat-card .stat-change.down {
    color: #c62828;
    background: #ffebee;
}

.stat-card .stat-bg {
    position: absolute;
    right: -10px;
    bottom: -10px;
    font-size: 6rem;
    opacity: 0.05;
    color: #1a237e;
}

/* Colores de estadísticas */
.stat-card.primary .stat-icon { background: #e3f2fd; color: #1565c0; }
.stat-card.success .stat-icon { background: #e8f5e9; color: #2e7d32; }
.stat-card.warning .stat-icon { background: #fff3e0; color: #e65100; }
.stat-card.info .stat-icon { background: #e0f7fa; color: #00838f; }
.stat-card.purple .stat-icon { background: #f3e5f5; color: #6a1b9a; }
.stat-card.danger .stat-icon { background: #ffebee; color: #c62828; }

/* Acciones rápidas */
.quick-action {
    background: white;
    border-radius: 12px;
    padding: 15px 20px;
    text-align: center;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #495057;
    border: 2px solid #f1f3f5;
    display: block;
    height: 100%;
}

.quick-action:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: #1976d2;
    color: #1976d2;
}

.quick-action i {
    font-size: 1.8rem;
    display: block;
    margin-bottom: 8px;
}

.quick-action .qa-label {
    font-size: 0.8rem;
    font-weight: 600;
}

.quick-action .qa-badge {
    font-size: 0.65rem;
    background: #1976d2;
    color: white;
    padding: 2px 10px;
    border-radius: 20px;
    display: inline-block;
    margin-top: 4px;
}

/* Tabla de horarios */
.schedule-table {
    border-radius: 12px;
    overflow: hidden;
}

.schedule-table thead {
    background: #1a237e;
    color: white;
}

.schedule-table thead th {
    font-weight: 600;
    font-size: 0.8rem;
    padding: 12px 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.schedule-table tbody td {
    padding: 10px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
}

.schedule-table tbody tr:hover {
    background: #f8f9fa;
}

/* Badge de rotación */
.badge-rotacion {
    background: #ff9800;
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 600;
}

.badge-nucleo {
    background: #9c27b0;
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 600;
}

/* Alertas */
.alert-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 15px;
    border-radius: 10px;
    margin-bottom: 8px;
    border-left: 4px solid;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.alert-item:last-child {
    margin-bottom: 0;
}

.alert-item .alert-icon {
    font-size: 1.2rem;
    margin-top: 2px;
}

.alert-item .alert-content {
    flex: 1;
}

.alert-item .alert-content .alert-title {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1a237e;
}

.alert-item .alert-content .alert-message {
    font-size: 0.8rem;
    color: #6c757d;
}

.alert-item.warning { border-color: #ff9800; }
.alert-item.danger { border-color: #f44336; }
.alert-item.info { border-color: #2196f3; }
.alert-item.success { border-color: #4caf50; }

.alert-item.warning .alert-icon { color: #ff9800; }
.alert-item.danger .alert-icon { color: #f44336; }
.alert-item.info .alert-icon { color: #2196f3; }
.alert-item.success .alert-icon { color: #4caf50; }

/* Lista de últimas materias */
.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-list li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f3f5;
}

.activity-list li:last-child {
    border-bottom: none;
}

.activity-list .activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e3f2fd;
    color: #1565c0;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.activity-list .activity-text {
    flex: 1;
    font-size: 0.85rem;
}

.activity-list .activity-text .clave {
    font-weight: 600;
    color: #1a237e;
}

.activity-list .activity-time {
    font-size: 0.7rem;
    color: #6c757d;
}

/* Top maestros */
.top-maestro {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #f1f3f5;
}

.top-maestro:last-child {
    border-bottom: none;
}

.top-maestro .position {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.75rem;
    color: #495057;
    flex-shrink: 0;
}

.top-maestro .position.gold { background: #fff3e0; color: #e65100; }
.top-maestro .position.silver { background: #f5f5f5; color: #616161; }
.top-maestro .position.bronze { background: #fbe9e7; color: #bf360c; }

.top-maestro .maestro-info {
    flex: 1;
}

.top-maestro .maestro-info .nombre {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1a237e;
}

.top-maestro .maestro-info .horas {
    font-size: 0.7rem;
    color: #6c757d;
}

.top-maestro .maestro-bar {
    width: 100%;
    height: 4px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 4px;
}

.top-maestro .maestro-bar .bar-fill {
    height: 100%;
    border-radius: 4px;
    background: linear-gradient(90deg, #1976d2, #42a5f5);
    transition: width 1s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .stat-card .stat-number {
        font-size: 1.5rem;
    }
    .quick-action {
        padding: 12px 15px;
    }
    .quick-action i {
        font-size: 1.4rem;
    }
    .schedule-table {
        font-size: 0.8rem;
    }
    .schedule-table thead th,
    .schedule-table tbody td {
        padding: 6px 10px;
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

.dashboard-section {
    animation: fadeInUp 0.4s ease;
}

/* Badge de estado del sistema */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.online {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-badge .status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #4caf50;
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>

<div class="dashboard-section">
    <!-- ========================================== -->
    <!-- TOP BAR MEJORADO -->
    <!-- ========================================== -->
    <div class="top-bar">
        <div>
            <h4>
                <i class="fas fa-chart-line text-primary"></i> Panel de Control
            </h4>
            <span class="fecha-actual">
                <i class="far fa-calendar-alt"></i> 
                <?php echo date('l, d \d\e F \d\e Y'); ?>
            </span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="status-badge online">
                <span class="status-dot"></span>
                Sistema Activo
            </span>
            <span class="badge bg-primary p-2">
                <i class="fas fa-user"></i> Admin
            </span>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TARJETAS DE ESTADÍSTICAS MEJORADAS -->
    <!-- ========================================== -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_grupos'] ?? 0; ?></div>
                <div class="stat-label">Grupos Registrados</div>
                <div class="stat-change up">
                    <i class="fas fa-arrow-up"></i> <?php echo $stats['grupos_activos'] ?? 0; ?> activos
                </div>
                <div class="stat-bg"><i class="fas fa-users"></i></div>
            </div>
        </div>
        
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_maestros'] ?? 0; ?></div>
                <div class="stat-label">Maestros</div>
                <div class="stat-change up">
                    <i class="fas fa-user-check"></i> <?php echo $stats['maestros_disponibles'] ?? 0; ?> disponibles
                </div>
                <div class="stat-bg"><i class="fas fa-chalkboard-teacher"></i></div>
            </div>
        </div>
        
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_materias'] ?? 0; ?></div>
                <div class="stat-label">Materias</div>
                <div class="stat-change up">
                    <i class="fas fa-graduation-cap"></i> <?php echo $stats['materias_activas'] ?? 0; ?> activas
                </div>
                <div class="stat-bg"><i class="fas fa-book"></i></div>
            </div>
        </div>
        
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_horarios'] ?? 0; ?></div>
                <div class="stat-label">Horarios Asignados</div>
                <div class="stat-change up">
                    <i class="fas fa-clock"></i> <?php echo $stats['horarios_hoy'] ?? 0; ?> para hoy
                </div>
                <div class="stat-bg"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ACCIONES RÁPIDAS MEJORADAS -->
    <!-- ========================================== -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h5 class="mb-3">
                <i class="fas fa-bolt text-warning"></i> Acciones Rápidas
                <small class="text-muted fw-normal">Gestiona el sistema de manera eficiente</small>
            </h5>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
            <a href="modules/grupos/crear.php" class="quick-action">
                <i class="fas fa-user-plus text-primary"></i>
                <span class="qa-label">Nuevo Grupo</span>
                <span class="qa-badge">+</span>
            </a>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
            <a href="modules/maestros/crear.php" class="quick-action">
                <i class="fas fa-user-tie text-success"></i>
                <span class="qa-label">Nuevo Maestro</span>
                <span class="qa-badge">+</span>
            </a>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
            <a href="modules/materias/crear.php" class="quick-action">
                <i class="fas fa-book-open text-warning"></i>
                <span class="qa-label">Nueva Materia</span>
                <span class="qa-badge">+</span>
            </a>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
            <a href="modules/horarios/crear.php" class="quick-action">
                <i class="fas fa-plus-circle text-danger"></i>
                <span class="qa-label">Asignar Horario</span>
                <span class="qa-badge">+</span>
            </a>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
            <a href="modules/horarios/generar.php" class="quick-action">
                <i class="fas fa-magic text-info"></i>
                <span class="qa-label">Generar Horarios</span>
                <span class="qa-badge">Auto</span>
            </a>
        </div>
        
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
            <a href="reports/" class="quick-action">
                <i class="fas fa-file-pdf text-danger"></i>
                <span class="qa-label">Reportes</span>
                <span class="qa-badge">PDF</span>
            </a>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- CONTENIDO PRINCIPAL MEJORADO -->
    <!-- ========================================== -->
    <div class="row g-4 mb-4">
        <!-- Alertas del Sistema -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle text-warning"></i> Alertas del Sistema
                    </h5>
                    <span class="badge bg-secondary"><?php echo count($alertas); ?></span>
                </div>
                <div class="card-body">
                    <?php if (count($alertas) > 0): ?>
                        <?php foreach ($alertas as $alerta): ?>
                            <div class="alert-item <?php echo $alerta['tipo']; ?>">
                                <div class="alert-icon">
                                    <i class="fas fa-<?php echo $alerta['icono']; ?>"></i>
                                </div>
                                <div class="alert-content">
                                    <div class="alert-title"><?php echo $alerta['titulo']; ?></div>
                                    <div class="alert-message"><?php echo $alerta['mensaje']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            <p class="mt-2 text-muted mb-0">No hay alertas pendientes</p>
                            <small class="text-success">Todo funciona correctamente</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Últimas Materias Agregadas -->
        <div class="col-xl-4 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock text-primary"></i> Últimas Materias
                    </h5>
                    <a href="modules/materias/" class="btn btn-sm btn-outline-primary">Ver todas</a>
                </div>
                <div class="card-body">
                    <?php if (count($ultimas_materias) > 0): ?>
                        <ul class="activity-list">
                            <?php foreach ($ultimas_materias as $materia): ?>
                                <li>
                                    <div class="activity-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="activity-text">
                                        <span class="clave"><?php echo htmlspecialchars($materia['clave']); ?></span>
                                        <span><?php echo htmlspecialchars($materia['nombre']); ?></span>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('d/m/Y', strtotime($materia['created_at'])); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-book-open text-muted" style="font-size: 2rem;"></i>
                            <p class="mt-2 text-muted">No hay materias registradas</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Maestros -->
        <div class="col-xl-4 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy text-warning"></i> Maestros con más horas
                    </h5>
                    <a href="modules/maestros/" class="btn btn-sm btn-outline-primary">Ver todos</a>
                </div>
                <div class="card-body">
                    <?php if (count($maestros_top) > 0): ?>
                        <?php 
                        $max_horas = $maestros_top[0]['total_horas'] ?? 1;
                        $posiciones = ['gold', 'silver', 'bronze', '', ''];
                        ?>
                        <?php foreach ($maestros_top as $index => $maestro): ?>
                            <div class="top-maestro">
                                <div class="position <?php echo $posiciones[$index] ?? ''; ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div class="maestro-info">
                                    <div class="nombre"><?php echo htmlspecialchars($maestro['nombre']); ?></div>
                                    <div class="horas"><?php echo $maestro['total_horas']; ?> horas asignadas</div>
                                    <div class="maestro-bar">
                                        <div class="bar-fill" style="width: <?php echo ($maestro['total_horas'] / $max_horas) * 100; ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chalkboard-teacher text-muted" style="font-size: 2rem;"></i>
                            <p class="mt-2 text-muted">No hay maestros registrados</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- HORARIOS DE HOY MEJORADOS -->
    <!-- ========================================== -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day text-primary"></i> 
                        Horarios de Hoy - <?php echo date('d/m/Y'); ?>
                    </h5>
                    <div class="d-flex gap-2">
                        <span class="badge bg-primary"><?php echo count($horarios_hoy); ?> clases</span>
                        <a href="modules/horarios/" class="btn btn-sm btn-outline-primary">
                            Ver todos <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table schedule-table mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-users"></i> Grupo</th>
                                    <th><i class="fas fa-book"></i> Materia</th>
                                    <th><i class="fas fa-chalkboard-teacher"></i> Maestro</th>
                                    <th><i class="fas fa-door-open"></i> Salón</th>
                                    <th><i class="fas fa-clock"></i> Hora</th>
                                    <th><i class="fas fa-info-circle"></i> Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($horarios_hoy) > 0): ?>
                                    <?php foreach ($horarios_hoy as $horario): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($horario['grupo'] ?? 'N/A'); ?></strong></td>
                                            <td><?php echo htmlspecialchars($horario['materia'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($horario['maestro'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($horario['salon'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php echo $horario['hora_inicio'] ?? '--:--'; ?> - 
                                                <?php echo $horario['hora_fin'] ?? '--:--'; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($horario['es_rotacion']) && $horario['es_rotacion']): ?>
                                                    <span class="badge-rotacion">
                                                        <i class="fas fa-sync-alt"></i> Rotación
                                                    </span>
                                                    <?php if (isset($horario['nucleo']) && $horario['nucleo']): ?>
                                                        <span class="badge-nucleo">
                                                            <?php echo htmlspecialchars($horario['nucleo']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Normal</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-calendar-alt text-muted" style="font-size: 2rem;"></i>
                                            <p class="mt-2 text-muted mb-0">No hay horarios programados para hoy</p>
                                            <small class="text-muted">Disfruta tu día libre</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- FOOTER -->
    <!-- ========================================== -->
    <footer class="mt-4 text-center text-muted">
        <hr>
        <p class="mb-0">
            <i class="fas fa-graduation-cap"></i> COBAO - Colegio de Bachilleres del Estado de Oaxaca
            <br>
            <small>Sistema de Gestión de Horarios v2.0 | <?php echo date('Y'); ?></small>
        </p>
    </footer>
</div>

<?php include 'includes/footer.php'; ?>