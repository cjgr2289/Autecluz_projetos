<?php
/**
 * includes/notificaciones_helper.php
 * Notificaciones del sistema con soporte para encargado de proyecto
 */

/**
 * Obtiene destinatarios de un proyecto:
 *   - Encargado del proyecto
 *   - Usuarios de compras
 *   - Usuarios de almacén
 * 
 * @param PDO $db
 * @param int $proyecto_id
 * @return array  Lista de usuarios con email válido
 */
function obtenerDestinatariosProyecto($db, $proyecto_id) {
    // Obtener el encargado del proyecto
    $stmt = $db->prepare("SELECT encargado_id FROM proyectos WHERE id = ?");
    $stmt->execute([$proyecto_id]);
    $proyecto = $stmt->fetch();
    
    $destinatarios = [];
    
    // 1. Encargado del proyecto
    if (!empty($proyecto['encargado_id'])) {
        $stmt = $db->prepare("SELECT id, username, nombre_completo, email, idioma_preferido 
                              FROM usuarios 
                              WHERE id = ? AND activo = 1 AND email IS NOT NULL AND email != ''");
        $stmt->execute([$proyecto['encargado_id']]);
        $encargado = $stmt->fetch();
        if ($encargado) {
            $destinatarios[] = $encargado;
        }
    }
    
    // 2. Usuarios de compras
    $stmt = $db->prepare("SELECT id, username, nombre_completo, email, idioma_preferido 
                          FROM usuarios 
                          WHERE tipo_usuario = 'compras' AND activo = 1 
                          AND email IS NOT NULL AND email != ''");
    $stmt->execute();
    $destinatarios = array_merge($destinatarios, $stmt->fetchAll());
    
    // 3. Usuarios de almacén
    $stmt = $db->prepare("SELECT id, username, nombre_completo, email, idioma_preferido 
                          FROM usuarios 
                          WHERE tipo_usuario = 'almacen' AND activo = 1 
                          AND email IS NOT NULL AND email != ''");
    $stmt->execute();
    $destinatarios = array_merge($destinatarios, $stmt->fetchAll());
    
    // Eliminar duplicados por email
    $por_email = [];
    foreach ($destinatarios as $d) {
        $por_email[$d['email']] = $d;
    }
    
    return array_values($por_email);
}

/**
 * Obtiene todas las notificaciones activas para un usuario.
 * 
 * IMPORTANTE: cada consulta usa su propio array de parámetros
 * ($sql_params_*) para evitar el error "Invalid parameter number".
 */
function obtenerNotificaciones($db, $usuario_id) {
    $notificaciones = [
        'vencidos'        => [],
        'proximos'        => [],
        'modificados'     => [],
        'agregados'       => [],
        'separados'       => [],
        'total_no_leidas' => 0,
    ];
    
    $esMaster = esMaster();
    $tipo_usuario = $_SESSION['tipo_usuario'] ?? '';
    $puede_ver_todo = $esMaster || in_array($tipo_usuario, ['directivo', 'gerenciador', 'compras', 'supervisor', 'proyectista', 'almacen']);
    
    if (!$puede_ver_todo) {
        return $notificaciones;
    }
    
    // ============================================
    // 1. Items VENCIDOS
    // ============================================
    $stmt = $db->prepare("
        SELECT i.id, i.nombre_item, i.fecha_requerida, i.estado,
               p.id as proyecto_id, p.nombre as proyecto_nombre,
               DATEDIFF(CURDATE(), i.fecha_requerida) as dias_atraso,
               (SELECT COUNT(*) FROM notificaciones_leidas nl 
                WHERE nl.usuario_id = ? AND nl.tipo = 'item_vencido' AND nl.referencia_id = i.id) as leida
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        WHERE i.estado NOT IN ('llego', 'stock', 'separado', 'entregado', 'recibido')
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
    // 2. Items PRÓXIMOS A VENCER
    // ============================================
    $stmt = $db->prepare("
        SELECT i.id, i.nombre_item, i.fecha_requerida, i.estado,
               p.id as proyecto_id, p.nombre as proyecto_nombre,
               DATEDIFF(i.fecha_requerida, CURDATE()) as dias_restantes,
               (SELECT COUNT(*) FROM notificaciones_leidas nl 
                WHERE nl.usuario_id = ? AND nl.tipo = 'item_proximo' AND nl.referencia_id = i.id) as leida
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        WHERE i.estado NOT IN ('llego', 'stock', 'separado', 'entregado', 'recibido')
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
    // 3. Items SEPARADOS (listos para entregar)
    // ============================================
    if (in_array($tipo_usuario, ['almacen', 'supervisor', 'proyectista']) || $esMaster) {
        // Parámetros SOLO para esta consulta
        $sql_params_sep = [$usuario_id];  // placeholder del subquery
        $sql_filtro_sep = '';
        
        if (!$esMaster && !in_array($tipo_usuario, ['almacen', 'directivo', 'gerenciador', 'compras'])) {
            $sql_filtro_sep = " AND (p.encargado_id = ? OR p.usuario_creacion = ?)";
            $sql_params_sep[] = $usuario_id;
            $sql_params_sep[] = $usuario_id;
        }
        
        $stmt = $db->prepare("
            SELECT i.id, i.nombre_item, i.cantidad, i.cantidad_stock, i.fecha_requerida,
                   p.id as proyecto_id, p.nombre as proyecto_nombre,
                   (SELECT COUNT(*) FROM notificaciones_leidas nl 
                    WHERE nl.usuario_id = ? AND nl.tipo = 'item_separado' AND nl.referencia_id = i.id) as leida
            FROM items_proyecto i
            JOIN proyectos p ON i.proyecto_id = p.id
            WHERE i.estado = 'separado'
              AND p.estado NOT IN ('finalizado')
              $sql_filtro_sep
            ORDER BY i.fecha_requerida ASC
            LIMIT 30
        ");
        $stmt->execute($sql_params_sep);
        foreach ($stmt->fetchAll() as $row) {
            if (!$row['leida']) {
                $notificaciones['separados'][] = $row;
            }
        }
    }
    
    // ============================================
    // 4. Items MODIFICADOS recientemente
    // ============================================
    // Parámetros SOLO para esta consulta
    $sql_params_mod = [$usuario_id, $usuario_id];  // subquery + usuario_id != ?
    $sql_filtro_mod = '';
    
    if (!$esMaster && !in_array($tipo_usuario, ['directivo', 'gerenciador', 'compras'])) {
        $sql_filtro_mod = " AND (p.usuario_creacion = ? OR p.encargado_id = ?)";
        $sql_params_mod[] = $usuario_id;
        $sql_params_mod[] = $usuario_id;
    }
    
    $stmt = $db->prepare("
        SELECT hi.id as historial_id, hi.fecha_cambio, hi.comentario,
               hi.estado_anterior, hi.estado_nuevo, 
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
          $sql_filtro_mod
        ORDER BY hi.fecha_cambio DESC
        LIMIT 30
    ");
    $stmt->execute($sql_params_mod);
    foreach ($stmt->fetchAll() as $row) {
        if (!$row['leida']) {
            $notificaciones['modificados'][] = $row;
        }
    }
    
    // ============================================
    // 5. Items AGREGADOS recientemente
    // ============================================
    // Parámetros SOLO para esta consulta
    $sql_params_agg = [$usuario_id];  // placeholder del subquery
    $sql_filtro_agg = '';
    
    if (!$esMaster && !in_array($tipo_usuario, ['directivo', 'gerenciador', 'compras'])) {
        $sql_filtro_agg = " AND (p.usuario_creacion = ? OR p.encargado_id = ?)";
        $sql_params_agg[] = $usuario_id;
        $sql_params_agg[] = $usuario_id;
    }
    
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
          $sql_filtro_agg
        ORDER BY i.fecha_creacion DESC
        LIMIT 20
    ");
    $stmt->execute($sql_params_agg);
    foreach ($stmt->fetchAll() as $row) {
        if (!$row['leida']) {
            $notificaciones['agregados'][] = $row;
        }
    }
    
    // Total
    $notificaciones['total_no_leidas'] = 
        count($notificaciones['vencidos']) +
        count($notificaciones['proximos']) +
        count($notificaciones['modificados']) +
        count($notificaciones['agregados']) +
        count($notificaciones['separados']);
    
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
 * Marca todas las notificaciones como leídas
 */
function marcarTodasLeidas($db, $usuario_id, $tipo = null) {
    $notificaciones = obtenerNotificaciones($db, $usuario_id);
    
    $mapeo = [
        'item_vencido'      => 'vencidos',
        'item_proximo'      => 'proximos',
        'item_modificado'   => 'modificados',
        'item_agregado'     => 'agregados',
        'item_separado'     => 'separados',
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
                case 'item_separado':
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