<?php
// modules/proyectos/index.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();

$filtro_estado = $_GET['estado'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Estados activos vs finalizados
$estados_activos = ['solicitado', 'orçado', 'pendente_aprovacion_cliente', 'aprovado_cliente',
                    'espera_orden_compra', 'comprando_materiales', 'elaboracion', 'terminado',
                    'pendiente_cobro_cliente'];
$estados_finalizados = ['finalizado'];

// Función auxiliar para construir queries
function buildQuery($estados_permitidos, $filtro_estado, $filtro_busqueda) {
    $placeholders = implode(',', array_fill(0, count($estados_permitidos), '?'));
    $query = "SELECT p.*, u.nombre_completo as creador 
              FROM proyectos p 
              LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
              WHERE p.estado IN ($placeholders)";
    $params = $estados_permitidos;
    
    if ($filtro_estado && in_array($filtro_estado, $estados_permitidos)) {
        $query .= " AND p.estado = ?";
        $params[] = $filtro_estado;
    }
    
    if ($filtro_busqueda) {
        $query .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.orden_compra LIKE ?)";
        $params[] = "%$filtro_busqueda%";
        $params[] = "%$filtro_busqueda%";
        $params[] = "%$filtro_busqueda%";
    }
    
    $query .= " ORDER BY p.fecha_creacion DESC";
    return [$query, $params];
}

// Proyectos activos
[$query_activos, $params_activos] = buildQuery($estados_activos, $filtro_estado, $filtro_busqueda);
$stmt = $db->prepare($query_activos);
$stmt->execute($params_activos);
$proyectos_activos = $stmt->fetchAll();

// Proyectos finalizados
[$query_finalizados, $params_finalizados] = buildQuery($estados_finalizados, $filtro_estado, $filtro_busqueda);
$stmt = $db->prepare($query_finalizados);
$stmt->execute($params_finalizados);
$proyectos_finalizados = $stmt->fetchAll();

$estados = getEstadosProyecto();
$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Proyectos'); ?> - Sistema</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/badges.css">
    <link rel="stylesheet" href="../../assets/css/mensajes.css">
    <link rel="stylesheet" href="../../assets/css/proyectos.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Proyectos'); ?></h1>
            <?php if (tienePermiso(['directivo', 'gerenciador', 'supervisor', 'proyectista'])): ?>
            <a href="crear.php" class="btn-primary">+ <?php echo traducir('Crear Proyecto'); ?></a>
            <?php endif; ?>
        </div>
        
        <?php if ($mensaje === 'creado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Projeto criado com sucesso!' : '¡Proyecto creado exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'actualizado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Projeto atualizado com sucesso!' : '¡Proyecto actualizado exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'eliminado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Projeto excluído com sucesso!' : '¡Proyecto eliminado exitosamente!'; ?></div>
        <?php endif; ?>
        
        <div class="filtros">
            <form method="GET" action="">
                <input type="text" name="busqueda" 
                       placeholder="<?php echo traducir('Buscar'); ?>..." 
                       value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                <select name="estado">
                    <option value=""><?php echo traducir('Todos los estados'); ?></option>
                    <?php foreach ($estados as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo $filtro_estado == $key ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-secondary"><?php echo traducir('Filtrar'); ?></button>
                <?php if ($filtro_estado || $filtro_busqueda): ?>
                    <a href="index.php" class="btn-secondary"><?php echo traducir('Limpiar'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- ============================================
             TABLA 1: PROYECTOS ACTIVOS
             ============================================ -->
        <div class="proyectos-grupo">
            <div class="grupo-header" onclick="toggleGrupo('activos')">
                <div class="grupo-titulo">
                    <span class="grupo-toggle" id="toggle-activos">▼</span>
                    <h2><?php echo $_SESSION['idioma'] == 'pt' ? 'Projetos Ativos' : 'Proyectos Activos'; ?></h2>
                    <span class="grupo-badge activos"><?php echo count($proyectos_activos); ?></span>
                </div>
            </div>
            
            <div class="grupo-contenido" id="grupo-activos">
                <div class="table-responsive">
                    <table class="tabla-proyectos">
                        <thead>
                            <tr>
                                <th><?php echo traducir('Nombre'); ?></th>
                                <th><?php echo traducir('Fecha'); ?></th>
                                <th><?php echo traducir('Estado'); ?></th>
                                <th><?php echo traducir('Orden Compra'); ?></th>
                                <th><?php echo traducir('Acciones'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($proyectos_activos)): ?>
                                <tr>
                                    <td colspan="5" class="text-center empty-cell">
                                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum projeto ativo' : 'No hay proyectos activos'; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($proyectos_activos as $proyecto): ?>
                                <tr>
                                    <td class="col-nombre-proyecto">
                                        <a href="ver.php?id=<?php echo $proyecto['id']; ?>" class="proyecto-link">
                                            <div class="proyecto-nombre"><?php echo htmlspecialchars($proyecto['nombre']); ?></div>
                                        </a>
                                        <?php if (!empty($proyecto['descripcion'])): ?>
                                            <div class="proyecto-desc">
                                                <?php echo htmlspecialchars(truncarTexto($proyecto['descripcion'], 70)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($proyecto['creador'])): ?>
                                            <div class="proyecto-meta">
                                                <span class="meta-label"><?php echo traducir('Creado por'); ?>:</span>
                                                <?php echo htmlspecialchars($proyecto['creador']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-fechas">
                                        <div class="fecha-linea">
                                            <span class="fecha-label"><?php echo traducir('Ini'); ?>:</span>
                                            <span><?php echo formatearFecha($proyecto['fecha_inicio']); ?></span>
                                        </div>
                                        <div class="fecha-linea">
                                            <span class="fecha-label"><?php echo traducir('Fin'); ?>:</span>
                                            <span><?php echo formatearFecha($proyecto['fecha_fin']); ?></span>
                                        </div>
                                        <?php if (!empty($proyecto['fecha_aprobacion'])): ?>
                                        <div class="fecha-linea">
                                            <span class="fecha-label"><?php echo traducir('Apr'); ?>:</span>
                                            <span><?php echo formatearFecha($proyecto['fecha_aprobacion']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="estado-badge estado-<?php echo $proyecto['estado']; ?>">
                                            <?php echo $estados[$proyecto['estado']] ?? $proyecto['estado']; ?>
                                        </span>
                                    </td>
                                    <td class="col-oc">
                                        <?php if ($proyecto['orden_compra']): ?>
                                            <span class="badge-oc"><?php echo htmlspecialchars($proyecto['orden_compra']); ?></span>
                                        <?php else: ?>
                                            <span class="sin-oc">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-acciones">
                                        <div class="acciones-grupo">
                                            <a href="ver.php?id=<?php echo $proyecto['id']; ?>" 
                                            class="btn-accion btn-accion-ver" 
                                            data-tooltip="<?php echo traducir('Ver'); ?>">
                                                <?php echo icono('ver'); ?>
                                            </a>
                                            <?php if (tienePermiso(['directivo', 'gerenciador'])): ?>
                                                <a href="editar.php?id=<?php echo $proyecto['id']; ?>" 
                                                class="btn-accion btn-accion-editar" 
                                                data-tooltip="<?php echo traducir('Editar'); ?>">
                                                    <?php echo icono('editar'); ?>
                                                </a>
                                                <button type="button" 
                                                        class="btn-accion btn-accion-eliminar" 
                                                        data-tooltip="<?php echo traducir('Eliminar'); ?>"
                                                        onclick="confirmarEliminar(<?php echo $proyecto['id']; ?>, '<?php echo htmlspecialchars(addslashes($proyecto['nombre'])); ?>')">
                                                    <?php echo icono('eliminar'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- ============================================
             TABLA 2: PROYECTOS FINALIZADOS (COLAPSABLE, CERRADO POR DEFECTO)
             ============================================ -->
        <div class="proyectos-grupo">
            <div class="grupo-header" onclick="toggleGrupo('finalizados')">
                <div class="grupo-titulo">
                    <span class="grupo-toggle" id="toggle-finalizados">▶</span>
                    <h2><?php echo $_SESSION['idioma'] == 'pt' ? 'Projetos Finalizados' : 'Proyectos Finalizados'; ?></h2>
                    <span class="grupo-badge finalizados"><?php echo count($proyectos_finalizados); ?></span>
                </div>
            </div>
            
            <div class="grupo-contenido" id="grupo-finalizados" style="display:none;">
                <div class="table-responsive">
                    <table class="tabla-proyectos">
                        <thead>
                            <tr>
                                <th><?php echo traducir('Nombre'); ?></th>
                                <th><?php echo traducir('Fecha'); ?></th>
                                <th><?php echo traducir('Estado'); ?></th>
                                <th><?php echo traducir('Orden Compra'); ?></th>
                                <th><?php echo traducir('Acciones'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($proyectos_finalizados)): ?>
                                <tr>
                                    <td colspan="5" class="text-center empty-cell">
                                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum projeto finalizado' : 'No hay proyectos finalizados'; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($proyectos_finalizados as $proyecto): ?>
                                <tr>
                                    <td class="col-nombre-proyecto">
                                        <a href="ver.php?id=<?php echo $proyecto['id']; ?>" class="proyecto-link">
                                            <div class="proyecto-nombre"><?php echo htmlspecialchars($proyecto['nombre']); ?></div>
                                        </a>
                                        <?php if (!empty($proyecto['creador'])): ?>
                                            <div class="proyecto-meta">
                                                <span class="meta-label"><?php echo traducir('Creado por'); ?>:</span>
                                                <?php echo htmlspecialchars($proyecto['creador']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-fechas">
                                        <div class="fecha-linea">
                                            <span class="fecha-label"><?php echo traducir('Ini'); ?>:</span>
                                            <span><?php echo formatearFecha($proyecto['fecha_inicio']); ?></span>
                                        </div>
                                        <div class="fecha-linea">
                                            <span class="fecha-label"><?php echo traducir('Fin'); ?>:</span>
                                            <span><?php echo formatearFecha($proyecto['fecha_fin']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="estado-badge estado-<?php echo $proyecto['estado']; ?>">
                                            <?php echo $estados[$proyecto['estado']] ?? $proyecto['estado']; ?>
                                        </span>
                                    </td>
                                    <td class="col-oc">
                                        <?php if ($proyecto['orden_compra']): ?>
                                            <span class="badge-oc"><?php echo htmlspecialchars($proyecto['orden_compra']); ?></span>
                                        <?php else: ?>
                                            <span class="sin-oc">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-acciones">
                                        <a href="ver.php?id=<?php echo $proyecto['id']; ?>" class="btn-icon" title="<?php echo traducir('Ver'); ?>">👁</a>
                                        <a href="reporte_costos.php?id=<?php echo $proyecto['id']; ?>" class="btn-icon" title="<?php echo $_SESSION['idioma'] == 'pt' ? 'Custos' : 'Costos'; ?>">💰</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function toggleGrupo(id) {
        const contenido = document.getElementById('grupo-' + id);
        const toggle = document.getElementById('toggle-' + id);
        if (!contenido || !toggle) return;
        
        const visible = contenido.style.display !== 'none';
        contenido.style.display = visible ? 'none' : 'block';
        toggle.textContent = visible ? '▶' : '▼';
        
        // Guardar preferencia en localStorage
        try {
            localStorage.setItem('grupo_' + id, visible ? 'closed' : 'open');
        } catch(e) {}
    }
    
    // Restaurar estado al cargar (por defecto: activos abierto, finalizados cerrado)
    document.addEventListener('DOMContentLoaded', function() {
        try {
            ['activos', 'finalizados'].forEach(id => {
                const estado = localStorage.getItem('grupo_' + id);
                if (estado === 'closed') toggleGrupo(id);
                if (estado === 'open' && id === 'finalizados') toggleGrupo(id);
            });
        } catch(e) {}
    });
    </script>
    <script>
async function confirmarEliminar(id, nombre) {
    const idioma = '<?php echo $_SESSION['idioma']; ?>';
    
    const mensaje = idioma === 'pt'
        ? `Deseja realmente excluir o projeto "${nombre}"?\n\nEsta ação não pode ser desfeita.`
        : `¿Realmente desea eliminar el proyecto "${nombre}"?\n\nEsta acción no se puede deshacer.`;
    
    const ok = await Confirm.show({
        titulo: idioma === 'pt' ? 'Excluir Projeto' : 'Eliminar Proyecto',
        mensaje: mensaje,
        textoConfirmar: idioma === 'pt' ? 'Excluir' : 'Eliminar',
        textoCancelar: idioma === 'pt' ? 'Cancelar' : 'Cancelar',
        tipo: 'danger'
    });
    
    if (ok) {
        window.location.href = 'eliminar.php?id=' + id;
    }
}
</script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>