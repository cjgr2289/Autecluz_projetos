<?php
/**
 * includes/clientes_helper.php
 * Funciones para manejar clientes y sus responsables
 */

/**
 * Obtiene todos los clientes activos.
 */
function obtenerClientes($db, $solo_activos = true) {
    $sql = "SELECT c.*, 
                   (SELECT COUNT(*) FROM clientes_responsables cr WHERE cr.cliente_id = c.id AND cr.activo = 1) as total_responsables
            FROM clientes c";
    
    if ($solo_activos) {
        $sql .= " WHERE c.activo = 1";
    }
    
    $sql .= " ORDER BY c.nombre ASC";
    
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

/**
 * Obtiene un cliente por ID.
 */
function obtenerClientePorId($db, $id) {
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Obtiene los responsables activos de un cliente.
 */
function obtenerResponsablesCliente($db, $cliente_id, $solo_activos = true) {
    $sql = "SELECT * FROM clientes_responsables WHERE cliente_id = ?";
    if ($solo_activos) {
        $sql .= " AND activo = 1";
    }
    $sql .= " ORDER BY nombre ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$cliente_id]);
    return $stmt->fetchAll();
}

/**
 * Obtiene un responsable por ID.
 */
function obtenerResponsablePorId($db, $id) {
    $stmt = $db->prepare("SELECT cr.*, c.nombre as cliente_nombre 
                          FROM clientes_responsables cr
                          JOIN clientes c ON cr.cliente_id = c.id
                          WHERE cr.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Obtiene los responsables de un cliente en formato JSON (para AJAX).
 */
function obtenerResponsablesAjax($db, $cliente_id) {
    $stmt = $db->prepare("SELECT id, nombre, cargo, email, telefono, celular 
                          FROM clientes_responsables 
                          WHERE cliente_id = ? AND activo = 1 
                          ORDER BY nombre");
    $stmt->execute([$cliente_id]);
    return $stmt->fetchAll();
}

/**
 * Genera un resumen formateado del cliente + responsable para mostrar.
 */
function formatoClienteResponsable($cliente, $responsable) {
    $partes = [];
    
    if ($cliente) {
        $partes[] = $cliente['nombre'];
    }
    
    if ($responsable) {
        $partes[] = $responsable['nombre'] . ($responsable['cargo'] ? ' (' . $responsable['cargo'] . ')' : '');
    }
    
    return implode(' — ', $partes) ?: '—';
}