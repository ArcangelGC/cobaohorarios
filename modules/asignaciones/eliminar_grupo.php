<?php
// modules/asignaciones/eliminar_grupo.php - Eliminar todas las asignaciones de una materia en un grupo
session_start();

require_once '../../config/database.php';

$materia_id = intval($_GET['materia_id'] ?? 0);
$grupo_id = intval($_GET['grupo_id'] ?? 0);

if ($materia_id > 0 && $grupo_id > 0) {
    $sql = "DELETE FROM materias_grupos WHERE materia_id = ? AND grupo_id = ?";
    $resultado = ejecutarUpdate($sql, [$materia_id, $grupo_id]);
    
    if ($resultado) {
        $_SESSION['mensaje'] = 'Asignaciones eliminadas correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar las asignaciones';
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

header('Location: index.php');
exit;