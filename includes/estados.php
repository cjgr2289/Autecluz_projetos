<?php
/**
 * includes/estados.php
 * Catálogos de estados para proyectos e items
 */

/**
 * Devuelve los estados posibles de un proyecto (traducidos según idioma)
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
 * Devuelve los estados posibles de un item (traducidos según idioma)
 */
function getEstadosItem() {
    $idioma = $_SESSION['idioma'] ?? 'es';
    
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

/**
 * Devuelve los estados permitidos para un item según el estado del proyecto.
 * Aplica las reglas de negocio automáticas.
 */
function getEstadosItemByProyectoEstado($proyecto_estado) {
    $estados_permitidos = getEstadosItem();
    
    // Si el proyecto está en orçado o pendiente aprobación → solo "solicitado"
    if (in_array($proyecto_estado, ['orçado', 'pendente_aprovacion_cliente'])) {
        return ['solicitado' => $estados_permitidos['solicitado']];
    }
    
    // Si el proyecto está aprobado o más adelante → todos menos "pendiente" (es automático)
    if (in_array($proyecto_estado, ['aprovado_cliente', 'espera_orden_compra', 'comprando_materiales',
                                     'elaboracion', 'terminado', 'pendiente_cobro_cliente', 'finalizado'])) {
        unset($estados_permitidos['pendiente']);
        return $estados_permitidos;
    }
    
    return $estados_permitidos;
}