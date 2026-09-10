<?php
// modules/items/eliminar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador', 'compras'])) {
    header('Location: ../proyectos/index.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;
$proyecto_id = $_GET['proyecto'] ?? 0;

if ($id) {
    $stmt = $db->prepare("DELETE FROM items_proyecto WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: ../proyectos/ver.php?id=' . $proyecto_id);
exit();
?>