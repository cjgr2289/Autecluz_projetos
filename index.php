<?php
// index.php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: modules/login/login.php');
    exit();
}

// Obtener estadísticas para el dashboard
$db = Database::getInstance()->getConnection();

// Total proyectos
$stmt = $db->query("SELECT COUNT(*) as total FROM proyectos");
$total_proyectos = $stmt->fetch()['total'];

// Proyectos por estado
$stmt = $db->query("SELECT estado, COUNT(*) as cantidad FROM proyectos GROUP BY estado");
$proyectos_estados = $stmt->fetchAll();

// Items pendientes
$stmt = $db->query("SELECT COUNT(*) as total FROM items_proyecto WHERE estado IN ('solicitado', 'pendiente')");
$items_pendientes = $stmt->fetch()['total'];

// Últimos proyectos
$stmt = $db->query("SELECT * FROM proyectos ORDER BY fecha_creacion DESC LIMIT 5");
$ultimos_proyectos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Dashboard'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1><?php echo traducir('Bienvenido'); ?>, <?php echo $_SESSION['nombre_completo']; ?></h1>
        
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
            <table>
                <thead>
                    <tr>
                        <th><?php echo traducir('Nombre'); ?></th>
                        <th><?php echo traducir('Fecha Inicio'); ?></th>
                        <th><?php echo traducir('Fecha Fin'); ?></th>
                        <th><?php echo traducir('Estado'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimos_proyectos as $proyecto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($proyecto['nombre']); ?></td>
                        <td><?php echo $proyecto['fecha_inicio']; ?></td>
                        <td><?php echo $proyecto['fecha_fin']; ?></td>
                        <td><?php echo getEstadosProyecto()[$proyecto['estado']] ?? $proyecto['estado']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>