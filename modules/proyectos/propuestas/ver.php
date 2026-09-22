<?php
// modules/proyectos/propuestas/ver.php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

$propuesta = obtenerPropuestaCompleta($db, $id);

if (!$propuesta) {
    redirigir('modules/proyectos/index.php');
}

$proyecto_id = $propuesta['proyecto_id'];
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($propuesta['numero']); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/propuestas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../../includes/header.php'; ?>
    
    <div class="container">
        <div class="reporte-acciones no-print">
            <a href="index.php?proyecto=<?php echo $proyecto_id; ?>" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
            <a href="editar.php?id=<?php echo $id; ?>" class="btn-secondary">✎ <?php echo traducir('Editar'); ?></a>
            <a href="imprimir.php?id=<?php echo $id; ?>" target="_blank" class="btn-primary">
                🖨 <?php echo $_SESSION['idioma'] == 'pt' ? 'Imprimir' : 'Imprimir'; ?>
            </a>
        </div>
        
        <div class="propuesta-container">
            <?php 
            $estados = [
                'borrador' => ['label' => 'Borrador', 'color' => '#95a5a6'],
                'enviada'  => ['label' => 'Enviada', 'color' => '#3498db'],
                'aprobada' => ['label' => 'Aprobada', 'color' => '#27ae60'],
                'rechazada'=> ['label' => 'Rechazada', 'color' => '#e74c3c'],
            ];
            $e = $estados[$propuesta['estado']] ?? $estados['borrador'];
            
            renderEncabezadoAutecluz('PROPUESTA ECONÓMICA', $propuesta['numero'], formatearFecha($propuesta['fecha_creacion']));
            ?>
            
            <!-- Estado -->
            <div style="text-align:right; margin-bottom:1rem;">
                <span class="badge-estado" style="background:<?php echo $e['color']; ?>; font-size:0.85rem; padding:0.35rem 0.9rem;">
                    <?php echo $e['label']; ?>
                </span>
            </div>
            
            <!-- Datos del proyecto -->
            <div class="propuesta-info-grid">
                <div class="propuesta-info-card">
                    <div class="propuesta-info-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Projeto' : 'Proyecto'; ?></div>
                    <div class="propuesta-info-value"><?php echo htmlspecialchars($propuesta['proyecto_nombre']); ?></div>
                </div>
                <?php if ($propuesta['orden_compra']): ?>
                <div class="propuesta-info-card">
                    <div class="propuesta-info-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Ordem de Compra' : 'Orden de Compra'; ?></div>
                    <div class="propuesta-info-value"><?php echo htmlspecialchars($propuesta['orden_compra']); ?></div>
                </div>
                <?php endif; ?>
                <div class="propuesta-info-card">
                    <div class="propuesta-info-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Validade' : 'Validez'; ?></div>
                    <div class="propuesta-info-value"><?php echo $propuesta['validez_dias']; ?> <?php echo $_SESSION['idioma'] == 'pt' ? 'dias' : 'días'; ?></div>
                </div>
                <div class="propuesta-info-card">
                    <div class="propuesta-info-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Elaborado por' : 'Elaborado por'; ?></div>
                    <div class="propuesta-info-value"><?php echo htmlspecialchars($propuesta['creador'] ?? '—'); ?></div>
                </div>
            </div>
            
            <?php if (!empty($propuesta['descripcion'])): ?>
            <div class="propuesta-descripcion">
                <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Descrição' : 'Descripción'; ?>:</strong>
                <?php echo nl2br(htmlspecialchars($propuesta['descripcion'])); ?>
            </div>
            <?php endif; ?>
            
            <!-- Materiales -->
            <h2 class="propuesta-seccion-titulo">📦 <?php echo $_SESSION['idioma'] == 'pt' ? 'Materiais' : 'Materiales'; ?></h2>
            <table class="propuesta-tabla">
                <thead>
                    <tr>
                        <th style="width:4%;" class="text-center">#</th>
                        <th style="width:34%;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Descrição' : 'Descripción'; ?></th>
                        <th style="width:10%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Unidade' : 'Unidad'; ?></th>
                        <th style="width:8%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Qtd.' : 'Cant.'; ?></th>
                        <th style="width:11%;" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo' : 'Costo'; ?></th>
                        <th style="width:9%;" class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Lucro' : 'Lucro'; ?></th>
                        <th style="width:12%;" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'P. Venda' : 'P. Venta'; ?></th>
                        <th style="width:12%;" class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal' : 'Subtotal'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($propuesta['items'] as $it): ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($it['nombre_item']); ?></strong>
                                <?php if ($it['categoria']): ?>
                                    <br><small style="color:#95a5a6;"><?php echo htmlspecialchars($it['categoria']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo htmlspecialchars(getUnidadLabel($it['unidad_medida'])); ?></td>
                            <td class="text-center"><?php echo (float)$it['cantidad']; ?></td>
                            <td class="text-right" style="color:#7f8c8d;">
                                <?php echo formatearMoneda($it['costo_unitario'], $propuesta['moneda']); ?>
                            </td>
                            <td class="text-center" style="color:#e67e22;">
                                <?php echo number_format((float)$it['porcentaje_lucro'], 1, ',', '.'); ?>%
                            </td>
                            <td class="text-right">
                                <?php echo formatearMoneda($it['precio_venta_unitario'], $propuesta['moneda']); ?>
                            </td>
                            <td class="text-right">
                                <strong><?php echo formatearMoneda($it['subtotal'], $propuesta['moneda']); ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#f0f2f5; font-weight:bold;">
                        <td colspan="7" class="text-right" style="padding:0.75rem;">
                            <?php echo $_SESSION['idioma'] == 'pt' ? 'SUBTOTAL MATERIAIS' : 'SUBTOTAL MATERIALES'; ?>:
                        </td>
                        <td class="text-right" style="padding:0.75rem; color:#27ae60;">
                            <?php echo formatearMoneda($propuesta['subtotal_materiales'], $propuesta['moneda']); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
            
            <!-- Mano de obra -->
            <h2 class="propuesta-seccion-titulo">👷 <?php echo $_SESSION['idioma'] == 'pt' ? 'Mão de Obra' : 'Mano de Obra'; ?></h2>
            <table class="propuesta-tabla">
                <tbody>
                    <tr>
                        <td style="width:60%;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Modo' : 'Modo'; ?>:</td>
                        <td>
                            <strong>
                                <?php echo $propuesta['modo_mano_obra'] === 'horas' 
                                    ? ($_SESSION['idioma'] == 'pt' ? 'Por Horas' : 'Por Horas')
                                    : ($_SESSION['idioma'] == 'pt' ? 'Por Días' : 'Por Días'); ?>
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $_SESSION['idioma'] == 'pt' ? 'Cantidade' : 'Cantidad'; ?>:</td>
                        <td>
                            <?php echo (float)$propuesta['cantidad_tiempo']; ?>
                            <?php echo $propuesta['modo_mano_obra'] === 'horas' 
                                ? ($_SESSION['idioma'] == 'pt' ? 'horas' : 'horas')
                                : ($_SESSION['idioma'] == 'pt' ? 'días' : 'días'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $_SESSION['idioma'] == 'pt' ? 'Pessoas' : 'Personas'; ?>:</td>
                        <td><?php echo $propuesta['personas']; ?></td>
                    </tr>
                    <tr>
                        <td><?php echo $_SESSION['idioma'] == 'pt' ? 'Valor Unitário' : 'Valor Unitario'; ?>:</td>
                        <td>
                            <?php echo formatearMoneda($propuesta['valor_unitario'], $propuesta['moneda']); ?>
                            / <?php echo $propuesta['modo_mano_obra'] === 'horas' ? 'hora' : 'día'; ?>
                        </td>
                    </tr>
                    <tr style="background:#f0f2f5; font-weight:bold;">
                        <td><?php echo $_SESSION['idioma'] == 'pt' ? 'SUBTOTAL MÃO DE OBRA' : 'SUBTOTAL MANO DE OBRA'; ?>:</td>
                        <td style="color:#27ae60; font-size:1.05rem;">
                            <?php echo formatearMoneda($propuesta['subtotal_mano_obra'], $propuesta['moneda']); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Resumen -->
            <h2 class="propuesta-seccion-titulo">💰 <?php echo $_SESSION['idioma'] == 'pt' ? 'Resumo Financeiro' : 'Resumen Financiero'; ?></h2>
            <div class="propuesta-resumen">
                <div class="propuesta-resumen-linea">
                    <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal Materiales' : 'Subtotal Materiales'; ?>:</span>
                    <span><?php echo formatearMoneda($propuesta['subtotal_materiales'], $propuesta['moneda']); ?></span>
                </div>
                <div class="propuesta-resumen-linea">
                    <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal Mano de Obra' : 'Subtotal Mano de Obra'; ?>:</span>
                    <span><?php echo formatearMoneda($propuesta['subtotal_mano_obra'], $propuesta['moneda']); ?></span>
                </div>
                <div class="propuesta-resumen-linea subtotal">
                    <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Subtotal General' : 'Subtotal General'; ?>:</span>
                    <span><?php echo formatearMoneda($propuesta['subtotal_general'], $propuesta['moneda']); ?></span>
                </div>
                <?php if ((float)$propuesta['descuento_porcentaje'] > 0): ?>
                <div class="propuesta-resumen-linea descuento">
                    <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Desconto' : 'Descuento'; ?> (<?php echo number_format((float)$propuesta['descuento_porcentaje'], 2, ',', '.'); ?>%):</span>
                    <span>- <?php echo formatearMoneda($propuesta['descuento_valor'], $propuesta['moneda']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ((float)$propuesta['impuestos_porcentaje'] > 0): ?>
                <div class="propuesta-resumen-linea impuestos">
                    <span><?php echo $_SESSION['idioma'] == 'pt' ? 'Impostos' : 'Impuestos'; ?> (<?php echo number_format((float)$propuesta['impuestos_porcentaje'], 2, ',', '.'); ?>%):</span>
                    <span>+ <?php echo formatearMoneda($propuesta['impuestos_valor'], $propuesta['moneda']); ?></span>
                </div>
                <?php endif; ?>
                <div class="propuesta-resumen-linea total">
                    <span><?php echo $_SESSION['idioma'] == 'pt' ? 'TOTAL FINAL' : 'TOTAL FINAL'; ?>:</span>
                    <span><?php echo formatearMoneda($propuesta['total_final'], $propuesta['moneda']); ?></span>
                </div>
            </div>
            
            <!-- Condiciones y observaciones -->
            <?php if ($propuesta['condiciones_pago'] || $propuesta['observaciones']): ?>
            <div class="propuesta-condiciones">
                <?php if ($propuesta['condiciones_pago']): ?>
                <div class="propuesta-condicion-bloque">
                    <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Condições de Pagamento' : 'Condiciones de Pago'; ?>:</strong><br>
                    <?php echo htmlspecialchars($propuesta['condiciones_pago']); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($propuesta['observaciones']): ?>
                <div class="propuesta-condicion-bloque">
                    <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Observações' : 'Observaciones'; ?>:</strong><br>
                    <?php echo nl2br(htmlspecialchars($propuesta['observaciones'])); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../../../includes/footer.php'; ?>
</body>
</html>