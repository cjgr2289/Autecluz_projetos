<?php
/**
 * includes/sesion.php
 * Funciones de sesión, autenticación y permisos
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirige al login si no hay sesión activa
 */
function verificarSesion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . url('modules/login/login.php'));
        exit();
    }
}

/**
 * Verifica si el usuario actual tiene uno de los tipos permitidos
 */
function tienePermiso($tipos_permitidos) {
    if (!isset($_SESSION['tipo_usuario'])) {
        return false;
    }
    return in_array($_SESSION['tipo_usuario'], $tipos_permitidos);
}

/**
 * Verifica si el usuario actual es el Master (Administrador supremo)
 */
function esMaster() {
    return isset($_SESSION['username']) && $_SESSION['username'] === 'Master';
}

/**
 * Bloquea el acceso si el usuario no es Master
 */
function requiereMaster() {
    if (!esMaster()) {
        header('Location: ' . url('index.php'));
        exit();
    }
}