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
    $costo_actual = !empty($_POST['costo_actual']) ? (float)$_POST['costo_actual'] : null;
    $moneda = $_POST['moneda'] ?? 'USD';
    
    if (empty($nombre)) {
        $error = 'El nombre es obligatorio';
    } elseif (!empty($unidad_medida) && !esUnidadValida($unidad_medida)) {
        $error = 'Unidad de medida no válida';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO productos 
                                  (nombre, descripcion, categoria_id, unidad_medida, codigo, 
                                   costo_actual, moneda, fecha_ultimo_costo, usuario_creacion)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nombre, 
                $descripcion, 
                $categoria_id ?: null, 
                $unidad_medida, 
                $codigo, 
                $costo_actual, 
                $moneda,
                $costo_actual ? date('Y-m-d') : null,
                $_SESSION['usuario_id']
            ]);
            
            $redirect = $_POST['redirect'] ?? url('modules/productos/index.php');
            header("Location: $redirect?mensaje=creado");
            exit();
        } catch (PDOException $e) {
            $error = 'Error al crear: ' . $e->getMessage();
        }
    }
}

$redirect = $_GET['redirect'] ?? url('modules/productos/index.php');
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo traducir('Crear Producto'); ?></title>
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
            <h1><?php echo traducir('Crear Producto'); ?></h1>
            <a href="<?php echo htmlspecialchars($redirect); ?>" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                
                <div class="form-group">
                    <label><?php echo traducir('Nombre'); ?> *</label>
                    <input type="text" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label><?php echo traducir('Descripción'); ?></label>
                    <textarea name="descripcion" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo traducir('Categorias'); ?></label>
                        <select name="categoria_id">
                            <option value="">-- <?php echo traducir('Sin categoría'); ?> --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo traducir('Unidad de medida'); ?></label>
                        <select name="unidad_medida">
                            <option value="">-- <?php echo traducir('Seleccionar'); ?> --</option>
                            <?php foreach (getUnidadesMedida() as $cod => $lbl): ?>
                                <option value="<?php echo htmlspecialchars($cod); ?>">
                                    <?php echo htmlspecialchars($lbl); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?php echo traducir('Código / Referencia'); ?></label>
                    <input type="text" name="codigo">
                </div>
                
                <!-- ===== NUEVO: Costo ===== -->
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Custo Atual' : 'Costo Actual'; ?></label>
                        <input type="number" 
                               name="costo_actual" 
                               step="0.01" 
                               min="0"
                               placeholder="0.00">
                        <small style="color:#7f8c8d; display:block; margin-top:0.25rem;">
                            <?php echo $_SESSION['idioma'] == 'pt'
                                ? 'Preço base do produto. Será usado como padrão em novos projetos.'
                                : 'Precio base del producto. Se usará como predeterminado en nuevos proyectos.'; ?>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo traducir('Moneda'); ?></label>
                        <select name="moneda">
                            <option value="USD">USD - Dólar</option>
                            <option value="BRL">BRL - Real Brasileño</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="ARS">ARS - Peso Argentino</option>
                            <option value="PYG">PYG - Guaraní</option>
                            <option value="UYU">UYU - Peso Uruguayo</option>
                        </select>
                    </div>
                </div>
                
                <div style="display:flex; gap:0.75rem;">
                    <button type="submit" class="btn-primary"><?php echo traducir('Guardar'); ?></button>
                    <a href="<?php echo htmlspecialchars($redirect); ?>" class="btn-secondary"><?php echo traducir('Cancelar'); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>