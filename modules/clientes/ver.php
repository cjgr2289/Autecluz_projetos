<?php
// modules/clientes/ver.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

$cliente = obtenerClientePorId($db, $id);

if (!$cliente) {
    redirigir('modules/clientes/index.php');
}

$responsables = obtenerResponsablesCliente($db, $id);

// Proyectos del cliente
$stmt = $db->prepare("SELECT p.*, e.nombre_completo as encargado_nombre
                      FROM proyectos p
                      LEFT JOIN usuarios e ON p.encargado_id = e.id
                      WHERE p.cliente_id = ?
                      ORDER BY p.fecha_creacion DESC");
$stmt->execute([$id]);
$proyectos = $stmt->fetchAll();

$estados_proyecto = getEstadosProyecto();
$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($cliente['nombre']); ?> - Cliente</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($cliente['nombre']); ?></h1>
            <div>
                <a href="index.php" class="btn-secondary">← <?php echo traducir('Volver'); ?></a>
                <a href="editar.php?id=<?php echo $id; ?>" class="btn-secondary">✎ <?php echo traducir('Editar'); ?></a>
                <a href="responsables.php?cliente=<?php echo $id; ?>" class="btn-primary">👥 <?php echo $_SESSION['idioma'] == 'pt' ? 'Gerenciar Responsáveis' : 'Gestionar Responsables'; ?></a>
            </div>
        </div>
        
        <?php if ($mensaje === 'creado'): ?>
            <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Cliente criado!' : '¡Cliente creado!'; ?></div>
        <?php elseif ($mensaje === 'actualizado'): ?>
            <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Cliente atualizado!' : '¡Cliente actualizado!'; ?></div>
        <?php endif; ?>
        
        <div class="proyecto-header">
            <div class="proyecto-meta-bar">
                <?php if ($cliente['razon_social']): ?>
                <div class="meta-item">
                    <span class="meta-icon">🏢</span>
                    <span class="meta-label">Razón Social:</span>
                    <strong><?php echo htmlspecialchars($cliente['razon_social']); ?></strong>
                </div>
                <?php endif; ?>
                
                <?php if ($cliente['ruc']): ?>
                <div class="meta-item">
                    <span class="meta-icon">📋</span>
                    <span class="meta-label">RUC/CNPJ:</span>
                    <strong><?php echo htmlspecialchars($cliente['ruc']); ?></strong>
                </div>
                <?php endif; ?>
                
                <?php if ($cliente['email']): ?>
                <div class="meta-item">
                    <span class="meta-icon">✉️</span>
                    <span class="meta-label">Email:</span>
                    <strong><?php echo htmlspecialchars($cliente['email']); ?></strong>
                </div>
                <?php endif; ?>
                
                <?php if ($cliente['telefono']): ?>
                <div class="meta-item">
                    <span class="meta-icon">📞</span>
                    <span class="meta-label">Tel:</span>
                    <strong><?php echo htmlspecialchars($cliente['telefono']); ?></strong>
                </div>
                <?php endif; ?>
                
                <?php if ($cliente['ciudad'] || $cliente['pais']): ?>
                <div class="meta-item">
                    <span class="meta-icon">📍</span>
                    <span class="meta-label">Ubicación:</span>
                    <strong><?php echo htmlspecialchars(trim($cliente['ciudad'] . ', ' . $cliente['pais'], ', ')); ?></strong>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($cliente['direccion']): ?>
            <div style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid #e9ecef;">
                <strong>Dirección:</strong> <?php echo htmlspecialchars($cliente['direccion']); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($cliente['notas']): ?>
            <div style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid #e9ecef; font-size:0.9rem; color:#555;">
                <strong>Notas:</strong> <?php echo nl2br(htmlspecialchars($cliente['notas'])); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Responsables -->
        <div class="tabs-container" style="margin-top:1.5rem;">
            <div class="tabs-nav">
                <button class="tab-btn active" data-tab="responsables" onclick="cambiarTab('responsables')">
                    👥 <?php echo $_SESSION['idioma'] == 'pt' ? 'Responsáveis' : 'Responsables'; ?>
                    <span class="tab-count"><?php echo count($responsables); ?></span>
                </button>
                <button class="tab-btn" data-tab="proyectos" onclick="cambiarTab('proyectos')">
                    📁 <?php echo $_SESSION['idioma'] == 'pt' ? 'Projetos' : 'Proyectos'; ?>
                    <span class="tab-count"><?php echo count($proyectos); ?></span>
                </button>
            </div>
            
            <!-- Tab Responsables -->
            <div class="tab-panel active" id="tab-responsables">
                <?php if (empty($responsables)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <p><?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum responsável cadastrado' : 'Ningún responsable registrado'; ?></p>
                        <a href="responsables.php?cliente=<?php echo $id; ?>" class="btn-primary">
                            + <?php echo $_SESSION['idioma'] == 'pt' ? 'Adicionar Responsável' : 'Agregar Responsable'; ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Nome' : 'Nombre'; ?></th>
                                    <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Cargo' : 'Cargo'; ?></th>
                                    <th>Email</th>
                                    <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Telefone' : 'Teléfono'; ?></th>
                                    <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Celular' : 'Celular'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($responsables as $r): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($r['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($r['cargo'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($r['email'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($r['telefono'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($r['celular'] ?? '—'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab Proyectos -->
            <div class="tab-panel" id="tab-proyectos">
                <?php if (empty($proyectos)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📁</div>
                        <p><?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum projeto vinculado' : 'Ningún proyecto vinculado'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Projeto' : 'Proyecto'; ?></th>
                                    <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Solicitação' : 'Solicitud'; ?></th>
                                    <th class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Status' : 'Estado'; ?></th>
                                    <th class="text-center"><?php echo traducir('Acciones'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proyectos as $p): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
                                    <td><?php echo formatearFecha($p['fecha_solicitud']); ?></td>
                                    <td class="text-center">
                                        <span class="estado-badge estado-<?php echo $p['estado']; ?>">
                                            <?php echo $estados_proyecto[$p['estado']] ?? $p['estado']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo url('modules/proyectos/ver.php?id=' . $p['id']); ?>" 
                                           class="btn-accion btn-accion-ver">
                                            <?php echo icono('ver'); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function cambiarTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
        
        document.querySelector(`.tab-btn[data-tab="${tabName}"]`).classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
    }
    </script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>