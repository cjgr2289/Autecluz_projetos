<?php
/**
 * config/mailer.php
 * Configuración del servidor SMTP para el envío de correos
 */

return [
    // ===== Servidor SMTP =====
    'host'       => 'mail.autecluz.com',
    'port'       => 465,                       // SSL
    'encryption' => 'ssl',                     // 'ssl' o 'tls'
    
    // ===== Credenciales =====
    'username'   => 'gestionProjeto@autecluz.com',
    'password'   => 'Aut3cluz.26*',
    
    // ===== Remitente =====
    'from_email' => 'gestionProjeto@autecluz.com',
    'from_name'  => 'Sistema de Proyectos',
    
    // ===== Opciones =====
    'charset'    => 'UTF-8',
    'debug'      => 0,                         // 0=off, 1=cliente, 2=servidor
    'timeout'    => 30,
    
    // ===== Log =====
    'log_emails' => true,                      // Guardar log de envíos
    'log_file'   => __DIR__ . '/../logs/emails.log',
];