<?php
// modules/login/login.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Si ya está logueado, redirigir
if (isset($_SESSION['usuario_id'])) {
    header('Location: ../../index.php');
    exit();
}

$error = '';
$debug = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, ingrese usuario y contraseña';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = TRUE");
            $stmt->execute([$username]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                $error = 'Usuario no encontrado';
                // Debug opcional (quitar en producción)
                if (isset($_GET['debug'])) {
                    $debug = "No existe usuario con username = '$username'";
                }
            } elseif (!password_verify($password, $usuario['password'])) {
                $error = 'Contraseña incorrecta';
                if (isset($_GET['debug'])) {
                    $debug = "Usuario encontrado pero password_verify() falló.<br>";
                    $debug .= "Hash en BD: " . htmlspecialchars($usuario['password']) . "<br>";
                    $debug .= "Longitud hash: " . strlen($usuario['password']) . "<br>";
                    $debug .= "Password ingresado: " . htmlspecialchars($password);
                }
            } else {
                // Login exitoso
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
                $_SESSION['idioma'] = $usuario['idioma_preferido'] ?? 'es';
                
                header('Location: ../../index.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Error de base de datos: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Proyectos</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/mensajes.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Sistema de Proyectos</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($debug): ?>
                <div style="background:#fff3cd; padding:1rem; border-radius:4px; margin-bottom:1rem; font-size:0.85rem;">
                    <strong>Debug:</strong><br><?php echo $debug; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required autofocus
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Iniciar Sesión</button>
            </form>
        </div>
    </div>
</body>
</html>