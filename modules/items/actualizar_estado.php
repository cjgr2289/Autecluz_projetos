<?php
// modules/items/actualizar_estado.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['compras', 'directivo', 'gerenciador', 'almacen']) && !esMaster()) {
    redirigir('modules/proyectos/index.php');
}

$db = Database::getInstance()->getConnection();
$item_id = $_GET['id'] ?? 0;
$proyecto_id = $_GET['proyecto'] ?? 0;

if (!$item_id || !$proyecto_id) {
    redirigir('modules/proyectos/index.php');
}

$stmt = $db->prepare("SELECT i.*, p.estado as proyecto_estado, p.nombre as proyecto_nombre
                      FROM items_proyecto i 
                      JOIN proyectos p ON i.proyecto_id = p.id 
                      WHERE i.id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    redirigir('modules/proyectos/ver.php?id=' . $proyecto_id);
}

$estados_permitidos = getEstadosItemByProyectoEstado($item['proyecto_estado']);
$transiciones_validas = getTransicionesValidasItem();
$siguiente_estado = getSiguienteEstadoSugerido($item['estado']);

/**
 * Valida si una transición es permitida
 */
function esTransicionValida($estado_actual, $estado_nuevo, $transiciones) {
    if ($estado_actual === $estado_nuevo) return true;
    if (!isset($transiciones[$estado_actual])) return true;
    return in_array($estado_nuevo, $transiciones[$estado_actual]);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuevo_estado = $_POST['estado'] ?? '';
    $comentario = trim($_POST['comentario'] ?? '');
    $nueva_fecha = $_POST['fecha_requerida'] ?? $item['fecha_requerida'];
    $forzar_transicion = !empty($_POST['forzar_transicion']);
    $cantidad_stock_nueva = isset($_POST['cantidad_stock']) ? (int)$_POST['cantidad_stock'] : (int)$item['cantidad_stock'];
    $cantidad_entregada_nueva = isset($_POST['cantidad_entregada']) ? (int)$_POST['cantidad_entregada'] : (int)$item['cantidad_entregada'];
    
    $errores = [];
    
    if (!isset($estados_permitidos[$nuevo_estado])) {
        $errores[] = 'Estado no permitido para este proyecto';
    }
    
    if (!esTransicionValida($item['estado'], $nuevo_estado, $transiciones_validas) && !$forzar_transicion) {
        $errores[] = 'La transición no es válida. Marca "Forzar transición" si estás seguro.';
    }
    
    // Validar cantidad_stock si el estado es "stock"
    if ($nuevo_estado === 'stock') {
        if ($cantidad_stock_nueva < 0 || $cantidad_stock_nueva > $item['cantidad']) {
            $errores[] = 'La cantidad en stock debe estar entre 0 y ' . $item['cantidad'];
        }
    }
    
    // Validar cantidad_entregada si el estado es "entregado"
    if ($nuevo_estado === 'entregado') {
        if ($cantidad_entregada_nueva < 1 || $cantidad_entregada_nueva > $item['cantidad_stock']) {
            $errores[] = 'La cantidad a entregar debe estar entre 1 y ' . $item['cantidad_stock'];
        }
    }
    
    if (empty($errores)) {
        try {
            $db->beginTransaction();
            
            $estado_anterior = $item['estado'];
            
            // Guardar en historial
            $stmt = $db->prepare("
                INSERT INTO historial_items 
                    (item_id, estado_anterior, estado_nuevo, 
                     fecha_anterior, fecha_nueva, 
                     cantidad_anterior, cantidad_nueva,
                     cantidad_stock_anterior, cantidad_stock_nueva,
                     cantidad_entregada_anterior, cantidad_entregada_nueva,
                     usuario_id, comentario)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $comentario_final = $comentario;
            if (!esTransicionValida($estado_anterior, $nuevo_estado, $transiciones_validas) && $forzar_transicion) {
                $comentario_final = '[TRANSICIÓN FORZADA] ' . $comentario;
            }
            
            $stmt->execute([
                $item_id,
                $estado_anterior,
                $nuevo_estado,
                $item['fecha_requerida'],
                $nueva_fecha,
                $item['cantidad'],
                $item['cantidad'],
                $item['cantidad_stock'],
                $cantidad_stock_nueva,
                $item['cantidad_entregada'],
                $cantidad_entregada_nueva,
                $_SESSION['usuario_id'],
                $comentario_final
            ]);
            
            // Actualizar item
            $stmt = $db->prepare("
                UPDATE items_proyecto 
                SET estado = ?, 
                    fecha_requerida = ?,
                    cantidad_stock = ?,
                    cantidad_entregada = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $nuevo_estado, 
                $nueva_fecha, 
                $cantidad_stock_nueva,
                $cantidad_entregada_nueva,
                $item_id
            ]);
            
            $db->commit();
            
            // Notificar a los destinatarios
            if ($estado_anterior !== $nuevo_estado) {
                try {
                    notificarCambioEstadoItem(
                        $db, 
                        $item_id, 
                        $estado_anterior, 
                        $nuevo_estado, 
                        $_SESSION['usuario_id'], 
                        $comentario
                    );
                } catch (Exception $e) {
                    error_log("Error al enviar email de cambio de estado: " . $e->getMessage());
                }
            }
            
            redirigir('modules/proyectos/ver.php?id=' . $proyecto_id . '&mensaje=actualizado');
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errores[] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Actualizar Estado'); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Actualizar Estado'); ?></h1>
            <a href="<?php echo url('modules/proyectos/ver.php?id=' . $proyecto_id); ?>" class="btn-secondary">
                ← <?php echo traducir('Volver'); ?>
            </a>
        </div>
        
        <div class="form-container">
            <div class="info-message" style="margin-bottom:1.5rem;">
                <h3 style="margin:0 0 0.5rem 0;"><?php echo htmlspecialchars($item['nombre_item']); ?></h3>
                <p style="margin:0; font-size:0.9rem;">
                    <strong><?php echo traducir('Proyecto'); ?>:</strong>
                    <?php echo htmlspecialchars($item['proyecto_nombre']); ?>
                </p>
                <p style="margin:0.5rem 0 0 0; font-size:0.9rem;">
                    <strong><?php echo traducir('Estado actual'); ?>:</strong> 
                    <span class="estado-badge estado-<?php echo $item['estado']; ?>">
                        <?php echo getEstadosItem()[$item['estado']] ?? $item['estado']; ?>
                    </span>
                    <?php if ($siguiente_estado): ?>
                        <span style="margin-left:1rem; color:#7f8c8d; font-size:0.85rem;">
                            → <?php echo traducir('Sugerido'); ?>:
                            <strong><?php echo getEstadosItem()[$siguiente_estado] ?? $siguiente_estado; ?></strong>
                        </span>
                    <?php endif; ?>
                </p>
                <p style="margin:0.5rem 0 0 0; font-size:0.9rem;">
                    <strong><?php echo traducir('Cantidad solicitada'); ?>:</strong>
                    <?php echo $item['cantidad']; ?>
                    <?php if ($item['cantidad_stock'] > 0): ?>
                        &nbsp;|&nbsp;
                        <strong><?php echo traducir('En Stock'); ?>:</strong>
                        <span style="color:#27ae60; font-weight:bold;"><?php echo $item['cantidad_stock']; ?></span>
                        <?php if ($item['cantidad_stock'] < $item['cantidad']): ?>
                            &nbsp;|&nbsp;
                            <strong><?php echo traducir('Falta'); ?>:</strong>
                            <span style="color:#e74c3c; font-weight:bold;"><?php echo $item['cantidad'] - $item['cantidad_stock']; ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($item['cantidad_entregada'] > 0): ?>
                        &nbsp;|&nbsp;
                        <strong><?php echo traducir('Entregado'); ?>:</strong>
                        <span style="color:#3498db; font-weight:bold;"><?php echo $item['cantidad_entregada']; ?></span>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if (!empty($errores)): ?>
                <div class="error-message">
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <?php foreach ($errores as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="form-estado">
                <div class="form-group">
                    <label for="estado"><?php echo traducir('Nuevo Estado'); ?> *</label>
                    <select id="estado" name="estado" required>
                        <option value="">-- <?php echo traducir('Seleccionar'); ?> --</option>
                        <?php foreach ($estados_permitidos as $key => $value): 
                            $es_sugerido = ($key === $siguiente_estado);
                            $es_actual = ($key === $item['estado']);
                        ?>
                            <option value="<?php echo $key; ?>" 
                                    data-es-sugerido="<?php echo $es_sugerido ? '1' : '0'; ?>"
                                    <?php echo $es_sugerido ? 'style="font-weight:bold;"' : ''; ?>>
                                <?php echo $value; ?>
                                <?php if ($es_sugerido): ?> ⭐<?php endif; ?>
                                <?php if ($es_actual): ?> (<?php echo traducir('actual'); ?>)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Cantidad en Stock (solo si cambia a "stock") -->
                <div class="form-group" id="grupo-stock" style="display:none;">
                    <label for="cantidad_stock">
                        <?php echo traducir('Cantidad en Stock'); ?>
                    </label>
                    <input type="number" 
                           id="cantidad_stock" 
                           name="cantidad_stock" 
                           value="<?php echo $item['cantidad_stock'] > 0 ? $item['cantidad_stock'] : $item['cantidad']; ?>" 
                           min="0" 
                           max="<?php echo $item['cantidad']; ?>"
                           step="1">
                    <small style="color:#7f8c8d; display:block; margin-top:0.25rem;">
                        <?php echo traducir('Cantidad disponible'); ?>: 
                        máximo <?php echo $item['cantidad']; ?>.
                        <?php echo traducir('Si es menor, el resto quedará como'); ?> 
                        <em><?php echo traducir('Falta por comprar'); ?></em>.
                    </small>
                </div>
                
                <!-- Cantidad a Entregar (solo si cambia a "entregado") -->
                <div class="form-group" id="grupo-entrega" style="display:none;">
                    <label for="cantidad_entregada">
                        <?php echo traducir('Cantidad a entregar'); ?>
                    </label>
                    <input type="number" 
                           id="cantidad_entregada" 
                           name="cantidad_entregada" 
                           value="<?php echo $item['cantidad_stock'] > 0 ? $item['cantidad_stock'] : $item['cantidad']; ?>" 
                           min="1" 
                           max="<?php echo $item['cantidad_stock'] > 0 ? $item['cantidad_stock'] : $item['cantidad']; ?>"
                           step="1">
                    <small style="color:#7f8c8d; display:block; margin-top:0.25rem;">
                        <?php echo traducir('Entrega parcial'); ?>: 
                        puedes entregar menos de lo disponible.
                    </small>
                </div>
                
                <div id="aviso-transicion" style="display:none; margin-bottom:1rem; padding:0.85rem 1rem; background:#fff3cd; border-left:4px solid #f39c12; border-radius:4px; font-size:0.88rem;">
                    <strong>⚠ <?php echo traducir('Atención'); ?>:</strong>
                    <span id="aviso-transicion-texto"></span>
                    <div style="margin-top:0.5rem;">
                        <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                            <input type="checkbox" name="forzar_transicion" id="forzar_transicion" value="1">
                            <?php echo traducir('Forzar transición'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fecha_requerida"><?php echo traducir('Fecha Requerida'); ?></label>
                    <input type="date" id="fecha_requerida" name="fecha_requerida" 
                           value="<?php echo $item['fecha_requerida']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="comentario"><?php echo traducir('Comentario'); ?></label>
                    <textarea id="comentario" name="comentario" rows="3"></textarea>
                </div>
                
                <div style="display:flex; gap:0.75rem;">
                    <button type="submit" class="btn-primary">
                        💾 <?php echo traducir('Actualizar'); ?>
                    </button>
                    <a href="<?php echo url('modules/proyectos/ver.php?id=' . $proyecto_id); ?>" class="btn-secondary">
                        <?php echo traducir('Cancelar'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    const TRANSICIONES_VALIDAS = <?php echo json_encode($transiciones_validas); ?>;
    const ESTADO_ACTUAL = '<?php echo $item['estado']; ?>';
    const IDIOMA = '<?php echo $_SESSION['idioma']; ?>';
    
    const selectEstado = document.getElementById('estado');
    const aviso = document.getElementById('aviso-transicion');
    const avisoTexto = document.getElementById('aviso-transicion-texto');
    const grupoStock = document.getElementById('grupo-stock');
    const grupoEntrega = document.getElementById('grupo-entrega');
    
    function validarEstado() {
        const nuevoEstado = selectEstado.value;
        
        // Mostrar/ocultar campos según el estado
        grupoStock.style.display = (nuevoEstado === 'stock') ? 'block' : 'none';
        grupoEntrega.style.display = (nuevoEstado === 'entregado') ? 'block' : 'none';
        
        // Validar transición
        if (!nuevoEstado || nuevoEstado === ESTADO_ACTUAL) {
            aviso.style.display = 'none';
            return;
        }
        
        const validas = TRANSICIONES_VALIDAS[ESTADO_ACTUAL] || [];
        const esValida = validas.includes(nuevoEstado);
        
        aviso.style.display = esValida ? 'none' : 'block';
        
        if (!esValida) {
            avisoTexto.textContent = IDIOMA === 'pt'
                ? 'Esta transição não segue o fluxo normal.'
                : 'Esta transición no sigue el flujo normal.';
        }
    }
    
    if (selectEstado) {
        selectEstado.addEventListener('change', validarEstado);
        validarEstado();
    }
    </script>
    

    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>