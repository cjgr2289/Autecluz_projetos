<?php
// modules/proyectos/propuestas/index.php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
verificarSesion();

$db = Database::getInstance()->getConnection();
$proyecto_id = $_GET['proyecto'] ?? 0;

if (!$proyecto_id) {
    redirigir('modules/proyectos/index.php');
}

$stmt = $db->prepare("SELECT * FROM proyectos WHERE id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();

if (!$proyecto) {
    redirigir('modules/proyectos/index.php');
}

$propuestas = obtenerPropuestasProyecto($db, $proyecto_id);
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['idioma'] == 'pt' ? 'Propostas Econômicas' : 'Propuestas Económicas'; ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/propuestas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo $_SESSION['idioma'] == 'pt' ? 'Propostas Econômicas' : 'Propuestas Económicas'; ?></h1>
            <div>
                <a href="<?php echo url('modules/proyectos/ver.php?id=' . $proyecto_id); ?>" class="btn-secondary">
                    ← <?php echo traducir('Volver'); ?>
                </a>
                <a href="crear.php?proyecto=<?php echo $proyecto_id; ?>" class="btn-primary">
                    + <?php echo $_SESSION['idioma'] == 'pt' ? 'Nova Proposta' : 'Nueva Propuesta'; ?>
                </a>
            </div>
        </div>
        
        <div class="info-message">
            <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong>
            <?php if ($proyecto['orden_compra']): ?>
                — O.C.: <?php echo htmlspecialchars($proyecto['orden_compra']); ?>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_GET['mensaje'])): ?>
            <?php if ($_GET['mensaje'] === 'creado'): ?>
                <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Proposta criada com sucesso!' : '¡Propuesta creada exitosamente!'; ?></div>
            <?php elseif ($_GET['mensaje'] === 'actualizado'): ?>
                <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Proposta atualizada!' : '¡Propuesta actualizada!'; ?></div>
            <?php elseif ($_GET['mensaje'] === 'eliminado'): ?>
                <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Proposta excluída!' : '¡Propuesta eliminada!'; ?></div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (empty($propuestas)): ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3><?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhuma proposta criada ainda' : 'Ninguna propuesta creada aún'; ?></h3>
                <p><?php echo $_SESSION['idioma'] == 'pt' 
                    ? 'Crie a primeira proposta econômica para este projeto.' 
                    : 'Crea la primera propuesta económica para este proyecto.'; ?></p>
                <a href="crear.php?proyecto=<?php echo $proyecto_id; ?>" class="btn-primary">
                    + <?php echo $_SESSION['idioma'] == 'pt' ? 'Criar Proposta' : 'Crear Propuesta'; ?>
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Número' : 'Número'; ?></th>
                            <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Título' : 'Título'; ?></th>
                            <th class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Itens' : 'Items'; ?></th>
                            <th class="text-right"><?php echo $_SESSION['idioma'] == 'pt' ? 'Total' : 'Total'; ?></th>
                            <th class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Status' : 'Estado'; ?></th>
                            <th class="text-center"><?php echo $_SESSION['idioma'] == 'pt' ? 'Criado' : 'Creado'; ?></th>
                            <th class="text-center"><?php echo traducir('Acciones'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($propuestas as $prop): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($prop['numero']); ?></strong></td>
                                <td><?php echo htmlspecialchars($prop['titulo'] ?: '—'); ?></td>
                                <td class="text-center"><?php echo $prop['total_items']; ?></td>
                                <td class="text-right">
                                    <strong style="color:#27ae60;">
                                        <?php echo formatearMoneda($prop['total_final'], $prop['moneda']); ?>
                                    </strong>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $estados = [
                                        'borrador' => ['label' => 'Borrador', 'color' => '#95a5a6'],
                                        'enviada'  => ['label' => 'Enviada', 'color' => '#3498db'],
                                        'aprobada' => ['label' => 'Aprobada', 'color' => '#27ae60'],
                                        'rechazada'=> ['label' => 'Rechazada', 'color' => '#e74c3c'],
                                    ];
                                    $e = $estados[$prop['estado']] ?? $estados['borrador'];
                                    ?>
                                    <span class="badge-estado" style="background:<?php echo $e['color']; ?>;">
                                        <?php echo $e['label']; ?>
                                    </span>
                                </td>
                                <td class="text-center" style="font-size:0.85rem; color:#7f8c8d;">
                                    <?php echo formatearFecha($prop['fecha_creacion']); ?><br>
                                    <small><?php echo htmlspecialchars($prop['creador'] ?? ''); ?></small>
                                </td>
                                <td class="col-acciones">
                                    <div class="acciones-grupo">
                                        <a href="ver.php?id=<?php echo $prop['id']; ?>" 
                                           class="btn-accion btn-accion-ver" 
                                           data-tooltip="<?php echo traducir('Ver'); ?>">
                                            <?php echo icono('ver'); ?>
                                        </a>
                                        <a href="editar.php?id=<?php echo $prop['id']; ?>" 
                                           class="btn-accion btn-accion-editar" 
                                           data-tooltip="<?php echo traducir('Editar'); ?>">
                                            <?php echo icono('editar'); ?>
                                        </a>
                                        <a href="imprimir.php?id=<?php echo $prop['id']; ?>" 
                                           target="_blank"
                                           class="btn-accion btn-accion-reporte" 
                                           data-tooltip="<?php echo $_SESSION['idioma'] == 'pt' ? 'Imprimir' : 'Imprimir'; ?>">
                                            <?php echo icono('imprimir'); ?>
                                        </a>
                                        <button type="button" 
                                                class="btn-accion btn-accion-eliminar" 
                                                data-tooltip="<?php echo traducir('Eliminar'); ?>"
                                                onclick="confirmarEliminarPropuesta(<?php echo $prop['id']; ?>, '<?php echo htmlspecialchars(addslashes($prop['numero'])); ?>')">
                                            <?php echo icono('eliminar'); ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    async function confirmarEliminarPropuesta(id, numero) {
        const idioma = '<?php echo $_SESSION['idioma']; ?>';
        const ok = await Confirm.show({
            titulo: idioma === 'pt' ? 'Excluir Proposta' : 'Eliminar Propuesta',
            mensaje: idioma === 'pt'
                ? `Deseja realmente excluir a proposta "${numero}"?\n\nEsta ação não pode ser desfeita.`
                : `¿Realmente desea eliminar la propuesta "${numero}"?\n\nEsta acción no se puede deshacer.`,
            textoConfirmar: idioma === 'pt' ? 'Excluir' : 'Eliminar',
            tipo: 'danger'
        });
        
        if (ok) {
            window.location.href = '<?php echo url('modules/proyectos/propuestas/eliminar.php'); ?>?id=' + id;
        }
    }
    </script>
    
    <?php include '../../../includes/footer.php'; ?>
</body>
</html>