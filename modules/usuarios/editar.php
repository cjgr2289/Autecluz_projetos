<?php
// modules/usuarios/editar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();
requiereMaster();  // <-- Solo Master

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: index.php');
    exit();
}

$es_usuario_master = ($usuario['username'] === 'Master');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';
    $idioma = $_POST['idioma_preferido'] ?? 'es';
    $password = $_POST['password'] ?? '';
    
    // El usuario Master no puede ser desactivado ni cambiado de tipo
    if ($es_usuario_master) {
        $activo = 1;
        $tipo_usuario = 'directivo';
    } else {
        $activo = isset($_POST['activo']) ? 1 : 0;
    }
    
    $tipos_validos = ['directivo', 'gerenciador', 'supervisor', 'compras', 'proyectista', 'alamacen'];
    
    if (empty($nombre_completo) || !in_array($tipo_usuario, $tipos_validos)) {
        $error = 'Datos inválidos';
    } else {
        try {
            if (!empty($password)) {
                $stmt = $db->prepare("UPDATE usuarios 
                                      SET nombre_completo=?, email=?, tipo_usuario=?, 
                                          idioma_preferido=?, activo=?, password=? 
                                      WHERE id=?");
                $stmt->execute([$nombre_completo, $email, $tipo_usuario, $idioma, 
                               $activo, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $stmt = $db->prepare("UPDATE usuarios 
                                      SET nombre_completo=?, email=?, tipo_usuario=?, 
                                          idioma_preferido=?, activo=? 
                                      WHERE id=?");
                $stmt->execute([$nombre_completo, $email, $tipo_usuario, $idioma, $activo, $id]);
            }
            header('Location: index.php?mensaje=actualizado');
            exit();
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1>Editar Usuario</h1>
            <a href="index.php" class="btn-secondary">Volver</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($es_usuario_master): ?>
            <div class="info-message">
                <strong>Nota:</strong> Este es el usuario Master. Su tipo de usuario no puede cambiarse
                y no puede ser desactivado. Puede cambiar su contraseña y datos personales.
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" value="<?php echo htmlspecialchars($usuario['username']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Nueva Contraseña (dejar en blanco para no cambiar)</label>
                    <input type="password" name="password">
                </div>
                <div class="form-group">
                    <label>Nombre Completo *</label>
                    <input type="text" name="nombre_completo" value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo de Usuario</label>
                        <select name="tipo_usuario">
                            <?php foreach (['directivo', 'gerenciador', 'supervisor', 'compras', 'proyectista', 'almacen'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $usuario['tipo_usuario'] == $t ? 'selected' : ''; ?>>
                                    <?php echo traducir(ucfirst($t)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($es_usuario_master): ?>
                            <input type="hidden" name="tipo_usuario" value="directivo">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Idioma</label>
                        <select name="idioma_preferido">
                            <option value="es" <?php echo $usuario['idioma_preferido'] == 'es' ? 'selected' : ''; ?>>Español</option>
                            <option value="pt" <?php echo $usuario['idioma_preferido'] == 'pt' ? 'selected' : ''; ?>>Português</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="activo" 
                               <?php echo $usuario['activo'] ? 'checked' : ''; ?>
                               <?php echo $es_usuario_master ? 'disabled' : ''; ?>>
                        Usuario Activo
                        <?php if ($es_usuario_master): ?>
                            <input type="hidden" name="activo" value="1">
                        <?php endif; ?>
                    </label>
                </div>
                <button type="submit" class="btn-primary">Actualizar</button>
            </form>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>