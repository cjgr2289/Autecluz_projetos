<?php
// modules/proyectos/eliminar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador'])) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

if ($id) {
    $stmt = $db->prepare("DELETE FROM proyectos WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: index.php?mensaje=eliminado');
exit();
?>