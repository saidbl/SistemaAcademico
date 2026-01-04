<?php
// $idAlumno viene desde ver.php

$stmt = $pdo->prepare("
SELECT 
  m.nombre AS materia,
  c.periodo,
  c.calificacion
FROM calificaciones c
JOIN materias m ON m.id_materia = c.id_materia
WHERE c.id_alumno = ?
ORDER BY c.periodo
");
$stmt->execute([$idAlumno]);

echo "<h2>Kardex Académico</h2>";
echo "<table border='1' width='100%' cellpadding='6'>
<tr>
  <th>Materia</th>
  <th>Periodo</th>
  <th>Calificación</th>
</tr>";

foreach ($stmt as $r) {
    echo "<tr>
      <td>{$r['materia']}</td>
      <td>{$r['periodo']}</td>
      <td>{$r['calificacion']}</td>
    </tr>";
}

echo "</table>";
