<?php
/**
 * includes/uploads_helper.php
 * Funciones para manejo de archivos subidos (PDFs)
 */

/**
 * Directorio base de uploads (dentro del proyecto)
 */
function getUploadsDir() {
    return dirname(__DIR__) . '/uploads';
}

/**
 * Directorio de propuestas técnicas
 */
function getPropuestasDir() {
    return getUploadsDir() . '/propuestas';
}

/**
 * Devuelve la URL pública del archivo
 */
function getUrlArchivo($ruta_relativa) {
    if (empty($ruta_relativa)) return '';
    return url('uploads/' . ltrim($ruta_relativa, '/'));
}

/**
 * Valida un archivo PDF subido.
 * Devuelve ['ok' => true] o ['ok' => false, 'error' => '...']
 */
function validarPdfSubido($file, $max_size_mb = 20) {
    // Verificar que se subió correctamente
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'vacio' => true]; // sin archivo
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errores = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_TMP_DIR => 'No hay carpeta temporal en el servidor',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco',
            UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP bloqueó la subida',
        ];
        return ['ok' => false, 'error' => $errores[$file['error']] ?? 'Error desconocido al subir'];
    }
    
    // Tamaño
    $max_bytes = $max_size_mb * 1024 * 1024;
    if ($file['size'] > $max_bytes) {
        return ['ok' => false, 'error' => "El archivo supera el tamaño máximo de {$max_size_mb} MB"];
    }
    
    // Tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $mimes_validos = ['application/pdf', 'application/x-pdf'];
    if (!in_array($mime, $mimes_validos)) {
        return ['ok' => false, 'error' => 'El archivo debe ser un PDF válido'];
    }
    
    // Extensión
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return ['ok' => false, 'error' => 'La extensión del archivo debe ser .pdf'];
    }
    
    // Verificar cabecera PDF (magic number %PDF)
    $fp = fopen($file['tmp_name'], 'rb');
    $cabecera = fread($fp, 5);
    fclose($fp);
    if ($cabecera !== '%PDF-') {
        return ['ok' => false, 'error' => 'El archivo no parece ser un PDF válido'];
    }
    
    return ['ok' => true];
}

/**
 * Guarda un PDF subido en la carpeta de propuestas.
 * Devuelve ['ok' => true, 'archivo' => 'propuestas/nombre.pdf', 'nombre_original' => 'xxx.pdf']
 */
function guardarPdfPropuesta($file, $proyecto_id) {
    $validacion = validarPdfSubido($file);
    if (!$validacion['ok']) {
        return $validacion;
    }
    if (!empty($validacion['vacio'])) {
        return ['ok' => true, 'vacio' => true];
    }
    
    // Asegurar que existe la carpeta
    $dir = getPropuestasDir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    
    if (!is_writable($dir)) {
        return ['ok' => false, 'error' => 'La carpeta de uploads no tiene permisos de escritura'];
    }
    
    // Generar nombre único
    $nombre_original = $file['name'];
    $ext = 'pdf';
    $nombre_archivo = 'propuesta_proyecto_' . $proyecto_id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $ruta_fisica = $dir . '/' . $nombre_archivo;
    
    // Mover
    if (!move_uploaded_file($file['tmp_name'], $ruta_fisica)) {
        return ['ok' => false, 'error' => 'No se pudo guardar el archivo en el servidor'];
    }
    
    return [
        'ok'              => true,
        'archivo'         => 'propuestas/' . $nombre_archivo,
        'nombre_original' => $nombre_original,
    ];
}

/**
 * Elimina un archivo físico de propuesta.
 */
function eliminarPdfPropuesta($ruta_relativa) {
    if (empty($ruta_relativa)) return true;
    
    $ruta_fisica = getUploadsDir() . '/' . ltrim($ruta_relativa, '/');
    
    // Seguridad: verificar que está dentro de uploads
    $real = realpath($ruta_fisica);
    $base = realpath(getUploadsDir());
    
    if ($real === false || $base === false || strpos($real, $base) !== 0) {
        return false;
    }
    
    if (file_exists($ruta_fisica)) {
        return @unlink($ruta_fisica);
    }
    
    return true;
}

/**
 * Devuelve el tamaño de un archivo en formato legible.
 */
function getTamañoArchivo($ruta_relativa) {
    if (empty($ruta_relativa)) return '';
    
    $ruta_fisica = getUploadsDir() . '/' . ltrim($ruta_relativa, '/');
    if (!file_exists($ruta_fisica)) return '';
    
    $bytes = filesize($ruta_fisica);
    
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}