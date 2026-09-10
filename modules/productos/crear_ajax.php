<?php
// modules/productos/crear_ajax.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

$nombre = trim($_POST['nombre'] ?? '');
$categoria_id = $_POST['categoria_id'] ?? null;
$unidad_medida = $_POST['unidad_medida'] ?? '';
$codigo = $_POST['codigo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

if (empty($nombre)) {
    echo json_encode(['success' => false, 'error' => 'El nombre es obligatorio']);
    exit();
}

try {
    $stmt = $db->prepare("INSERT INTO productos (nombre, descripcion, categoria_id, unidad_medida, codigo, usuario_creacion)
                         VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $nombre, 
        $descripcion, 
        $categoria_id ?: null, 
        $unidad_medida, 
        $codigo, 
        $_SESSION['usuario_id']
    ]);
    
    $id = $db->lastInsertId();
    
    // Obtener el producto recién creado con su categoría
    $stmt = $db->prepare("SELECT p.*, c.nombre as categoria_nombre 
                          FROM productos p 
                          LEFT JOIN categorias c ON p.categoria_id = c.id 
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();
    
    echo json_encode(['success' => true, 'producto' => $producto]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>