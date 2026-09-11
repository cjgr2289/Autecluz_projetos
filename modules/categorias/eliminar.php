<?php
// modules/categorias/eliminar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

// Permisos: solo compras, directivo, gerenciador o Master
if (!tienePermiso(['directivo', 'gerenciador', 'compras']) && !esMaster()) {
    header('Location: index.php?error=no_permitido');
    exit();
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

if (!$id) {
    header('Location: index.php');
    exit();
}

// Verificar que la categoría no esté en uso por productos activos
$stmt = $db->prepare("SELECT COUNT(*) as total FROM productos WHERE categoria_id = ? AND activo = 1");
$stmt->execute([$id]);
$total = $stmt->fetch()['total'];

if ($total > 0) {
    header('Location: index.php?mensaje=en_uso');
    exit();
}

// Soft delete
$stmt = $db->prepare("UPDATE categorias SET activo = 0 WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php?mensaje=eliminado');
exit();
?>