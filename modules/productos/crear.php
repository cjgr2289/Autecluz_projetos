<?php
// modules/productos/crear.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
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
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO productos (nombre, descripcion, categoria_id, unidad_medida, codigo, usuario_creacion)
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $categoria_id ?: null, $unidad_medida, $codigo, $_SESSION['usuario_id']]);
            
            $redirect = $_POST['redirect'] ?? 'index.php';
            header("Location: $redirect?mensaje=creado");
            exit();
        } catch (PDOException $e) {
            $error = 'Error al crear: ' . $e->getMessage();
        }
    }
}

// Si viene desde el modal, redirige de vuelta al modal después de crear
$redirect = $_GET['redirect'] ?? 'index.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title>Crear Producto</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1>Crear Producto</h1>
            <a href="<?php echo htmlspecialchars($redirect); ?>" class="btn-secondary">Volver</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Categoría</label>
                        <select name="categoria_id">
                            <option value="">-- Sin categoría --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unidad de medida</label>
                        <input type="text" name="unidad_medida" placeholder="Ej: unidad, kg, m">
                    </div>
                </div>
                <div class="form-group">
                    <label>Código / Referencia</label>
                    <input type="text" name="codigo">
                </div>
                <button type="submit" class="btn-primary">Guardar</button>
            </form>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>