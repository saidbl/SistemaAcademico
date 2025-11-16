<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';

exigirSesionActiva();
exigirRol('Docente');

// Obtener id_docente real
$stmt = $pdo->prepare("SELECT id_docente FROM personal_docente WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$id_docente = $stmt->fetchColumn();

// Obtener niveles educativos para el menú
$niveles = $pdo->query("SELECT id_nivel, nombre FROM niveles_educativos")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Preferencias Docentes</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
  background:#f5f7fb; font-family:"Segoe UI", Arial, sans-serif; margin:0; color:#333;
}
.container {
  max-width:650px; margin:50px auto; background:white;
  padding:35px; border-radius:14px; box-shadow:0 4px 15px rgba(0,0,0,0.08);
}
h2 {
  color:#004b97; font-size:1.8em; margin-bottom:20px;
  display:flex;align-items:center;gap:10px;
}
label { font-weight:600; display:block; margin:15px 0 5px; }
select, input[type="number"], input[type="checkbox"] {
  width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; font-size:15px;
}
button {
  width:100%; margin-top:25px; padding:12px; background:#004b97; 
  border:none; border-radius:8px; color:white; font-size:16px; cursor:pointer;
}
button:hover { background:#003b78; }
.checkbox-group { display:flex; align-items:center; gap:10px; }
.success {
  background:#d1fae5; border:1px solid #10b981; padding:10px;
  text-align:center; margin-bottom:20px; border-radius:6px; color:#065f46;
}
</style>
</head>

<body>

<div class="container">
  <h2><i class="fa-solid fa-list-check"></i> Preferencias de Carga Docente</h2>

  <?php if (isset($_GET['ok'])): ?>
    <div class="success">Preferencias guardadas correctamente 📘</div>
  <?php endif; ?>

  <form method="POST" action="../../controllers/PreferenciasController.php">
  
    <!-- Continuidad de carga -->
    <label>¿Deseas repetir las mismas materias que impartiste este ciclo?</label>
    <div class="checkbox-group">
      <input type="checkbox" name="continuidad_carga" value="1">
      <span>Sí, deseo continuar con mis mismas materias</span>
    </div>

    <!-- Carga deseada -->
    <label>¿Cuántos grupos deseas impartir?</label>
    <input type="number" name="carga_deseada" min="1" max="20" required>

    <!-- Nivel preferido -->
    <label>Nivel educativo preferido</label>
    <select name="nivel_preferido" required>
      <option value="">Seleccione nivel</option>
      <?php foreach ($niveles as $n): ?>
        <option value="<?= $n['id_nivel'] ?>"><?= $n['nombre'] ?></option>
      <?php endforeach; ?>
    </select>

    <input type="hidden" name="id_docente" value="<?= $id_docente ?>">

    <button type="submit">Guardar preferencias</button>
  </form>
</div>

</body>
</html>
