<?php
/**
 * includes/formatos.php
 * Funciones de formato de datos (fechas, números, textos)
 */

/**
 * Formatea una fecha según el idioma del usuario.
 * - es: dd/mm/yyyy
 * - pt: dd/mm/yyyy  (Brasil usa el mismo formato numérico)
 * 
 * @param string|null $fecha  Fecha en formato Y-m-d o datetime
 * @param string $separador   Separador entre día/mes/año (por defecto '/')
 * @return string
 */
function formatearFecha($fecha, $separador = '/') {
    if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
        return '-';
    }
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return $fecha;
    }
    
    // Ambos idiomas usan dd/mm/yyyy
    return date('d' . $separador . 'm' . $separador . 'Y', $timestamp);
}

/**
 * Formatea fecha + hora según el idioma del usuario.
 * 
 * @param string|null $fecha
 * @return string
 */
function formatearFechaHora($fecha) {
    if (empty($fecha) || $fecha === '0000-00-00 00:00:00') {
        return '-';
    }
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return $fecha;
    }
    
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Formatea un número con separador de miles según idioma.
 * - es: 1.234,56
 * - pt: 1.234,56 (Brasil usa punto para miles y coma para decimales)
 * 
 * @param float|int $numero
 * @param int $decimales
 * @return string
 */
function formatearNumero($numero, $decimales = 2) {
    return number_format((float)$numero, $decimales, ',', '.');
}

/**
 * Trunca un texto a un número de caracteres, agregando "..." si es necesario.
 * 
 * @param string $texto
 * @param int $largo
 * @param string $sufijo
 * @return string
 */
function truncarTexto($texto, $largo = 60, $sufijo = '...') {
    if (mb_strlen($texto) <= $largo) {
        return $texto;
    }
    return mb_strimwidth($texto, 0, $largo, $sufijo);
}