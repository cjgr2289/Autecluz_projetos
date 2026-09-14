<?php
// modules/proyectos/editar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

// Permisos: directivo, gerenciador, supervisor y proyectista pueden editar
if (!tienePermiso(['directivo', 'gerenciador', 'supervisor', 'proyectista']) && !esMaster()) {
    header('Location: index.php?error=no_permitido');
    exit();
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

if (!$id) {
    header('Location: index.php');
    exit();
}

// Obtener el proyecto
$stmt = $db->prepare("SELECT * FROM proyectos WHERE id = ?");
$stmt->execute([$id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    header('Location: index.php');
    exit();
}

$estados = getEstadosProyecto();
$puede_cambiar_estado = tienePermiso(['directivo', 'gerenciador']) || esMaster();

$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitizar y validar
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $estado_anterior = $proyecto['estado'];
    $estado = $_POST['estado'] ?? $estado_anterior;
    $orden_compra = trim($_POST['orden_compra'] ?? '') ?: null;
    $fecha_aprobacion = $_POST['fecha_aprobacion'] ?? null;
    
    // Solo directivos/gerenciadores pueden cambiar el estado
    if (!$puede_cambiar_estado) {
        $estado = $estado_anterior;
    }
    
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
    
    if (!empty($fecha_aprobacion) && $fecha_aprobacion < $fecha_inicio) {
        $errores[] = 'La fecha de aprobación no puede ser anterior a la fecha de inicio';
    }
    
    // Validar que el estado sea válido
    if (!isset($estados[$estado])) {
        $errores[] = 'Estado no válido';
    }
    
    // Si no hay errores, actualizar
    if (empty($errores)) {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                UPDATE proyectos 
                SET nombre = ?, 
                    descripcion = ?, 
                    fecha_inicio = ?, 
                    fecha_fin = ?, 
                    estado = ?, 
                    orden_compra = ?, 
                    fecha_aprobacion = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $nombre, 
                $descripcion, 
                $fecha_inicio, 
                $fecha_fin, 
                $estado, 
                $orden_compra, 
                !empty($fecha_aprobacion) ? $fecha_aprobacion : null,
                $id
            ]);
            
            // Si cambió el estado, guardar historial y aplicar reglas automáticas
            if ($estado !== $estado_anterior) {
                $stmt = $db->prepare("
                    INSERT INTO historial_proyectos 
                        (proyecto_id, estado_anterior, estado_nuevo, usuario_id, comentario)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id,
                    $estado_anterior,
                    $estado,
                    $_SESSION['usuario_id'],
                    'Cambio de estado en edición'
                ]);
                
                // REGLA AUTOMÁTICA:
                // Cuando el proyecto pasa a "aprovado_cliente",
                // todos los items en estado "solicitado" pasan a "pendiente"
                if ($estado === 'aprovado_cliente' && $estado_anterior !== 'aprovado_cliente') {
                    $stmt = $db->prepare("
                        UPDATE items_proyecto 
                        SET estado = 'pendiente' 
                        WHERE proyecto_id = ? AND estado = 'solicitado'
                    ");
                    $stmt->execute([$id]);
                    
                    // Registrar el cambio automático en el historial de items
                    $items_cambiados = $stmt->rowCount();
                    if ($items_cambiados > 0) {
                        $stmt = $db->prepare("
                            INSERT INTO historial_items 
                                (item_id, estado_anterior, estado_nuevo, usuario_id, comentario)
                            SELECT id, 'solicitado', 'pendiente', ?, ?
                            FROM items_proyecto 
                            WHERE proyecto_id = ? AND estado = 'pendiente'
                        ");
                        $stmt->execute([
                            $_SESSION['usuario_id'],
                            'Cambio automático: proyecto aprobado por cliente',
                            $id
                        ]);
                    }
                }
                
                // Si el proyecto vuelve a un estado anterior a "aprovado_cliente",
                // los items "pendiente" podrían volver a "solicitado" (opcional)
                // Descomentar si se quiere esta regla:
                /*
                if (in_array($estado, ['solicitado', 'orçado', 'pendente_aprovacion_cliente'])) {
                    $stmt = $db->prepare("
                        UPDATE items_proyecto 
                        SET estado = 'solicitado' 
                        WHERE proyecto_id = ? AND estado = 'pendiente'
                    ");
                    $stmt->execute([$id]);
                }
                */
            }
            
            $db->commit();
            
            header('Location: ver.php?id=' . $id . '&mensaje=actualizado');
            exit();
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errores[] = 'Error al actualizar el proyecto: ' . $e->getMessage();
        }
    }
    
    // Repoblar valores con los datos enviados
    $valores = [
        'nombre'            => $nombre,
        'descripcion'       => $descripcion,
        'fecha_inicio'      => $fecha_inicio,
        'fecha_fin'         => $fecha_fin,
        'estado'            => $estado,
        'orden_compra'      => $orden_compra,
        'fecha_aprobacion'  => $fecha_aprobacion,
    ];
} else {
    // Valores originales del proyecto
    $valores = [
        'nombre'            => $proyecto['nombre'],
        'descripcion'       => $proyecto['descripcion'],
        'fecha_inicio'      => $proyecto['fecha_inicio'],
        'fecha_fin'         => $proyecto['fecha_fin'],
        'estado'            => $proyecto['estado'],
        'orden_compra'      => $proyecto['orden_compra'],
        'fecha_aprobacion'  => $proyecto['fecha_aprobacion'],
    ];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Editar Proyecto'); ?> - <?php echo htmlspecialchars($proyecto['nombre']); ?></title>
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
            <h1><?php echo traducir('Editar Proyecto'); ?></h1>
            <div>
                <a href="ver.php?id=<?php echo $id; ?>" class="btn-secondary">
                    ← <?php echo traducir('Volver'); ?>
                </a>
            </div>
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
        
        <!-- Info del proyecto -->
        <div class="info-message">
            <strong>#<?php echo $id; ?></strong> — <?php echo htmlspecialchars($proyecto['nombre']); ?>
            <?php if ($proyecto['orden_compra']): ?>
                <span style="margin-left:1rem;">
                    <strong>O.C.:</strong> <?php echo htmlspecialchars($proyecto['orden_compra']); ?>
                </span>
            <?php endif; ?>
        </div>
        
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
                           maxlength="200"
                           required 
                           autofocus>
                </div>
                
                <!-- ===== Descripción ===== -->
                <div class="form-group">
                    <label for="descripcion"><?php echo traducir('Descripción'); ?></label>
                    <textarea id="descripcion" 
                              name="descripcion" 
                              rows="3"><?php echo htmlspecialchars($valores['descripcion']); ?></textarea>
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
                
                <!-- ===== Estado actual + Selector ===== -->
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
                        
                        <!-- Mostrar estado actual como badge -->
                        <div style="margin-bottom:0.5rem;">
                            <span class="estado-badge estado-<?php echo $proyecto['estado']; ?>">
                                <?php echo $estados[$proyecto['estado']] ?? $proyecto['estado']; ?>
                            </span>
                        </div>
                        
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
                        
                        <?php if ($puede_cambiar_estado && $valores['estado'] !== $proyecto['estado']): ?>
                            <small style="color:#e67e22; display:block; margin-top:0.25rem; font-weight:600;">
                                ⚠ <?php echo $_SESSION['idioma'] == 'pt' 
                                    ? 'O status será alterado ao salvar.'
                                    : 'El estado será cambiado al guardar.'; ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_aprobacion"><?php echo traducir('Fecha Aprobación'); ?></label>
                        <input type="date" 
                               id="fecha_aprobacion" 
                               name="fecha_aprobacion" 
                               value="<?php echo htmlspecialchars($valores['fecha_aprobacion'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- ===== Orden de Compra ===== -->
                <div class="form-group">
                    <label for="orden_compra"><?php echo traducir('Número de Orden de Compra'); ?></label>
                    <input type="text" 
                           id="orden_compra" 
                           name="orden_compra" 
                           value="<?php echo htmlspecialchars($valores['orden_compra'] ?? ''); ?>"
                           placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Ex: OC-2026-001' : 'Ej: OC-2026-001'; ?>"
                           maxlength="50">
                </div>
                
                <!-- ===== Advertencia si cambia a aprobado por cliente ===== -->
                <?php if ($puede_cambiar_estado): ?>
                <div id="aviso-aprobado" style="display:none;" class="info-message" data-idioma="<?php echo $_SESSION['idioma']; ?>">
                    <strong>⚠ <?php echo $_SESSION['idioma'] == 'pt' ? 'Atenção:' : 'Atención:'; ?></strong>
                    <span id="aviso-aprobado-texto"></span>
                </div>
                <?php endif; ?>
                
                <!-- ===== Botones ===== -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        💾 <?php echo traducir('Actualizar'); ?>
                    </button>
                    <a href="ver.php?id=<?php echo $id; ?>" class="btn-secondary">
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
    
    // Auto-ajustar fecha_fin cuando cambia fecha_inicio
    document.getElementById('fecha_inicio').addEventListener('change', function() {
        const fin = document.getElementById('fecha_fin');
        if (fin.value && fin.value < this.value) {
            fin.value = this.value;
        }
    });
    
    // Detectar cambio a "aprovado_cliente" y mostrar advertencia
    <?php if ($puede_cambiar_estado): ?>
    const estadoSelect = document.getElementById('estado');
    const aviso = document.getElementById('aviso-aprobado');
    const avisoTexto = document.getElementById('aviso-aprobado-texto');
    const estadoOriginal = '<?php echo $proyecto['estado']; ?>';
    const idioma = '<?php echo $_SESSION['idioma']; ?>';
    
    function verificarCambioEstado() {
        const nuevoEstado = estadoSelect.value;
        
        if (nuevoEstado === 'aprovado_cliente' && estadoOriginal !== 'aprovado_cliente') {
            const msg = idioma === 'pt'
                ? 'Ao salvar, todos os itens "Solicitado" deste projeto passarão automaticamente para "Pendente".'
                : 'Al guardar, todos los items "Solicitado" de este proyecto pasarán automáticamente a "Pendiente".';
            avisoTexto.textContent = msg;
            aviso.style.display = 'block';
        } else if (nuevoEstado !== 'aprovado_cliente' && estadoOriginal === 'aprovado_cliente') {
            const msg = idioma === 'pt'
                ? 'Você está revertendo o status de "Aprovado pelo Cliente". Verifique se os itens precisam ser reajustados.'
                : 'Estás revirtiendo el estado de "Aprobado por Cliente". Verifica si los items necesitan reajustarse.';
            avisoTexto.textContent = msg;
            aviso.style.display = 'block';
        } else {
            aviso.style.display = 'none';
        }
    }
    
    estadoSelect.addEventListener('change', verificarCambioEstado);
    verificarCambioEstado(); // Verificar al cargar (por si viene con cambio previo)
    <?php endif; ?>
    </script>
    
    <script src="../../assets/js/notificaciones.js"></script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>