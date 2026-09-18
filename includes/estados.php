<?php
/**
 * includes/estados.php
 * Catálogos de estados para proyectos e items
 */

function getEstadosProyecto() {
    $idioma = $_SESSION['idioma'] ?? 'es';
    
    $estados = [
        'es' => [
            'solicitado' => 'Solicitado',
            'orçado' => 'Orçado',
            'pendente_aprovacion_cliente' => 'Pendiente por Aprobación',
            'aprovado_cliente' => 'Aprobado por Cliente',
            'espera_orden_compra' => 'A espera de Orden de Compra',
            'comprando_materiales' => 'Comprando Materiales',
            'elaboracion' => 'Elaboración',
            'terminado' => 'Terminado',
            'pendiente_cobro_cliente' => 'Pendiente por Cobro',
            'finalizado' => 'Finalizado'
        ],
        'pt' => [
            'solicitado' => 'Solicitado',
            'orçado' => 'Orçado',
            'pendente_aprovacion_cliente' => 'Pendente por Aprovação',
            'aprovado_cliente' => 'Aprovado pelo Cliente',
            'espera_orden_compra' => 'Aguardando Ordem de Compra',
            'comprando_materiales' => 'Comprando Materiais',
            'elaboracion' => 'Elaboração',
            'terminado' => 'Terminado',
            'pendiente_cobro_cliente' => 'Pendente de Cobrança',
            'finalizado' => 'Finalizado'
        ]
    ];
    
    return $estados[$idioma] ?? $estados['es'];
}

/**
 * Estados de un item, incluyendo "separado"
 */
function getEstadosItem() {
    $idioma = $_SESSION['idioma'] ?? 'es';
    
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

/**
 * Estados permitidos según el estado del proyecto
 */
function getEstadosItemByProyectoEstado($proyecto_estado) {
    $estados_permitidos = getEstadosItem();
    
    if (in_array($proyecto_estado, ['orçado', 'pendente_aprovacion_cliente'])) {
        return ['solicitado' => $estados_permitidos['solicitado']];
    }
    
    if (in_array($proyecto_estado, ['aprovado_cliente', 'espera_orden_compra', 'comprando_materiales',
                                     'elaboracion', 'terminado', 'pendiente_cobro_cliente', 'finalizado'])) {
        unset($estados_permitidos['pendiente']);
        return $estados_permitidos;
    }
    
    return $estados_permitidos;
}

/**
 * Transiciones válidas de estado para items
 */
function getTransicionesValidasItem() {
    return [
        'solicitado'       => ['pendiente', 'cotacion', 'stock'],
        'pendiente'        => ['cotacion', 'stock', 'pendiente_pago'],
        'cotacion'         => ['orçado', 'pendiente_pago', 'stock'],
        'orçado'           => ['pendiente_pago', 'comprado_llegar', 'stock'],
        'stock'            => ['separado', 'pendiente_pago', 'comprado_llegar', 'cotacion'],
        'separado'         => ['entregado', 'stock'],  // puede devolverse a stock
        'pendiente_pago'   => ['comprado_llegar', 'stock', 'llego'],
        'comprado_llegar'  => ['llego', 'stock', 'separado'],
        'llego'            => ['separado', 'stock', 'entregado'],
        'entregado'        => ['recibido', 'separado'],
        'recibido'         => [],
    ];
}

/**
 * Devuelve el siguiente estado lógico sugerido
 */
function getSiguienteEstadoSugerido($estado_actual) {
    $flujo = [
        'solicitado'       => 'cotacion',
        'pendiente'        => 'cotacion',
        'cotacion'         => 'orçado',
        'orçado'           => 'pendiente_pago',
        'stock'            => 'separado',
        'separado'         => 'entregado',
        'pendiente_pago'   => 'comprado_llegar',
        'comprado_llegar'  => 'llego',
        'llego'            => 'separado',
        'entregado'        => 'recibido',
        'recibido'         => null,
    ];
    
    return $flujo[$estado_actual] ?? null;
}