<?php
// modules/productos/buscar_ajax.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

$busqueda = $_GET['q'] ?? '';
$categoria_id = $_GET['categoria'] ?? '';

$productos = buscarProductos($db, $busqueda, $categoria_id, 100);

echo json_encode(['success' => true, 'productos' => $productos]);
?>