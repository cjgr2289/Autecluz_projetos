<?php
// modules/proyectos/index.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();

$filtro_busqueda = $_GET['busqueda'] ?? '';
$categoria_activa = $_GET['cat'] ?? ''; // 'preparacion', 'activos', etc.

// ============================================
// Cargar TODOS los proyectos activos
// ============================================
$query = "SELECT p.*, 
                 u.nombre_completo as creador,
                 e.nombre_completo as encargado_nombre
          FROM proyectos p 
          LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
          LEFT JOIN usuarios e ON p.encargado_id = e.id 
          WHERE 1=1";
$params = [];

if ($filtro_busqueda) {
    $query .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.orden_compra LIKE ?)";
    $params[] = "%$filtro_busqueda%";
    $params[] = "%$filtro_busqueda%";
    $params[] = "%$filtro_busqueda%";
}

$query .= " ORDER BY p.fecha_creacion DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$proyectos_todos = $stmt->fetchAll();

// ============================================
// FILTRAR por permisos
// ============================================
$proyectos_visibles = filtrarProyectosVisibles($proyectos_todos);

// ============================================
// AGRUPAR por categoría
// ============================================
$grupos = agruparProyectosPorCategoria($proyectos_visibles);

// ============================================
// CATEGORÍAS visibles para este usuario
// ============================================
$categorias = getCategoriasProyecto();
$categorias_visibles = getCategoriasVisiblesParaUsuario();

// ============================================
// ESTADÍSTICAS globales
// ============================================
$total_visibles = count($proyectos_visibles);

$estados = getEstadosProyecto();
$mensaje = $_GET['mensaje'] ?? '';
$error_msg = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Proyectos'); ?> - Sistema</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/proyectos.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Proyectos'); ?></h1>
            <?php if (tienePermiso(['directivo', 'gerenciador', 'supervisor', 'proyectista']) || esMaster()): ?>
                <a href="<?php echo url('modules/proyectos/crear.php'); ?>" class="btn-primary">
                    + <?php echo traducir('Crear Proyecto'); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <?php if ($mensaje === 'creado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Projeto criado com sucesso!' : '¡Proyecto creado exitosamente!'; ?></div>
        <?php elseif ($mensaje === 'actualizado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Projeto atualizado!' : '¡Proyecto actualizado!'; ?></div>
        <?php elseif ($mensaje === 'eliminado'): ?>
            <div class="success-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Projeto excluído!' : '¡Proyecto eliminado!'; ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg === 'sin_acceso'): ?>
            <div class="error-message">🔒 <?php echo $_SESSION['idioma'] == 'pt' 
                ? 'Você não tem permissão para acessar este projeto.' 
                : 'No tiene permiso para acceder a este proyecto.'; ?></div>
        <?php endif; ?>
        
        <!-- ===== Filtros ===== -->
        <div class="filtros">
            <form method="GET" action="">
                <input type="text" name="busqueda" 
                       placeholder="<?php echo traducir('Buscar'); ?>..." 
                       value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                <button type="submit" class="btn-secondary"><?php echo traducir('Filtrar'); ?></button>
                <?php if ($filtro_busqueda): ?>
                    <a href="<?php echo url('modules/proyectos/index.php'); ?>" class="btn-secondary"><?php echo traducir('Limpiar'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- ===== Resumen general ===== -->
        <div class="proyectos-resumen-global">
            <span class="resumen-item">
                <strong><?php echo $total_visibles; ?></strong> <?php echo $_SESSION['idioma'] == 'pt' ? 'projetos visíveis' : 'proyectos visibles'; ?>
            </span>
            <?php foreach ($categorias_visibles as $cat_key): 
                $cat = $categorias[$cat_key];
                $count = count($grupos[$cat_key] ?? []);
                if ($count == 0) continue;
            ?>
                <span class="resumen-item resumen-cat" style="border-left-color: <?php echo $cat['color']; ?>;">
                    <?php echo $cat['icono']; ?> 
                    <strong><?php echo $count; ?></strong>
                    <?php echo $_SESSION['idioma'] == 'pt' ? $cat['titulo_pt'] : $cat['titulo_es']; ?>
                </span>
            <?php endforeach; ?>
        </div>
        
        <!-- ===== Grupos de proyectos ===== -->
        <?php 
        $primer_grupo = true;
        foreach ($categorias_visibles as $cat_key): 
            $cat = $categorias[$cat_key];
            $proyectos_cat = $grupos[$cat_key] ?? [];
            $count = count($proyectos_cat);
        ?>
        
        <div class="proyectos-grupo" data-categoria="<?php echo $cat_key; ?>">
            <div class="grupo-header" onclick="toggleGrupo('<?php echo $cat_key; ?>')" style="border-left-color: <?php echo $cat['color']; ?>;">
                <div class="grupo-titulo">
                    <span class="grupo-toggle" id="toggle-<?php echo $cat_key; ?>">
                        <?php echo $primer_grupo ? '▼' : '▶'; ?>
                    </span>
                    <span class="grupo-icono"><?php echo $cat['icono']; ?></span>
                    <h2><?php echo $_SESSION['idioma'] == 'pt' ? $cat['titulo_pt'] : $cat['titulo_es']; ?></h2>
                    <span class="grupo-badge" style="background: <?php echo $cat['color']; ?>;">
                        <?php echo $count; ?>
                    </span>
                </div>
            </div>
            
            <div class="grupo-contenido" id="grupo-<?php echo $cat_key; ?>" style="<?php echo $primer_grupo ? '' : 'display:none;'; ?>">
                <?php if ($count === 0): ?>
                    <div class="grupo-vacio">
                        <?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum projeto nesta categoria' : 'Ningún proyecto en esta categoría'; ?>
                    </div>
                <?php else: ?>
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
                                <?php foreach ($proyectos_cat as $proyecto): ?>
                                <tr>
                                    <td class="col-nombre-proyecto">
                                        <a href="<?php echo url('modules/proyectos/ver.php?id=' . $proyecto['id']); ?>" class="proyecto-link">
                                            <div class="proyecto-nombre"><?php echo htmlspecialchars($proyecto['nombre']); ?></div>
                                        </a>
                                        <?php if (!empty($proyecto['descripcion'])): ?>
                                            <div class="proyecto-desc">
                                                <?php echo htmlspecialchars(truncarTexto($proyecto['descripcion'], 70)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="proyecto-meta">
                                            <span class="meta-label"><?php echo traducir('Creado por'); ?>:</span>
                                            <?php echo htmlspecialchars($proyecto['creador'] ?? '-'); ?>
                                            <?php if (!empty($proyecto['encargado_nombre'])): ?>
                                                · <span class="meta-label">🎯</span>
                                                <?php echo htmlspecialchars($proyecto['encargado_nombre']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="col-fechas">
                                        <div class="fecha-linea">
                                            <span class="fecha-label"><?php echo $_SESSION['idioma'] == 'pt' ? 'Sol' : 'Sol'; ?>:</span>
                                            <span><?php echo formatearFecha($proyecto['fecha_solicitud']); ?></span>
                                        </div>
                                        <?php if ($proyecto['fecha_inicio']): ?>
                                        <div class="fecha-linea">
                                            <span class="fecha-label"><?php echo traducir('Ini'); ?>:</span>
                                            <span><?php echo formatearFecha($proyecto['fecha_inicio']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($proyecto['fecha_fin']): ?>
                                        <div class="fecha-linea">
                                            <span class="fecha-label"><?php echo traducir('Fin'); ?>:</span>
                                            <span><?php echo formatearFecha($proyecto['fecha_fin']); ?></span>
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
                                            <a href="<?php echo url('modules/proyectos/ver.php?id=' . $proyecto['id']); ?>" 
                                               class="btn-accion btn-accion-ver" 
                                               data-tooltip="<?php echo traducir('Ver'); ?>">
                                                <?php echo icono('ver'); ?>
                                            </a>
                                            <?php if (tienePermiso(['directivo', 'gerenciador']) || esMaster()): ?>
                                                <a href="<?php echo url('modules/proyectos/editar.php?id=' . $proyecto['id']); ?>" 
                                                   class="btn-accion btn-accion-editar" 
                                                   data-tooltip="<?php echo traducir('Editar'); ?>">
                                                    <?php echo icono('editar'); ?>
                                                </a>
                                                <button type="button" 
                                                        class="btn-accion btn-accion-eliminar" 
                                                        data-tooltip="<?php echo traducir('Eliminar'); ?>"
                                                        onclick="confirmarEliminarProyecto(<?php echo $proyecto['id']; ?>, '<?php echo htmlspecialchars(addslashes($proyecto['nombre'])); ?>')">
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
        </div>
        
        <?php 
        $primer_grupo = false;
        endforeach; 
        ?>
        
        <?php if ($total_visibles === 0): ?>
            <div class="empty-state" style="margin-top:2rem;">
                <div class="empty-icon">📁</div>
                <h3><?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum projeto disponível' : 'Ningún proyecto disponible'; ?></h3>
                <p><?php echo $_SESSION['idioma'] == 'pt' 
                    ? 'Você não tem projetos para visualizar no momento.' 
                    : 'No tiene proyectos para visualizar en este momento.'; ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    function toggleGrupo(catKey) {
        const contenido = document.getElementById('grupo-' + catKey);
        const toggle = document.getElementById('toggle-' + catKey);
        if (!contenido || !toggle) return;
        
        const visible = contenido.style.display !== 'none';
        contenido.style.display = visible ? 'none' : 'block';
        toggle.textContent = visible ? '▶' : '▼';
        
        try {
            localStorage.setItem('grupo_proyecto_' + catKey, visible ? 'closed' : 'open');
        } catch(e) {}
    }
    
    // Restaurar estado de grupos
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.proyectos-grupo').forEach(grupo => {
            const catKey = grupo.dataset.categoria;
            try {
                const estado = localStorage.getItem('grupo_proyecto_' + catKey);
                if (estado === 'closed') {
                    const contenido = document.getElementById('grupo-' + catKey);
                    const toggle = document.getElementById('toggle-' + catKey);
                    if (contenido && contenido.style.display !== 'none') {
                        toggleGrupo(catKey);
                    }
                }
            } catch(e) {}
        });
    });
    
    async function confirmarEliminarProyecto(id, nombre) {
        const idioma = '<?php echo $_SESSION['idioma']; ?>';
        const ok = await Confirm.show({
            titulo: idioma === 'pt' ? 'Excluir Projeto' : 'Eliminar Proyecto',
            mensaje: idioma === 'pt'
                ? `Deseja realmente excluir o projeto "${nombre}"?\n\nEsta ação não pode ser desfeita.`
                : `¿Realmente desea eliminar el proyecto "${nombre}"?\n\nEsta acción no se puede deshacer.`,
            textoConfirmar: idioma === 'pt' ? 'Excluir' : 'Eliminar',
            tipo: 'danger'
        });
        
        if (ok) {
            window.location.href = '<?php echo url('modules/proyectos/eliminar.php'); ?>?id=' + id;
        }
    }
    </script>
    <?php include '../../includes/footer.php'; ?>
</body>
</html>