<?php
// modules/notificaciones/obtener.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance()->getConnection();
$usuario_id = $_SESSION['usuario_id'];

$notificaciones = obtenerNotificaciones($db, $usuario_id);

echo json_encode([
    'success' => true,
    'total' => $notificaciones['total_no_leidas'],
    'notificaciones' => $notificaciones,
]);