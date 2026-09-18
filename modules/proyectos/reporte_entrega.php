<?php
// modules/proyectos/reporte_entrega.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$proyecto_id = $_GET['id'] ?? 0;

if (!$proyecto_id) {
    redirigir('modules/proyectos/index.php');
}

$stmt = $db->prepare("SELECT p.*, u.nombre_completo as creador, 
                             e.nombre_completo as encargado_nombre
                      FROM proyectos p 
                      LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
                      LEFT JOIN usuarios e ON p.encargado_id = e.id
                      WHERE p.id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    redirigir('modules/proyectos/index.php');
}

// ============================================
// Items SEPARADOS (listos para entregar)
// ============================================
$stmt = $db->prepare("SELECT i.*, 
                             p.nombre as producto_nombre, 
                             c.nombre as categoria_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE i.proyecto_id = ? AND i.estado = 'separado'
                      ORDER BY c.nombre ASC, i.nombre_item ASC");
$stmt->execute([$proyecto_id]);
$items_separados = $stmt->fetchAll();

// ============================================
// Items YA ENTREGADOS/RECIBIDOS (histórico)
// ============================================
$stmt = $db->prepare("SELECT i.*, 
                             p.nombre as producto_nombre, 
                             c.nombre as categoria_nombre,
                             ue.nombre_completo as entregado_por_nombre,
                             ur.nombre_completo as recibido_por_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      LEFT JOIN usuarios ue ON i.entregado_por = ue.id
                      LEFT JOIN usuarios ur ON i.recibido_por = ur.id
                      WHERE i.proyecto_id = ? AND i.estado IN ('entregado', 'recibido')
                      ORDER BY c.nombre ASC, i.nombre_item ASC");
$stmt->execute([$proyecto_id]);
$items_entregados = $stmt->fetchAll();

// ============================================
// Items en STOCK parcial (pendientes por separar)
// ============================================
$stmt = $db->prepare("SELECT i.*, 
                             p.nombre as producto_nombre, 
                             c.nombre as categoria_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE i.proyecto_id = ? 
                        AND i.estado = 'stock' 
                        AND i.cantidad_stock > 0
                      ORDER BY c.nombre ASC, i.nombre_item ASC");
$stmt->execute([$proyecto_id]);
$items_stock = $stmt->fetchAll();

$estados_item = getEstadosItem();

// Calcular totales
$total_para_entregar = 0;
$total_entregados = 0;
$total_recibidos = 0;

foreach ($items_separados as $it) {
    $total_para_entregar += (int)($it['cantidad_stock'] ?: $it['cantidad']);
}
foreach ($items_entregados as $it) {
    if ($it['estado'] === 'entregado') $total_entregados += (int)$it['cantidad_entregada'];
    if ($it['estado'] === 'recibido') $total_recibidos += (int)$it['cantidad_entregada'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_SESSION['idioma'] == 'pt' ? 'Comprovante de Entrega' : 'Comprobante de Entrega'; ?> - <?php echo htmlspecialchars($proyecto['nombre']); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/reportes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="reporte-acciones no-print">
            <a href="<?php echo url('modules/proyectos/reporte.php?id=' . $proyecto_id); ?>" class="btn-secondary">
                ← <?php echo traducir('Volver'); ?>
            </a>
            <a href="<?php echo url('modules/proyectos/ver.php?id=' . $proyecto_id); ?>" class="btn-secondary">
                <?php echo traducir('Ver Proyecto'); ?>
            </a>
            <button type="button" class="btn-primary" onclick="window.print()">
                🖨 <?php echo traducir('Imprimir'); ?>
            </button>
        </div>
        
        <div class="reporte-container">
            <!-- ===== Encabezado tipo acta ===== -->
            <div class="hoja-entrega-header">
                <h1><?php echo $_SESSION['idioma'] == 'pt' ? 'COMPROVANTE DE ENTREGA DE MATERIAIS' : 'COMPROBANTE DE ENTREGA DE MATERIALES'; ?></h1>
                <p><?php echo $_SESSION['idioma'] == 'pt' ? 'Documento de recebimento e conformidade' : 'Documento de recepción y conformidad'; ?></p>
            </div>
            
            <!-- ===== Info del proyecto ===== -->
            <div class="hoja-entrega-info">
                <div>
                    <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Projeto' : 'Proyecto'; ?>:</strong>
                    <span><?php echo htmlspecialchars($proyecto['nombre']); ?></span>
                </div>
                <div>
                    <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Ordem de Compra' : 'Orden de Compra'; ?>:</strong>
                    <span><?php echo htmlspecialchars($proyecto['orden_compra'] ?? '-'); ?></span>
                </div>
                <div>
                    <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Data de Início' : 'Fecha Inicio'; ?>:</strong>
                    <span><?php echo formatearFecha($proyecto['fecha_inicio']); ?></span>
                </div>
                <div>
                    <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Data de Término' : 'Fecha Fin'; ?>:</strong>
                    <span><?php echo formatearFecha($proyecto['fecha_fin']); ?></span>
                </div>
                <div>
                    <strong><?php echo traducir('Encargado'); ?>:</strong>
                    <span><?php echo htmlspecialchars($proyecto['encargado_nombre'] ?? '-'); ?></span>
                </div>
                <div>
                    <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Emitido em' : 'Emitido el'; ?>:</strong>
                    <span><?php echo date('d/m/Y H:i'); ?></span>
                </div>
            </div>
            
            <!-- ===== Sección 1: Materiales listos para entregar (SEPARADO) ===== -->
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? '1. MATERIAIS PRONTOS PARA ENTREGA' : '1. MATERIALES LISTOS PARA ENTREGA'; ?></h2>
                <p style="font-size:0.85rem; color:#7f8c8d; margin-bottom:1rem;">
                    <?php echo $_SESSION['idioma'] == 'pt'
                        ? 'Itens separados no almoxarifado aguardando entrega ao operador.'
                        : 'Items separados en el almacén esperando entrega al operador.'; ?>
                </p>
                
                <?php if (empty($items_separados)): ?>
                    <p style="text-align:center; padding:1.5rem; color:#999; background:#f8f9fa; border-radius:4px;">
                        <?php echo traducir('No hay items separados para entregar'); ?>
                    </p>
                <?php else: ?>
                    <table class="tabla-reporte">
                        <thead>
                            <tr>
                                <th style="width:4%;" class="text-center">#</th>
                                <th style="width:28%;"><?php echo traducir('Nombre'); ?></th>
                                <th style="width:14%;"><?php echo traducir('Categorias'); ?></th>
                                <th style="width:10%;" class="text-center"><?php echo traducir('Cantidad'); ?></th>
                                <th style="width:10%;" class="text-center"><?php echo traducir('Unidad'); ?></th>
                                <th style="width:14%;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Fornecedor' : 'Proveedor'; ?></th>
                                <th style="width:10%;" class="text-center"><?php echo traducir('Estado'); ?></th>
                                <th style="width:10%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Recebido' : 'Recibido'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($items_separados as $item): 
                                $cantidad_entregar = $item['cantidad_stock'] ?: $item['cantidad'];
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['nombre_item']); ?></strong>
                                        <?php if (!empty($item['especificaciones'])): ?>
                                            <br><small style="color:#7f8c8d;"><?php echo htmlspecialchars($item['especificaciones']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['categoria_nombre'] ?? '-'); ?></td>
                                    <td class="text-center"><strong><?php echo $cantidad_entregar; ?></strong></td>
                                    <td class="text-center"><?php echo htmlspecialchars(getUnidadLabel($item['unidad_medida'])); ?></td>
                                    <td><?php echo htmlspecialchars($item['proveedor'] ?? '-'); ?></td>
                                    <td class="text-center">
                                        <span class="estado-badge estado-<?php echo $item['estado']; ?>" style="font-size:0.72rem;">
                                            <?php echo $estados_item[$item['estado']] ?? $item['estado']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="casilla-check"></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f0f2f5; font-weight:bold;">
                                <td colspan="3" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'TOTAL' : 'TOTAL'; ?>:</td>
                                <td class="text-center"><?php echo $total_para_entregar; ?></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- ===== Sección 2: Materiales en stock parcial ===== -->
            <?php if (!empty($items_stock)): ?>
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? '2. ESTOQUE PARCIAL (INFORMATIVO)' : '2. STOCK PARCIAL (INFORMATIVO)'; ?></h2>
                <p style="font-size:0.85rem; color:#7f8c8d; margin-bottom:1rem;">
                    <?php echo $_SESSION['idioma'] == 'pt'
                        ? 'Itens com estoque parcial aguardando separação.'
                        : 'Items con stock parcial esperando ser separados.'; ?>
                </p>
                
                <table class="tabla-reporte">
                    <thead>
                        <tr>
                            <th style="width:5%;" class="text-center">#</th>
                            <th style="width:35%;"><?php echo traducir('Nombre'); ?></th>
                            <th style="width:12%;" class="text-center"><?php echo traducir('Cantidad'); ?></th>
                            <th style="width:12%;" class="text-center"><?php echo traducir('Disponible en stock'); ?></th>
                            <th style="width:12%;" class="text-center"><?php echo traducir('Falta por comprar'); ?></th>
                            <th style="width:12%;" class="text-center"><?php echo traducir('Unidad'); ?></th>
                            <th style="width:12%;" class="text-center"><?php echo traducir('Fecha Requerida'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($items_stock as $item): 
                            $faltante = (int)$item['cantidad'] - (int)$item['cantidad_stock'];
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($item['nombre_item']); ?></td>
                                <td class="text-center"><?php echo $item['cantidad']; ?></td>
                                <td class="text-center" style="color:#27ae60; font-weight:bold;"><?php echo $item['cantidad_stock']; ?></td>
                                <td class="text-center" style="color:#e74c3c; font-weight:bold;"><?php echo $faltante > 0 ? $faltante : '-'; ?></td>
                                <td class="text-center"><?php echo htmlspecialchars(getUnidadLabel($item['unidad_medida'])); ?></td>
                                <td class="text-center"><?php echo formatearFecha($item['fecha_requerida']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- ===== Sección 3: Materiales ya entregados (histórico) ===== -->
            <?php if (!empty($items_entregados)): ?>
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? '3. HISTÓRICO DE ENTREGAS' : '3. HISTÓRICO DE ENTREGAS'; ?></h2>
                
                <table class="tabla-reporte">
                    <thead>
                        <tr>
                            <th style="width:4%;" class="text-center">#</th>
                            <th style="width:26%;"><?php echo traducir('Nombre'); ?></th>
                            <th style="width:12%;"><?php echo traducir('Categorias'); ?></th>
                            <th style="width:10%;" class="text-center"><?php echo traducir('Cantidad entregada'); ?></th>
                            <th style="width:10%;" class="text-center"><?php echo traducir('Unidad'); ?></th>
                            <th style="width:12%;" class="text-center"><?php echo traducir('Estado'); ?></th>
                            <th style="width:13%;"><?php echo traducir('Entregado por'); ?></th>
                            <th style="width:13%;"><?php echo traducir('Recibido por'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($items_entregados as $item): ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['nombre_item']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($item['categoria_nombre'] ?? '-'); ?></td>
                                <td class="text-center"><strong><?php echo $item['cantidad_entregada'] ?: $item['cantidad']; ?></strong></td>
                                <td class="text-center"><?php echo htmlspecialchars(getUnidadLabel($item['unidad_medida'])); ?></td>
                                <td class="text-center">
                                    <span class="estado-badge estado-<?php echo $item['estado']; ?>" style="font-size:0.72rem;">
                                        <?php echo $estados_item[$item['estado']] ?? $item['estado']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['entregado_por_nombre'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($item['recibido_por_nombre'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Resumen de entregas y recepciones -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem;">
                    <div style="background:#e8f5e9; padding:0.75rem 1rem; border-radius:6px; border-left:4px solid #27ae60;">
                        <div style="font-size:0.72rem; color:#2e7d32; font-weight:700; text-transform:uppercase;">
                            <?php echo $_SESSION['idioma'] == 'pt' ? 'Entregues' : 'Entregados'; ?>
                        </div>
                        <div style="font-size:1.3rem; font-weight:bold; color:#1b5e20;">
                            <?php echo $total_entregados; ?>
                        </div>
                        <div style="font-size:0.75rem; color:#555;">
                            <?php echo $_SESSION['idioma'] == 'pt' ? 'aguardando confirmação' : 'esperando confirmación'; ?>
                        </div>
                    </div>
                    <div style="background:#c8e6c9; padding:0.75rem 1rem; border-radius:6px; border-left:4px solid #1b5e20;">
                        <div style="font-size:0.72rem; color:#1b5e20; font-weight:700; text-transform:uppercase;">
                            <?php echo $_SESSION['idioma'] == 'pt' ? 'Recebidos' : 'Recibidos'; ?>
                        </div>
                        <div style="font-size:1.3rem; font-weight:bold; color:#1b5e20;">
                            <?php echo $total_recibidos; ?>
                        </div>
                        <div style="font-size:0.75rem; color:#555;">
                            <?php echo $_SESSION['idioma'] == 'pt' ? 'confirmados pelo operador' : 'confirmados por el operador'; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ===== Declaración ===== -->
            <div class="reporte-nota">
                <?php echo $_SESSION['idioma'] == 'pt'
                    ? 'Declaro ter recebido os materiais listados acima em perfeito estado de conservação e conformidade com as especificações do projeto.'
                    : 'Declaro haber recibido los materiales listados arriba en perfecto estado de conservación y conformidad con las especificaciones del proyecto.'; ?>
            </div>
            
            <!-- ===== Firmas ===== -->
            <div class="bloque-firmas">
                <div class="firma-box">
                    <div class="firma-linea">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Responsável pela Entrega' : 'Responsable de la Entrega'; ?>
                    </div>
                    <div class="firma-cargo">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Almoxarifado' : 'Almacén'; ?>
                    </div>
                </div>
                <div class="firma-box">
                    <div class="firma-linea">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Responsável pelo Recebimento' : 'Responsable de la Recepción'; ?>
                    </div>
                    <div class="firma-cargo">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Operador do Projeto' : 'Operador del Proyecto'; ?>
                    </div>
                </div>
            </div>
            
            <div class="bloque-firmas" style="margin-top:2rem;">
                <div class="firma-box">
                    <div class="firma-linea">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Nome e Documento' : 'Nombre y Documento'; ?>
                    </div>
                </div>
                <div class="firma-box">
                    <div class="firma-linea">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Data do Recebimento' : 'Fecha de Recepción'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>