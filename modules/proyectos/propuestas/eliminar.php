<?php
// modules/proyectos/propuestas/eliminar.php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador']) && !esMaster()) {
    redirigir('modules/proyectos/index.php');
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

if ($id) {
    // Obtener el proyecto antes de borrar
    $stmt = $db->prepare("SELECT proyecto_id FROM propuestas_economicas WHERE id = ?");
    $stmt->execute([$id]);
    $prop = $stmt->fetch();
    
    if ($prop) {
        // Borrar propuesta (los items se borran en cascada)
        $stmt = $db->prepare("DELETE FROM propuestas_economicas WHERE id = ?");
        $stmt->execute([$id]);
        
        redirigir('modules/proyectos/propuestas/index.php?proyecto=' . $prop['proyecto_id'] . '&mensaje=eliminado');
    }
}

redirigir('modules/proyectos/index.php');