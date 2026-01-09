<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recuperar contraseña</title>

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
        width: 380px;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        text-align: center;
        animation: fadeIn 0.6s ease;
    }

    .card h2 {
        margin-bottom: 10px;
        color: #1e3c72;
    }

    .card p {
        font-size: 14px;
        color: #555;
        margin-bottom: 25px;
    }

    .input-group {
        margin-bottom: 20px;
        text-align: left;
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
    <h2>Recuperar contraseña</h2>
    <p>Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>

    <form method="POST" action="procesar_forgot.php">
        <div class="input-group">
            <label for="correo">Correo institucional</label>
            <input 
                type="email" 
                id="correo" 
                name="correo" 
                placeholder="usuario@institucion.edu"
                required
            >
        </div>

        <button type="submit" class="btn">Enviar enlace</button>
        <?php if (isset($_GET['msg'])): ?>
    <p style="color: green; font-size: 13px;">
        <?= htmlspecialchars($_GET['msg']) ?>
    </p>
<?php endif; ?>
    </form>

    <div class="footer-text">
        © Sistema Académico
    </div>
</div>

</body>
</html>
