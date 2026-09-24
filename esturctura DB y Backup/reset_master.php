<?php
// reset_master.php - Resetea la contraseña del usuario Master
require_once 'config/database.php';

$password = 'S1st3m4s*';
$hash = password_hash($password, PASSWORD_DEFAULT);

$db = Database::getInstance()->getConnection();

// Verificar si existe el usuario
$stmt = $db->prepare("SELECT id FROM usuarios WHERE username = 'Master'");
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE username = 'Master'");
    $stmt->execute([$hash]);
    echo "✓ Contraseña del usuario Master actualizada correctamente.<br>";
    echo "Usuario: Master<br>";
    echo "Contraseña: S1st3m4s*<br>";
} else {
    $stmt = $db->prepare("INSERT INTO usuarios (username, password, nombre_completo, tipo_usuario, idioma_preferido) 
                          VALUES ('Master', ?, 'Administrador Master', 'directivo', 'es')");
    $stmt->execute([$hash]);
    echo "✓ Usuario Master creado correctamente.<br>";
    echo "Usuario: Master<br>";
    echo "Contraseña: S1st3m4s*<br>";
}

// Verificar
$stmt = $db->prepare("SELECT password FROM usuarios WHERE username = 'Master'");
$stmt->execute();
$row = $stmt->fetch();
$verifica = password_verify($password, $row['password']);
echo "Verificación de login: " . ($verifica ? '✓ FUNCIONA' : '✗ FALLA');