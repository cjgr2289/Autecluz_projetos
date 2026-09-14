<?php
// modules/notificaciones/marcar_leida.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$db = Database::getInstance()->getConnection();
$usuario_id = $_SESSION['usuario_id'];

$tipo = $_POST['tipo'] ?? '';
$referencia_id = intval($_POST['referencia_id'] ?? 0);
$todas = $_POST['todas'] ?? false;

$tipos_validos = ['item_vencido', 'item_proximo', 'item_modificado', 'item_agregado'];

if ($todas) {
    marcarTodasLeidas($db, $usuario_id);
    echo json_encode(['success' => true, 'mensaje' => 'Todas marcadas como leídas']);
    exit();
}

if (!in_array($tipo, $tipos_validos) || !$referencia_id) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

$ok = marcarNotificacionLeida($db, $usuario_id, $tipo, $referencia_id);

echo json_encode(['success' => $ok]);