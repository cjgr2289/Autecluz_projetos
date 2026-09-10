<?php
// modules/productos/index.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();

$busqueda = $_GET['busqueda'] ?? '';
$categoria_id = $_GET['categoria'] ?? '';

$query = "SELECT p.*, c.nombre as categoria_nombre, u.nombre_completo as creador 
          FROM productos p 
          LEFT JOIN categorias c ON p.categoria_id = c.id 
          LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
          WHERE 1=1";
$params = [];

if ($busqueda) {
    $query .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if ($categoria_id) {
    $query .= " AND p.categoria_id = ?";
    $params[] = $categoria_id;
}

$query .= " ORDER BY p.fecha_creacion DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$productos = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Productos'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Productos'); ?></h1>
            <div>
                <a href="../categorias/index.php" class="btn-secondary"><?php echo traducir('Categorias'); ?></a>
                <a href="crear.php" class="btn-primary">+ <?php echo traducir('Crear nuevo producto'); ?></a>
            </div>
        </div>
        
        <div class="filtros">
            <form method="GET" action="">
                <input type="text" name="busqueda" placeholder="<?php echo traducir('Buscar'); ?>..." 
                       value="<?php echo htmlspecialchars($busqueda); ?>">
                <select name="categoria">
                    <option value=""><?php echo traducir('Todas las categorias'); ?></option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                            <?php echo $categoria_id == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-secondary"><?php echo traducir('Buscar'); ?></button>
            </form>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?php echo traducir('Nombre'); ?></th>
                        <th><?php echo traducir('Categoria'); ?></th>
                        <th><?php echo traducir('Unidad'); ?></th>
                        <th>Código</th>
                        <th><?php echo traducir('Acciones'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr><td colspan="5">No hay productos registrados</td></tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($p['categoria_nombre'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($p['unidad_medida'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($p['codigo'] ?? '-'); ?></td>
                            <td>
                                <a href="editar.php?id=<?php echo $p['id']; ?>" class="btn-small">Editar</a>
                                <a href="eliminar.php?id=<?php echo $p['id']; ?>" class="btn-small btn-danger"
                                   onclick="return confirm('¿Está seguro?')">Eliminar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>