<?php
// modules/proyectos/ver_propuesta.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$proyecto_id = $_GET['id'] ?? 0;

if (!$proyecto_id) {
    redirigir('modules/proyectos/index.php');
}

$stmt = $db->prepare("SELECT id, propuesta_tecnica, propuesta_nombre_original 
                      FROM proyectos 
                      WHERE id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();

if (!$proyecto || empty($proyecto['propuesta_tecnica'])) {
    redirigir('modules/proyectos/ver.php?id=' . $proyecto_id . '&error=sin_propuesta');
}

$ruta_fisica = getUploadsDir() . '/' . ltrim($proyecto['propuesta_tecnica'], '/');

if (!file_exists($ruta_fisica)) {
    redirigir('modules/proyectos/ver.php?id=' . $proyecto_id . '&error=archivo_no_encontrado');
}

// Servir el archivo inline (para ver en el navegador)
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($proyecto['propuesta_nombre_original'] ?? 'propuesta.pdf') . '"');
header('Content-Length: ' . filesize($ruta_fisica));
header('Cache-Control: private, max-age=0, must-revalidate');

if (ob_get_level()) {
    ob_end_clean();
}

readfile($ruta_fisica);
exit();