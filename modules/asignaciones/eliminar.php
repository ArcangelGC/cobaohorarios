<?php
// modules/asignaciones/eliminar.php - Eliminar asignación
session_start();

require_once '../../config/database.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $sql = "DELETE FROM materias_grupos WHERE id = ?";
    $resultado = ejecutarUpdate($sql, [$id]);
    
    if ($resultado) {
        $_SESSION['mensaje'] = 'Asignación eliminada correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar la asignación';
        $_SESSION['tipo_mensaje'] = 'danger';
    }
}

header('Location: index.php');
exit;