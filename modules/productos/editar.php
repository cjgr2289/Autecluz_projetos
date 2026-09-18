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
    redirigir('modules/productos/index.php');
}

$stmt = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = $_POST['descripcion'] ?? '';
    $categoria_id = $_POST['categoria_id'] ?? null;
    $unidad_medida = $_POST['unidad_medida'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    $costo_actual = !empty($_POST['costo_actual']) ? (float)$_POST['costo_actual'] : null;
    $moneda = $_POST['moneda'] ?? 'USD';
    
    // Detectar si el costo cambió
    $costo_anterior = $producto['costo_actual'];
    $costo_cambio = ($costo_anterior != $costo_actual);
    
    if (empty($nombre)) {
        $error = 'El nombre es obligatorio';
    } elseif (!empty($unidad_medida) && !esUnidadValida($unidad_medida)) {
        $error = 'Unidad de medida no válida';
    } else {
        try {
            $stmt = $db->prepare("UPDATE productos 
                                  SET nombre=?, descripcion=?, categoria_id=?, unidad_medida=?, 
                                      codigo=?, costo_actual=?, moneda=?,
                                      fecha_ultimo_costo = CASE WHEN ? THEN CURDATE() ELSE fecha_ultimo_costo END
                                  WHERE id=?");
            $stmt->execute([
                $nombre, $descripcion, $categoria_id ?: null, $unidad_medida, $codigo,
                $costo_actual, $moneda,
                $costo_cambio ? 1 : 0,
                $id
            ]);
            
            redirigir('modules/productos/index.php?mensaje=actualizado');
            
        } catch (PDOException $e) {
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo traducir('Editar Producto'); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Editar Producto'); ?></h1>
            <a href="<?php echo url('modules/productos/index.php'); ?>" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
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
                        <select name="unidad_medida">
                            <option value="">-- <?php echo traducir('Seleccionar'); ?> --</option>
                            <?php foreach (getUnidadesMedida() as $cod => $lbl): ?>
                                <option value="<?php echo htmlspecialchars($cod); ?>" 
                                    <?php echo ($producto['unidad_medida'] ?? '') === $cod ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lbl); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?php echo traducir('Código / Referencia'); ?></label>
                    <input type="text" name="codigo" value="<?php echo htmlspecialchars($producto['codigo'] ?? ''); ?>">
                </div>
                
                <!-- ===== Costo ===== -->
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo Atual' : 'Costo Actual'; ?></label>
                        <input type="number" 
                               name="costo_actual" 
                               step="0.01" 
                               min="0"
                               value="<?php echo $producto['costo_actual'] !== null ? number_format((float)$producto['costo_actual'], 2, '.', '') : ''; ?>"
                               placeholder="0.00">
                        <small style="color:#7f8c8d; display:block; margin-top:0.25rem;">
                            <?php echo $_SESSION['idioma'] == 'pt'
                                ? 'Preço base do produto. Será usado como padrão em novos projetos.'
                                : 'Precio base del producto. Se usará como predeterminado en nuevos proyectos.'; ?>
                            <?php if ($producto['fecha_ultimo_costo']): ?>
                                <br>
                                <?php echo $_SESSION['idioma'] == 'pt' ? 'Última atualização' : 'Última actualización'; ?>:
                                <?php echo formatearFecha($producto['fecha_ultimo_costo']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo traducir('Moneda'); ?></label>
                        <select name="moneda">
                            <?php 
                            $monedas = ['USD', 'BRL', 'EUR', 'ARS', 'PYG', 'UYU'];
                            $moneda_actual = $producto['moneda'] ?? 'USD';
                            foreach ($monedas as $m): ?>
                                <option value="<?php echo $m; ?>" <?php echo $moneda_actual === $m ? 'selected' : ''; ?>>
                                    <?php echo $m; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="display:flex; gap:0.75rem;">
                    <button type="submit" class="btn-primary"><?php echo traducir('Actualizar'); ?></button>
                    <a href="<?php echo url('modules/productos/index.php'); ?>" class="btn-secondary"><?php echo traducir('Cancelar'); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>