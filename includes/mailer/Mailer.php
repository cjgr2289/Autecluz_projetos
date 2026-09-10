<?php
/**
 * includes/mailer/Mailer.php
 * Clase wrapper sobre PHPMailer para simplificar el envío de correos
 */

// Cargar PHPMailer manualmente
require_once __DIR__ . '/../../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../lib/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Mailer {
    private $config;
    private $mail;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../../config/mailer.php';
    }
    
    /**
     * Inicializa PHPMailer con la configuración SMTP
     */
    private function initMailer() {
        $mail = new PHPMailer(true);
        
        // Debug (opcional)
        if ($this->config['debug'] > 0) {
            $mail->SMTPDebug = $this->config['debug'];
            $mail->Debugoutput = 'error_log';
        }
        
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = $this->config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->config['username'];
        $mail->Password   = $this->config['password'];
        $mail->SMTPSecure = $this->config['encryption'] === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $this->config['port'];
        $mail->CharSet    = $this->config['charset'];
        $mail->Timeout    = $this->config['timeout'];
        
        // Opciones SSL para aceptar certificados
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];
        
        // Remitente
        $mail->setFrom($this->config['from_email'], $this->config['from_name']);
        
        return $mail;
    }
    
    /**
     * Envía un correo
     * 
     * @param string|array $to       Email(s) destinatario(s)
     * @param string $subject        Asunto
     * @param string $htmlBody       Cuerpo HTML
     * @param string $altBody        Cuerpo texto plano (fallback)
     * @param array  $attachments    Adjuntos: [['path'=>'...', 'name'=>'...'], ...]
     * @return bool
     */
    public function enviar($to, $subject, $htmlBody, $altBody = '', $attachments = []) {
        try {
            $mail = $this->initMailer();
            
            // Destinatarios
            $destinatarios = is_array($to) ? $to : [$to];
            foreach ($destinatarios as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($email);
                }
            }
            
            if (count($mail->getToAddresses()) === 0) {
                $this->log("Sin destinatarios válidos: " . json_encode($to));
                return false;
            }
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);
            
            // Adjuntos
            foreach ($attachments as $att) {
                if (!empty($att['path']) && file_exists($att['path'])) {
                    $mail->addAttachment($att['path'], $att['name'] ?? '');
                }
            }
            
            $mail->send();
            $this->log("OK -> " . implode(', ', $destinatarios) . " | Asunto: $subject");
            return true;
            
        } catch (Exception $e) {
            $this->log("ERROR -> " . $e->getMessage() . " | " . $this->mail->ErrorInfo ?? '');
            return false;
        } catch (\Exception $e) {
            $this->log("ERROR GENERAL -> " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Guarda una entrada en el log de envíos
     */
    private function log($mensaje) {
        if (!$this->config['log_emails']) return;
        
        $dir = dirname($this->config['log_file']);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        $linea = date('Y-m-d H:i:s') . " | $mensaje" . PHP_EOL;
        @file_put_contents($this->config['log_file'], $linea, FILE_APPEND);
    }
}