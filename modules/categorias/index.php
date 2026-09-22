<?php
// modules/categorias/index.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();

// Permisos
$puede_gestionar = tienePermiso(['directivo', 'gerenciador', 'compras']) || esMaster();

// Crear categoría
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $puede_gestionar) {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    if ($nombre) {
        try {
            $stmt = $db->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
            $stmt->execute([$nombre, $descripcion]);
            
            try {
                sessionStorage_set('toast_pendiente', json_encode([
                    'tipo' => 'success',
                    'mensaje' => $_SESSION['idioma'] == 'pt' ? 'Categoria criada com sucesso!' : '¡Categoría creada exitosamente!'
                ]));
            } catch (Exception $e) {}
            
            header('Location: index.php?mensaje=creado');
            exit();
        } catch (PDOException $e) {
            $error = 'Error al crear: ' . $e->getMessage();
        }
    }
}

// Listar
$stmt = $db->query("SELECT c.*, 
                    (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id AND p.activo = 1) as total_productos 
                    FROM categorias c 
                    WHERE c.activo = 1 
                    ORDER BY c.nombre");
$categorias = $stmt->fetchAll();

$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Categorias'); ?> - Sistema</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/navbar.css">
    <link rel="stylesheet" href="../../assets/css/tablas.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
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
            <h1><?php echo traducir('Categorias'); ?></h1>
            <a href="../productos/index.php" class="btn-secondary">
                ← <?php echo traducir('Productos'); ?>
            </a>
        </div>
        
        <?php if ($mensaje === 'creado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Categoria criada com sucesso!' : '¡Categoría creada exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'actualizado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Categoria atualizada com sucesso!' : '¡Categoría actualizada exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'eliminado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Categoria excluída com sucesso!' : '¡Categoría eliminada exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'en_uso'): ?>
            <div class="error-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Não é possível excluir: categoria em uso.' : 'No se puede eliminar: categoría en uso.'; ?></div>
        <?php elseif (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($puede_gestionar): ?>
        <div class="form-container" style="margin-bottom:1.5rem;">
            <h3 style="margin-top:0;"><?php echo $_SESSION['idioma'] == 'pt' ? 'Nova Categoria' : 'Nueva Categoría'; ?></h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo traducir('Nombre'); ?> *</label>
                        <input type="text" name="nombre" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label><?php echo traducir('Descripción'); ?></label>
                        <input type="text" name="descripcion" maxlength="255">
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    + <?php echo traducir('Agregar'); ?>
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?php echo traducir('Nombre'); ?></th>
                        <th><?php echo traducir('Descripción'); ?></th>
                        <th style="width:100px; text-align:center;"><?php echo traducir('Productos'); ?></th>
                        <?php if ($puede_gestionar): ?>
                            <th style="width:1%; text-align:center;"><?php echo traducir('Acciones'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorias)): ?>
                        <tr><td colspan="<?php echo $puede_gestionar ? 4 : 3; ?>" class="empty-cell">
                            <?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhuma categoria cadastrada' : 'No hay categorías registradas'; ?>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $c): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['descripcion'] ?? '-'); ?></td>
                            <td style="text-align:center;">
                                <span class="cat-badge"><?php echo $c['total_productos']; ?></span>
                            </td>
                            <?php if ($puede_gestionar): ?>
                            <td class="col-acciones">
                                <div class="acciones-grupo">
                                    <a href="editar.php?id=<?php echo $c['id']; ?>" 
                                       class="btn-accion btn-accion-editar" 
                                       data-tooltip="<?php echo traducir('Editar'); ?>">
                                        <?php echo icono('editar'); ?>
                                    </a>
                                    <?php if ($c['total_productos'] == 0): ?>
                                        <button type="button" 
                                                class="btn-accion btn-accion-eliminar" 
                                                data-tooltip="<?php echo traducir('Eliminar'); ?>"
                                                onclick="confirmarEliminarCategoria(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>')">
                                            <?php echo icono('eliminar'); ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn-accion" 
                                                disabled
                                                style="opacity:0.4; cursor:not-allowed;"
                                                data-tooltip="<?php echo $_SESSION['idioma'] == 'pt' ? 'Em uso - não pode excluir' : 'En uso - no se puede eliminar'; ?>">
                                            <?php echo icono('eliminar'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    async function confirmarEliminarCategoria(id, nombre) {
        const idioma = '<?php echo $_SESSION['idioma']; ?>';
        
        const ok = await Confirm.show({
            titulo: idioma === 'pt' ? 'Excluir Categoria' : 'Eliminar Categoría',
            mensaje: idioma === 'pt'
                ? `Deseja realmente excluir a categoria "${nombre}"?`
                : `¿Realmente desea eliminar la categoría "${nombre}"?`,
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
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>