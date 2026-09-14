<?php
// modules/proyectos/reporte_costos.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$proyecto_id = $_GET['id'] ?? 0;

if (!$proyecto_id) {
    header('Location: index.php');
    exit();
}

$stmt = $db->prepare("SELECT p.*, u.nombre_completo as creador 
                      FROM proyectos p 
                      LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
                      WHERE p.id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    header('Location: index.php');
    exit();
}

// Items con costo
$stmt = $db->prepare("SELECT i.*, 
                             p.nombre as producto_nombre, 
                             c.nombre as categoria_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE i.proyecto_id = ? 
                      ORDER BY c.nombre ASC, i.nombre_item ASC");
$stmt->execute([$proyecto_id]);
$items = $stmt->fetchAll();

$estadisticas = calcularEstadisticasProyecto($items);
$estados_item = getEstadosItem();
$orden_estados = getOrdenEstadosItem();

// Detectar monedas usadas
$monedas_usadas = [];
foreach ($items as $it) {
    if (!empty($it['costo_unitario'])) {
        $monedas_usadas[$it['moneda'] ?? 'USD'] = true;
    }
}
$monedas_usadas = array_keys($monedas_usadas);
$moneda_principal = !empty($monedas_usadas) ? $monedas_usadas[0] : 'USD';
$multi_moneda = count($monedas_usadas) > 1;
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['idioma'] == 'pt' ? 'Relatório de Custos' : 'Reporte de Costos'; ?> - <?php echo htmlspecialchars($proyecto['nombre']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/reportes.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="reporte-acciones no-print">
            <a href="reporte.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
            <a href="editar_costos.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">
                ✎ <?php echo $_SESSION['idioma'] == 'pt' ? 'Editar Custos' : 'Editar Costos'; ?>
            </a>
            <button type="button" class="btn-primary" onclick="window.print()">
                🖨 <?php echo $_SESSION['idioma'] == 'pt' ? 'Imprimir' : 'Imprimir'; ?>
            </button>
        </div>
        
        <div class="reporte-container">
            <?php renderEncabezadoReporte(
                $_SESSION['idioma'] == 'pt' ? 'Relatório de Custos de Materiais' : 'Reporte de Costos de Materiales',
                $_SESSION['idioma'] == 'pt' ? 'Análise financeira detalhada' : 'Análisis financiero detallado',
                $proyecto
            ); ?>
            
            <!-- ===== Resumen general ===== -->
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? 'Resumo Geral' : 'Resumen General'; ?></h2>
                <div class="resumen-estado">
                    <div class="resumen-card">
                        <div class="resumen-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Total de Itens' : 'Total de Items'; ?></div>
                        <div class="resumen-valor"><?php echo $estadisticas['total_items']; ?></div>
                        <div class="resumen-detalle"><?php echo $estadisticas['total_cantidad']; ?> <?php echo $_SESSION['idioma'] == 'pt' ? 'unidades' : 'unidades'; ?></div>
                    </div>
                    <div class="resumen-card" style="border-left-color:#27ae60;">
                        <div class="resumen-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Com Custo' : 'Con Costo'; ?></div>
                        <div class="resumen-valor"><?php echo $estadisticas['items_con_costo']; ?></div>
                        <div class="resumen-detalle"><?php echo $estadisticas['items_sin_costo']; ?> <?php echo $_SESSION['idioma'] == 'pt' ? 'sem custo' : 'sin costo'; ?></div>
                    </div>
                    <div class="resumen-card" style="border-left-color:#2c3e50; background:#e8f5e9;">
                        <div class="resumen-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo Total' : 'Costo Total'; ?></div>
                        <div class="resumen-valor" style="color:#27ae60;">
                            <?php echo formatearMoneda($estadisticas['total_costo'], $moneda_principal); ?>
                        </div>
                        <?php if ($multi_moneda): ?>
                            <div class="resumen-detalle" style="color:#e74c3c;">
                                ⚠ <?php echo $_SESSION['idioma'] == 'pt' ? 'Múltiplas moedas' : 'Múltiples monedas'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- ===== Costos por estado ===== -->
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? 'Custos por Status' : 'Costos por Estado'; ?></h2>
                <table class="tabla-reporte">
                    <thead>
                        <tr>
                            <th><?php echo traducir('Estado'); ?></th>
                            <th class="text-center">Items</th>
                            <th class="text-center"><?php echo traducir('Cantidad'); ?></th>
                            <th class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo' : 'Costo'; ?></th>
                            <th class="text-right">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_costo = $estadisticas['total_costo'];
                        foreach ($orden_estados as $estado): 
                            if (!isset($estadisticas['por_estado'][$estado])) continue;
                            $datos = $estadisticas['por_estado'][$estado];
                            $porcentaje = $total_costo > 0 ? ($datos['costo'] / $total_costo) * 100 : 0;
                        ?>
                            <tr>
                                <td><strong><?php echo $estados_item[$estado] ?? $estado; ?></strong></td>
                                <td class="text-center"><?php echo $datos['items']; ?></td>
                                <td class="text-center"><?php echo $datos['cantidad']; ?></td>
                                <td class="text-right"><?php echo formatearMoneda($datos['costo'], $moneda_principal); ?></td>
                                <td class="text-right"><?php echo number_format($porcentaje, 1, ',', '.'); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f0f2f5; font-weight:bold;">
                            <td colspan="2" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'TOTAL' : 'TOTAL'; ?>:</td>
                            <td class="text-center"><?php echo $estadisticas['total_cantidad']; ?></td>
                            <td class="text-right"><?php echo formatearMoneda($estadisticas['total_costo'], $moneda_principal); ?></td>
                            <td class="text-right">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- ===== Costos por categoría ===== -->
            <?php if (!empty($estadisticas['por_categoria'])): ?>
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? 'Custos por Categoria' : 'Costos por Categoría'; ?></h2>
                <table class="tabla-reporte">
                    <thead>
                        <tr>
                            <th><?php echo traducir('Categorias'); ?></th>
                            <th class="text-center">Items</th>
                            <th class="text-center"><?php echo traducir('Cantidad'); ?></th>
                            <th class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo' : 'Costo'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        uasort($estadisticas['por_categoria'], fn($a, $b) => $b['costo'] <=> $a['costo']);
                        foreach ($estadisticas['por_categoria'] as $cat => $datos): 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat); ?></td>
                                <td class="text-center"><?php echo $datos['items']; ?></td>
                                <td class="text-center"><?php echo $datos['cantidad']; ?></td>
                                <td class="text-right"><?php echo formatearMoneda($datos['costo'], $moneda_principal); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- ===== Detalle completo ===== -->
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? 'Detalhamento por Item' : 'Detalle por Item'; ?></h2>
                <table class="tabla-reporte">
                    <thead>
                        <tr>
                            <th style="width:25%;"><?php echo traducir('Nombre'); ?></th>
                            <th style="width:12%;"><?php echo traducir('Categorias'); ?></th>
                            <th class="text-center" style="width:8%;"><?php echo traducir('Cantidad'); ?></th>
                            <th class="text-right" style="width:13%;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo Unit.' : 'Costo Unit.'; ?></th>
                            <th class="text-right" style="width:13%;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal' : 'Subtotal'; ?></th>
                            <th style="width:15%;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Fornecedor' : 'Proveedor'; ?></th>
                            <th style="width:14%;"><?php echo traducir('Estado'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal_general = 0;
                        foreach ($items as $item): 
                            $tiene_costo = !empty($item['costo_unitario']);
                            $subtotal = $tiene_costo ? $item['costo_unitario'] * $item['cantidad'] : 0;
                            $subtotal_general += $subtotal;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nombre_item']); ?></td>
                                <td><?php echo htmlspecialchars($item['categoria_nombre'] ?? '-'); ?></td>
                                <td class="text-center"><?php echo $item['cantidad']; ?></td>
                                <td class="text-right">
                                    <?php echo $tiene_costo ? formatearMoneda($item['costo_unitario'], $item['moneda']) : '-'; ?>
                                </td>
                                <td class="text-right">
                                    <strong><?php echo $tiene_costo ? formatearMoneda($subtotal, $item['moneda']) : '-'; ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($item['proveedor'] ?? '-'); ?></td>
                                <td>
                                    <span class="estado-badge estado-<?php echo $item['estado']; ?>" style="font-size:0.72rem;">
                                        <?php echo $estados_item[$item['estado']] ?? $item['estado']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#2c3e50; color:white; font-weight:bold;">
                            <td colspan="4" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'CUSTO TOTAL' : 'COSTO TOTAL'; ?>:</td>
                            <td class="text-right"><?php echo formatearMoneda($estadisticas['total_costo'], $moneda_principal); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="reporte-nota">
                <?php echo $_SESSION['idioma'] == 'pt' 
                    ? 'Os valores apresentados são baseados nas informações registradas no sistema. Documento sem valor fiscal.'
                    : 'Los valores presentados se basan en la información registrada en el sistema. Documento sin valor fiscal.'; ?>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>