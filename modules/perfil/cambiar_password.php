<?php
// modules/perfil/cambiar_password.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$usuario_id = $_SESSION['usuario_id'];

$errores = [];
$exito = false;
$forzar = isset($_GET['forzar']) || !empty($_SESSION['forzar_cambio_password']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    
    // Validaciones
    if (empty($password_actual)) {
        $errores[] = 'Debe ingresar su contraseña actual';
    }
    if (empty($password_nueva)) {
        $errores[] = 'Debe ingresar una nueva contraseña';
    }
    if (strlen($password_nueva) < 6) {
        $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres';
    }
    if ($password_nueva !== $password_confirmar) {
        $errores[] = 'Las contraseñas no coinciden';
    }
    if ($password_nueva === $password_actual) {
        $errores[] = 'La nueva contraseña debe ser diferente a la actual';
    }
    
    // Verificar contraseña actual
    if (empty($errores)) {
        $stmt = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $row = $stmt->fetch();
        
        if (!$row || !password_verify($password_actual, $row['password'])) {
            $errores[] = 'La contraseña actual es incorrecta';
        }
    }
    
    // Cambiar contraseña
    if (empty($errores)) {
        try {
            $nuevo_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            
            // ✅ Actualizar contraseña Y marcar debe_cambiar_password = FALSE
            $stmt = $db->prepare("UPDATE usuarios 
                                  SET password = ?, debe_cambiar_password = FALSE 
                                  WHERE id = ?");
            $stmt->execute([$nuevo_hash, $usuario_id]);
            
            // ✅ Quitar la marca de la sesión
            unset($_SESSION['forzar_cambio_password']);
            
            // ✅ Redirigir con flag para que el toast se muestre y luego cierre sesión
            header('Location: ' . url('modules/perfil/cambiar_password.php?mensaje=ok'));
            exit();
            
        } catch (PDOException $e) {
            $errores[] = 'Error al cambiar la contraseña: ' . $e->getMessage();
        }
    }
}

$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Cambiar Contraseña'); ?> - Sistema</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Cambiar Contraseña'); ?></h1>
            <?php if (!$forzar): ?>
                <a href="<?php echo url('index.php'); ?>" class="btn-secondary">
                    ← <?php echo traducir('Volver'); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($errores)): ?>
            <div class="error-message">
                <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Erros encontrados:' : 'Errores encontrados:'; ?></strong>
                <ul style="margin: 0.5rem 0 0 1.5rem;">
                    <?php foreach ($errores as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($forzar): ?>
            <div class="info-message" style="margin-bottom:1.5rem; background:#fff3cd; color:#856404; border-color:#f39c12;">
                <strong>⚠ <?php echo $_SESSION['idioma'] == 'pt' ? 'Atenção:' : 'Atención:'; ?></strong>
                <?php echo $_SESSION['idioma'] == 'pt'
                    ? 'Você deve alterar sua senha antes de continuar usando o sistema.'
                    : 'Debe cambiar su contraseña antes de continuar usando el sistema.'; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="info-message" style="margin-bottom:1.5rem;">
                <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Informação:' : 'Información:'; ?></strong>
                <?php echo $_SESSION['idioma'] == 'pt'
                    ? 'Por segurança, recomendamos alterar sua senha regularmente e não compartilhá-la com ninguém.'
                    : 'Por seguridad, recomendamos cambiar su contraseña regularmente y no compartirla con nadie.'; ?>
            </div>
            
            <form method="POST" action="" autocomplete="off" id="form-password">
                <div class="form-group">
                    <label for="password_actual">
                        <?php echo traducir('Contraseña') . ' ' . ($_SESSION['idioma'] == 'pt' ? 'atual' : 'actual'); ?> *
                    </label>
                    <input type="password" 
                           id="password_actual" 
                           name="password_actual" 
                           required
                           autofocus
                           autocomplete="current-password"
                           placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Digite sua senha atual' : 'Ingrese su contraseña actual'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password_nueva">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Nova Senha' : 'Nueva Contraseña'; ?> *
                    </label>
                    <input type="password" 
                           id="password_nueva" 
                           name="password_nueva" 
                           required
                           autocomplete="new-password"
                           minlength="6"
                           placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Mínimo 6 caracteres' : 'Mínimo 6 caracteres'; ?>">
                    <small style="color:#7f8c8d; display:block; margin-top:0.25rem;">
                        <?php echo $_SESSION['idioma'] == 'pt'
                            ? 'Deve ter pelo menos 6 caracteres.'
                            : 'Debe tener al menos 6 caracteres.'; ?>
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirmar">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Confirmar Nova Senha' : 'Confirmar Nueva Contraseña'; ?> *
                    </label>
                    <input type="password" 
                           id="password_confirmar" 
                           name="password_confirmar" 
                           required
                           autocomplete="new-password"
                           minlength="6"
                           placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Repita a nova senha' : 'Repita la nueva contraseña'; ?>">
                </div>
                
                <!-- Barra de fortaleza -->
                <div id="fortaleza" style="display:none; margin-bottom:1rem;">
                    <div style="display:flex; gap:4px; margin-bottom:0.35rem;">
                        <div class="fortaleza-barra" data-nivel="1"></div>
                        <div class="fortaleza-barra" data-nivel="2"></div>
                        <div class="fortaleza-barra" data-nivel="3"></div>
                        <div class="fortaleza-barra" data-nivel="4"></div>
                    </div>
                    <small id="fortaleza-texto" style="color:#7f8c8d;"></small>
                </div>
                
                <div class="form-actions" style="display:flex; gap:0.75rem;">
                    <button type="submit" class="btn-primary">
                        🔒 <?php echo $_SESSION['idioma'] == 'pt' ? 'Alterar Senha' : 'Cambiar Contraseña'; ?>
                    </button>
                    <?php if (!$forzar): ?>
                        <a href="<?php echo url('index.php'); ?>" class="btn-secondary">
                            <?php echo traducir('Cancelar'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    .fortaleza-barra {
        flex: 1;
        height: 5px;
        background: #e9ecef;
        border-radius: 3px;
        transition: background 0.2s;
    }
    .fortaleza-barra.activa[data-nivel="1"] { background: #e74c3c; }
    .fortaleza-barra.activa[data-nivel="2"] { background: #f39c12; }
    .fortaleza-barra.activa[data-nivel="3"] { background: #f1c40f; }
    .fortaleza-barra.activa[data-nivel="4"] { background: #27ae60; }
    </style>
    
    <script>
    const idioma = '<?php echo $_SESSION['idioma']; ?>';
    
    // ============================================
    // TOAST DE ÉXITO Y CIERRE AUTOMÁTICO
    // ============================================
    <?php if ($mensaje === 'ok'): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar toast de éxito
        if (typeof Toast !== 'undefined') {
            Toast.success(
                idioma === 'pt'
                    ? 'Senha alterada com sucesso! Você será desconectado em 5 segundos...'
                    : '¡Contraseña cambiada exitosamente! Será desconectado en 5 segundos...',
                {
                    titulo: idioma === 'pt' ? 'Sucesso' : 'Éxito',
                    duracion: 5000
                }
            );
        }
        
        // Contador visual
        let segundos = 5;
        const intervalo = setInterval(function() {
            segundos--;
            if (segundos <= 0) {
                clearInterval(intervalo);
                // ✅ Redirigir al logout
                window.location.href = '<?php echo url('modules/login/logout.php'); ?>';
            }
        }, 1000);
        
        // Deshabilitar el formulario mientras se cierra
        const form = document.getElementById('form-password');
        if (form) {
            form.style.opacity = '0.5';
            form.style.pointerEvents = 'none';
        }
    });
    <?php endif; ?>
    
    // ============================================
    // VALIDADOR EN TIEMPO REAL
    // ============================================
    const passNueva = document.getElementById('password_nueva');
    const passConfirmar = document.getElementById('password_confirmar');
    const fortaleza = document.getElementById('fortaleza');
    const fortalezaTexto = document.getElementById('fortaleza-texto');
    const barras = document.querySelectorAll('.fortaleza-barra');
    const form = document.getElementById('form-password');
    
    if (passNueva) {
        passNueva.addEventListener('input', function() {
            const val = this.value;
            
            if (!val) {
                fortaleza.style.display = 'none';
                return;
            }
            
            fortaleza.style.display = 'block';
            
            let nivel = 0;
            if (val.length >= 6) nivel++;
            if (val.length >= 10) nivel++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) nivel++;
            if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) nivel++;
            
            barras.forEach((b, i) => {
                b.classList.toggle('activa', i < nivel);
            });
            
            const textos = idioma === 'pt'
                ? ['Muito fraca', 'Fraca', 'Média', 'Forte', 'Muito forte']
                : ['Muy débil', 'Débil', 'Media', 'Fuerte', 'Muy fuerte'];
            
            fortalezaTexto.textContent = textos[nivel] || '';
        });
    }
    
    if (form) {
        form.addEventListener('submit', function(e) {
            if (passNueva.value !== passConfirmar.value) {
                e.preventDefault();
                if (typeof Toast !== 'undefined') {
                    Toast.warning(idioma === 'pt'
                        ? 'As senhas não coincidem.'
                        : 'Las contraseñas no coinciden.');
                } else {
                    alert(idioma === 'pt'
                        ? 'As senhas não coincidem.'
                        : 'Las contraseñas no coinciden.');
                }
            }
        });
    }
    </script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>