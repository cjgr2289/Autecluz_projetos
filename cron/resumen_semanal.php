<?php
/**
 * cron/resumen_semanal.php
 * Script CLI para enviar el resumen semanal de items pendientes.
 * 
 * Uso desde línea de comandos:
 *   php C:\xampp\htdocs\sistema_proyectos\cron\resumen_semanal.php
 * 
 * Programar en Windows Task Scheduler (todos los lunes a las 8:00 AM):
 *   Programa: C:\xampp\php\php.exe
 *   Argumentos: C:\xampp\htdocs\sistema_proyectos\cron\resumen_semanal.php
 *   Iniciar en: C:\xampp\htdocs\sistema_proyectos\cron
 * 
 * Programar en Linux (crontab -e):
 *   0 8 * * 1 php /var/www/html/sistema_proyectos/cron/resumen_semanal.php >> /var/log/resumen_semanal.log 2>&1
 */

// Solo ejecutable por CLI (evita que se ejecute desde el navegador)
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos.\n");
}

// Iniciar sesión ficticia para que funcionen las funciones que dependen de $_SESSION
$_SESSION = [];
$_SESSION['idioma'] = 'es';

// Cargar dependencias
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=================================================\n";
echo "  Resumen Semanal - Sistema de Proyectos\n";
echo "  Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "=================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    $resultado = enviarResumenSemanal($db);
    
    echo "Enviados: {$resultado['enviados']} correo(s)\n";
    
    if (!empty($resultado['errores'])) {
        echo "\nErrores:\n";
        foreach ($resultado['errores'] as $err) {
            echo "  - $err\n";
        }
    }
    
    echo "\n✓ Proceso finalizado.\n";
    
    // Guardar log
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    $linea = date('Y-m-d H:i:s') . " | Enviados: {$resultado['enviados']} | Errores: " . count($resultado['errores']) . "\n";
    @file_put_contents($log_dir . '/resumen_semanal.log', $linea, FILE_APPEND);
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}