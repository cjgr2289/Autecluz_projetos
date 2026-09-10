<?php
// modules/usuarios/index.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();
requiereMaster();  // <-- Solo Master puede acceder

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM usuarios ORDER BY fecha_creacion DESC");
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title>Usuarios</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1>Usuarios</h1>
            <a href="crear.php" class="btn-primary">+ Nuevo Usuario</a>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Idioma</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($u['username']); ?>
                            <?php if ($u['username'] === 'Master'): ?>
                                <span class="badge-master">MASTER</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($u['tipo_usuario']); ?></td>
                        <td><?php echo strtoupper($u['idioma_preferido']); ?></td>
                        <td><?php echo $u['activo'] ? 'Activo' : 'Inactivo'; ?></td>
                        <td>
                            <a href="editar.php?id=<?php echo $u['id']; ?>" class="btn-small">Editar</a>
                            <?php if ($u['username'] !== 'Master'): ?>
                            <a href="eliminar.php?id=<?php echo $u['id']; ?>" class="btn-small btn-danger"
                               onclick="return confirm('¿Eliminar usuario?')">Eliminar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>