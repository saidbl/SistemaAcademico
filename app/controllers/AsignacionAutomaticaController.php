<?php
session_start();
require_once __DIR__ . '/../helper/session_helper.php';
require_once __DIR__ . '/../helper/auth_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

exigirSesionActiva();
exigirRol('Administrador');

// ------------------------------------------------------
// CONFIGURACIÓN DE BLOQUES POR TURNO
// ------------------------------------------------------

// Bloques MATUTINOS
$BLOQUES_MATUTINO = [
    0 => ['inicio' => '07:00:00', 'fin' => '08:30:00'],
    1 => ['inicio' => '08:30:00', 'fin' => '10:00:00'],
    2 => ['inicio' => '10:30:00', 'fin' => '12:00:00'], // Descanso
    3 => ['inicio' => '12:00:00', 'fin' => '13:30:00'],
    4 => ['inicio' => '13:30:00', 'fin' => '15:00:00'],
];

// Bloques VESPERTINOS
$BLOQUES_VESPERTINO = [
    0 => ['inicio' => '15:00:00', 'fin' => '16:30:00'],
    1 => ['inicio' => '16:30:00', 'fin' => '18:00:00'],
    2 => ['inicio' => '18:00:00', 'fin' => '19:00:00'], // Descanso
    3 => ['inicio' => '19:00:00', 'fin' => '20:30:00'],
    4 => ['inicio' => '20:30:00', 'fin' => '22:00:00'],
];

$DIAS = ['Lunes','Martes','Miercoles','Jueves','Viernes'];

// ------------------------------------------------------
// SELECCIÓN DE DOCENTE (RESPETA PREFERENCIAS, EVITA CHOQUES Y REPETICIÓN)
// ------------------------------------------------------
function seleccionarDocenteParaSlot(
    int $idNivelGrupo,
    array &$docentes,
    PDO $pdo,
    string $dia,
    string $horaInicio,
    string $horaFin,
    array $usoDocentesGrupo,
    array $usoDocenteDia
) {
    $buscar = function(bool $ignorarChoques) use (
        $idNivelGrupo, &$docentes, $pdo, $dia, $horaInicio, $horaFin,
        $usoDocentesGrupo, $usoDocenteDia
    ) {
        $candidatoId = null;
        $mejorScore = -999999;

        foreach ($docentes as $id_docente => $d) {

            // Verificar choque de horario si no estamos ignorando choques
            if (!$ignorarChoques) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM horarios
                    WHERE id_docente = ? AND dia_semana = ? 
                      AND hora_inicio = ? AND hora_fin = ?
                ");
                $stmt->execute([$id_docente, $dia, $horaInicio, $horaFin]);
                if ($stmt->fetchColumn() > 0) {
                    continue;
                }
            }

            // -----------------------------
            // CALCULAR SCORE
            // -----------------------------
            $score = 0;

            // Preferencias originales
            $score += ($d['respondido'] ? 10 : 0); // respondió formulario
            $score += ($d['nivel_preferido'] == $idNivelGrupo ? 5 : 0); // prefiere este nivel

            // ¿Está por debajo de carga deseada?
            if ($d['carga_deseada'] > $d['carga_actual']) {
                $score += 2;
            }

            // Penalizar carga alta
            $score -= $d['carga_actual'] * 1.2;

            // ❌ Penalizar si ya se ha usado mucho en este grupo
            $vecesGrupo = $usoDocentesGrupo[$id_docente] ?? 0;
            $score -= $vecesGrupo * 15;

            // ❌ Penalizar si ya se ha usado en este día
            $vecesDia = $usoDocenteDia[$dia][$id_docente] ?? 0;
            $score -= $vecesDia * 25;

            if ($score > $mejorScore) {
                $mejorScore = $score;
                $candidatoId = $id_docente;
            }
        }

        return $candidatoId;
    };

    // Intento normal (sin choques)
    $id = $buscar(false);

    // Si nadie disponible sin choque, intentamos ignorar choques
    if ($id === null) {
        $id = $buscar(true);
    }

    // Aumentamos la carga actual del docente elegido
    if ($id !== null) {
        $docentes[$id]['carga_actual']++;
    }

    return $id;
}

try {
    // ------------------------------------------------------
    // 1) LIMPIAR HORARIOS EXISTENTES
    // ------------------------------------------------------
    $pdo->beginTransaction();
    $pdo->exec("DELETE FROM horarios");

    // ------------------------------------------------------
    // 2) CARGAR GRUPOS CON TURNO EXPLÍCITO
    // ------------------------------------------------------
    $sqlGrupos = "
        SELECT 
            g.id_grupo,
            g.id_nivel,
            g.id_carrera,
            g.nombre,
            g.grado,
            g.cupo_maximo,
            g.turno,                -- turno viene directo de la BD
            g.id_nivel_academico,

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

    // ------------------------------------------------------
    // 3) CARGAR DOCENTES Y PREFERENCIAS
    // ------------------------------------------------------
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
            ) ult 
            ON p1.id_docente = ult.id_docente 
           AND p1.fecha_registro = ult.max_fecha
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

    // DOCENTE DE RESPALDO
    $fallbackDocenteId = $pdo->query("SELECT id_docente FROM personal_docente ORDER BY id_docente LIMIT 1")->fetchColumn();

    // ------------------------------------------------------
    // 4) MATERIA DESCANSO POR NIVEL
    // ------------------------------------------------------
    $descansoRows = $pdo->query("SELECT id_nivel, id_materia FROM materias WHERE nombre = 'Descanso'")
                        ->fetchAll(PDO::FETCH_ASSOC);

    $MATERIA_DESCANSO = [];
    foreach ($descansoRows as $d) {
        $MATERIA_DESCANSO[(int)$d['id_nivel']] = (int)$d['id_materia'];
    }

    // ------------------------------------------------------
    // VARIABLES DE CONTROL
    // ------------------------------------------------------
    $totalAsignaciones = 0;
    $detalleAsignaciones = [];

    $gruposSinTaller = [];
    $gruposSinOptativa = [];
    $sinEspacioHorario = [];

    // ------------------------------------------------------
    // 5) GENERACIÓN DE HORARIOS
    // ------------------------------------------------------
    foreach ($grupos as $g) {

        $idGrupo          = (int)$g['id_grupo'];
        $idNivel          = (int)$g['id_nivel'];
        $idCarrera        = $g['id_carrera'];
        $idNivelAcademico = (int)$g['id_nivel_academico'];
        $nombreGrupo      = $g['nombre'];

        // CONTADORES DE USO DE DOCENTES (para este grupo)
        $usoDocentesGrupo = [];  // id_docente => veces asignado en este grupo
        $usoDocenteDia = [
            "Lunes"      => [],
            "Martes"     => [],
            "Miercoles"  => [],
            "Jueves"     => [],
            "Viernes"    => []
        ];

        // NORMALIZAR TURNO: si no es "matutino", lo tratamos como vespertino
        $turnoRaw  = $g['turno'] ?? 'Matutino';
        $turnoNorm = strtolower(trim($turnoRaw));
        $esVespertino = ($turnoNorm === 'vespertino' || $turnoNorm === 'v' || $turnoNorm === 'vespertin');
        if ($turnoNorm !== 'matutino' && !$esVespertino) {
            $esVespertino = true;
        }

        $turnoTexto = $esVespertino ? 'Vespertino' : 'Matutino';
        $BLOQUES_USAR = $esVespertino ? $BLOQUES_VESPERTINO : $BLOQUES_MATUTINO;

        // Debug opcional
        error_log("GENERANDO {$nombreGrupo} | turno BD='{$turnoRaw}' | normalizado='{$turnoTexto}'");

        // --------------------------------------------------
        // 5.1 OBTENER MATERIAS DEL GRUPO
        // --------------------------------------------------
        if (in_array($idNivel, [1,2,3])) {

            $stmt = $pdo->prepare("
                SELECT m.id_materia, m.nombre, m.tipo
                FROM materias_por_nivel_academico mn
                JOIN materias m ON m.id_materia = mn.id_materia
                WHERE mn.id_nivel_academico = ?
                  AND m.nombre <> 'Descanso'
                ORDER BY m.tipo, m.nombre
            ");
            $stmt->execute([$idNivelAcademico]);
            $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!array_filter($materias, fn($m)=>$m['tipo']==='Taller')) {
                $gruposSinTaller[] = $nombreGrupo;
            }

        } else {
            if ($idCarrera === null) {
                $sinEspacioHorario[] = "$nombreGrupo (sin carrera)";
                continue;
            }

            $stmt = $pdo->prepare("
                SELECT m.id_materia, m.nombre, m.tipo
                FROM materias_por_carrera mc
                JOIN materias m ON m.id_materia = mc.id_materia
                WHERE mc.id_carrera = ?
                  AND mc.id_nivel_academico = ?
                  AND m.nombre <> 'Descanso'
            ");
            $stmt->execute([$idCarrera, $idNivelAcademico]);
            $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($materias)) {
                $stmt = $pdo->prepare("
                    SELECT m.id_materia, m.nombre, m.tipo
                    FROM materias_por_carrera mc
                    JOIN materias m ON m.id_materia = mc.id_materia
                    WHERE mc.id_carrera = ?
                      AND m.nombre <> 'Descanso'
                ");
                $stmt->execute([$idCarrera]);
                $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (!array_filter($materias, fn($m)=>strtolower($m['tipo'])==='optativa')) {
                $gruposSinOptativa[] = $nombreGrupo;
            }
        }

        if (empty($materias)) {
            $sinEspacioHorario[] = "$nombreGrupo (sin materias)";
            continue;
        }

        // --------------------------------------------------
        // 5.2 INSERTAR DESCANSOS
        // --------------------------------------------------
        $idMateriaDescanso = $MATERIA_DESCANSO[$idNivel] ?? null;

        if ($idMateriaDescanso !== null) {
            foreach ($DIAS as $dia) {

                $inicio = $BLOQUES_USAR[2]['inicio'];
                $fin    = $BLOQUES_USAR[2]['fin'];

                $stmt = $pdo->prepare("
                    INSERT INTO horarios 
                    (id_grupo, id_materia, id_docente, dia_semana, hora_inicio, hora_fin, aula)
                    VALUES (?, ?, NULL, ?, ?, ?, NULL)
                ");
                $stmt->execute([$idGrupo, $idMateriaDescanso, $dia, $inicio, $fin]);

                $totalAsignaciones++;
            }
        }

        // --------------------------------------------------
        // 5.3 CREAR SLOTS DE CLASE
        // --------------------------------------------------
        $bloquesClase = array_values(array_filter(array_keys($BLOQUES_USAR), fn($b)=>$b!=2));

        $numMaterias = count($materias);
        $slotsPorDia = min(count($bloquesClase), $numMaterias);

        $slots = [];
        foreach ($DIAS as $dia) {
            for ($i = 0; $i < $slotsPorDia; $i++) {
                $b = $bloquesClase[$i];
                $slots[] = [
                    'dia'    => $dia,
                    'inicio' => $BLOQUES_USAR[$b]['inicio'],
                    'fin'    => $BLOQUES_USAR[$b]['fin']
                ];
            }
        }

        $materiasInfo = [];
        $totalSlots = count($slots);
        $base = intdiv($totalSlots, $numMaterias);
        $resto = $totalSlots % $numMaterias;

        foreach ($materias as $i => $m) {
            $veces = $base + ($i < $resto ? 1 : 0);
            $materiasInfo[$m['id_materia']] = [
                'row'=>$m,
                'restantes'=>$veces
            ];
        }

        $materiasUsadasDia = array_fill_keys($DIAS, []);

        // --------------------------------------------------
        // 5.4 INSERTAR HORARIOS DE CLASE
        // --------------------------------------------------
        foreach ($slots as $slot) {

            $dia = $slot['dia'];

            $candidatos = array_filter(
                array_keys($materiasInfo),
                function($id) use ($materiasInfo, $materiasUsadasDia, $dia) {
                    return $materiasInfo[$id]['restantes'] > 0 &&
                           !in_array($id, $materiasUsadasDia[$dia]);
                }
            );

            if (empty($candidatos)) {
                $candidatos = array_filter(
                    array_keys($materiasInfo),
                    fn($id)=>$materiasInfo[$id]['restantes'] > 0
                );
            }

            if (empty($candidatos)) break;

            $idMat = $candidatos[array_rand($candidatos)];
            $materiasInfo[$idMat]['restantes']--;

            $materiasUsadasDia[$dia][] = $idMat;

            // Seleccionar docente evitando choques y repeticiones
            $idDocente = seleccionarDocenteParaSlot(
                $idNivel,
                $DOCENTES,
                $pdo,
                $dia,
                $slot['inicio'],
                $slot['fin'],
                $usoDocentesGrupo,
                $usoDocenteDia
            );

            if ($idDocente === null) {
                $idDocente = $fallbackDocenteId;
            }

            $stmt = $pdo->prepare("
                INSERT INTO horarios 
                (id_grupo, id_materia, id_docente, dia_semana, hora_inicio, hora_fin, aula)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $idGrupo,
                $idMat,
                $idDocente,
                $dia,
                $slot['inicio'],
                $slot['fin'],
                'Aula-' . $idGrupo
            ]);

            // Registrar uso del docente en este grupo y día
            if ($idDocente !== null) {
                $usoDocentesGrupo[$idDocente] = ($usoDocentesGrupo[$idDocente] ?? 0) + 1;
                $usoDocenteDia[$dia][$idDocente] = ($usoDocenteDia[$dia][$idDocente] ?? 0) + 1;
            }

            $totalAsignaciones++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'status'=>'ok',
        'total_asignaciones'=>$totalAsignaciones,
        'grupos_sin_taller'=>$gruposSinTaller,
        'grupos_sin_optativa'=>$gruposSinOptativa,
        'sin_espacio_horario'=>$sinEspacioHorario
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ERROR GENERAR_HORARIOS: ".$e->getMessage());
    echo json_encode([
        'status'=>'error',
        'message'=>'Error generando horarios: '.$e->getMessage()
    ]);
    exit;
}
