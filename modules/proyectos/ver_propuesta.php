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

// Obtener proyecto completo (necesitamos los campos para validar permisos)
$stmt = $db->prepare("SELECT p.* FROM proyectos p WHERE p.id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    redirigir('modules/proyectos/index.php?error=sin_acceso');
}

// ✅ VALIDAR PERMISOS DE ACCESO AL PROYECTO
if (!puedeVerProyecto($proyecto)) {
    redirigir('modules/proyectos/index.php?error=sin_acceso');
}

// Verificar que tenga propuesta técnica
if (empty($proyecto['propuesta_tecnica'])) {
    redirigir('modules/proyectos/ver.php?id=' . $proyecto_id . '&error=sin_propuesta');
}

// Ruta física del archivo
$ruta_fisica = getUploadsDir() . '/' . ltrim($proyecto['propuesta_tecnica'], '/');

if (!file_exists($ruta_fisica)) {
    redirigir('modules/proyectos/ver.php?id=' . $proyecto_id . '&error=archivo_no_encontrado');
}

// ============================================
// SERVIR EL PDF INLINE
// ============================================
// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_end_clean();
}

$nombre_original = $proyecto['propuesta_nombre_original'] ?? 'propuesta.pdf';

// Headers para visualizar el PDF en el navegador
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($nombre_original) . '"');
header('Content-Length: ' . filesize($ruta_fisica));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Leer y enviar el archivo
readfile($ruta_fisica);
exit();