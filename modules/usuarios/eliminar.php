<?php
// modules/usuarios/eliminar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();
requiereMaster();  // <-- Solo Master

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

// No permitir eliminar al Master
$stmt = $db->prepare("SELECT username FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if ($user && $user['username'] !== 'Master') {
    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php?mensaje=eliminado');
} else {
    header('Location: index.php?error=no_permitido');
}
exit();
?>