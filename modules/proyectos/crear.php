<?php
// modules/proyectos/crear.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador', 'supervisor', 'proyectista']) && !esMaster()) {
    redirigir('modules/proyectos/index.php?error=no_permitido');
}

$db = Database::getInstance()->getConnection();
$estados = getEstadosProyecto();

// Cargar candidatos a encargado
$stmt = $db->query("SELECT id, nombre_completo, tipo_usuario 
                    FROM usuarios 
                    WHERE tipo_usuario IN ('supervisor', 'proyectista') 
                      AND activo = 1 
                    ORDER BY nombre_completo");
$encargados_disponibles = $stmt->fetchAll();

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
    $encargado_id = !empty($_POST['encargado_id']) ? (int)$_POST['encargado_id'] : null;
    
    // Validaciones básicas
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
    if (!isset($estados[$estado])) {
        $estado = 'solicitado';
    }
    if (!tienePermiso(['directivo', 'gerenciador']) && !esMaster()) {
        $estado = 'solicitado';
    }
    
    // Validar PDF (si se subió)
    $pdf_subido = null;
    if (!empty($_FILES['propuesta_tecnica']['name'])) {
        $validacion = validarPdfSubido($_FILES['propuesta_tecnica']);
        if (!$validacion['ok']) {
            $errores[] = 'Propuesta técnica: ' . $validacion['error'];
        }
    }
    
    if (empty($errores)) {
        try {
            $db->beginTransaction();
            
            // Insertar proyecto
            $stmt = $db->prepare("
                INSERT INTO proyectos 
                    (nombre, descripcion, fecha_inicio, fecha_fin, estado, 
                     orden_compra, fecha_aprobacion, usuario_creacion, encargado_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nombre, $descripcion, $fecha_inicio, $fecha_fin, $estado,
                $orden_compra,
                !empty($fecha_aprobacion) ? $fecha_aprobacion : null,
                $_SESSION['usuario_id'],
                $encargado_id
            ]);
            
            $proyecto_id = $db->lastInsertId();
            
            // Guardar PDF si se subió
            if (!empty($_FILES['propuesta_tecnica']['name'])) {
                $resultado_pdf = guardarPdfPropuesta($_FILES['propuesta_tecnica'], $proyecto_id);
                if ($resultado_pdf['ok'] && empty($resultado_pdf['vacio'])) {
                    $stmt = $db->prepare("UPDATE proyectos 
                                          SET propuesta_tecnica = ?, 
                                              propuesta_nombre_original = ?, 
                                              propuesta_fecha_subida = NOW(),
                                              propuesta_subida_por = ?
                                          WHERE id = ?");
                    $stmt->execute([
                        $resultado_pdf['archivo'],
                        $resultado_pdf['nombre_original'],
                        $_SESSION['usuario_id'],
                        $proyecto_id
                    ]);
                } else {
                    // No detener la creación, solo registrar error
                    error_log("Error al guardar PDF de propuesta: " . ($resultado_pdf['error'] ?? 'desconocido'));
                }
            }
            
            // Historial
            $stmt = $db->prepare("
                INSERT INTO historial_proyectos 
                    (proyecto_id, estado_anterior, estado_nuevo, usuario_id, comentario)
                VALUES (?, NULL, ?, ?, ?)
            ");
            $stmt->execute([
                $proyecto_id, $estado, $_SESSION['usuario_id'], 'Proyecto creado'
            ]);
            
            $db->commit();
            
            redirigir('modules/proyectos/ver.php?id=' . $proyecto_id . '&mensaje=creado');
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errores[] = 'Error al crear el proyecto: ' . $e->getMessage();
        }
    }
}

$valores = [
    'nombre'            => $_POST['nombre'] ?? '',
    'descripcion'       => $_POST['descripcion'] ?? '',
    'fecha_inicio'      => $_POST['fecha_inicio'] ?? date('Y-m-d'),
    'fecha_fin'         => $_POST['fecha_fin'] ?? date('Y-m-d', strtotime('+30 days')),
    'estado'            => $_POST['estado'] ?? 'solicitado',
    'orden_compra'      => $_POST['orden_compra'] ?? '',
    'fecha_aprobacion'  => $_POST['fecha_aprobacion'] ?? '',
    'encargado_id'      => $_POST['encargado_id'] ?? '',
];

$puede_cambiar_estado = tienePermiso(['directivo', 'gerenciador']) || esMaster();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Crear Proyecto'); ?> - Sistema</title>
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
            <h1><?php echo traducir('Crear Proyecto'); ?></h1>
            <a href="<?php echo url('modules/proyectos/index.php'); ?>" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
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
                    <label for="propuesta_tecnica">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Proposta Técnica (PDF)' : 'Propuesta Técnica (PDF)'; ?>
                    </label>
                    <div class="file-upload-wrapper">
                        <input type="file" 
                               id="propuesta_tecnica" 
                               name="propuesta_tecnica" 
                               accept="application/pdf,.pdf"
                               class="file-input">
                        <div class="file-upload-info">
                            <span class="file-name" id="file-name"><?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum arquivo selecionado' : 'Ningún archivo seleccionado'; ?></span>
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
                               value="<?php echo htmlspecialchars($valores['fecha_aprobacion']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="orden_compra"><?php echo traducir('Número de Orden de Compra'); ?></label>
                        <input type="text" id="orden_compra" name="orden_compra" 
                               value="<?php echo htmlspecialchars($valores['orden_compra']); ?>"
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
                        💾 <?php echo traducir('Crear Proyecto'); ?>
                    </button>
                    <a href="<?php echo url('modules/proyectos/index.php'); ?>" class="btn-secondary">
                        <?php echo traducir('Cancelar'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Mostrar el nombre del archivo seleccionado
    const fileInput = document.getElementById('propuesta_tecnica');
    const fileName = document.getElementById('file-name');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Validar tipo
                if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                    alert('<?php echo $_SESSION['idioma'] == 'pt' ? 'Apenas arquivos PDF são permitidos' : 'Solo se permiten archivos PDF'; ?>');
                    this.value = '';
                    fileName.textContent = '<?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum arquivo selecionado' : 'Ningún archivo seleccionado'; ?>';
                    return;
                }
                
                // Validar tamaño (20 MB)
                if (file.size > 20 * 1024 * 1024) {
                    alert('<?php echo $_SESSION['idioma'] == 'pt' ? 'O arquivo excede 20 MB' : 'El archivo supera 20 MB'; ?>');
                    this.value = '';
                    fileName.textContent = '<?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum arquivo selecionado' : 'Ningún archivo seleccionado'; ?>';
                    return;
                }
                
                fileName.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                fileName.style.color = '#27ae60';
            }
        });
    }
    
    // Validar fechas
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