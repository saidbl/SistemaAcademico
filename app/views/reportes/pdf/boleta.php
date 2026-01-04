<?php
$stmt = $pdo->prepare("
SELECT 
  m.nombre AS materia,
  c.calificacion
FROM calificaciones c
JOIN materias m ON m.id_materia = c.id_materia
WHERE c.id_alumno = ?
");
$stmt->execute([$idAlumno]);

echo "<h2>Boleta de Calificaciones</h2>";
echo "<table border='1' width='100%' cellpadding='6'>
<tr><th>Materia</th><th>Calificación</th></tr>";

foreach ($stmt as $r) {
    echo "<tr>
      <td>{$r['materia']}</td>
      <td>{$r['calificacion']}</td>
    </tr>";
}

echo "</table>";
