<?php
/**
 * includes/notificaciones_helper.php
 * Funciones para obtener notificaciones del sistema
 */

/**
 * Obtiene todas las notificaciones activas para un usuario.
 * Devuelve un array con las notificaciones agrupadas por tipo,
 * excluyendo las que el usuario ya marcó como leídas.
 */
function obtenerNotificaciones($db, $usuario_id) {
    $notificaciones = [
        'vencidos'      => [],
        'proximos'      => [],
        'modificados'   => [],
        'agregados'     => [],
        'total_no_leidas' => 0,
    ];
    
    $esMaster = esMaster();
    $tipo_usuario = $_SESSION['tipo_usuario'] ?? '';
    $puede_ver_todo = $esMaster || in_array($tipo_usuario, ['directivo', 'gerenciador', 'compras', 'supervisor', 'proyectista']);
    
    // Si no tiene permisos, no devolvemos nada
    if (!$puede_ver_todo) {
        return $notificaciones;
    }
    
    // ============================================
    // 1. Items VENCIDOS (fecha_requerida < hoy, sin llegar)
    // ============================================
    $stmt = $db->prepare("
        SELECT i.id, i.nombre_item, i.fecha_requerida, i.estado,
               p.id as proyecto_id, p.nombre as proyecto_nombre,
               DATEDIFF(CURDATE(), i.fecha_requerida) as dias_atraso,
               (SELECT COUNT(*) FROM notificaciones_leidas nl 
                WHERE nl.usuario_id = ? AND nl.tipo = 'item_vencido' AND nl.referencia_id = i.id) as leida
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        WHERE i.estado NOT IN ('llego', 'stock', 'entregado', 'recibido')
          AND p.estado NOT IN ('finalizado', 'terminado', 'pendiente_cobro_cliente')
          AND i.fecha_requerida < CURDATE()
        ORDER BY i.fecha_requerida ASC
        LIMIT 50
    ");
    $stmt->execute([$usuario_id]);
    foreach ($stmt->fetchAll() as $row) {
        if (!$row['leida']) {
            $notificaciones['vencidos'][] = $row;
        }
    }
    
    // ============================================
    // 2. Items PRÓXIMOS A VENCER (en los próximos 7 días)
    // ============================================
    $stmt = $db->prepare("
        SELECT i.id, i.nombre_item, i.fecha_requerida, i.estado,
               p.id as proyecto_id, p.nombre as proyecto_nombre,
               DATEDIFF(i.fecha_requerida, CURDATE()) as dias_restantes,
               (SELECT COUNT(*) FROM notificaciones_leidas nl 
                WHERE nl.usuario_id = ? AND nl.tipo = 'item_proximo' AND nl.referencia_id = i.id) as leida
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        WHERE i.estado NOT IN ('llego', 'stock', 'entregado', 'recibido')
          AND p.estado NOT IN ('finalizado', 'terminado', 'pendiente_cobro_cliente')
          AND i.fecha_requerida BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY i.fecha_requerida ASC
        LIMIT 50
    ");
    $stmt->execute([$usuario_id]);
    foreach ($stmt->fetchAll() as $row) {
        if (!$row['leida']) {
            $notificaciones['proximos'][] = $row;
        }
    }
    
    // ============================================
    // 3. Items MODIFICADOS recientemente (últimos 7 días)
    //    Solo notifica al creador del proyecto o a compras/directivo
    // ============================================
    $filtro_creador = '';
    $params = [$usuario_id];
    
    if (!$esMaster && !in_array($tipo_usuario, ['directivo', 'gerenciador', 'compras'])) {
        // Proyectista/supervisor solo ve las de sus propios proyectos
        $filtro_creador = " AND p.usuario_creacion = ?";
        $params[] = $usuario_id;
    }
    
    $stmt = $db->prepare("
        SELECT hi.id as historial_id, hi.fecha_cambio, hi.comentario,
               hi.estado_anterior, hi.estado_nuevo, hi.cantidad_anterior, hi.cantidad_nueva,
               i.id as item_id, i.nombre_item, i.estado as item_estado_actual,
               p.id as proyecto_id, p.nombre as proyecto_nombre,
               u.nombre_completo as usuario_cambio,
               (SELECT COUNT(*) FROM notificaciones_leidas nl 
                WHERE nl.usuario_id = ? AND nl.tipo = 'item_modificado' AND nl.referencia_id = hi.id) as leida
        FROM historial_items hi
        JOIN items_proyecto i ON hi.item_id = i.id
        JOIN proyectos p ON i.proyecto_id = p.id
        LEFT JOIN usuarios u ON hi.usuario_id = u.id
        WHERE hi.fecha_cambio >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          AND hi.usuario_id != ?
          $filtro_creador
        ORDER BY hi.fecha_cambio DESC
        LIMIT 30
    ");
    array_unshift($params, $usuario_id); // para el segundo parámetro (usuario_id != ?)
    $params = array_merge([$usuario_id, $usuario_id], $filtro_creador ? [$usuario_id] : []);
    
    // Reejecutamos con los parámetros correctos
    $stmt = $db->prepare("
        SELECT hi.id as historial_id, hi.fecha_cambio, hi.comentario,
               hi.estado_anterior, hi.estado_nuevo, hi.cantidad_anterior, hi.cantidad_nueva,
               i.id as item_id, i.nombre_item, i.estado as item_estado_actual,
               p.id as proyecto_id, p.nombre as proyecto_nombre,
               u.nombre_completo as usuario_cambio,
               (SELECT COUNT(*) FROM notificaciones_leidas nl 
                WHERE nl.usuario_id = ? AND nl.tipo = 'item_modificado' AND nl.referencia_id = hi.id) as leida
        FROM historial_items hi
        JOIN items_proyecto i ON hi.item_id = i.id
        JOIN proyectos p ON i.proyecto_id = p.id
        LEFT JOIN usuarios u ON hi.usuario_id = u.id
        WHERE hi.fecha_cambio >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          AND (hi.usuario_id IS NULL OR hi.usuario_id != ?)
          $filtro_creador
        ORDER BY hi.fecha_cambio DESC
        LIMIT 30
    ");
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $row) {
        if (!$row['leida']) {
            $notificaciones['modificados'][] = $row;
        }
    }
    
    // ============================================
    // 4. Items AGREGADOS recientemente (últimos 7 días)
    // ============================================
    $stmt = $db->prepare("
        SELECT i.id, i.nombre_item, i.cantidad, i.fecha_creacion, i.fecha_requerida,
               p.id as proyecto_id, p.nombre as proyecto_nombre,
               u.nombre_completo as creador,
               (SELECT COUNT(*) FROM notificaciones_leidas nl 
                WHERE nl.usuario_id = ? AND nl.tipo = 'item_agregado' AND nl.referencia_id = i.id) as leida
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        LEFT JOIN usuarios u ON p.usuario_creacion = u.id
        WHERE i.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          $filtro_creador
        ORDER BY i.fecha_creacion DESC
        LIMIT 20
    ");
    $params_add = [$usuario_id];
    if ($filtro_creador) {
        $params_add[] = $usuario_id;
    }
    $stmt->execute($params_add);
    foreach ($stmt->fetchAll() as $row) {
        if (!$row['leida']) {
            $notificaciones['agregados'][] = $row;
        }
    }
    
    // ============================================
    // Total
    // ============================================
    $notificaciones['total_no_leidas'] = 
        count($notificaciones['vencidos']) +
        count($notificaciones['proximos']) +
        count($notificaciones['modificados']) +
        count($notificaciones['agregados']);
    
    return $notificaciones;
}

/**
 * Marca una notificación específica como leída
 */
function marcarNotificacionLeida($db, $usuario_id, $tipo, $referencia_id) {
    try {
        $stmt = $db->prepare("INSERT IGNORE INTO notificaciones_leidas (usuario_id, tipo, referencia_id) 
                              VALUES (?, ?, ?)");
        $stmt->execute([$usuario_id, $tipo, $referencia_id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Marca TODAS las notificaciones de un tipo como leídas
 */
function marcarTodasLeidas($db, $usuario_id, $tipo = null) {
    $notificaciones = obtenerNotificaciones($db, $usuario_id);
    
    $mapeo = [
        'item_vencido'      => 'vencidos',
        'item_proximo'      => 'proximos',
        'item_modificado'   => 'modificados',
        'item_agregado'     => 'agregados',
    ];
    
    $tipos_a_marcar = $tipo ? [$tipo] : array_keys($mapeo);
    
    foreach ($tipos_a_marcar as $t) {
        if (!isset($mapeo[$t])) continue;
        $grupo = $mapeo[$t];
        
        foreach ($notificaciones[$grupo] as $item) {
            $ref_id = 0;
            switch ($t) {
                case 'item_vencido':
                case 'item_proximo':
                case 'item_agregado':
                    $ref_id = $item['id'];
                    break;
                case 'item_modificado':
                    $ref_id = $item['historial_id'];
                    break;
            }
            if ($ref_id) {
                marcarNotificacionLeida($db, $usuario_id, $t, $ref_id);
            }
        }
    }
    
    return true;
}