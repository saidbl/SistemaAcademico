<?php
// ============================================
//  SISTEMA EDUCATIVO - LOGIN PRINCIPAL
// ============================================

// Iniciar sesión
session_start();
require_once __DIR__ . '/app/config/database.php';

// Si ya hay sesión activa, redirigir
if (isset($_SESSION['usuario_id'])) {
    header("Location: app/views/dashboard/" . strtolower($_SESSION['tipo_usuario']) . "_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Educativo | Inicio de Sesión</title>
    <link rel="stylesheet" href="public/css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #004e92, #000428);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-container {
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            width: 380px;
            padding: 2.5rem;
            text-align: center;
            animation: fadeIn 1s ease;
        }

        h2 {
            color: #004e92;
            margin-bottom: 1.5rem;
        }

        .input-group {
            text-align: left;
            margin-bottom: 1rem;
        }

        .input-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            outline: none;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .input-group input:focus {
            border-color: #004e92;
        }

        .btn-login {
            background: linear-gradient(135deg, #004e92, #000428);
            color: #fff;
            border: none;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #003366, #000);
        }

        .error {
            background-color: #ffe6e6;
            color: #b30000;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: <?php echo isset($_GET['error']) ? 'block' : 'none'; ?>;
        }

        .links {
            margin-top: 1rem;
        }

        .links a {
            color: #004e92;
            text-decoration: none;
            font-size: 14px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(-10px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .footer {
            margin-top: 1.5rem;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Acceso al Sistema</h2>

        <form action="app/controllers/AuthController.php" method="POST">
            <div class="error">
                <?php
                    if (isset($_GET['error'])) {
                        echo htmlspecialchars($_GET['error']);
                    }
                ?>
            </div>

            <div class="input-group">
                <label for="identificador">Número de boleta o empleado</label>
                <input type="text" name="identificador" id="identificador" required autofocus>
            </div>

            <div class="input-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" name="contrasena" id="contrasena" required minlength="8">
            </div>

            <!-- CAPTCHA simple -->
            <div class="input-group">
                <label for="captcha">Verificación (escribe el número mostrado)</label>
                <?php 
                    $numero_captcha = rand(1000,9999);
                    $_SESSION['captcha'] = $numero_captcha;
                ?>
                <div style="font-size:18px; font-weight:bold; background:#004e92; color:#fff; padding:8px; border-radius:5px; display:inline-block;">
                    <?php echo $numero_captcha; ?>
                </div>
                <input type="text" name="captcha" placeholder="Ingresa el número" required>
            </div>

            <button type="submit" class="btn-login">Iniciar sesión</button>

            <div class="links">
                <a href="app/views/auth/recuperar_contrasena.php">¿Olvidaste tu contraseña?</a>
            </div>

            <div class="footer">
                <p>© <?php echo date("Y"); ?> Centro Educativo - Todos los derechos reservados</p>
            </div>
        </form>
    </div>

</body>
</html>
