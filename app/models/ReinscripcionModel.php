<?php

class ReinscripcionModel
{
    private PDO $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    /* ================================
       UTILIDADES
    =================================*/
    private function parametro($nombre, $default = null)
    {
        $stmt = $this->db->prepare("SELECT valor FROM parametros_sistema WHERE nombre = ?");
        $stmt->execute([$nombre]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    private function siguienteNivelAcademico($idNivelAcademico)
    {
        $sql = "
            SELECT na2.id_nivel_academico
            FROM niveles_academicos na1
            JOIN niveles_academicos na2 
              ON na2.id_nivel = na1.id_nivel
             AND na2.orden = na1.orden + 1
            WHERE na1.id_nivel_academico = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idNivelAcademico]);
        $next = $stmt->fetchColumn();

        return $next ?: null;
    }

    private function buscarGrupo($idNivel, $idNivelAcademico, $periodo)
    {
        $sql = "
            SELECT g.id_grupo,
                   g.cupo_maximo,
                   (
                        SELECT COUNT(*) 
                        FROM reinscripciones r 
                        WHERE r.id_grupo = g.id_grupo AND r.periodo = ?
                   ) AS ocupados
            FROM grupos g
            WHERE g.id_nivel = ?
              AND g.id_nivel_academico = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$periodo, $idNivel, $idNivelAcademico]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ((int)$row["ocupados"] < (int)$row["cupo_maximo"]) {
                return (int)$row["id_grupo"];
            }
        }
        return null;
    }

    /* ================================
       RF-027 — VERIFICAR APTITUD
    =================================*/
    public function esApto($idAlumno)
    {
        // PROMEDIO
        $stmt = $this->db->prepare("SELECT promedio_general, id_usuario FROM alumnos WHERE id_alumno = ?");
        $stmt->execute([$idAlumno]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $promMin = $this->parametro("PROMEDIO_MIN_REINSCRIPCION", 6.0);
        if ($row["promedio_general"] < $promMin) return false;

        // REPORTES
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM incidencias WHERE id_involucrado = ?");
        $stmt->execute([$row["id_usuario"]]);
        $reportes = $stmt->fetchColumn();
        if ($reportes > $this->parametro("MAX_REPORTES_REINSCRIPCION", 3)) return false;

        // REPROBADAS
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM calificaciones
            WHERE id_alumno = ? AND calificacion < 6
        ");
        $stmt->execute([$idAlumno]);
        $reprobadas = $stmt->fetchColumn();
        if ($reprobadas > $this->parametro("MAX_REPROBADAS_REINSCRIPCION", 0)) return false;

        return true;
    }

    /* ================================
       RF-025 + RF-029 — BÁSICOS
    =================================*/
    public function inscribirBasicos($periodo)
    {
        $SQL = "
            SELECT id_alumno, id_nivel, id_nivel_academico
            FROM alumnos
            WHERE id_nivel IN (1,2,3)
        ";
        $alumnos = $this->db->query($SQL)->fetchAll(PDO::FETCH_ASSOC);

        $res = [
            "procesados" => 0,
            "inscritos" => 0,
            "sinGrupo" => 0
        ];

        foreach ($alumnos as $al) {
            $res["procesados"]++;

            $next = $this->siguienteNivelAcademico($al["id_nivel_academico"]);
            if (!$next) continue;

            $idGrupo = $this->buscarGrupo($al["id_nivel"], $next, $periodo);
            if (!$idGrupo) {
                $res["sinGrupo"]++;
                continue;
            }

            // Registrar reinscripción
            $stmt = $this->db->prepare("
                INSERT INTO reinscripciones (id_alumno, id_grupo, periodo, fecha)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$al["id_alumno"], $idGrupo, $periodo]);

            // Actualizar nivel académico
            $stmt = $this->db->prepare("
                UPDATE alumnos SET id_nivel_academico = ? WHERE id_alumno = ?
            ");
            $stmt->execute([$next, $al["id_alumno"]]);

            // Inscribir materias
            $stmt = $this->db->prepare("
                SELECT id_materia FROM materias_por_nivel_academico WHERE id_nivel_academico = ?
            ");
            $stmt->execute([$next]);
            $materias = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($materias as $m) {
                $insertCalif = $this->db->prepare("
                    INSERT INTO calificaciones (id_alumno, id_materia, periodo)
                    VALUES (?, ?, ?)
                ");
                $insertCalif->execute([$al["id_alumno"], $m, $periodo . "-P1"]);
            }

            $res["inscritos"]++;
        }

        return $res;
    }

    /* ================================
       RF-026 — CITAS PREPA/UNI
    =================================*/
    public function generarCitas($periodo, $inicio)
    {
        $SQL = "
            SELECT id_alumno, promedio_general, id_usuario
            FROM alumnos
            WHERE id_nivel IN (4,5)
            ORDER BY promedio_general DESC,
                     (SELECT COUNT(*) FROM incidencias i WHERE i.id_involucrado = alumnos.id_usuario) ASC,
                     fecha_ingreso ASC
        ";
        $alumnos = $this->db->query($SQL)->fetchAll(PDO::FETCH_ASSOC);

        $base = new DateTime($inicio);
        $count = 0;
        $porHora = 20;

        foreach ($alumnos as $al) {
            if (!$this->esApto($al["id_alumno"])) continue;

            $hora = clone $base;
            $hora->modify("+" . floor($count / $porHora) . " hour");

            $stmt = $this->db->prepare("
                INSERT INTO citas_reinscripcion (id_alumno, fecha, turno, periodo)
                VALUES (?, ?, 'Matutino', ?)
            ");
            $stmt->execute([$al["id_alumno"], $hora->format("Y-m-d H:i:s"), $periodo]);

            $count++;
        }

        return ["citas_generadas" => $count];
    }

    /* ================================
       RF-028 — RECURSE
    =================================*/
    public function generarRecursamiento($periodo)
    {
        $sql = "
            SELECT DISTINCT id_alumno
            FROM calificaciones
            WHERE calificacion < 6
        ";
        $alumnos = $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        $total = 0;

        foreach ($alumnos as $al) {
            $stmt = $this->db->prepare("
                SELECT id_materia FROM calificaciones 
                WHERE id_alumno = ? AND calificacion < 6
            ");
            $stmt->execute([$al]);
            $mats = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($mats as $m) {
                $ins = $this->db->prepare("
                    INSERT INTO recursamiento (id_alumno, id_materia, periodo, estado)
                    VALUES (?, ?, ?, 'Pendiente')
                ");
                $ins->execute([$al, $m, $periodo]);
                $total++;
            }
        }

        return ["total_recursamientos" => $total];
    }

    /* ================================
       REINSCRIPCION PREPA/UNI COMPLETA
    =================================*/
    public function reinscribirPrepaUni($periodo)
    {
        $sql = "
            SELECT c.id_alumno, a.id_nivel, a.id_nivel_academico
            FROM citas_reinscripcion c
            JOIN alumnos a ON a.id_alumno = c.id_alumno
            WHERE c.periodo = ?
        ";
        $st = $this->db->prepare($sql);
        $st->execute([$periodo]);
        $citas = $st->fetchAll(PDO::FETCH_ASSOC);

        $res = [
            "procesados" => 0,
            "reinscritos" => 0,
            "noAptos" => 0,
            "sinGrupo" => 0
        ];

        foreach ($citas as $c) {
            $res["procesados"]++;

            if (!$this->esApto($c["id_alumno"])) {
                $res["noAptos"]++;
                continue;
            }

            $next = $this->siguienteNivelAcademico($c["id_nivel_academico"]);
            if (!$next) continue;

            $idGrupo = $this->buscarGrupo($c["id_nivel"], $next, $periodo);
            if (!$idGrupo) {
                $res["sinGrupo"]++;
                continue;
            }

            // Registrar reinscripción final
            $stmt = $this->db->prepare("
                INSERT INTO reinscripciones (id_alumno, id_grupo, periodo, fecha)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$c["id_alumno"], $idGrupo, $periodo]);

            $res["reinscritos"]++;
        }

        return $res;
    }
}
