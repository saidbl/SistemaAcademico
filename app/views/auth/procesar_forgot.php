<?php
require_once '../../config/database.php';

// 🔹 PHPMailer (manual)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../../libs/PHPMailer/src/Exception.php';
require '../../../libs/PHPMailer/src/PHPMailer.php';
require '../../../libs/PHPMailer/src/SMTP.php';

$correo = trim($_POST['correo']);

// 1️⃣ Buscar usuario
$sql = "SELECT id_usuario FROM usuarios WHERE correo_personal = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$correo]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Mensaje genérico (seguridad)
if (!$usuario) {
    header("Location: recuperar_contrasena.php?msg=Si el correo existe, recibirás instrucciones");
    exit();
}

// 2️⃣ Generar token
$token  = bin2hex(random_bytes(32));
$expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

// 3️⃣ Guardar token
$sql = "UPDATE usuarios 
        SET reset_token = ?, reset_token_expira = ?
        WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$token, $expira, $usuario['id_usuario']]);

// 4️⃣ Link de recuperación
$link = "http://localhost/SistemaAcademico/app/views/auth/reset_password.php?token=$token";

// 5️⃣ Enviar correo con PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'vbcs121104@gmail.com';       // 👈 cambia esto
    $mail->Password   = 'yali rjwu gmwr lgub';       // 👈 clave de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('TU_CORREO@gmail.com', 'Sistema Académico');
    $mail->addAddress($correo);

    $mail->isHTML(true);
    $mail->Subject = 'Recuperación de contraseña';
    $mail->Body = "
        <p>Hola,</p>
        <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
        <p><a href='$link'>$link</a></p>
        <p><strong>Este enlace expira en 1 hora.</strong></p>
        <hr>
        <p>Sistema Académico</p>
    ";

    $mail->send();

} catch (Exception $e) {
    die("Error al enviar correo: " . $mail->ErrorInfo);
}

// 6️⃣ Redirección final
header("Location: recuperar_contrasena.php?msg=Revisa tu correo");
exit();
