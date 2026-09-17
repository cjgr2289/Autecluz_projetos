<?php
/**
 * Plantilla: Bienvenida a nuevo usuario
 * Variables: $idioma, $usuario, $password_temporal
 * 
 * La URL del botón está fija al dominio de producción.
 */

$t = function($es, $pt) use ($idioma) {
    return $idioma === 'pt' ? $pt : $es;
};

// URL FIJA del sistema (siempre apunta al dominio de producción)
$url_login = 'https://projetos.autecluz.com/';
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t('Bienvenido', 'Bem-vindo'); ?></title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background:#f5f6fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6fa; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background:#27ae60; padding: 24px 30px; color:#ffffff;">
                            <h1 style="margin:0; font-size: 20px;">🎉 <?php echo $t('¡Bienvenido!', 'Bem-vindo!'); ?></h1>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="margin:0 0 15px 0; font-size: 15px; color:#333;">
                                <?php echo $t('Hola', 'Olá'); ?> <strong><?php echo htmlspecialchars($usuario['nombre_completo']); ?></strong>,
                            </p>
                            <p style="margin:0 0 20px 0; font-size: 15px; color:#333;">
                                <?php echo $t(
                                    'Se ha creado una cuenta para ti en el Sistema de Proyectos. A continuación tus credenciales de acceso:',
                                    'Uma conta foi criada para você no Sistema de Projetos. Abaixo suas credenciais de acesso:'
                                ); ?>
                            </p>
                            
                            <!-- Credenciales -->
                            <div style="background:#f8f9fa; padding: 20px; border-radius:6px; border-left: 4px solid #27ae60;">
                                <p style="margin:0 0 10px 0; font-size: 14px;">
                                    <strong><?php echo $t('Usuario', 'Usuário'); ?>:</strong> 
                                    <span style="background:#ffffff; padding: 3px 8px; border-radius: 4px; font-family: monospace;">
                                        <?php echo htmlspecialchars($usuario['username']); ?>
                                    </span>
                                </p>
                                <p style="margin:0; font-size: 14px;">
                                    <strong><?php echo $t('Contraseña', 'Senha'); ?>:</strong> 
                                    <span style="background:#ffffff; padding: 3px 8px; border-radius: 4px; font-family: monospace;">
                                        <?php echo htmlspecialchars($password_temporal); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <!-- Advertencia -->
                            <p style="margin: 20px 0 0 0; font-size: 13px; color:#e74c3c;">
                                ⚠️ <?php echo $t(
                                    'Por seguridad, cambie su contraseña en el primer inicio de sesión.',
                                    'Por segurança, altere sua senha no primeiro login.'
                                ); ?>
                            </p>
                            
                            <!-- Botón con URL FIJA -->
                            <div style="text-align: center; margin-top: 30px;">
                                <a href="<?php echo $url_login; ?>" 
                                   style="display: inline-block; background:#3498db; color:#ffffff; padding: 12px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px;">
                                    <?php echo $t('Iniciar Sesión', 'Entrar'); ?>
                                </a>
                            </div>
                            
                            <!-- Alternativa de URL -->
                            <p style="margin: 20px 0 0 0; font-size: 12px; color:#7f8c8d; text-align: center; word-break: break-all;">
                                <?php echo $t('O copie y pegue este enlace en su navegador:', 'Ou copie e cole este link no seu navegador:'); ?><br>
                                <a href="<?php echo $url_login; ?>" style="color:#3498db;">
                                    <?php echo $url_login; ?>
                                </a>
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