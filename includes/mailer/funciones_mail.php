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
        error_log("[MAILER] Plantilla no encontrada: $path");
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
// NOTIFICACIÓN 1: Items agregados
// ============================================

function notificarItemsAgregados($db, $proyecto_id, $items_agregados) {
    if (empty($items_agregados)) return false;
    
    error_log("[MAILER] notificarItemsAgregados llamado para proyecto $proyecto_id con " . count($items_agregados) . " items");
    
    $stmt = $db->prepare("SELECT p.*, u.nombre_completo as creador 
                          FROM proyectos p 
                          LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
                          WHERE p.id = ?");
    $stmt->execute([$proyecto_id]);
    $proyecto = $stmt->fetch();
    
    if (!$proyecto) {
        error_log("[MAILER] Proyecto $proyecto_id no encontrado");
        return false;
    }
    
    $destinatarios = obtenerDestinatariosProyecto($db, $proyecto_id);
    
    if (empty($destinatarios)) {
        error_log("[MAILER] No hay destinatarios para proyecto $proyecto_id");
        return false;
    }
    
    error_log("[MAILER] Destinatarios encontrados: " . count($destinatarios));
    
    $mailer = new Mailer();
    $exito_total = true;
    
    $por_idioma = [];
    foreach ($destinatarios as $u) {
        $idioma = $u['idioma_preferido'] ?? 'es';
        $por_idioma[$idioma][] = $u;
    }
    
    $url_proyecto = getBaseUrl() . 'modules/proyectos/ver.php?id=' . $proyecto_id;
    
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
 * Envía un correo cuando un item cambia de estado.
 * Destinatarios: encargado del proyecto + compras + almacén
 */
function notificarCambioEstadoItem($db, $item_id, $estado_anterior, $estado_nuevo, $usuario_cambio_id, $comentario = '') {
    error_log("[MAILER] === notificarCambioEstadoItem INICIO ===");
    error_log("[MAILER] Item ID: $item_id, $estado_anterior -> $estado_nuevo");
    
    // Obtener item + proyecto
    $stmt = $db->prepare("
        SELECT i.*, 
               p.id as proyecto_id, p.nombre as proyecto_nombre, 
               p.usuario_creacion as solicitante_id,
               p.encargado_id
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        WHERE i.id = ?
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        error_log("[MAILER] ERROR: Item $item_id no encontrado");
        return false;
    }
    
    error_log("[MAILER] Item encontrado: {$item['nombre_item']} (proyecto: {$item['proyecto_nombre']})");
    
    // Obtener destinatarios
    $destinatarios = obtenerDestinatariosProyecto($db, $item['proyecto_id']);
    
    if (empty($destinatarios)) {
        error_log("[MAILER] ERROR: No hay destinatarios para proyecto {$item['proyecto_id']}");
        return false;
    }
    
    error_log("[MAILER] Destinatarios: " . count($destinatarios) . " usuarios");
    foreach ($destinatarios as $d) {
        error_log("[MAILER]   - {$d['nombre_completo']} <{$d['email']}>");
    }
    
    // Nombre del usuario que cambió
    $stmt = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_cambio_id]);
    $usuario_cambio = $stmt->fetch();
    
    $mailer = new Mailer();
    $exito_total = true;
    
    // Agrupar por idioma
    $por_idioma = [];
    foreach ($destinatarios as $u) {
        $idioma = $u['idioma_preferido'] ?? 'es';
        $por_idioma[$idioma][] = $u;
    }
    
    $url_proyecto = getBaseUrl() . 'modules/proyectos/ver.php?id=' . $item['proyecto_id'];
    
    foreach ($por_idioma as $idioma => $usuarios) {
        error_log("[MAILER] Procesando idioma: $idioma (" . count($usuarios) . " usuarios)");
        
        $estados_item = getEstadosItemParaIdioma($idioma);
        
        $html = renderizarPlantilla('item_estado_cambiado', [
            'idioma'            => $idioma,
            'item'              => $item,
            'estado_anterior'   => $estados_item[$estado_anterior] ?? $estado_anterior,
            'estado_nuevo'      => $estados_item[$estado_nuevo] ?? $estado_nuevo,
            'usuario_cambio'    => $usuario_cambio['nombre_completo'] ?? '',
            'comentario'        => $comentario,
            'url_proyecto'      => $url_proyecto,
        ]);
        
        if (empty($html)) {
            error_log("[MAILER] ERROR: HTML vacío para plantilla item_estado_cambiado");
            $exito_total = false;
            continue;
        }
        
        $asunto = $idioma === 'pt'
            ? 'Atualização de status do item: ' . $item['nombre_item']
            : 'Actualización de estado del item: ' . $item['nombre_item'];
        
        $emails = array_column($usuarios, 'email');
        error_log("[MAILER] Enviando a: " . implode(', ', $emails));
        
        $ok = $mailer->enviar($emails, $asunto, $html);
        
        error_log("[MAILER] Resultado envío ($idioma): " . ($ok ? 'OK' : 'FALLÓ'));
        
        if (!$ok) $exito_total = false;
    }
    
    error_log("[MAILER] === notificarCambioEstadoItem FIN (" . ($exito_total ? 'OK' : 'FALLÓ') . ") ===");
    return $exito_total;
}

/**
 * Estados de item para un idioma específico
 */
function getEstadosItemParaIdioma($idioma) {
    $estados = [
        'es' => [
            'solicitado'       => 'Solicitado',
            'pendiente'        => 'Pendiente',
            'stock'            => 'En Stock',
            'separado'         => 'Separado',
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
            'separado'         => 'Separado',
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

function notificarUsuarioCreado($db, $usuario_id, $password_temporal) {
    $usuario = obtenerUsuarioPorId($db, $usuario_id);
    if (!$usuario || empty($usuario['email'])) return false;
    
    $idioma = $usuario['idioma_preferido'] ?? 'es';
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
               p.encargado_id
        FROM items_proyecto i
        JOIN proyectos p ON i.proyecto_id = p.id
        WHERE i.id = ?
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item) return false;
    
    $destinatarios = obtenerDestinatariosProyecto($db, $item['proyecto_id']);
    if (empty($destinatarios)) return false;
    
    $stmt = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_edito_id]);
    $usuario_edito = $stmt->fetch();
    
    $mailer = new Mailer();
    $exito_total = true;
    
    $por_idioma = [];
    foreach ($destinatarios as $u) {
        $idioma = $u['idioma_preferido'] ?? 'es';
        $por_idioma[$idioma][] = $u;
    }
    
    $url_proyecto = getBaseUrl() . 'modules/proyectos/ver.php?id=' . $item['proyecto_id'];
    
    foreach ($por_idioma as $idioma => $usuarios) {
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
        
        $emails = array_column($usuarios, 'email');
        $ok = $mailer->enviar($emails, $asunto, $html);
        if (!$ok) $exito_total = false;
    }
    
    return $exito_total;
}

// ============================================
// NOTIFICACIÓN 5: Resumen semanal
// ============================================

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
        WHERE i.estado NOT IN ('llego', 'stock', 'separado', 'entregado', 'recibido')
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
        WHERE i.estado NOT IN ('llego', 'separado', 'entregado', 'recibido')
          AND p.estado NOT IN ('finalizado', 'terminado', 'pendiente_cobro_cliente')
          AND i.fecha_requerida BETWEEN ? AND ?
        ORDER BY i.fecha_requerida ASC
        LIMIT 100
    ");
    $stmt->execute([$hoy, $fecha_limite]);
    return $stmt->fetchAll();
}

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
        WHERE i.estado NOT IN ('llego', 'stock', 'separado', 'entregado', 'recibido')
          AND p.estado NOT IN ('finalizado', 'terminado', 'pendiente_cobro_cliente')
          AND i.fecha_requerida < ?
        ORDER BY i.fecha_requerida ASC
        LIMIT 100
    ");
    $stmt->execute([$hoy, $hoy]);
    return $stmt->fetchAll();
}