<?php
$stmt = $pdo->prepare("
SELECT u.nombre, u.apellido_paterno, u.apellido_materno
FROM alumnos a
JOIN usuarios u ON u.id_usuario = a.id_usuario
WHERE a.id_alumno = ?
");
$stmt->execute([$idAlumno]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

$nombre = $alumno['nombre'].' '.$alumno['apellido_paterno'].' '.$alumno['apellido_materno'];

echo "
<h2 style='text-align:center'>Constancia de Estudios</h2>
<p>Se hace constar que el alumno(a):</p>
<h3>$nombre</h3>
<p>se encuentra inscrito en el ciclo escolar vigente.</p>
<br><br>
<p>Atentamente<br>Sistema Académico</p>
";
