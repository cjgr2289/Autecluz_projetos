<?php
/**
 * includes/functions.php
 * Cargador central de funciones del sistema.
 * Importa todos los módulos de funciones.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CARGAR SUBMÓDULOS
// ============================================
require_once __DIR__ . '/formatos.php';
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/traducciones.php';
require_once __DIR__ . '/estados.php';
require_once __DIR__ . '/productos_helper.php';
require_once __DIR__ . '/mailer/funciones_mail.php'; // CARGAR FUNCIONES DE EMAIL
require_once __DIR__ . '/unidades.php';