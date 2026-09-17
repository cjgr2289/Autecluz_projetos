<?php
// modules/usuarios/cambiar_idioma.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit();
}

$idioma = $_POST['idioma'] ?? '';

if (!in_array($idioma, ['es', 'pt'])) {
    echo json_encode(['success' => false, 'error' => 'Idioma no válido']);
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE usuarios SET idioma_preferido = ? WHERE id = ?");
    $stmt->execute([$idioma, $_SESSION['usuario_id']]);
    
    $_SESSION['idioma'] = $idioma;
    
    echo json_encode(['success' => true, 'idioma' => $idioma]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>