<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

exigirSesionActiva();

$rol = $_SESSION['tipo_usuario'];
$idUsuario = $_SESSION['usuario_id'];

/* -------------------------------
  FUNCIÓN CLAVE
-------------------------------- */
function obtenerIdAlumno(PDO $pdo, int $idUsuario) {
    $stmt = $pdo->prepare("SELECT id_alumno FROM alumnos WHERE id_usuario = ?");
    $stmt->execute([$idUsuario]);
    return $stmt->fetchColumn();
}

/* -------------------------------
  REPORTES DISPONIBLES POR ROL
-------------------------------- */
$reportesPorRol = [
  'Alumno' => ['Kardex Académico','Boleta de Calificaciones','Constancia de Estudios'],
  'Docente' => ['Lista de Alumnos por Grupo','Reporte de Asistencia'],
  'Padre' => ['Kardex Académico','Boleta de Calificaciones'],
  'Administrador' => [
      'Kardex Académico','Boleta de Calificaciones','Constancia de Estudios',
      'Lista de Alumnos por Grupo','Reporte de Asistencia'
  ]
];

/* -------------------------------
  REPORTES EXISTENTES
-------------------------------- */
$reportes = $pdo->query("SELECT * FROM reportes")->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------
  CONTEXTOS SEGÚN ROL
-------------------------------- */
$alumnos = $grupos = [];

if ($rol === 'Administrador') {
    $alumnos = $pdo->query("
        SELECT u.id_usuario, u.nombre 
        FROM usuarios u 
        WHERE u.tipo_usuario='Alumno'
    ")->fetchAll();

    $grupos = $pdo->query("SELECT id_grupo, nombre FROM grupos")->fetchAll();
}

if ($rol === 'Docente') {
    $stmt = $pdo->prepare("
        SELECT g.id_grupo, g.nombre
        FROM docentes_grupos dg
        JOIN grupos g ON g.id_grupo = dg.id_grupo
        WHERE dg.id_docente = ?
    ");
    $stmt->execute([$idUsuario]);
    $grupos = $stmt->fetchAll();
}

if ($rol === 'Padre') {
    $stmt = $pdo->prepare("
        SELECT u.id_usuario, u.nombre
        FROM tutores t
        JOIN usuarios u ON u.id_usuario = t.id_alumno
        WHERE t.id_tutor = ?
    ");
    $stmt->execute([$idUsuario]);
    $alumnos = $stmt->fetchAll();
}

/* -------------------------------
  GENERAR REPORTE
-------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reporte = $_POST['reporte'];

    ob_start();

    switch ($reporte) {

        case 'Kardex Académico':
        case 'Boleta de Calificaciones':
        case 'Constancia de Estudios':

            if ($rol === 'Alumno') {
                $idAlumno = obtenerIdAlumno($pdo, $idUsuario);
            } else {
                $idAlumno = obtenerIdAlumno($pdo, (int)$_POST['id_alumno']);
            }

            if (!$idAlumno) {
                die('Alumno no válido');
            }

            include __DIR__ . '/pdf/' . match($reporte) {
                'Kardex Académico' => 'kardex.php',
                'Boleta de Calificaciones' => 'boleta.php',
                'Constancia de Estudios' => 'contancia.php',
            };
            break;

        case 'Lista de Alumnos por Grupo':
        case 'Reporte de Asistencia':

            $idGrupo = (int)$_POST['id_grupo'];
            if (!$idGrupo) die('Grupo inválido');

            include __DIR__ . '/pdf/' . match($reporte) {
                'Lista de Alumnos por Grupo' => 'lista_grupo.php',
                'Reporte de Asistencia' => 'asistencia.php',
            };
            break;
    }

    $html = ob_get_clean();

    $pdf = new Dompdf();
    $pdf->loadHtml($html);
    $pdf->render();
    $pdf->stream("reporte.pdf", ["Attachment" => false]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reportes</title>
<style>
body{font-family:'Segoe UI';background:#f5f7fb}
.box{background:white;width:500px;margin:40px auto;padding:25px;border-radius:12px}
select,button{width:100%;padding:10px;margin-top:10px}
</style>
</head>
<body>

<div class="box">
<h3>📊 Reportes (<?= $rol ?>)</h3>

<form method="POST">

<select name="reporte" required>
<?php foreach($reportes as $r): ?>
  <?php if(in_array($r['nombre'],$reportesPorRol[$rol])): ?>
    <option><?= $r['nombre'] ?></option>
  <?php endif; ?>
<?php endforeach; ?>
</select>

<?php if(!empty($alumnos)): ?>
<select name="id_alumno" required>
<?php foreach($alumnos as $a): ?>
  <option value="<?= $a['id_usuario'] ?>"><?= $a['nombre'] ?></option>
<?php endforeach; ?>
</select>
<?php endif; ?>

<?php if(!empty($grupos)): ?>
<select name="id_grupo" required>
<?php foreach($grupos as $g): ?>
  <option value="<?= $g['id_grupo'] ?>"><?= $g['nombre'] ?></option>
<?php endforeach; ?>
</select>
<?php endif; ?>

<button>Generar reporte</button>

</form>
</div>

</body>
</html>
