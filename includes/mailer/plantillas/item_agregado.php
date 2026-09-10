<?php
/**
 * Plantilla: Notificación de items agregados a un proyecto
 * Variables: $idioma, $proyecto, $items
 */

$t = function($es, $pt) use ($idioma) {
    return $idioma === 'pt' ? $pt : $es;
};

$estados_item = [
    'es' => ['solicitado' => 'Solicitado', 'pendiente' => 'Pendiente', 'stock' => 'En Stock', 'cotacion' => 'Cotación'],
    'pt' => ['solicitado' => 'Solicitado', 'pendiente' => 'Pendente', 'stock' => 'Em Estoque', 'cotacion' => 'Cotação'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t('Nuevos items', 'Novos itens'); ?></title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background:#f5f6fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6fa; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background:#2c3e50; padding: 24px 30px; color:#ffffff;">
                            <h1 style="margin:0; font-size: 20px;">📦 <?php echo $t('Nuevos items agregados', 'Novos itens adicionados'); ?></h1>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="margin:0 0 15px 0; font-size: 15px; color:#333;">
                                <?php echo $t('Se agregaron nuevos items al proyecto:', 'Novos itens foram adicionados ao projeto:'); ?>
                            </p>
                            <h2 style="margin:0 0 20px 0; font-size: 18px; color:#2c3e50;">
                                <?php echo htmlspecialchars($proyecto['nombre']); ?>
                            </h2>
                            
                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; font-size: 14px;">
                                <thead>
                                    <tr style="background:#f0f2f5;">
                                        <th align="left" style="padding:10px; border-bottom:2px solid #dee2e6;"><?php echo $t('Item', 'Item'); ?></th>
                                        <th align="center" style="padding:10px; border-bottom:2px solid #dee2e6; width:70px;"><?php echo $t('Cant.', 'Qtd.'); ?></th>
                                        <th align="center" style="padding:10px; border-bottom:2px solid #dee2e6; width:80px;"><?php echo $t('Unidad', 'Unidade'); ?></th>
                                        <th align="center" style="padding:10px; border-bottom:2px solid #dee2e6; width:110px;"><?php echo $t('Fecha req.', 'Data req.'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td style="padding:10px; border-bottom:1px solid #eef0f2;">
                                            <strong><?php echo htmlspecialchars($item['nombre_item']); ?></strong>
                                            <?php if (!empty($item['especificaciones'])): ?>
                                                <br><small style="color:#999;"><?php echo htmlspecialchars($item['especificaciones']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td align="center" style="padding:10px; border-bottom:1px solid #eef0f2;"><?php echo $item['cantidad']; ?></td>
                                        <td align="center" style="padding:10px; border-bottom:1px solid #eef0f2;"><?php echo htmlspecialchars($item['unidad_medida'] ?? '-'); ?></td>
                                        <td align="center" style="padding:10px; border-bottom:1px solid #eef0f2;">
                                            <?php echo formatearFecha($item['fecha_requerida']); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <p style="margin: 25px 0 0 0; font-size: 13px; color:#7f8c8d;">
                                <?php echo $t('Por favor, revise el sistema para más detalles.', 'Por favor, verifique o sistema para mais detalhes.'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8f9fa; padding: 15px 30px; font-size: 12px; color:#95a5a6; text-align: center;">
                            <?php echo $t('Sistema de Proyectos - Notificación automática', 'Sistema de Projetos - Notificação automática'); ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>