<?php
// modules/grupos/eliminar.php - Eliminar grupo (desactivar)
session_start();

require_once '../../config/database.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Verificar si el grupo tiene horarios asignados
    $sql_verificar = "SELECT COUNT(*) as total FROM horarios WHERE grupo_id = ? AND activo = 1";
    $result = ejecutarConsulta($sql_verificar, [$id]);
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        // Si tiene horarios, mostrar mensaje de error
        $_SESSION['mensaje'] = 'No se puede eliminar el grupo porque tiene horarios asignados.';
        $_SESSION['tipo_mensaje'] = 'danger';
    } else {
        // Desactivar el grupo (eliminación lógica)
        $sql = "UPDATE grupos SET activo = 0 WHERE id = ?";
        $resultado = ejecutarUpdate($sql, [$id]);
        
        if ($resultado) {
            $_SESSION['mensaje'] = 'Grupo eliminado exitosamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al eliminar el grupo';
            $_SESSION['tipo_mensaje'] = 'danger';
        }
    }
}

header('Location: index.php');
exit;