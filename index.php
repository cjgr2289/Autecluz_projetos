<?php
// index.php
require_once 'config/database.php';
require_once 'includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();

// Estados considerados "activos" (todo menos finalizado)
$estados_activos = "'solicitado','orçado','pendente_aprovacion_cliente','aprovado_cliente',
                    'espera_orden_compra','comprando_materiales','elaboracion','terminado',
                    'pendiente_cobro_cliente'";

// Total proyectos activos
$stmt = $db->query("SELECT COUNT(*) as total FROM proyectos WHERE estado IN ($estados_activos)");
$total_proyectos = $stmt->fetch()['total'];

// Proyectos por estado
$stmt = $db->query("SELECT estado, COUNT(*) as cantidad FROM proyectos WHERE estado IN ($estados_activos) GROUP BY estado");
$proyectos_estados = $stmt->fetchAll();

// Items pendientes (no llegaron) en proyectos activos
$stmt = $db->query("SELECT COUNT(*) as total 
                    FROM items_proyecto i 
                    JOIN proyectos p ON i.proyecto_id = p.id 
                    WHERE i.estado NOT IN ('llego') AND p.estado IN ($estados_activos)");
$items_pendientes = $stmt->fetch()['total'];

// Últimos proyectos activos
$stmt = $db->query("SELECT p.*, u.nombre_completo as creador 
                    FROM proyectos p 
                    LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
                    WHERE p.estado IN ($estados_activos)
                    ORDER BY p.fecha_creacion DESC 
                    LIMIT 5");
$ultimos_proyectos = $stmt->fetchAll();

$estados_proyecto = getEstadosProyecto();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Dashboard'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/tablas.css">
    <link rel="stylesheet" href="assets/css/badges.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/proyectos.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1><?php echo traducir('Bienvenido'); ?>, <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></h1>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3><?php echo traducir('Total Proyectos'); ?></h3>
                <div class="stat-number"><?php echo $total_proyectos; ?></div>
            </div>
            
            <div class="stat-card">
                <h3><?php echo traducir('Items Pendientes'); ?></h3>
                <div class="stat-number"><?php echo $items_pendientes; ?></div>
            </div>
        </div>
        
        <div class="dashboard-proyectos">
            <h2><?php echo traducir('Últimos Proyectos'); ?></h2>
            <div class="table-responsive">
                <table class="tabla-proyectos">
                    <thead>
                        <tr>
                            <th><?php echo traducir('Nombre'); ?></th>
                            <th><?php echo traducir('Fecha'); ?></th>
                            <th><?php echo traducir('Estado'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimos_proyectos)): ?>
                            <tr><td colspan="3" class="empty-cell"><?php echo traducir('No hay proyectos'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($ultimos_proyectos as $proyecto): ?>
                            <tr>
                                <td class="col-nombre-proyecto">
                                    <!-- LINK al proyecto -->
                                    <a href="modules/proyectos/ver.php?id=<?php echo $proyecto['id']; ?>" 
                                       class="proyecto-link">
                                        <div class="proyecto-nombre"><?php echo htmlspecialchars($proyecto['nombre']); ?></div>
                                    </a>
                                    <?php if (!empty($proyecto['creador'])): ?>
                                        <div class="proyecto-meta">
                                            <span class="meta-label"><?php echo traducir('Creado por'); ?>:</span>
                                            <?php echo htmlspecialchars($proyecto['creador']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="col-fechas">
                                    <div class="fecha-linea">
                                        <span class="fecha-label"><?php echo traducir('Ini'); ?>:</span>
                                        <span><?php echo formatearFecha($proyecto['fecha_inicio']); ?></span>
                                    </div>
                                    <div class="fecha-linea">
                                        <span class="fecha-label"><?php echo traducir('Fin'); ?>:</span>
                                        <span><?php echo formatearFecha($proyecto['fecha_fin']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="estado-badge estado-<?php echo $proyecto['estado']; ?>">
                                        <?php echo $estados_proyecto[$proyecto['estado']] ?? $proyecto['estado']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>