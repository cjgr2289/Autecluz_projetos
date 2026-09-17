<?php
// modules/usuarios/index.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();
requiereMaster();

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM usuarios ORDER BY fecha_creacion DESC");
$usuarios = $stmt->fetchAll();

$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Usuarios'); ?> - Sistema</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/navbar.css">
    <link rel="stylesheet" href="../../assets/css/tablas.css">
    <link rel="stylesheet" href="../../assets/css/badges.css">
    <link rel="stylesheet" href="../../assets/css/mensajes.css">
    <link rel="stylesheet" href="../../assets/css/ui.css">
    <link rel="stylesheet" href="../../assets/css/notificaciones.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Usuarios'); ?></h1>
            <a href="crear.php" class="btn-primary">
                + <?php echo traducir('Nuevo Usuario'); ?>
            </a>
        </div>
        
        <?php if ($mensaje === 'creado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Usuário criado com sucesso!' : '¡Usuario creado exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'actualizado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Usuário atualizado com sucesso!' : '¡Usuario actualizado exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'eliminado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Usuário excluído com sucesso!' : '¡Usuario eliminado exitosamente!'; ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?php echo traducir('Usuario'); ?></th>
                        <th><?php echo traducir('Nombre Completo'); ?></th>
                        <th><?php echo traducir('Email'); ?></th>
                        <th><?php echo traducir('Tipo'); ?></th>
                        <th><?php echo traducir('Idioma'); ?></th>
                        <th><?php echo traducir('Estado'); ?></th>
                        <th style="width:1%; text-align:center;"><?php echo traducir('Acciones'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr><td colspan="7" class="empty-cell"><?php echo traducir('No hay usuarios'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                <?php if ($u['username'] === 'Master'): ?>
                                    <span class="badge-master">MASTER</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                            <td>
                                <span class="cat-badge"><?php echo htmlspecialchars($u['tipo_usuario']); ?></span>
                            </td>
                            <td><?php echo strtoupper($u['idioma_preferido']); ?></td>
                            <td>
                                <?php if ($u['activo']): ?>
                                    <span class="estado-badge estado-aprovado_cliente">
                                        <?php echo traducir('Activo'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="estado-badge estado-solicitado">
                                        <?php echo traducir('Inactivo'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="col-acciones">
                                <div class="acciones-grupo">
                                    <a href="editar.php?id=<?php echo $u['id']; ?>" 
                                       class="btn-accion btn-accion-editar" 
                                       data-tooltip="<?php echo traducir('Editar'); ?>">
                                        <?php echo icono('editar'); ?>
                                    </a>
                                    <?php if ($u['username'] !== 'Master'): ?>
                                        <button type="button" 
                                                class="btn-accion btn-accion-eliminar" 
                                                data-tooltip="<?php echo traducir('Eliminar'); ?>"
                                                onclick="confirmarEliminarUsuario(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>')">
                                            <?php echo icono('eliminar'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    async function confirmarEliminarUsuario(id, username) {
        const idioma = '<?php echo $_SESSION['idioma']; ?>';
        
        const ok = await Confirm.show({
            titulo: idioma === 'pt' ? 'Excluir Usuário' : 'Eliminar Usuario',
            mensaje: idioma === 'pt'
                ? `Deseja realmente excluir o usuário "${username}"?\n\nEsta ação não pode ser desfeita.`
                : `¿Realmente desea eliminar el usuario "${username}"?\n\nEsta acción no se puede deshacer.`,
            textoConfirmar: idioma === 'pt' ? 'Excluir' : 'Eliminar',
            textoCancelar: idioma === 'pt' ? 'Cancelar' : 'Cancelar',
            tipo: 'danger'
        });
        
        if (ok) {
            window.location.href = 'eliminar.php?id=' + id;
        }
    }
    
    // Toast pendiente tras recargar
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const pendiente = sessionStorage.getItem('toast_pendiente');
            if (pendiente) {
                const data = JSON.parse(pendiente);
                sessionStorage.removeItem('toast_pendiente');
                if (typeof Toast !== 'undefined') {
                    Toast[data.tipo] ? Toast[data.tipo](data.mensaje) : Toast.info(data.mensaje);
                }
            }
        } catch(e) {}
    });
    </script>
    
    <script src="../../assets/js/notificaciones.js"></script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>