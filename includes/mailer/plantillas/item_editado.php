<?php
/**
 * Plantilla: Notificación de item editado
 * Variables: $idioma, $item, $cambios, $usuario
 */

$t = function($es, $pt) use ($idioma) {
    return $idioma === 'pt' ? $pt : $es;
};

$etiquetas = [
    'nombre_item'      => $t('Nombre', 'Nome'),
    'cantidad'         => $t('Cantidad', 'Quantidade'),
    'unidad_medida'    => $t('Unidad', 'Unidade'),
    'fecha_requerida'  => $t('Fecha Requerida', 'Data Requerida'),
    'especificaciones' => $t('Especificaciones', 'Especificações'),
];
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t('Item actualizado', 'Item atualizado'); ?></title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background:#f5f6fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6fa; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    
                    <tr>
                        <td style="background:#e67e22; padding: 24px 30px; color:#ffffff;">
                            <h1 style="margin:0; font-size: 20px;">✎ <?php echo $t('Item actualizado', 'Item atualizado'); ?></h1>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 30px;">
                            <p style="margin:0 0 15px 0; font-size: 15px; color:#333;">
                                <?php echo $t('Se han realizado cambios en el siguiente item:', 'Foram feitas alterações no seguinte item:'); ?>
                            </p>
                            
                            <div style="background:#f8f9fa; padding: 15px; border-radius:6px; margin-bottom: 20px;">
                                <p style="margin:0 0 10px 0;"><strong><?php echo $t('Item', 'Item'); ?>:</strong> <?php echo htmlspecialchars($item['nombre_item']); ?></p>
                                <p style="margin:0;"><strong><?php echo $t('Proyecto', 'Projeto'); ?>:</strong> <?php echo htmlspecialchars($item['proyecto_nombre']); ?></p>
                            </div>
                            
                            <h3 style="margin: 20px 0 10px 0; font-size: 15px; color:#e67e22;">
                                <?php echo $t('Cambios realizados', 'Alterações realizadas'); ?>:
                            </h3>
                            
                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; font-size: 13px; border: 1px solid #e9ecef; border-radius: 4px;">
                                <thead>
                                    <tr style="background:#f0f2f5;">
                                        <th align="left" style="padding:8px; border-bottom:2px solid #dee2e6;"><?php echo $t('Campo', 'Campo'); ?></th>
                                        <th align="left" style="padding:8px; border-bottom:2px solid #dee2e6; background:#fbe9e7;"><?php echo $t('Antes', 'Antes'); ?></th>
                                        <th align="left" style="padding:8px; border-bottom:2px solid #dee2e6; background:#e8f5e9;"><?php echo $t('Después', 'Depois'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cambios as $campo => $vals): ?>
                                    <tr>
                                        <td style="padding:8px; border-bottom:1px solid #eef0f2;">
                                            <strong><?php echo $etiquetas[$campo] ?? $campo; ?></strong>
                                        </td>
                                        <td style="padding:8px; border-bottom:1px solid #eef0f2; background:#fff5f5; color:#c0392b; text-decoration: line-through;">
                                            <?php echo htmlspecialchars($vals['anterior']); ?>
                                        </td>
                                        <td style="padding:8px; border-bottom:1px solid #eef0f2; background:#f0fff4; color:#27ae60; font-weight:bold;">
                                            <?php echo htmlspecialchars($vals['nuevo']); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if (!empty($usuario)): ?>
                            <p style="margin: 20px 0 0 0; font-size: 13px; color:#7f8c8d;">
                                <strong><?php echo $t('Modificado por', 'Modificado por'); ?>:</strong> <?php echo htmlspecialchars($usuario); ?>
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
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