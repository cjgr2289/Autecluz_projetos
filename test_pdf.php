<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test PDF</title>
    <style>
        body { font-family: monospace; background: #f5f6fa; padding: 20px; }
        .box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; }
        .ok { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 6px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 0.9rem; }
        th { background: #2c3e50; color: white; }
    </style>
</head>
<body>

<h1>🔍 Diagnóstico de Archivos PDF</h1>

<div class="box">
    <h2>1. Variables del sistema</h2>
    <?php
    echo "<pre>";
    echo "__FILE__ (test_pdf.php) = " . __FILE__ . "\n";
    echo "dirname(__DIR__) desde raíz = " . dirname(__DIR__) . "\n";
    echo "getUploadsDir() = " . getUploadsDir() . "\n";
    echo "getPropuestasDir() = " . getPropuestasDir() . "\n";
    echo "</pre>";
    ?>
</div>

<div class="box">
    <h2>2. Existencia de carpetas</h2>
    <table>
        <tr><th>Ruta</th><th>¿Existe?</th><th>¿Escribible?</th></tr>
        <tr>
            <td><?php echo getUploadsDir(); ?></td>
            <td><?php echo is_dir(getUploadsDir()) ? '<span class="ok">✓ SÍ</span>' : '<span class="error">✗ NO</span>'; ?></td>
            <td><?php echo is_writable(getUploadsDir()) ? '<span class="ok">✓ SÍ</span>' : '<span class="error">✗ NO</span>'; ?></td>
        </tr>
        <tr>
            <td><?php echo getPropuestasDir(); ?></td>
            <td><?php echo is_dir(getPropuestasDir()) ? '<span class="ok">✓ SÍ</span>' : '<span class="error">✗ NO</span>'; ?></td>
            <td><?php echo is_writable(getPropuestasDir()) ? '<span class="ok">✓ SÍ</span>' : '<span class="error">✗ NO</span>'; ?></td>
        </tr>
    </table>
</div>

<div class="box">
    <h2>3. Archivos PDF en la base de datos</h2>
    <?php
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT id, nombre, propuesta_tecnica, propuesta_nombre_original, propuesta_fecha_subida
        FROM proyectos 
        WHERE propuesta_tecnica IS NOT NULL
    ");
    $proyectos = $stmt->fetchAll();
    
    if (empty($proyectos)) {
        echo '<p class="warning">⚠ No hay proyectos con propuesta técnica en la BD</p>';
    } else {
        echo '<table>';
        echo '<tr><th>ID</th><th>Proyecto</th><th>Ruta BD</th><th>Ruta Física</th><th>¿Existe?</th><th>Tamaño</th></tr>';
        
        foreach ($proyectos as $p) {
            $ruta_fisica = getUploadsDir() . '/' . ltrim($p['propuesta_tecnica'], '/');
            $existe = file_exists($ruta_fisica);
            $tamano = $existe ? round(filesize($ruta_fisica) / 1024, 2) . ' KB' : '—';
            
            echo '<tr>';
            echo '<td>' . $p['id'] . '</td>';
            echo '<td>' . htmlspecialchars($p['nombre']) . '</td>';
            echo '<td>' . htmlspecialchars($p['propuesta_tecnica']) . '</td>';
            echo '<td style="font-size:0.75rem;">' . htmlspecialchars($ruta_fisica) . '</td>';
            echo '<td>' . ($existe ? '<span class="ok">✓ SÍ</span>' : '<span class="error">✗ NO</span>') . '</td>';
            echo '<td>' . $tamano . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    ?>
</div>

<div class="box">
    <h2>4. Archivos reales en uploads/propuestas</h2>
    <?php
    $dir = getPropuestasDir();
    if (!is_dir($dir)) {
        echo '<p class="error">✗ La carpeta no existe: ' . $dir . '</p>';
    } else {
        $archivos = scandir($dir);
        $pdfs = array_filter($archivos, function($f) {
            return $f !== '.' && $f !== '..' && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf';
        });
        
        if (empty($pdfs)) {
            echo '<p class="warning">⚠ No hay archivos PDF en la carpeta</p>';
        } else {
            echo '<pre>';
            foreach ($pdfs as $pdf) {
                $ruta = $dir . '/' . $pdf;
                echo "✓ " . $pdf . " (" . round(filesize($ruta) / 1024, 2) . " KB)\n";
            }
            echo '</pre>';
        }
    }
    ?>
</div>

<div class="box">
    <h2>5. Comparación de rutas</h2>
    <?php
    echo "<pre>";
    echo "Raíz del proyecto (dirname(__DIR__) de config/) = " . dirname(__DIR__) . "\n";
    echo "getUploadsDir() = " . getUploadsDir() . "\n\n";
    
    echo "Ruta que usa ver_propuesta.php:\n";
    echo "  getUploadsDir() . '/' . ltrim(\$propuesta_tecnica, '/')\n\n";
    
    if (!empty($proyectos)) {
        $p = $proyectos[0];
        echo "Ejemplo con el primer proyecto (ID {$p['id']}):\n";
        echo "  propuesta_tecnica de BD = '{$p['propuesta_tecnica']}'\n";
        echo "  Ruta física completa = " . getUploadsDir() . '/' . ltrim($p['propuesta_tecnica'], '/') . "\n";
        echo "  ¿Existe? " . (file_exists(getUploadsDir() . '/' . ltrim($p['propuesta_tecnica'], '/')) ? 'SÍ' : 'NO') . "\n";
    }
    echo "</pre>";
    ?>
</div>

</body>
</html>