<?php
/**
 * includes/proyectos_helper.php
 * Funciones para manejar la visibilidad y categorización de proyectos
 */

/**
 * Categorías de proyectos según su estado.
 */
function getCategoriasProyecto() {
    return [
        'preparacion' => [
            'titulo_es' => 'En Preparación',
            'titulo_pt' => 'Em Preparação',
            'icono' => '📝',
            'estados' => ['solicitado', 'orçado', 'pendente_aprovacion_cliente', 'aprovado_cliente', 'espera_orden_compra'],
            'color' => '#f39c12',
        ],
        'activos' => [
            'titulo_es' => 'Activos',
            'titulo_pt' => 'Ativos',
            'icono' => '🚀',
            'estados' => ['comprando_materiales', 'elaboracion'],
            'color' => '#3498db',
        ],
        'culminados' => [
            'titulo_es' => 'Culminados',
            'titulo_pt' => 'Concluídos',
            'icono' => '📦',
            'estados' => ['terminado', 'pendiente_cobro_cliente'],
            'color' => '#9b59b6',
        ],
        'finalizados' => [
            'titulo_es' => 'Finalizados',
            'titulo_pt' => 'Finalizados',
            'icono' => '✅',
            'estados' => ['finalizado'],
            'color' => '#27ae60',
        ],
        'rechazados' => [
            'titulo_es' => 'Rechazados',
            'titulo_pt' => 'Rejeitados',
            'icono' => '❌',
            'estados' => ['rechazado'],
            'color' => '#e74c3c',
        ],
    ];
}

/**
 * Devuelve la categoría (key) a la que pertenece un estado.
 */
function getCategoriaDeEstado($estado) {
    foreach (getCategoriasProyecto() as $key => $cat) {
        if (in_array($estado, $cat['estados'])) {
            return $key;
        }
    }
    return 'preparacion'; // default
}

/**
 * Determina si el usuario actual puede ver un proyecto.
 * 
 * @param array $proyecto  Datos del proyecto (debe incluir usuario_creacion, encargado_id, estado)
 * @return bool
 */
function puedeVerProyecto($proyecto) {
    if (!isset($_SESSION['usuario_id'])) return false;
    
    $usuario_id = (int)$_SESSION['usuario_id'];
    $tipo_usuario = $_SESSION['tipo_usuario'] ?? '';
    $es_master = esMaster();
    
    // Master siempre ve todo
    if ($es_master) return true;
    
    $estado = $proyecto['estado'];
    $categoria = getCategoriaDeEstado($estado);
    
    $es_creador = ((int)($proyecto['usuario_creacion'] ?? 0) === $usuario_id);
    $es_encargado = ((int)($proyecto['encargado_id'] ?? 0) === $usuario_id);
    
    // Directivos y gerenciadores ven todo
    if (in_array($tipo_usuario, ['directivo', 'gerenciador'])) {
        return true;
    }
    
    // Compras: ve Activos, Culminados, Finalizados, Rechazados (NO Preparación)
    if ($tipo_usuario === 'compras') {
        return in_array($categoria, ['activos', 'culminados', 'finalizados', 'rechazados']);
    }
    
    // Almacén: SOLO ve proyectos en estado "comprando_materiales" o "elaboracion"
    if ($tipo_usuario === 'almacen') {
        return in_array($estado, ['comprando_materiales', 'elaboracion']);
    }
    
    // Supervisor y Proyectista:
    // - Ven Preparación SOLO si son creadores
    // - Ven Activos si son creadores O encargados
    // - NO ven Culminados, Finalizados ni Rechazados
    if (in_array($tipo_usuario, ['supervisor', 'proyectista'])) {
        if ($categoria === 'preparacion') {
            return $es_creador;
        }
        if ($categoria === 'activos') {
            return $es_creador || $es_encargado;
        }
        // Otras categorías: NO
        return false;
    }
    
    return false;
}

/**
 * Filtra un array de proyectos devolviendo solo los que el usuario puede ver.
 */
function filtrarProyectosVisibles($proyectos) {
    $visibles = [];
    foreach ($proyectos as $p) {
        if (puedeVerProyecto($p)) {
            $visibles[] = $p;
        }
    }
    return $visibles;
}

/**
 * Agrupa proyectos por categoría.
 * Devuelve un array: ['preparacion' => [...], 'activos' => [...], ...]
 */
function agruparProyectosPorCategoria($proyectos) {
    $grupos = [];
    foreach (getCategoriasProyecto() as $key => $cat) {
        $grupos[$key] = [];
    }
    
    foreach ($proyectos as $p) {
        $cat = getCategoriaDeEstado($p['estado']);
        if (isset($grupos[$cat])) {
            $grupos[$cat][] = $p;
        }
    }
    
    return $grupos;
}

/**
 * Devuelve las categorías que el usuario puede ver según su rol.
 */
function getCategoriasVisiblesParaUsuario() {
    $tipo_usuario = $_SESSION['tipo_usuario'] ?? '';
    $es_master = esMaster();
    
    $todas = array_keys(getCategoriasProyecto());
    
    if ($es_master) return $todas;
    
    if (in_array($tipo_usuario, ['directivo', 'gerenciador'])) {
        return $todas;
    }
    
    if ($tipo_usuario === 'compras') {
        return ['activos', 'culminados', 'finalizados', 'rechazados'];
    }
    
    if ($tipo_usuario === 'almacen') {
        return ['activos']; // aunque solo verá los "comprando_materiales" y "elaboracion"
    }
    
    if (in_array($tipo_usuario, ['supervisor', 'proyectista'])) {
        return ['preparacion', 'activos'];
    }
    
    return [];
}