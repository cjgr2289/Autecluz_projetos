<?php
// generar_hash.php
$password = 'S1st3m4s*';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Contraseña: " . $password . "<br>";
echo "Hash generado:<br>";
echo "<textarea rows='3' cols='80'>" . $hash . "</textarea><br><br>";
echo "Verificación: " . (password_verify($password, $hash) ? '✓ OK' : '✗ FALLO');