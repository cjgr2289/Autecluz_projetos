<?php
/**
 * includes/mailer/funciones_mail.php
 * Funciones de alto nivel para enviar correos según eventos del sistema
 */

require_once __DIR__ . '/Mailer.php';

/**
 * Traduce un texto a un idioma específico (no al de la sesión actual).
 * Se usa para enviar correos en el idioma del RECEPTOR.
 */
function traducirParaIdioma($texto, $idioma) {
    // Guardar idioma actual para restaurar después
    $idioma_anterior = $_SESSION['idioma'] ?? 'es';
    $_SESSION['idioma'] = $idioma;
    
    $traduccion = traducir($texto);
    
    // Restaurar
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
// NOTIFICACIÓN 1: Nuevos items en un proyecto
// ============================================

/**
 * Envía un correo a todos los usuarios de tipo "compras" cuando se agregan
 * uno o más items a un proyecto.
 * 
 * @param PDO $db
 * @param int $proyecto_id
 * @param array $items_agregados  Array de items: [['nombre_item','cantidad','unidad_medida','fecha_requerida','especificaciones'], ...]
 * @return bool
 */
function notificarItemsAgregados($db, $proyecto_id, $items_agregados) {
    if (empty($items_agregados)) return false;
    
    // Obtener proyecto
    $stmt = $db->prepare("SELECT p.*, u.nombre_completo as creador 
                          FROM proyectos p 
                          LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
                          WHERE p.id = ?");
    $stmt->execute([$proyecto_id]);
    $proyecto = $stmt->fetch();
    
    if (!$proyecto) return false;
    
    // Obtener todos los usuarios de compras
    $usuarios_compras = obtenerUsuariosPorTipo($db, 'compras');
    if (empty($usuarios_compras)) return false;
    
    $mailer = new Mailer();
    $exito_total = true;
    
    // Agrupar destinatarios por idioma (para enviar en el idioma correcto a cada uno)
    $por_idioma = [];
    foreach ($usuarios_compras as $u) {
        $idioma = $u['idioma_preferido'] ?? 'es';
        $por_idioma[$idioma][] = $u;
    }
    
    foreach ($por_idioma as $idioma => $usuarios) {
        // Renderizar plantilla en el idioma del grupo
        $html = renderizarPlantilla('item_agregado', [
            'idioma'    => $idioma,
            'proyecto'  => $proyecto,
            'items'     => $items_agregados,
        ]);
        
        $asunto = $idioma === 'pt' 
            ? 'Novos itens adicionados ao projeto: ' . $proyecto['nombre']
            : 'Nuevos items agregados al proyecto: ' . $proyecto['nombre'];
        
        // Enviar a cada usuario individualmente (privacidad) o en un solo envío
        foreach ($usuarios as $u) {
            $ok = $mailer->enviar($u['email'], $asunto, $html);
            if (!$ok) $exito_total = false;
        }
    }
    
    return $exito_total;
}

// ============================================
// NOTIFICACIÓN 2: Cambio de estado de un item
// ============================================

/**
 * Envía un correo al usuario que creó el proyecto (solicitante) cuando un item
 * cambia de estado.
 * 
 * @param PDO $db
 * @param int $item_id
 * @param string $estado_anterior
 * @param string $estado_nuevo
 * @param int $usuario_cambio_id  Usuario que realizó el cambio
 * @param string $comentario
 * @return bool
 */
function notificarCambioEstadoItem($db, $item_id, $estado_anterior, $estado_nuevo, $usuario_cambio_id, $comentario = '') {
    // Obtener item + proyecto + creador del proyecto
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
    
    // Obtener nombre del usuario que hizo el cambio
    $stmt = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_cambio_id]);
    $usuario_cambio = $stmt->fetch();
    
    $idioma = $item['solicitante_idioma'] ?? 'es';
    $estados_item = getEstadosItemParaIdioma($idioma);
    
    // Renderizar plantilla
    $html = renderizarPlantilla('item_estado_cambiado', [
        'idioma'            => $idioma,
        'item'              => $item,
        'estado_anterior'   => $estados_item[$estado_anterior] ?? $estado_anterior,
        'estado_nuevo'      => $estados_item[$estado_nuevo] ?? $estado_nuevo,
        'usuario_cambio'    => $usuario_cambio['nombre_completo'] ?? '',
        'comentario'        => $comentario,
    ]);
    
    $asunto = $idioma === 'pt'
        ? 'Atualização de status do item: ' . $item['nombre_item']
        : 'Actualización de estado del item: ' . $item['nombre_item'];
    
    $mailer = new Mailer();
    return $mailer->enviar($item['solicitante_email'], $asunto, $html);
}

/**
 * Devuelve los estados de item en un idioma específico (sin depender de la sesión)
 */
function getEstadosItemParaIdioma($idioma) {
    $estados = [
        'es' => [
            'solicitado' => 'Solicitado',
            'pendiente' => 'Pendiente',
            'stock' => 'En Stock',
            'cotacion' => 'Cotación',
            'orçado' => 'Orçado',
            'pendiente_pago' => 'Pendiente por Pago',
            'comprado_llegar' => 'Comprado por Llegar',
            'llego' => 'Llegó'
        ],
        'pt' => [
            'solicitado' => 'Solicitado',
            'pendiente' => 'Pendente',
            'stock' => 'Em Estoque',
            'cotacion' => 'Cotação',
            'orçado' => 'Orçado',
            'pendiente_pago' => 'Pendente de Pagamento',
            'comprado_llegar' => 'Comprado a Chegar',
            'llego' => 'Chegou'
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
 * @param PDO $db
 * @param int $usuario_id
 * @param string $password_temporal  Contraseña en texto plano (solo para este envío)
 * @return bool
 */
function notificarUsuarioCreado($db, $usuario_id, $password_temporal) {
    $usuario = obtenerUsuarioPorId($db, $usuario_id);
    if (!$usuario || empty($usuario['email'])) return false;
    
    $idioma = $usuario['idioma_preferido'] ?? 'es';
    
    // Login URL
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $login_url = $protocolo . '://' . $host . '/sistema_proyectos/modules/login/login.php';
    
    // Renderizar plantilla
    $html = renderizarPlantilla('usuario_creado', [
        'idioma'            => $idioma,
        'usuario'           => $usuario,
        'password_temporal' => $password_temporal,
        'login_url'         => $login_url,
    ]);
    
    $asunto = $idioma === 'pt'
        ? 'Bem-vindo ao Sistema de Projetos'
        : 'Bienvenido al Sistema de Proyectos';
    
    $mailer = new Mailer();
    return $mailer->enviar($usuario['email'], $asunto, $html);
}