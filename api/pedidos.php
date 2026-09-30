<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

include_once __DIR__ . "/config/database.php";

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON: " . json_last_error_msg()]);
    exit;
}

if (!isset($data->productos) || !is_array($data->productos) || count($data->productos) === 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Cart is empty or invalid data"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection error"]);
    exit;
}

try {
    $db->beginTransaction();

    $subtotal = 0;
    foreach ($data->productos as $prod) {
        $subtotal += floatval($prod->precio) * intval($prod->cantidad);
    }

    $impuestos = $subtotal * 0.13;
    $total = $subtotal + $impuestos;

    $id_cliente = isset($data->id_cliente) ? $data->id_cliente : null;
    $id_usuario = isset($data->id_usuario) ? intval($data->id_usuario) : 1;
    $tipo_pedido = isset($data->tipo_pedido) ? $data->tipo_pedido : 'Local';

    $queryPedido = "INSERT INTO pedidos (id_cliente, id_usuario, tipo_pedido, subtotal, impuestos, total, estado, fecha_hora) 
                    VALUES (:id_cliente, :id_usuario, :tipo_pedido, :subtotal, :impuestos, :total, 'Entregado', NOW())";
    $stmtPedido = $db->prepare($queryPedido);
    $stmtPedido->bindParam(":id_cliente", $id_cliente);
    $stmtPedido->bindParam(":id_usuario", $id_usuario);
    $stmtPedido->bindParam(":tipo_pedido", $tipo_pedido);
    $stmtPedido->bindParam(":subtotal", $subtotal);
    $stmtPedido->bindParam(":impuestos", $impuestos);
    $stmtPedido->bindParam(":total", $total);
    $stmtPedido->execute();

    $id_pedido = $db->lastInsertId();

    $queryDetalle = "INSERT INTO detalle_pedidos (id_pedido, id_producto, cantidad, precio_unitario, subtotal_item, observacion) 
                     VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario, :subtotal_item, :observacion)";
    $stmtDetalle = $db->prepare($queryDetalle);

    foreach ($data->productos as $prod) {
        $subtotal_item = floatval($prod->precio) * intval($prod->cantidad);
        $observacion = isset($prod->observacion) ? $prod->observacion : null;
        
        $stmtDetalle->bindParam(":id_pedido", $id_pedido);
        $stmtDetalle->bindParam(":id_producto", $prod->id);
        $stmtDetalle->bindParam(":cantidad", $prod->cantidad);
        $stmtDetalle->bindParam(":precio_unitario", $prod->precio);
        $stmtDetalle->bindParam(":subtotal_item", $subtotal_item);
        $stmtDetalle->bindParam(":observacion", $observacion);
        $stmtDetalle->execute();
    }

    $metodo_pago = isset($data->metodo_pago) ? $data->metodo_pago : 'Efectivo';
    
    $queryPago = "INSERT INTO pagos (id_pedido, metodo_pago, monto, estado, fecha_hora) 
                  VALUES (:id_pedido, :metodo_pago, :monto, 'Exitoso', NOW())";
    $stmtPago = $db->prepare($queryPago);
    $stmtPago->bindParam(":id_pedido", $id_pedido);
    $stmtPago->bindParam(":metodo_pago", $metodo_pago);
    $stmtPago->bindParam(":monto", $total);
    $stmtPago->execute();

    $db->commit();

    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Sale registered successfully",
        "id_pedido" => intval($id_pedido),
        "total" => floatval($total)
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>