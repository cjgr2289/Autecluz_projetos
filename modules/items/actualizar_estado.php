<?php
// modules/items/actualizar_estado.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verificarSesion();

if (!tienePermiso(['compras', 'directivo', 'gerenciador', 'almacen']) && !esMaster()) {
    header('Location: ../proyectos/index.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$item_id = $_GET['id'] ?? 0;
$proyecto_id = $_GET['proyecto'] ?? 0;

if (!$item_id || !$proyecto_id) {
    header('Location: ../proyectos/index.php');
    exit();
}

$stmt = $db->prepare("SELECT i.*, p.estado as proyecto_estado 
                      FROM items_proyecto i 
                      JOIN proyectos p ON i.proyecto_id = p.id 
                      WHERE i.id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: ../proyectos/ver.php?id=' . $proyecto_id);
    exit();
}

$estados_permitidos = getEstadosItemByProyectoEstado($item['proyecto_estado']);

// ============================================
// VALIDACIÓN DE TRANSICIONES
// ============================================
// Define qué transiciones son válidas desde cada estado.
// null significa "cualquier estado siguiente permitido" (sin restricción).
$transiciones_validas = [
    'solicitado'       => ['pendiente', 'cotacion', 'stock'],
    'pendiente'        => ['cotacion', 'stock', 'pendiente_pago'],
    'cotacion'         => ['orçado', 'pendiente_pago', 'stock'],
    'orçado'           => ['pendiente_pago', 'comprado_llegar', 'stock'],
    'stock'            => ['pendiente', 'cotacion', 'orçado', 'pendiente_pago', 'comprado_llegar', 'llego', 'entregado'],
    'pendiente_pago'   => ['comprado_llegar', 'stock', 'llego'],
    'comprado_llegar'  => ['llego', 'stock', 'entregado'],
    'llego'            => ['entregado', 'stock', 'recibido'],
    'entregado'        => ['recibido', 'llego'],  // puede devolverse si hubo error
    'recibido'         => [],                      // estado final, no se puede cambiar
];

/**
 * Valida si una transición de estado es permitida.
 * 
 * @param string $estado_actual
 * @param string $estado_nuevo
 * @return bool
 */
function esTransicionValida($estado_actual, $estado_nuevo) {
    global $transiciones_validas;
    
    // Mismo estado: no hay transición
    if ($estado_actual === $estado_nuevo) {
        return true;
    }
    
    // Si el estado actual no está en el mapa, permitir cualquier transición
    if (!isset($transiciones_validas[$estado_actual])) {
        return true;
    }
    
    // Verificar si el nuevo estado está en la lista de permitidos
    return in_array($estado_nuevo, $transiciones_validas[$estado_actual]);
}

/**
 * Devuelve el siguiente estado lógico sugerido para un item.
 */
function getSiguienteEstadoSugerido($estado_actual) {
    $flujo = [
        'solicitado'       => 'cotacion',
        'pendiente'        => 'cotacion',
        'cotacion'         => 'orçado',
        'orçado'           => 'pendiente_pago',
        'stock'            => 'entregado',
        'pendiente_pago'   => 'comprado_llegar',
        'comprado_llegar'  => 'llego',
        'llego'            => 'entregado',
        'entregado'        => 'recibido',
        'recibido'         => null,
    ];
    
    return $flujo[$estado_actual] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuevo_estado = $_POST['estado'] ?? '';
    $comentario = $_POST['comentario'] ?? '';
    $nueva_fecha = $_POST['fecha_requerida'] ?? $item['fecha_requerida'];
    $forzar_transicion = !empty($_POST['forzar_transicion']);
    
    // Validar que el estado esté permitido por el proyecto
    if (!isset($estados_permitidos[$nuevo_estado])) {
        $error = 'Estado no permitido para este proyecto';
    }
    // Validar transición
    elseif (!esTransicionValida($item['estado'], $nuevo_estado) && !$forzar_transicion) {
        $siguiente = getSiguienteEstadoSugerido($item['estado']);
        $error = 'La transición de "' . $estados_permitidos[$item['estado']] . '" a "' 
               . ($estados_permitidos[$nuevo_estado] ?? $nuevo_estado) . '" no es válida.';
        if ($siguiente) {
            $error .= ' Estado sugerido: ' . $estados_permitidos[$siguiente];
        }
        $error .= ' Si estás seguro, marca la casilla "Forzar transición".';
    } 
    else {
        try {
            $estado_anterior = $item['estado'];
            
            $db->beginTransaction();
            
            // Guardar en historial
            $stmt = $db->prepare("
                INSERT INTO historial_items 
                    (item_id, estado_anterior, estado_nuevo, 
                     fecha_anterior, fecha_nueva, usuario_id, comentario)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $comentario_final = $comentario;
            if (!esTransicionValida($estado_anterior, $nuevo_estado) && $forzar_transicion) {
                $comentario_final = '[TRANSICIÓN FORZADA] ' . $comentario;
            }
            
            $stmt->execute([
                $item_id,
                $estado_anterior,
                $nuevo_estado,
                $item['fecha_requerida'],
                $nueva_fecha,
                $_SESSION['usuario_id'],
                $comentario_final
            ]);
            
            // Actualizar item
            $stmt = $db->prepare("
                UPDATE items_proyecto 
                SET estado = ?, fecha_requerida = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nuevo_estado, $nueva_fecha, $item_id]);
            
            // Actualizar columnas de trazabilidad si existen
            // (entrega y recepción)
            try {
                if ($nuevo_estado === 'entregado' && $estado_anterior !== 'entregado') {
                    $stmt = $db->prepare("UPDATE items_proyecto 
                                          SET entregado_por = ?, fecha_entrega = NOW() 
                                          WHERE id = ?");
                    $stmt->execute([$_SESSION['usuario_id'], $item_id]);
                }
                if ($nuevo_estado === 'recibido' && $estado_anterior !== 'recibido') {
                    $stmt = $db->prepare("UPDATE items_proyecto 
                                          SET recibido_por = ?, fecha_recepcion = NOW() 
                                          WHERE id = ?");
                    $stmt->execute([$_SESSION['usuario_id'], $item_id]);
                }
            } catch (PDOException $e) {
                // Las columnas pueden no existir, ignorar silenciosamente
            }
            
            $db->commit();
            
            // Notificar al solicitante si el estado cambió
            if ($estado_anterior !== $nuevo_estado) {
                try {
                    notificarCambioEstadoItem(
                        $db, 
                        $item_id, 
                        $estado_anterior, 
                        $nuevo_estado, 
                        $_SESSION['usuario_id'], 
                        $comentario
                    );
                } catch (Exception $e) {
                    error_log("Error al enviar email de cambio de estado: " . $e->getMessage());
                }
            }
            
            // Guardar toast pendiente para mostrar tras la redirección
            $estado_label = $estados_permitidos[$nuevo_estado] ?? $nuevo_estado;
            $msg = $_SESSION['idioma'] == 'pt'
                ? "Status alterado para: $estado_label"
                : "Estado actualizado a: $estado_label";
            
            // Redirigir con mensaje de éxito
            header('Location: ../proyectos/ver.php?id=' . $proyecto_id . '&mensaje=actualizado');
            exit();
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

$estados_item = getEstadosItem();
$siguiente_estado = getSiguienteEstadoSugerido($item['estado']);
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo traducir('Actualizar Estado'); ?> - <?php echo htmlspecialchars($item['nombre_item']); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/formularios.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/mensajes.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/badges.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/ui.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/notificaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/footer.css'); ?>">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo traducir('Actualizar Estado'); ?></h1>
            <a href="../proyectos/ver.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">
                ← <?php echo traducir('Volver'); ?>
            </a>
        </div>
        
        <div class="form-container">
            <!-- Info del item -->
            <div class="info-message" style="margin-bottom:1.5rem;">
                <h3 style="margin:0 0 0.5rem 0;"><?php echo htmlspecialchars($item['nombre_item']); ?></h3>
                <p style="margin:0; font-size:0.9rem;">
                    <strong><?php echo traducir('Estado'); ?>:</strong> 
                    <span class="estado-badge estado-<?php echo $item['estado']; ?>">
                        <?php echo $estados_item[$item['estado']] ?? $item['estado']; ?>
                    </span>
                    <?php if ($siguiente_estado): ?>
                        <span style="margin-left:1rem; color:#7f8c8d; font-size:0.85rem;">
                            → <?php echo $_SESSION['idioma'] == 'pt' ? 'Sugerido' : 'Sugerido'; ?>:
                            <strong><?php echo $estados_item[$siguiente_estado] ?? $siguiente_estado; ?></strong>
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <strong>⚠</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="form-estado">
                <div class="form-group">
                    <label for="estado"><?php echo traducir('Estado'); ?> *</label>
                    <select id="estado" name="estado" required>
                        <option value="">-- <?php echo traducir('Seleccionar'); ?> --</option>
                        <?php foreach ($estados_permitidos as $key => $value): 
                            $es_sugerido = ($key === $siguiente_estado);
                            $es_actual = ($key === $item['estado']);
                        ?>
                            <option value="<?php echo $key; ?>" 
                                    data-es-sugerido="<?php echo $es_sugerido ? '1' : '0'; ?>"
                                    data-es-actual="<?php echo $es_actual ? '1' : '0'; ?>"
                                    <?php echo $es_sugerido ? 'style="font-weight:bold;"' : ''; ?>>
                                <?php echo $value; ?>
                                <?php if ($es_sugerido): ?> ⭐<?php endif; ?>
                                <?php if ($es_actual): ?> (<?php echo $_SESSION['idioma'] == 'pt' ? 'atual' : 'actual'; ?>)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:#7f8c8d; display:block; margin-top:0.25rem;">
                        <?php echo $_SESSION['idioma'] == 'pt'
                            ? 'Estados com ⭐ são o próximo passo lógico.'
                            : 'Los estados con ⭐ son el siguiente paso lógico.'; ?>
                    </small>
                </div>
                
                <!-- Advertencia de transición (oculta por defecto) -->
                <div id="aviso-transicion" style="display:none; margin-bottom:1rem; padding:0.85rem 1rem; background:#fff3cd; border-left:4px solid #f39c12; border-radius:4px; font-size:0.88rem;">
                    <strong>⚠ <?php echo $_SESSION['idioma'] == 'pt' ? 'Atenção:' : 'Atención:'; ?></strong>
                    <span id="aviso-transicion-texto"></span>
                    <div style="margin-top:0.5rem;">
                        <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                            <input type="checkbox" name="forzar_transicion" id="forzar_transicion" value="1">
                            <?php echo $_SESSION['idioma'] == 'pt' 
                                ? 'Forçar transição (estou ciente de que esta transição pode não ser ideal)'
                                : 'Forzar transición (estoy consciente de que esta transición puede no ser ideal)'; ?>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fecha_requerida"><?php echo traducir('Fecha Requerida'); ?></label>
                    <input type="date" id="fecha_requerida" name="fecha_requerida" 
                           value="<?php echo $item['fecha_requerida']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="comentario"><?php echo traducir('Comentario'); ?></label>
                    <textarea id="comentario" name="comentario" rows="3"
                              placeholder="<?php echo $_SESSION['idioma'] == 'pt' ? 'Observações sobre a mudança...' : 'Observaciones sobre el cambio...'; ?>"></textarea>
                </div>
                
                <div style="display:flex; gap:0.75rem;">
                    <button type="submit" class="btn-primary">
                        💾 <?php echo traducir('Actualizar'); ?>
                    </button>
                    <a href="../proyectos/ver.php?id=<?php echo $proyecto_id; ?>" class="btn-secondary">
                        <?php echo traducir('Cancelar'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Transiciones válidas (duplicadas del PHP para validación cliente)
    const TRANSICIONES_VALIDAS = <?php echo json_encode($transiciones_validas); ?>;
    const ESTADOS_LABELS = <?php echo json_encode($estados_item); ?>;
    const ESTADO_ACTUAL = '<?php echo $item['estado']; ?>';
    const IDIOMA = '<?php echo $_SESSION['idioma']; ?>';
    
    const selectEstado = document.getElementById('estado');
    const aviso = document.getElementById('aviso-transicion');
    const avisoTexto = document.getElementById('aviso-transicion-texto');
    
    function validarTransicion() {
        const nuevoEstado = selectEstado.value;
        
        // Sin cambio o sin estado: ocultar
        if (!nuevoEstado || nuevoEstado === ESTADO_ACTUAL) {
            aviso.style.display = 'none';
            return;
        }
        
        // Verificar si es válida
        const validas = TRANSICIONES_VALIDAS[ESTADO_ACTUAL] || [];
        const esValida = validas.includes(nuevoEstado);
        
        if (!esValida) {
            const labelActual = ESTADOS_LABELS[ESTADO_ACTUAL] || ESTADO_ACTUAL;
            const labelNuevo = ESTADOS_LABELS[nuevoEstado] || nuevoEstado;
            
            avisoTexto.textContent = IDIOMA === 'pt'
                ? `A transição de "${labelActual}" para "${labelNuevo}" não segue o fluxo normal.`
                : `La transición de "${labelActual}" a "${labelNuevo}" no sigue el flujo normal.`;
            
            aviso.style.display = 'block';
        } else {
            aviso.style.display = 'none';
        }
    }
    
    if (selectEstado) {
        selectEstado.addEventListener('change', validarTransicion);
        validarTransicion(); // Verificar al cargar
    }
    
    // Validar al enviar
    document.getElementById('form-estado').addEventListener('submit', function(e) {
        const nuevoEstado = selectEstado.value;
        
        if (!nuevoEstado) {
            e.preventDefault();
            alert(IDIOMA === 'pt' ? 'Selecione um estado.' : 'Seleccione un estado.');
            return;
        }
        
        // Si la transición no es válida y no se forzó, avisar
        const validas = TRANSICIONES_VALIDAS[ESTADO_ACTUAL] || [];
        const esValida = nuevoEstado === ESTADO_ACTUAL || validas.includes(nuevoEstado);
        const forzar = document.getElementById('forzar_transicion');
        
        if (!esValida && (!forzar || !forzar.checked)) {
            e.preventDefault();
            alert(IDIOMA === 'pt'
                ? 'A transição não é válida. Marque "Forçar transição" para continuar.'
                : 'La transición no es válida. Marque "Forzar transición" para continuar.');
        }
    });
    </script>
    
    <script src="<?php echo url('assets/js/notificaciones.js'); ?>"></script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>