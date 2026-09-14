<?php
// modules/items/actualizar_estado.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['compras', 'directivo', 'gerenciador']) && !esMaster()) {
    header('Location: ../proyectos/index.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$item_id = $_GET['id'] ?? 0;
$proyecto_id = $_GET['proyecto'] ?? 0;

if (!$item_id || !$proyecto_id) {
    header('Location: ../proyectos/index.php');
    exit();
}

$stmt = $db->prepare("SELECT i.*, p.estado as proyecto_estado 
                      FROM items_proyecto i 
                      JOIN proyectos p ON i.proyecto_id = p.id 
                      WHERE i.id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: ../proyectos/ver.php?id=' . $proyecto_id);
    exit();
}

$estados_permitidos = getEstadosItemByProyectoEstado($item['proyecto_estado']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuevo_estado = $_POST['estado'] ?? '';
    $comentario = $_POST['comentario'] ?? '';
    $nueva_fecha = $_POST['fecha_requerida'] ?? $item['fecha_requerida'];
    
    if (!isset($estados_permitidos[$nuevo_estado])) {
        $error = 'Estado no permitido para este proyecto';
    } else {
        try {
            $estado_anterior = $item['estado'];
            
            // Guardar historial
            $stmt = $db->prepare("
                INSERT INTO historial_items (item_id, estado_anterior, estado_nuevo, 
                                           fecha_anterior, fecha_nueva, usuario_id, comentario)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $item_id,
                $estado_anterior,
                $nuevo_estado,
                $item['fecha_requerida'],
                $nueva_fecha,
                $_SESSION['usuario_id'],
                $comentario
            ]);

            // Actualizar item
            $stmt = $db->prepare("
                UPDATE items_proyecto 
                SET estado = ?, fecha_requerida = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nuevo_estado, $nueva_fecha, $item_id]);
            
            // Notificar al solicitante si el estado cambió
            if ($estado_anterior !== $nuevo_estado) {
                try {
                    notificarCambioEstadoItem(
                        $db, 
                        $item_id, 
                        $estado_anterior, 
                        $nuevo_estado, 
                        $_SESSION['usuario_id'], 
                        $comentario
                    );
                } catch (Exception $e) {
                    error_log("Error al enviar email de cambio de estado: " . $e->getMessage());
                }
            }
            
            header('Location: ../proyectos/ver.php?id=' . $proyecto_id . '&mensaje=actualizado');
            exit();
        } catch (PDOException $e) {
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

$estados_item = getEstadosItem();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Actualizar Estado'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/mensajes.css">
    <link rel="stylesheet" href="../../assets/css/badges.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Actualizar Estado'); ?></h1>
            <a href="../proyectos/ver.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">
                ← <?php echo traducir('Volver'); ?>
            </a>
        </div>
        
        <div class="form-container">
            <h3><?php echo htmlspecialchars($item['nombre_item']); ?></h3>
            <p style="margin-bottom:1rem;">
                <strong><?php echo traducir('Estado'); ?>:</strong> 
                <span class="estado-badge estado-<?php echo $item['estado']; ?>">
                    <?php echo $estados_item[$item['estado']] ?? $item['estado']; ?>
                </span>
            </p>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="estado"><?php echo traducir('Estado'); ?></label>
                    <select id="estado" name="estado" required>
                        <option value="">-- <?php echo traducir('Seleccionar'); ?> --</option>
                        <?php foreach ($estados_permitidos as $key => $value): ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fecha_requerida"><?php echo traducir('Fecha Requerida'); ?></label>
                    <input type="date" id="fecha_requerida" name="fecha_requerida" 
                           value="<?php echo $item['fecha_requerida']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="comentario"><?php echo traducir('Comentario'); ?></label>
                    <textarea id="comentario" name="comentario" rows="3"></textarea>
                </div>
                
                <div style="display:flex; gap:0.75rem;">
                    <button type="submit" class="btn-primary"><?php echo traducir('Actualizar'); ?></button>
                    <a href="../proyectos/ver.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">
                        <?php echo traducir('Cancelar'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>