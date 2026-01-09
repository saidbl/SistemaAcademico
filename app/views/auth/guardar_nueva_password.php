<?php
require_once '../../config/database.php';

$token = $_POST['token'];
$pass1 = $_POST['password'];
$pass2 = $_POST['password2'];

if ($pass1 !== $pass2) {
    die("Las contraseñas no coinciden");
}

$hash = password_hash($pass1, PASSWORD_DEFAULT);

$sql = "UPDATE usuarios 
        SET contrasena_hash = ?, 
            reset_token = NULL,
            reset_token_expira = NULL
        WHERE reset_token = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$hash, $token]);

header("Location: ../../../index.php?msg=Contraseña actualizada");
exit();
