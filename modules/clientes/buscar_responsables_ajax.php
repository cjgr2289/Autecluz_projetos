<?php
// modules/clientes/buscar_responsables_ajax.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$cliente_id = (int)($_GET['cliente'] ?? 0);

if (!$cliente_id) {
    echo json_encode(['success' => false, 'responsables' => []]);
    exit();
}

$responsables = obtenerResponsablesAjax($db, $cliente_id);

echo json_encode([
    'success' => true,
    'responsables' => $responsables
]);