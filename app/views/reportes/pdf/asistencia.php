<?php
/**
 * REPORTE DE ASISTENCIA - VERSIÓN FINAL COMPATIBLE
 */

// 1. Obtener la materia automáticamente si no existe
if (!isset($idMateria)) {
    $stmtMat = $pdo->prepare("SELECT id_materia FROM horarios WHERE id_grupo = ? AND id_docente = ? LIMIT 1");
    $stmtMat->execute([$idGrupo, $idDocente]);
    $idMateria = $stmtMat->fetchColumn();
}

try {
    // 2. Consulta mejorada: 
    // Quitamos 'r.estatus = Aprobada' para que liste a todos los inscritos en el grupo.
    $query = "
        SELECT 
            u.boleta,
            u.nombre,
            u.apellido_paterno,
            u.apellido_materno,
            COUNT(CASE WHEN asist.estado = 'Presente' THEN 1 END) AS total_asistencias,
            COUNT(CASE WHEN asist.estado = 'Falta' THEN 1 END) AS total_faltas,
            COUNT(asist.id_asistencia) AS total_sesiones
        FROM usuarios u
        INNER JOIN alumnos a ON u.id_usuario = a.id_usuario
        INNER JOIN reinscripciones r ON a.id_alumno = r.id_alumno
        LEFT JOIN asistencias asist ON a.id_alumno = asist.id_alumno 
             AND asist.id_materia = :idMateria
        WHERE r.id_grupo = :idGrupo
        GROUP BY a.id_alumno, u.boleta, u.nombre, u.apellido_paterno, u.apellido_materno
        ORDER BY u.apellido_paterno ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'idGrupo'   => $idGrupo,
        'idMateria' => $idMateria
    ]);

    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Lista de Asistencia del Grupo</h2>";

    if (!$alumnos) {
        // Si sigue saliendo esto, es que no hay nadie en la tabla 'reinscripciones' con ese id_grupo
        echo "<p style='color:red;'>Atención: No se encontraron alumnos vinculados al Grupo ID: $idGrupo en la tabla reinscripciones.</p>";
    } else {
        echo "<table border='1' width='100%' cellpadding='8' style='border-collapse: collapse;'>
        <tr style='background-color: #eee;'>
            <th>Boleta</th>
            <th>Alumno</th>
            <th>Asistencias</th>
            <th>Faltas</th>
            <th>%</th>
        </tr>";

        foreach ($alumnos as $al) {
            $nombreC = htmlspecialchars($al['apellido_paterno'] . " " . $al['apellido_materno'] . " " . $al['nombre']);
            $porcentaje = ($al['total_sesiones'] > 0) 
                ? round(($al['total_asistencias'] / $al['total_sesiones']) * 100, 1) . "%" 
                : "0%";

            echo "<tr>
                <td>{$al['boleta']}</td>
                <td>{$nombreC}</td>
                <td align='center'>{$al['total_asistencias']}</td>
                <td align='center'>{$al['total_faltas']}</td>
                <td align='center'>$porcentaje</td>
            </tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}