<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';

try {
    // Verifica parámetro
    if (!isset($_GET['id_nivel'])) {
        echo json_encode(["error" => "Falta el parámetro id_nivel"]);
        exit;
    }

    $id_nivel = intval($_GET['id_nivel']);

    // Consulta niveles académicos (grados o semestres)
    $stmt = $pdo->prepare("
        SELECT id_nivel_academico, nombre, tipo, orden
        FROM niveles_academicos
        WHERE id_nivel = ?
        ORDER BY orden ASC
    ");
    $stmt->execute([$id_nivel]);
    $niveles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($niveles ?: []);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener niveles académicos", "detalle" => $e->getMessage()]);
}
?>
