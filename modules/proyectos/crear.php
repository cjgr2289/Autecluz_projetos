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

$stmt = $db->query("SELECT id, nombre_completo, tipo_usuario 
                    FROM usuarios 
                    WHERE tipo_usuario IN ('supervisor', 'proyectista') 
                      AND activo = 1 
                    ORDER BY nombre_completo");
$encargados_disponibles = $stmt->fetchAll();

$clientes_disponibles = obtenerClientes($db);

$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_solicitud = $_POST['fecha_solicitud'] ?? '';
    $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
    $cliente_responsable_id = !empty($_POST['cliente_responsable_id']) ? (int)$_POST['cliente_responsable_id'] : null;
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $estado = $_POST['estado'] ?? 'solicitado';
    $orden_compra = trim($_POST['orden_compra'] ?? '') ?: null;
    $fecha_aprobacion = $_POST['fecha_aprobacion'] ?? null;
    $encargado_id = !empty($_POST['encargado_id']) ? (int)$_POST['encargado_id'] : null;
    
    // Validaciones básicas
    if (empty($nombre)) $errores[] = 'El nombre del proyecto es obligatorio';
    if (empty($fecha_solicitud)) $errores[] = 'La fecha de solicitud es obligatoria';
    if (empty($cliente_id)) $errores[] = 'Debe seleccionar un cliente';
    
    // Fecha inicio/fin solo son obligatorias si el estado ya es aprobado o posterior
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
    
    if (!isset($estados[$estado])) $estado = 'solicitado';
    
    if (!tienePermiso(['directivo', 'gerenciador']) && !esMaster()) {
        $estado = 'solicitado';
    }
    
    // Validar PDF
    if (!empty($_FILES['propuesta_tecnica']['name'])) {
        $validacion = validarPdfSubido($_FILES['propuesta_tecnica']);
        if (!$validacion['ok']) $errores[] = 'Propuesta técnica: ' . $validacion['error'];
    }
    
    if (empty($errores)) {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO proyectos 
                    (nombre, descripcion, fecha_solicitud, cliente_id, cliente_responsable_id,
                     fecha_inicio, fecha_fin, estado, orden_compra, fecha_aprobacion, 
                     usuario_creacion, encargado_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nombre, $descripcion, $fecha_solicitud, $cliente_id, $cliente_responsable_id,
                !empty($fecha_inicio) ? $fecha_inicio : null,
                !empty($fecha_fin) ? $fecha_fin : null,
                $estado, $orden_compra,
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
                                          SET propuesta_tecnica = ?, propuesta_nombre_original = ?, 
                                              propuesta_fecha_subida = NOW(), propuesta_subida_por = ?
                                          WHERE id = ?");
                    $stmt->execute([
                        $resultado_pdf['archivo'], $resultado_pdf['nombre_original'],
                        $_SESSION['usuario_id'], $proyecto_id
                    ]);
                }
            }
            
            // Historial
            $stmt = $db->prepare("
                INSERT INTO historial_proyectos 
                    (proyecto_id, estado_anterior, estado_nuevo, usuario_id, comentario)
                VALUES (?, NULL, ?, ?, ?)
            ");
            $stmt->execute([$proyecto_id, $estado, $_SESSION['usuario_id'], 'Proyecto creado']);
            
            $db->commit();
            
            redirigir('modules/proyectos/ver.php?id=' . $proyecto_id . '&mensaje=creado');
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errores[] = 'Error al crear el proyecto: ' . $e->getMessage();
        }
    }
}

$valores = [
    'nombre'                  => $_POST['nombre'] ?? '',
    'descripcion'             => $_POST['descripcion'] ?? '',
    'fecha_solicitud'         => $_POST['fecha_solicitud'] ?? date('Y-m-d'),
    'cliente_id'              => $_POST['cliente_id'] ?? '',
    'cliente_responsable_id'  => $_POST['cliente_responsable_id'] ?? '',
    'fecha_inicio'            => $_POST['fecha_inicio'] ?? '',
    'fecha_fin'               => $_POST['fecha_fin'] ?? '',
    'estado'                  => $_POST['estado'] ?? 'solicitado',
    'orden_compra'            => $_POST['orden_compra'] ?? '',
    'fecha_aprobacion'        => $_POST['fecha_aprobacion'] ?? '',
    'encargado_id'            => $_POST['encargado_id'] ?? '',
];

$puede_cambiar_estado = tienePermiso(['directivo', 'gerenciador']) || esMaster();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
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
                
                <!-- ===== Datos básicos ===== -->
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
                        <option value="">-- <?php echo $_SESSION['idioma'] == 'pt' ? 'Selecione primeiro o cliente' : 'Seleccione primero el cliente'; ?> --</option>
                    </select>
                </div>
                
                <!-- ===== Propuesta técnica (PDF) ===== -->
                <div class="form-group">
                    <label for="propuesta_tecnica">
                        📄 <?php echo $_SESSION['idioma'] == 'pt' ? 'Proposta Técnica (PDF)' : 'Propuesta Técnica (PDF)'; ?>
                    </label>
                    <div class="file-upload-wrapper">
                        <input type="file" id="propuesta_tecnica" name="propuesta_tecnica" 
                               accept="application/pdf,.pdf" class="file-input">
                        <div class="file-upload-info">
                            <span class="file-name" id="file-name"><?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum arquivo selecionado' : 'Ningún archivo seleccionado'; ?></span>
                            <span class="file-hint"><?php echo $_SESSION['idioma'] == 'pt' ? 'Máx. 20 MB, apenas PDF' : 'Máx. 20 MB, solo PDF'; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- ===== Estado ===== -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="estado">
                            <?php echo traducir('Estado'); ?>
                            <?php if (!$puede_cambiar_estado): ?>
                                <small style="color:#7f8c8d; font-weight:normal;">(<?php echo $_SESSION['idioma'] == 'pt' ? 'somente leitura' : 'solo lectura'; ?>)</small>
                            <?php endif; ?>
                        </label>
                        <select id="estado" name="estado" <?php echo !$puede_cambiar_estado ? 'disabled' : ''; ?> onchange="toggleFechas()">
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
                        <label for="orden_compra"><?php echo traducir('Número de Orden de Compra'); ?></label>
                        <input type="text" id="orden_compra" name="orden_compra" 
                               value="<?php echo htmlspecialchars($valores['orden_compra']); ?>"
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
                            <label for="fecha_inicio">📅 <?php echo traducir('Fecha Inicio'); ?> *</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" 
                                   value="<?php echo htmlspecialchars($valores['fecha_inicio']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="fecha_fin">🏁 <?php echo traducir('Fecha Fin'); ?> *</label>
                            <input type="date" id="fecha_fin" name="fecha_fin" 
                                   value="<?php echo htmlspecialchars($valores['fecha_fin']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_aprobacion">✅ <?php echo traducir('Fecha Aprobación'); ?></label>
                        <input type="date" id="fecha_aprobacion" name="fecha_aprobacion" 
                               value="<?php echo htmlspecialchars($valores['fecha_aprobacion']); ?>">
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
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">💾 <?php echo traducir('Crear Proyecto'); ?></button>
                    <a href="<?php echo url('modules/proyectos/index.php'); ?>" class="btn-secondary"><?php echo traducir('Cancelar'); ?></a>
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
        
        // Limpiar
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
                selectResp.innerHTML = '<option value="">-- Error al cargar --</option>';
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
        
        const estadosConFechas = ['aprovado_cliente', 'espera_orden_compra', 'comprando_materiales',
                                   'elaboracion', 'terminado', 'pendiente_cobro_cliente', 'finalizado'];
        
        if (estadosConFechas.includes(estado)) {
            grupoFechas.style.display = 'block';
            document.getElementById('fecha_inicio').required = true;
            document.getElementById('fecha_fin').required = true;
        } else {
            grupoFechas.style.display = 'none';
            document.getElementById('fecha_inicio').required = false;
            document.getElementById('fecha_fin').required = false;
        }
    }
    
    // ============================================
    // VALIDACIONES
    // ============================================
    document.getElementById('form-proyecto').addEventListener('submit', function(e) {
        const solicitud = document.getElementById('fecha_solicitud').value;
        const inicio = document.getElementById('fecha_inicio').value;
        const fin = document.getElementById('fecha_fin').value;
        
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
    });
    
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
                    fileName.textContent = '<?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum arquivo selecionado' : 'Ningún archivo seleccionado'; ?>';
                    return;
                }
                
                if (file.size > 20 * 1024 * 1024) {
                    alert('<?php echo $_SESSION['idioma'] == 'pt' ? 'Excede 20 MB' : 'Supera 20 MB'; ?>');
                    this.value = '';
                    fileName.textContent = '<?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum arquivo selecionado' : 'Ningún archivo seleccionado'; ?>';
                    return;
                }
                
                fileName.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                fileName.style.color = '#27ae60';
            }
        });
    }
    
    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        toggleFechas();
        
        // Si viene con cliente seleccionado, cargar responsables
        <?php if (!empty($valores['cliente_id'])): ?>
        cargarResponsables();
        // Preseleccionar el responsable después de cargar
        setTimeout(() => {
            const respSelect = document.getElementById('cliente_responsable_id');
            if (respSelect) respSelect.value = '<?php echo $valores['cliente_responsable_id'] ?? ''; ?>';
        }, 500);
        <?php endif; ?>
    });
    </script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>