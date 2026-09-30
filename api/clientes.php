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
    echo json_encode(["status" => "error", "message" => "Error de conexión."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"));

try {
    switch ($method) {
        case 'GET':
            $query = "SELECT * FROM clientes WHERE estado = 1 ORDER BY nombre_razon_social";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $clientes]);
            break;

        case 'POST':
            $query = "INSERT INTO clientes (tipo_documento, numero_documento, nombre_razon_social, telefono, direccion) 
                      VALUES (:tipo, :numero, :nombre, :telefono, :direccion)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":tipo", $data->tipo_documento);
            $stmt->bindParam(":numero", $data->numero_documento);
            $stmt->bindParam(":nombre", $data->nombre_razon_social);
            $stmt->bindParam(":telefono", $data->telefono);
            $stmt->bindParam(":direccion", $data->direccion);
            $stmt->execute();
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Cliente registrado.", "id" => $db->lastInsertId()]);
            break;

        case 'PUT':
            $query = "UPDATE clientes SET tipo_documento=:tipo, numero_documento=:numero, 
                      nombre_razon_social=:nombre, telefono=:telefono, direccion=:direccion 
                      WHERE id_cliente = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":tipo", $data->tipo_documento);
            $stmt->bindParam(":numero", $data->numero_documento);
            $stmt->bindParam(":nombre", $data->nombre_razon_social);
            $stmt->bindParam(":telefono", $data->telefono);
            $stmt->bindParam(":direccion", $data->direccion);
            $stmt->bindParam(":id", $data->id_cliente);
            $stmt->execute();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Cliente actualizado."]);
            break;

        case 'DELETE':
            $query = "UPDATE clientes SET estado = 0 WHERE id_cliente = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $data->id_cliente);
            $stmt->execute();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Cliente eliminado."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>