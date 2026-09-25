<?php
/**
 * includes/feriados_helper.php
 * Funciones para manejo de feriados y cálculo de días hábiles
 */

/**
 * Obtiene los feriados activos en un rango de fechas.
 * 
 * @param PDO $db
 * @param string $fecha_desde  Y-m-d
 * @param string $fecha_hasta  Y-m-d
 * @param string|null $pais
 * @param string|null $region
 * @return array  Lista de fechas (Y-m-d)
 */
function obtenerFeriadosRango($db, $fecha_desde, $fecha_hasta, $pais = 'Brasil', $region = null) {
    $sql = "SELECT fecha FROM feriados 
            WHERE activo = 1 
              AND fecha BETWEEN ? AND ?";
    $params = [$fecha_desde, $fecha_hasta];
    
    if ($pais) {
        $sql .= " AND pais = ?";
        $params[] = $pais;
    }
    
    if ($region) {
        $sql .= " AND (region = ? OR region IS NULL)";
        $params[] = $region;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return array_column($stmt->fetchAll(), 'fecha');
}

/**
 * Verifica si una fecha específica es feriado.
 * 
 * @param PDO $db
 * @param string $fecha  Y-m-d
 * @param string|null $pais
 * @param string|null $region
 * @return bool
 */
function esFeriado($db, $fecha, $pais = 'Brasil', $region = null) {
    $sql = "SELECT COUNT(*) as total FROM feriados 
            WHERE activo = 1 
              AND fecha = ?";
    $params = [$fecha];
    
    if ($pais) {
        $sql .= " AND pais = ?";
        $params[] = $pais;
    }
    
    if ($region) {
        $sql .= " AND (region = ? OR region IS NULL)";
        $params[] = $region;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch()['total'] > 0;
}

/**
 * Verifica si una fecha es día hábil (no fin de semana, no feriado).
 * 
 * @param PDO $db
 * @param string $fecha  Y-m-d
 * @param string|null $pais
 * @param string|null $region
 * @return bool
 */
function esDiaHabil($db, $fecha, $pais = 'Brasil', $region = null) {
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return false;
    
    // Verificar fin de semana (1=Lunes, 7=Domingo)
    $dia_semana = (int)date('N', $timestamp);
    if ($dia_semana >= 6) return false;  // Sábado o Domingo
    
    // Verificar feriado
    if (esFeriado($db, $fecha, $pais, $region)) return false;
    
    return true;
}

/**
 * Calcula la fecha fin sumando N días hábiles a partir de una fecha inicio.
 * El día de inicio cuenta como día 1 si es hábil; si no, se mueve al siguiente hábil.
 * 
 * @param PDO $db
 * @param string $fecha_inicio  Y-m-d
 * @param int $duracion_dias
 * @param string|null $pais
 * @param string|null $region
 * @return string|null  Y-m-d
 */
function calcularFechaFinHabil($db, $fecha_inicio, $duracion_dias, $pais = 'Brasil', $region = null) {
    if (empty($fecha_inicio) || $duracion_dias < 1) return null;
    
    $fecha = strtotime($fecha_inicio);
    if ($fecha === false) return null;
    
    // Si el día de inicio no es hábil, mover al siguiente hábil
    $intentos = 0;
    while (!esDiaHabil($db, date('Y-m-d', $fecha), $pais, $region)) {
        $fecha = strtotime('+1 day', $fecha);
        $intentos++;
        if ($intentos > 30) return null;  // Safety
    }
    
    // Contar días hábiles
    $dias_contados = 1;
    while ($dias_contados < $duracion_dias) {
        $fecha = strtotime('+1 day', $fecha);
        
        if (esDiaHabil($db, date('Y-m-d', $fecha), $pais, $region)) {
            $dias_contados++;
        }
        
        // Safety: máximo 5 años (1825 días)
        if ($dias_contados > 1825) break;
    }
    
    return date('Y-m-d', $fecha);
}

/**
 * Calcula la fecha fin usando días calendario (sin excluir fines de semana/feriados).
 */
function calcularFechaFinCalendario($fecha_inicio, $duracion_dias) {
    if (empty($fecha_inicio) || $duracion_dias < 1) return null;
    
    $timestamp = strtotime($fecha_inicio);
    if ($timestamp === false) return null;
    
    // Día 1 es el inicio; sumar duracion - 1
    $fecha_fin = strtotime('+' . ($duracion_dias - 1) . ' days', $timestamp);
    
    return date('Y-m-d', $fecha_fin);
}

/**
 * Cuenta los días hábiles entre dos fechas (inclusive).
 */
function contarDiasHabiles($db, $fecha_desde, $fecha_hasta, $pais = 'Brasil', $region = null) {
    $desde = strtotime($fecha_desde);
    $hasta = strtotime($fecha_hasta);
    
    if ($desde === false || $hasta === false || $desde > $hasta) return 0;
    
    $contador = 0;
    $fecha = $desde;
    
    while ($fecha <= $hasta) {
        if (esDiaHabil($db, date('Y-m-d', $fecha), $pais, $region)) {
            $contador++;
        }
        $fecha = strtotime('+1 day', $fecha);
    }
    
    return $contador;
}

/**
 * Devuelve el siguiente día hábil a partir de una fecha.
 */
function siguienteDiaHabil($db, $fecha, $pais = 'Brasil', $region = null) {
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return null;
    
    $intentos = 0;
    do {
        $timestamp = strtotime('+1 day', $timestamp);
        $intentos++;
        if ($intentos > 30) return null;
    } while (!esDiaHabil($db, date('Y-m-d', $timestamp), $pais, $region));
    
    return date('Y-m-d', $timestamp);
}

/**
 * Devuelve el día hábil anterior a una fecha.
 */
function diaHabilAnterior($db, $fecha, $pais = 'Brasil', $region = null) {
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return null;
    
    $intentos = 0;
    do {
        $timestamp = strtotime('-1 day', $timestamp);
        $intentos++;
        if ($intentos > 30) return null;
    } while (!esDiaHabil($db, date('Y-m-d', $timestamp), $pais, $region));
    
    return date('Y-m-d', $timestamp);
}

/**
 * Obtiene todos los feriados de un año específico.
 */
function obtenerFeriadosAnio($db, $anio, $pais = 'Brasil', $region = null) {
    return obtenerFeriadosRango($db, "$anio-01-01", "$anio-12-31", $pais, $region);
}

/**
 * Formatea una fecha con día de la semana.
 */
function formatearFechaConDia($fecha) {
    if (empty($fecha)) return '-';
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return $fecha;
    
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $dia_semana = $dias[(int)date('w', $timestamp)];
    
    return $dia_semana . ', ' . date('d/m/Y', $timestamp);
}

/**
 * Devuelve un badge HTML del día de la semana.
 */
function badgeDiaSemana($fecha) {
    if (empty($fecha)) return '';
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return '';
    
    $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    $dia = $dias[(int)date('w', $timestamp)];
    
    $clase = 'dia-semana';
    $dia_semana_num = (int)date('N', $timestamp);
    if ($dia_semana_num >= 6) $clase .= ' fin-semana';
    
    return '<span class="' . $clase . '">' . $dia . '</span>';
}