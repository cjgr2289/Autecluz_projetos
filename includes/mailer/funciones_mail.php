<?php
/**
 * includes/mailer/funciones_mail.php
 * Funciones de alto nivel para enviar correos según eventos del sistema
 */

require_once __DIR__ . '/Mailer.php';

/**
 * Traduce un texto a un idioma específico (no al de la sesión actual).
 */
function traducirParaIdioma($texto, $idioma) {
    $idioma_anterior = $_SESSION['idioma'] ?? 'es';
    $_SESSION['idioma'] = $idioma;
    $traduccion = traducir($texto);
    $_SESSION['idioma'] = $idioma_anterior;
    return $traduccion;
}

/**
 * Carga y renderiza una plantilla de email con las variables dadas.
 */
function renderizarPlantilla($nombre_plantilla, $vars = []) {
    $path = __DIR__ . '/plantillas/' . $nombre_plantilla . '.php';
    if (!file_exists($path)) {
        return '';
    }
    extract($vars);
    ob_start();
    include $path;
    return ob_get_clean();
}

/**
 * Obtiene todos los usuarios de un tipo específico con email válido
 */
function obtenerUsuariosPorTipo($db, $tipo) {
    $stmt = $db->prepare("SELECT id, username, nombre_completo, email, idioma_preferido 
                          FROM usuarios 
                          WHERE tipo_usuario = ? AND activo = 1 AND email IS NOT NULL AND email != ''");
    $stmt->execute([$tipo]);
    return $stmt->fetchAll();
}

/**
 * Obtiene un usuario por ID (con email)
 */
function obtenerUsuarioPorId($db, $id) {
    $stmt = $db->prepare("SELECT id, username, nombre_completo, email, idioma_preferido 
                          FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ============================================
// NOTIFICACIÓN 1: Items agregados (AGRUPADA)
// ============================================

/**
 * Envía UN SOLO correo a todos los usuarios de compras cuando se agregan
 * uno o más items a un proyecto (agrupado por idioma).
 */
function notificarItemsAgregados($db, $proyecto_id, $items_agregados) {
    if (empty($items_agregados)) return false;
    
    $stmt = $db->prepare("SELECT p.*, u.nombre_completo as creador 
                          FROM proyectos p 
                          LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
                          WHERE p.id = ?");
    $stmt->execute([$proyecto_id]);
    $proyecto = $stmt->fetch();
    
    if (!$proyecto) return false;
    
    $usuarios_compras = obtenerUsuariosPorTipo($db, 'compras');
    if (empty($usuarios_compras)) return false;
    
    $mailer = new Mailer();
    $exito_total = true;
    
    // Agrupar por idioma
    $por_idioma = [];
    foreach ($usuarios_compras as $u) {
        $idioma = $u['idioma_preferido'] ?? 'es';
        $por_idioma[$idioma][] = $u;
    }
    
    // URL base para los enlaces dentro del correo
    $base_url = getBaseUrl();
    $url_proyecto = $base_url . 'modules/proyectos/ver.php?id=' . $proyecto_id;
    
    foreach ($por_idioma as $idioma => $usuarios) {
        $html = renderizarPlantilla('item_agregado', [
            'idioma'        => $idioma,
            'proyecto'      => $proyecto,
            'items'         => $items_agregados,
            'url_proyecto'  => $url_proyecto,
        ]);
        
        $cantidad_items = count($items_agregados);
        $asunto = $idioma === 'pt'
            ? "Novos itens adicionados ao projeto: {$proyecto['nombre']} ($cantidad_items)"
            : "Nuevos items agregados al proyecto: {$proyecto['nombre']} ($cantidad_items)";
        
        $emails = array_column($usuarios, 'email');
        $ok = $mailer->enviar($emails, $asunto, $html);
        if (!$ok) $exito_total = false;
    }
    
    return $exito_total;
}

// ============================================
// NOTIFICACIÓN 2: Cambio de estado de un item
// ============================================

/**
 * Envía un correo al usuario que creó el proyecto cuando un item cambia de estado.
 */
function notificarCambioEstadoItem($db, $item_id, $estado_anterior, $estado_nuevo, $usuario_cambio_id, $comentario = '') {
    $stmt = $db->prepare("
        SELECT i.*, 
               p.id as proyecto_id, p.nombre as proyecto_nombre, 
               p.usuario_creacion as solicitante_id,
               u.nombre_completo as solicitante_nombre, 
               u.email as solicitante_email,
               u.idioma_preferido as solicitante_idioma
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        LEFT JOIN usuarios u ON p.usuario_creacion = u.id
        WHERE i.id = ?
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item || empty($item['solicitante_email'])) return false;
    
    $stmt = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_cambio_id]);
    $usuario_cambio = $stmt->fetch();
    
    $idioma = $item['solicitante_idioma'] ?? 'es';
    $estados_item = getEstadosItemParaIdioma($idioma);
    
    // URL del proyecto
    $url_proyecto = getBaseUrl() . 'modules/proyectos/ver.php?id=' . $item['proyecto_id'];
    
    $html = renderizarPlantilla('item_estado_cambiado', [
        'idioma'            => $idioma,
        'item'              => $item,
        'estado_anterior'   => $estados_item[$estado_anterior] ?? $estado_anterior,
        'estado_nuevo'      => $estados_item[$estado_nuevo] ?? $estado_nuevo,
        'usuario_cambio'    => $usuario_cambio['nombre_completo'] ?? '',
        'comentario'        => $comentario,
        'url_proyecto'      => $url_proyecto,
    ]);
    
    $asunto = $idioma === 'pt'
        ? 'Atualização de status do item: ' . $item['nombre_item']
        : 'Actualización de estado del item: ' . $item['nombre_item'];
    
    $mailer = new Mailer();
    return $mailer->enviar($item['solicitante_email'], $asunto, $html);
}

/**
 * Devuelve los estados de item en un idioma específico.
 */
function getEstadosItemParaIdioma($idioma) {
    $estados = [
        'es' => [
            'solicitado'       => 'Solicitado',
            'pendiente'        => 'Pendiente',
            'stock'            => 'En Stock',
            'cotacion'         => 'Cotación',
            'orçado'           => 'Orçado',
            'pendiente_pago'   => 'Pendiente por Pago',
            'comprado_llegar'  => 'Comprado por Llegar',
            'llego'            => 'Llegó',
            'entregado'        => 'Entregado',
            'recibido'         => 'Recibido',
        ],
        'pt' => [
            'solicitado'       => 'Solicitado',
            'pendiente'        => 'Pendente',
            'stock'            => 'Em Estoque',
            'cotacion'         => 'Cotação',
            'orçado'           => 'Orçado',
            'pendiente_pago'   => 'Pendente de Pagamento',
            'comprado_llegar'  => 'Comprado a Chegar',
            'llego'            => 'Chegou',
            'entregado'        => 'Entregue',
            'recibido'         => 'Recebido',
        ]
    ];
    return $estados[$idioma] ?? $estados['es'];
}

// ============================================
// NOTIFICACIÓN 3: Nuevo usuario creado
// ============================================

/**
 * Envía un correo de bienvenida al nuevo usuario con sus credenciales.
 * 
 * ✅ CORREGIDO: usa getBaseUrl() para construir la URL correcta
 *    tanto en localhost como en el hosting.
 */
function notificarUsuarioCreado($db, $usuario_id, $password_temporal) {
    $usuario = obtenerUsuarioPorId($db, $usuario_id);
    if (!$usuario || empty($usuario['email'])) return false;
    
    $idioma = $usuario['idioma_preferido'] ?? 'es';
    
    // ✅ Usar getBaseUrl() que detecta automáticamente el entorno
    // En localhost → https://localhost/sistema_proyectos/
    // En hosting   → https://projetos.autecluz.com/
    $base_url = getBaseUrl();
    $login_url = $base_url . 'modules/login/login.php';
    
    $html = renderizarPlantilla('usuario_creado', [
        'idioma'            => $idioma,
        'usuario'           => $usuario,
        'password_temporal' => $password_temporal,
        'login_url'         => $login_url,
        'base_url'          => $base_url,
    ]);
    
    $asunto = $idioma === 'pt'
        ? 'Bem-vindo ao Sistema de Projetos'
        : 'Bienvenido al Sistema de Proyectos';
    
    $mailer = new Mailer();
    return $mailer->enviar($usuario['email'], $asunto, $html);
}

// ============================================
// NOTIFICACIÓN 4: Item editado
// ============================================

/**
 * Notifica al solicitante del proyecto cuando un item es editado.
 */
function notificarItemEditado($db, $item_id, $datos_anteriores, $datos_nuevos, $usuario_edito_id) {
    $campos_comparables = ['nombre_item', 'cantidad', 'unidad_medida', 'fecha_requerida', 'especificaciones'];
    $cambios = [];
    
    foreach ($campos_comparables as $campo) {
        $ant = $datos_anteriores[$campo] ?? null;
        $nue = $datos_nuevos[$campo] ?? null;
        
        if ($campo === 'cantidad') {
            $ant = (int)$ant;
            $nue = (int)$nue;
        } else {
            $ant = trim((string)$ant);
            $nue = trim((string)$nue);
        }
        
        if ($ant !== $nue) {
            $cambios[$campo] = ['anterior' => $ant, 'nuevo' => $nue];
        }
    }
    
    if (empty($cambios)) return false;
    
    $stmt = $db->prepare("
        SELECT i.*, 
               p.id as proyecto_id, p.nombre as proyecto_nombre, 
               p.usuario_creacion as solicitante_id,
               u.nombre_completo as solicitante_nombre, 
               u.email as solicitante_email,
               u.idioma_preferido as solicitante_idioma
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        LEFT JOIN usuarios u ON p.usuario_creacion = u.id
        WHERE i.id = ?
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item || empty($item['solicitante_email'])) return false;
    
    $stmt = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_edito_id]);
    $usuario_edito = $stmt->fetch();
    
    $idioma = $item['solicitante_idioma'] ?? 'es';
    
    $unidades = [
        'es' => ['unidad' => 'Unidad', 'caja' => 'Caja', 'kg' => 'Kilogramo (kg)', 'm' => 'Metro (m)'],
        'pt' => ['unidad' => 'Unidade', 'caja' => 'Caixa', 'kg' => 'Quilograma (kg)', 'm' => 'Metro (m)'],
    ];
    $unidad_label = function($cod) use ($idioma, $unidades) {
        if (empty($cod)) return '-';
        return $unidades[$idioma][$cod] ?? $cod;
    };
    
    $cambios_formateados = [];
    foreach ($cambios as $campo => $vals) {
        $anterior = $vals['anterior'];
        $nuevo = $vals['nuevo'];
        
        if ($campo === 'fecha_requerida') {
            $anterior = formatearFecha($anterior);
            $nuevo = formatearFecha($nuevo);
        } elseif ($campo === 'unidad_medida') {
            $anterior = $unidad_label($anterior);
            $nuevo = $unidad_label($nuevo);
        }
        
        $cambios_formateados[$campo] = [
            'anterior' => $anterior === '' ? '-' : $anterior,
            'nuevo'    => $nuevo === '' ? '-' : $nuevo,
        ];
    }
    
    $url_proyecto = getBaseUrl() . 'modules/proyectos/ver.php?id=' . $item['proyecto_id'];
    
    $html = renderizarPlantilla('item_editado', [
        'idioma'       => $idioma,
        'item'         => $item,
        'cambios'      => $cambios_formateados,
        'usuario'      => $usuario_edito['nombre_completo'] ?? '',
        'url_proyecto' => $url_proyecto,
    ]);
    
    $asunto = $idioma === 'pt'
        ? 'Item atualizado no projeto: ' . $item['proyecto_nombre']
        : 'Item actualizado en el proyecto: ' . $item['proyecto_nombre'];
    
    $mailer = new Mailer();
    return $mailer->enviar($item['solicitante_email'], $asunto, $html);
}

// ============================================
// NOTIFICACIÓN 5: Resumen semanal
// ============================================

/**
 * Envía un resumen semanal a compras, directivos y gerenciadores.
 */
function enviarResumenSemanal($db) {
    $resultado = ['enviados' => 0, 'errores' => []];
    
    $items_estancados = obtenerItemsEstancados($db, 7);
    $items_proximos = obtenerItemsProximosAVencer($db, 7);
    $items_vencidos = obtenerItemsVencidos($db);
    
    if (empty($items_estancados) && empty($items_proximos) && empty($items_vencidos)) {
        $resultado['errores'][] = 'No hay items para reportar';
        return $resultado;
    }
    
    $destinatarios = [];
    foreach (['compras', 'directivo', 'gerenciador'] as $tipo) {
        $destinatarios = array_merge($destinatarios, obtenerUsuariosPorTipo($db, $tipo));
    }
    
    if (empty($destinatarios)) {
        $resultado['errores'][] = 'No hay destinatarios';
        return $resultado;
    }
    
    $por_idioma = [];
    foreach ($destinatarios as $u) {
        $idioma = $u['idioma_preferido'] ?? 'es';
        $por_idioma[$idioma][] = $u;
    }
    
    $mailer = new Mailer();
    $url_proyectos = getBaseUrl() . 'modules/proyectos/index.php';
    
    foreach ($por_idioma as $idioma => $usuarios) {
        $html = renderizarPlantilla('resumen_semanal', [
            'idioma'           => $idioma,
            'items_estancados' => $items_estancados,
            'items_proximos'   => $items_proximos,
            'items_vencidos'   => $items_vencidos,
            'fecha'            => date('d/m/Y'),
            'url_proyectos'    => $url_proyectos,
        ]);
        
        $asunto = $idioma === 'pt'
            ? 'Resumo semanal de itens pendentes - ' . date('d/m/Y')
            : 'Resumen semanal de items pendientes - ' . date('d/m/Y');
        
        $emails = array_column($usuarios, 'email');
        $ok = $mailer->enviar($emails, $asunto, $html);
        
        if ($ok) {
            $resultado['enviados'] += count($emails);
        } else {
            $resultado['errores'][] = "Error al enviar a idioma $idioma";
        }
    }
    
    return $resultado;
}

/**
 * Items cuyo estado no ha cambiado en los últimos N días.
 */
function obtenerItemsEstancados($db, $dias = 7) {
    $fecha_limite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));
    
    $stmt = $db->prepare("
        SELECT i.*, 
               p.nombre as proyecto_nombre,
               p.orden_compra,
               c.nombre as categoria_nombre,
               (SELECT MAX(fecha_cambio) FROM historial_items hi WHERE hi.item_id = i.id) as ultimo_cambio
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        LEFT JOIN productos prod ON i.producto_id = prod.id
        LEFT JOIN categorias c ON prod.categoria_id = c.id
        WHERE i.estado NOT IN ('llego', 'stock')
          AND p.estado NOT IN ('finalizado', 'terminado', 'pendiente_cobro_cliente')
          AND (
              (SELECT MAX(fecha_cambio) FROM historial_items hi WHERE hi.item_id = i.id) < ?
              OR (SELECT MAX(fecha_cambio) FROM historial_items hi WHERE hi.item_id = i.id) IS NULL
          )
        ORDER BY ultimo_cambio ASC
        LIMIT 100
    ");
    $stmt->execute([$fecha_limite]);
    return $stmt->fetchAll();
}

/**
 * Items cuya fecha_requerida vence en los próximos N días.
 */
function obtenerItemsProximosAVencer($db, $dias = 7) {
    $hoy = date('Y-m-d');
    $fecha_limite = date('Y-m-d', strtotime("+{$dias} days"));
    
    $stmt = $db->prepare("
        SELECT i.*, 
               p.nombre as proyecto_nombre,
               p.orden_compra,
               c.nombre as categoria_nombre
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        LEFT JOIN productos prod ON i.producto_id = prod.id
        LEFT JOIN categorias c ON prod.categoria_id = c.id
        WHERE i.estado NOT IN ('llego')
          AND p.estado NOT IN ('finalizado', 'terminado', 'pendiente_cobro_cliente')
          AND i.fecha_requerida BETWEEN ? AND ?
        ORDER BY i.fecha_requerida ASC
        LIMIT 100
    ");
    $stmt->execute([$hoy, $fecha_limite]);
    return $stmt->fetchAll();
}

/**
 * Items cuya fecha_requerida ya pasó y aún no llegaron.
 */
function obtenerItemsVencidos($db) {
    $hoy = date('Y-m-d');
    
    $stmt = $db->prepare("
        SELECT i.*, 
               p.nombre as proyecto_nombre,
               p.orden_compra,
               c.nombre as categoria_nombre,
               DATEDIFF(?, i.fecha_requerida) as dias_atraso
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        LEFT JOIN productos prod ON i.producto_id = prod.id
        LEFT JOIN categorias c ON prod.categoria_id = c.id
        WHERE i.estado NOT IN ('llego')
          AND p.estado NOT IN ('finalizado', 'terminado', 'pendiente_cobro_cliente')
          AND i.fecha_requerida < ?
        ORDER BY i.fecha_requerida ASC
        LIMIT 100
    ");
    $stmt->execute([$hoy, $hoy]);
    return $stmt->fetchAll();
}