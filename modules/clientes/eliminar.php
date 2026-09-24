<?php
// modules/clientes/eliminar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador']) && !esMaster()) {
    redirigir('modules/clientes/index.php');
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

if ($id) {
    // Soft delete: marcar cliente como inactivo
    $stmt = $db->prepare("UPDATE clientes SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    // También marcar los responsables como inactivos
    $stmt = $db->prepare("UPDATE clientes_responsables SET activo = 0 WHERE cliente_id = ?");
    $stmt->execute([$id]);
}

redirigir('modules/clientes/index.php?mensaje=eliminado');