<?php
// modules/clientes/editar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador', 'comercial', 'supervisor']) && !esMaster()) {
    redirigir('modules/clientes/index.php');
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

$cliente = obtenerClientePorId($db, $id);

if (!$cliente) {
    redirigir('modules/clientes/index.php');
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $razon_social = trim($_POST['razon_social'] ?? '');
    $ruc = trim($_POST['ruc'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $pais = trim($_POST['pais'] ?? 'Brasil');
    $sitio_web = trim($_POST['sitio_web'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    
    if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
    
    if (empty($errores)) {
        try {
            $stmt = $db->prepare("UPDATE clientes 
                                  SET nombre=?, razon_social=?, ruc=?, email=?, telefono=?, 
                                      direccion=?, ciudad=?, pais=?, sitio_web=?, notas=?
                                  WHERE id=?");
            $stmt->execute([
                $nombre, $razon_social ?: null, $ruc ?: null, $email ?: null,
                $telefono ?: null, $direccion ?: null, $ciudad ?: null,
                $pais, $sitio_web ?: null, $notas ?: null, $id
            ]);
            
            redirigir('modules/clientes/ver.php?id=' . $id . '&mensaje=actualizado');
        } catch (PDOException $e) {
            $errores[] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['idioma'] == 'pt' ? 'Editar Cliente' : 'Editar Cliente'; ?></title>
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
            <h1><?php echo $_SESSION['idioma'] == 'pt' ? 'Editar Cliente' : 'Editar Cliente'; ?></h1>
            <a href="ver.php?id=<?php echo $id; ?>" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
        </div>
        
        <?php if (!empty($errores)): ?>
            <div class="error-message">
                <ul style="margin: 0.5rem 0 0 1.5rem;">
                    <?php foreach ($errores as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Nome' : 'Nombre'; ?> *</label>
                        <input type="text" name="nombre" required maxlength="200"
                               value="<?php echo htmlspecialchars($cliente['nombre']); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Razão Social' : 'Razón Social'; ?></label>
                        <input type="text" name="razon_social" maxlength="250"
                               value="<?php echo htmlspecialchars($cliente['razon_social'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>RUC / CNPJ</label>
                        <input type="text" name="ruc" maxlength="50"
                               value="<?php echo htmlspecialchars($cliente['ruc'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" maxlength="150"
                               value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Telefone' : 'Teléfono'; ?></label>
                        <input type="text" name="telefono" maxlength="50"
                               value="<?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Site' : 'Sitio Web'; ?></label>
                        <input type="text" name="sitio_web" maxlength="200"
                               value="<?php echo htmlspecialchars($cliente['sitio_web'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Endereço' : 'Dirección'; ?></label>
                    <textarea name="direccion" rows="2"><?php echo htmlspecialchars($cliente['direccion'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Cidade' : 'Ciudad'; ?></label>
                        <input type="text" name="ciudad" maxlength="100"
                               value="<?php echo htmlspecialchars($cliente['ciudad'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'País' : 'País'; ?></label>
                        <input type="text" name="pais" maxlength="100"
                               value="<?php echo htmlspecialchars($cliente['pais'] ?? 'Brasil'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Notas' : 'Notas'; ?></label>
                    <textarea name="notas" rows="3"><?php echo htmlspecialchars($cliente['notas'] ?? ''); ?></textarea>
                </div>
                
                <div style="display:flex; gap:0.75rem;">
                    <button type="submit" class="btn-primary">💾 <?php echo traducir('Actualizar'); ?></button>
                    <a href="ver.php?id=<?php echo $id; ?>" class="btn-secondary"><?php echo traducir('Cancelar'); ?></a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>