<?php
// modules/productos/editar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch();

if (!$producto) {
    header('Location: index.php');
    exit();
}

$stmt = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = $_POST['descripcion'] ?? '';
    $categoria_id = $_POST['categoria_id'] ?? null;
    $unidad_medida = $_POST['unidad_medida'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    
    if (empty($nombre)) {
        $error = 'El nombre es obligatorio';
    } elseif (!empty($unidad_medida) && !esUnidadValida($unidad_medida)) {
        $error = 'Unidad de medida no válida';
    } else {
        $stmt = $db->prepare("UPDATE productos SET nombre=?, descripcion=?, categoria_id=?, unidad_medida=?, codigo=? WHERE id=?");
        $stmt->execute([$nombre, $descripcion, $categoria_id ?: null, $unidad_medida, $codigo, $id]);
        header('Location: index.php?mensaje=actualizado');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo traducir('Editar Producto'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/mensajes.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Editar Producto'); ?></h1>
            <a href="index.php" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label><?php echo traducir('Nombre'); ?> *</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo traducir('Descripción'); ?></label>
                    <textarea name="descripcion" rows="3"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo traducir('Categorias'); ?></label>
                        <select name="categoria_id">
                            <option value="">-- <?php echo traducir('Sin categoría'); ?> --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $producto['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo traducir('Unidad de medida'); ?></label>
                        <?php renderSelectUnidades('unidad_medida', $producto['unidad_medida'] ?? ''); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo traducir('Código / Referencia'); ?></label>
                    <input type="text" name="codigo" value="<?php echo htmlspecialchars($producto['codigo'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-primary"><?php echo traducir('Actualizar'); ?></button>
            </form>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>