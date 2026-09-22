<?php
// modules/proyectos/editar.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador', 'supervisor', 'proyectista']) && !esMaster()) {
    redirigir('modules/proyectos/index.php?error=no_permitido');
}

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

if (!$id) {
    redirigir('modules/proyectos/index.php');
}

$stmt = $db->prepare("SELECT p.*, 
                             u.nombre_completo as propuesta_subida_por_nombre
                      FROM proyectos p
                      LEFT JOIN usuarios u ON p.propuesta_subida_por = u.id
                      WHERE p.id = ?");
$stmt->execute([$id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    redirigir('modules/proyectos/index.php');
}

$stmt = $db->query("SELECT id, nombre_completo, tipo_usuario 
                    FROM usuarios 
                    WHERE tipo_usuario IN ('supervisor', 'proyectista') 
                      AND activo = 1 
                    ORDER BY nombre_completo");
$encargados_disponibles = $stmt->fetchAll();

$estados = getEstadosProyecto();
$puede_cambiar_estado = tienePermiso(['directivo', 'gerenciador']) || esMaster();

$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $estado_anterior = $proyecto['estado'];
    $estado = $_POST['estado'] ?? $estado_anterior;
    $orden_compra = trim($_POST['orden_compra'] ?? '') ?: null;
    $fecha_aprobacion = $_POST['fecha_aprobacion'] ?? null;
    $encargado_id = !empty($_POST['encargado_id']) ? (int)$_POST['encargado_id'] : null;
    $eliminar_pdf = !empty($_POST['eliminar_pdf']);
    
    if (!$puede_cambiar_estado) {
        $estado = $estado_anterior;
    }
    
    // Validaciones
    if (empty($nombre)) $errores[] = 'El nombre del proyecto es obligatorio';
    if (empty($fecha_inicio)) $errores[] = 'La fecha de inicio es obligatoria';
    if (empty($fecha_fin)) $errores[] = 'La fecha de fin es obligatoria';
    if (!empty($fecha_inicio) && !empty($fecha_fin) && $fecha_fin < $fecha_inicio) {
        $errores[] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
    }
    if (!isset($estados[$estado])) $errores[] = 'Estado no válido';
    
    // Validar PDF si se subió uno nuevo
    $subir_pdf_nuevo = !empty($_FILES['propuesta_tecnica']['name']);
    if ($subir_pdf_nuevo) {
        $validacion = validarPdfSubido($_FILES['propuesta_tecnica']);
        if (!$validacion['ok']) {
            $errores[] = 'Propuesta técnica: ' . $validacion['error'];
        }
    }
    
    if (empty($errores)) {
        try {
            $db->beginTransaction();
            
            // Manejar PDF
            $pdf_actual = $proyecto['propuesta_tecnica'];
            $nombre_original = $proyecto['propuesta_nombre_original'];
            $fecha_subida = $proyecto['propuesta_fecha_subida'];
            $subida_por = $proyecto['propuesta_subida_por'];
            
            // Si el usuario pidió eliminar el PDF
            if ($eliminar_pdf && $pdf_actual) {
                eliminarPdfPropuesta($pdf_actual);
                $pdf_actual = null;
                $nombre_original = null;
                $fecha_subida = null;
                $subida_por = null;
            }
            
            // Si se subió un PDF nuevo, reemplazar el anterior
            if ($subir_pdf_nuevo) {
                if ($pdf_actual) {
                    eliminarPdfPropuesta($pdf_actual);
                }
                
                $resultado_pdf = guardarPdfPropuesta($_FILES['propuesta_tecnica'], $id);
                if ($resultado_pdf['ok'] && empty($resultado_pdf['vacio'])) {
                    $pdf_actual = $resultado_pdf['archivo'];
                    $nombre_original = $resultado_pdf['nombre_original'];
                    $fecha_subida = date('Y-m-d H:i:s');
                    $subida_por = $_SESSION['usuario_id'];
                } else {
                    error_log("Error al guardar PDF: " . ($resultado_pdf['error'] ?? 'desconocido'));
                }
            }
            
            $stmt = $db->prepare("
                UPDATE proyectos 
                SET nombre = ?, 
                    descripcion = ?, 
                    fecha_inicio = ?, 
                    fecha_fin = ?, 
                    estado = ?, 
                    orden_compra = ?, 
                    fecha_aprobacion = ?,
                    encargado_id = ?,
                    propuesta_tecnica = ?,
                    propuesta_nombre_original = ?,
                    propuesta_fecha_subida = ?,
                    propuesta_subida_por = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $nombre, $descripcion, $fecha_inicio, $fecha_fin,
                $estado, $orden_compra,
                !empty($fecha_aprobacion) ? $fecha_aprobacion : null,
                $encargado_id,
                $pdf_actual,
                $nombre_original,
                $fecha_subida,
                $subida_por,
                $id
            ]);
            
            // Historial si cambió el estado
            if ($estado !== $estado_anterior) {
                $stmt = $db->prepare("
                    INSERT INTO historial_proyectos 
                        (proyecto_id, estado_anterior, estado_nuevo, usuario_id, comentario)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id, $estado_anterior, $estado,
                    $_SESSION['usuario_id'], 'Cambio de estado en edición'
                ]);
                
                // Regla automática: aprovado_cliente → items solicitado → pendiente
                if ($estado === 'aprovado_cliente' && $estado_anterior !== 'aprovado_cliente') {
                    $stmt = $db->prepare("UPDATE items_proyecto 
                                          SET estado = 'pendiente' 
                                          WHERE proyecto_id = ? AND estado = 'solicitado'");
                    $stmt->execute([$id]);
                    
                    if ($stmt->rowCount() > 0) {
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
            }
            
            $db->commit();
            
            redirigir('modules/proyectos/ver.php?id=' . $id . '&mensaje=actualizado');
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errores[] = 'Error al actualizar el proyecto: ' . $e->getMessage();
        }
    }
    
    $valores = [
        'nombre' => $nombre, 'descripcion' => $descripcion,
        'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin,
        'estado' => $estado, 'orden_compra' => $orden_compra,
        'fecha_aprobacion' => $fecha_aprobacion, 'encargado_id' => $encargado_id,
    ];
} else {
    $valores = [
        'nombre' => $proyecto['nombre'], 'descripcion' => $proyecto['descripcion'],
        'fecha_inicio' => $proyecto['fecha_inicio'], 'fecha_fin' => $proyecto['fecha_fin'],
        'estado' => $proyecto['estado'], 'orden_compra' => $proyecto['orden_compra'],
        'fecha_aprobacion' => $proyecto['fecha_aprobacion'], 
        'encargado_id' => $proyecto['encargado_id'] ?? null,
    ];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Editar Proyecto'); ?> - <?php echo htmlspecialchars($proyecto['nombre']); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/proyectos.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Editar Proyecto'); ?></h1>
            <a href="<?php echo url('modules/proyectos/ver.php?id=' . $id); ?>" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
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
        
        <div class="info-message">
            <strong>#<?php echo $id; ?></strong> — <?php echo htmlspecialchars($proyecto['nombre']); ?>
        </div>
        
        <div class="form-container">
            <form method="POST" action="" id="form-proyecto" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="nombre"><?php echo traducir('Nombre del Proyecto'); ?> *</label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?php echo htmlspecialchars($valores['nombre']); ?>"
                           maxlength="200" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="descripcion"><?php echo traducir('Descripción'); ?></label>
                    <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($valores['descripcion']); ?></textarea>
                </div>
                
                <!-- ===== Propuesta técnica (PDF) ===== -->
                <div class="form-group">
                    <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Proposta Técnica (PDF)' : 'Propuesta Técnica (PDF)'; ?></label>
                    
                    <?php if ($proyecto['propuesta_tecnica']): ?>
                        <!-- Archivo actual -->
                        <div class="archivo-actual">
                            <div class="archivo-info">
                                <span class="archivo-icono">📄</span>
                                <div>
                                    <div class="archivo-nombre">
                                        <?php echo htmlspecialchars($proyecto['propuesta_nombre_original'] ?? 'propuesta.pdf'); ?>
                                    </div>
                                    <div class="archivo-meta">
                                        <?php echo getTamañoArchivo($proyecto['propuesta_tecnica']); ?>
                                        <?php if ($proyecto['propuesta_fecha_subida']): ?>
                                            · <?php echo formatearFecha($proyecto['propuesta_fecha_subida']); ?>
                                        <?php endif; ?>
                                        <?php if ($proyecto['propuesta_subida_por_nombre']): ?>
                                            · <?php echo htmlspecialchars($proyecto['propuesta_subida_por_nombre']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="archivo-acciones">
                                <a href="<?php echo getUrlArchivo($proyecto['propuesta_tecnica']); ?>" 
                                   target="_blank" 
                                   class="btn-secondary btn-sm">
                                    👁 <?php echo $_SESSION['idioma'] == 'pt' ? 'Ver' : 'Ver'; ?>
                                </a>
                                <label class="checkbox-eliminar">
                                    <input type="checkbox" name="eliminar_pdf" value="1" 
                                           onchange="toggleEliminar(this)">
                                    <?php echo $_SESSION['idioma'] == 'pt' ? 'Excluir' : 'Eliminar'; ?>
                                </label>
                            </div>
                        </div>
                        <div style="margin-top:0.75rem; font-size:0.85rem; color:#7f8c8d;">
                            <?php echo $_SESSION['idioma'] == 'pt' 
                                ? 'Para substituir, selecione um novo arquivo abaixo:' 
                                : 'Para reemplazar, seleccione un nuevo archivo abajo:'; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Input para subir nuevo archivo -->
                    <div class="file-upload-wrapper" style="margin-top:0.5rem;">
                        <input type="file" 
                               id="propuesta_tecnica" 
                               name="propuesta_tecnica" 
                               accept="application/pdf,.pdf"
                               class="file-input">
                        <div class="file-upload-info">
                            <span class="file-name" id="file-name">
                                <?php echo $proyecto['propuesta_tecnica'] 
                                    ? ($_SESSION['idioma'] == 'pt' ? 'Selecione para substituir' : 'Seleccione para reemplazar')
                                    : ($_SESSION['idioma'] == 'pt' ? 'Nenhum arquivo selecionado' : 'Ningún archivo seleccionado'); ?>
                            </span>
                            <span class="file-hint"><?php echo $_SESSION['idioma'] == 'pt' ? 'Máx. 20 MB, apenas PDF' : 'Máx. 20 MB, solo PDF'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_inicio"><?php echo traducir('Fecha Inicio'); ?> *</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" 
                               value="<?php echo htmlspecialchars($valores['fecha_inicio']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha_fin"><?php echo traducir('Fecha Fin'); ?> *</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" 
                               value="<?php echo htmlspecialchars($valores['fecha_fin']); ?>" required>
                    </div>
                </div>
                
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
                        <div style="margin-bottom:0.5rem;">
                            <span class="estado-badge estado-<?php echo $proyecto['estado']; ?>">
                                <?php echo $estados[$proyecto['estado']] ?? $proyecto['estado']; ?>
                            </span>
                        </div>
                        <select id="estado" name="estado" <?php echo !$puede_cambiar_estado ? 'disabled' : ''; ?>>
                            <?php foreach ($estados as $key => $value): ?>
                                <option value="<?php echo $key; ?>" 
                                    <?php echo $valores['estado'] == $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$puede_cambiar_estado): ?>
                            <input type="hidden" name="estado" value="<?php echo htmlspecialchars($valores['estado']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="fecha_aprobacion"><?php echo traducir('Fecha Aprobación'); ?></label>
                        <input type="date" id="fecha_aprobacion" name="fecha_aprobacion" 
                               value="<?php echo htmlspecialchars($valores['fecha_aprobacion'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="orden_compra"><?php echo traducir('Número de Orden de Compra'); ?></label>
                        <input type="text" id="orden_compra" name="orden_compra" 
                               value="<?php echo htmlspecialchars($valores['orden_compra'] ?? ''); ?>"
                               maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="encargado_id"><?php echo traducir('Encargado del Proyecto'); ?></label>
                        <select id="encargado_id" name="encargado_id">
                            <option value="">-- <?php echo traducir('Seleccione un encargado'); ?> --</option>
                            <?php foreach ($encargados_disponibles as $enc): ?>
                                <option value="<?php echo $enc['id']; ?>"
                                    <?php echo $valores['encargado_id'] == $enc['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($enc['nombre_completo']); ?>
                                    (<?php echo traducir(ucfirst($enc['tipo_usuario'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        💾 <?php echo traducir('Actualizar'); ?>
                    </button>
                    <a href="<?php echo url('modules/proyectos/ver.php?id=' . $id); ?>" class="btn-secondary">
                        <?php echo traducir('Cancelar'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    const fileInput = document.getElementById('propuesta_tecnica');
    const fileName = document.getElementById('file-name');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                    alert('<?php echo $_SESSION['idioma'] == 'pt' ? 'Apenas arquivos PDF são permitidos' : 'Solo se permiten archivos PDF'; ?>');
                    this.value = '';
                    return;
                }
                
                if (file.size > 20 * 1024 * 1024) {
                    alert('<?php echo $_SESSION['idioma'] == 'pt' ? 'O arquivo excede 20 MB' : 'El archivo supera 20 MB'; ?>');
                    this.value = '';
                    return;
                }
                
                fileName.textContent = '📄 ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                fileName.style.color = '#27ae60';
                fileName.style.fontWeight = '600';
            }
        });
    }
    
    function toggleEliminar(checkbox) {
        const archivoActual = document.querySelector('.archivo-actual');
        if (checkbox.checked) {
            archivoActual.style.opacity = '0.4';
            archivoActual.style.textDecoration = 'line-through';
        } else {
            archivoActual.style.opacity = '1';
            archivoActual.style.textDecoration = 'none';
        }
    }
    
    document.getElementById('form-proyecto').addEventListener('submit', function(e) {
        const inicio = document.getElementById('fecha_inicio').value;
        const fin = document.getElementById('fecha_fin').value;
        
        if (inicio && fin && fin < inicio) {
            e.preventDefault();
            alert('<?php echo $_SESSION["idioma"] == "pt" 
                ? "A data de término não pode ser anterior à data de início." 
                : "La fecha de fin no puede ser anterior a la fecha de inicio."; ?>');
            return false;
        }
    });
    </script>
    
    <script src="<?php echo url('assets/js/notificaciones.js'); ?>"></script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>