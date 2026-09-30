<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Directorio donde se guardarán las imágenes
$directorio = __DIR__ . "/../uploads/productos/";

// Crear el directorio si no existe
if (!file_exists($directorio)) {
    mkdir($directorio, 0777, true);
}

// Validar que se haya enviado un archivo
if (!isset($_FILES['imagen'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No se recibió ninguna imagen."]);
    exit;
}

$archivo = $_FILES['imagen'];

// Validar tipo de archivo (solo imágenes)
$tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
if (!in_array($archivo['type'], $tiposPermitidos)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Solo se permiten imágenes JPG, PNG o WEBP."]);
    exit;
}

// Validar tamaño (máximo 5MB)
if ($archivo['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "La imagen no debe superar los 5MB."]);
    exit;
}

// Generar nombre único para evitar sobrescribir
$extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
$nombreUnico = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
$rutaDestino = $directorio . $nombreUnico;

// Mover el archivo
if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Imagen subida correctamente.",
        "ruta" => "uploads/productos/" . $nombreUnico
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al mover el archivo."]);
}
?>