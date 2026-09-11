<?php
// modules/proyectos/agregar_item.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

header('Content-Type: application/json');

if (!tienePermiso(['compras', 'directivo', 'gerenciador', 'supervisor', 'proyectista']) && !esMaster()) {
    echo json_encode(['success' => false, 'error' => 'Sin permisos']);
    exit();
}

$db = Database::getInstance()->getConnection();

$proyecto_id = $_POST['proyecto_id'] ?? 0;
$producto_id = $_POST['producto_id'] ?? null;
$nombre_item = trim($_POST['nombre_item'] ?? '');
$cantidad = intval($_POST['cantidad'] ?? 1);
$unidad_medida = $_POST['unidad_medida'] ?? '';
$fecha_requerida = $_POST['fecha_requerida'] ?? '';
$especificaciones = $_POST['especificaciones'] ?? '';

// Validaciones
if (!$proyecto_id || empty($nombre_item) || !$fecha_requerida || $cantidad < 1) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

// Validar unidad de medida
if (!empty($unidad_medida) && !esUnidadValida($unidad_medida)) {
    echo json_encode(['success' => false, 'error' => 'Unidad de medida no válida']);
    exit();
}

try {
    $stmt = $db->prepare("SELECT estado FROM proyectos WHERE id = ?");
    $stmt->execute([$proyecto_id]);
    $proyecto = $stmt->fetch();

    if (!$proyecto) {
        echo json_encode(['success' => false, 'error' => 'Proyecto no encontrado']);
        exit();
    }

    $estado_inicial = 'solicitado';
    if (in_array($proyecto['estado'], ['aprovado_cliente', 'espera_orden_compra', 'comprando_materiales',
                                       'elaboracion', 'terminado', 'pendiente_cobro_cliente', 'finalizado'])) {
        $estado_inicial = 'pendiente';
    }

    $stmt = $db->prepare("INSERT INTO items_proyecto 
                          (proyecto_id, producto_id, nombre_item, cantidad, unidad_medida, 
                           especificaciones, fecha_requerida, estado)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $proyecto_id,
        $producto_id ?: null,
        $nombre_item,
        $cantidad,
        $unidad_medida,
        $especificaciones,
        $fecha_requerida,
        $estado_inicial
    ]);

    $item_id = $db->lastInsertId();

    // Historial
    $stmt = $db->prepare("INSERT INTO historial_items 
                          (item_id, estado_anterior, estado_nuevo, fecha_anterior, fecha_nueva, 
                           cantidad_anterior, cantidad_nueva, usuario_id, comentario)
                          VALUES (?, NULL, ?, NULL, ?, NULL, ?, ?, ?)");
    $stmt->execute([
        $item_id,
        $estado_inicial,
        $fecha_requerida,
        $cantidad,
        $_SESSION['usuario_id'],
        'Item creado'
    ]);
    
    // Notificación por email
    try {
        notificarItemsAgregados($db, $proyecto_id, [[
            'nombre_item'      => $nombre_item,
            'cantidad'         => $cantidad,
            'unidad_medida'    => $unidad_medida,
            'fecha_requerida'  => $fecha_requerida,
            'especificaciones' => $especificaciones
        ]]);
    } catch (Exception $e) {
        error_log("Error al enviar email de items agregados: " . $e->getMessage());
    }
    
    echo json_encode(['success' => true, 'item_id' => $item_id]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}