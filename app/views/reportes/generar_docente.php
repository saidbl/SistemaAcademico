<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

exigirSesionActiva();
exigirRol('Docente');

$idReporte = $_GET['id'] ?? null;
if (!$idReporte) die('Reporte no válido');

/* 🔹 Validar que el reporte esté asignado al Docente */
$check = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reporte_rol 
    WHERE id_reporte = ? AND rol = 'Docente'
");
$check->execute([$idReporte]);
if ($check->fetchColumn() == 0) {
    die('No tienes permiso para este reporte');
}

/* 🔹 Obtener nombre del reporte */
$stmt = $pdo->prepare("SELECT nombre FROM reportes WHERE id_reporte = ?");
$stmt->execute([$idReporte]);
$nombreReporte = $stmt->fetchColumn();

/* 🔹 Obtener id_docente */
$stmt = $pdo->prepare("
    SELECT id_docente 
    FROM personal_docente 
    WHERE id_usuario = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$idDocente = $stmt->fetchColumn();

/* 🔹 Grupos del docente */
$stmt = $pdo->prepare("
    SELECT DISTINCT g.id_grupo, g.nombre
    FROM horarios h
    JOIN grupos g ON g.id_grupo = h.id_grupo
    WHERE h.id_docente = ?
");
$stmt->execute([$idDocente]);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ======================================================
   PASO 1: MOSTRAR SELECTOR DE GRUPO (UI BONITA)
====================================================== */
if (!isset($_GET['grupo'])):
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Seleccionar Grupo</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{font-family:'Segoe UI';background:#f5f7fb;margin:0}
.box{
  background:white;
  width:420px;
  margin:80px auto;
  padding:30px;
  border-radius:14px;
  box-shadow:0 5px 15px rgba(0,0,0,.1);
}
h3{text-align:center;color:#004b97}
select,button{
  width:100%;
  padding:10px;
  margin-top:15px;
  border-radius:6px;
}
button{
  background:#004b97;
  color:white;
  border:none;
  cursor:pointer;
}
button:hover{background:#006dd9}
.back{text-align:center;margin-top:15px}
</style>
</head>
<body>

<div class="box">
  <h3><i class="fa-solid fa-users"></i> Selecciona un grupo</h3>

  <form method="GET">
    <input type="hidden" name="id" value="<?= $idReporte ?>">

    <select name="grupo" required>
      <option value="">-- Selecciona --</option>
      <?php foreach($grupos as $g): ?>
        <option value="<?= $g['id_grupo'] ?>">
          <?= htmlspecialchars($g['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button>
      <i class="fa-solid fa-file-pdf"></i> Generar reporte
    </button>
  </form>

  <div class="back">
    <a href="/SistemaAcademico/app/views/reportes/docente.php">⬅ Volver</a>
  </div>
</div>

</body>
</html>
<?php
exit;
endif;

/* ======================================================
   PASO 2: GENERAR PDF
====================================================== */

$idGrupo = (int) $_GET['grupo'];
if ($idGrupo <= 0) {
    die('Grupo inválido');
}

/* 🔹 HACEMOS $idGrupo DISPONIBLE PARA EL PDF */
ob_start();

switch ($nombreReporte) {

    case 'Lista de Alumnos por Grupo':
        // 👉 $idGrupo YA existe
        include __DIR__ . '/pdf/lista_grupo.php';
        break;

    case 'Reporte de Asistencia':
        include __DIR__ . '/pdf/asistencia.php';
        break;

    default:
        die('Reporte no disponible para docente');
}

$html = ob_get_clean();

/* 🔹 Generar PDF */
$pdf = new Dompdf();
$pdf->loadHtml($html);
$pdf->render();
$pdf->stream("reporte_docente.pdf", ["Attachment" => false]);
exit;
