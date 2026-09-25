<?php
/**
 * includes/responsables_helper.php
 * Funciones para manejo del catálogo de responsables
 */

/**
 * Obtiene todos los responsables activos.
 */
function obtenerResponsables($db, $solo_activos = true) {
    $sql = "SELECT r.*, 
                   u.nombre_completo as usuario_nombre,
                   (SELECT COUNT(*) FROM actividades_proyecto a 
                    WHERE a.responsable_id = r.id 
                      AND a.estado IN ('pendiente', 'en_curso', 'atrasada')) as actividades_activas
            FROM responsables r
            LEFT JOIN usuarios u ON r.usuario_id = u.id";
    
    if ($solo_activos) {
        $sql .= " WHERE r.activo = 1";
    }
    
    $sql .= " ORDER BY r.nombre ASC";
    
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

/**
 * Obtiene un responsable por ID.
 */
function obtenerResponsablePorId($db, $id) {
    $stmt = $db->prepare("SELECT r.*, u.nombre_completo as usuario_nombre 
                          FROM responsables r
                          LEFT JOIN usuarios u ON r.usuario_id = u.id
                          WHERE r.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Obtiene el nombre de un responsable por ID (para mostrar).
 */
function getNombreResponsable($db, $id) {
    if (empty($id)) return '-';
    
    $stmt = $db->prepare("SELECT nombre FROM responsables WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    
    return $row ? $row['nombre'] : '-';
}

/**
 * Cuenta las actividades activas de un responsable.
 * 
 * @return array  ['total' => int, 'atrasadas' => int, 'proximas' => int, 'en_curso' => int]
 */
function contarActividadesResponsable($db, $responsable_id) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'atrasada' THEN 1 ELSE 0 END) as atrasadas,
            SUM(CASE WHEN estado = 'en_curso' THEN 1 ELSE 0 END) as en_curso,
            SUM(CASE WHEN estado = 'pendiente' 
                     AND fecha_inicio <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) 
                     THEN 1 ELSE 0 END) as proximas
        FROM actividades_proyecto
        WHERE responsable_id = ?
          AND estado NOT IN ('completada', 'cancelada')
    ");
    $stmt->execute([$responsable_id]);
    $row = $stmt->fetch();
    
    return [
        'total' => (int)($row['total'] ?? 0),
        'atrasadas' => (int)($row['atrasadas'] ?? 0),
        'en_curso' => (int)($row['en_curso'] ?? 0),
        'proximas' => (int)($row['proximas'] ?? 0),
    ];
}

/**
 * Obtiene la lista de actividades activas de un responsable.
 */
function obtenerActividadesResponsable($db, $responsable_id, $limite = null) {
    $sql = "SELECT a.*, 
                   e.nombre as etapa_nombre,
                   p.nombre as proyecto_nombre,
                   p.id as proyecto_id
            FROM actividades_proyecto a
            JOIN etapas_proyecto e ON a.etapa_id = e.id
            JOIN proyectos p ON a.proyecto_id = p.id
            WHERE a.responsable_id = ?
              AND a.estado NOT IN ('completada', 'cancelada')
            ORDER BY a.fecha_fin ASC";
    
    if ($limite) {
        $sql .= " LIMIT " . (int)$limite;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$responsable_id]);
    return $stmt->fetchAll();
}

/**
 * Genera HTML del badge de un responsable.
 */
function badgeResponsable($db, $responsable_id) {
    if (empty($responsable_id)) {
        return '<span class="sin-responsable">— Sin responsable —</span>';
    }
    
    $resp = obtenerResponsablePorId($db, $responsable_id);
    if (!$resp) {
        return '<span class="sin-responsable">— Sin responsable —</span>';
    }
    
    $html = '<span class="badge-responsable">';
    $html .= '<span class="badge-resp-icono">👤</span>';
    $html .= htmlspecialchars($resp['nombre']);
    if (!empty($resp['cargo'])) {
        $html .= ' <small>(' . htmlspecialchars($resp['cargo']) . ')</small>';
    }
    $html .= '</span>';
    
    return $html;
}

/**
 * Verifica si un responsable está siendo usado en alguna actividad.
 */
function responsableEnUso($db, $responsable_id) {
    $stmt = $db->prepare("SELECT COUNT(*) as total 
                          FROM actividades_proyecto 
                          WHERE responsable_id = ?");
    $stmt->execute([$responsable_id]);
    return $stmt->fetch()['total'] > 0;
}

/**
 * Devuelve la carga de trabajo agrupada por responsable.
 */
function obtenerCargaTrabajoPorResponsable($db) {
    $stmt = $db->query("
        SELECT r.id, r.nombre, r.cargo,
               COUNT(a.id) as total_actividades,
               SUM(CASE WHEN a.estado = 'atrasada' THEN 1 ELSE 0 END) as atrasadas,
               SUM(CASE WHEN a.estado = 'en_curso' THEN 1 ELSE 0 END) as en_curso,
               SUM(CASE WHEN a.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
               SUM(CASE WHEN a.estado = 'completada' THEN 1 ELSE 0 END) as completadas
        FROM responsables r
        LEFT JOIN actividades_proyecto a ON a.responsable_id = r.id
        WHERE r.activo = 1
        GROUP BY r.id
        ORDER BY total_actividades DESC, r.nombre ASC
    ");
    return $stmt->fetchAll();
}