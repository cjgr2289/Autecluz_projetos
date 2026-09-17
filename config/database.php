<?php
/**
 * config/database.php
 * Conexión a la base de datos con detección automática de entorno.
 * 
 * 1. Intenta conectar a LOCAL (localhost / XAMPP)
 * 2. Si falla, intenta con el HOSTING remoto
 * 
 * Uso:
 *   $db = Database::getInstance()->getConnection();
 */

class Database {
    private static $instance = null;
    private $conn;
    private $entorno_actual = 'desconocido';
    
    // ============================================
    // CONFIGURACIONES DE CONEXIÓN
    // ============================================
    
    /**
     * Entorno LOCAL (XAMPP / WAMP / LAMP)
     */
    private $config_local = [
        'host'     => 'localhost',
        'port'     => 3306,
        'dbname'   => 'sistema_proyectos',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ];
    
    /**
     * Entorno HOSTING (Producción)
     */
    private $config_hosting = [
        'host'     => '186.209.113.99',
        'port'     => 3306,
        'dbname'   => 'autecluz_Projetos',
        'username' => 'autecluz_carlosgomez',
        'password' => '$Aut3cluz_2026*',
        'charset'  => 'utf8mb4',
    ];
    
    // ============================================
    // CONSTRUCTOR PRIVADO (Singleton)
    // ============================================
    
    private function __construct() {
        $this->conn = $this->conectarConFallback();
    }
    
    /**
     * Intenta conectar a LOCAL primero, si falla usa HOSTING.
     * Devuelve la conexión PDO activa o lanza una excepción.
     */
    private function conectarConFallback() {
        // 1er intento: LOCAL
        try {
            $conn = $this->crearConexion($this->config_local);
            $this->entorno_actual = 'local';
            $this->log("✓ Conectado a LOCAL ({$this->config_local['dbname']})");
            return $conn;
        } catch (PDOException $e) {
            $this->log("✗ Falló LOCAL: " . $e->getMessage());
        }
        
        // 2do intento: HOSTING
        try {
            $conn = $this->crearConexion($this->config_hosting);
            $this->entorno_actual = 'hosting';
            $this->log("✓ Conectado a HOSTING ({$this->config_hosting['dbname']})");
            return $conn;
        } catch (PDOException $e) {
            $this->log("✗ Falló HOSTING: " . $e->getMessage());
        }
        
        // Si ambos fallan, detener con un error claro
        $this->mostrarErrorConexion();
    }
    
    /**
     * Crea una conexión PDO con la configuración dada.
     */
    private function crearConexion($config) {
        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=%s",
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );
        
        return new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 5,  // 5 segundos de timeout
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]
        );
    }
    
    /**
     * Muestra un error amigable si ninguna conexión funcionó.
     */
    private function mostrarErrorConexion() {
        $this->log("✗ No se pudo conectar a ningún entorno (local ni hosting)");
        
        // Detectar si es petición AJAX (JSON) o normal (HTML)
        $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        $esAjax = $esAjax || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        $esAjax = $esAjax || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
        
        if ($esAjax) {
            header('Content-Type: application/json; charset=utf-8', true, 500);
            echo json_encode([
                'success' => false,
                'error'   => 'No se pudo conectar a la base de datos',
                'detalle' => 'Ni el servidor local ni el hosting respondieron correctamente.'
            ]);
        } else {
            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Error de Conexión</title>
                <style>
                    body {
                        font-family: 'Segoe UI', Arial, sans-serif;
                        background: #f5f6fa;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                        margin: 0;
                        padding: 1rem;
                    }
                    .error-box {
                        background: white;
                        border-radius: 12px;
                        padding: 2.5rem;
                        max-width: 500px;
                        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                        text-align: center;
                        border-left: 5px solid #e74c3c;
                    }
                    .error-icon {
                        font-size: 3rem;
                        margin-bottom: 1rem;
                    }
                    h1 {
                        color: #2c3e50;
                        margin: 0 0 0.5rem 0;
                        font-size: 1.4rem;
                    }
                    p {
                        color: #7f8c8d;
                        line-height: 1.6;
                        margin: 0.5rem 0;
                        font-size: 0.95rem;
                    }
                    .detalle {
                        margin-top: 1.5rem;
                        padding: 1rem;
                        background: #f8f9fa;
                        border-radius: 6px;
                        font-size: 0.82rem;
                        color: #555;
                        text-align: left;
                        font-family: monospace;
                        border-left: 3px solid #dee2e6;
                    }
                    .detalle strong {
                        color: #2c3e50;
                    }
                    .acciones {
                        margin-top: 1.5rem;
                        display: flex;
                        gap: 0.75rem;
                        justify-content: center;
                    }
                    .btn {
                        padding: 0.65rem 1.25rem;
                        border-radius: 6px;
                        text-decoration: none;
                        font-size: 0.9rem;
                        font-weight: 600;
                        background: #3498db;
                        color: white;
                        border: none;
                        cursor: pointer;
                    }
                    .btn:hover {
                        background: #2980b9;
                    }
                </style>
            </head>
            <body>
                <div class="error-box">
                    <div class="error-icon">🔌</div>
                    <h1>No se pudo conectar a la base de datos</h1>
                    <p>El sistema intentó conectarse primero al servidor <strong>local</strong> y luego al <strong>hosting</strong>, pero ambos fallaron.</p>
                    
                    <div class="detalle">
                        <strong>Verifique:</strong><br>
                        • Que MySQL esté corriendo en local (XAMPP)<br>
                        • Que el servidor de hosting esté disponible<br>
                        • Que las credenciales sean correctas<br>
                        • Su conexión a internet
                    </div>
                    
                    <div class="acciones">
                        <button class="btn" onclick="location.reload()">Reintentar</button>
                    </div>
                </div>
            </body>
            </html>
            <?php
        }
        
        exit();
    }
    
    /**
     * Guarda un mensaje en el log de conexión (solo si DEBUG está activo).
     */
    private function log($mensaje) {
        // Solo guardar log si DEBUG_CONNECTION está definido como true
        if (!defined('DEBUG_CONNECTION') || DEBUG_CONNECTION !== true) {
            return;
        }
        
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        $linea = date('Y-m-d H:i:s') . " | " . $mensaje . PHP_EOL;
        @file_put_contents($log_dir . '/conexion.log', $linea, FILE_APPEND);
    }
    
    // ============================================
    // MÉTODOS PÚBLICOS
    // ============================================
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Devuelve el entorno en el que se conectó: 'local', 'hosting' o 'desconocido'.
     * Útil para depuración.
     */
    public function getEntorno() {
        return $this->entorno_actual;
    }
    
    /**
     * Verifica si la conexión está activa.
     */
    public function testConexion() {
        try {
            $this->conn->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}