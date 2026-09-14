<?php
/**
 * Plantilla: Resumen semanal de items pendientes
 * Variables: $idioma, $items_estancados, $items_proximos, $items_vencidos, $fecha
 */

$t = function($es, $pt) use ($idioma) {
    return $idioma === 'pt' ? $pt : $es;
};

$estados_item = [
    'es' => ['solicitado'=>'Solicitado','pendiente'=>'Pendiente','stock'=>'En Stock','cotacion'=>'Cotación',
             'orçado'=>'Orçado','pendiente_pago'=>'Pendiente por Pago','comprado_llegar'=>'Comprado por Llegar','llego'=>'Llegó'],
    'pt' => ['solicitado'=>'Solicitado','pendiente'=>'Pendente','stock'=>'Em Estoque','cotacion'=>'Cotação',
             'orçado'=>'Orçado','pendiente_pago'=>'Pendente de Pagamento','comprado_llegar'=>'Comprado a Chegar','llego'=>'Chegou'],
];
$label_estado = function($cod) use ($idioma, $estados_item) {
    return $estados_item[$idioma][$cod] ?? $cod;
};

$total_total = count($items_estancados) + count($items_proximos) + count($items_vencidos);
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t('Resumen semanal', 'Resumo semanal'); ?></title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background:#f5f6fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6fa; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="700" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    
                    <tr>
                        <td style="background:#2c3e50; padding: 24px 30px; color:#ffffff;">
                            <h1 style="margin:0; font-size: 20px;">📊 <?php echo $t('Resumen semanal', 'Resumo semanal'); ?> - <?php echo $fecha; ?></h1>
                            <p style="margin:8px 0 0 0; font-size: 13px; opacity: 0.85;">
                                <?php echo $t(
                                    "Se encontraron $total_total items que requieren atención.",
                                    "Foram encontrados $total_total itens que requerem atenção."
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- ===== VENCIDOS ===== -->
                    <?php if (!empty($items_vencidos)): ?>
                    <tr>
                        <td style="padding: 25px 30px 10px 30px;">
                            <h2 style="margin:0 0 12px 0; font-size: 16px; color:#c0392b; padding-bottom:6px; border-bottom: 2px solid #c0392b;">
                                🚨 <?php echo $t('Items VENCIDOS', 'Itens VENCIDOS'); ?> (<?php echo count($items_vencidos); ?>)
                            </h2>
                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; font-size: 12px;">
                                <thead>
                                    <tr style="background:#fbe9e7;">
                                        <th align="left" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Item', 'Item'); ?></th>
                                        <th align="left" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Proyecto', 'Projeto'); ?></th>
                                        <th align="center" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Fecha', 'Data'); ?></th>
                                        <th align="center" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Atraso', 'Atraso'); ?></th>
                                        <th align="left" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Estado', 'Status'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items_vencidos as $it): ?>
                                    <tr>
                                        <td style="padding:6px; border-bottom:1px solid #eef0f2;"><?php echo htmlspecialchars($it['nombre_item']); ?></td>
                                        <td style="padding:6px; border-bottom:1px solid #eef0f2;"><?php echo htmlspecialchars($it['proyecto_nombre']); ?></td>
                                        <td align="center" style="padding:6px; border-bottom:1px solid #eef0f2;"><?php echo formatearFecha($it['fecha_requerida']); ?></td>
                                        <td align="center" style="padding:6px; border-bottom:1px solid #eef0f2; color:#c0392b; font-weight:bold;"><?php echo $it['dias_atraso']; ?>d</td>
                                        <td style="padding:6px; border-bottom:1px solid #eef0f2;"><?php echo $label_estado($it['estado']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- ===== PRÓXIMOS A VENCER ===== -->
                    <?php if (!empty($items_proximos)): ?>
                    <tr>
                        <td style="padding: 25px 30px 10px 30px;">
                            <h2 style="margin:0 0 12px 0; font-size: 16px; color:#e67e22; padding-bottom:6px; border-bottom: 2px solid #e67e22;">
                                ⏰ <?php echo $t('Próximos a vencer (7 días)', 'Próximos do vencimento (7 dias)'); ?> (<?php echo count($items_proximos); ?>)
                            </h2>
                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; font-size: 12px;">
                                <thead>
                                    <tr style="background:#fff8e1;">
                                        <th align="left" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Item', 'Item'); ?></th>
                                        <th align="left" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Proyecto', 'Projeto'); ?></th>
                                        <th align="center" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Fecha', 'Data'); ?></th>
                                        <th align="left" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Estado', 'Status'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items_proximos as $it): ?>
                                    <tr>
                                        <td style="padding:6px; border-bottom:1px solid #eef0f2;"><?php echo htmlspecialchars($it['nombre_item']); ?></td>
                                        <td style="padding:6px; border-bottom:1px solid #eef0f2;"><?php echo htmlspecialchars($it['proyecto_nombre']); ?></td>
                                        <td align="center" style="padding:6px; border-bottom:1px solid #eef0f2; color:#e67e22; font-weight:bold;"><?php echo formatearFecha($it['fecha_requerida']); ?></td>
                                        <td style="padding:6px; border-bottom:1px solid #eef0f2;"><?php echo $label_estado($it['estado']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- ===== ESTANCADOS ===== -->
                    <?php if (!empty($items_estancados)): ?>
                    <tr>
                        <td style="padding: 25px 30px 10px 30px;">
                            <h2 style="margin:0 0 12px 0; font-size: 16px; color:#7f8c8d; padding-bottom:6px; border-bottom: 2px solid #95a5a6;">
                                🐢 <?php echo $t('Sin cambios en la última semana', 'Sem alterações na última semana'); ?> (<?php echo count($items_estancados); ?>)
                            </h2>
                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; font-size: 12px;">
                                <thead>
                                    <tr style="background:#f0f2f5;">
                                        <th align="left" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Item', 'Item'); ?></th>
                                        <th align="left" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Proyecto', 'Projeto'); ?></th>
                                        <th align="left" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Estado', 'Status'); ?></th>
                                        <th align="center" style="padding:6px; border-bottom:1px solid #dee2e6;"><?php echo $t('Último cambio', 'Última alteração'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items_estancados as $it): ?>
                                    <tr>
                                        <td style="padding:6px; border-bottom:1px solid #eef0f2;"><?php echo htmlspecialchars($it['nombre_item']); ?></td>
                                        <td style="padding:6px; border-bottom:1px solid #eef0f2;"><?php echo htmlspecialchars($it['proyecto_nombre']); ?></td>
                                        <td style="padding:6px; border-bottom:1px solid #eef0f2;"><?php echo $label_estado($it['estado']); ?></td>
                                        <td align="center" style="padding:6px; border-bottom:1px solid #eef0f2; color:#7f8c8d;">
                                            <?php echo !empty($it['ultimo_cambio']) ? formatearFecha($it['ultimo_cambio']) : $t('Nunca', 'Nunca'); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <tr>
                        <td style="padding: 20px 30px; text-align: center;">
                            <a href="<?php 
                                $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                echo $protocolo . '://' . $host . '/sistema_proyectos/modules/proyectos/index.php';
                            ?>" style="display: inline-block; background:#3498db; color:#ffffff; padding: 10px 24px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold;">
                                <?php echo $t('Ver Proyectos', 'Ver Projetos'); ?>
                            </a>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="background:#f8f9fa; padding: 15px 30px; font-size: 12px; color:#95a5a6; text-align: center;">
                            <?php echo $t('Sistema de Proyectos - Resumen semanal automático', 'Sistema de Projetos - Resumo semanal automático'); ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>