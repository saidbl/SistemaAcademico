<?php
require_once __DIR__ . '/../config/database.php';

class Materia {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Listar todas las materias
    public function listar() {
        $stmt = $this->pdo->query("
            SELECT m.id_materia, m.nombre, m.tipo, n.nombre AS nivel
            FROM materias m
            JOIN niveles_educativos n ON m.id_nivel = n.id_nivel
            ORDER BY n.id_nivel, m.id_materia
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener una materia por ID
    public function obtener($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM materias WHERE id_materia = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear una materia (y asignar según el nivel)
    public function crear($nombre, $tipo, $id_nivel, $id_carrera = null, $id_nivel_academico = null) {
        // Insertar materia base
        $stmt = $this->pdo->prepare("INSERT INTO materias (nombre, tipo, id_nivel) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $tipo, $id_nivel]);
        $id_materia = $this->pdo->lastInsertId();

        // Niveles 1–3 = Kinder, Primaria, Secundaria → materias_por_nivel_academico
        if (in_array($id_nivel, [1, 2, 3])) {
            $stmt = $this->pdo->prepare("
                INSERT INTO materias_por_nivel_academico (id_materia, id_nivel_academico)
                SELECT ?, id_nivel_academico
                FROM niveles_academicos
                WHERE id_nivel = ?
            ");
            $stmt->execute([$id_materia, $id_nivel]);
        } 
        // Niveles 4–5 = Prepa, Universidad → materias_por_carrera
        else if (in_array($id_nivel, [4, 5])) {
            if ($id_carrera && $id_nivel_academico) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO materias_por_carrera (id_carrera, id_materia, id_nivel_academico)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$id_carrera, $id_materia, $id_nivel_academico]);
            }
        }

        return $id_materia;
    }

    // Editar
    public function actualizar($id, $nombre, $tipo, $id_nivel) {
        $stmt = $this->pdo->prepare("
            UPDATE materias SET nombre = ?, tipo = ?, id_nivel = ? WHERE id_materia = ?
        ");
        return $stmt->execute([$nombre, $tipo, $id_nivel, $id]);
    }

    // Eliminar
    public function eliminar($id) {
        $stmt = $this->pdo->prepare("DELETE FROM materias WHERE id_materia = ?");
        return $stmt->execute([$id]);
    }

    // Obtener niveles educativos
    public function obtenerNiveles() {
        $stmt = $this->pdo->query("SELECT * FROM niveles_educativos ORDER BY id_nivel");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener carreras
    public function obtenerCarreras($nivel = null) {
        if ($nivel) {
            $stmt = $this->pdo->prepare("SELECT * FROM carreras WHERE id_nivel = ?");
            $stmt->execute([$nivel]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Obtener semestres (niveles académicos) por nivel
    public function obtenerNivelesAcademicos($nivel) {
        $stmt = $this->pdo->prepare("SELECT * FROM niveles_academicos WHERE id_nivel = ?");
        $stmt->execute([$nivel]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
