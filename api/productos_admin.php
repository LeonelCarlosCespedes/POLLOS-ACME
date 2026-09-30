<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once __DIR__ . "/config/database.php";

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error de conexion."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput);

try {
    switch ($method) {
        case 'GET':
            $query = "SELECT p.*, c.nombre as categoria 
                      FROM productos p
                      LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                      ORDER BY p.id_producto DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $productos]);
            break;

        case 'POST':
            $query = "INSERT INTO productos (id_categoria, nombre, descripcion, precio, tipo, imagen, estado) 
                      VALUES (:id_categoria, :nombre, :descripcion, :precio, :tipo, :imagen, 1)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id_categoria", $data->id_categoria);
            $stmt->bindParam(":nombre", $data->nombre);
            $stmt->bindParam(":descripcion", $data->descripcion);
            $stmt->bindParam(":precio", $data->precio);
            $stmt->bindParam(":tipo", $data->tipo);
            $stmt->bindParam(":imagen", $data->imagen);
            $stmt->execute();
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Producto creado.", "id" => $db->lastInsertId()]);
            break;

        case 'PUT':
            if (isset($data->imagen) && $data->imagen !== null) {
                $query = "UPDATE productos SET id_categoria=:id_categoria, nombre=:nombre, 
                          descripcion=:descripcion, precio=:precio, tipo=:tipo, imagen=:imagen 
                          WHERE id_producto = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":imagen", $data->imagen);
            } else {
                $query = "UPDATE productos SET id_categoria=:id_categoria, nombre=:nombre, 
                          descripcion=:descripcion, precio=:precio, tipo=:tipo 
                          WHERE id_producto = :id";
                $stmt = $db->prepare($query);
            }
            $stmt->bindParam(":id_categoria", $data->id_categoria);
            $stmt->bindParam(":nombre", $data->nombre);
            $stmt->bindParam(":descripcion", $data->descripcion);
            $stmt->bindParam(":precio", $data->precio);
            $stmt->bindParam(":tipo", $data->tipo);
            $stmt->bindParam(":id", $data->id_producto);
            $stmt->execute();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Producto actualizado."]);
            break;

        case 'DELETE':
            if (!isset($data->id_producto)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "ID de producto requerido."]);
                exit;
            }

            // Soft delete: marcar como inactivo
            $query = "UPDATE productos SET estado = 0 WHERE id_producto = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $data->id_producto);
            $stmt->execute();

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Producto eliminado."]);
            break;

        default:
            http_response_code(405);
            echo json_encode(["status" => "error", "message" => "Metodo no permitido."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>