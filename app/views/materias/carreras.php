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

    // Consulta carreras por nivel
    $stmt = $pdo->prepare("SELECT id_carrera, nombre, tipo FROM carreras WHERE id_nivel = ? ORDER BY nombre ASC");
    $stmt->execute([$id_nivel]);
    $carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($carreras ?: []);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener carreras", "detalle" => $e->getMessage()]);
}
?>
