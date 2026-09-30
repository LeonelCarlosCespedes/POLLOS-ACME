<?php
// Configurar origen dinámico para permitir credenciales sin conflictos CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Manejar preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once __DIR__ . "/config/database.php";

// Leer JSON recibido
$data = json_decode(file_get_contents("php://input"));

// Validar que la estructura recibida tenga 'username' y 'password'
if (!isset($data->username) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Usuario y contraseña son requeridos. Verifica las claves JSON en JS."
    ]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos."]);
    exit;
}

try {
    // Buscar usuario activo
    $query = "SELECT id_usuario, nombre_completo, username, password_hash, rol 
              FROM usuarios 
              WHERE username = :username AND estado = 1 
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $data->username);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Verificar hash de contraseña
        if (password_verify($data->password, $usuario['password_hash'])) {
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Login exitoso",
                "usuario" => [
                    "id" => $usuario['id_usuario'],
                    "nombre" => $usuario['nombre_completo'],
                    "username" => $usuario['username'],
                    "rol" => $usuario['rol']
                ]
            ]);
        } else {
            // 401: Contraseña no coincide con el hash
            http_response_code(401);
            echo json_encode([
                "status" => "error",
                "message" => "Contraseña incorrecta."
            ]);
        }
    } else {
        // 404: Usuario no existe o estado != 1
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Usuario no encontrado o inactivo."
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error en la consulta: " . $e->getMessage()]);
}
?>