<?php
// modules/proyectos/propuestas/imprimir.php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

$propuesta = obtenerPropuestaCompleta($db, $id);

if (!$propuesta) {
    redirigir('modules/proyectos/index.php');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($propuesta['numero']); ?> - Imprimir</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/propuestas.css'); ?>">
    <style>
        body { background: #f0f2f5; padding: 20px; font-family: Arial, sans-serif; }
        .propuesta-container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .no-print { text-align: center; margin-bottom: 20px; }
        
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none; }
            .propuesta-container { box-shadow: none; padding: 15px; max-width: 100%; }
            @page { size: A4; margin: 1cm; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 12px 30px; background: #3498db; color: white; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer;">
            🖨 Imprimir / Guardar como PDF
        </button>
    </div>
    
    <div class="propuesta-container">
        <?php renderEncabezadoAutecluz('PROPUESTA ECONÓMICA', $propuesta['numero'], formatearFecha($propuesta['fecha_creacion'])); ?>
        
        <!-- Datos del cliente/proyecto -->
        <div class="propuesta-info-grid">
            <div class="propuesta-info-card">
                <div class="propuesta-info-label">Proyecto</div>
                <div class="propuesta-info-value"><?php echo htmlspecialchars($propuesta['proyecto_nombre']); ?></div>
            </div>
            <?php if ($propuesta['orden_compra']): ?>
            <div class="propuesta-info-card">
                <div class="propuesta-info-label">Orden de Compra</div>
                <div class="propuesta-info-value"><?php echo htmlspecialchars($propuesta['orden_compra']); ?></div>
            </div>
            <?php endif; ?>
            <div class="propuesta-info-card">
                <div class="propuesta-info-label">Validez</div>
                <div class="propuesta-info-value"><?php echo $propuesta['validez_dias']; ?> días</div>
            </div>
            <div class="propuesta-info-card">
                <div class="propuesta-info-label">Elaborado por</div>
                <div class="propuesta-info-value"><?php echo htmlspecialchars($propuesta['creador'] ?? '—'); ?></div>
            </div>
        </div>
        
        <?php if (!empty($propuesta['descripcion'])): ?>
        <div class="propuesta-descripcion">
            <strong>Descripción:</strong>
            <?php echo nl2br(htmlspecialchars($propuesta['descripcion'])); ?>
        </div>
        <?php endif; ?>
        
        <h2 class="propuesta-seccion-titulo">📦 Materiales</h2>
        <table class="propuesta-tabla">
            <thead>
                <tr>
                    <th class="text-center" style="width:4%;">#</th>
                    <th style="width:38%;">Descripción</th>
                    <th class="text-center" style="width:9%;">Unidad</th>
                    <th class="text-center" style="width:7%;">Cant.</th>
                    <th class="text-right" style="width:14%;">P. Unit.</th>
                    <th class="text-right" style="width:14%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($propuesta['items'] as $it): ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($it['nombre_item']); ?></strong>
                            <?php if ($it['categoria']): ?>
                                <br><small style="color:#7f8c8d;"><?php echo htmlspecialchars($it['categoria']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?php echo htmlspecialchars(getUnidadLabel($it['unidad_medida'])); ?></td>
                        <td class="text-center"><?php echo (float)$it['cantidad']; ?></td>
                        <td class="text-right"><?php echo formatearMoneda($it['precio_venta_unitario'], $propuesta['moneda']); ?></td>
                        <td class="text-right"><strong><?php echo formatearMoneda($it['subtotal'], $propuesta['moneda']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f0f2f5; font-weight:bold;">
                    <td colspan="5" class="text-right">Subtotal Materiales:</td>
                    <td class="text-right"><?php echo formatearMoneda($propuesta['subtotal_materiales'], $propuesta['moneda']); ?></td>
                </tr>
            </tfoot>
        </table>
        
        <h2 class="propuesta-seccion-titulo">👷 Mano de Obra</h2>
        <table class="propuesta-tabla">
            <tbody>
                <tr>
                    <td style="width:60%;">Modo de cálculo:</td>
                    <td><strong><?php echo $propuesta['modo_mano_obra'] === 'horas' ? 'Por Horas' : 'Por Días'; ?></strong></td>
                </tr>
                <tr>
                    <td>Cantidad:</td>
                    <td><?php echo (float)$propuesta['cantidad_tiempo']; ?> <?php echo $propuesta['modo_mano_obra'] === 'horas' ? 'horas' : 'días'; ?></td>
                </tr>
                <tr>
                    <td>Personas:</td>
                    <td><?php echo $propuesta['personas']; ?></td>
                </tr>
                <tr>
                    <td>Valor unitario:</td>
                    <td><?php echo formatearMoneda($propuesta['valor_unitario'], $propuesta['moneda']); ?> / <?php echo $propuesta['modo_mano_obra'] === 'horas' ? 'hora' : 'día'; ?></td>
                </tr>
                <tr style="background:#f0f2f5; font-weight:bold;">
                    <td>Subtotal Mano de Obra:</td>
                    <td><?php echo formatearMoneda($propuesta['subtotal_mano_obra'], $propuesta['moneda']); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h2 class="propuesta-seccion-titulo">💰 Resumen Financiero</h2>
        <div class="propuesta-resumen">
            <div class="propuesta-resumen-linea">
                <span>Subtotal Materiales:</span>
                <span><?php echo formatearMoneda($propuesta['subtotal_materiales'], $propuesta['moneda']); ?></span>
            </div>
            <div class="propuesta-resumen-linea">
                <span>Subtotal Mano de Obra:</span>
                <span><?php echo formatearMoneda($propuesta['subtotal_mano_obra'], $propuesta['moneda']); ?></span>
            </div>
            <div class="propuesta-resumen-linea subtotal">
                <span>Subtotal General:</span>
                <span><?php echo formatearMoneda($propuesta['subtotal_general'], $propuesta['moneda']); ?></span>
            </div>
            <?php if ((float)$propuesta['descuento_porcentaje'] > 0): ?>
            <div class="propuesta-resumen-linea descuento">
                <span>Descuento (<?php echo number_format((float)$propuesta['descuento_porcentaje'], 2, ',', '.'); ?>%):</span>
                <span>- <?php echo formatearMoneda($propuesta['descuento_valor'], $propuesta['moneda']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ((float)$propuesta['impuestos_porcentaje'] > 0): ?>
            <div class="propuesta-resumen-linea impuestos">
                <span>Impuestos (<?php echo number_format((float)$propuesta['impuestos_porcentaje'], 2, ',', '.'); ?>%):</span>
                <span>+ <?php echo formatearMoneda($propuesta['impuestos_valor'], $propuesta['moneda']); ?></span>
            </div>
            <?php endif; ?>
            <div class="propuesta-resumen-linea total">
                <span>TOTAL FINAL:</span>
                <span><?php echo formatearMoneda($propuesta['total_final'], $propuesta['moneda']); ?></span>
            </div>
        </div>
        
        <?php if ($propuesta['condiciones_pago'] || $propuesta['observaciones']): ?>
        <div class="propuesta-condiciones">
            <?php if ($propuesta['condiciones_pago']): ?>
            <div class="propuesta-condicion-bloque">
                <strong>Condiciones de Pago:</strong><br>
                <?php echo htmlspecialchars($propuesta['condiciones_pago']); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($propuesta['observaciones']): ?>
            <div class="propuesta-condicion-bloque">
                <strong>Observaciones:</strong><br>
                <?php echo nl2br(htmlspecialchars($propuesta['observaciones'])); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Firmas -->
        <div class="propuesta-firmas">
            <div class="propuesta-firma-box">
                <div class="propuesta-firma-linea"></div>
                <div class="propuesta-firma-nombre">AUTECLUZ SOLUÇÕES INDUSTRIAIS LTDA</div>
                <div class="propuesta-firma-cargo">Responsable Comercial</div>
            </div>
            <div class="propuesta-firma-box">
                <div class="propuesta-firma-linea"></div>
                <div class="propuesta-firma-nombre">Cliente</div>
                <div class="propuesta-firma-cargo">Aprobación / Aceptación</div>
            </div>
        </div>
        
        <div class="propuesta-footer">
            Propuesta válida por <?php echo $propuesta['validez_dias']; ?> días a partir de la fecha de emisión.
            Documento generado el <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>
</body>
</html>