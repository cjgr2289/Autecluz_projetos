<?php
// Includes/header.php
$idioma_actual = $_SESSION['idioma'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?php echo $idioma_actual; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS modular -->
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/style.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/navbar.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/formularios.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/tablas.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/badges.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/mensajes.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/dashboard.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/proyectos.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/items.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/modal.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/productos.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/usuarios.css">
    <link rel="stylesheet" href="/sistema_proyectos/assets/css/footer.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-brand">
                <h2>Sistema de Proyectos</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="/sistema_proyectos/index.php"><?php echo traducir('Dashboard'); ?></a></li>
                <li><a href="/sistema_proyectos/modules/proyectos/index.php"><?php echo traducir('Proyectos'); ?></a></li>
                <li><a href="/sistema_proyectos/modules/productos/index.php"><?php echo traducir('Productos'); ?></a></li>
                <li><a href="/sistema_proyectos/modules/categorias/index.php"><?php echo traducir('Categorias'); ?></a></li>
                
                <?php if (esMaster()): ?>
                <li><a href="/sistema_proyectos/modules/usuarios/index.php"><?php echo traducir('Usuarios'); ?></a></li>
                <?php endif; ?>
                
                <li><a href="/sistema_proyectos/modules/login/logout.php"><?php echo traducir('Cerrar Sesión'); ?></a></li>
                <li>
                    <select id="cambiar_idioma" class="idioma-select">
                        <option value="es" <?php echo $idioma_actual == 'es' ? 'selected' : ''; ?>>Español</option>
                        <option value="pt" <?php echo $idioma_actual == 'pt' ? 'selected' : ''; ?>>Português</option>
                    </select>
                </li>
            </ul>
        </nav>
    </header>
    <main>