<?php
/**
 * includes/cronograma_helper.php
 * Funciones para manejo de etapas y actividades del cronograma
 */

// ============================================
// ETAPAS
// ============================================

/**
 * Obtiene las etapas de un proyecto ordenadas.
 */
function obtenerEtapasProyecto($db, $proyecto_id) {
    $stmt = $db->prepare("
        SELECT e.*,
               u.nombre_completo as creador,
               (SELECT COUNT(*) FROM actividades_proyecto a WHERE a.etapa_id = e.id) as total_actividades,
               (SELECT COUNT(*) FROM actividades_proyecto a WHERE a.etapa_id = e.id AND a.estado = 'completada') as actividades_completadas,
               (SELECT COUNT(*) FROM actividades_proyecto a WHERE a.etapa_id = e.id AND a.estado = 'atrasada') as actividades_atrasadas
        FROM etapas_proyecto e
        LEFT JOIN usuarios u ON e.usuario_creacion = u.id
        WHERE e.proyecto_id = ?
        ORDER BY e.orden ASC, e.id ASC
    ");
    $stmt->execute([$proyecto_id]);
    return $stmt->fetchAll();
}

/**
 * Obtiene una etapa por ID.
 */
function obtenerEtapaPorId($db, $etapa_id) {
    $stmt = $db->prepare("SELECT * FROM etapas_proyecto WHERE id = ?");
    $stmt->execute([$etapa_id]);
    return $stmt->fetch();
}

/**
 * Calcula el porcentaje de avance de una etapa.
 */
function calcularAvanceEtapa($db, $etapa_id) {
    $stmt = $db->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas
        FROM actividades_proyecto 
        WHERE etapa_id = ?
    ");
    $stmt->execute([$etapa_id]);
    $row = $stmt->fetch();
    
    if ($row['total'] == 0) return 0;
    
    return round(($row['completadas'] / $row['total']) * 100);
}

/**
 * Actualiza el estado de una etapa según el estado de sus actividades.
 * Si todas están completadas → etapa completada.
 */
function actualizarEstadoEtapa($db, $etapa_id) {
    $stmt = $db->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas
        FROM actividades_proyecto 
        WHERE etapa_id = ?
    ");
    $stmt->execute([$etapa_id]);
    $row = $stmt->fetch();
    
    if ($row['total'] == 0) return;
    
    $nuevo_estado = null;
    
    if ($row['total'] == $row['completadas']) {
        $nuevo_estado = 'completada';
    } else {
        // Si hay al menos una en curso o completada, marcar como en_curso
        $stmt = $db->prepare("
            SELECT COUNT(*) as activas
            FROM actividades_proyecto 
            WHERE etapa_id = ? AND estado IN ('en_curso', 'completada')
        ");
        $stmt->execute([$etapa_id]);
        if ($stmt->fetch()['activas'] > 0) {
            $nuevo_estado = 'en_curso';
        } else {
            $nuevo_estado = 'pendiente';
        }
    }
    
    $stmt = $db->prepare("UPDATE etapas_proyecto SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $etapa_id]);
}

/**
 * Reordena las etapas de un proyecto.
 * 
 * @param PDO $db
 * @param int $proyecto_id
 * @param array $orden  [ ['id' => 1, 'orden' => 0], ['id' => 2, 'orden' => 1], ... ]
 */
function reordenarEtapas($db, $proyecto_id, $orden) {
    $stmt = $db->prepare("UPDATE etapas_proyecto SET orden = ? WHERE id = ? AND proyecto_id = ?");
    foreach ($orden as $item) {
        $stmt->execute([(int)$item['orden'], (int)$item['id'], $proyecto_id]);
    }
    return true;
}

// ============================================
// ACTIVIDADES
// ============================================

/**
 * Obtiene las actividades de una etapa (con sub-actividades anidadas).
 */
function obtenerActividadesEtapa($db, $etapa_id, $solo_principales = true) {
    $sql = "
        SELECT a.*,
               r.nombre as responsable_nombre,
               r.cargo as responsable_cargo,
               u.nombre_completo as creador,
               i.nombre_item as item_nombre,
               dep.nombre as depende_de_nombre,
               (SELECT COUNT(*) FROM actividades_proyecto sub WHERE sub.parent_id = a.id) as total_subactividades
        FROM actividades_proyecto a
        LEFT JOIN responsables r ON a.responsable_id = r.id
        LEFT JOIN usuarios u ON a.usuario_creacion = u.id
        LEFT JOIN items_proyecto i ON a.item_id = i.id
        LEFT JOIN actividades_proyecto dep ON a.depende_de_id = dep.id
        WHERE a.etapa_id = ?
    ";
    
    if ($solo_principales) {
        $sql .= " AND a.parent_id IS NULL";
    }
    
    $sql .= " ORDER BY a.orden ASC, a.id ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$etapa_id]);
    $actividades = $stmt->fetchAll();
    
    // Cargar sub-actividades
    foreach ($actividades as &$act) {
        $stmt = $db->prepare("
            SELECT a.*,
                   r.nombre as responsable_nombre,
                   r.cargo as responsable_cargo
            FROM actividades_proyecto a
            LEFT JOIN responsables r ON a.responsable_id = r.id
            WHERE a.parent_id = ?
            ORDER BY a.orden ASC
        ");
        $stmt->execute([$act['id']]);
        $act['subactividades'] = $stmt->fetchAll();
    }
    
    return $actividades;
}

/**
 * Obtiene TODAS las actividades de un proyecto (plano, sin jerarquía).
 */
function obtenerActividadesProyecto($db, $proyecto_id) {
    $stmt = $db->prepare("
        SELECT a.*,
               e.nombre as etapa_nombre,
               e.color as etapa_color,
               r.nombre as responsable_nombre,
               r.cargo as responsable_cargo,
               i.nombre_item as item_nombre
        FROM actividades_proyecto a
        JOIN etapas_proyecto e ON a.etapa_id = e.id
        LEFT JOIN responsables r ON a.responsable_id = r.id
        LEFT JOIN items_proyecto i ON a.item_id = i.id
        WHERE a.proyecto_id = ?
        ORDER BY e.orden ASC, a.orden ASC, a.id ASC
    ");
    $stmt->execute([$proyecto_id]);
    return $stmt->fetchAll();
}

/**
 * Obtiene una actividad por ID.
 */
function obtenerActividadPorId($db, $actividad_id) {
    $stmt = $db->prepare("
        SELECT a.*,
               e.nombre as etapa_nombre,
               e.proyecto_id,
               r.nombre as responsable_nombre,
               r.cargo as responsable_cargo,
               i.nombre_item as item_nombre
        FROM actividades_proyecto a
        JOIN etapas_proyecto e ON a.etapa_id = e.id
        LEFT JOIN responsables r ON a.responsable_id = r.id
        LEFT JOIN items_proyecto i ON a.item_id = i.id
        WHERE a.id = ?
    ");
    $stmt->execute([$actividad_id]);
    return $stmt->fetch();
}

/**
 * Cuenta las actividades de un proyecto agrupadas por estado.
 */
function contarActividadesProyecto($db, $proyecto_id) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'en_curso' THEN 1 ELSE 0 END) as en_curso,
            SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN estado = 'atrasada' THEN 1 ELSE 0 END) as atrasadas,
            SUM(CASE WHEN estado = 'pausada' THEN 1 ELSE 0 END) as pausadas,
            SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
        FROM actividades_proyecto
        WHERE proyecto_id = ?
    ");
    $stmt->execute([$proyecto_id]);
    $row = $stmt->fetch();
    
    return [
        'total' => (int)($row['total'] ?? 0),
        'pendientes' => (int)($row['pendientes'] ?? 0),
        'en_curso' => (int)($row['en_curso'] ?? 0),
        'completadas' => (int)($row['completadas'] ?? 0),
        'atrasadas' => (int)($row['atrasadas'] ?? 0),
        'pausadas' => (int)($row['pausadas'] ?? 0),
        'canceladas' => (int)($row['canceladas'] ?? 0),
    ];
}

/**
 * Reordena las actividades de una etapa.
 */
function reordenarActividades($db, $etapa_id, $orden) {
    $stmt = $db->prepare("UPDATE actividades_proyecto SET orden = ? WHERE id = ? AND etapa_id = ?");
    foreach ($orden as $item) {
        $stmt->execute([(int)$item['orden'], (int)$item['id'], $etapa_id]);
    }
    return true;
}

/**
 * Registra un cambio en el historial de una actividad.
 */
function registrarHistorialActividad($db, $actividad_id, $campo, $anterior, $nuevo, $comentario = '') {
    $stmt = $db->prepare("
        INSERT INTO actividades_historial 
            (actividad_id, campo_modificado, valor_anterior, valor_nuevo, usuario_id, comentario)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $actividad_id,
        $campo,
        (string)$anterior,
        (string)$nuevo,
        $_SESSION['usuario_id'] ?? null,
        $comentario
    ]);
}

/**
 * Devuelve el historial de una actividad.
 */
function obtenerHistorialActividad($db, $actividad_id, $limite = 50) {
    $stmt = $db->prepare("
        SELECT h.*, u.nombre_completo as usuario
        FROM actividades_historial h
        LEFT JOIN usuarios u ON h.usuario_id = u.id
        WHERE h.actividad_id = ?
        ORDER BY h.fecha_cambio DESC
        LIMIT " . (int)$limite
    );
    $stmt->execute([$actividad_id]);
    return $stmt->fetchAll();
}

// ============================================
// NOTIFICACIONES DE ACTIVIDADES
// ============================================

/**
 * Obtiene actividades atrasadas (fecha_fin < hoy, no completadas).
 */
function obtenerActividadesAtrasadas($db, $proyecto_id = null) {
    $sql = "
        SELECT a.*,
               e.nombre as etapa_nombre,
               p.nombre as proyecto_nombre,
               p.id as proyecto_id,
               r.nombre as responsable_nombre,
               DATEDIFF(CURDATE(), a.fecha_fin) as dias_atraso
        FROM actividades_proyecto a
        JOIN etapas_proyecto e ON a.etapa_id = e.id
        JOIN proyectos p ON a.proyecto_id = p.id
        LEFT JOIN responsables r ON a.responsable_id = r.id
        WHERE a.estado NOT IN ('completada', 'cancelada')
          AND a.fecha_fin < CURDATE()
    ";
    $params = [];
    
    if ($proyecto_id) {
        $sql .= " AND a.proyecto_id = ?";
        $params[] = $proyecto_id;
    }
    
    $sql .= " ORDER BY a.fecha_fin ASC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtiene actividades próximas a vencer (entre hoy y hoy + N días).
 */
function obtenerActividadesProximasVencer($db, $dias = 3, $proyecto_id = null) {
    $sql = "
        SELECT a.*,
               e.nombre as etapa_nombre,
               p.nombre as proyecto_nombre,
               p.id as proyecto_id,
               r.nombre as responsable_nombre,
               DATEDIFF(a.fecha_fin, CURDATE()) as dias_restantes
        FROM actividades_proyecto a
        JOIN etapas_proyecto e ON a.etapa_id = e.id
        JOIN proyectos p ON a.proyecto_id = p.id
        LEFT JOIN responsables r ON a.responsable_id = r.id
        WHERE a.estado NOT IN ('completada', 'cancelada')
          AND a.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ";
    $params = [$dias];
    
    if ($proyecto_id) {
        $sql .= " AND a.proyecto_id = ?";
        $params[] = $proyecto_id;
    }
    
    $sql .= " ORDER BY a.fecha_fin ASC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtiene actividades que deben comenzar hoy o en los próximos N días.
 */
function obtenerActividadesPorComenzar($db, $dias = 1, $proyecto_id = null) {
    $sql = "
        SELECT a.*,
               e.nombre as etapa_nombre,
               p.nombre as proyecto_nombre,
               p.id as proyecto_id,
               r.nombre as responsable_nombre,
               DATEDIFF(a.fecha_inicio, CURDATE()) as dias_para_inicio
        FROM actividades_proyecto a
        JOIN etapas_proyecto e ON a.etapa_id = e.id
        JOIN proyectos p ON a.proyecto_id = p.id
        LEFT JOIN responsables r ON a.responsable_id = r.id
        WHERE a.estado = 'pendiente'
          AND a.fecha_inicio BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ";
    $params = [$dias];
    
    if ($proyecto_id) {
        $sql .= " AND a.proyecto_id = ?";
        $params[] = $proyecto_id;
    }
    
    $sql .= " ORDER BY a.fecha_inicio ASC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Actualiza el estado de actividades atrasadas (para cron).
 * Devuelve el número de actividades actualizadas.
 */
function actualizarActividadesAtrasadas($db) {
    $stmt = $db->prepare("
        UPDATE actividades_proyecto 
        SET estado = 'atrasada'
        WHERE estado IN ('pendiente', 'en_curso')
          AND fecha_fin < CURDATE()
    ");
    $stmt->execute();
    return $stmt->rowCount();
}

// ============================================
// HELPERS DE ESTADOS
// ============================================

/**
 * Devuelve los estados posibles de una actividad (traducidos).
 */
function getEstadosActividad() {
    $idioma = $_SESSION['idioma'] ?? 'es';
    
    $estados = [
        'es' => [
            'pendiente'  => 'Pendiente',
            'en_curso'   => 'En Curso',
            'completada' => 'Completada',
            'atrasada'   => 'Atrasada',
            'pausada'    => 'Pausada',
            'cancelada'  => 'Cancelada',
        ],
        'pt' => [
            'pendiente'  => 'Pendente',
            'en_curso'   => 'Em Andamento',
            'completada' => 'Concluída',
            'atrasada'   => 'Atrasada',
            'pausada'    => 'Pausada',
            'cancelada'  => 'Cancelada',
        ]
    ];
    
    return $estados[$idioma] ?? $estados['es'];
}

/**
 * Devuelve las prioridades posibles (traducidas).
 */
function getPrioridadesActividad() {
    $idioma = $_SESSION['idioma'] ?? 'es';
    
    $prioridades = [
        'es' => [
            'baja'    => 'Baja',
            'media'   => 'Media',
            'alta'    => 'Alta',
            'critica' => 'Crítica',
        ],
        'pt' => [
            'baja'    => 'Baixa',
            'media'   => 'Média',
            'alta'    => 'Alta',
            'critica' => 'Crítica',
        ]
    ];
    
    return $prioridades[$idioma] ?? $prioridades['es'];
}

/**
 * Devuelve los estados posibles de una etapa.
 */
function getEstadosEtapa() {
    $idioma = $_SESSION['idioma'] ?? 'es';
    
    $estados = [
        'es' => [
            'pendiente'  => 'Pendiente',
            'en_curso'   => 'En Curso',
            'completada' => 'Completada',
            'cancelada'  => 'Cancelada',
        ],
        'pt' => [
            'pendiente'  => 'Pendente',
            'en_curso'   => 'Em Andamento',
            'completada' => 'Concluída',
            'cancelada'  => 'Cancelada',
        ]
    ];
    
    return $estados[$idioma] ?? $estados['es'];
}

/**
 * Devuelve el icono según el estado de una actividad.
 */
function getIconoEstadoActividad($estado) {
    $iconos = [
        'pendiente'  => '⏸️',
        'en_curso'   => '🟢',
        'completada' => '✅',
        'atrasada'   => '🔴',
        'pausada'    => '⏸️',
        'cancelada'  => '❌',
    ];
    
    return $iconos[$estado] ?? '❓';
}

/**
 * Devuelve el color (hex) según el estado de una actividad.
 */
function getColorEstadoActividad($estado) {
    $colores = [
        'pendiente'  => '#95a5a6',
        'en_curso'   => '#3498db',
        'completada' => '#27ae60',
        'atrasada'   => '#e74c3c',
        'pausada'    => '#f39c12',
        'cancelada'  => '#7f8c8d',
    ];
    
    return $colores[$estado] ?? '#95a5a6';
}

/**
 * Calcula el estado sugerido según las fechas.
 */
function getEstadoSugeridoPorFechas($fecha_inicio, $fecha_fin, $estado_actual) {
    if (in_array($estado_actual, ['completada', 'cancelada', 'pausada'])) {
        return $estado_actual;
    }
    
    $hoy = date('Y-m-d');
    
    if ($fecha_fin < $hoy) return 'atrasada';
    if ($fecha_inicio <= $hoy && $fecha_fin >= $hoy) return 'en_curso';
    
    return 'pendiente';
}