<?php
// modules/productos/eliminar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador'])) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

// Soft delete
$stmt = $db->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php?mensaje=eliminado');
exit();
?>