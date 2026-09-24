<?php
// modules/clientes/index.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();

$busqueda = $_GET['busqueda'] ?? '';

$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM clientes_responsables cr WHERE cr.cliente_id = c.id AND cr.activo = 1) as total_responsables,
               (SELECT COUNT(*) FROM proyectos p WHERE p.cliente_id = c.id) as total_proyectos
        FROM clientes c
        WHERE c.activo = 1";
$params = [];

if ($busqueda) {
    $sql .= " AND (c.nombre LIKE ? OR c.razon_social LIKE ? OR c.ruc LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY c.nombre ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['idioma'] == 'pt' ? 'Clientes' : 'Clientes'; ?> - Sistema</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo $_SESSION['idioma'] == 'pt' ? 'Clientes' : 'Clientes'; ?></h1>
            <a href="crear.php" class="btn-primary">
                + <?php echo $_SESSION['idioma'] == 'pt' ? 'Novo Cliente' : 'Nuevo Cliente'; ?>
            </a>
        </div>
        
        <?php if ($mensaje === 'creado'): ?>
            <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Cliente criado com sucesso!' : '¡Cliente creado exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'actualizado'): ?>
            <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Cliente atualizado!' : '¡Cliente actualizado!'; ?></div>
        <?php elseif ($mensaje === 'eliminado'): ?>
            <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Cliente excluído!' : '¡Cliente eliminado!'; ?></div>
        <?php endif; ?>
        
        <div class="filtros">
            <form method="GET" action="">
                <input type="text" name="busqueda" 
                       placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Buscar por nome, razão social ou RUC...' : 'Buscar por nombre, razón social o RUC...'; ?>" 
                       value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn-secondary"><?php echo traducir('Buscar'); ?></button>
                <?php if ($busqueda): ?>
                    <a href="index.php" class="btn-secondary"><?php echo traducir('Limpiar'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Nome' : 'Nombre'; ?></th>
                        <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Razão Social' : 'Razón Social'; ?></th>
                        <th>RUC/CNPJ</th>
                        <th class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Responsáveis' : 'Responsables'; ?></th>
                        <th class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Projetos' : 'Proyectos'; ?></th>
                        <th class="text-center"><?php echo traducir('Acciones'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr><td colspan="6" class="empty-cell">
                            <?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum cliente cadastrado' : 'No hay clientes registrados'; ?>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['razon_social'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($c['ruc'] ?? '—'); ?></td>
                            <td class="text-center">
                                <a href="responsables.php?cliente=<?php echo $c['id']; ?>" class="cat-badge" style="text-decoration:none;">
                                    <?php echo $c['total_responsables']; ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="cat-badge"><?php echo $c['total_proyectos']; ?></span>
                            </td>
                            <td class="col-acciones">
                                <div class="acciones-grupo">
                                    <a href="ver.php?id=<?php echo $c['id']; ?>" 
                                       class="btn-accion btn-accion-ver" 
                                       data-tooltip="<?php echo traducir('Ver'); ?>">
                                        <?php echo icono('ver'); ?>
                                    </a>
                                    <a href="responsables.php?cliente=<?php echo $c['id']; ?>" 
                                       class="btn-accion" 
                                       data-tooltip="<?php echo $_SESSION['idioma'] == 'pt' ? 'Responsáveis' : 'Responsables'; ?>">
                                        👥
                                    </a>
                                    <a href="editar.php?id=<?php echo $c['id']; ?>" 
                                       class="btn-accion btn-accion-editar" 
                                       data-tooltip="<?php echo traducir('Editar'); ?>">
                                        <?php echo icono('editar'); ?>
                                    </a>
                                    <button type="button" 
                                            class="btn-accion btn-accion-eliminar" 
                                            data-tooltip="<?php echo traducir('Eliminar'); ?>"
                                            onclick="confirmarEliminarCliente(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>')">
                                        <?php echo icono('eliminar'); ?>
                                    </button>
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
    async function confirmarEliminarCliente(id, nombre) {
        const idioma = '<?php echo $_SESSION['idioma']; ?>';
        const ok = await Confirm.show({
            titulo: idioma === 'pt' ? 'Excluir Cliente' : 'Eliminar Cliente',
            mensaje: idioma === 'pt'
                ? `Deseja realmente excluir o cliente "${nombre}"?\n\nOs responsáveis também serão desativados.`
                : `¿Realmente desea eliminar el cliente "${nombre}"?\n\nLos responsables también serán desactivados.`,
            textoConfirmar: idioma === 'pt' ? 'Excluir' : 'Eliminar',
            tipo: 'danger'
        });
        if (ok) window.location.href = '<?php echo url('modules/clientes/eliminar.php'); ?>?id=' + id;
    }
    </script>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>