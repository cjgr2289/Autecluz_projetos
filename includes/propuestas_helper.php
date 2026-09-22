<?php
/**
 * includes/propuestas_helper.php
 * Funciones para el manejo de propuestas económicas
 */

/**
 * Genera el siguiente número de propuesta correlativo.
 * Formato: PROP-YYYY-NNNN (ej: PROP-2026-0001)
 */
function generarNumeroPropuesta($db) {
    $año = date('Y');
    
    // Actualizar correlativo
    $stmt = $db->prepare("
        INSERT INTO correlativos (tipo, prefijo, ultimo_numero, año) 
        VALUES ('propuesta', 'PROP', 1, ?)
        ON DUPLICATE KEY UPDATE 
            ultimo_numero = IF(año = VALUES(año), ultimo_numero + 1, 1),
            año = VALUES(año)
    ");
    $stmt->execute([$año]);
    
    // Obtener el número actual
    $stmt = $db->prepare("SELECT prefijo, ultimo_numero, año FROM correlativos WHERE tipo = 'propuesta'");
    $stmt->execute();
    $corr = $stmt->fetch();
    
    $numero = sprintf('%s-%d-%04d', $corr['prefijo'], $corr['año'], $corr['ultimo_numero']);
    
    return $numero;
}

/**
 * Calcula el precio de venta de un item (costo + % lucro).
 */
function calcularPrecioVenta($costo, $porcentaje_lucro) {
    $costo = (float)$costo;
    $lucro = (float)$porcentaje_lucro;
    return round($costo * (1 + $lucro / 100), 2);
}

/**
 * Calcula el subtotal de mano de obra.
 * 
 * @param string $modo           'horas' o 'dias'
 * @param float $cantidad_tiempo Horas totales o días totales
 * @param int $personas          Cantidad de personas
 * @param float $valor_unitario  R$/hora o R$/día
 * @param int $horas_por_dia     Solo aplica si modo='dias' (default 8)
 * @return float
 */
function calcularManoObra($modo, $cantidad_tiempo, $personas, $valor_unitario, $horas_por_dia = 8) {
    $cantidad_tiempo = (float)$cantidad_tiempo;
    $personas = max(1, (int)$personas);
    $valor_unitario = (float)$valor_unitario;
    
    if ($modo === 'horas') {
        // Modo por horas: valor_hora × horas × personas
        $total_horas = $cantidad_tiempo;
        $total = $total_horas * $valor_unitario * $personas;
    } else {
        // Modo por días: valor_dia × días × personas
        // El valor_unitario es el valor de un día completo (8 horas por defecto)
        $total_dias = $cantidad_tiempo;
        $total = $total_dias * $valor_unitario * $personas;
    }
    
    return round($total, 2);
}

/**
 * Calcula los totales de una propuesta.
 * 
 * @param array $items          Array de items con costo_unitario, porcentaje_lucro, cantidad
 * @param array $mano_obra      Datos de mano de obra
 * @param float $desc_pct       Porcentaje de descuento
 * @param float $imp_pct        Porcentaje de impuestos
 * @return array
 */
function calcularTotalesPropuesta($items, $mano_obra, $desc_pct = 0, $imp_pct = 0) {
    $subtotal_materiales = 0;
    
    foreach ($items as $it) {
        $costo = (float)($it['costo_unitario'] ?? 0);
        $lucro = (float)($it['porcentaje_lucro'] ?? 0);
        $cantidad = (float)($it['cantidad'] ?? 0);
        
        $precio_venta = calcularPrecioVenta($costo, $lucro);
        $subtotal_materiales += $precio_venta * $cantidad;
    }
    
    $subtotal_mano_obra = calcularManoObra(
        $mano_obra['modo'] ?? 'dias',
        $mano_obra['cantidad_tiempo'] ?? 0,
        $mano_obra['personas'] ?? 1,
        $mano_obra['valor_unitario'] ?? 93.00,
        $mano_obra['horas_por_dia'] ?? 8
    );
    
    $subtotal_general = $subtotal_materiales + $subtotal_mano_obra;
    $descuento_valor = round($subtotal_general * ($desc_pct / 100), 2);
    $base_impuesto = $subtotal_general - $descuento_valor;
    $impuestos_valor = round($base_impuesto * ($imp_pct / 100), 2);
    $total_final = $base_impuesto + $impuestos_valor;
    
    return [
        'subtotal_materiales' => round($subtotal_materiales, 2),
        'subtotal_mano_obra'  => round($subtotal_mano_obra, 2),
        'subtotal_general'    => round($subtotal_general, 2),
        'descuento_valor'     => $descuento_valor,
        'impuestos_valor'     => $impuestos_valor,
        'total_final'         => round($total_final, 2),
    ];
}

/**
 * Obtiene una propuesta completa con sus items.
 */
function obtenerPropuestaCompleta($db, $propuesta_id) {
    $stmt = $db->prepare("
        SELECT pe.*, 
               p.nombre as proyecto_nombre,
               p.orden_compra,
               u.nombre_completo as creador,
               e.nombre_completo as encargado_nombre
        FROM propuestas_economicas pe
        JOIN proyectos p ON pe.proyecto_id = p.id
        LEFT JOIN usuarios u ON pe.usuario_creacion = u.id
        LEFT JOIN usuarios e ON p.encargado_id = e.id
        WHERE pe.id = ?
    ");
    $stmt->execute([$propuesta_id]);
    $propuesta = $stmt->fetch();
    
    if (!$propuesta) return null;
    
    // Items
    $stmt = $db->prepare("
        SELECT * FROM propuestas_items 
        WHERE propuesta_id = ? 
        ORDER BY orden ASC, id ASC
    ");
    $stmt->execute([$propuesta_id]);
    $propuesta['items'] = $stmt->fetchAll();
    
    return $propuesta;
}

/**
 * Obtiene todas las propuestas de un proyecto.
 */
function obtenerPropuestasProyecto($db, $proyecto_id) {
    $stmt = $db->prepare("
        SELECT pe.*, 
               u.nombre_completo as creador,
               (SELECT COUNT(*) FROM propuestas_items pi WHERE pi.propuesta_id = pe.id) as total_items
        FROM propuestas_economicas pe
        LEFT JOIN usuarios u ON pe.usuario_creacion = u.id
        WHERE pe.proyecto_id = ?
        ORDER BY pe.fecha_creacion DESC
    ");
    $stmt->execute([$proyecto_id]);
    return $stmt->fetchAll();
}

/**
 * Genera HTML del encabezado de Autecluz para la propuesta.
 */
function renderEncabezadoAutecluz($titulo = 'PROPUESTA ECONÓMICA', $numero = '', $fecha = null) {
    $fecha = $fecha ?: date('d/m/Y');
    ?>
    <div class="propuesta-header-institucional">
        <div class="propuesta-empresa">
            <div class="propuesta-logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 100 100">
                    <rect width="100" height="100" rx="8" fill="#0d47a1"/>
                    <text x="50" y="58" text-anchor="middle" fill="white" font-family="Arial Black" font-size="34" font-weight="900">A</text>
                    <text x="50" y="80" text-anchor="middle" fill="#64b5f6" font-family="Arial" font-size="10" font-weight="bold">AUTECLUZ</text>
                </svg>
            </div>
            <div class="propuesta-empresa-datos">
                <div class="propuesta-empresa-nombre">AUTECLUZ SOLUÇÕES INDUSTRIAIS LTDA</div>
                <div class="propuesta-empresa-info">
                    CNPJ: 00.000.000/0001-00<br>
                    Endereço: Rua Exemplo, 123 - Cidade<br>
                    Tel: (00) 0000-0000 · Email: contato@autecluz.com
                </div>
            </div>
        </div>
        <div class="propuesta-doc-datos">
            <div class="propuesta-doc-titulo"><?php echo htmlspecialchars($titulo); ?></div>
            <?php if ($numero): ?>
                <div class="propuesta-doc-numero">
                    <span>Nº</span> <strong><?php echo htmlspecialchars($numero); ?></strong>
                </div>
            <?php endif; ?>
            <div class="propuesta-doc-fecha">
                <span>Data:</span> <?php echo $fecha; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Obtiene los items del proyecto que pueden incluirse en una propuesta.
 */
function obtenerItemsDisponiblesPropuesta($db, $proyecto_id) {
    $stmt = $db->prepare("
        SELECT i.*, 
               p.nombre as producto_nombre,
               p.costo_actual as producto_costo,
               p.porcentaje_lucro as producto_lucro,
               c.nombre as categoria_nombre
        FROM items_proyecto i
        LEFT JOIN productos p ON i.producto_id = p.id
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE i.proyecto_id = ?
        ORDER BY c.nombre ASC, i.nombre_item ASC
    ");
    $stmt->execute([$proyecto_id]);
    return $stmt->fetchAll();
}