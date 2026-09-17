<?php
// modules/proyectos/ver.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$proyecto_id = $_GET['id'] ?? 0;

if (!$proyecto_id) {
    redirigir('modules/proyectos/index.php');
}

// Datos del proyecto
$stmt = $db->prepare("SELECT p.*, u.nombre_completo as creador 
                      FROM proyectos p 
                      LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
                      WHERE p.id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    redirigir('modules/proyectos/index.php');
}

// Items con info del producto y categoría
$stmt = $db->prepare("SELECT i.*, p.nombre as producto_nombre, c.nombre as categoria_nombre
                      FROM items_proyecto i 
                      LEFT JOIN productos p ON i.producto_id = p.id
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE i.proyecto_id = ? 
                      ORDER BY i.fecha_requerida ASC, i.fecha_creacion DESC");
$stmt->execute([$proyecto_id]);
$items = $stmt->fetchAll();

// Historial de proyecto
$stmt = $db->prepare("SELECT h.*, u.nombre_completo as usuario 
                      FROM historial_proyectos h 
                      LEFT JOIN usuarios u ON h.usuario_id = u.id 
                      WHERE h.proyecto_id = ? 
                      ORDER BY h.fecha_cambio DESC");
$stmt->execute([$proyecto_id]);
$historial = $stmt->fetchAll();

// Historial de items (últimos cambios)
$stmt = $db->prepare("SELECT hi.*, u.nombre_completo as usuario, i.nombre_item
                      FROM historial_items hi 
                      LEFT JOIN usuarios u ON hi.usuario_id = u.id
                      LEFT JOIN items_proyecto i ON hi.item_id = i.id
                      WHERE i.proyecto_id = ? 
                      ORDER BY hi.fecha_cambio DESC
                      LIMIT 50");
$stmt->execute([$proyecto_id]);
$historial_items = $stmt->fetchAll();

$estados_proyecto = getEstadosProyecto();
$estados_item = getEstadosItem();
$puede_agregar_items = tienePermiso(['compras', 'directivo', 'gerenciador', 'supervisor', 'proyectista', 'almacen']) || esMaster();
$puede_cambiar_estado_item = tienePermiso(['compras', 'directivo', 'gerenciador', 'almacen']) || esMaster();
$puede_editar_proyecto = tienePermiso(['directivo', 'gerenciador', 'proyectista']) || esMaster();

// Estadísticas rápidas
$total_items = count($items);
$items_pendientes = 0;
$items_llegaron = 0;
$items_entregados = 0;
$items_recibidos = 0;
foreach ($items as $it) {
    if (in_array($it['estado'], ['solicitado', 'pendiente', 'cotacion'])) $items_pendientes++;
    if ($it['estado'] === 'llego') $items_llegaron++;
    if ($it['estado'] === 'entregado') $items_entregados++;
    if ($it['estado'] === 'recibido') $items_recibidos++;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($proyecto['nombre']); ?> - Sistema</title>
    
    <!-- CSS modular completo -->
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/proyectos.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/items.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/modal.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        
        <!-- ============================================
             HEADER DEL PROYECTO
             ============================================ -->
        <div class="proyecto-header">
            <div class="proyecto-header-top">
                <div class="proyecto-header-title">
                    <h1><?php echo htmlspecialchars($proyecto['nombre']); ?></h1>
                    <span class="estado-badge estado-<?php echo $proyecto['estado']; ?>">
                        <?php echo $estados_proyecto[$proyecto['estado']] ?? $proyecto['estado']; ?>
                    </span>
                </div>
                
                <div class="proyecto-header-actions">
                    <a href="index.php" class="btn-secondary btn-sm">← <?php echo traducir('Volver'); ?></a>
                    
                    <?php if ($puede_editar_proyecto): ?>
                        <a href="editar.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary btn-sm">
                            ✎ <?php echo traducir('Editar'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Dropdown de reportes -->
                    <div class="dropdown-reportes">
                        <button type="button" class="btn-secondary btn-sm" onclick="toggleDropdownReportes(event)">
                            📊 <?php echo $_SESSION['idioma'] == 'pt' ? 'Relatórios' : 'Reportes'; ?> ▾
                        </button>
                        <div class="dropdown-menu" id="dropdown-reportes">
                            <a href="reporte.php?id=<?php echo $proyecto_id; ?>">
                                📋 <?php echo $_SESSION['idioma'] == 'pt' ? 'Itens por Status' : 'Items por Estado'; ?>
                            </a>
                            <a href="reporte_entrega.php?id=<?php echo $proyecto_id; ?>">
                                📦 <?php echo $_SESSION['idioma'] == 'pt' ? 'Entrega de Materiais' : 'Entrega de Materiales'; ?>
                            </a>
                            <a href="reporte_costos.php?id=<?php echo $proyecto_id; ?>">
                                💰 <?php echo $_SESSION['idioma'] == 'pt' ? 'Custos' : 'Costos'; ?>
                            </a>
                            <?php if (tienePermiso(['compras', 'directivo', 'gerenciador']) || esMaster()): ?>
                                <a href="editar_costos.php?id=<?php echo $proyecto_id; ?>">
                                    ✎ <?php echo $_SESSION['idioma'] == 'pt' ? 'Editar Custos' : 'Editar Costos'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($puede_agregar_items): ?>
                        <button type="button" class="btn-primary btn-sm" onclick="abrirModalProducto()">
                            + <?php echo traducir('Agregar Item'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Meta info compacta en una sola línea -->
            <div class="proyecto-meta-bar">
                <div class="meta-item">
                    <span class="meta-icon">📅</span>
                    <span class="meta-label"><?php echo traducir('Inicio'); ?>:</span>
                    <strong><?php echo formatearFecha($proyecto['fecha_inicio']); ?></strong>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">🏁</span>
                    <span class="meta-label"><?php echo traducir('Fin'); ?>:</span>
                    <strong><?php echo formatearFecha($proyecto['fecha_fin']); ?></strong>
                </div>
                <?php if ($proyecto['fecha_aprobacion']): ?>
                <div class="meta-item">
                    <span class="meta-icon">✅</span>
                    <span class="meta-label"><?php echo traducir('Apr'); ?>:</span>
                    <strong><?php echo formatearFecha($proyecto['fecha_aprobacion']); ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($proyecto['orden_compra']): ?>
                <div class="meta-item">
                    <span class="meta-icon">📄</span>
                    <span class="meta-label">O.C.:</span>
                    <span class="badge-oc"><?php echo htmlspecialchars($proyecto['orden_compra']); ?></span>
                </div>
                <?php endif; ?>
                <div class="meta-item">
                    <span class="meta-icon">👤</span>
                    <span class="meta-label"><?php echo traducir('Creado por'); ?>:</span>
                    <strong><?php echo htmlspecialchars($proyecto['creador'] ?? '-'); ?></strong>
                </div>
            </div>
            
            <!-- Descripción colapsable -->
            <?php if (!empty($proyecto['descripcion'])): ?>
            <details class="proyecto-descripcion">
                <summary><?php echo traducir('Descripción'); ?></summary>
                <p><?php echo nl2br(htmlspecialchars($proyecto['descripcion'])); ?></p>
            </details>
            <?php endif; ?>
        </div>
        
        <!-- ============================================
             STATS RÁPIDAS
             ============================================ -->
        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-value"><?php echo $total_items; ?></div>
                <div class="stat-mini-label"><?php echo traducir('Items'); ?></div>
            </div>
            <div class="stat-mini stat-mini-warning">
                <div class="stat-mini-value"><?php echo $items_pendientes; ?></div>
                <div class="stat-mini-label"><?php echo traducir('Pendientes'); ?></div>
            </div>
            <div class="stat-mini stat-mini-success">
                <div class="stat-mini-value"><?php echo $items_llegaron; ?></div>
                <div class="stat-mini-label"><?php echo traducir('Llegaron'); ?></div>
            </div>
            <div class="stat-mini stat-mini-success">
                <div class="stat-mini-value"><?php echo $items_entregados + $items_recibidos; ?></div>
                <div class="stat-mini-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Entregues' : 'Entregados'; ?></div>
            </div>
        </div>
        
        <!-- ============================================
             TABS: ITEMS / HISTORIAL
             ============================================ -->
        <div class="tabs-container">
            <div class="tabs-nav">
                <button class="tab-btn active" data-tab="items" onclick="cambiarTab('items')">
                    📦 <?php echo traducir('Items del Proyecto'); ?>
                    <span class="tab-count"><?php echo $total_items; ?></span>
                </button>
                <button class="tab-btn" data-tab="historial-proyecto" onclick="cambiarTab('historial-proyecto')">
                    📋 <?php echo traducir('Historial'); ?>
                    <span class="tab-count"><?php echo count($historial); ?></span>
                </button>
                <button class="tab-btn" data-tab="historial-items" onclick="cambiarTab('historial-items')">
                    🔄 <?php echo $_SESSION['idioma'] == 'pt' ? 'Histórico de Itens' : 'Historial de Items'; ?>
                    <span class="tab-count"><?php echo count($historial_items); ?></span>
                </button>
            </div>
            
            <!-- ===== TAB: ITEMS ===== -->
            <div class="tab-panel active" id="tab-items">
                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <p><?php echo traducir('No hay items en este proyecto'); ?></p>
                        <?php if ($puede_agregar_items): ?>
                            <button type="button" class="btn-primary" onclick="abrirModalProducto()">
                                + <?php echo traducir('Agregar Item'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="tabla-items">
                            <thead>
                                <tr>
                                    <th><?php echo traducir('Nombre'); ?></th>
                                    <th><?php echo traducir('Categorias'); ?></th>
                                    <th><?php echo traducir('Cantidad'); ?></th>
                                    <th><?php echo traducir('Unidad'); ?></th>
                                    <th><?php echo traducir('Fecha Requerida'); ?></th>
                                    <th><?php echo traducir('Estado'); ?></th>
                                    <th class="col-acciones-th"><?php echo traducir('Acciones'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="items-tbody">
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="col-item-nombre">
                                        <div class="item-nombre"><?php echo htmlspecialchars($item['nombre_item']); ?></div>
                                        <?php if (!empty($item['especificaciones'])): ?>
                                            <div class="item-nota"><?php echo htmlspecialchars(truncarTexto($item['especificaciones'], 50)); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-item-cat">
                                        <?php if ($item['categoria_nombre']): ?>
                                            <span class="cat-badge"><?php echo htmlspecialchars($item['categoria_nombre']); ?></span>
                                        <?php else: ?>
                                            <span class="sin-cat">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-item-cant"><?php echo $item['cantidad']; ?></td>
                                    <td class="col-item-unidad"><?php echo htmlspecialchars(getUnidadLabel($item['unidad_medida'])); ?></td>
                                    <td class="col-item-fecha"><?php echo formatearFecha($item['fecha_requerida']); ?></td>
                                    <td class="col-item-estado">
                                        <span class="estado-badge estado-<?php echo $item['estado']; ?>">
                                            <?php echo $estados_item[$item['estado']] ?? $item['estado']; ?>
                                        </span>
                                    </td>
                                    <td class="col-item-acciones">
                                        <div class="acciones-grupo">
                                            <?php if ($puede_cambiar_estado_item): ?>
                                                <a href="<?php echo url('modules/items/actualizar_estado.php?id=' . $item['id'] . '&proyecto=' . $proyecto_id); ?>" 
                                                   class="btn-accion btn-accion-estado" 
                                                   data-tooltip="<?php echo traducir('Estado'); ?>">
                                                    <?php echo icono('estado'); ?>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="<?php echo url('modules/items/editar.php?id=' . $item['id'] . '&proyecto=' . $proyecto_id); ?>" 
                                               class="btn-accion btn-accion-editar" 
                                               data-tooltip="<?php echo traducir('Editar'); ?>">
                                                <?php echo icono('editar'); ?>
                                            </a>
                                            
                                            <?php if (tienePermiso(['directivo', 'gerenciador', 'compras']) || esMaster()): ?>
                                                <button type="button" 
                                                        class="btn-accion btn-accion-eliminar" 
                                                        data-tooltip="<?php echo traducir('Eliminar'); ?>"
                                                        onclick="confirmarEliminarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['nombre_item'])); ?>')">
                                                    <?php echo icono('eliminar'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- ===== TAB: HISTORIAL PROYECTO ===== -->
            <div class="tab-panel" id="tab-historial-proyecto">
                <?php if (empty($historial)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <p><?php echo traducir('No hay historial'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="historial-lista">
                        <?php foreach ($historial as $h): ?>
                            <div class="historial-item">
                                <div class="historial-icon">🔄</div>
                                <div class="historial-content">
                                    <div class="historial-linea-1">
                                        <?php if ($h['estado_anterior'] && $h['estado_nuevo']): ?>
                                            <span class="estado-badge estado-<?php echo $h['estado_anterior']; ?>">
                                                <?php echo $estados_proyecto[$h['estado_anterior']] ?? $h['estado_anterior']; ?>
                                            </span>
                                            <span class="historial-arrow">→</span>
                                            <span class="estado-badge estado-<?php echo $h['estado_nuevo']; ?>">
                                                <?php echo $estados_proyecto[$h['estado_nuevo']] ?? $h['estado_nuevo']; ?>
                                            </span>
                                        <?php else: ?>
                                            <strong><?php echo traducir('Creación'); ?></strong>
                                        <?php endif; ?>
                                    </div>
                                    <div class="historial-linea-2">
                                        <span class="historial-usuario">👤 <?php echo htmlspecialchars($h['usuario'] ?? '-'); ?></span>
                                        <span class="historial-fecha">📅 <?php echo formatearFechaHora($h['fecha_cambio']); ?></span>
                                    </div>
                                    <?php if (!empty($h['comentario'])): ?>
                                        <div class="historial-comentario"><?php echo htmlspecialchars($h['comentario']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- ===== TAB: HISTORIAL ITEMS ===== -->
            <div class="tab-panel" id="tab-historial-items">
                <?php if (empty($historial_items)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔄</div>
                        <p><?php echo traducir('No hay historial'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="historial-lista">
                        <?php foreach ($historial_items as $hi): ?>
                            <div class="historial-item">
                                <div class="historial-icon">📦</div>
                                <div class="historial-content">
                                    <div class="historial-item-nombre">
                                        <strong><?php echo htmlspecialchars($hi['nombre_item'] ?? '-'); ?></strong>
                                    </div>
                                    <div class="historial-linea-1">
                                        <?php if ($hi['estado_anterior'] && $hi['estado_nuevo']): ?>
                                            <span class="estado-badge estado-<?php echo $hi['estado_anterior']; ?>">
                                                <?php echo $estados_item[$hi['estado_anterior']] ?? $hi['estado_anterior']; ?>
                                            </span>
                                            <span class="historial-arrow">→</span>
                                            <span class="estado-badge estado-<?php echo $hi['estado_nuevo']; ?>">
                                                <?php echo $estados_item[$hi['estado_nuevo']] ?? $hi['estado_nuevo']; ?>
                                            </span>
                                        <?php else: ?>
                                            <strong><?php echo traducir('Creación'); ?></strong>
                                        <?php endif; ?>
                                    </div>
                                    <div class="historial-linea-2">
                                        <span class="historial-usuario">👤 <?php echo htmlspecialchars($hi['usuario'] ?? '-'); ?></span>
                                        <span class="historial-fecha">📅 <?php echo formatearFechaHora($hi['fecha_cambio']); ?></span>
                                    </div>
                                    <?php if (!empty($hi['comentario'])): ?>
                                        <div class="historial-comentario"><?php echo htmlspecialchars($hi['comentario']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- ============================================
         MODAL AGREGAR PRODUCTO
         ============================================ -->
    <?php if ($puede_agregar_items): ?>
    <div id="modal-producto" class="modal-overlay" style="display:none;">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><?php echo traducir('Agregar Item al Proyecto'); ?></h2>
                <button type="button" class="modal-close" onclick="cerrarModalProducto()">&times;</button>
            </div>
            
            <div class="modal-body">
                <!-- Sección 1: Buscar producto -->
                <div class="modal-section">
                    <h3>1. <?php echo traducir('Buscar producto en el catálogo'); ?></h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo traducir('Categorias'); ?></label>
                            <select id="filtro-categoria" onchange="buscarProductos()">
                                <option value=""><?php echo traducir('Todas las categorias'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?php echo traducir('Buscar'); ?></label>
                            <input type="text" id="filtro-busqueda" 
                                   placeholder="<?php echo traducir('Escriba para buscar productos...'); ?>" 
                                   oninput="debounceBuscar()">
                        </div>
                    </div>
                    
                    <div class="productos-lista" id="productos-lista">
                        <p class="empty-msg"><?php echo traducir('Escriba para buscar productos...'); ?></p>
                    </div>
                    
                    <div class="nuevo-producto-toggle">
                        <button type="button" class="btn-secondary" onclick="toggleNuevoProducto()">
                            + <?php echo traducir('Crear nuevo producto'); ?>
                        </button>
                    </div>
                    
                    <div id="nuevo-producto-form" style="display:none;" class="nuevo-producto-form">
                        <h4><?php echo traducir('Nuevo Producto'); ?></h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo traducir('Nombre'); ?> *</label>
                                <input type="text" id="nuevo-prod-nombre">
                            </div>
                            <div class="form-group">
                                <label><?php echo traducir('Categorias'); ?></label>
                                <select id="nuevo-prod-categoria">
                                    <option value=""><?php echo traducir('Sin categoría'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo traducir('Unidad de medida'); ?></label>
                                <select id="nuevo-prod-unidad">
                                    <option value="">-- <?php echo traducir('Seleccionar'); ?> --</option>
                                    <?php foreach (getUnidadesMedida() as $cod => $lbl): ?>
                                        <option value="<?php echo htmlspecialchars($cod); ?>"><?php echo htmlspecialchars($lbl); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo traducir('Código'); ?></label>
                                <input type="text" id="nuevo-prod-codigo">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?php echo traducir('Descripción'); ?></label>
                            <textarea id="nuevo-prod-descripcion" rows="2"></textarea>
                        </div>
                        <button type="button" class="btn-primary" onclick="crearProductoRapido()">
                            <?php echo traducir('Crear'); ?> <?php echo traducir('y seleccionar'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Sección 2: Detalles del item -->
                <div class="modal-section">
                    <h3>2. <?php echo traducir('Detalles del item'); ?></h3>
                    
                    <div class="form-group">
                        <label><?php echo traducir('Producto seleccionado'); ?></label>
                        <input type="text" id="item-nombre" 
                               placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Selecione ou digite um produto personalizado' : 'Seleccione o escriba un producto personalizado'; ?>" 
                               required>
                        <input type="hidden" id="item-producto-id" value="">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo traducir('Cantidad'); ?> *</label>
                            <input type="number" id="item-cantidad" value="1" min="1" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo traducir('Unidad de medida'); ?></label>
                            <select id="item-unidad">
                                <option value="">-- <?php echo traducir('Seleccionar'); ?> --</option>
                                <?php foreach (getUnidadesMedida() as $cod => $lbl): ?>
                                    <option value="<?php echo htmlspecialchars($cod); ?>"><?php echo htmlspecialchars($lbl); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?php echo traducir('Fecha Requerida'); ?> *</label>
                            <input type="date" id="item-fecha" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo traducir('Especificaciones / Notas'); ?></label>
                        <textarea id="item-especificaciones" rows="2"></textarea>
                    </div>
                    
                    <button type="button" class="btn-primary btn-agregar-item" onclick="agregarItem()">
                        ✓ <?php echo traducir('Agregar a la lista'); ?>
                    </button>
                </div>
                
                <!-- Sección 3: Items agregados -->
                <div class="modal-section">
                    <h3>3. <?php echo traducir('Items agregados'); ?> (<span id="contador-items">0</span>)</h3>
                    <div id="items-pendientes" class="items-pendientes">
                        <p class="empty-msg"><?php echo traducir('Aún no ha agregado items'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModalProducto()"><?php echo traducir('Cancelar'); ?></button>
                <button type="button" class="btn-primary" onclick="guardarTodos()">
                    <?php echo traducir('Guardar todos los items'); ?>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
    const PROYECTO_ID = <?php echo $proyecto_id; ?>;
    
    // Traducciones para el modal
    const TRAD = {
        seleccionar: '<?php echo traducir('Seleccionar'); ?>',
        sinCategoria: '<?php echo traducir('Sin categoría'); ?>',
        noProductos: '<?php echo traducir('No se encontraron productos'); ?>',
        noItems: '<?php echo traducir('Aún no ha agregado items'); ?>',
        buscandoProductos: '<?php echo traducir('Escriba para buscar productos...'); ?>',
        guardando: '<?php echo $_SESSION['idioma'] == 'pt' ? 'Salvando...' : 'Guardando...'; ?>',
        guardarTodos: '<?php echo traducir('Guardar todos los items'); ?>',
        confirmarCerrar: '<?php echo $_SESSION['idioma'] == 'pt' ? 'Há itens pendentes sem salvar. Deseja fechar mesmo assim? Eles serão perdidos.' : 'Hay items pendientes sin guardar. ¿Desea cerrar de todas formas? Se perderán.'; ?>',
        confirmarSumar: '<?php echo $_SESSION['idioma'] == 'pt' ? 'Este produto já está na lista. Deseja somar a quantidade?' : 'Este producto ya está en la lista. ¿Desea sumar la cantidad?'; ?>',
        errorNombre: '<?php echo $_SESSION['idioma'] == 'pt' ? 'Selecione ou digite um produto' : 'Debe seleccionar o escribir un producto'; ?>',
        errorCantidad: '<?php echo $_SESSION['idioma'] == 'pt' ? 'A quantidade deve ser pelo menos 1' : 'La cantidad debe ser al menos 1'; ?>',
        errorFecha: '<?php echo $_SESSION['idioma'] == 'pt' ? 'Informe a data requerida' : 'Debe indicar la fecha requerida'; ?>',
        sinItemsGuardar: '<?php echo $_SESSION['idioma'] == 'pt' ? 'Não há itens para salvar. Adicione pelo menos um.' : 'No hay items para guardar. Agregue al menos uno.'; ?>',
        errorGuardar: '<?php echo $_SESSION['idioma'] == 'pt' ? 'Alguns itens não puderam ser salvos:' : 'Algunos items no se pudieron guardar:'; ?>',
        nombreObligatorio: '<?php echo $_SESSION['idioma'] == 'pt' ? 'O nome do produto é obrigatório' : 'El nombre del producto es obligatorio'; ?>',
        errorCrear: '<?php echo $_SESSION['idioma'] == 'pt' ? 'Erro' : 'Error'; ?>',
        nombre: '<?php echo traducir('Nombre'); ?>',
        cantidad: '<?php echo traducir('Cantidad'); ?>',
        unidad: '<?php echo traducir('Unidad'); ?>',
        fecha: '<?php echo traducir('Fecha'); ?>',
        categoria: '<?php echo traducir('Categorias'); ?>'
    };
    
    // Función auxiliar para traducir en JS
    function t(key) {
        return (window.TRAD && TRAD[key]) ? TRAD[key] : key;
    }
    
    // ===== TABS =====
    function cambiarTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
        
        document.querySelector(`.tab-btn[data-tab="${tabName}"]`).classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
    }
    
// ===== DROPDOWN REPORTES =====
function toggleDropdownReportes(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const container = document.querySelector('.dropdown-reportes');
    const menu = document.getElementById('dropdown-reportes');
    if (!container || !menu) return;
    
    const estaAbierto = container.classList.contains('abierto');
    
    // Cerrar cualquier otro dropdown abierto
    document.querySelectorAll('.dropdown-reportes.abierto').forEach(el => {
        if (el !== container) el.classList.remove('abierto');
    });
    
    // Toggle
    container.classList.toggle('abierto', !estaAbierto);
}

// Cerrar al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-reportes')) {
        document.querySelectorAll('.dropdown-reportes.abierto').forEach(el => {
            el.classList.remove('abierto');
        });
    }
});

// Cerrar con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.dropdown-reportes.abierto').forEach(el => {
            el.classList.remove('abierto');
        });
    }
});
    
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('dropdown-reportes');
        if (dropdown && !e.target.closest('.dropdown-reportes')) {
            dropdown.style.display = 'none';
        }
    });
    
    // ===== ELIMINAR ITEM =====
    async function confirmarEliminarItem(itemId, nombre) {
        const idioma = '<?php echo $_SESSION['idioma']; ?>';
        const proyectoId = <?php echo $proyecto_id; ?>;
        
        const mensaje = idioma === 'pt'
            ? `Deseja excluir o item "${nombre}" deste projeto?`
            : `¿Desea eliminar el item "${nombre}" de este proyecto?`;
        
        const ok = await Confirm.show({
            titulo: idioma === 'pt' ? 'Excluir Item' : 'Eliminar Item',
            mensaje: mensaje,
            textoConfirmar: idioma === 'pt' ? 'Excluir' : 'Eliminar',
            textoCancelar: idioma === 'pt' ? 'Cancelar' : 'Cancelar',
            tipo: 'danger'
        });
        
        if (ok) {
            // ✅ Usar url() para generar la ruta absoluta correcta
            window.location.href = '<?php echo url('modules/items/eliminar.php'); ?>?id=' + itemId + '&proyecto=' + proyectoId;
        }
    }
    </script>
    <script src="<?php echo url('assets/js/modal_productos.js'); ?>"></script>
    <script src="<?php echo url('assets/js/notificaciones.js'); ?>"></script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>