<?php
// modules/clientes/responsables.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['directivo', 'gerenciador', 'comercial', 'supervisor']) && !esMaster()) {
    redirigir('modules/clientes/index.php');
}

$db = Database::getInstance()->getConnection();
$cliente_id = $_GET['cliente'] ?? 0;

$cliente = obtenerClientePorId($db, $cliente_id);

if (!$cliente) {
    redirigir('modules/clientes/index.php');
}

$errores = [];
$exito_msg = '';

// ============================================
// CREAR RESPONSABLE
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre = trim($_POST['nombre'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio';
    }
    
    if (empty($errores)) {
        try {
            $stmt = $db->prepare("INSERT INTO clientes_responsables 
                                  (cliente_id, nombre, cargo, email, telefono, celular, notas)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $cliente_id, $nombre, $cargo ?: null, $email ?: null,
                $telefono ?: null, $celular ?: null, $notas ?: null
            ]);
            
            redirigir('modules/clientes/responsables.php?cliente=' . $cliente_id . '&mensaje=creado');
        } catch (PDOException $e) {
            $errores[] = 'Error al crear: ' . $e->getMessage();
        }
    }
}

// ============================================
// EDITAR RESPONSABLE
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio';
    }
    
    if (empty($errores) && $id) {
        try {
            // Verificar que el responsable pertenece a este cliente
            $stmt = $db->prepare("SELECT id FROM clientes_responsables WHERE id = ? AND cliente_id = ?");
            $stmt->execute([$id, $cliente_id]);
            if (!$stmt->fetch()) {
                $errores[] = 'Responsable no encontrado';
            } else {
                $stmt = $db->prepare("UPDATE clientes_responsables 
                                      SET nombre = ?, cargo = ?, email = ?, 
                                          telefono = ?, celular = ?, notas = ?
                                      WHERE id = ? AND cliente_id = ?");
                $stmt->execute([
                    $nombre, $cargo ?: null, $email ?: null,
                    $telefono ?: null, $celular ?: null, $notas ?: null,
                    $id, $cliente_id
                ]);
                
                redirigir('modules/clientes/responsables.php?cliente=' . $cliente_id . '&mensaje=actualizado');
            }
        } catch (PDOException $e) {
            $errores[] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

// ============================================
// ELIMINAR RESPONSABLE
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        // Verificar si el responsable está siendo usado en algún proyecto
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM proyectos WHERE cliente_responsable_id = ?");
        $stmt->execute([$id]);
        $en_uso = $stmt->fetch()['total'];
        
        if ($en_uso > 0) {
            redirigir('modules/clientes/responsables.php?cliente=' . $cliente_id . '&mensaje=en_uso');
        } else {
            $stmt = $db->prepare("UPDATE clientes_responsables SET activo = 0 WHERE id = ? AND cliente_id = ?");
            $stmt->execute([$id, $cliente_id]);
            redirigir('modules/clientes/responsables.php?cliente=' . $cliente_id . '&mensaje=eliminado');
        }
    }
}

// ============================================
// REACTIVAR RESPONSABLE (opcional)
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'reactivar') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare("UPDATE clientes_responsables SET activo = 1 WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$id, $cliente_id]);
        redirigir('modules/clientes/responsables.php?cliente=' . $cliente_id . '&mensaje=reactivado');
    }
}

// ============================================
// OBTENER LISTA DE RESPONSABLES (activos e inactivos)
// ============================================
$responsables_activos = obtenerResponsablesCliente($db, $cliente_id, true);
$responsables_inactivos = obtenerResponsablesCliente($db, $cliente_id, false);
// Filtrar los inactivos (los que están en la lista completa pero no en la activa)
$ids_activos = array_column($responsables_activos, 'id');
$responsables_inactivos = array_filter($responsables_inactivos, function($r) use ($ids_activos) {
    return !in_array($r['id'], $ids_activos);
});

$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsables - <?php echo htmlspecialchars($cliente['nombre']); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/tablas.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
    <style>
        .btn-accion-editar-responsable {
            background: #f39c12;
        }
        .btn-accion-editar-responsable:hover {
            background: #e67e22;
        }
        .btn-accion-editar-responsable:hover svg {
            stroke: white;
        }
        .modal-responsable .modal-content {
            max-width: 600px;
        }
        .responsables-inactivos {
            margin-top: 2rem;
            opacity: 0.75;
        }
        .responsables-inactivos h3 {
            color: #7f8c8d;
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>👥 <?php echo $_SESSION['idioma'] == 'pt' ? 'Responsáveis' : 'Responsables'; ?></h1>
            <div>
                <a href="ver.php?id=<?php echo $cliente_id; ?>" class="btn-secondary">
                    ← <?php echo traducir('Volver'); ?>
                </a>
            </div>
        </div>
        
        <div class="info-message">
            <strong>Cliente:</strong> <?php echo htmlspecialchars($cliente['nombre']); ?>
            <?php if ($cliente['razon_social']): ?>
                — <?php echo htmlspecialchars($cliente['razon_social']); ?>
            <?php endif; ?>
        </div>
        
        <?php if ($mensaje === 'creado'): ?>
            <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Responsável adicionado!' : '¡Responsable agregado!'; ?></div>
        <?php elseif ($mensaje === 'actualizado'): ?>
            <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Responsável atualizado!' : '¡Responsable actualizado!'; ?></div>
        <?php elseif ($mensaje === 'eliminado'): ?>
            <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Responsável removido!' : '¡Responsable eliminado!'; ?></div>
        <?php elseif ($mensaje === 'reactivado'): ?>
            <div class="success-message">✓ <?php echo $_SESSION['idioma'] == 'pt' ? 'Responsável reativado!' : '¡Responsable reactivado!'; ?></div>
        <?php elseif ($mensaje === 'en_uso'): ?>
            <div class="error-message">⚠ <?php echo $_SESSION['idioma'] == 'pt' 
                ? 'Não é possível excluir: este responsável está vinculado a um ou mais projetos.' 
                : 'No se puede eliminar: este responsable está vinculado a uno o más proyectos.'; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errores)): ?>
            <div class="error-message">
                <ul style="margin: 0.5rem 0 0 1.5rem;">
                    <?php foreach ($errores as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- ============================================
             FORMULARIO: CREAR RESPONSABLE
             ============================================ -->
        <div class="form-container" style="margin-bottom:1.5rem;">
            <h3 style="margin-top:0;">➕ <?php echo $_SESSION['idioma'] == 'pt' ? 'Adicionar Novo Responsável' : 'Agregar Nuevo Responsable'; ?></h3>
            
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Nome' : 'Nombre'; ?> *</label>
                        <input type="text" name="nombre" required maxlength="150">
                    </div>
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Cargo' : 'Cargo'; ?></label>
                        <input type="text" name="cargo" maxlength="100" 
                               placeholder="Ej: Gerente, Ingeniero, Compras">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" maxlength="150">
                    </div>
                    <div class="form-group">
                        <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Telefone' : 'Teléfono'; ?></label>
                        <input type="text" name="telefono" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Celular</label>
                        <input type="text" name="celular" maxlength="50">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notas</label>
                    <input type="text" name="notas" maxlength="255">
                </div>
                
                <button type="submit" class="btn-primary">+ <?php echo $_SESSION['idioma'] == 'pt' ? 'Adicionar' : 'Agregar'; ?></button>
            </form>
        </div>
        
        <!-- ============================================
             LISTA: RESPONSABLES ACTIVOS
             ============================================ -->
        <h3 style="margin-bottom:0.75rem;">
            ✅ <?php echo $_SESSION['idioma'] == 'pt' ? 'Responsáveis Ativos' : 'Responsables Activos'; ?>
            (<?php echo count($responsables_activos); ?>)
        </h3>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Nome' : 'Nombre'; ?></th>
                        <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Cargo' : 'Cargo'; ?></th>
                        <th>Email</th>
                        <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Telefone' : 'Teléfono'; ?></th>
                        <th>Celular</th>
                        <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Notas' : 'Notas'; ?></th>
                        <th class="text-center" style="width:120px;"><?php echo traducir('Acciones'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($responsables_activos)): ?>
                        <tr><td colspan="7" class="empty-cell">
                            <?php echo $_SESSION['idioma'] == 'pt' ? 'Nenhum responsável cadastrado' : 'Ningún responsable registrado'; ?>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($responsables_activos as $r): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['cargo'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($r['email'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($r['telefono'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($r['celular'] ?? '—'); ?></td>
                            <td>
                                <?php if ($r['notas']): ?>
                                    <small style="color:#7f8c8d;"><?php echo htmlspecialchars(truncarTexto($r['notas'], 40)); ?></small>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="acciones-grupo">
                                    <!-- Botón Editar -->
                                    <button type="button" 
                                            class="btn-accion btn-accion-editar-responsable" 
                                            data-tooltip="<?php echo traducir('Editar'); ?>"
                                            onclick='abrirModalEditar(<?php echo json_encode([
                                                "id" => $r["id"],
                                                "nombre" => $r["nombre"],
                                                "cargo" => $r["cargo"] ?? "",
                                                "email" => $r["email"] ?? "",
                                                "telefono" => $r["telefono"] ?? "",
                                                "celular" => $r["celular"] ?? "",
                                                "notas" => $r["notas"] ?? ""
                                            ]); ?>)'>
                                        <?php echo icono('editar'); ?>
                                    </button>
                                    
                                    <!-- Botón Eliminar -->
                                    <button type="button" 
                                            class="btn-accion btn-accion-eliminar" 
                                            data-tooltip="<?php echo traducir('Eliminar'); ?>"
                                            onclick="confirmarEliminarResponsable(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars(addslashes($r['nombre'])); ?>')">
                                        <?php echo icono('eliminar'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- ============================================
             LISTA: RESPONSABLES INACTIVOS (si hay)
             ============================================ -->
        <?php if (!empty($responsables_inactivos)): ?>
        <div class="responsables-inactivos">
            <h3>🚫 <?php echo $_SESSION['idioma'] == 'pt' ? 'Responsáveis Inativos' : 'Responsables Inactivos'; ?>
                (<?php echo count($responsables_inactivos); ?>)
            </h3>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Nome' : 'Nombre'; ?></th>
                            <th><?php echo $_SESSION['idioma'] == 'pt' ? 'Cargo' : 'Cargo'; ?></th>
                            <th>Email</th>
                            <th class="text-center" style="width:100px;"><?php echo traducir('Acciones'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responsables_inactivos as $r): ?>
                        <tr>
                            <td style="text-decoration: line-through; color:#95a5a6;">
                                <?php echo htmlspecialchars($r['nombre']); ?>
                            </td>
                            <td style="color:#95a5a6;"><?php echo htmlspecialchars($r['cargo'] ?? '—'); ?></td>
                            <td style="color:#95a5a6;"><?php echo htmlspecialchars($r['email'] ?? '—'); ?></td>
                            <td class="text-center">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="accion" value="reactivar">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn-secondary btn-sm" 
                                            onclick="return confirm('<?php echo $_SESSION['idioma'] == 'pt' ? 'Reativar este responsável?' : '¿Reactivar este responsable?'; ?>');">
                                        ♻ <?php echo $_SESSION['idioma'] == 'pt' ? 'Reativar' : 'Reactivar'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- ============================================
         MODAL EDITAR RESPONSABLE
         ============================================ -->
    <div id="modal-editar-responsable" class="modal-overlay modal-responsable" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✎ <?php echo $_SESSION['idioma'] == 'pt' ? 'Editar Responsável' : 'Editar Responsable'; ?></h2>
                <button type="button" class="modal-close" onclick="cerrarModalEditar()">&times;</button>
            </div>
            
            <form method="POST" id="form-editar-responsable">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit-id" value="">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Nome' : 'Nombre'; ?> *</label>
                            <input type="text" name="nombre" id="edit-nombre" required maxlength="150">
                        </div>
                        <div class="form-group">
                            <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Cargo' : 'Cargo'; ?></label>
                            <input type="text" name="cargo" id="edit-cargo" maxlength="100">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="edit-email" maxlength="150">
                        </div>
                        <div class="form-group">
                            <label><?php echo $_SESSION['idioma'] == 'pt' ? 'Telefone' : 'Teléfono'; ?></label>
                            <input type="text" name="telefono" id="edit-telefono" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label>Celular</label>
                            <input type="text" name="celular" id="edit-celular" maxlength="50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Notas</label>
                        <input type="text" name="notas" id="edit-notas" maxlength="255">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModalEditar()">
                        <?php echo traducir('Cancelar'); ?>
                    </button>
                    <button type="submit" class="btn-primary">
                        💾 <?php echo traducir('Actualizar'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // ============================================
    // MODAL EDITAR RESPONSABLE
    // ============================================
    function abrirModalEditar(data) {
        document.getElementById('edit-id').value = data.id;
        document.getElementById('edit-nombre').value = data.nombre;
        document.getElementById('edit-cargo').value = data.cargo;
        document.getElementById('edit-email').value = data.email;
        document.getElementById('edit-telefono').value = data.telefono;
        document.getElementById('edit-celular').value = data.celular;
        document.getElementById('edit-notas').value = data.notas;
        
        document.getElementById('modal-editar-responsable').style.display = 'flex';
        
        setTimeout(() => {
            document.getElementById('edit-nombre').focus();
        }, 100);
    }
    
    function cerrarModalEditar() {
        document.getElementById('modal-editar-responsable').style.display = 'none';
    }
    
    // Cerrar modal al hacer clic fuera
    document.getElementById('modal-editar-responsable').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalEditar();
        }
    });
    
    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalEditar();
        }
    });
    
    // ============================================
    // CONFIRMAR ELIMINAR
    // ============================================
    async function confirmarEliminarResponsable(id, nombre) {
        const idioma = '<?php echo $_SESSION['idioma']; ?>';
        const ok = await Confirm.show({
            titulo: idioma === 'pt' ? 'Remover Responsável' : 'Eliminar Responsable',
            mensaje: idioma === 'pt'
                ? `Deseja remover o responsável "${nombre}"?\n\nVocê poderá reativá-lo depois se necessário.`
                : `¿Desea eliminar el responsable "${nombre}"?\n\nPodrá reactivarlo después si es necesario.`,
            textoConfirmar: idioma === 'pt' ? 'Remover' : 'Eliminar',
            tipo: 'danger'
        });
        
        if (ok) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const inputAccion = document.createElement('input');
            inputAccion.type = 'hidden';
            inputAccion.name = 'accion';
            inputAccion.value = 'eliminar';
            form.appendChild(inputAccion);
            
            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id';
            inputId.value = id;
            form.appendChild(inputId);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
   <?php include '../../includes/footer.php'; ?>
</body>
</html>