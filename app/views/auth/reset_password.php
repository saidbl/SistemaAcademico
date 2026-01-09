<?php
require_once '../../config/database.php';

$token = $_GET['token'] ?? '';

$sql = "SELECT id_usuario FROM usuarios 
        WHERE reset_token = ? 
        AND reset_token_expira > NOW()";
$stmt = $pdo->prepare($sql);
$stmt->execute([$token]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("Token inválido o expirado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Restablecer contraseña</title>

<style>
    body {
        margin: 0;
        height: 100vh;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: 'Segoe UI', Tahoma, sans-serif;
    }

    .card {
        background: #fff;
        width: 400px;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 15px 30px rgba(0,0,0,0.25);
        animation: fadeIn 0.6s ease;
    }

    .card h2 {
        text-align: center;
        color: #1e3c72;
        margin-bottom: 10px;
    }

    .card p {
        text-align: center;
        font-size: 14px;
        color: #555;
        margin-bottom: 25px;
    }

    .input-group {
        margin-bottom: 20px;
    }

    .input-group label {
        display: block;
        font-size: 13px;
        margin-bottom: 5px;
        color: #333;
    }

    .input-group input {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 14px;
        outline: none;
        transition: border 0.3s;
    }

    .input-group input:focus {
        border-color: #2a5298;
    }

    .btn {
        width: 100%;
        padding: 12px;
        background: #2a5298;
        border: none;
        color: #fff;
        font-size: 15px;
        font-weight: bold;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s, transform 0.2s;
    }

    .btn:hover {
        background: #1e3c72;
        transform: translateY(-2px);
    }

    .footer-text {
        margin-top: 20px;
        text-align: center;
        font-size: 12px;
        color: #777;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>

<body>

<div class="card">
    <h2>Restablecer contraseña</h2>
    <p>Ingresa tu nueva contraseña y confírmala para continuar.</p>

    <form method="POST" action="guardar_nueva_password.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="input-group">
            <label for="password">Nueva contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="input-group">
            <label for="password2">Confirmar contraseña</label>
            <input type="password" id="password2" name="password2" required>
        </div>

        <button type="submit" class="btn">Guardar contraseña</button>
    </form>

    <div class="footer-text">
        © Sistema Académico
    </div>
</div>

</body>
</html>
