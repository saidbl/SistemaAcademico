<?php
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../../index.php?error=Debes iniciar sesión");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel | <?php echo $_SESSION['tipo_usuario']; ?></title>
    <link rel="stylesheet" href="../../../public/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
