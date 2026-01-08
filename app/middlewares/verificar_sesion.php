<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['token'])) {
    header("Location: /SistemaAcademico/index.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$tokenSesion = $_SESSION['token'];
$sessionPhpId = session_id();

// Obtener datos reales de BD
$sql = "SELECT token_sesion, session_php_id 
        FROM usuarios 
        WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no existe el usuario → sesión inválida
if (!$usuario) {
    session_destroy();
    header("Location: /SistemaAcademico/index.php");
    exit();
}

/*
  VALIDACIÓN REAL:
  - Si el token o el session_id NO coinciden
  - entonces la sesión fue abierta en otro dispositivo
*/
if (
    $usuario['token_sesion'] !== $tokenSesion ||
    $usuario['session_php_id'] !== $sessionPhpId
) {
    session_destroy();
    header("Location: /SistemaAcademico/index.php?error=Tu sesión fue iniciada en otro dispositivo");
    exit();
}
