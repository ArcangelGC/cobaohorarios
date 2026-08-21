<?php
// includes/functions.php
// Funciones auxiliares para el sistema (VERSIÓN MEJORADA)

require_once __DIR__ . '/../config/database.php';

/**
 * Obtener estadísticas completas para el dashboard
 */
function obtenerEstadisticasCompletas() {
    $stats = [];
    
    // Total de grupos
    $result = ejecutarConsulta("SELECT COUNT(*) as total FROM grupos WHERE activo = 1");
    $stats['total_grupos'] = $result->fetch_assoc()['total'] ?? 0;
    $stats['grupos_activos'] = $stats['total_grupos'];
    
    // Total de maestros
    $result = ejecutarConsulta("SELECT COUNT(*) as total FROM maestros WHERE activo = 1");
    $stats['total_maestros'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Maestros disponibles
    $result = ejecutarConsulta("SELECT COUNT(*) as total FROM maestros WHERE disponible = 1 AND activo = 1");
    $stats['maestros_disponibles'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Total de materias
    $result = ejecutarConsulta("SELECT COUNT(*) as total FROM materias WHERE activo = 1");
    $stats['total_materias'] = $result->fetch_assoc()['total'] ?? 0;
    $stats['materias_activas'] = $stats['total_materias'];
    
    // Total de horarios
    $result = ejecutarConsulta("SELECT COUNT(*) as total FROM horarios WHERE activo = 1");
    $stats['total_horarios'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Horarios de hoy
    $dia_semana = obtenerDiaSemana();
    $result = ejecutarConsulta("SELECT COUNT(*) as total FROM horarios WHERE dia_semana = ? AND activo = 1", [$dia_semana]);
    $stats['horarios_hoy'] = $result->fetch_assoc()['total'] ?? 0;
    
    return $stats;
}

/**
 * Obtener el día de la semana en español
 */
function obtenerDiaSemana() {
    $dias = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    $dia_ingles = date('l');
    return $dias[$dia_ingles] ?? 'Lunes';
}

/**
 * Obtener horarios del día actual
 */
function obtenerHorariosHoy() {
    $dia_semana = obtenerDiaSemana();
    
    try {
        $sql = "SELECT 
                    g.nombre as grupo,
                    m.nombre as materia,
                    CONCAT(ma.nombre, ' ', ma.apellido_paterno) as maestro,
                    s.nombre as salon,
                    h.dia_semana,
                    TIME_FORMAT(h.hora_inicio, '%H:%i') as hora_inicio,
                    TIME_FORMAT(h.hora_fin, '%H:%i') as hora_fin,
                    h.es_rotacion,
                    n.nombre as nucleo
                FROM horarios h
                JOIN grupos g ON h.grupo_id = g.id
                JOIN materias m ON h.materia_id = m.id
                JOIN maestros ma ON h.maestro_id = ma.id
                JOIN salones s ON h.salon_id = s.id
                LEFT JOIN nucleos n ON h.nucleo_id = n.id
                WHERE h.dia_semana = ? AND h.activo = 1
                ORDER BY h.hora_inicio ASC
                LIMIT 20";
        return obtenerRegistros($sql, [$dia_semana]);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtener grupos por semestre
 */
function obtenerGruposPorSemestre() {
    $grupos = [];
    
    for ($i = 1; $i <= 6; $i++) {
        $sql = "SELECT COUNT(*) as total 
                FROM grupos g 
                WHERE g.semestre_id = ? AND g.activo = 1";
        $result = ejecutarConsulta($sql, [$i]);
        $row = $result->fetch_assoc();
        
        $grupos[$i] = [
            'total' => $row['total'] ?? 0
        ];
    }
    
    return $grupos;
}

/**
 * Obtener alertas del sistema
 */
function obtenerAlertasSistema() {
    $alertas = [];
    
    // 1. Verificar conflictos de horario (maestros)
    try {
        $result = ejecutarConsulta("SELECT COUNT(*) as total FROM vista_conflictos_maestros");
        $conflictos_maestros = $result->fetch_assoc()['total'] ?? 0;
        
        if ($conflictos_maestros > 0) {
            $alertas[] = [
                'tipo' => 'danger',
                'icono' => 'exclamation-circle',
                'titulo' => 'Conflictos de Maestros',
                'mensaje' => "Hay $conflictos_maestros conflictos de horario entre maestros. Revisa la asignacion."
            ];
        }
    } catch (Exception $e) {
        // La vista puede no existir aún
    }
    
    // 2. Grupos sin horario asignado
    $result = ejecutarConsulta("
        SELECT COUNT(*) as total 
        FROM grupos g 
        WHERE g.activo = 1 
        AND NOT EXISTS (
            SELECT 1 FROM horarios h 
            WHERE h.grupo_id = g.id AND h.activo = 1
        )
    ");
    $sin_horario = $result->fetch_assoc()['total'] ?? 0;
    
    if ($sin_horario > 0) {
        $alertas[] = [
            'tipo' => 'warning',
            'icono' => 'clock',
            'titulo' => 'Grupos sin Horario',
            'mensaje' => "Hay $sin_horario grupos que no tienen horario asignado."
        ];
    }
    
    // 3. Maestros sin asignación
    $result = ejecutarConsulta("
        SELECT COUNT(*) as total 
        FROM maestros m 
        WHERE m.activo = 1 
        AND NOT EXISTS (
            SELECT 1 FROM horarios h 
            WHERE h.maestro_id = m.id AND h.activo = 1
        )
    ");
    $maestros_sin = $result->fetch_assoc()['total'] ?? 0;
    
    if ($maestros_sin > 0) {
        $alertas[] = [
            'tipo' => 'info',
            'icono' => 'user',
            'titulo' => 'Maestros sin Asignacion',
            'mensaje' => "Hay $maestros_sin maestros que no tienen horas asignadas."
        ];
    }
    
    // 4. Materias sin horario
    $result = ejecutarConsulta("
        SELECT COUNT(*) as total 
        FROM materias m 
        WHERE m.activo = 1 
        AND NOT EXISTS (
            SELECT 1 FROM horarios h 
            WHERE h.materia_id = m.id AND h.activo = 1
        )
    ");
    $materias_sin = $result->fetch_assoc()['total'] ?? 0;
    
    if ($materias_sin > 0) {
        $alertas[] = [
            'tipo' => 'warning',
            'icono' => 'book',
            'titulo' => 'Materias sin Horario',
            'mensaje' => "Hay $materias_sin materias que no tienen horario asignado."
        ];
    }
    
    return $alertas;
}

/**
 * Obtener el nombre del semestre por ID
 */
function obtenerNombreSemestre($id) {
    $semestres = [
        1 => 'Primer Semestre',
        2 => 'Segundo Semestre',
        3 => 'Tercer Semestre',
        4 => 'Cuarto Semestre',
        5 => 'Quinto Semestre',
        6 => 'Sexto Semestre'
    ];
    return $semestres[$id] ?? 'Desconocido';
}

/**
 * Formatear fecha para mostrar
 */
function formatearFecha($fecha, $formato = 'd/m/Y') {
    if (empty($fecha)) return '-';
    return date($formato, strtotime($fecha));
}

/**
 * Obtener el color del semestre para badges
 */
function getColorSemestre($semestre_id) {
    $colores = [
        1 => 'info',
        2 => 'info',
        3 => 'warning',
        4 => 'warning',
        5 => 'purple',
        6 => 'purple'
    ];
    return $colores[$semestre_id] ?? 'secondary';
}

/**
 * Obtener el color del tipo de materia
 */
function getColorTipoMateria($tipo) {
    $colores = [
        'tronco' => 'success',
        'especialidad' => 'primary',
        'nucleo' => 'purple'
    ];
    return $colores[$tipo] ?? 'secondary';
}

/**
 * Verificar si una materia tiene horarios asignados
 */
function materiaTieneHorarios($materia_id) {
    $sql = "SELECT COUNT(*) as total FROM horarios WHERE materia_id = ? AND activo = 1";
    $result = ejecutarConsulta($sql, [$materia_id]);
    $row = $result->fetch_assoc();
    return ($row['total'] ?? 0) > 0;
}

/**
 * Obtener total de horas de un maestro
 */
function getTotalHorasMaestro($maestro_id) {
    $sql = "SELECT COUNT(*) as total FROM horarios WHERE maestro_id = ? AND activo = 1";
    $result = ejecutarConsulta($sql, [$maestro_id]);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

/**
 * Obtener total de horas de un salón
 */
function getTotalHorasSalon($salon_id) {
    $sql = "SELECT COUNT(*) as total FROM horarios WHERE salon_id = ? AND activo = 1";
    $result = ejecutarConsulta($sql, [$salon_id]);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}
?>