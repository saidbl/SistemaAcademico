<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

$sql = "UPDATE usuarios SET token_sesion = NULL WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['usuario_id']]);

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: ../../../index.php");
exit();

?>
