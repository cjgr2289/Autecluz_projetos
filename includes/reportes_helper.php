<?php
/**
 * includes/reportes_helper.php
 * Funciones auxiliares para reportes
 */

/**
 * Formatea un valor monetario
 */
function formatearMoneda($valor, $moneda = 'USD') {
    if ($valor === null || $valor === '') {
        return '-';
    }
    
    $simbolos = [
        'USD' => '$',
        'BRL' => 'R$',
        'EUR' => '€',
        'ARS' => '$',
        'PYG' => '₲',
        'UYU' => '$U',
    ];
    
    $simbolo = $simbolos[$moneda] ?? $moneda . ' ';
    return $simbolo . ' ' . number_format((float)$valor, 2, ',', '.');
}

/**
 * Devuelve el orden preferido de los estados de items para reportes.
 * Incluye los nuevos estados "entregado" y "recibido".
 */
function getOrdenEstadosItem() {
    return [
        'solicitado',
        'pendiente',
        'cotacion',
        'orçado',
        'stock',
        'pendiente_pago',
        'comprado_llegar',
        'llego',
        'entregado',
        'recibido',
    ];
}

/**
 * Calcula estadísticas de un proyecto (totales, subtotales por estado, etc.)
 */
function calcularEstadisticasProyecto($items) {
    $total_items = count($items);
    $total_cantidad = 0;
    $total_costo = 0;
    $items_con_costo = 0;
    $items_completados = 0;      // entregado + recibido
    $items_en_proceso = 0;       // cualquier estado intermedio
    $por_estado = [];
    $por_categoria = [];
    
    $estados_finales = ['entregado', 'recibido'];
    $estados_pendientes = ['solicitado', 'pendiente', 'cotacion', 'orçado', 
                           'pendiente_pago', 'comprado_llegar', 'llego', 'stock'];
    
    foreach ($items as $item) {
        $total_cantidad += (int)$item['cantidad'];
        
        $costo = (float)($item['costo_unitario'] ?? 0);
        if ($costo > 0) {
            $total_costo += $costo * (int)$item['cantidad'];
            $items_con_costo++;
        }
        
        // Categorizar por tipo de estado
        if (in_array($item['estado'], $estados_finales)) {
            $items_completados++;
        } elseif (in_array($item['estado'], $estados_pendientes)) {
            $items_en_proceso++;
        }
        
        $estado = $item['estado'];
        if (!isset($por_estado[$estado])) {
            $por_estado[$estado] = ['cantidad' => 0, 'items' => 0, 'costo' => 0];
        }
        $por_estado[$estado]['items']++;
        $por_estado[$estado]['cantidad'] += (int)$item['cantidad'];
        $por_estado[$estado]['costo'] += $costo * (int)$item['cantidad'];
        
        $cat = $item['categoria_nombre'] ?? 'Sin categoría';
        if (!isset($por_categoria[$cat])) {
            $por_categoria[$cat] = ['cantidad' => 0, 'items' => 0, 'costo' => 0];
        }
        $por_categoria[$cat]['items']++;
        $por_categoria[$cat]['cantidad'] += (int)$item['cantidad'];
        $por_categoria[$cat]['costo'] += $costo * (int)$item['cantidad'];
    }
    
    // Calcular porcentaje de completitud
    $porcentaje_completado = $total_items > 0 
        ? round(($items_completados / $total_items) * 100, 1) 
        : 0;
    
    return [
        'total_items'              => $total_items,
        'total_cantidad'           => $total_cantidad,
        'total_costo'              => $total_costo,
        'items_con_costo'          => $items_con_costo,
        'items_sin_costo'          => $total_items - $items_con_costo,
        'items_completados'        => $items_completados,
        'items_en_proceso'         => $items_en_proceso,
        'porcentaje_completado'    => $porcentaje_completado,
        'por_estado'               => $por_estado,
        'por_categoria'            => $por_categoria,
    ];
}

/**
 * Genera el HTML del encabezado de reporte (para imprimir)
 */
function renderEncabezadoReporte($titulo, $subtitulo = '', $proyecto = null) {
    $idioma = $_SESSION['idioma'] ?? 'es';
    $fecha = date('d/m/Y H:i');
    ?>
    <div class="reporte-header">
        <div class="reporte-header-left">
            <h1><?php echo htmlspecialchars($titulo); ?></h1>
            <?php if ($subtitulo): ?>
                <p class="reporte-subtitulo"><?php echo htmlspecialchars($subtitulo); ?></p>
            <?php endif; ?>
            <?php if ($proyecto): ?>
                <div class="reporte-proyecto">
                    <strong><?php echo $idioma === 'pt' ? 'Projeto' : 'Proyecto'; ?>:</strong>
                    <?php echo htmlspecialchars($proyecto['nombre']); ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="reporte-header-right">
            <div class="reporte-fecha">
                <strong><?php echo $idioma === 'pt' ? 'Emitido em' : 'Emitido el'; ?>:</strong>
                <?php echo $fecha; ?>
            </div>
            <div class="reporte-sistema">Sistema de Proyectos</div>
        </div>
    </div>
    <?php
}