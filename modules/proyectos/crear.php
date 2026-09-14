<?php
// modules/proyectos/crear.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

// Permisos: directivo, gerenciador, supervisor y proyectista pueden crear proyectos
if (!tienePermiso(['directivo', 'gerenciador', 'supervisor', 'proyectista']) && !esMaster()) {
    header('Location: index.php?error=no_permitido');
    exit();
}

$db = Database::getInstance()->getConnection();
$estados = getEstadosProyecto();

$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitizar y validar
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $estado = $_POST['estado'] ?? 'solicitado';
    $orden_compra = trim($_POST['orden_compra'] ?? '') ?: null;
    $fecha_aprobacion = $_POST['fecha_aprobacion'] ?? null;
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = 'El nombre del proyecto es obligatorio';
    }
    
    if (empty($fecha_inicio)) {
        $errores[] = 'La fecha de inicio es obligatoria';
    }
    
    if (empty($fecha_fin)) {
        $errores[] = 'La fecha de fin es obligatoria';
    }
    
    if (!empty($fecha_inicio) && !empty($fecha_fin) && $fecha_fin < $fecha_inicio) {
        $errores[] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
    }
    
    // Validar que el estado sea válido
    if (!isset($estados[$estado])) {
        $estado = 'solicitado';
    }
    
    // Solo directivos/gerenciadores/Master pueden establecer un estado distinto a "solicitado"
    if (!tienePermiso(['directivo', 'gerenciador']) && !esMaster()) {
        $estado = 'solicitado';
    }
    
    // Validar fecha de aprobación (solo si el estado es aprobado por cliente o posterior)
    if (!empty($fecha_aprobacion)) {
        if ($fecha_aprobacion < $fecha_inicio) {
            $errores[] = 'La fecha de aprobación no puede ser anterior a la fecha de inicio';
        }
    }
    
    // Si no hay errores, guardar
    if (empty($errores)) {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO proyectos 
                    (nombre, descripcion, fecha_inicio, fecha_fin, estado, 
                     orden_compra, fecha_aprobacion, usuario_creacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nombre, 
                $descripcion, 
                $fecha_inicio, 
                $fecha_fin, 
                $estado, 
                $orden_compra, 
                !empty($fecha_aprobacion) ? $fecha_aprobacion : null,
                $_SESSION['usuario_id']
            ]);
            
            $proyecto_id = $db->lastInsertId();
            
            // Guardar en historial
            $stmt = $db->prepare("
                INSERT INTO historial_proyectos 
                    (proyecto_id, estado_anterior, estado_nuevo, usuario_id, comentario)
                VALUES (?, NULL, ?, ?, ?)
            ");
            $stmt->execute([
                $proyecto_id,
                $estado,
                $_SESSION['usuario_id'],
                'Proyecto creado'
            ]);
            
            $db->commit();
            
            header('Location: ver.php?id=' . $proyecto_id . '&mensaje=creado');
            exit();
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errores[] = 'Error al crear el proyecto: ' . $e->getMessage();
        }
    }
}

// Valores por defecto para repoblar el formulario
$valores = [
    'nombre'            => $_POST['nombre'] ?? '',
    'descripcion'       => $_POST['descripcion'] ?? '',
    'fecha_inicio'      => $_POST['fecha_inicio'] ?? date('Y-m-d'),
    'fecha_fin'         => $_POST['fecha_fin'] ?? date('Y-m-d', strtotime('+30 days')),
    'estado'            => $_POST['estado'] ?? 'solicitado',
    'orden_compra'      => $_POST['orden_compra'] ?? '',
    'fecha_aprobacion'  => $_POST['fecha_aprobacion'] ?? '',
];

$puede_cambiar_estado = tienePermiso(['directivo', 'gerenciador']) || esMaster();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Crear Proyecto'); ?> - Sistema</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/navbar.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/mensajes.css">
    <link rel="stylesheet" href="../../assets/css/badges.css">
    <link rel="stylesheet" href="../../assets/css/proyectos.css">
    <link rel="stylesheet" href="../../assets/css/notificaciones.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Crear Proyecto'); ?></h1>
            <a href="index.php" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
        </div>
        
        <?php if (!empty($errores)): ?>
            <div class="error-message">
                <strong><?php echo $_SESSION['idioma'] == 'pt' ? 'Erros encontrados:' : 'Errores encontrados:'; ?></strong>
                <ul style="margin: 0.5rem 0 0 1.5rem;">
                    <?php foreach ($errores as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="" id="form-proyecto">
                
                <!-- ===== Nombre ===== -->
                <div class="form-group">
                    <label for="nombre">
                        <?php echo traducir('Nombre del Proyecto'); ?> *
                    </label>
                    <input type="text" 
                           id="nombre" 
                           name="nombre" 
                           value="<?php echo htmlspecialchars($valores['nombre']); ?>"
                           placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Ex: Reforma do escritório' : 'Ej: Reforma de oficina'; ?>"
                           maxlength="200"
                           required 
                           autofocus>
                </div>
                
                <!-- ===== Descripción ===== -->
                <div class="form-group">
                    <label for="descripcion"><?php echo traducir('Descripción'); ?></label>
                    <textarea id="descripcion" 
                              name="descripcion" 
                              rows="3"
                              placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Descrição do projeto...' : 'Descripción del proyecto...'; ?>"><?php echo htmlspecialchars($valores['descripcion']); ?></textarea>
                </div>
                
                <!-- ===== Fechas ===== -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_inicio"><?php echo traducir('Fecha Inicio'); ?> *</label>
                        <input type="date" 
                               id="fecha_inicio" 
                               name="fecha_inicio" 
                               value="<?php echo htmlspecialchars($valores['fecha_inicio']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_fin"><?php echo traducir('Fecha Fin'); ?> *</label>
                        <input type="date" 
                               id="fecha_fin" 
                               name="fecha_fin" 
                               value="<?php echo htmlspecialchars($valores['fecha_fin']); ?>" 
                               required>
                    </div>
                </div>
                
                <!-- ===== Estado y Fecha de Aprobación ===== -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="estado">
                            <?php echo traducir('Estado'); ?>
                            <?php if (!$puede_cambiar_estado): ?>
                                <small style="color:#7f8c8d; font-weight:normal;">
                                    (<?php echo $_SESSION['idioma'] == 'pt' ? 'somente leitura' : 'solo lectura'; ?>)
                                </small>
                            <?php endif; ?>
                        </label>
                        <select id="estado" 
                                name="estado" 
                                <?php echo !$puede_cambiar_estado ? 'disabled' : ''; ?>>
                            <?php foreach ($estados as $key => $value): ?>
                                <option value="<?php echo $key; ?>" 
                                    <?php echo $valores['estado'] == $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$puede_cambiar_estado): ?>
                            <input type="hidden" name="estado" value="<?php echo htmlspecialchars($valores['estado']); ?>">
                            <small style="color:#7f8c8d; display:block; margin-top:0.25rem;">
                                <?php echo $_SESSION['idioma'] == 'pt' 
                                    ? 'Apenas diretores e gerentes podem alterar o status.'
                                    : 'Solo directivos y gerenciadores pueden cambiar el estado.'; ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_aprobacion"><?php echo traducir('Fecha Aprobación'); ?></label>
                        <input type="date" 
                               id="fecha_aprobacion" 
                               name="fecha_aprobacion" 
                               value="<?php echo htmlspecialchars($valores['fecha_aprobacion']); ?>">
                    </div>
                </div>
                
                <!-- ===== Orden de Compra ===== -->
                <div class="form-group">
                    <label for="orden_compra"><?php echo traducir('Número de Orden de Compra'); ?></label>
                    <input type="text" 
                           id="orden_compra" 
                           name="orden_compra" 
                           value="<?php echo htmlspecialchars($valores['orden_compra']); ?>"
                           placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Ex: OC-2026-001' : 'Ej: OC-2026-001'; ?>"
                           maxlength="50">
                </div>
                
                <!-- ===== Botones ===== -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        💾 <?php echo traducir('Crear Proyecto'); ?>
                    </button>
                    <a href="index.php" class="btn-secondary">
                        <?php echo traducir('Cancelar'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Validación cliente: fecha_fin >= fecha_inicio
    document.getElementById('form-proyecto').addEventListener('submit', function(e) {
        const inicio = document.getElementById('fecha_inicio').value;
        const fin = document.getElementById('fecha_fin').value;
        const aprob = document.getElementById('fecha_aprobacion').value;
        
        if (inicio && fin && fin < inicio) {
            e.preventDefault();
            alert('<?php echo $_SESSION["idioma"] == "pt" 
                ? "A data de término não pode ser anterior à data de início." 
                : "La fecha de fin no puede ser anterior a la fecha de inicio."; ?>');
            return false;
        }
        
        if (inicio && aprob && aprob < inicio) {
            e.preventDefault();
            alert('<?php echo $_SESSION["idioma"] == "pt" 
                ? "A data de aprovação não pode ser anterior à data de início." 
                : "La fecha de aprobación no puede ser anterior a la fecha de inicio."; ?>');
            return false;
        }
    });
    
    // Auto-ajustar fecha_fin cuando cambia fecha_inicio (solo si fecha_fin < fecha_inicio)
    document.getElementById('fecha_inicio').addEventListener('change', function() {
        const fin = document.getElementById('fecha_fin');
        if (fin.value && fin.value < this.value) {
            fin.value = this.value;
        }
    });
    </script>
    
    <script src="../../assets/js/notificaciones.js"></script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>