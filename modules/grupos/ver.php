<?php
// modules/grupos/ver.php - Ver detalles del grupo (VERSIÓN MEJORADA)
session_start();

$page_title = 'Detalles del Grupo';
$page_icon = 'eye';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener información del grupo
$grupo = obtenerRegistro("
    SELECT g.*, s.numero as semestre_numero, s.tipo as semestre_tipo,
           e.nombre as especialidad_nombre, n.nombre as nucleo_nombre
    FROM grupos g
    JOIN semestres s ON g.semestre_id = s.id
    LEFT JOIN especialidades e ON g.especialidad_id = e.id
    LEFT JOIN nucleos n ON g.nucleo_actual = n.id
    WHERE g.id = ? AND g.activo = 1
", [$id]);

if (!$grupo) {
    header('Location: index.php');
    exit;
}

// Obtener materias del grupo (desde materias_grupos)
$materias = obtenerRegistros("
    SELECT 
        m.id,
        m.nombre as materia_nombre,
        m.clave as materia_clave,
        m.tipo_materia,
        CONCAT(ma.nombre, ' ', ma.apellido_paterno) as maestro_nombre,
        ma.id as maestro_id
    FROM materias_grupos mg
    JOIN materias m ON mg.materia_id = m.id
    JOIN maestros ma ON mg.maestro_id = ma.id
    WHERE mg.grupo_id = ?
    ORDER BY m.nombre ASC
", [$id]);

// Obtener horarios del grupo
$horarios = obtenerRegistros("
    SELECT h.*, 
           m.nombre as materia_nombre,
           m.clave as materia_clave,
           CONCAT(ma.nombre, ' ', ma.apellido_paterno) as maestro_nombre,
           s.nombre as salon_nombre
    FROM horarios h
    JOIN materias m ON h.materia_id = m.id
    JOIN maestros ma ON h.maestro_id = ma.id
    JOIN salones s ON h.salon_id = s.id
    WHERE h.grupo_id = ? AND h.activo = 1
    ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes'), h.hora_inicio
", [$id]);

// Contar materias y horarios
$total_materias = count($materias);
$total_horarios = count($horarios);

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS MEJORADOS PARA VER GRUPO
   ============================================ */
.grupo-detail .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.grupo-detail .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.grupo-detail .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.grupo-detail .card-header h5 i {
    color: #1976d2;
}

/* Avatar del grupo */
.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B0000, #5C0000);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: #ffffff;
    flex-shrink: 0;
}

/* Información del grupo */
.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f1f3f5;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item .label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.85rem;
}

.detail-item .value {
    font-weight: 500;
    color: #1a237e;
    font-size: 0.9rem;
}

.detail-item .value .badge-estado {
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
}

.badge-semestre {
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
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
    padding: 4px 12px;
    border-radius: 20px;
}

.badge-nucleo {
    background: #f3e5f5;
    color: #6a1b9a;
    font-size: 0.7rem;
    padding: 4px 12px;
    border-radius: 20px;
}

/* Tarjeta de estadísticas */
.stat-mini-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 12px 15px;
    text-align: center;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.stat-mini-card:hover {
    border-color: #8B0000;
    background: #fff5f5;
}

.stat-mini-card .stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1a237e;
}

.stat-mini-card .stat-label {
    font-size: 0.7rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Tabla de materias */
.materias-table {
    border-radius: 12px;
    overflow: hidden;
}

.materias-table thead {
    background: #1a237e;
    color: white;
}

.materias-table thead th {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 10px 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.materias-table tbody td {
    padding: 8px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.85rem;
}

.materias-table tbody tr:hover {
    background: #f8f9fa;
}

.badge-tipo {
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
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

/* Tabla de horarios */
.horario-table {
    border-radius: 12px;
    overflow: hidden;
}

.horario-table thead {
    background: #1a237e;
    color: white;
}

.horario-table thead th {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 10px 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.horario-table tbody td {
    padding: 8px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.85rem;
}

.horario-table tbody tr:hover {
    background: #f8f9fa;
}

.badge-dia {
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
}

.badge-dia.lunes { background: #e3f2fd; color: #1565c0; }
.badge-dia.martes { background: #fff3e0; color: #e65100; }
.badge-dia.miercoles { background: #f3e5f5; color: #6a1b9a; }
.badge-dia.jueves { background: #e8f5e9; color: #2e7d32; }
.badge-dia.viernes { background: #fce4ec; color: #c62828; }

/* Botones */
.btn-back {
    background: #e9ecef;
    color: #495057;
    border: none;
    border-radius: 10px;
    padding: 10px 25px;
    transition: all 0.3s ease;
    font-weight: 500;
    text-decoration: none;
}

.btn-back:hover {
    background: #dee2e6;
    transform: translateY(-2px);
}

.btn-edit {
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 25px;
    transition: all 0.3s ease;
    font-weight: 500;
    text-decoration: none;
}

.btn-edit:hover {
    background: #0d47a1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
    color: white;
}

.btn-horario {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 25px;
    transition: all 0.3s ease;
    font-weight: 500;
    text-decoration: none;
}

.btn-horario:hover {
    background: #1e7e34;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    color: white;
}

/* Estado vacío */
.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-state i {
    font-size: 3rem;
    color: #dee2e6;
    margin-bottom: 15px;
}

.empty-state h6 {
    color: #495057;
}

.empty-state p {
    color: #6c757d;
}

/* Responsive */
@media (max-width: 992px) {
    .avatar-circle {
        width: 60px;
        height: 60px;
        font-size: 1.8rem;
    }
}

@media (max-width: 768px) {
    .materias-table,
    .horario-table {
        font-size: 0.75rem;
    }
    .materias-table thead th,
    .materias-table tbody td,
    .horario-table thead th,
    .horario-table tbody td {
        padding: 6px 10px;
    }
}

/* Animación */
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

.grupo-detail .card {
    animation: fadeInUp 0.4s ease;
}
</style>

<div class="grupo-detail">
    <div class="row g-4">
        <!-- ==========================================
        COLUMNA IZQUIERDA - INFORMACIÓN DEL GRUPO
        ========================================== -->
        <div class="col-lg-4 col-md-5">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-circle">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($grupo['nombre']); ?></h5>
                            <small class="text-muted">ID: <?php echo $grupo['id']; ?></small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Datos del grupo -->
                    <div class="detail-item">
                        <span class="label">Nombre</span>
                        <span class="value"><?php echo htmlspecialchars($grupo['nombre']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Semestre</span>
                        <span class="value">
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
                            <small class="text-muted"><?php echo ucfirst($grupo['semestre_tipo']); ?></small>
                        </span>
                    </div>
                    
                    <?php if ($grupo['especialidad_nombre']): ?>
                    <div class="detail-item">
                        <span class="label">Especialidad</span>
                        <span class="value">
                            <span class="badge-especialidad">
                                <i class="fas fa-tag me-1"></i>
                                <?php echo htmlspecialchars($grupo['especialidad_nombre']); ?>
                            </span>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($grupo['nucleo_nombre']): ?>
                    <div class="detail-item">
                        <span class="label">Núcleo</span>
                        <span class="value">
                            <span class="badge-nucleo">
                                <i class="fas fa-layer-group me-1"></i>
                                <?php echo htmlspecialchars($grupo['nucleo_nombre']); ?>
                            </span>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <span class="label">Capacidad</span>
                        <span class="value">
                            <i class="fas fa-users me-1"></i>
                            <?php echo $grupo['capacidad']; ?> estudiantes
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Materias</span>
                        <span class="value">
                            <i class="fas fa-book me-1"></i>
                            <?php echo $total_materias; ?> asignadas
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Horarios</span>
                        <span class="value">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo $total_horarios; ?> programados
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Registrado</span>
                        <span class="value">
                            <i class="far fa-calendar-alt me-1"></i>
                            <?php echo date('d/m/Y', strtotime($grupo['created_at'])); ?>
                        </span>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="editar.php?id=<?php echo $id; ?>" class="btn-edit">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                        <a href="../horarios/crear.php?grupo_id=<?php echo $id; ?>" class="btn-horario">
                            <i class="fas fa-plus-circle me-1"></i> Horario
                        </a>
                        <a href="index.php" class="btn-back">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas rápidas -->
            <div class="row g-2 mt-3">
                <div class="col-6">
                    <div class="stat-mini-card">
                        <div class="stat-number"><?php echo $total_materias; ?></div>
                        <div class="stat-label">Materias</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-mini-card">
                        <div class="stat-number"><?php echo $total_horarios; ?></div>
                        <div class="stat-label">Horarios</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ==========================================
        COLUMNA DERECHA - MATERIAS Y HORARIOS
        ========================================== -->
        <div class="col-lg-8 col-md-7">
            <!-- ==========================================
            MATERIAS DEL GRUPO
            ========================================== -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-book text-primary"></i>
                        <h5 class="mb-0">Materias Asignadas</h5>
                        <span class="badge bg-secondary ms-2"><?php echo $total_materias; ?></span>
                    </div>
                    <a href="../materias/" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt"></i> Ver todas
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($total_materias > 0): ?>
                        <div class="table-responsive">
                            <table class="table materias-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 15%;">Clave</th>
                                        <th style="width: 40%;">Materia</th>
                                        <th style="width: 15%;">Tipo</th>
                                        <th style="width: 25%;">Maestro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materias as $index => $m): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <span class="badge bg-dark"><?php echo htmlspecialchars($m['materia_clave']); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($m['materia_nombre']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge-tipo <?php echo $m['tipo_materia']; ?>">
                                                    <?php 
                                                    $tipo_label = $m['tipo_materia'];
                                                    if ($tipo_label == 'tronco') echo 'Tronco';
                                                    elseif ($tipo_label == 'especialidad') echo 'Esp.';
                                                    else echo 'Núcleo';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <i class="fas fa-user-tie me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($m['maestro_nombre']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <h6>No hay materias asignadas</h6>
                            <p>Este grupo aún no tiene materias asignadas</p>
                            <a href="../materias/" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Asignar materias
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ==========================================
            HORARIOS DEL GRUPO
            ========================================== -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <h5 class="mb-0">Horarios del Grupo</h5>
                        <span class="badge bg-secondary ms-2"><?php echo $total_horarios; ?></span>
                    </div>
                    <a href="../horarios/crear.php?grupo_id=<?php echo $id; ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Asignar
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($total_horarios > 0): ?>
                        <div class="table-responsive">
                            <table class="table horario-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 12%;">Día</th>
                                        <th style="width: 18%;">Hora</th>
                                        <th style="width: 28%;">Materia</th>
                                        <th style="width: 22%;">Maestro</th>
                                        <th style="width: 20%;">Salón</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($horarios as $h): ?>
                                        <tr>
                                            <td>
                                                <span class="badge-dia <?php echo strtolower($h['dia_semana']); ?>">
                                                    <?php echo $h['dia_semana']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo substr($h['hora_inicio'], 0, 5); ?> - 
                                                <?php echo substr($h['hora_fin'], 0, 5); ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($h['materia_nombre']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($h['materia_clave']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($h['maestro_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($h['salon_nombre']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-alt"></i>
                            <h6>No hay horarios asignados</h6>
                            <p>Este grupo aún no tiene horarios programados</p>
                            <a href="../horarios/crear.php?grupo_id=<?php echo $id; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i> Asignar horario
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>