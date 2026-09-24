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

// Obtener el proyecto
$stmt = $db->prepare("SELECT * FROM proyectos WHERE id = ?");
$stmt->execute([$id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    redirigir('modules/proyectos/index.php');
}

// Candidatos a encargado
$stmt = $db->query("SELECT id, nombre_completo, tipo_usuario 
                    FROM usuarios 
                    WHERE tipo_usuario IN ('supervisor', 'proyectista') 
                      AND activo = 1 
                    ORDER BY nombre_completo");
$encargados_disponibles = $stmt->fetchAll();

// Clientes disponibles
$clientes_disponibles = obtenerClientes($db);

// Responsables del cliente actual
$responsables_cliente = !empty($proyecto['cliente_id']) 
    ? obtenerResponsablesCliente($db, $proyecto['cliente_id']) 
    : [];

$estados = getEstadosProyecto();
$puede_cambiar_estado = tienePermiso(['directivo', 'gerenciador']) || esMaster();

$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_solicitud = $_POST['fecha_solicitud'] ?? '';
    $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
    $cliente_responsable_id = !empty($_POST['cliente_responsable_id']) ? (int)$_POST['cliente_responsable_id'] : null;
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
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
    if (empty($fecha_solicitud)) $errores[] = 'La fecha de solicitud es obligatoria';
    if (empty($cliente_id)) $errores[] = 'Debe seleccionar un cliente';
    
    // Fecha inicio/fin solo obligatorias si el proyecto está aprobado o posterior
    $estados_con_fechas = ['aprovado_cliente', 'espera_orden_compra', 'comprando_materiales', 
                           'elaboracion', 'terminado', 'pendiente_cobro_cliente', 'finalizado'];
    
    if (in_array($estado, $estados_con_fechas)) {
        if (empty($fecha_inicio)) $errores[] = 'La fecha de inicio es obligatoria cuando el proyecto está aprobado';
        if (empty($fecha_fin)) $errores[] = 'La fecha de fin es obligatoria cuando el proyecto está aprobado';
    }
    
    if (!empty($fecha_inicio) && !empty($fecha_fin) && $fecha_fin < $fecha_inicio) {
        $errores[] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
    }
    
    if (!empty($fecha_solicitud) && !empty($fecha_inicio) && $fecha_inicio < $fecha_solicitud) {
        $errores[] = 'La fecha de inicio no puede ser anterior a la fecha de solicitud';
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
            
            if ($eliminar_pdf && $pdf_actual) {
                eliminarPdfPropuesta($pdf_actual);
                $pdf_actual = null;
                $nombre_original = null;
                $fecha_subida = null;
                $subida_por = null;
            }
            
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
                }
            }
            
            $stmt = $db->prepare("
                UPDATE proyectos 
                SET nombre = ?, 
                    descripcion = ?, 
                    fecha_solicitud = ?,
                    cliente_id = ?,
                    cliente_responsable_id = ?,
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
                $nombre, 
                $descripcion,
                $fecha_solicitud,
                $cliente_id,
                $cliente_responsable_id,
                !empty($fecha_inicio) ? $fecha_inicio : null,
                !empty($fecha_fin) ? $fecha_fin : null,
                $estado, 
                $orden_compra, 
                !empty($fecha_aprobacion) ? $fecha_aprobacion : null,
                $encargado_id,
                $pdf_actual,
                $nombre_original,
                $fecha_subida,
                $subida_por,
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
                    $id, $estado_anterior, $estado,
                    $_SESSION['usuario_id'], 'Cambio de estado en edición'
                ]);
                
                // Regla automática: aprovado_cliente → items solicitado → pendiente
                if ($estado === 'aprovado_cliente' && $estado_anterior !== 'aprovado_cliente') {
                    $stmt = $db->prepare("
                        UPDATE items_proyecto 
                        SET estado = 'pendiente' 
                        WHERE proyecto_id = ? AND estado = 'solicitado'
                    ");
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
        'nombre'                  => $nombre,
        'descripcion'             => $descripcion,
        'fecha_solicitud'         => $fecha_solicitud,
        'cliente_id'              => $cliente_id,
        'cliente_responsable_id'  => $cliente_responsable_id,
        'fecha_inicio'            => $fecha_inicio,
        'fecha_fin'               => $fecha_fin,
        'estado'                  => $estado,
        'orden_compra'            => $orden_compra,
        'fecha_aprobacion'        => $fecha_aprobacion,
        'encargado_id'            => $encargado_id,
    ];
} else {
    $valores = [
        'nombre'                  => $proyecto['nombre'],
        'descripcion'             => $proyecto['descripcion'],
        'fecha_solicitud'         => $proyecto['fecha_solicitud'],
        'cliente_id'              => $proyecto['cliente_id'],
        'cliente_responsable_id'  => $proyecto['cliente_responsable_id'],
        'fecha_inicio'            => $proyecto['fecha_inicio'],
        'fecha_fin'               => $proyecto['fecha_fin'],
        'estado'                  => $proyecto['estado'],
        'orden_compra'            => $proyecto['orden_compra'],
        'fecha_aprobacion'        => $proyecto['fecha_aprobacion'],
        'encargado_id'            => $proyecto['encargado_id'] ?? null,
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
            <div>
                <a href="<?php echo url('modules/proyectos/ver.php?id=' . $id); ?>" class="btn-secondary">
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
        
        <div class="info-message">
            <strong>#<?php echo $id; ?></strong> — <?php echo htmlspecialchars($proyecto['nombre']); ?>
            <?php if ($proyecto['orden_compra']): ?>
                <span style="margin-left:1rem;">
                    <strong>O.C.:</strong> <?php echo htmlspecialchars($proyecto['orden_compra']); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <div class="form-container">
            <form method="POST" action="" id="form-proyecto" enctype="multipart/form-data">
                
                <!-- ===== Nombre ===== -->
                <div class="form-group">
                    <label for="nombre"><?php echo traducir('Nombre del Proyecto'); ?> *</label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?php echo htmlspecialchars($valores['nombre']); ?>"
                           maxlength="200" required autofocus>
                </div>
                
                <!-- ===== Descripción ===== -->
                <div class="form-group">
                    <label for="descripcion"><?php echo traducir('Descripción'); ?></label>
                    <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($valores['descripcion']); ?></textarea>
                </div>
                
                <!-- ===== Fecha solicitud + Cliente + Responsable ===== -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_solicitud">
                            📅 <?php echo $_SESSION['idioma'] == 'pt' ? 'Data de Solicitação' : 'Fecha de Solicitud'; ?> *
                        </label>
                        <input type="date" id="fecha_solicitud" name="fecha_solicitud" 
                               value="<?php echo htmlspecialchars($valores['fecha_solicitud']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cliente_id">
                            🏢 <?php echo $_SESSION['idioma'] == 'pt' ? 'Cliente' : 'Cliente'; ?> *
                        </label>
                        <select id="cliente_id" name="cliente_id" required onchange="cargarResponsables()">
                            <option value="">-- <?php echo $_SESSION['idioma'] == 'pt' ? 'Selecione um cliente' : 'Seleccione un cliente'; ?> --</option>
                            <?php foreach ($clientes_disponibles as $c): ?>
                                <option value="<?php echo $c['id']; ?>"
                                    <?php echo $valores['cliente_id'] == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:#7f8c8d; display:block; margin-top:0.25rem;">
                            <a href="<?php echo url('modules/clientes/crear.php'); ?>" target="_blank">
                                + <?php echo $_SESSION['idioma'] == 'pt' ? 'Criar novo cliente' : 'Crear nuevo cliente'; ?>
                            </a>
                        </small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="cliente_responsable_id">
                        👤 <?php echo $_SESSION['idioma'] == 'pt' ? 'Solicitante do Cliente' : 'Solicitante del Cliente'; ?>
                    </label>
                    <select id="cliente_responsable_id" name="cliente_responsable_id">
                        <option value="">-- <?php echo $_SESSION['idioma'] == 'pt' ? 'Selecione um responsável' : 'Seleccione un responsable'; ?> --</option>
                        <?php foreach ($responsables_cliente as $r): ?>
                            <option value="<?php echo $r['id']; ?>"
                                <?php echo $valores['cliente_responsable_id'] == $r['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['nombre']); ?>
                                <?php if ($r['cargo']): ?> (<?php echo htmlspecialchars($r['cargo']); ?>)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- ===== Propuesta técnica (PDF) ===== -->
                <div class="form-group">
                    <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Proposta Técnica (PDF)' : 'Propuesta Técnica (PDF)'; ?></label>
                    
                    <?php if ($proyecto['propuesta_tecnica']): ?>
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
                                    </div>
                                </div>
                            </div>
                            <div class="archivo-acciones">
                                <a href="<?php echo url('modules/proyectos/ver_propuesta.php?id=' . $id); ?>" 
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
                    
                    <div class="file-upload-wrapper" style="margin-top:0.5rem;">
                        <input type="file" id="propuesta_tecnica" name="propuesta_tecnica" 
                               accept="application/pdf,.pdf" class="file-input">
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
                
                <!-- ===== Estado + Orden de Compra ===== -->
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
                        
                        <select id="estado" name="estado" 
                                <?php echo !$puede_cambiar_estado ? 'disabled' : ''; ?>
                                onchange="toggleFechas()">
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
                        <label for="orden_compra"><?php echo traducir('Número de Orden de Compra'); ?></label>
                        <input type="text" id="orden_compra" name="orden_compra" 
                               value="<?php echo htmlspecialchars($valores['orden_compra'] ?? ''); ?>"
                               maxlength="50">
                    </div>
                </div>
                
                <!-- ===== Fechas inicio/fin (solo si aprobado) ===== -->
                <div id="grupo-fechas-proyecto" style="display:none;">
                    <div class="info-message" style="margin-bottom:1rem;">
                        <strong>ℹ️ <?php echo $_SESSION['idioma'] == 'pt' ? 'Atenção:' : 'Atención:'; ?></strong>
                        <?php echo $_SESSION['idioma'] == 'pt'
                            ? 'Como o projeto está aprovado, indique as datas de início e término.'
                            : 'Como el proyecto está aprobado, indique las fechas de inicio y fin.'; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_inicio">📅 <?php echo traducir('Fecha Inicio'); ?></label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" 
                                   value="<?php echo htmlspecialchars($valores['fecha_inicio'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="fecha_fin">🏁 <?php echo traducir('Fecha Fin'); ?></label>
                            <input type="date" id="fecha_fin" name="fecha_fin" 
                                   value="<?php echo htmlspecialchars($valores['fecha_fin'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_aprobacion">✅ <?php echo traducir('Fecha Aprobación'); ?></label>
                        <input type="date" id="fecha_aprobacion" name="fecha_aprobacion" 
                               value="<?php echo htmlspecialchars($valores['fecha_aprobacion'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="encargado_id">🎯 <?php echo traducir('Encargado del Proyecto'); ?></label>
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
                
                <!-- ===== Advertencia si cambia a aprobado por cliente ===== -->
                <?php if ($puede_cambiar_estado): ?>
                <div id="aviso-aprobado" style="display:none;" class="info-message">
                    <strong>⚠ <?php echo $_SESSION['idioma'] == 'pt' ? 'Atenção:' : 'Atención:'; ?></strong>
                    <span id="aviso-aprobado-texto"></span>
                </div>
                <?php endif; ?>
                
                <!-- ===== Botones ===== -->
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
    // ============================================
    // CARGAR RESPONSABLES DEL CLIENTE
    // ============================================
    function cargarResponsables() {
        const clienteId = document.getElementById('cliente_id').value;
        const selectResp = document.getElementById('cliente_responsable_id');
        
        selectResp.innerHTML = '<option value="">-- <?php echo $_SESSION['idioma'] == 'pt' ? 'Carregando...' : 'Cargando...'; ?> --</option>';
        
        if (!clienteId) {
            selectResp.innerHTML = '<option value="">-- <?php echo $_SESSION['idioma'] == 'pt' ? 'Selecione primeiro o cliente' : 'Seleccione primero el cliente'; ?> --</option>';
            return;
        }
        
        fetch('<?php echo url('modules/clientes/buscar_responsables_ajax.php'); ?>?cliente=' + clienteId)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.responsables.length) {
                    selectResp.innerHTML = '<option value="">-- <?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum responsável cadastrado' : 'Ningún responsable registrado'; ?> --</option>';
                    return;
                }
                
                let html = '<option value="">-- <?php echo $_SESSION['idioma'] == 'pt' ? 'Selecione o solicitante' : 'Seleccione el solicitante'; ?> --</option>';
                data.responsables.forEach(r => {
                    const cargo = r.cargo ? ` (${r.cargo})` : '';
                    const tel = r.celular || r.telefono || '';
                    html += `<option value="${r.id}">${escapeHtml(r.nombre)}${escapeHtml(cargo)}${tel ? ' - ' + escapeHtml(tel) : ''}</option>`;
                });
                selectResp.innerHTML = html;
            })
            .catch(e => {
                console.error(e);
                selectResp.innerHTML = '<option value="">-- Error --</option>';
            });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
    
    // ============================================
    // TOGGLE FECHAS SEGÚN ESTADO
    // ============================================
    function toggleFechas() {
        const estado = document.getElementById('estado').value;
        const grupoFechas = document.getElementById('grupo-fechas-proyecto');
        const inputInicio = document.getElementById('fecha_inicio');
        const inputFin = document.getElementById('fecha_fin');
        
        const estadosConFechas = ['aprovado_cliente', 'espera_orden_compra', 'comprando_materiales',
                                   'elaboracion', 'terminado', 'pendiente_cobro_cliente', 'finalizado'];
        
        if (estadosConFechas.includes(estado)) {
            grupoFechas.style.display = 'block';
            inputInicio.required = true;
            inputFin.required = true;
        } else {
            grupoFechas.style.display = 'none';
            inputInicio.required = false;
            inputFin.required = false;
        }
    }
    
    // ============================================
    // TOGGLE ELIMINAR PDF
    // ============================================
    function toggleEliminar(checkbox) {
        const archivoActual = document.querySelector('.archivo-actual');
        if (!archivoActual) return;
        
        if (checkbox.checked) {
            archivoActual.style.opacity = '0.4';
            archivoActual.style.textDecoration = 'line-through';
        } else {
            archivoActual.style.opacity = '1';
            archivoActual.style.textDecoration = 'none';
        }
    }
    
    // ============================================
    // VALIDAR PDF
    // ============================================
    const fileInput = document.getElementById('propuesta_tecnica');
    const fileName = document.getElementById('file-name');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                    alert('<?php echo $_SESSION['idioma'] == 'pt' ? 'Apenas PDF' : 'Solo PDF'; ?>');
                    this.value = '';
                    return;
                }
                
                if (file.size > 20 * 1024 * 1024) {
                    alert('<?php echo $_SESSION['idioma'] == 'pt' ? 'Excede 20 MB' : 'Supera 20 MB'; ?>');
                    this.value = '';
                    return;
                }
                
                fileName.textContent = '📄 ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                fileName.style.color = '#27ae60';
                fileName.style.fontWeight = '600';
            }
        });
    }
    
    // ============================================
    // VALIDACIONES AL ENVIAR
    // ============================================
    document.getElementById('form-proyecto').addEventListener('submit', function(e) {
        const solicitud = document.getElementById('fecha_solicitud').value;
        const inicio = document.getElementById('fecha_inicio').value;
        const fin = document.getElementById('fecha_fin').value;
        const aprob = document.getElementById('fecha_aprobacion').value;
        
        if (solicitud && inicio && inicio < solicitud) {
            e.preventDefault();
            alert('<?php echo $_SESSION["idioma"] == "pt" 
                ? "A data de início não pode ser anterior à data de solicitação." 
                : "La fecha de inicio no puede ser anterior a la fecha de solicitud."; ?>');
            return false;
        }
        
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
    
    // ============================================
    // DETECTAR CAMBIO A "aprovado_cliente"
    // ============================================
    <?php if ($puede_cambiar_estado): ?>
    const estadoSelect = document.getElementById('estado');
    const aviso = document.getElementById('aviso-aprobado');
    const avisoTexto = document.getElementById('aviso-aprobado-texto');
    const estadoOriginal = '<?php echo $proyecto['estado']; ?>';
    const idioma = '<?php echo $_SESSION['idioma']; ?>';
    
    function verificarCambioEstado() {
        const nuevoEstado = estadoSelect.value;
        
        if (nuevoEstado === 'aprovado_cliente' && estadoOriginal !== 'aprovado_cliente') {
            avisoTexto.textContent = idioma === 'pt'
                ? 'Ao salvar, todos os itens "Solicitado" deste projeto passarão automaticamente para "Pendente". Os itens se tornarão visíveis para a equipe de compras e almoxarifado.'
                : 'Al guardar, todos los items "Solicitado" de este proyecto pasarán automáticamente a "Pendiente". Los items se harán visibles para el equipo de compras y almacén.';
            aviso.style.display = 'block';
        } else if (nuevoEstado !== 'aprovado_cliente' && estadoOriginal === 'aprovado_cliente') {
            avisoTexto.textContent = idioma === 'pt'
                ? 'Você está revertendo o status de "Aprovado pelo Cliente". Os itens poderão ficar ocultos novamente.'
                : 'Estás revirtiendo el estado de "Aprobado por Cliente". Los items podrían volver a ocultarse.';
            aviso.style.display = 'block';
        } else {
            aviso.style.display = 'none';
        }
    }
    
    estadoSelect.addEventListener('change', verificarCambioEstado);
    verificarCambioEstado();
    <?php endif; ?>
    
    // ============================================
    // INICIALIZAR
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        toggleFechas();
        
        // Si viene con cliente, ya están los responsables cargados desde PHP
        // pero si el usuario cambia, se recargan vía AJAX
    });
    </script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>