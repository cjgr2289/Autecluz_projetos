<?php
// modules/categorias/editar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

// Permisos: solo compras, directivo, gerenciador o Master
if (!tienePermiso(['directivo', 'gerenciador', 'compras']) && !esMaster()) {
    header('Location: index.php?error=no_permitido');
    exit();
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM categorias WHERE id = ? AND activo = 1");
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    if (empty($nombre)) {
        $error = 'El nombre es obligatorio';
    } else {
        try {
            $stmt = $db->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $id]);
            header('Location: index.php?mensaje=actualizado');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Ya existe una categoría con ese nombre';
            } else {
                $error = 'Error al actualizar: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo traducir('Editar'); ?> <?php echo traducir('Categorias'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/mensajes.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Editar'); ?> <?php echo traducir('Categorias'); ?></h1>
            <a href="index.php" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label><?php echo traducir('Nombre'); ?> *</label>
                    <input type="text" name="nombre" 
                           value="<?php echo htmlspecialchars($categoria['nombre']); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo traducir('Descripción'); ?></label>
                    <textarea name="descripcion" rows="3"><?php echo htmlspecialchars($categoria['descripcion'] ?? ''); ?></textarea>
                </div>
                <div style="display:flex; gap:0.75rem;">
                    <button type="submit" class="btn-primary"><?php echo traducir('Actualizar'); ?></button>
                    <a href="index.php" class="btn-secondary"><?php echo traducir('Cancelar'); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>