<?php
// modules/usuarios/crear.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();
requiereMaster();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';
    $idioma = $_POST['idioma_preferido'] ?? 'es';
    
    $tipos_validos = ['directivo', 'gerenciador', 'supervisor', 'compras', 'proyectista'];
    
    if (empty($username) || empty($password) || empty($nombre_completo) || !in_array($tipo_usuario, $tipos_validos)) {
        $error = 'Todos los campos son obligatorios';
    } elseif (strtolower($username) === 'master') {
        $error = 'El nombre de usuario "Master" está reservado';
    } else {
        $db = Database::getInstance()->getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO usuarios 
                                  (username, password, nombre_completo, email, tipo_usuario, idioma_preferido)
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $nombre_completo,
                $email,
                $tipo_usuario,
                $idioma
            ]);
            
            $nuevo_usuario_id = $db->lastInsertId();
            
            // ============================================
            // ENVIAR NOTIFICACIÓN AL NUEVO USUARIO
            // ============================================
            if (!empty($email)) {
                try {
                    notificarUsuarioCreado($db, $nuevo_usuario_id, $password);
                } catch (Exception $e) {
                    error_log("Error al enviar email de bienvenida: " . $e->getMessage());
                }
            }
            
            header('Location: index.php?mensaje=creado');
            exit();
        } catch (PDOException $e) {
            $error = 'Error al crear: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo traducir('Crear Usuario'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/mensajes.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Crear Usuario'); ?></h1>
            <a href="index.php" class="btn-secondary"><?php echo traducir('Volver'); ?></a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label><?php echo traducir('Usuario'); ?> *</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label><?php echo traducir('Contraseña'); ?> *</label>
                    <input type="text" name="password" required>
                    <small style="color:#7f8c8d;">Se enviará por email al nuevo usuario.</small>
                </div>
                <div class="form-group">
                    <label><?php echo traducir('Nombre Completo'); ?> *</label>
                    <input type="text" name="nombre_completo" required>
                </div>
                <div class="form-group">
                    <label><?php echo traducir('Email'); ?></label>
                    <input type="email" name="email" placeholder="usuario@ejemplo.com">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo traducir('Tipo de Usuario'); ?> *</label>
                        <select name="tipo_usuario" required>
                            <option value="">-- <?php echo traducir('Seleccionar'); ?> --</option>
                            <option value="directivo"><?php echo traducir('Directivo'); ?></option>
                            <option value="gerenciador"><?php echo traducir('Gerenciador'); ?></option>
                            <option value="supervisor"><?php echo traducir('Supervisor'); ?></option>
                            <option value="compras"><?php echo traducir('Compras'); ?></option>
                            <option value="proyectista"><?php echo traducir('Proyectista'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo traducir('Idioma Preferido'); ?></label>
                        <select name="idioma_preferido">
                            <option value="es">Español</option>
                            <option value="pt">Português</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-primary"><?php echo traducir('Crear Usuario'); ?></button>
            </form>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>