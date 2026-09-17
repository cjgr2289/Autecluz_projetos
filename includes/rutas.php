<?php
/**
 * includes/rutas.php
 * Sistema de rutas dinámicas robusto para localhost y hosting.
 * 
 * Estrategia:
 *   1) Detectar la ruta física del archivo actual (__FILE__)
 *   2) Compararla con la raíz del proyecto (dirname de este mismo archivo)
 *   3) Calcular la diferencia = ruta web base
 * 
 * Esto funciona sin importar dónde esté el proyecto en el servidor.
 */

/**
 * Detecta la URL base del proyecto automáticamente.
 * 
 * @return string URL base terminada en "/"
 */
function getBaseUrl() {
    static $base_url = null;
    
    if ($base_url !== null) {
        return $base_url;
    }
    
    // ============================================
    // 1. Detectar protocolo
    // ============================================
    $protocolo = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        $protocolo = 'https';
    }
    
    // ============================================
    // 2. Detectar host (con o sin puerto)
    // ============================================
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // ============================================
    // 3. Calcular base_path comparando rutas físicas
    // ============================================
    
    // Ruta física de ESTE archivo (includes/rutas.php)
    // Ej: C:/xampp/htdocs/sistema_proyectos/includes/rutas.php
    //     /home/user/public_html/includes/rutas.php
    $archivo_actual = str_replace('\\', '/', __FILE__);
    
    // Ruta física de la RAÍZ del proyecto (un nivel arriba de includes/)
    // Ej: C:/xampp/htdocs/sistema_proyectos
    //     /home/user/public_html
    $raiz_proyecto = str_replace('\\', '/', dirname(__DIR__));
    
    // Document root del servidor
    // Ej: C:/xampp/htdocs
    //     /home/user/public_html
    $document_root = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
    
    // Calcular la diferencia entre raíz del proyecto y document root
    // Si el proyecto está dentro de una subcarpeta, esta diferencia es la subcarpeta
    $base_path = '';
    
    if (!empty($document_root) && strpos($raiz_proyecto, $document_root) === 0) {
        // La raíz del proyecto está DENTRO del document root
        // Ej: raiz = C:/xampp/htdocs/sistema_proyectos
        //     doc_root = C:/xampp/htdocs
        //     base_path = /sistema_proyectos
        $base_path = substr($raiz_proyecto, strlen($document_root));
    }
    
    // Normalizar: asegurar que empieza con / y no termina con /
    $base_path = '/' . trim($base_path, '/');
    if ($base_path === '/') {
        $base_path = '';
    }
    
    // ============================================
    // 4. Construir URL base
    // ============================================
    $base_url = $protocolo . '://' . $host . $base_path . '/';
    
    return $base_url;
}

/**
 * Genera una URL completa a partir de una ruta relativa.
 */
function url($ruta = '') {
    $base = getBaseUrl();
    $ruta = ltrim($ruta, '/');
    return $base . $ruta;
}

/**
 * Redirige a una ruta relativa del proyecto.
 */
function redirigir($ruta) {
    header('Location: ' . url($ruta));
    exit();
}

/**
 * Devuelve solo el path (sin dominio).
 */
function path($ruta = '') {
    static $base_path = null;
    
    if ($base_path === null) {
        $full = getBaseUrl();
        $parsed = parse_url($full);
        $base_path = rtrim($parsed['path'] ?? '', '/');
    }
    
    $ruta = ltrim($ruta, '/');
    return $base_path . '/' . $ruta;
}

/**
 * DEBUG: Muestra info de detección (solo para diagnóstico)
 */
function debugRutas() {
    $archivo_actual = str_replace('\\', '/', __FILE__);
    $raiz_proyecto = str_replace('\\', '/', dirname(__DIR__));
    $document_root = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
    
    return [
        'archivo_actual'   => $archivo_actual,
        'raiz_proyecto'    => $raiz_proyecto,
        'document_root'    => $document_root,
        'script_name'      => $_SERVER['SCRIPT_NAME'] ?? '',
        'script_filename'  => $_SERVER['SCRIPT_FILENAME'] ?? '',
        'http_host'        => $_SERVER['HTTP_HOST'] ?? '',
        'base_url'         => getBaseUrl(),
        'base_path'        => path(),
    ];
}