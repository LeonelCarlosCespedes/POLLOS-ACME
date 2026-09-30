<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once __DIR__ . "/config/database.php";

$database = new Database();
$db = $database->getConnection();

if($db) {
    $query = "SELECT p.id_producto, p.nombre, p.precio, p.tipo, p.imagen, c.nombre as categoria 
              FROM productos p
              INNER JOIN categorias c ON p.id_categoria = c.id_categoria
              WHERE p.estado = 1 
              ORDER BY c.nombre, p.nombre";
              
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if($productos) {
        http_response_code(200);
        echo json_encode(["status" => "success", "data" => $productos]);
    } else {
        http_response_code(200);
        echo json_encode(["status" => "success", "data" => [], "message" => "No hay productos disponibles"]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "No se pudo conectar a la base de datos"]);
}
?>