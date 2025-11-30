<?php
session_start();
require_once __DIR__ . '/../helper/session_helper.php';
require_once __DIR__ . '/../helper/auth_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

exigirSesionActiva();
exigirRol('Administrador');

// ------------------------------------------------------
// Configuración de bloques y días (coincide con tu vista)
// ------------------------------------------------------
$BLOQUES = [
    0 => ['inicio' => '07:00:00', 'fin' => '08:30:00'],
    1 => ['inicio' => '08:30:00', 'fin' => '10:00:00'],
    2 => ['inicio' => '10:30:00', 'fin' => '12:00:00'], // Descanso (RF-020)
    3 => ['inicio' => '12:00:00', 'fin' => '13:30:00'],
    4 => ['inicio' => '13:30:00', 'fin' => '15:00:00'],
];

$DIAS = ['Lunes','Martes','Miercoles','Jueves','Viernes'];

// ------------------------------------------------------
// Utilidades
// ------------------------------------------------------

/**
 * Seleccionar docente respetando RN-014 y garantizando que siempre haya docente:
 *  - Primer intento: evita choques de horario.
 *  - Segundo intento: ignora choques si nadie está libre (para no dejar materia sin profe).
 */
function seleccionarDocenteParaSlot(
    int $idNivelGrupo,
    array &$docentes,
    PDO $pdo,
    string $dia,
    string $horaInicio,
    string $horaFin
) {
    // Función interna para evaluar candidatos, con o sin chequeo de choques
    $buscar = function(bool $ignorarChoques) use ($idNivelGrupo, &$docentes, $pdo, $dia, $horaInicio, $horaFin) {
        $candidatoId = null;
        $mejorScore = -1;

        foreach ($docentes as $id_docente => $d) {
            // Verificar choque en BD si no se ignoran choques
            if (!$ignorarChoques) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM horarios
                    WHERE id_docente = ? AND dia_semana = ? AND hora_inicio = ? AND hora_fin = ?
                ");
                $stmt->execute([$id_docente, $dia, $horaInicio, $horaFin]);
                $ocupado = $stmt->fetchColumn() > 0;
                if ($ocupado) {
                    continue;
                }
            }

            $respondido     = (int)$d['respondido'];       // 1 o 0
            $nivelPreferido = $d['nivel_preferido'];       // puede ser null
            $cargaDeseada   = (int)$d['carga_deseada'];    // puede ser 0
            $cargaActual    = (int)$d['carga_actual'];

            // Preferencia por nivel educativo (Preescolar/Primaria/Secundaria/Prepa/Uni)
            $matchNivel = ($nivelPreferido !== null && (int)$nivelPreferido === $idNivelGrupo) ? 1 : 0;

            // ¿está por debajo de su carga deseada?
            $debajoCarga = ($cargaDeseada > 0 && $cargaActual < $cargaDeseada) ? 1 : 0;

            // Construimos un "score" simple
            $score = ($respondido * 10) + ($matchNivel * 5) + ($debajoCarga * 2) - $cargaActual;

            if ($score > $mejorScore) {
                $mejorScore = $score;
                $candidatoId = $id_docente;
            }
        }

        return $candidatoId;
    };

    // 1) Intento normal (sin choque)
    $id = $buscar(false);

    // 2) Si nadie disponible sin choque, intentamos ignorar choques
    if ($id === null) {
        $id = $buscar(true);
    }

    if ($id !== null) {
        $docentes[$id]['carga_actual']++;
    }

    return $id;
}

// ------------------------------------------------------
// 1) Limpiar horarios existentes (RF-017: generación completa)
// ------------------------------------------------------
try {
    $pdo->beginTransaction();
    $pdo->exec("DELETE FROM horarios");
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo limpiar la tabla de horarios: '.$e->getMessage()
    ]);
    exit;
}

// ------------------------------------------------------
// 2) Cargar datos base: grupos, materias descanso, docentes+preferencias
// ------------------------------------------------------

// Grupos con info de nivel educativo y nivel académico
$sqlGrupos = "
    SELECT g.*, 
           na.tipo   AS tipo_academico,
           na.orden  AS orden_academico,
           ne.nombre AS nombre_nivel,
           ne.periodo
    FROM grupos g
    LEFT JOIN niveles_academicos na ON g.id_nivel_academico = na.id_nivel_academico
    LEFT JOIN niveles_educativos ne ON g.id_nivel = ne.id_nivel
    ORDER BY g.id_nivel, na.orden, g.nombre
";
$grupos = $pdo->query($sqlGrupos)->fetchAll(PDO::FETCH_ASSOC);

// Materias de descanso por nivel (id_nivel => id_materia)
$sqlDescanso = "SELECT id_nivel, id_materia FROM materias WHERE nombre = 'Descanso'";
$descansoRows = $pdo->query($sqlDescanso)->fetchAll(PDO::FETCH_ASSOC);
$MATERIA_DESCANSO = [];
foreach ($descansoRows as $row) {
    $MATERIA_DESCANSO[(int)$row['id_nivel']] = (int)$row['id_materia'];
}

// Docentes con su última preferencia registrada (RN-014)
$sqlDocentes = "
    SELECT d.id_docente,
           COALESCE(p.continuidad_carga,0) AS continuidad_carga,
           COALESCE(p.carga_deseada,0)     AS carga_deseada,
           p.nivel_preferido,
           COALESCE(p.respondido,0)        AS respondido
    FROM personal_docente d
    LEFT JOIN (
        SELECT p1.*
        FROM preferencias_docente p1
        JOIN (
            SELECT id_docente, MAX(fecha_registro) AS max_fecha
            FROM preferencias_docente
            GROUP BY id_docente
        ) ult ON p1.id_docente = ult.id_docente AND p1.fecha_registro = ult.max_fecha
    ) p ON p.id_docente = d.id_docente
";
$docRows = $pdo->query($sqlDocentes)->fetchAll(PDO::FETCH_ASSOC);
$DOCENTES = [];
foreach ($docRows as $r) {
    $DOCENTES[(int)$r['id_docente']] = [
        'continuidad_carga' => (int)$r['continuidad_carga'],
        'carga_deseada'      => (int)$r['carga_deseada'],
        'nivel_preferido'    => $r['nivel_preferido'] !== null ? (int)$r['nivel_preferido'] : null,
        'respondido'         => (int)$r['respondido'],
        'carga_actual'       => 0,
    ];
}

// Docente de respaldo por si algo sale raro (para nunca dejar materia sin profe)
$fallbackDocenteId = null;
$fallbackRow = $pdo->query("SELECT id_docente FROM personal_docente ORDER BY id_docente LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($fallbackRow) {
    $fallbackDocenteId = (int)$fallbackRow['id_docente'];
}

// ------------------------------------------------------
// 3) Recorrer grupos y generar sus horarios
// ------------------------------------------------------

$totalAsignaciones = 0;
$detalleAsignaciones = [];

$gruposSinTaller   = [];
$gruposSinOptativa = [];
$sinEspacioHorario = [];

foreach ($grupos as $g) {
    $idGrupo          = (int)$g['id_grupo'];
    $idNivel          = (int)$g['id_nivel'];           // 1=Preescolar, 2=Primaria, 3=Secundaria, 4=Prepa, 5=Uni
    $idNivelAcademico = (int)$g['id_nivel_academico'];
    $idCarrera        = $g['id_carrera'] !== null ? (int)$g['id_carrera'] : null;
    $nombreGrupo      = $g['nombre'];

    // ---------------------------
    // 3.1 Obtener materias del grupo
    // ---------------------------
    $materias = [];

    if (in_array($idNivel, [1,2,3])) {
        // Preescolar, Primaria, Secundaria -> materias_por_nivel_academico
        $stmtMat = $pdo->prepare("
            SELECT m.id_materia, m.nombre, m.tipo
            FROM materias_por_nivel_academico mn
            JOIN materias m ON m.id_materia = mn.id_materia
            WHERE mn.id_nivel_academico = ?
              AND m.nombre <> 'Descanso'
            ORDER BY m.tipo, m.nombre
        ");
        $stmtMat->execute([$idNivelAcademico]);
        $materias = $stmtMat->fetchAll(PDO::FETCH_ASSOC);

        // RF-018: Debe existir al menos un Taller
        $tieneTaller = false;
        foreach ($materias as $m) {
            if ($m['tipo'] === 'Taller') {
                $tieneTaller = true;
                break;
            }
        }
        if (!$tieneTaller) {
            $gruposSinTaller[] = $nombreGrupo;
        }

    } else {
        // Preparatoria y Universidad -> materias_por_carrera
        if ($idCarrera === null) {
            $sinEspacioHorario[] = $nombreGrupo . ' (sin carrera asociada)';
            continue;
        }

        // Intento 1: carrera + nivel académico
        $stmtMat = $pdo->prepare("
            SELECT m.id_materia, m.nombre, m.tipo
            FROM materias_por_carrera mc
            JOIN materias m ON m.id_materia = mc.id_materia
            WHERE mc.id_carrera = ?
              AND mc.id_nivel_academico = ?
              AND m.nombre <> 'Descanso'
            ORDER BY m.tipo, m.nombre
        ");
        $stmtMat->execute([$idCarrera, $idNivelAcademico]);
        $materias = $stmtMat->fetchAll(PDO::FETCH_ASSOC);

        // Si no hay nada, intento 2: solo carrera (técnica + optativa)
        if (empty($materias)) {
            $stmtMat = $pdo->prepare("
                SELECT m.id_materia, m.nombre, m.tipo
                FROM materias_por_carrera mc
                JOIN materias m ON m.id_materia = mc.id_materia
                WHERE mc.id_carrera = ?
                  AND m.nombre <> 'Descanso'
                ORDER BY m.tipo, m.nombre
            ");
            $stmtMat->execute([$idCarrera]);
            $materias = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
        }

        // RF-019: Debe existir al menos una Optativa
        $tieneOptativa = false;
        foreach ($materias as $m) {
            if (strcasecmp($m['tipo'], 'Optativa') === 0) {
                $tieneOptativa = true;
                break;
            }
        }
        if (!$tieneOptativa) {
            $gruposSinOptativa[] = $nombreGrupo;
        }
    }

    if (empty($materias)) {
        $sinEspacioHorario[] = $nombreGrupo . ' (sin materias configuradas)';
        continue;
    }

    // ---------------------------
    // 3.2 Insertar descansos (RF-020)
    // ---------------------------
    $idMateriaDescanso = $MATERIA_DESCANSO[$idNivel] ?? null;

    if ($idMateriaDescanso !== null) {
        foreach ($DIAS as $dia) {
            $inicio = $BLOQUES[2]['inicio'];
            $fin    = $BLOQUES[2]['fin'];

            $stmtIns = $pdo->prepare("
                INSERT INTO horarios (id_grupo, id_materia, id_docente, dia_semana, hora_inicio, hora_fin, aula)
                VALUES (?, ?, NULL, ?, ?, ?, NULL)
            ");
            $stmtIns->execute([$idGrupo, $idMateriaDescanso, $dia, $inicio, $fin]);

            $totalAsignaciones++;
            $detalleAsignaciones[] = [
                'grupo'   => $nombreGrupo,
                'dia'     => $dia,
                'inicio'  => $inicio,
                'fin'     => $fin,
                'materia' => 'Descanso',
                'docente' => null
            ];
        }
    }

    // ---------------------------
    // 3.3 Generar slots de clase (sin el bloque de descanso)
    //      Y evitando repetir materia en el mismo día
    // ---------------------------
    $numMaterias = count($materias);
    if ($numMaterias === 0) {
        $sinEspacioHorario[] = $nombreGrupo . ' (sin materias para clase)';
        continue;
    }

    // Bloques de clase (sin descanso)
    $bloquesClase = [];
    foreach ($BLOQUES as $idx => $b) {
        if ($idx == 2) continue; // 2 es descanso
        $bloquesClase[] = $idx;
    }
    $numBloquesClase = count($bloquesClase); // normalmente 4

    // Número de bloques de clase por día: máximo 4, pero nunca más que el número de materias
    $slotsPorDia = min($numBloquesClase, $numMaterias);

    $slots = []; // cada slot: [dia, idxBloque, inicio, fin]
    foreach ($DIAS as $dia) {
        for ($i = 0; $i < $slotsPorDia; $i++) {
            $idxBloque = $bloquesClase[$i];
            $slots[] = [
                'dia'    => $dia,
                'idx'    => $idxBloque,
                'inicio' => $BLOQUES[$idxBloque]['inicio'],
                'fin'    => $BLOQUES[$idxBloque]['fin'],
            ];
        }
    }

    $numSlots = count($slots);
    if ($numSlots === 0) {
        $sinEspacioHorario[] = $nombreGrupo . ' (sin slots de clase)';
        continue;
    }

    // ---------------------------
    // 3.4 Calcular cuántas veces aparece cada materia
    // ---------------------------
    $materiasInfo = []; // id_materia => ['row' => ..., 'pendientes' => n]

    $baseReps = intdiv($numSlots, $numMaterias);
    $resto    = $numSlots % $numMaterias;

    foreach ($materias as $idx => $m) {
        $veces = $baseReps + ($idx < $resto ? 1 : 0);
        $materiasInfo[(int)$m['id_materia']] = [
            'row'        => $m,
            'pendientes' => $veces,
        ];
    }

    // Para controlar materias usadas por día (para no repetir en el mismo día)
    $materiasDiaUsadas = [];
    foreach ($DIAS as $d) {
        $materiasDiaUsadas[$d] = [];
    }

    // ---------------------------
    // 3.5 Insertar horarios de clase con docente asignado
    //      evitando repetir materia en el mismo día
    // ---------------------------
    foreach ($slots as $slot) {
        $dia = $slot['dia'];

        // 1) candidatos que aún no se usen ese día
        $candidatos = [];
        foreach ($materiasInfo as $idMat => $info) {
            if ($info['pendientes'] > 0 && !in_array($idMat, $materiasDiaUsadas[$dia], true)) {
                $candidatos[] = $idMat;
            }
        }

        // 2) si ya usé todas las materias en ese día, permito repetir (caso extremo)
        if (empty($candidatos)) {
            foreach ($materiasInfo as $idMat => $info) {
                if ($info['pendientes'] > 0) {
                    $candidatos[] = $idMat;
                }
            }
        }

        if (empty($candidatos)) {
            // No queda nada pendiente, salimos
            break;
        }

        // Elegir materia al azar entre candidatos
        $idMateriaElegida = $candidatos[array_rand($candidatos)];
        $materiasInfo[$idMateriaElegida]['pendientes']--;
        $materiasDiaUsadas[$dia][] = $idMateriaElegida;

        $m         = $materiasInfo[$idMateriaElegida]['row'];
        $idMateria = (int)$m['id_materia'];

        // Seleccionar docente usando preferencias (puede devolver null en casos raros)
        $idDocente = null;
        if (!empty($DOCENTES)) {
            $idDocente = seleccionarDocenteParaSlot(
                $idNivel,
                $DOCENTES,
                $pdo,
                $slot['dia'],
                $slot['inicio'],
                $slot['fin']
            );
        }

        // Fallback definitivo: si aún es null, usar docente de respaldo
        if ($idDocente === null && $fallbackDocenteId !== null) {
            $idDocente = $fallbackDocenteId;
        }

        $stmtIns = $pdo->prepare("
            INSERT INTO horarios (id_grupo, id_materia, id_docente, dia_semana, hora_inicio, hora_fin, aula)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        // Aula simple por ahora: Aula-{id_grupo}
        $aula = 'Aula-' . $idGrupo;

        $stmtIns->execute([
            $idGrupo,
            $idMateria,
            $idDocente,
            $slot['dia'],
            $slot['inicio'],
            $slot['fin'],
            $aula
        ]);

        $totalAsignaciones++;
        $detalleAsignaciones[] = [
            'grupo'   => $nombreGrupo,
            'dia'     => $slot['dia'],
            'inicio'  => $slot['inicio'],
            'fin'     => $slot['fin'],
            'materia' => $m['nombre'],
            'docente' => $idDocente
        ];
    }
}

// Todo bien, confirmamos transacción
$pdo->commit();

// ------------------------------------------------------
// 4) Respuesta JSON al frontend
// ------------------------------------------------------

echo json_encode([
    'status'              => 'ok',
    'total_asignaciones'  => $totalAsignaciones,
    'asignaciones'        => $detalleAsignaciones,
    'grupos_sin_taller'   => $gruposSinTaller,
    'grupos_sin_optativa' => $gruposSinOptativa,
    'sin_espacio_horario' => $sinEspacioHorario
]);
exit;
