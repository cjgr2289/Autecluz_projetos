<?php
// modules/proyectos/crear.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador', 'supervisor', 'proyectista'])) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$estados = getEstadosProyecto();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $estado = $_POST['estado'] ?? 'solicitado';
    $orden_compra = $_POST['orden_compra'] ?? null;
    $fecha_aprobacion = $_POST['fecha_aprobacion'] ?? null;
    
    if (empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
        $error = 'Los campos nombre, fecha inicio y fecha fin son obligatorios';
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_fin, 
                                     estado, orden_compra, fecha_aprobacion, usuario_creacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nombre, $descripcion, $fecha_inicio, $fecha_fin, 
                           $estado, $orden_compra, $fecha_aprobacion, $_SESSION['usuario_id']]);
            
            header('Location: index.php?mensaje=creado');
            exit();
        } catch (PDOException $e) {
            $error = 'Error al crear el proyecto: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Crear Proyecto'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Crear Proyecto'); ?></h1>
            <a href="index.php" class="btn-secondary"><?php echo traducir('Volver'); ?></a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre"><?php echo traducir('Nombre del Proyecto'); ?> *</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label for="descripcion"><?php echo traducir('Descripción'); ?></label>
                    <textarea id="descripcion" name="descripcion" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_inicio"><?php echo traducir('Fecha Inicio'); ?> *</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_fin"><?php echo traducir('Fecha Fin'); ?> *</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="estado"><?php echo traducir('Estado'); ?></label>
                        <select id="estado" name="estado">
                            <?php foreach ($estados as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_aprobacion"><?php echo traducir('Fecha Aprobación'); ?></label>
                        <input type="date" id="fecha_aprobacion" name="fecha_aprobacion">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="orden_compra"><?php echo traducir('Número de Orden de Compra'); ?></label>
                    <input type="text" id="orden_compra" name="orden_compra">
                </div>
                
                <button type="submit" class="btn-primary"><?php echo traducir('Crear Proyecto'); ?></button>
            </form>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>