<?php
// modules/productos/categorias_ajax.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
echo json_encode(['success' => true, 'categorias' => $stmt->fetchAll()]);
?>