<?php
/**
 * $idGrupo   -> El ID del grupo que quieres consultar (ej. 1, 61, etc.)
 * $idDocente -> El ID del docente (id_docente de la tabla personal_docente)
 * $pdo       -> Tu conexión PDO
 */

echo "<h2>Lista de Alumnos por Grupo y Docente</h2>";

// Consulta corregida según tu SQL:
// 1. Buscamos en 'usuarios' para obtener el nombre y boleta.
// 2. Unimos con 'alumnos' para vincular al usuario.
// 3. Unimos con 'reinscripciones' para saber en qué grupo está el alumno.
// 4. Unimos con 'horarios' para verificar que el docente imparta clases en ese grupo.

$query = "
    SELECT DISTINCT
        u.boleta,
        u.nombre,
        u.apellido_paterno,
        u.apellido_materno
    FROM usuarios u
    JOIN alumnos a ON u.id_usuario = a.id_usuario
    JOIN reinscripciones r ON a.id_alumno = r.id_alumno
    JOIN horarios h ON r.id_grupo = h.id_grupo
    WHERE r.id_grupo = :idGrupo 
      AND h.id_docente = :idDocente
    ORDER BY u.apellido_paterno ASC, u.apellido_materno ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute([
    'idGrupo'   => $idGrupo,
    'idDocente' => $idDocente
]);

$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$alumnos) {
    echo "<p>No se encontraron alumnos para este grupo con el docente especificado.</p>";
    return;
}

echo "<table border='1' width='100%' cellpadding='6' style='border-collapse: collapse;'>
<tr style='background-color: #eee;'>
    <th>Boleta</th>
    <th>Nombre del Alumno</th>
</tr>";

foreach ($alumnos as $a) {
    $nombreCompleto = htmlspecialchars($a['apellido_paterno'] . " " . $a['apellido_materno'] . " " . $a['nombre']);
    echo "<tr>
        <td>" . htmlspecialchars($a['boleta']) . "</td>
        <td>{$nombreCompleto}</td>
    </tr>";
}

echo "</table>";
?>