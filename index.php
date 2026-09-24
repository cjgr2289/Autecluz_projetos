<?php
// index.php - Dashboard
require_once 'config/database.php';
require_once 'includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();

// ============================================
// ESTADOS CONSIDERADOS "ACTIVOS" para el dashboard
// ============================================
$estados_activos_dashboard = ['comprando_materiales', 'elaboracion'];

// ============================================
// PROYECTOS VISIBLES SEGÚN ROL
// ============================================
// Obtener TODOS los proyectos activos
$stmt = $db->query("
    SELECT p.*, 
           u.nombre_completo as creador,
           e.nombre_completo as encargado_nombre
    FROM proyectos p 
    LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
    LEFT JOIN usuarios e ON p.encargado_id = e.id 
    WHERE p.estado IN ('" . implode("','", $estados_activos_dashboard) . "')
    ORDER BY p.fecha_creacion DESC
");
$todos_activos = $stmt->fetchAll();

// Filtrar según permisos del usuario
$proyectos_activos = filtrarProyectosVisibles($todos_activos);

// ============================================
// ESTADÍSTICAS
// ============================================
$total_activos = count($proyectos_activos);

// Items pendientes (no llegaron) en proyectos activos visibles
$items_pendientes = 0;
if ($total_activos > 0) {
    $ids = array_column($proyectos_activos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM items_proyecto i 
        WHERE i.proyecto_id IN ($placeholders)
          AND i.estado NOT IN ('llego', 'entregado', 'recibido', 'stock', 'separado')
    ");
    $stmt->execute($ids);
    $items_pendientes = $stmt->fetch()['total'];
}

// Proyectos por estado (para las tarjetas de stats)
$proyectos_por_estado = [];
foreach ($proyectos_activos as $p) {
    if (!isset($proyectos_por_estado[$p['estado']])) {
        $proyectos_por_estado[$p['estado']] = 0;
    }
    $proyectos_por_estado[$p['estado']]++;
}

// Últimos 6 proyectos activos
$ultimos_proyectos = array_slice($proyectos_activos, 0, 6);

$estados_proyecto = getEstadosProyecto();
$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Dashboard'); ?> - Sistema</title>
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/proyectos.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        
        <!-- ===== Bienvenida ===== -->
        <div class="dashboard-welcome">
            <h1>👋 <?php echo traducir('Bienvenido'); ?>, <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></h1>
            <p class="dashboard-subtitulo">
                <?php echo $_SESSION['idioma'] == 'pt' 
                    ? 'Veja abaixo o resumo dos projetos ativos.' 
                    : 'Vea abajo el resumen de los proyectos activos.'; ?>
            </p>
        </div>
        
        <!-- ===== Tarjetas de estadísticas ===== -->
        <div class="dashboard-stats">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-icono">🚀</div>
                <div class="stat-card-contenido">
                    <h3><?php echo $_SESSION['idioma'] == 'pt' ? 'Projetos Ativos' : 'Proyectos Activos'; ?></h3>
                    <div class="stat-number"><?php echo $total_activos; ?></div>
                    <small><?php echo $_SESSION['idioma'] == 'pt' ? 'em execução' : 'en ejecución'; ?></small>
                </div>
            </div>
            
            <div class="stat-card stat-card-warning">
                <div class="stat-card-icono">📦</div>
                <div class="stat-card-contenido">
                    <h3><?php echo traducir('Items Pendientes'); ?></h3>
                    <div class="stat-number"><?php echo $items_pendientes; ?></div>
                    <small><?php echo $_SESSION['idioma'] == 'pt' ? 'aguardando recebimento' : 'esperando recepción'; ?></small>
                </div>
            </div>
            
            <div class="stat-card stat-card-info">
                <div class="stat-card-icono">🛒</div>
                <div class="stat-card-contenido">
                    <h3><?php echo $_SESSION['idioma'] == 'pt' ? 'Comprando Materiais' : 'Comprando Materiales'; ?></h3>
                    <div class="stat-number"><?php echo $proyectos_por_estado['comprando_materiales'] ?? 0; ?></div>
                </div>
            </div>
            
            <div class="stat-card stat-card-success">
                <div class="stat-card-icono">🔨</div>
                <div class="stat-card-contenido">
                    <h3><?php echo $_SESSION['idioma'] == 'pt' ? 'Em Elaboração' : 'En Elaboración'; ?></h3>
                    <div class="stat-number"><?php echo $proyectos_por_estado['elaboracion'] ?? 0; ?></div>
                </div>
            </div>
        </div>
        
        <!-- ===== Tabla de proyectos activos ===== -->
        <div class="dashboard-proyectos">
            <div class="dashboard-proyectos-header">
                <h2>🚀 <?php echo $_SESSION['idioma'] == 'pt' ? 'Projetos Ativos' : 'Proyectos Activos'; ?></h2>
                <a href="<?php echo url('modules/proyectos/index.php'); ?>" class="btn-secondary btn-sm">
                    <?php echo $_SESSION['idioma'] == 'pt' ? 'Ver todos' : 'Ver todos'; ?> →
                </a>
            </div>
            
            <?php if (empty($ultimos_proyectos)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📁</div>
                    <h3><?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum projeto ativo' : 'Ningún proyecto activo'; ?></h3>
                    <p><?php echo $_SESSION['idioma'] == 'pt' 
                        ? 'Não há projetos ativos no momento.' 
                        : 'No hay proyectos activos en este momento.'; ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="tabla-proyectos">
                        <thead>
                            <tr>
                                <th><?php echo traducir('Nombre'); ?></th>
                                <th><?php echo traducir('Fecha'); ?></th>
                                <th><?php echo traducir('Estado'); ?></th>
                                <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Encargado' : 'Encargado'; ?></th>
                                <th class="text-center"><?php echo traducir('Acciones'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_proyectos as $proyecto): ?>
                            <tr>
                                <td class="col-nombre-proyecto">
                                    <a href="<?php echo url('modules/proyectos/ver.php?id=' . $proyecto['id']); ?>" class="proyecto-link">
                                        <div class="proyecto-nombre"><?php echo htmlspecialchars($proyecto['nombre']); ?></div>
                                    </a>
                                    <?php if (!empty($proyecto['descripcion'])): ?>
                                        <div class="proyecto-desc">
                                            <?php echo htmlspecialchars(truncarTexto($proyecto['descripcion'], 70)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="col-fechas">
                                    <?php if ($proyecto['fecha_inicio']): ?>
                                    <div class="fecha-linea">
                                        <span class="fecha-label"><?php echo traducir('Ini'); ?>:</span>
                                        <span><?php echo formatearFecha($proyecto['fecha_inicio']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($proyecto['fecha_fin']): ?>
                                    <div class="fecha-linea">
                                        <span class="fecha-label"><?php echo traducir('Fin'); ?>:</span>
                                        <span><?php echo formatearFecha($proyecto['fecha_fin']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="estado-badge estado-<?php echo $proyecto['estado']; ?>">
                                        <?php echo $estados_proyecto[$proyecto['estado']] ?? $proyecto['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($proyecto['encargado_nombre'])): ?>
                                        <span class="badge-encargado">🎯 <?php echo htmlspecialchars($proyecto['encargado_nombre']); ?></span>
                                    <?php else: ?>
                                        <span class="sin-oc">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-acciones">
                                    <div class="acciones-grupo">
                                        <a href="<?php echo url('modules/proyectos/ver.php?id=' . $proyecto['id']); ?>" 
                                           class="btn-accion btn-accion-ver" 
                                           data-tooltip="<?php echo traducir('Ver'); ?>">
                                            <?php echo icono('ver'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>