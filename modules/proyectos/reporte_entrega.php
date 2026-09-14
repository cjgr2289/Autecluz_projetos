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

// Solo items que YA LLEGARON
$stmt = $db->prepare("SELECT i.*, 
                             p.nombre as producto_nombre, 
                             c.nombre as categoria_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE i.proyecto_id = ? AND i.estado = 'llego'
                      ORDER BY c.nombre ASC, i.nombre_item ASC");
$stmt->execute([$proyecto_id]);
$items_llegados = $stmt->fetchAll();

// Items que están "comprado_llegar" (en tránsito, informativo)
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

$total_entregados = 0;
foreach ($items_llegados as $it) {
    $total_entregados += (int)$it['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['idioma'] == 'pt' ? 'Comprovante de Entrega' : 'Comprobante de Entrega'; ?> - <?php echo htmlspecialchars($proyecto['nombre']); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/reportes.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
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
                    <span><?php echo $total_entregados; ?> <?php echo $_SESSION['idioma'] == 'pt' ? 'unidades' : 'unidades'; ?></span>
                </div>
            </div>
            
            <!-- ===== Sección 1: Materiales entregados ===== -->
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? '1. MATERIAIS ENTREGUES' : '1. MATERIALES ENTREGADOS'; ?></h2>
                
                <?php if (empty($items_llegados)): ?>
                    <p style="text-align:center; padding:1.5rem; color:#999; background:#f8f9fa; border-radius:4px;">
                        <?php echo $_SESSION['idioma'] == 'pt' 
                            ? 'Nenhum material foi entregue ainda.'
                            : 'Ningún material ha sido entregado aún.'; ?>
                    </p>
                <?php else: ?>
                    <table class="tabla-reporte">
                        <thead>
                            <tr>
                                <th style="width:5%;" class="text-center">#</th>
                                <th style="width:30%;"><?php echo traducir('Nombre'); ?></th>
                                <th style="width:15%;"><?php echo traducir('Categorias'); ?></th>
                                <th style="width:10%;" class="text-center"><?php echo traducir('Cantidad'); ?></th>
                                <th style="width:10%;" class="text-center"><?php echo traducir('Unidad'); ?></th>
                                <th style="width:15%;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Fornecedor' : 'Proveedor'; ?></th>
                                <th style="width:15%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Recebido' : 'Recibido'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($items_llegados as $item): ?>
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
                                        <span class="casilla-check"></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f0f2f5; font-weight:bold;">
                                <td colspan="3" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'TOTAL' : 'TOTAL'; ?>:</td>
                                <td class="text-center"><?php echo $total_entregados; ?></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- ===== Sección 2: Pendientes por llegar (informativo) ===== -->
            <?php if (!empty($items_transito)): ?>
            <div class="reporte-seccion">
                <h2><?php echo $_SESSION['idioma'] == 'pt' ? '2. MATERIAIS EM TRÂNSITO (INFORMATIVO)' : '2. MATERIALES EN TRÁNSITO (INFORMATIVO)'; ?></h2>
                
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
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Setor de Compras' : 'Departamento de Compras'; ?>
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