<?php
// Includes/header.php
$idioma_actual = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma_actual; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS modular con rutas dinámicas -->
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/proyectos.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/items.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/modal.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/productos.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/usuarios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/reportes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-brand">
                <h2>Sistema de Proyectos</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="<?php echo url('index.php'); ?>"><?php echo traducir('Dashboard'); ?></a></li>
                <li><a href="<?php echo url('modules/proyectos/index.php'); ?>"><?php echo traducir('Proyectos'); ?></a></li>
                <li><a href="<?php echo url('modules/productos/index.php'); ?>"><?php echo traducir('Productos'); ?></a></li>
                <li><a href="<?php echo url('modules/categorias/index.php'); ?>"><?php echo traducir('Categorias'); ?></a></li>
                
                <?php if (esMaster()): ?>
                <li><a href="<?php echo url('modules/usuarios/index.php'); ?>"><?php echo traducir('Usuarios'); ?></a></li>
                <?php endif; ?>
                
                <!-- Menú de perfil de usuario -->
                <li class="nav-user-menu">
                    <button type="button" class="nav-user-btn" onclick="toggleUserMenu(event)">
                        <span class="user-avatar">👤</span>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? ''); ?></span>
                        <span class="user-arrow">▾</span>
                    </button>
                    <div class="user-dropdown" id="user-dropdown" style="display:none;">
                        <div class="user-dropdown-header">
                            <strong><?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? ''); ?></strong>
                            <small><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></small>
                        </div>
                        <a href="<?php echo url('modules/perfil/cambiar_password.php'); ?>">
                            🔒 <?php echo traducir('Cambiar Contraseña'); ?>
                        </a>
                        <a href="<?php echo url('modules/login/logout.php'); ?>" class="user-dropdown-logout">
                            🚪 <?php echo traducir('Cerrar Sesión'); ?>
                        </a>
                    </div>
                </li>
                
                <li class="nav-notificaciones">
                    <button type="button" id="btn-notificaciones" class="btn-notificaciones" onclick="toggleNotificaciones(event)">
                        <span class="icono-campana">🔔</span>
                        <span class="badge-notificaciones" id="badge-notificaciones" style="display:none;">0</span>
                    </button>
                </li>
                
                <li>
                    <select id="cambiar_idioma" class="idioma-select">
                        <option value="es" <?php echo $idioma_actual == 'es' ? 'selected' : ''; ?>>Español</option>
                        <option value="pt" <?php echo $idioma_actual == 'pt' ? 'selected' : ''; ?>>Português</option>
                    </select>
                </li>
            </ul>
        </nav>
    </header>
    
    <!-- ============================================
         AVISO: Cambio de contraseña obligatorio
         ============================================ -->
    <?php if (!empty($_SESSION['forzar_cambio_password']) && basename($_SERVER['PHP_SELF']) !== 'cambiar_password.php'): ?>
        <div style="background:#fff3cd; border-bottom:2px solid #f39c12; padding:0.75rem 1rem; text-align:center; font-size:0.9rem; color:#856404;">
            <strong>⚠ <?php echo $_SESSION['idioma'] == 'pt' ? 'Atenção:' : 'Atención:'; ?></strong>
            <?php echo $_SESSION['idioma'] == 'pt'
                ? 'Você deve alterar sua senha antes de continuar.'
                : 'Debe cambiar su contraseña antes de continuar.'; ?>
            <a href="<?php echo url('modules/perfil/cambiar_password.php?forzar=1'); ?>" 
               style="color:#856404; font-weight:bold; text-decoration:underline; margin-left:0.5rem;">
                <?php echo $_SESSION['idioma'] == 'pt' ? 'Alterar agora' : 'Cambiar ahora'; ?>
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Panel de notificaciones -->
    <div id="panel-notificaciones" class="panel-notificaciones" style="display:none;">
        <div class="panel-header">
            <h3>🔔 <?php echo traducir('Notificaciones'); ?></h3>
            <button type="button" class="btn-marcar-todas" onclick="marcarTodasLeidas()" title="<?php echo traducir('Marcar todas como leídas'); ?>">
                ✓ <?php echo traducir('Marcar todas'); ?>
            </button>
        </div>
        <div class="panel-body" id="panel-notificaciones-body">
            <div class="panel-loading">
                <span class="spinner"></span>
                <?php echo traducir('Cargando'); ?>...
            </div>
        </div>
        <div class="panel-footer">
            <a href="<?php echo url('modules/proyectos/index.php'); ?>">
                <?php echo traducir('Ver todos los proyectos'); ?> →
            </a>
        </div>
    </div>
    
    <!-- JS globales -->
    <script>
    // Constante global con la URL base para el JS
    window.BASE_URL = '<?php echo getBaseUrl(); ?>';
    </script>
    <script src="<?php echo url('assets/js/ui.js'); ?>"></script>
    <script src="<?php echo url('assets/js/script.js'); ?>"></script>
    <script src="<?php echo url('assets/js/notificaciones.js'); ?>"></script>
    
    <main>