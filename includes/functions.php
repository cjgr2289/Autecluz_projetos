<?php
/**
 * includes/functions.php
 * Cargador central de funciones del sistema.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CARGAR SUBMÓDULOS
// ============================================
require_once __DIR__ . '/rutas.php';
require_once __DIR__ . '/formatos.php';
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/traducciones.php';
require_once __DIR__ . '/estados.php';
require_once __DIR__ . '/unidades.php';
require_once __DIR__ . '/productos_helper.php';
require_once __DIR__ . '/notificaciones_helper.php';
require_once __DIR__ . '/reportes_helper.php';
require_once __DIR__ . '/iconos.php';
require_once __DIR__ . '/mailer/funciones_mail.php';
require_once __DIR__ . '/uploads_helper.php';
require_once __DIR__ . '/propuestas_helper.php';
require_once __DIR__ . '/clientes_helper.php';
require_once __DIR__ . '/proyectos_helper.php';