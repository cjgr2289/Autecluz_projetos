<?php
// modules/proyectos/editar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador', 'supervisor', 'proyectista'])) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM proyectos WHERE id = ?");
$stmt->execute([$id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    header('Location: index.php');
    exit();
}

$estados = getEstadosProyecto();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $estado_anterior = $proyecto['estado'];
    $estado = $_POST['estado'] ?? 'solicitado';
    $orden_compra = $_POST['orden_compra'] ?? null;
    $fecha_aprobacion = $_POST['fecha_aprobacion'] ?: null;
    
    if (empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
        $error = 'Los campos nombre, fecha inicio y fecha fin son obligatorios';
    } else {
        try {
            // Solo directivo/gerenciador pueden cambiar estado
            if (!tienePermiso(['directivo', 'gerenciador'])) {
                $estado = $estado_anterior;
            }
            
            $db->beginTransaction();
            
            $stmt = $db->prepare("UPDATE proyectos 
                                  SET nombre=?, descripcion=?, fecha_inicio=?, fecha_fin=?, 
                                      estado=?, orden_compra=?, fecha_aprobacion=? 
                                  WHERE id=?");
            $stmt->execute([$nombre, $descripcion, $fecha_inicio, $fecha_fin, 
                           $estado, $orden_compra, $fecha_aprobacion, $id]);
            
            // Si cambió el estado, guardar historial y aplicar reglas
            if ($estado !== $estado_anterior) {
                $stmt = $db->prepare("INSERT INTO historial_proyectos 
                                      (proyecto_id, estado_anterior, estado_nuevo, usuario_id, comentario)
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $estado_anterior, $estado, $_SESSION['usuario_id'], 
                               'Cambio de estado en edición']);
                
                // Regla automática: al pasar a "aprovado_cliente", los items pasan a "pendiente"
                // si estaban en "solicitado"
                if ($estado === 'aprovado_cliente' && $estado_anterior !== 'aprovado_cliente') {
                    $stmt = $db->prepare("UPDATE items_proyecto 
                                          SET estado = 'pendiente' 
                                          WHERE proyecto_id = ? AND estado = 'solicitado'");
                    $stmt->execute([$id]);
                }
            }
            
            $db->commit();
            header('Location: index.php?mensaje=actualizado');
            exit();
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title>Editar Proyecto</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1>Editar Proyecto</h1>
            <a href="index.php" class="btn-secondary">Volver</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label>Nombre del Proyecto *</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($proyecto['nombre']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3"><?php echo htmlspecialchars($proyecto['descripcion'] ?? ''); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha Inicio *</label>
                        <input type="date" name="fecha_inicio" value="<?php echo $proyecto['fecha_inicio']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Fin *</label>
                        <input type="date" name="fecha_fin" value="<?php echo $proyecto['fecha_fin']; ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" <?php echo !tienePermiso(['directivo', 'gerenciador']) ? 'disabled' : ''; ?>>
                            <?php foreach ($estados as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $proyecto['estado'] == $key ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!tienePermiso(['directivo', 'gerenciador'])): ?>
                            <input type="hidden" name="estado" value="<?php echo $proyecto['estado']; ?>">
                            <small>Solo directivos y gerenciadores pueden cambiar el estado</small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Fecha Aprobación</label>
                        <input type="date" name="fecha_aprobacion" value="<?php echo $proyecto['fecha_aprobacion'] ?? ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Número de Orden de Compra</label>
                    <input type="text" name="orden_compra" value="<?php echo htmlspecialchars($proyecto['orden_compra'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-primary">Actualizar</button>
            </form>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>