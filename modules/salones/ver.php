<?php
// modules/salones/ver.php - Ver detalles del salón
session_start();

$page_title = 'Detalles del Salón';
$page_icon = 'eye';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener datos del salón
$sql_salon = "SELECT 
                    s.*,
                    COUNT(h.id) as total_horarios,
                    COUNT(DISTINCT h.materia_id) as total_materias
                FROM salones s
                LEFT JOIN horarios h ON s.id = h.salon_id AND h.activo = 1
                WHERE s.id = ? AND s.activo = 1
                GROUP BY s.id";

$salon = obtenerRegistro($sql_salon, [$id]);

if (!$salon) {
    header('Location: index.php');
    exit;
}

// Obtener horarios del salón
$horarios = obtenerRegistros("
    SELECT h.*, 
           g.nombre as grupo_nombre,
           m.nombre as materia_nombre,
           m.clave as materia_clave,
           CONCAT(ma.nombre, ' ', ma.apellido_paterno) as maestro_nombre
    FROM horarios h
    LEFT JOIN grupos g ON h.grupo_id = g.id
    LEFT JOIN materias m ON h.materia_id = m.id
    LEFT JOIN maestros ma ON h.maestro_id = ma.id
    WHERE h.salon_id = ? AND h.activo = 1
    ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes'), h.hora_inicio
", [$id]);

include '../../includes/header.php';
?>

<style>
.salon-detail .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.salon-detail .card-header {
    background: linear-gradient(135deg, #f8f9fa, #fff);
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 20px 25px;
}

.salon-detail .card-header h5 {
    font-weight: 700;
    color: #1a237e;
}

.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #e3f2fd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: #1565c0;
}

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

.detail-item .value .badge-estado.disponible {
    background: #e8f5e9;
    color: #2e7d32;
}

.detail-item .value .badge-estado.ocupado {
    background: #ffebee;
    color: #c62828;
}

.detail-item .value .badge-tipo {
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
}

.detail-item .value .badge-tipo.aula {
    background: #e3f2fd;
    color: #1565c0;
}

.detail-item .value .badge-tipo.laboratorio {
    background: #fff3e0;
    color: #e65100;
}

.detail-item .value .badge-tipo.taller {
    background: #f3e5f5;
    color: #6a1b9a;
}

.detail-item .value .badge-tipo.auditorio {
    background: #e8f5e9;
    color: #2e7d32;
}

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
    font-size: 0.8rem;
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

.empty-horarios {
    text-align: center;
    padding: 40px 20px;
}

.empty-horarios i {
    font-size: 3rem;
    color: #dee2e6;
    margin-bottom: 15px;
}

.empty-horarios p {
    color: #6c757d;
}

@media (max-width: 768px) {
    .avatar-circle {
        width: 60px;
        height: 60px;
        font-size: 1.8rem;
    }
    .horario-table {
        font-size: 0.75rem;
    }
    .horario-table thead th,
    .horario-table tbody td {
        padding: 6px 10px;
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

.salon-detail .card {
    animation: fadeInUp 0.4s ease;
}
</style>

<div class="salon-detail">
    <div class="row g-4">
        <!-- Columna izquierda: Información del salón -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-circle">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($salon['nombre']); ?></h5>
                            <small class="text-muted">ID: <?php echo $salon['id']; ?></small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="detail-item">
                        <span class="label">Nombre</span>
                        <span class="value"><?php echo htmlspecialchars($salon['nombre']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Tipo</span>
                        <span class="value">
                            <span class="badge-tipo <?php echo $salon['tipo']; ?>">
                                <i class="fas fa-<?php 
                                    echo $salon['tipo'] == 'aula' ? 'chalkboard' : 
                                        ($salon['tipo'] == 'laboratorio' ? 'flask' : 
                                        ($salon['tipo'] == 'taller' ? 'tools' : 'microphone')); 
                                ?>"></i>
                                <?php echo ucfirst($salon['tipo']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Capacidad</span>
                        <span class="value">
                            <i class="fas fa-users me-1"></i>
                            <?php echo $salon['capacidad']; ?> estudiantes
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Ubicación</span>
                        <span class="value">
                            <?php if ($salon['ubicacion']): ?>
                                <i class="fas fa-location-dot me-1"></i>
                                <?php echo htmlspecialchars($salon['ubicacion']); ?>
                            <?php else: ?>
                                <span class="text-muted">No especificada</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Estado</span>
                        <span class="value">
                            <?php if ($salon['disponible']): ?>
                                <span class="badge-estado disponible">
                                    <i class="fas fa-check-circle me-1"></i> Disponible
                                </span>
                            <?php else: ?>
                                <span class="badge-estado ocupado">
                                    <i class="fas fa-times-circle me-1"></i> Ocupado
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Horas ocupadas</span>
                        <span class="value"><?php echo $salon['total_horarios'] ?? 0; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Materias</span>
                        <span class="value"><?php echo $salon['total_materias'] ?? 0; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Registrado</span>
                        <span class="value"><?php echo date('d/m/Y', strtotime($salon['created_at'])); ?></span>
                    </div>
                    
                    <div class="d-flex gap-2 mt-3">
                        <a href="editar.php?id=<?php echo $id; ?>" class="btn-edit">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                        <a href="index.php" class="btn-back">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Columna derecha: Horarios del salón -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 p-2 rounded-circle">
                            <i class="fas fa-clock text-success fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Horarios en este Salón</h5>
                            <small class="text-muted"><?php echo $salon['total_horarios'] ?? 0; ?> horas programadas</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (count($horarios) > 0): ?>
                        <div class="table-responsive">
                            <table class="table horario-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Día</th>
                                        <th>Hora</th>
                                        <th>Materia</th>
                                        <th>Maestro</th>
                                        <th>Grupo</th>
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
                                                <?php echo htmlspecialchars($h['materia_nombre'] ?? 'N/A'); ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($h['materia_clave'] ?? ''); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($h['maestro_nombre'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($h['grupo_nombre'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-horarios">
                            <i class="fas fa-calendar-alt"></i>
                            <p>Este salón no tiene horarios asignados</p>
                            <a href="../horarios/crear.php?salon_id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
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