<?php
// modules/proyectos/eliminar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador']) && !esMaster()) {
    redirigir('modules/proyectos/index.php');
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

if ($id) {
    // Obtener la propuesta técnica antes de borrar
    $stmt = $db->prepare("SELECT propuesta_tecnica FROM proyectos WHERE id = ?");
    $stmt->execute([$id]);
    $proyecto = $stmt->fetch();
    
    // Eliminar archivo físico
    if ($proyecto && !empty($proyecto['propuesta_tecnica'])) {
        eliminarPdfPropuesta($proyecto['propuesta_tecnica']);
    }
    
    // Eliminar registro
    $stmt = $db->prepare("DELETE FROM proyectos WHERE id = ?");
    $stmt->execute([$id]);
}

redirigir('modules/proyectos/index.php?mensaje=eliminado');