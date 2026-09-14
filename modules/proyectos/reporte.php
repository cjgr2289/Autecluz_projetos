<?php
// modules/proyectos/reporte.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$proyecto_id = $_GET['id'] ?? 0;

if (!$proyecto_id) {
    header('Location: index.php');
    exit();
}

// Datos del proyecto
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

// Items ordenados por estado y luego por fecha
$orden_estados = getOrdenEstadosItem();
$orden_sql = "FIELD(i.estado, '" . implode("','", $orden_estados) . "')";

$stmt = $db->prepare("SELECT i.*, 
                             p.nombre as producto_nombre, 
                             c.nombre as categoria_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE i.proyecto_id = ? 
                      ORDER BY $orden_sql, i.fecha_requerida ASC, i.nombre_item ASC");
$stmt->execute([$proyecto_id]);
$items = $stmt->fetchAll();

$estadisticas = calcularEstadisticasProyecto($items);
$estados_item = getEstadosItem();
$estados_proyecto = getEstadosProyecto();

// Agrupar por estado
$items_por_estado = [];
foreach ($items as $item) {
    $items_por_estado[$item['estado']][] = $item;
}

// Ordenar grupos según el orden preferido
$items_por_estado_ordenados = [];
foreach ($orden_estados as $estado) {
    if (isset($items_por_estado[$estado])) {
        $items_por_estado_ordenados[$estado] = $items_por_estado[$estado];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo traducir('Reporte'); ?> - <?php echo htmlspecialchars($proyecto['nombre']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/badges.css">
    <link rel="stylesheet" href="../../assets/css/reportes.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="reporte-acciones">
            <a href="ver.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
            <a href="reporte_entrega.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">
                📦 <?php echo $_SESSION['idioma'] == 'pt' ? 'Relatório de Entrega' : 'Reporte de Entrega'; ?>
            </a>
            <a href="reporte_costos.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">
                💰 <?php echo $_SESSION['idioma'] == 'pt' ? 'Relatório de Custos' : 'Reporte de Costos'; ?>
            </a>
            <button type="button" class="btn-primary" onclick="window.print()">
                🖨 <?php echo $_SESSION['idioma'] == 'pt' ? 'Imprimir' : 'Imprimir'; ?>
            </button>
        </div>
        
        <div class="reporte-container">
            <?php renderEncabezadoReporte(
                $_SESSION['idioma'] == 'pt' ? 'Relatório de Itens por Status' : 'Reporte de Items por Estado',
                $_SESSION['idioma'] == 'pt' ? 'Lista completa de itens ordenados por status' : 'Lista completa de items ordenados por estado',
                $proyecto
            ); ?>
            
            <!-- Info del proyecto -->
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? 'Informações do Projeto' : 'Información del Proyecto'; ?></h2>
                <table class="tabla-reporte" style="font-size:0.85rem;">
                    <tbody>
                        <tr>
                            <td style="width:20%;"><strong><?php echo traducir('Estado'); ?>:</strong></td>
                            <td><?php echo $estados_proyecto[$proyecto['estado']] ?? $proyecto['estado']; ?></td>
                            <td style="width:20%;"><strong><?php echo traducir('Creado por'); ?>:</strong></td>
                            <td><?php echo htmlspecialchars($proyecto['creador'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo traducir('Fecha Inicio'); ?>:</strong></td>
                            <td><?php echo formatearFecha($proyecto['fecha_inicio']); ?></td>
                            <td><strong><?php echo traducir('Fecha Fin'); ?>:</strong></td>
                            <td><?php echo formatearFecha($proyecto['fecha_fin']); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo traducir('Fecha Aprobación'); ?>:</strong></td>
                            <td><?php echo formatearFecha($proyecto['fecha_aprobacion']); ?></td>
                            <td><strong><?php echo traducir('Orden de Compra'); ?>:</strong></td>
                            <td><?php echo htmlspecialchars($proyecto['orden_compra'] ?? '-'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Resumen por estado -->
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? 'Resumo por Status' : 'Resumen por Estado'; ?></h2>
                <div class="resumen-estado">
                    <?php foreach ($items_por_estado_ordenados as $estado => $items_grupo): 
                        $stats = $estadisticas['por_estado'][$estado] ?? ['items' => 0, 'cantidad' => 0, 'costo' => 0];
                    ?>
                        <div class="resumen-card">
                            <div class="resumen-label"><?php echo $estados_item[$estado] ?? $estado; ?></div>
                            <div class="resumen-valor"><?php echo $stats['items']; ?> items</div>
                            <div class="resumen-detalle">
                                <?php echo $stats['cantidad']; ?> 
                                <?php echo $_SESSION['idioma'] == 'pt' ? 'unidades' : 'unidades'; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Items agrupados por estado -->
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? 'Itens por Status' : 'Items por Estado'; ?></h2>
                
                <?php if (empty($items)): ?>
                    <p style="text-align:center; padding:2rem; color:#999;">
                        <?php echo traducir('No hay items en este proyecto'); ?>
                    </p>
                <?php else: ?>
                    <?php foreach ($items_por_estado_ordenados as $estado => $items_grupo): ?>
                        <h3 style="font-size:0.95rem; margin:1.25rem 0 0.5rem 0; padding:0.4rem 0.75rem; background:#eef2f5; border-left:4px solid #2c3e50; color:#2c3e50;">
                            <?php echo $estados_item[$estado] ?? $estado; ?>
                            <span style="font-weight:normal; color:#7f8c8d; font-size:0.8rem;">
                                (<?php echo count($items_grupo); ?> 
                                <?php echo count($items_grupo) === 1 
                                    ? ($_SESSION['idioma'] == 'pt' ? 'item' : 'item')
                                    : ($_SESSION['idioma'] == 'pt' ? 'itens' : 'items'); ?>)
                            </span>
                        </h3>
                        
                        <table class="tabla-reporte">
                            <thead>
                                <tr>
                                    <th style="width:30%;"><?php echo traducir('Nombre'); ?></th>
                                    <th style="width:15%;"><?php echo traducir('Categorias'); ?></th>
                                    <th class="text-center" style="width:8%;"><?php echo traducir('Cantidad'); ?></th>
                                    <th class="text-center" style="width:10%;"><?php echo traducir('Unidad'); ?></th>
                                    <th class="text-center" style="width:12%;"><?php echo traducir('Fecha Requerida'); ?></th>
                                    <?php if ($estadisticas['items_con_costo'] > 0): ?>
                                    <th class="text-right" style="width:12%;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo' : 'Costo'; ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items_grupo as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['nombre_item']); ?></strong>
                                            <?php if (!empty($item['especificaciones'])): ?>
                                                <br><small style="color:#7f8c8d;"><?php echo htmlspecialchars($item['especificaciones']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['categoria_nombre'] ?? '-'); ?></td>
                                        <td class="text-center"><?php echo $item['cantidad']; ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars(getUnidadLabel($item['unidad_medida'])); ?></td>
                                        <td class="text-center"><?php echo formatearFecha($item['fecha_requerida']); ?></td>
                                        <?php if ($estadisticas['items_con_costo'] > 0): ?>
                                        <td class="text-right">
                                            <?php if ($item['costo_unitario']): ?>
                                                <?php echo formatearMoneda($item['costo_unitario'] * $item['cantidad'], $item['moneda']); ?>
                                            <?php else: ?>
                                                <span style="color:#ccc;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="reporte-nota">
                <?php echo $_SESSION['idioma'] == 'pt' 
                    ? 'Este relatório foi gerado automaticamente pelo Sistema de Projetos. Documento sem valor fiscal.'
                    : 'Este reporte fue generado automáticamente por el Sistema de Proyectos. Documento sin valor fiscal.'; ?>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>