<?php
// modules/proyectos/index.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();

// Obtener filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

$query = "SELECT p.*, u.nombre_completo as creador 
          FROM proyectos p 
          LEFT JOIN usuarios u ON p.usuario_creacion = u.id 
          WHERE 1=1";
$params = [];

if ($filtro_estado) {
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
$stmt = $db->prepare($query);
$stmt->execute($params);
$proyectos = $stmt->fetchAll();

$estados = getEstadosProyecto();

// Mensajes
$mensaje = $_GET['mensaje'] ?? '';
$error_msg = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Proyectos'); ?> - Sistema</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
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
        
        <?php if ($error_msg === 'no_permitido'): ?>
            <div class="error-message"><?php echo $_SESSION['idioma'] == 'pt' ? 'Ação não permitida' : 'Acción no permitida'; ?></div>
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
                    <?php if (empty($proyectos)): ?>
                        <tr>
                            <td colspan="5" class="text-center empty-cell">
                                <?php echo traducir('No hay proyectos'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($proyectos as $proyecto): ?>
                        <tr>
                            <!-- Columna Nombre: se expande, nombre completo visible -->
                            <td class="col-nombre-proyecto">
                                <div class="proyecto-nombre">
                                    <?php echo htmlspecialchars($proyecto['nombre']); ?>
                                </div>
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
                            
                            <!-- Columna Fechas: compacta -->
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
                            
                            <!-- Columna Estado -->
                            <td>
                                <span class="estado-badge estado-<?php echo $proyecto['estado']; ?>">
                                    <?php echo $estados[$proyecto['estado']] ?? $proyecto['estado']; ?>
                                </span>
                            </td>
                            
                            <!-- Columna Orden de Compra -->
                            <td class="col-oc">
                                <?php if ($proyecto['orden_compra']): ?>
                                    <span class="badge-oc"><?php echo htmlspecialchars($proyecto['orden_compra']); ?></span>
                                <?php else: ?>
                                    <span class="sin-oc">—</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Columna Acciones: iconos compactos -->
                            <td class="col-acciones">
                                <a href="ver.php?id=<?php echo $proyecto['id']; ?>" 
                                   class="btn-icon" 
                                   title="<?php echo traducir('Ver'); ?>">
                                    👁
                                </a>
                                <?php if (tienePermiso(['directivo', 'gerenciador'])): ?>
                                    <a href="editar.php?id=<?php echo $proyecto['id']; ?>" 
                                       class="btn-icon" 
                                       title="<?php echo traducir('Editar'); ?>">
                                        ✎
                                    </a>
                                    <a href="eliminar.php?id=<?php echo $proyecto['id']; ?>" 
                                       class="btn-icon btn-icon-danger" 
                                       onclick="return confirm('<?php echo traducir('¿Está seguro?'); ?>')" 
                                       title="<?php echo traducir('Eliminar'); ?>">
                                        🗑
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($proyectos) > 0): ?>
        <div class="tabla-info">
            <?php 
            $total = count($proyectos);
            echo traducir('Mostrando') . ' ' . $total . ' ' . traducir('proyecto(s)'); 
            ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>