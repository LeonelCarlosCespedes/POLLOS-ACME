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
            $query = "SELECT id_usuario, nombre_completo, rol, username, estado FROM usuarios ORDER BY nombre_completo";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $usuarios]);
            break;

        case 'POST':
            // Hashear la contraseña
            $passwordHash = password_hash($data->password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO usuarios (nombre_completo, rol, username, password_hash, estado) 
                      VALUES (:nombre, :rol, :username, :password, 1)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nombre", $data->nombre_completo);
            $stmt->bindParam(":rol", $data->rol);
            $stmt->bindParam(":username", $data->username);
            $stmt->bindParam(":password", $passwordHash);
            $stmt->execute();
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Usuario registrado.", "id" => $db->lastInsertId()]);
            break;

        case 'PUT':
            // Si viene nueva contraseña, hashearla
            if (isset($data->password) && !empty($data->password)) {
                $passwordHash = password_hash($data->password, PASSWORD_DEFAULT);
                $query = "UPDATE usuarios SET nombre_completo=:nombre, rol=:rol, username=:username, 
                          password_hash=:password WHERE id_usuario = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":password", $passwordHash);
            } else {
                $query = "UPDATE usuarios SET nombre_completo=:nombre, rol=:rol, username=:username 
                          WHERE id_usuario = :id";
                $stmt = $db->prepare($query);
            }
            $stmt->bindParam(":nombre", $data->nombre_completo);
            $stmt->bindParam(":rol", $data->rol);
            $stmt->bindParam(":username", $data->username);
            $stmt->bindParam(":id", $data->id_usuario);
            $stmt->execute();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Usuario actualizado."]);
            break;

        case 'DELETE':
            $query = "UPDATE usuarios SET estado = 0 WHERE id_usuario = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $data->id_usuario);
            $stmt->execute();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Usuario desactivado."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>