<?php

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

$usuarioModel = new Usuario($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identificador = trim($_POST['identificador']);
    $contrasena = trim($_POST['contrasena']);
    $captcha = trim($_POST['captcha']);

    if ($captcha != $_SESSION['captcha']) {
        header("Location: ../../index.php?error=Captcha incorrecto");
        exit();
    }

    if (empty($identificador) || empty($contrasena)) {
        header("Location: ../../index.php?error=Debes llenar todos los campos");
        exit();
    }

    $usuario = $usuarioModel->verificarCredenciales($identificador, $contrasena);

    if ($usuario) {

        if ($usuario['intentos_login'] >= 3) {
            header("Location: ../../index.php?error=Cuenta bloqueada por intentos fallidos");
            exit();
        }

        $token = bin2hex(random_bytes(32));
        $usuarioModel->actualizarSesion($usuario['id_usuario'], $token);
        $usuarioModel->reiniciarIntentos($usuario['id_usuario']);

        $_SESSION['usuario_id'] = $usuario['id_usuario'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'] . " " . $usuario['apellido_paterno'];
        $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
        $_SESSION['token'] = $token;
        $_SESSION['ultima_actividad'] = time();

        switch ($usuario['tipo_usuario']) {
            case 'Administrador':
                header("Location: ../views/dashboard/administrador_dashboard.php");
                break;
            case 'Docente':
                header("Location: ../views/dashboard/docente_dashboard.php");
                break;
            case 'Alumno':
                header("Location: ../views/dashboard/alumno_dashboard.php");
                break;
            case 'Padre':
                header("Location: ../views/dashboard/padre_dashboard.php");
                break;
            default:
                header("Location: ../../index.php?error=Tipo de usuario no reconocido");
        }
        exit();

    } else {
        $sql = "SELECT id_usuario FROM usuarios WHERE (boleta = :identificador OR numero_empleado = :identificador)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['identificador' => $identificador]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($u) {
            $usuarioModel->incrementarIntentos($u['id_usuario']);
        }

        header("Location: ../../index.php?error=Credenciales incorrectas");
        exit();
    }

} else {
    header("Location: ../../index.php");
    exit();
}
?>
