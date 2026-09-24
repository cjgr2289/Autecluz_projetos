<?php
// modules/proyectos/descargar_propuesta.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$proyecto_id = $_GET['id'] ?? 0;

if (!$proyecto_id) {
    redirigir('modules/proyectos/index.php');
}

// Obtener proyecto completo
$stmt = $db->prepare("SELECT p.* FROM proyectos p WHERE p.id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    redirigir('modules/proyectos/index.php?error=sin_acceso');
}

// ✅ VALIDAR PERMISOS
if (!puedeVerProyecto($proyecto)) {
    redirigir('modules/proyectos/index.php?error=sin_acceso');
}

// Verificar propuesta técnica
if (empty($proyecto['propuesta_tecnica'])) {
    redirigir('modules/proyectos/ver.php?id=' . $proyecto_id . '&error=sin_propuesta');
}

// Ruta física
$ruta_fisica = getUploadsDir() . '/' . ltrim($proyecto['propuesta_tecnica'], '/');

if (!file_exists($ruta_fisica)) {
    redirigir('modules/proyectos/ver.php?id=' . $proyecto_id . '&error=archivo_no_encontrado');
}

// ============================================
// FORZAR DESCARGA
// ============================================
if (ob_get_level()) {
    ob_end_clean();
}

$nombre_descarga = $proyecto['propuesta_nombre_original'];
if (empty($nombre_descarga)) {
    $nombre_descarga = 'propuesta_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $proyecto['nombre']) . '.pdf';
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($nombre_descarga) . '"');
header('Content-Length: ' . filesize($ruta_fisica));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($ruta_fisica);
exit();