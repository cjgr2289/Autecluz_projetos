<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST DE HELPERS DEL CRONOGRAMA ===\n\n";

$db = Database::getInstance()->getConnection();

// Test 1: Feriados
echo "1. FERIADOS\n";
$feriados = obtenerFeriadosAnio($db, 2026);
echo "   Feriados en 2026: " . count($feriados) . "\n";
echo "   Primeros 5: " . implode(', ', array_slice($feriados, 0, 5)) . "\n\n";

// Test 2: Día hábil
echo "2. DÍAS HÁBILES\n";
$fechas_test = ['2026-09-24', '2026-09-25', '2026-09-26', '2026-09-27', '2026-09-15'];
foreach ($fechas_test as $f) {
    $es = esDiaHabil($db, $f) ? 'SÍ' : 'NO';
    echo "   $f: $es\n";
}
echo "\n";

// Test 3: Cálculo de fecha fin (días hábiles)
echo "3. CÁLCULO DE FECHA FIN\n";
$inicio = '2026-09-24';
$duracion = 10;
$fin = calcularFechaFinHabil($db, $inicio, $duracion);
echo "   Inicio: $inicio, Duración: $duracion días hábiles\n";
echo "   Fin: $fin\n\n";

// Test 4: Responsables
echo "4. RESPONSABLES\n";
$responsables = obtenerResponsables($db);
echo "   Total responsables: " . count($responsables) . "\n\n";

// Test 5: Actividades (si hay)
echo "5. ACTIVIDADES\n";
$stmt = $db->query("SELECT COUNT(*) as total FROM actividades_proyecto");
echo "   Total actividades en BD: " . $stmt->fetch()['total'] . "\n";

echo "\n=== FIN ===\n";