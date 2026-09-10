<?php
/**
 * Plantilla: Notificación de cambio de estado de un item
 * Variables: $idioma, $item, $estado_anterior, $estado_nuevo, $usuario_cambio, $comentario
 */

$t = function($es, $pt) use ($idioma) {
    return $idioma === 'pt' ? $pt : $es;
};
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t('Actualización de estado', 'Atualização de status'); ?></title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background:#f5f6fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6fa; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    
                    <tr>
                        <td style="background:#3498db; padding: 24px 30px; color:#ffffff;">
                            <h1 style="margin:0; font-size: 20px;">🔄 <?php echo $t('Actualización de estado', 'Atualização de status'); ?></h1>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 30px;">
                            <p style="margin:0 0 15px 0; font-size: 15px; color:#333;">
                                <?php echo $t('El estado del siguiente item ha cambiado:', 'O status do seguinte item foi alterado:'); ?>
                            </p>
                            
                            <div style="background:#f8f9fa; padding: 15px; border-radius:6px; margin-bottom: 20px;">
                                <p style="margin:0 0 10px 0;"><strong><?php echo $t('Item', 'Item'); ?>:</strong> <?php echo htmlspecialchars($item['nombre_item']); ?></p>
                                <p style="margin:0 0 10px 0;"><strong><?php echo $t('Proyecto', 'Projeto'); ?>:</strong> <?php echo htmlspecialchars($item['proyecto_nombre']); ?></p>
                                <p style="margin:0 0 10px 0;"><strong><?php echo $t('Cantidad', 'Quantidade'); ?>:</strong> <?php echo $item['cantidad']; ?></p>
                                <p style="margin:0;"><strong><?php echo $t('Fecha requerida', 'Data requerida'); ?>:</strong> <?php echo formatearFecha($item['fecha_requerida']); ?></p>
                            </div>
                            
                            <div style="text-align: center; padding: 20px 0;">
                                <span style="display:inline-block; background:#e9ecef; color:#495057; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: bold;">
                                    <?php echo htmlspecialchars($estado_anterior); ?>
                                </span>
                                <span style="margin: 0 15px; color:#95a5a6; font-size: 20px;">→</span>
                                <span style="display:inline-block; background:#3498db; color:#ffffff; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: bold;">
                                    <?php echo htmlspecialchars($estado_nuevo); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($usuario_cambio)): ?>
                            <p style="margin: 20px 0 0 0; font-size: 13px; color:#7f8c8d;">
                                <strong><?php echo $t('Modificado por', 'Modificado por'); ?>:</strong> <?php echo htmlspecialchars($usuario_cambio); ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($comentario)): ?>
                            <div style="margin-top: 15px; padding: 10px 15px; background: #fff3cd; border-left: 3px solid #f39c12; border-radius: 4px; font-size: 13px;">
                                <strong><?php echo $t('Comentario', 'Comentário'); ?>:</strong> <?php echo htmlspecialchars($comentario); ?>
                            </div>
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