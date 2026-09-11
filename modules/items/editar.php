<?php
// modules/items/editar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['compras', 'directivo', 'gerenciador', 'supervisor', 'proyectista']) && !esMaster()) {
    header('Location: ../proyectos/index.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;
$proyecto_id = $_GET['proyecto'] ?? 0;

$stmt = $db->prepare("SELECT i.*, p.estado as proyecto_estado 
                      FROM items_proyecto i 
                      JOIN proyectos p ON i.proyecto_id = p.id 
                      WHERE i.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: ../proyectos/ver.php?id=' . $proyecto_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre_item'] ?? '');
    $cantidad = intval($_POST['cantidad'] ?? 1);
    $unidad = $_POST['unidad_medida'] ?? '';
    $fecha = $_POST['fecha_requerida'] ?? '';
    $especificaciones = $_POST['especificaciones'] ?? '';
    
    // Validar unidad
    if (!empty($unidad) && !esUnidadValida($unidad)) {
        $error = 'Unidad de medida no válida';
    } elseif (empty($nombre) || $cantidad < 1 || empty($fecha)) {
        $error = 'Nombre, cantidad y fecha son obligatorios';
    } else {
        $stmt = $db->prepare("UPDATE items_proyecto 
                              SET nombre_item=?, cantidad=?, unidad_medida=?, 
                                  fecha_requerida=?, especificaciones=? 
                              WHERE id=?");
        $stmt->execute([$nombre, $cantidad, $unidad, $fecha, $especificaciones, $id]);
        
        header('Location: ../proyectos/ver.php?id=' . $proyecto_id . '&mensaje=actualizado');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo traducir('Editar'); ?> Item</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/mensajes.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Editar'); ?> Item</h1>
            <a href="../proyectos/ver.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label><?php echo traducir('Nombre'); ?> *</label>
                    <input type="text" name="nombre_item" 
                           value="<?php echo htmlspecialchars($item['nombre_item']); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo traducir('Cantidad'); ?> *</label>
                        <input type="number" name="cantidad" 
                               value="<?php echo $item['cantidad']; ?>" min="1" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo traducir('Unidad'); ?></label>
                        <?php renderSelectUnidades('unidad_medida', $item['unidad_medida'] ?? ''); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo traducir('Fecha Requerida'); ?> *</label>
                    <input type="date" name="fecha_requerida" 
                           value="<?php echo $item['fecha_requerida']; ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo traducir('Especificaciones / Notas'); ?></label>
                    <textarea name="especificaciones" rows="3"><?php echo htmlspecialchars($item['especificaciones'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn-primary"><?php echo traducir('Actualizar'); ?></button>
            </form>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>