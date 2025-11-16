<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helper/session_helper.php';
require_once __DIR__ . '/../helper/auth_helper.php';

exigirSesionActiva();
exigirRol('Administrador');

header('Content-Type: application/json; charset=utf-8');

// =====================================
// 0. LIMPIAR HORARIOS ANTERIORES
// =====================================
$pdo->exec("DELETE FROM horarios");

// =====================================
// 1. OBTENER PREFERENCIAS DOCENTES
// =====================================

$preferencias = $pdo->query("
    SELECT p.*, d.id_docente, u.nombre, u.apellido_paterno
    FROM preferencias_docente p
    JOIN personal_docente d ON p.id_docente = d.id_docente
    JOIN usuarios u ON d.id_usuario = u.id_usuario
")->fetchAll(PDO::FETCH_ASSOC);

$docentes = [];
foreach ($preferencias as $pref) {
    $prioridad = 0;
    if ((int)$pref['respondido'] === 1)        $prioridad += 2;
    if ((int)$pref['continuidad_carga'] === 1) $prioridad += 2;
    if (!is_null($pref['nivel_preferido']))    $prioridad += 1;
    if ((int)$pref['carga_deseada'] > 0)       $prioridad += 1;

    $docentes[] = [
        'id_docente'    => (int)$pref['id_docente'],
        'prioridad'     => $prioridad,
        'carga_deseada' => (int)$pref['carga_deseada'],
        'nivel_pref'    => $pref['nivel_preferido'] !== null ? (int)$pref['nivel_preferido'] : null
    ];
}

// Ordenar docentes por prioridad (mayor a menor)
usort($docentes, function($a, $b){
    return $b['prioridad'] <=> $a['prioridad'];
});

// =====================================
// 2. OBTENER MATERIAS POR GRUPO
// =====================================

$materias_por_grupo = $pdo->query("
    SELECT 
        g.id_grupo,
        g.nombre            AS grupo,
        g.id_nivel_academico,
        na.id_nivel,
        m.id_materia,
        m.nombre            AS materia,
        m.tipo              AS tipo_materia
    FROM grupos g
    JOIN niveles_academicos na 
        ON na.id_nivel_academico = g.id_nivel_academico
    JOIN materias_por_nivel_academico mpn 
        ON mpn.id_nivel_academico = g.id_nivel_academico
    JOIN materias m 
        ON m.id_materia = mpn.id_materia
    ORDER BY g.id_grupo, m.id_materia
")->fetchAll(PDO::FETCH_ASSOC);

// Agrupar materias por grupo
$gruposMaterias = [];
foreach ($materias_por_grupo as $row) {
    $id_grupo = (int)$row['id_grupo'];
    if (!isset($gruposMaterias[$id_grupo])) {
        $gruposMaterias[$id_grupo] = [
            'id_grupo'          => $id_grupo,
            'id_nivel_academico'=> (int)$row['id_nivel_academico'],
            'id_nivel'          => (int)$row['id_nivel'],
            'materias'          => []
        ];
    }
    $gruposMaterias[$id_grupo]['materias'][] = [
        'id_materia' => (int)$row['id_materia'],
        'nombre'     => $row['materia'],
        'tipo'       => $row['tipo_materia']
    ];
}

// =====================================
// 3. ASIGNAR DOCENTES A MATERIAS (SIN HORARIO)
// =====================================

$asignaciones = []; // cada item: [grupo, materia, docente, id_nivel, tipo_materia]

foreach ($docentes as $doc) {
    $restantes = max(1, $doc['carga_deseada']); // al menos 1

    foreach ($gruposMaterias as $g) {
        if ($restantes <= 0) break;

        // Respetar nivel preferido si lo tiene
        if (!is_null($doc['nivel_pref']) && $doc['nivel_pref'] !== $g['id_nivel']) {
            continue;
        }

        foreach ($g['materias'] as $mat) {

            // Verificar que esta materia de este grupo no tenga ya docente
            $key = $g['id_grupo'] . '-' . $mat['id_materia'];
            if (isset($asignaciones[$key])) {
                continue;
            }

            // Registrar asignación docente–materia–grupo
            $asignaciones[$key] = [
                'id_grupo'   => $g['id_grupo'],
                'id_materia' => $mat['id_materia'],
                'id_docente' => $doc['id_docente'],
                'id_nivel'   => $g['id_nivel'],
                'tipo'       => $mat['tipo']
            ];

            $restantes--;
            if ($restantes <= 0) break;
        }
    }
}

// Convertir a lista normal
$asignaciones = array_values($asignaciones);

// =====================================
// 4. REGLAS RF-018 / RF-019 (Taller y Optativa)
//    Y AGRUPAR POR GRUPO
// =====================================

$porGrupo = [];
foreach ($asignaciones as $a) {
    $g = $a['id_grupo'];
    if (!isset($porGrupo[$g])) {
        $porGrupo[$g] = [
            'id_grupo' => $g,
            'id_nivel' => $a['id_nivel'],
            'materias' => []
        ];
    }
    $porGrupo[$g]['materias'][] = $a;
}

$grupos_sin_taller   = [];
$grupos_sin_optativa = [];

// Filtrar grupos que no cumplen reglas
foreach ($porGrupo as $gid => $gdata) {
    $nivel = $gdata['id_nivel'];

    $tieneTaller   = false;
    $tieneOptativa = false;

    foreach ($gdata['materias'] as $m) {
        if (strcasecmp($m['tipo'], 'Taller') === 0)    $tieneTaller = true;
        if (strcasecmp($m['tipo'], 'Optativa') === 0)  $tieneOptativa = true;
    }

    // Taller obligatorio en niveles 1..4
    if (in_array($nivel, [1,2,3,4], true) && !$tieneTaller) {
        $grupos_sin_taller[] = $gid;
        unset($porGrupo[$gid]);
        continue;
    }

    // Optativa obligatoria en niveles 4 y 5
    if (in_array($nivel, [4,5], true) && !$tieneOptativa) {
        $grupos_sin_optativa[] = $gid;
        unset($porGrupo[$gid]);
        continue;
    }
}

// =====================================
// 5. GENERAR HORARIO COMPLETO
// =====================================

// Bloques de clase (5 materias) + descanso entre 2 y 3 (no se inserta)
$bloques = [
    0 => ['inicio' => '07:00:00', 'fin' => '08:30:00'],
    1 => ['inicio' => '08:30:00', 'fin' => '10:00:00'],
    2 => ['inicio' => '10:30:00', 'fin' => '12:00:00'], // después del descanso
    3 => ['inicio' => '12:00:00', 'fin' => '13:30:00'],
    4 => ['inicio' => '13:30:00', 'fin' => '15:00:00'],
];

$dias = ['Lunes','Martes','Miercoles','Jueves','Viernes'];

// Tablas en memoria para evitar choques
$ocupacionDocente = []; // [id_docente][dia][bloque] = true
$ocupacionGrupo   = []; // [id_grupo][dia][bloque]   = true

$insertStmt = $pdo->prepare("
    INSERT INTO horarios (id_grupo, id_materia, id_docente, dia_semana, hora_inicio, hora_fin)
    VALUES (?, ?, ?, ?, ?, ?)
");

$asignaciones_insertadas = [];
$asignaciones_sin_espacio = [];

foreach ($porGrupo as $gid => $gdata) {

    $materiasGrupo = $gdata['materias'];

    foreach ($materiasGrupo as $m) {
        $colocado = false;

        // Buscar un hueco (día/bloque) libre para el grupo y el docente
        foreach ($dias as $dia) {

            for ($b = 0; $b < 5; $b++) {

                // verificar ocupación grupo/docente
                if (isset($ocupacionGrupo[$gid][$dia][$b]))   continue;
                if (isset($ocupacionDocente[$m['id_docente']][$dia][$b])) continue;

                // Registrar ocupación en memoria
                $ocupacionGrupo[$gid][$dia][$b] = true;
                $ocupacionDocente[$m['id_docente']][$dia][$b] = true;

                // Insertar en BD
                $insertStmt->execute([
                    $gid,
                    $m['id_materia'],
                    $m['id_docente'],
                    $dia,
                    $bloques[$b]['inicio'],
                    $bloques[$b]['fin']
                ]);

                $asignaciones_insertadas[] = [
                    'grupo'   => $gid,
                    'materia' => $m['id_materia'],
                    'docente' => $m['id_docente'],
                    'dia'     => $dia,
                    'bloque'  => $b
                ];

                $colocado = true;
                break; // siguiente materia
            }

            if ($colocado) break;
        }

        if (!$colocado) {
            $asignaciones_sin_espacio[] = [
                'grupo'   => $gid,
                'materia' => $m['id_materia'],
                'docente' => $m['id_docente']
            ];
        }
    }
}

// =====================================
// 6. RESPUESTA JSON
// =====================================

echo json_encode([
    'status'                => 'ok',
    'total_asignaciones'    => count($asignaciones_insertadas),
    'asignaciones'          => $asignaciones_insertadas,
    'grupos_sin_taller'     => $grupos_sin_taller,
    'grupos_sin_optativa'   => $grupos_sin_optativa,
    'sin_espacio_horario'   => $asignaciones_sin_espacio
], JSON_PRETTY_PRINT);
exit;
