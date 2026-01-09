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

<form method="POST" action="guardar_nueva_password.php">
  <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
  <input type="password" name="password" placeholder="Nueva contraseña" required>
  <input type="password" name="password2" placeholder="Confirmar contraseña" required>
  <button type="submit">Guardar</button>
</form>
