<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$_SESSION['idioma'] = 'es';
$_SESSION['usuario_id'] = 1; // ID del usuario Master

echo "<pre>";
echo "=== TEST DE ENVÍO DE EMAIL ===\n\n";

$db = Database::getInstance()->getConnection();

// Buscar un item para probar
$stmt = $db->query("SELECT id, nombre_item, estado FROM items_proyecto LIMIT 1");
$item = $stmt->fetch();

if (!$item) {
    die("No hay items en la BD para probar\n");
}

echo "Item de prueba: #{$item['id']} - {$item['nombre_item']} (estado: {$item['estado']})\n\n";

// Verificar destinatarios
$destinatarios = obtenerDestinatariosProyecto($db, 1);
echo "Destinatarios del proyecto 1: " . count($destinatarios) . "\n";
foreach ($destinatarios as $d) {
    echo "  - {$d['nombre_completo']} <{$d['email']}>\n";
}

echo "\nIntentando enviar notificación...\n";

try {
    $resultado = notificarCambioEstadoItem(
        $db,
        $item['id'],
        'pendiente',
        'cotacion',
        1,
        'Prueba de envío de email'
    );
    
    echo "\nResultado: " . ($resultado ? '✓ ENVIADO' : '✗ FALLÓ') . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN ===\n";
echo "</pre>";