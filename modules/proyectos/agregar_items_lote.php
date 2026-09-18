<?php
// modules/proyectos/agregar_items_lote.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

header('Content-Type: application/json');

if (!tienePermiso(['compras', 'directivo', 'gerenciador', 'supervisor', 'proyectista', 'almacen']) && !esMaster()) {
    echo json_encode(['success' => false, 'error' => 'Sin permisos']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$proyecto_id = $input['proyecto_id'] ?? 0;
$items = $input['items'] ?? [];

if (!$proyecto_id || empty($items)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

$db = Database::getInstance()->getConnection();

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

$items_guardados = [];
$errores = [];

try {
    $db->beginTransaction();
    
    $stmt_insert = $db->prepare("INSERT INTO items_proyecto 
                                 (proyecto_id, producto_id, nombre_item, cantidad, unidad_medida, 
                                  especificaciones, fecha_requerida, estado,
                                  costo_unitario, moneda)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_historial = $db->prepare("INSERT INTO historial_items 
                                    (item_id, estado_anterior, estado_nuevo, fecha_anterior, fecha_nueva, 
                                     cantidad_anterior, cantidad_nueva, usuario_id, comentario)
                                    VALUES (?, NULL, ?, NULL, ?, NULL, ?, ?, ?)");
    $stmt_producto = $db->prepare("SELECT costo_actual, moneda FROM productos WHERE id = ?");
    
    foreach ($items as $item) {
        $nombre = trim($item['nombre_item'] ?? '');
        $cantidad = intval($item['cantidad'] ?? 1);
        $unidad = $item['unidad_medida'] ?? '';
        $fecha = $item['fecha_requerida'] ?? '';
        $espec = $item['especificaciones'] ?? '';
        $producto_id = $item['producto_id'] ?? null;
        
        if (empty($nombre) || !$fecha || $cantidad < 1) {
            $errores[] = "Item inválido: $nombre";
            continue;
        }
        
        if (!empty($unidad) && !esUnidadValida($unidad)) {
            $errores[] = "Unidad no válida en: $nombre";
            continue;
        }
        
        // Heredar costo del producto
        $costo_unitario = null;
        $moneda = 'USD';
        
        if ($producto_id) {
            $stmt_producto->execute([$producto_id]);
            $prod = $stmt_producto->fetch();
            if ($prod && $prod['costo_actual'] !== null) {
                $costo_unitario = $prod['costo_actual'];
                $moneda = $prod['moneda'] ?? 'USD';
            }
        }
        
        $stmt_insert->execute([
            $proyecto_id, $producto_id ?: null, $nombre, $cantidad,
            $unidad, $espec, $fecha, $estado_inicial,
            $costo_unitario, $moneda
        ]);
        
        $item_id = $db->lastInsertId();
        
        $stmt_historial->execute([
            $item_id, $estado_inicial, $fecha, $cantidad,
            $_SESSION['usuario_id'], 'Item creado (lote)'
        ]);
        
        $items_guardados[] = [
            'id' => $item_id,
            'nombre_item' => $nombre,
            'cantidad' => $cantidad,
            'unidad_medida' => $unidad,
            'fecha_requerida' => $fecha,
            'especificaciones' => $espec,
        ];
    }
    
    $db->commit();
    
    if (!empty($items_guardados)) {
        try {
            notificarItemsAgregados($db, $proyecto_id, $items_guardados);
        } catch (Exception $e) {
            error_log("Error al enviar email: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'guardados' => count($items_guardados),
        'errores' => $errores,
        'items' => $items_guardados
    ]);
    
} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}