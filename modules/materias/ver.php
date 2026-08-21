<?php
// modules/materias/ver.php - Ver detalles de la materia (VERSIÓN CORREGIDA)
session_start();

$page_title = 'Detalles de la Materia';
$page_icon = 'eye';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener información de la materia
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
        WHERE m.id = ? AND m.activo = 1";

$materia = obtenerRegistro($sql, [$id]);

if (!$materia) {
    header('Location: index.php');
    exit;
}

// Obtener los grupos donde se imparte esta materia y sus maestros
$grupos_materia = obtenerRegistros("
    SELECT 
        g.id as grupo_id,
        g.nombre as grupo_nombre,
        g.semestre_id,
        ma.id as maestro_id,
        CONCAT(ma.nombre, ' ', ma.apellido_paterno) as maestro_nombre,
        ma.email as maestro_email
    FROM materias_grupos mg
    JOIN grupos g ON mg.grupo_id = g.id
    JOIN maestros ma ON mg.maestro_id = ma.id
    WHERE mg.materia_id = ?
    ORDER BY g.nombre ASC
", [$id]);

// Obtener horarios de la materia
$horarios = obtenerRegistros("
    SELECT h.*, 
           CONCAT(ma.nombre, ' ', ma.apellido_paterno) as maestro_nombre,
           s.nombre as salon_nombre,
           g.nombre as grupo_nombre
    FROM horarios h
    LEFT JOIN maestros ma ON h.maestro_id = ma.id
    LEFT JOIN salones s ON h.salon_id = s.id
    LEFT JOIN grupos g ON h.grupo_id = g.id
    WHERE h.materia_id = ? AND h.activo = 1
    ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes'), h.hora_inicio
", [$id]);

$total_horas = count($horarios);
$total_grupos = count($grupos_materia);

include '../../includes/header.php';
?>

<style>
/* ============================================
   ESTILOS MEJORADOS - ROJO COBAO
   ============================================ */
.materia-detail .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.materia-detail .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.materia-detail .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.materia-detail .card-header h5 i {
    color: #8B0000;
}

/* Avatar de materia */
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

/* Items de información */
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

.detail-item .value .badge-semestre {
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
}

.detail-item .value .badge-semestre.basico {
    background: #e3f2fd;
    color: #1565c0;
}

.detail-item .value .badge-semestre.especialidad {
    background: #fff3e0;
    color: #e65100;
}

.detail-item .value .badge-semestre.nucleo {
    background: #f3e5f5;
    color: #6a1b9a;
}

.badge-tipo {
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
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

/* Tarjetas de estadísticas */
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

/* Tabla de grupos - ROJO COBAO */
.grupos-table {
    border-radius: 12px;
    overflow: hidden;
}

.grupos-table thead {
    background: linear-gradient(135deg, #8B0000, #5C0000);
    color: white;
}

.grupos-table thead th {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 10px 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.grupos-table tbody td {
    padding: 8px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.85rem;
}

.grupos-table tbody tr:hover {
    background: #f8f9fa;
}

/* Tabla de horarios - ROJO COBAO */
.horario-table {
    border-radius: 12px;
    overflow: hidden;
}

.horario-table thead {
    background: linear-gradient(135deg, #8B0000, #5C0000);
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
    background: #8B0000;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 25px;
    transition: all 0.3s ease;
    font-weight: 500;
    text-decoration: none;
}

.btn-edit:hover {
    background: #5C0000;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 0, 0, 0.3);
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
    .materia-detail .card-header {
        padding: 15px;
    }
}

@media (max-width: 768px) {
    .horario-table,
    .grupos-table {
        font-size: 0.75rem;
    }
    .horario-table thead th,
    .horario-table tbody td,
    .grupos-table thead th,
    .grupos-table tbody td {
        padding: 6px 10px;
    }
    .detail-item {
        flex-direction: column;
        gap: 4px;
    }
    .detail-item .value {
        font-size: 0.85rem;
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

.materia-detail .card {
    animation: fadeInUp 0.4s ease;
}
</style>

<div class="materia-detail">
    <div class="row g-4">
        <!-- ==========================================
        COLUMNA IZQUIERDA - INFORMACIÓN DE LA MATERIA
        ========================================== -->
        <div class="col-lg-4 col-md-5">
            <div class="card detail-card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-circle">
                            <i class="fas fa-book"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($materia['nombre']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($materia['clave']); ?></small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Datos de la materia -->
                    <div class="detail-item">
                        <span class="label">Clave</span>
                        <span class="value"><?php echo htmlspecialchars($materia['clave']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Semestre</span>
                        <span class="value">
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
                                <?php echo ucfirst($materia['semestre_tipo']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Tipo</span>
                        <span class="value">
                            <span class="badge-tipo <?php echo $materia['tipo_materia']; ?>">
                                <?php echo ucfirst($materia['tipo_materia']); ?>
                            </span>
                        </span>
                    </div>
                    
                    <?php if ($materia['especialidad_nombre']): ?>
                    <div class="detail-item">
                        <span class="label">Especialidad</span>
                        <span class="value">
                            <span class="badge-especialidad">
                                <i class="fas fa-tag me-1"></i>
                                <?php echo htmlspecialchars($materia['especialidad_nombre']); ?>
                            </span>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($materia['nucleo_nombre']): ?>
                    <div class="detail-item">
                        <span class="label">Núcleo</span>
                        <span class="value">
                            <span class="badge-nucleo">
                                <i class="fas fa-layer-group me-1"></i>
                                <?php echo htmlspecialchars($materia['nucleo_nombre']); ?>
                            </span>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <span class="label">Créditos</span>
                        <span class="value">
                            <i class="fas fa-star me-1" style="color: #f1c40f;"></i>
                            <?php echo $materia['creditos']; ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Grupos donde se imparte</span>
                        <span class="value">
                            <i class="fas fa-users me-1"></i>
                            <?php echo $total_grupos; ?> grupos
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Horarios</span>
                        <span class="value">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo $total_horas; ?> programados
                        </span>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="editar.php?id=<?php echo $id; ?>" class="btn-edit">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                        <a href="../horarios/crear.php?materia_id=<?php echo $id; ?>" class="btn-horario">
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
                        <div class="stat-number"><?php echo $total_grupos; ?></div>
                        <div class="stat-label">Grupos</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-mini-card">
                        <div class="stat-number"><?php echo $total_horas; ?></div>
                        <div class="stat-label">Horarios</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ==========================================
        COLUMNA DERECHA - GRUPOS Y HORARIOS
        ========================================== -->
        <div class="col-lg-8 col-md-7">
            <!-- ==========================================
            GRUPOS DONDE SE IMPARTE
            ========================================== -->
            <div class="card detail-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-users text-primary"></i>
                        <h5 class="mb-0">Grupos donde se imparte</h5>
                        <span class="badge bg-secondary ms-2"><?php echo $total_grupos; ?></span>
                    </div>
                    <a href="../grupos/" class="btn btn-sm" style="background: #8B0000; color: white;">
                        <i class="fas fa-external-link-alt"></i> Ver todos
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($total_grupos > 0): ?>
                        <div class="table-responsive">
                            <table class="table grupos-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 25%;">Grupo</th>
                                        <th style="width: 40%;">Maestro Responsable</th>
                                        <th style="width: 15%;">Semestre</th>
                                        <th style="width: 15%; text-align: center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grupos_materia as $index => $g): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($g['grupo_nombre']); ?></strong>
                                            </td>
                                            <td>
                                                <i class="fas fa-user-tie me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($g['maestro_nombre']); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $clase_sem = 'basico';
                                                if ($g['semestre_id'] >= 3 && $g['semestre_id'] <= 4) {
                                                    $clase_sem = 'especialidad';
                                                } elseif ($g['semestre_id'] >= 5) {
                                                    $clase_sem = 'nucleo';
                                                }
                                                ?>
                                                <span class="badge-semestre <?php echo $clase_sem; ?>" style="font-size: 0.65rem; padding: 2px 10px;">
                                                    <?php echo $g['semestre_id']; ?>°
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="../grupos/ver.php?id=<?php echo $g['grupo_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Ver grupo">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h6>No se imparte en ningún grupo</h6>
                            <p>Esta materia no está asignada a ningún grupo</p>
                            <a href="../asignaciones/crear.php?materia_id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Asignar a grupo
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ==========================================
            HORARIOS DE LA MATERIA
            ========================================== -->
            <div class="card detail-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <h5 class="mb-0">Horarios de la Materia</h5>
                        <span class="badge bg-secondary ms-2"><?php echo $total_horas; ?></span>
                    </div>
                    <a href="../horarios/crear.php?materia_id=<?php echo $id; ?>" class="btn btn-sm" style="background: #28a745; color: white;">
                        <i class="fas fa-plus"></i> Asignar
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($total_horas > 0): ?>
                        <div class="table-responsive">
                            <table class="table horario-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 12%;">Día</th>
                                        <th style="width: 18%;">Hora</th>
                                        <th style="width: 22%;">Maestro</th>
                                        <th style="width: 18%;">Salón</th>
                                        <th style="width: 15%;">Grupo</th>
                                        <th style="width: 15%; text-align: center;">Acción</th>
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
                                                <i class="fas fa-user-tie me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($h['maestro_nombre'] ?? '-'); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-door-open me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($h['salon_nombre'] ?? '-'); ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($h['grupo_nombre'] ?? '-'); ?></strong>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="../horarios/editar.php?id=<?php echo $h['id']; ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Editar horario">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-alt"></i>
                            <h6>No hay horarios asignados</h6>
                            <p>Esta materia no tiene horarios programados</p>
                            <a href="../horarios/crear.php?materia_id=<?php echo $id; ?>" class="btn btn-success btn-sm">
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