<?php
// modules/proyectos/editar_costos.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['compras', 'directivo', 'gerenciador']) && !esMaster()) {
    redirigir('modules/proyectos/index.php');
}

$db = Database::getInstance()->getConnection();
$proyecto_id = $_GET['id'] ?? 0;

if (!$proyecto_id) {
    redirigir('modules/proyectos/index.php');
}

$stmt = $db->prepare("SELECT * FROM proyectos WHERE id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    redirigir('modules/proyectos/index.php');
}

$propagar_a_catalogo = !empty($_POST['propagar_catalogo']) || true; // Por defecto SÍ propaga

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $items_data = $_POST['items'] ?? [];
    $guardados = 0;
    $propagados = 0;
    
    try {
        $db->beginTransaction();
        
        $stmt_verificar = $db->prepare("SELECT costo_unitario, producto_id FROM items_proyecto WHERE id = ? AND proyecto_id = ?");
        $stmt_update = $db->prepare("UPDATE items_proyecto 
                                     SET costo_unitario=?, moneda=?, proveedor=?, 
                                         numero_factura=?, fecha_compra=? 
                                     WHERE id=? AND proyecto_id=?");
        $stmt_historial = $db->prepare("INSERT INTO historial_items 
                                        (item_id, estado_anterior, estado_nuevo, 
                                         costo_anterior, costo_nuevo, usuario_id, comentario)
                                        VALUES (?, NULL, NULL, ?, ?, ?, ?)");
        $stmt_producto_verif = $db->prepare("SELECT costo_actual FROM productos WHERE id = ?");
        $stmt_producto_update = $db->prepare("UPDATE productos 
                                              SET costo_actual = ?, moneda = ?, fecha_ultimo_costo = CURDATE()
                                              WHERE id = ?");
        
        foreach ($items_data as $item_id => $datos) {
            $costo = !empty($datos['costo_unitario']) ? (float)$datos['costo_unitario'] : null;
            $moneda = $datos['moneda'] ?? 'USD';
            $proveedor = trim($datos['proveedor'] ?? '') ?: null;
            $factura = trim($datos['numero_factura'] ?? '') ?: null;
            $fecha_compra = !empty($datos['fecha_compra']) ? $datos['fecha_compra'] : null;
            
            // Obtener costo anterior del item
            $stmt_verificar->execute([$item_id, $proyecto_id]);
            $row = $stmt_verificar->fetch();
            if (!$row) continue;
            
            $costo_anterior = $row['costo_unitario'];
            $producto_id = $row['producto_id'];
            
            // Actualizar item
            $stmt_update->execute([$costo, $moneda, $proveedor, $factura, $fecha_compra, $item_id, $proyecto_id]);
            
            // Registrar en historial si cambió
            if ($costo_anterior != $costo) {
                $stmt_historial->execute([
                    $item_id,
                    $costo_anterior,
                    $costo,
                    $_SESSION['usuario_id'],
                    'Actualización de costo'
                ]);
            }
            
            // ============================================
            // PROPAGAR COSTO AL CATÁLOGO DE PRODUCTOS
            // ============================================
            if ($producto_id && $costo !== null && $propagar_a_catalogo) {
                // Verificar si el costo del producto es diferente
                $stmt_producto_verif->execute([$producto_id]);
                $prod = $stmt_producto_verif->fetch();
                
                if ($prod && ($prod['costo_actual'] != $costo || $prod['costo_actual'] === null)) {
                    $stmt_producto_update->execute([$costo, $moneda, $producto_id]);
                    $propagados++;
                }
            }
            
            $guardados++;
        }
        
        $db->commit();
        
        $msg = 'guardado';
        if ($propagados > 0) {
            $msg = 'guardado_propagado';
        }
        
        redirigir('modules/proyectos/editar_costos.php?id=' . $proyecto_id . '&mensaje=' . $msg);
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        $error = 'Error al guardar: ' . $e->getMessage();
    }
}

// Listar items
$stmt = $db->prepare("SELECT i.*, 
                             p.nombre as producto_nombre, 
                             p.costo_actual as producto_costo_actual,
                             c.nombre as categoria_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE i.proyecto_id = ? 
                      ORDER BY c.nombre ASC, i.nombre_item ASC");
$stmt->execute([$proyecto_id]);
$items = $stmt->fetchAll();

$estados_item = getEstadosItem();

$monedas = [
    'USD' => 'USD - Dólar',
    'BRL' => 'BRL - Real Brasileño',
    'EUR' => 'EUR - Euro',
    'ARS' => 'ARS - Peso Argentino',
    'PYG' => 'PYG - Guaraní',
    'UYU' => 'UYU - Peso Uruguayo',
];
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['idioma'] == 'pt' ? 'Editar Custos' : 'Editar Costos'; ?> - <?php echo htmlspecialchars($proyecto['nombre']); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
    <style>
        .tabla-costos { font-size: 0.85rem; }
        .tabla-costos th { background: #2c3e50; color:white; padding: 0.5rem; font-size: 0.78rem; }
        .tabla-costos td { padding: 0.35rem 0.4rem; vertical-align: middle; }
        .tabla-costos input[type="number"],
        .tabla-costos input[type="text"],
        .tabla-costos input[type="date"],
        .tabla-costos select {
            width: 100%;
            padding: 0.35rem 0.4rem;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 0.82rem;
            box-sizing: border-box;
        }
        .tabla-costos input[type="number"] { text-align: right; }
        .tabla-costos .col-costo { width: 110px; }
        .tabla-costos .col-moneda { width: 90px; }
        .tabla-costos .col-proveedor { width: 150px; }
        .tabla-costos .col-factura { width: 110px; }
        .tabla-costos .col-fecha { width: 120px; }
        .tabla-costos .col-subtotal { width: 110px; text-align:right; font-weight:bold; color:#27ae60; }
        .costo-producto-badge {
            display: inline-block;
            font-size: 0.68rem;
            padding: 0.1rem 0.35rem;
            border-radius: 8px;
            background: #e3f2fd;
            color: #1565c0;
            margin-left: 0.35rem;
            font-weight: 600;
        }
        .costo-cambiado {
            background: #fff3cd !important;
            border-color: #f39c12 !important;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1><?php echo $_SESSION['idioma'] == 'pt' ? 'Editar Custos' : 'Editar Costos'; ?></h1>
            <div>
                <a href="<?php echo url('modules/proyectos/reporte_costos.php?id=' . $proyecto_id); ?>" class="btn-secondary">
                    ← <?php echo $_SESSION['idioma'] == 'pt' ? 'Ver Relatório' : 'Ver Reporte'; ?>
                </a>
                <a href="<?php echo url('modules/proyectos/ver.php?id=' . $proyecto_id); ?>" class="btn-secondary">
                    <?php echo traducir('Ver Proyecto'); ?>
                </a>
            </div>
        </div>
        
        <div class="info-message">
            <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong>
            <?php if ($proyecto['orden_compra']): ?>
                — O.C.: <?php echo htmlspecialchars($proyecto['orden_compra']); ?>
            <?php endif; ?>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'guardado'): ?>
            <div class="success-message">
                <?php echo $_SESSION['idioma'] == 'pt' ? 'Custos salvos com sucesso!' : '¡Costos guardados exitosamente!'; ?>
            </div>
        <?php elseif (isset($_GET['mensaje']) && $_GET['mensaje'] === 'guardado_propagado'): ?>
            <div class="success-message">
                <?php echo $_SESSION['idioma'] == 'pt' 
                    ? '✓ Custos salvos e atualizados no catálogo de produtos!'
                    : '¡Costos guardados y actualizados en el catálogo de productos!'; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-message" style="background:#e3f2fd; border-color:#bbdefb; color:#0d47a1;">
            <strong>💡 <?php echo $_SESSION['idioma'] == 'pt' ? 'Informação:' : 'Información:'; ?></strong>
            <?php echo $_SESSION['idioma'] == 'pt'
                ? 'Ao alterar o custo de um item, o valor também será atualizado no catálogo de produtos.'
                : 'Al cambiar el costo de un item, el valor también se actualizará en el catálogo de productos.'; ?>
        </div>
        
        <form method="POST" id="form-costos">
            <div class="table-responsive">
                <table class="tabla-costos">
                    <thead>
                        <tr>
                            <th style="min-width:180px;"><?php echo traducir('Nombre'); ?></th>
                            <th class="text-center" style="width:60px;"><?php echo traducir('Cantidad'); ?></th>
                            <th class="col-costo"><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo Unit.' : 'Costo Unit.'; ?></th>
                            <th class="col-moneda"><?php echo $_SESSION['idioma'] == 'pt' ? 'Moeda' : 'Moneda'; ?></th>
                            <th class="col-proveedor"><?php echo $_SESSION['idioma'] == 'pt' ? 'Fornecedor' : 'Proveedor'; ?></th>
                            <th class="col-factura"><?php echo $_SESSION['idioma'] == 'pt' ? 'Nº Fatura' : 'Nº Factura'; ?></th>
                            <th class="col-fecha"><?php echo $_SESSION['idioma'] == 'pt' ? 'Data Compra' : 'Fecha Compra'; ?></th>
                            <th class="col-subtotal"><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal' : 'Subtotal'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="8" class="empty-cell">
                                <?php echo traducir('No hay items en este proyecto'); ?>
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['nombre_item']); ?></strong>
                                    <?php if ($item['producto_id'] && $item['producto_costo_actual'] !== null): ?>
                                        <span class="costo-producto-badge" title="<?php echo $_SESSION['idioma'] == 'pt' ? 'Custo no catálogo' : 'Costo en catálogo'; ?>">
                                            📦 <?php echo formatearMoneda($item['producto_costo_actual'], $item['moneda']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($item['categoria_nombre']): ?>
                                        <br><small style="color:#95a5a6;"><?php echo htmlspecialchars($item['categoria_nombre']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <strong><?php echo $item['cantidad']; ?></strong>
                                    <br><small style="color:#95a5a6;"><?php echo htmlspecialchars(getUnidadLabel($item['unidad_medida'])); ?></small>
                                </td>
                                <td>
                                    <input type="number" 
                                           name="items[<?php echo $item['id']; ?>][costo_unitario]" 
                                           value="<?php echo $item['costo_unitario'] !== null ? number_format((float)$item['costo_unitario'], 2, '.', '') : ''; ?>"
                                           step="0.01" 
                                           min="0"
                                           placeholder="0.00"
                                           data-cantidad="<?php echo $item['cantidad']; ?>"
                                           data-item-id="<?php echo $item['id']; ?>"
                                           data-costo-original="<?php echo $item['costo_unitario'] !== null ? number_format((float)$item['costo_unitario'], 2, '.', '') : ''; ?>"
                                           class="input-costo">
                                </td>
                                <td>
                                    <select name="items[<?php echo $item['id']; ?>][moneda]">
                                        <?php foreach ($monedas as $cod => $lbl): ?>
                                            <option value="<?php echo $cod; ?>" 
                                                <?php echo ($item['moneda'] ?? 'USD') === $cod ? 'selected' : ''; ?>>
                                                <?php echo $cod; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" 
                                           name="items[<?php echo $item['id']; ?>][proveedor]" 
                                           value="<?php echo htmlspecialchars($item['proveedor'] ?? ''); ?>"
                                           placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Nome do fornecedor' : 'Nombre del proveedor'; ?>">
                                </td>
                                <td>
                                    <input type="text" 
                                           name="items[<?php echo $item['id']; ?>][numero_factura]" 
                                           value="<?php echo htmlspecialchars($item['numero_factura'] ?? ''); ?>"
                                           placeholder="FAC-001">
                                </td>
                                <td>
                                    <input type="date" 
                                           name="items[<?php echo $item['id']; ?>][fecha_compra]" 
                                           value="<?php echo $item['fecha_compra'] ?? ''; ?>">
                                </td>
                                <td class="col-subtotal subtotal-live" id="subtotal-<?php echo $item['id']; ?>">
                                    <?php 
                                    $subtotal = $item['costo_unitario'] ? $item['costo_unitario'] * $item['cantidad'] : 0;
                                    echo $item['costo_unitario'] ? formatearMoneda($subtotal, $item['moneda']) : '-';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f0f2f5; font-weight:bold;">
                            <td colspan="7" class="text-right" style="padding: 0.75rem;">
                                <?php echo $_SESSION['idioma'] == 'pt' ? 'TOTAL GERAL' : 'TOTAL GENERAL'; ?>:
                            </td>
                            <td class="text-right" style="padding: 0.75rem; font-size: 1rem; color: #27ae60;" id="total-general">
                                $ 0,00
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div style="margin-top:1.5rem; display:flex; gap:0.75rem;">
                <button type="submit" class="btn-primary">
                    💾 <?php echo traducir('Guardar'); ?>
                </button>
                <a href="<?php echo url('modules/proyectos/reporte_costos.php?id=' . $proyecto_id); ?>" class="btn-secondary">
                    <?php echo traducir('Cancelar'); ?>
                </a>
            </div>
        </form>
    </div>
    
    <script>
    const MONEDAS_SIMBOLOS = {
        'USD': '$', 'BRL': 'R$', 'EUR': '€', 'ARS': '$', 'PYG': '₲', 'UYU': '$U'
    };
    
    function formatearMoneda(valor, moneda) {
        const simbolo = MONEDAS_SIMBOLOS[moneda] || moneda;
        return simbolo + ' ' + valor.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    function recalcularSubtotal(input) {
        const itemId = input.dataset.itemId;
        const cantidad = parseInt(input.dataset.cantidad) || 0;
        const costo = parseFloat(input.value) || 0;
        
        // Marcar como cambiado si difiere del original
        const original = parseFloat(input.dataset.costoOriginal) || 0;
        if (costo !== original) {
            input.classList.add('costo-cambiado');
        } else {
            input.classList.remove('costo-cambiado');
        }
        
        const row = input.closest('tr');
        const selectMoneda = row.querySelector('select[name*="[moneda]"]');
        const moneda = selectMoneda ? selectMoneda.value : 'USD';
        
        const subtotalCelda = document.getElementById('subtotal-' + itemId);
        if (subtotalCelda) {
            subtotalCelda.textContent = costo > 0 ? formatearMoneda(costo * cantidad, moneda) : '-';
        }
        recalcularTotal();
    }
    
    function recalcularTotal() {
        let total = 0;
        document.querySelectorAll('.input-costo').forEach(input => {
            const cantidad = parseInt(input.dataset.cantidad) || 0;
            const costo = parseFloat(input.value) || 0;
            total += costo * cantidad;
        });
        
        const totalEl = document.getElementById('total-general');
        if (totalEl) {
            totalEl.textContent = formatearMoneda(total, 'USD');
        }
    }
    
    document.querySelectorAll('.input-costo').forEach(input => {
        input.addEventListener('input', () => recalcularSubtotal(input));
    });
    
    document.querySelectorAll('select[name*="[moneda]"]').forEach(select => {
        select.addEventListener('change', () => {
            const inputCosto = select.closest('tr').querySelector('.input-costo');
            if (inputCosto) recalcularSubtotal(inputCosto);
        });
    });
    
    recalcularTotal();
    </script>

    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>