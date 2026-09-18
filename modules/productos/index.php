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
          WHERE p.activo = 1";
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

$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Productos'); ?> - Sistema</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Productos'); ?></h1>
            <div>
                <a href="<?php echo url('modules/categorias/index.php'); ?>" class="btn-secondary">
                    <?php echo traducir('Categorias'); ?>
                </a>
                <a href="<?php echo url('modules/productos/crear.php'); ?>" class="btn-primary">
                    + <?php echo traducir('Nuevo Producto'); ?>
                </a>
            </div>
        </div>
        
        <?php if ($mensaje === 'creado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Produto criado com sucesso!' : '¡Producto creado exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'actualizado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Produto atualizado com sucesso!' : '¡Producto actualizado exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'eliminado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Produto excluído com sucesso!' : '¡Producto eliminado exitosamente!'; ?></div>
        <?php endif; ?>
        
        <div class="filtros">
            <form method="GET" action="">
                <input type="text" name="busqueda" 
                       placeholder="<?php echo traducir('Buscar'); ?>..." 
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
                <button type="submit" class="btn-secondary">
                    <?php echo traducir('Filtrar'); ?>
                </button>
                <?php if ($busqueda || $categoria_id): ?>
                    <a href="<?php echo url('modules/productos/index.php'); ?>" class="btn-secondary"><?php echo traducir('Limpiar'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?php echo traducir('Nombre'); ?></th>
                        <th><?php echo traducir('Categorias'); ?></th>
                        <th style="width:110px;"><?php echo traducir('Unidad'); ?></th>
                        <th style="width:100px;"><?php echo traducir('Código'); ?></th>
                        <th style="width:110px; text-align:right;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo Atual' : 'Costo Actual'; ?></th>
                        <th style="width:1%; text-align:center;"><?php echo traducir('Acciones'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr><td colspan="6" class="empty-cell"><?php echo traducir('No hay productos registrados'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($p['nombre']); ?></strong>
                                <?php if (!empty($p['descripcion'])): ?>
                                    <div class="proyecto-desc"><?php echo htmlspecialchars(truncarTexto($p['descripcion'], 70)); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['categoria_nombre']): ?>
                                    <span class="cat-badge"><?php echo htmlspecialchars($p['categoria_nombre']); ?></span>
                                <?php else: ?>
                                    <span class="sin-cat">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(getUnidadLabel($p['unidad_medida']) ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($p['codigo'] ?? '-'); ?></td>
                            <td style="text-align:right; font-weight:600; color:#27ae60;">
                                <?php if ($p['costo_actual'] !== null): ?>
                                    <?php echo formatearMoneda($p['costo_actual'], $p['moneda'] ?? 'USD'); ?>
                                <?php else: ?>
                                    <span style="color:#ccc;">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-acciones">
                                <div class="acciones-grupo">
                                    <a href="<?php echo url('modules/productos/editar.php?id=' . $p['id']); ?>" 
                                       class="btn-accion btn-accion-editar" 
                                       data-tooltip="<?php echo traducir('Editar'); ?>">
                                        <?php echo icono('editar'); ?>
                                    </a>
                                    <button type="button" 
                                            class="btn-accion btn-accion-eliminar" 
                                            data-tooltip="<?php echo traducir('Eliminar'); ?>"
                                            onclick="confirmarEliminarProducto(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['nombre'])); ?>')">
                                        <?php echo icono('eliminar'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    async function confirmarEliminarProducto(id, nombre) {
        const idioma = '<?php echo $_SESSION['idioma']; ?>';
        const ok = await Confirm.show({
            titulo: idioma === 'pt' ? 'Excluir Produto' : 'Eliminar Producto',
            mensaje: idioma === 'pt'
                ? `Deseja realmente excluir o produto "${nombre}"?\n\nO produto ficará inativo mas continuará nos projetos existentes.`
                : `¿Realmente desea eliminar el producto "${nombre}"?\n\nEl producto quedará inactivo pero seguirá en los proyectos existentes.`,
            textoConfirmar: idioma === 'pt' ? 'Excluir' : 'Eliminar',
            tipo: 'danger'
        });
        
        if (ok) {
            window.location.href = '<?php echo url('modules/productos/eliminar.php'); ?>?id=' + id;
        }
    }
    </script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>