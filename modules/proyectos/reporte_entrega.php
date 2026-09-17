<?php
// modules/proyectos/reporte_entrega.php
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

// ============================================
// Items ENTREGADOS y RECIBIDOS (materiales ya salidos de almacén)
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
// Items LLEGADOS pero aún no entregados (listos en almacén)
// ============================================
$stmt = $db->prepare("SELECT i.*, 
                             p.nombre as producto_nombre, 
                             c.nombre as categoria_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE i.proyecto_id = ? AND i.estado = 'llego'
                      ORDER BY c.nombre ASC, i.nombre_item ASC");
$stmt->execute([$proyecto_id]);
$items_llegados_sin_entregar = $stmt->fetchAll();

// ============================================
// Items en tránsito (comprado_llegar)
// ============================================
$stmt = $db->prepare("SELECT i.*, 
                             p.nombre as producto_nombre, 
                             c.nombre as categoria_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE i.proyecto_id = ? AND i.estado = 'comprado_llegar'
                      ORDER BY i.nombre_item ASC");
$stmt->execute([$proyecto_id]);
$items_transito = $stmt->fetchAll();

$estados_item = getEstadosItem();

// Calcular totales
$total_entregados = 0;
$total_recibidos = 0;
foreach ($items_entregados as $it) {
    if ($it['estado'] === 'entregado') $total_entregados += (int)$it['cantidad'];
    if ($it['estado'] === 'recibido') $total_recibidos += (int)$it['cantidad'];
}
$total_general = $total_entregados + $total_recibidos;
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
            <a href="reporte.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
            <a href="ver.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">
                <?php echo traducir('Ver Proyecto'); ?>
            </a>
            <button type="button" class="btn-primary" onclick="window.print()">
                🖨 <?php echo $_SESSION['idioma'] == 'pt' ? 'Imprimir' : 'Imprimir'; ?>
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
                    <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Emitido em' : 'Emitido el'; ?>:</strong>
                    <span><?php echo date('d/m/Y H:i'); ?></span>
                </div>
                <div>
                    <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Total Entregue' : 'Total Entregado'; ?>:</strong>
                    <span><?php echo $total_general; ?> <?php echo $_SESSION['idioma'] == 'pt' ? 'unidades' : 'unidades'; ?></span>
                </div>
            </div>
            
            <!-- ===== Sección 1: Materiales entregados y recibidos ===== -->
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? '1. MATERIAIS ENTREGUES' : '1. MATERIALES ENTREGADOS'; ?></h2>
                
                <?php if (empty($items_entregados)): ?>
                    <p style="text-align:center; padding:1.5rem; color:#999; background:#f8f9fa; border-radius:4px;">
                        <?php echo $_SESSION['idioma'] == 'pt' 
                            ? 'Nenhum material foi entregue ainda.'
                            : 'Ningún material ha sido entregado aún.'; ?>
                    </p>
                <?php else: ?>
                    <table class="tabla-reporte">
                        <thead>
                            <tr>
                                <th style="width:4%;" class="text-center">#</th>
                                <th style="width:26%;"><?php echo traducir('Nombre'); ?></th>
                                <th style="width:13%;"><?php echo traducir('Categorias'); ?></th>
                                <th style="width:8%;" class="text-center"><?php echo traducir('Cantidad'); ?></th>
                                <th style="width:9%;" class="text-center"><?php echo traducir('Unidad'); ?></th>
                                <th style="width:14%;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Fornecedor' : 'Proveedor'; ?></th>
                                <th style="width:13%;" class="text-center"><?php echo traducir('Estado'); ?></th>
                                <th style="width:13%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Recebido' : 'Recibido'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($items_entregados as $item): 
                                $es_recibido = ($item['estado'] === 'recibido');
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
                                    <td class="text-center"><strong><?php echo $item['cantidad']; ?></strong></td>
                                    <td class="text-center"><?php echo htmlspecialchars(getUnidadLabel($item['unidad_medida'])); ?></td>
                                    <td><?php echo htmlspecialchars($item['proveedor'] ?? '-'); ?></td>
                                    <td class="text-center">
                                        <span class="estado-badge estado-<?php echo $item['estado']; ?>" style="font-size:0.72rem;">
                                            <?php echo $estados_item[$item['estado']] ?? $item['estado']; ?>
                                        </span>
                                        <?php if ($item['fecha_entrega']): ?>
                                            <br><small style="color:#7f8c8d; font-size:0.68rem;">
                                                <?php echo formatearFecha($item['fecha_entrega']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($es_recibido): ?>
                                            <span style="font-size:1.3rem; color:#27ae60;">✓</span>
                                            <?php if ($item['fecha_recepcion']): ?>
                                                <br><small style="color:#7f8c8d; font-size:0.68rem;">
                                                    <?php echo formatearFecha($item['fecha_recepcion']); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="casilla-check"></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f0f2f5; font-weight:bold;">
                                <td colspan="3" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'TOTAL' : 'TOTAL'; ?>:</td>
                                <td class="text-center"><?php echo $total_general; ?></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
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
                <?php endif; ?>
            </div>
            
            <!-- ===== Sección 2: Listos para entregar ===== -->
            <?php if (!empty($items_llegados_sin_entregar)): ?>
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? '2. MATERIAIS PRONTOS PARA ENTREGA' : '2. MATERIALES LISTOS PARA ENTREGA'; ?></h2>
                <p style="font-size:0.85rem; color:#7f8c8d; margin-bottom:1rem;">
                    <?php echo $_SESSION['idioma'] == 'pt'
                        ? 'Itens que já chegaram ao almoxarifado mas ainda não foram entregues ao operador.'
                        : 'Items que ya llegaron al almacén pero aún no han sido entregados al operador.'; ?>
                </p>
                
                <table class="tabla-reporte">
                    <thead>
                        <tr>
                            <th style="width:5%;" class="text-center">#</th>
                            <th style="width:45%;"><?php echo traducir('Nombre'); ?></th>
                            <th style="width:15%;" class="text-center"><?php echo traducir('Cantidad'); ?></th>
                            <th style="width:15%;" class="text-center"><?php echo traducir('Unidad'); ?></th>
                            <th style="width:20%;" class="text-center"><?php echo traducir('Fecha Requerida'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($items_llegados_sin_entregar as $item): ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($item['nombre_item']); ?></td>
                                <td class="text-center"><?php echo $item['cantidad']; ?></td>
                                <td class="text-center"><?php echo htmlspecialchars(getUnidadLabel($item['unidad_medida'])); ?></td>
                                <td class="text-center"><?php echo formatearFecha($item['fecha_requerida']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- ===== Sección 3: En tránsito ===== -->
            <?php if (!empty($items_transito)): ?>
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? '3. MATERIAIS EM TRÂNSITO (INFORMATIVO)' : '3. MATERIALES EN TRÁNSITO (INFORMATIVO)'; ?></h2>
                
                <table class="tabla-reporte">
                    <thead>
                        <tr>
                            <th style="width:5%;" class="text-center">#</th>
                            <th style="width:45%;"><?php echo traducir('Nombre'); ?></th>
                            <th style="width:15%;" class="text-center"><?php echo traducir('Cantidad'); ?></th>
                            <th style="width:15%;" class="text-center"><?php echo traducir('Unidad'); ?></th>
                            <th style="width:20%;" class="text-center"><?php echo traducir('Fecha Requerida'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($items_transito as $item): ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($item['nombre_item']); ?></td>
                                <td class="text-center"><?php echo $item['cantidad']; ?></td>
                                <td class="text-center"><?php echo htmlspecialchars(getUnidadLabel($item['unidad_medida'])); ?></td>
                                <td class="text-center"><?php echo formatearFecha($item['fecha_requerida']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Setor de Compras / Almoxarifado' : 'Departamento de Compras / Almacén'; ?>
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