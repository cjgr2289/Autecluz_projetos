<?php
/**
 * includes/productos_helper.php
 * Helpers relacionados con productos del catálogo
 */

/**
 * Busca productos en el catálogo con filtros opcionales
 * 
 * @param PDO $db
 * @param string $busqueda       Texto a buscar (nombre, código o descripción)
 * @param int|null $categoria_id Filtrar por categoría
 * @param int $limit             Límite de resultados
 * @return array
 */
function buscarProductos($db, $busqueda = '', $categoria_id = null, $limit = 50) {
    $query = "SELECT p.*, c.nombre as categoria_nombre 
              FROM productos p 
              LEFT JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.activo = 1";
    $params = [];
    
    if (!empty($busqueda)) {
        $query .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR p.descripcion LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    if (!empty($categoria_id)) {
        $query .= " AND p.categoria_id = ?";
        $params[] = $categoria_id;
    }
    
    $query .= " ORDER BY p.nombre ASC LIMIT " . intval($limit);
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}