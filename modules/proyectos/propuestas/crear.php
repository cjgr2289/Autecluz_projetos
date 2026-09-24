<?php
// modules/proyectos/propuestas/crear.php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador', 'comercial']) && !esMaster()) {
    redirigir('modules/proyectos/index.php');
}

$db = Database::getInstance()->getConnection();
$proyecto_id = $_GET['proyecto'] ?? 0;

if (!$proyecto_id) {
    redirigir('modules/proyectos/index.php');
}

$stmt = $db->prepare("SELECT * FROM proyectos WHERE id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    redirigir('modules/proyectos/index.php');
}

// Items del proyecto disponibles
$items_disponibles = obtenerItemsDisponiblesPropuesta($db, $proyecto_id);

$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Datos básicos
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Mano de obra
    $modo_mano_obra = $_POST['modo_mano_obra'] ?? 'dias';
    $cantidad_tiempo = (float)($_POST['cantidad_tiempo'] ?? 0);
    $personas = (int)($_POST['personas'] ?? 1);
    $valor_unitario = (float)($_POST['valor_unitario'] ?? 93.00);
    $horas_por_dia = (int)($_POST['horas_por_dia'] ?? 8);
    
    // Descuentos e impuestos
    $descuento_porcentaje = (float)($_POST['descuento_porcentaje'] ?? 0);
    $impuestos_porcentaje = (float)($_POST['impuestos_porcentaje'] ?? 0);
    
    // Validez
    $validez_dias = (int)($_POST['validez_dias'] ?? 30);
    $condiciones_pago = trim($_POST['condiciones_pago'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Moneda
    $moneda = $_POST['moneda'] ?? 'BRL';
    
    // Items seleccionados
    $items_data = $_POST['items'] ?? [];
    
    // Validaciones
    if (empty($items_data)) {
        $errores[] = 'Debe seleccionar al menos un item';
    }
    if ($cantidad_tiempo <= 0) {
        $errores[] = 'La cantidad de tiempo debe ser mayor a 0';
    }
    if ($personas < 1) {
        $errores[] = 'Debe indicar al menos 1 persona';
    }
    
    if (empty($errores)) {
        try {
            $db->beginTransaction();
            
            // Preparar items para calcular
            $items_procesados = [];
            foreach ($items_data as $item_id => $datos) {
                if (empty($datos['incluir'])) continue;
                
                $item_proyecto_id = (int)$item_id;
                
                // Obtener datos del item
                $stmt = $db->prepare("
                    SELECT i.*, 
                           p.costo_actual as producto_costo,
                           p.porcentaje_lucro as producto_lucro,
                           c.nombre as categoria_nombre
                    FROM items_proyecto i
                    LEFT JOIN productos p ON i.producto_id = p.id
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    WHERE i.id = ? AND i.proyecto_id = ?
                ");
                $stmt->execute([$item_proyecto_id, $proyecto_id]);
                $item = $stmt->fetch();
                
                if (!$item) continue;
                
                $costo = (float)($datos['costo_unitario'] ?? $item['costo_unitario'] ?? $item['producto_costo'] ?? 0);
                $lucro = (float)($datos['porcentaje_lucro'] ?? $item['producto_lucro'] ?? 30);
                $cantidad = (float)($datos['cantidad'] ?? $item['cantidad']);
                
                $items_procesados[] = [
                    'item_id'          => $item['id'],
                    'producto_id'      => $item['producto_id'],
                    'nombre_item'      => $item['nombre_item'],
                    'descripcion'      => $item['especificaciones'],
                    'categoria'        => $item['categoria_nombre'],
                    'unidad_medida'    => $item['unidad_medida'],
                    'cantidad'         => $cantidad,
                    'costo_unitario'   => $costo,
                    'porcentaje_lucro' => $lucro,
                    'precio_venta_unitario' => calcularPrecioVenta($costo, $lucro),
                    'subtotal'         => calcularPrecioVenta($costo, $lucro) * $cantidad,
                    'orden'            => $datos['orden'] ?? 0,
                ];
            }
            
            // Calcular totales
            $mano_obra_data = [
                'modo' => $modo_mano_obra,
                'cantidad_tiempo' => $cantidad_tiempo,
                'personas' => $personas,
                'valor_unitario' => $valor_unitario,
                'horas_por_dia' => $horas_por_dia,
            ];
            
            $totales = calcularTotalesPropuesta($items_procesados, $mano_obra_data, $descuento_porcentaje, $impuestos_porcentaje);
            
            // Generar número
            $numero = generarNumeroPropuesta($db);
            
            // Insertar propuesta
            $stmt = $db->prepare("
                INSERT INTO propuestas_economicas 
                    (proyecto_id, numero, titulo, descripcion,
                     modo_mano_obra, cantidad_tiempo, personas, valor_unitario, horas_por_dia,
                     subtotal_materiales, subtotal_mano_obra, subtotal_general,
                     descuento_porcentaje, descuento_valor,
                     impuestos_porcentaje, impuestos_valor,
                     total_final, moneda, validez_dias, condiciones_pago, observaciones,
                     usuario_creacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $proyecto_id, $numero, $titulo, $descripcion,
                $modo_mano_obra, $cantidad_tiempo, $personas, $valor_unitario, $horas_por_dia,
                $totales['subtotal_materiales'], $totales['subtotal_mano_obra'], $totales['subtotal_general'],
                $descuento_porcentaje, $totales['descuento_valor'],
                $impuestos_porcentaje, $totales['impuestos_valor'],
                $totales['total_final'], $moneda, $validez_dias, $condiciones_pago, $observaciones,
                $_SESSION['usuario_id']
            ]);
            
            $propuesta_id = $db->lastInsertId();
            
            // Insertar items
            $stmt = $db->prepare("
                INSERT INTO propuestas_items 
                    (propuesta_id, item_id, producto_id, nombre_item, descripcion,
                     categoria, unidad_medida, cantidad, costo_unitario, porcentaje_lucro,
                     precio_venta_unitario, subtotal, orden)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($items_procesados as $it) {
                $stmt->execute([
                    $propuesta_id, $it['item_id'], $it['producto_id'],
                    $it['nombre_item'], $it['descripcion'], $it['categoria'],
                    $it['unidad_medida'], $it['cantidad'],
                    $it['costo_unitario'], $it['porcentaje_lucro'],
                    $it['precio_venta_unitario'], $it['subtotal'], $it['orden']
                ]);
            }
            
            $db->commit();
            
            redirigir('modules/proyectos/propuestas/ver.php?id=' . $propuesta_id . '&mensaje=creado');
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errores[] = 'Error al crear la propuesta: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['idioma'] == 'pt' ? 'Nova Proposta Econômica' : 'Nueva Propuesta Económica'; ?></title>
     <link rel="shortcut icon" href="../../../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/propuestas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo $_SESSION['idioma'] == 'pt' ? 'Nova Proposta Econômica' : 'Nueva Propuesta Económica'; ?></h1>
            <a href="index.php?proyecto=<?php echo $proyecto_id; ?>" class="btn-secondary">
                ← <?php echo traducir('Volver'); ?>
            </a>
        </div>
        
        <?php if (!empty($errores)): ?>
            <div class="error-message">
                <ul style="margin: 0.5rem 0 0 1.5rem;">
                    <?php foreach ($errores as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="info-message">
            <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong>
            <?php if ($proyecto['orden_compra']): ?>
                — O.C.: <?php echo htmlspecialchars($proyecto['orden_compra']); ?>
            <?php endif; ?>
        </div>
        
        <form method="POST" id="form-propuesta">
            
            <!-- ===== Datos básicos ===== -->
            <div class="form-section">
                <h3>📋 <?php echo $_SESSION['idioma'] == 'pt' ? 'Dados Básicos' : 'Datos Básicos'; ?></h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Título da Proposta' : 'Título de la Propuesta'; ?></label>
                        <input type="text" name="titulo" 
                               value="<?php echo htmlspecialchars($_POST['titulo'] ?? 'Propuesta Económica - ' . $proyecto['nombre']); ?>"
                               maxlength="200">
                    </div>
                    <div class="form-group" style="max-width:150px;">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Validade (dias)' : 'Validez (días)'; ?></label>
                        <input type="number" name="validez_dias" value="<?php echo (int)($_POST['validez_dias'] ?? 30); ?>" min="1" max="365">
                    </div>
                    <div class="form-group" style="max-width:150px;">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Moeda' : 'Moneda'; ?></label>
                        <select name="moneda">
                            <option value="BRL" <?php echo ($_POST['moneda'] ?? 'BRL') === 'BRL' ? 'selected' : ''; ?>>BRL</option>
                            <option value="USD" <?php echo ($_POST['moneda'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Descrição' : 'Descripción'; ?></label>
                    <textarea name="descripcion" rows="2"><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- ===== Mano de obra ===== -->
            <div class="form-section">
                <h3>👷 <?php echo $_SESSION['idioma'] == 'pt' ? 'Mão de Obra' : 'Mano de Obra'; ?></h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Modo de Cálculo' : 'Modo de Cálculo'; ?></label>
                        <select name="modo_mano_obra" id="modo_mano_obra" onchange="actualizarModoManoObra()">
                            <option value="dias" <?php echo ($_POST['modo_mano_obra'] ?? 'dias') === 'dias' ? 'selected' : ''; ?>>
                                <?php echo $_SESSION['idioma'] == 'pt' ? 'Por Días' : 'Por Días'; ?>
                            </option>
                            <option value="horas" <?php echo ($_POST['modo_mano_obra'] ?? '') === 'horas' ? 'selected' : ''; ?>>
                                <?php echo $_SESSION['idioma'] == 'pt' ? 'Por Horas' : 'Por Horas'; ?>
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label id="label-tiempo">
                            <?php echo $_SESSION['idioma'] == 'pt' ? 'Cantidade de Días' : 'Cantidad de Días'; ?>
                        </label>
                        <input type="number" name="cantidad_tiempo" id="cantidad_tiempo" 
                               value="<?php echo (float)($_POST['cantidad_tiempo'] ?? 1); ?>" 
                               min="0.5" step="0.5" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Pessoas' : 'Personas'; ?></label>
                        <input type="number" name="personas" value="<?php echo (int)($_POST['personas'] ?? 1); ?>" min="1" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label id="label-valor">
                            <?php echo $_SESSION['idioma'] == 'pt' ? 'Valor por Día (R$)' : 'Valor por Día (R$)'; ?>
                        </label>
                        <input type="number" name="valor_unitario" id="valor_unitario" 
                               value="<?php echo (float)($_POST['valor_unitario'] ?? 93.00); ?>" 
                               min="0" step="0.01" required>
                        <small style="color:#7f8c8d;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Valor padrão: R$ 93,00/dia' : 'Valor predeterminado: R$ 93,00/día'; ?></small>
                    </div>
                    <div class="form-group" id="grupo-horas-dia">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Horas por Día' : 'Horas por Día'; ?></label>
                        <input type="number" name="horas_por_dia" value="<?php echo (int)($_POST['horas_por_dia'] ?? 8); ?>" min="1" max="24">
                    </div>
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal Mão de Obra' : 'Subtotal Mano de Obra'; ?></label>
                        <input type="text" id="subtotal_mano_obra_preview" readonly 
                               style="background:#f8f9fa; font-weight:bold; color:#27ae60;"
                               value="R$ 0,00">
                    </div>
                </div>
            </div>
            
            <!-- ===== Materiales ===== -->
            <div class="form-section">
                <h3>📦 <?php echo $_SESSION['idioma'] == 'pt' ? 'Materiais' : 'Materiales'; ?></h3>
                <p style="font-size:0.85rem; color:#7f8c8d; margin-bottom:1rem;">
                    <?php echo $_SESSION['idioma'] == 'pt'
                        ? 'Selecione os itens que farão parte da proposta e ajuste o lucro se necessário.'
                        : 'Seleccione los items que formarán parte de la propuesta y ajuste el lucro si es necesario.'; ?>
                </p>
                
                <?php if (empty($items_disponibles)): ?>
                    <div class="info-message">
                        ⚠ <?php echo $_SESSION['idioma'] == 'pt' 
                            ? 'Este projeto ainda não tem itens cadastrados.' 
                            : 'Este proyecto aún no tiene items registrados.'; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="tabla-items-propuesta">
                            <thead>
                                <tr>
                                    <th style="width:40px;" class="text-center">
                                        <input type="checkbox" id="toggle-todos" onchange="toggleTodos(this)">
                                    </th>
                                    <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Item' : 'Item'; ?></th>
                                    <th style="width:80px;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Cant.' : 'Cant.'; ?></th>
                                    <th style="width:110px;" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo Unit.' : 'Costo Unit.'; ?></th>
                                    <th style="width:100px;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Lucro %' : 'Lucro %'; ?></th>
                                    <th style="width:120px;" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'Preço Venda' : 'Precio Venta'; ?></th>
                                    <th style="width:130px;" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal' : 'Subtotal'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items_disponibles as $idx => $it): 
                                    $costo = (float)($it['costo_unitario'] ?? $it['producto_costo'] ?? 0);
                                    $lucro = (float)($it['producto_lucro'] ?? 30);
                                    $cantidad = (float)$it['cantidad'];
                                    $precio_venta = calcularPrecioVenta($costo, $lucro);
                                    $subtotal = $precio_venta * $cantidad;
                                ?>
                                    <tr class="fila-item" data-item-id="<?php echo $it['id']; ?>">
                                        <td class="text-center">
                                            <input type="checkbox" 
                                                   name="items[<?php echo $it['id']; ?>][incluir]" 
                                                   value="1"
                                                   class="check-item"
                                                   data-item-id="<?php echo $it['id']; ?>"
                                                   onchange="toggleItem(this)">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($it['nombre_item']); ?></strong>
                                            <?php if ($it['categoria_nombre']): ?>
                                                <br><small style="color:#95a5a6;"><?php echo htmlspecialchars($it['categoria_nombre']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" 
                                                   name="items[<?php echo $it['id']; ?>][cantidad]" 
                                                   value="<?php echo $cantidad; ?>"
                                                   data-item-id="<?php echo $it['id']; ?>"
                                                   class="input-cantidad"
                                                   min="0.01" step="0.01"
                                                   onchange="recalcularFila(<?php echo $it['id']; ?>)">
                                        </td>
                                        <td class="text-right">
                                            <input type="number" 
                                                   name="items[<?php echo $it['id']; ?>][costo_unitario]" 
                                                   value="<?php echo number_format($costo, 2, '.', ''); ?>"
                                                   data-item-id="<?php echo $it['id']; ?>"
                                                   class="input-costo"
                                                   min="0" step="0.01"
                                                   onchange="recalcularFila(<?php echo $it['id']; ?>)">
                                        </td>
                                        <td class="text-center">
                                            <input type="number" 
                                                   name="items[<?php echo $it['id']; ?>][porcentaje_lucro]" 
                                                   value="<?php echo number_format($lucro, 2, '.', ''); ?>"
                                                   data-item-id="<?php echo $it['id']; ?>"
                                                   class="input-lucro"
                                                   min="0" step="0.1"
                                                   onchange="recalcularFila(<?php echo $it['id']; ?>)">
                                        </td>
                                        <td class="text-right">
                                            <strong class="precio-venta" data-item-id="<?php echo $it['id']; ?>" style="color:#2980b9;">
                                                <?php echo formatearMoneda($precio_venta, 'BRL'); ?>
                                            </strong>
                                        </td>
                                        <td class="text-right">
                                            <strong class="subtotal-item" data-item-id="<?php echo $it['id']; ?>" style="color:#27ae60;">
                                                <?php echo formatearMoneda($subtotal, 'BRL'); ?>
                                            </strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:#f0f2f5; font-weight:bold;">
                                    <td colspan="6" class="text-right" style="padding:0.75rem;">
                                        <?php echo $_SESSION['idioma'] == 'pt' ? 'SUBTOTAL MATERIAIS' : 'SUBTOTAL MATERIALES'; ?>:
                                    </td>
                                    <td class="text-right" style="padding:0.75rem; color:#27ae60; font-size:1rem;" id="subtotal-materiales">
                                        R$ 0,00
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- ===== Descuentos e impuestos ===== -->
            <div class="form-section">
                <h3>💰 <?php echo $_SESSION['idioma'] == 'pt' ? 'Ajustes Finales' : 'Ajustes Finales'; ?></h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Desconto (%)' : 'Descuento (%)'; ?></label>
                        <input type="number" name="descuento_porcentaje" id="descuento_porcentaje"
                               value="<?php echo (float)($_POST['descuento_porcentaje'] ?? 0); ?>"
                               min="0" max="100" step="0.1"
                               onchange="recalcularTotales()">
                    </div>
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Impostos (%)' : 'Impuestos (%)'; ?></label>
                        <input type="number" name="impuestos_porcentaje" id="impuestos_porcentaje"
                               value="<?php echo (float)($_POST['impuestos_porcentaje'] ?? 0); ?>"
                               min="0" max="100" step="0.1"
                               onchange="recalcularTotales()">
                    </div>
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Condições de Pagamento' : 'Condiciones de Pago'; ?></label>
                        <input type="text" name="condiciones_pago" 
                               value="<?php echo htmlspecialchars($_POST['condiciones_pago'] ?? ''); ?>"
                               placeholder="Ej: 50% anticipo, 50% contra entrega"
                               maxlength="200">
                    </div>
                </div>
            </div>
            
            <!-- ===== Resumen ===== -->
            <div class="form-section">
                <h3>📊 <?php echo $_SESSION['idioma'] == 'pt' ? 'Resumo' : 'Resumen'; ?></h3>
                
                <div class="resumen-propuesta">
                    <div class="resumen-linea">
                        <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal Materiais' : 'Subtotal Materiales'; ?>:</span>
                        <span id="res-sub-mat">R$ 0,00</span>
                    </div>
                    <div class="resumen-linea">
                        <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal Mão de Obra' : 'Subtotal Mano de Obra'; ?>:</span>
                        <span id="res-sub-mo">R$ 0,00</span>
                    </div>
                    <div class="resumen-linea resumen-subtotal">
                        <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal Geral' : 'Subtotal General'; ?>:</span>
                        <span id="res-sub-total">R$ 0,00</span>
                    </div>
                    <div class="resumen-linea resumen-descuento">
                        <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Desconto' : 'Descuento'; ?>:</span>
                        <span id="res-descuento">- R$ 0,00</span>
                    </div>
                    <div class="resumen-linea resumen-impuestos">
                        <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Impostos' : 'Impuestos'; ?>:</span>
                        <span id="res-impuestos">+ R$ 0,00</span>
                    </div>
                    <div class="resumen-linea resumen-total">
                        <span><?php echo $_SESSION['idioma'] == 'pt' ? 'TOTAL FINAL' : 'TOTAL FINAL'; ?>:</span>
                        <span id="res-total">R$ 0,00</span>
                    </div>
                </div>
            </div>
            
            <!-- ===== Observaciones ===== -->
            <div class="form-section">
                <h3>📝 <?php echo $_SESSION['idioma'] == 'pt' ? 'Observações' : 'Observaciones'; ?></h3>
                <div class="form-group">
                    <textarea name="observaciones" rows="4" 
                              placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Observações adicionais...' : 'Observaciones adicionales...'; ?>"><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
                <button type="submit" class="btn-primary">💾 <?php echo $_SESSION['idioma'] == 'pt' ? 'Salvar Proposta' : 'Guardar Propuesta'; ?></button>
                <a href="index.php?proyecto=<?php echo $proyecto_id; ?>" class="btn-secondary"><?php echo traducir('Cancelar'); ?></a>
            </div>
        </form>
    </div>
    
    <script>
    const HORAS_POR_DIA_DEFAULT = 8;
    const VALOR_DIA_DEFAULT = 93.00;
    const MONEDA = 'BRL';
    const SIMBOLO = 'R$';
    
    function formatearMonedaJS(valor) {
        return SIMBOLO + ' ' + valor.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    function actualizarModoManoObra() {
        const modo = document.getElementById('modo_mano_obra').value;
        const labelTiempo = document.getElementById('label-tiempo');
        const labelValor = document.getElementById('label-valor');
        const grupoHorasDia = document.getElementById('grupo-horas-dia');
        
        if (modo === 'horas') {
            labelTiempo.textContent = '<?php echo $_SESSION['idioma'] == 'pt' ? "Quantidade de Horas" : "Cantidad de Horas"; ?>';
            labelValor.textContent = '<?php echo $_SESSION['idioma'] == 'pt' ? "Valor por Hora (R$)" : "Valor por Hora (R$)"; ?>';
            grupoHorasDia.style.opacity = '0.4';
            grupoHorasDia.querySelector('input').disabled = true;
        } else {
            labelTiempo.textContent = '<?php echo $_SESSION['idioma'] == 'pt' ? "Quantidade de Días" : "Cantidad de Días"; ?>';
            labelValor.textContent = '<?php echo $_SESSION['idioma'] == 'pt' ? "Valor por Día (R$)" : "Valor por Día (R$)"; ?>';
            grupoHorasDia.style.opacity = '1';
            grupoHorasDia.querySelector('input').disabled = false;
        }
        
        recalcularTotales();
    }
    
    function toggleTodos(checkbox) {
        document.querySelectorAll('.check-item').forEach(chk => {
            chk.checked = checkbox.checked;
            toggleItem(chk);
        });
    }
    
    function toggleItem(checkbox) {
        const fila = checkbox.closest('tr');
        const inputs = fila.querySelectorAll('input:not([type="checkbox"])');
        
        if (checkbox.checked) {
            fila.style.background = '#e8f5e9';
            inputs.forEach(inp => inp.disabled = false);
        } else {
            fila.style.background = '';
            inputs.forEach(inp => inp.disabled = true);
        }
        
        recalcularTotales();
    }
    
    function recalcularFila(itemId) {
        const fila = document.querySelector(`tr[data-item-id="${itemId}"]`);
        if (!fila) return;
        
        const costo = parseFloat(fila.querySelector('.input-costo').value) || 0;
        const lucro = parseFloat(fila.querySelector('.input-lucro').value) || 0;
        const cantidad = parseFloat(fila.querySelector('.input-cantidad').value) || 0;
        
        const precioVenta = costo * (1 + lucro / 100);
        const subtotal = precioVenta * cantidad;
        
        fila.querySelector('.precio-venta').textContent = formatearMonedaJS(precioVenta);
        fila.querySelector('.subtotal-item').textContent = formatearMonedaJS(subtotal);
        
        recalcularTotales();
    }
    
    function recalcularTotales() {
        // Subtotal materiales
        let subtotalMateriales = 0;
        document.querySelectorAll('.check-item:checked').forEach(chk => {
            const fila = chk.closest('tr');
            const subtotalTxt = fila.querySelector('.subtotal-item').textContent;
            const subtotal = parseFloat(subtotalTxt.replace(/[R$\s.]/g, '').replace(',', '.')) || 0;
            subtotalMateriales += subtotal;
        });
        
        // Subtotal mano de obra
        const modo = document.getElementById('modo_mano_obra').value;
        const cantidadTiempo = parseFloat(document.getElementById('cantidad_tiempo').value) || 0;
        const personas = parseInt(document.querySelector('input[name="personas"]').value) || 1;
        const valorUnitario = parseFloat(document.getElementById('valor_unitario').value) || 0;
        
        const subtotalManoObra = cantidadTiempo * valorUnitario * personas;
        
        document.getElementById('subtotal_mano_obra_preview').value = formatearMonedaJS(subtotalManoObra);
        document.getElementById('subtotal-materiales').textContent = formatearMonedaJS(subtotalMateriales);
        
        // Subtotal general
        const subtotalGeneral = subtotalMateriales + subtotalManoObra;
        
        // Descuento
        const descPct = parseFloat(document.getElementById('descuento_porcentaje').value) || 0;
        const descValor = subtotalGeneral * (descPct / 100);
        
        // Impuestos
        const impPct = parseFloat(document.getElementById('impuestos_porcentaje').value) || 0;
        const impValor = (subtotalGeneral - descValor) * (impPct / 100);
        
        // Total
        const totalFinal = subtotalGeneral - descValor + impValor;
        
        // Actualizar resumen
        document.getElementById('res-sub-mat').textContent = formatearMonedaJS(subtotalMateriales);
        document.getElementById('res-sub-mo').textContent = formatearMonedaJS(subtotalManoObra);
        document.getElementById('res-sub-total').textContent = formatearMonedaJS(subtotalGeneral);
        document.getElementById('res-descuento').textContent = '- ' + formatearMonedaJS(descValor);
        document.getElementById('res-impuestos').textContent = '+ ' + formatearMonedaJS(impValor);
        document.getElementById('res-total').textContent = formatearMonedaJS(totalFinal);
    }
    
    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        actualizarModoManoObra();
        
        // Escuchar cambios en mano de obra
        ['cantidad_tiempo', 'valor_unitario'].forEach(id => {
            document.getElementById(id).addEventListener('input', recalcularTotales);
        });
        document.querySelector('input[name="personas"]').addEventListener('input', recalcularTotales);
    });
    </script>
    
    <?php include '../../../includes/footer.php'; ?>
</body>
</html>