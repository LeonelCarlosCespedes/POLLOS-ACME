<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

try {
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'ventas_dia';

    switch ($tipo) {
        case 'ventas_dia':
            // Ventas del día actual
            $query = "SELECT 
                        COUNT(*) as total_ventas,
                        SUM(total) as monto_total,
                        AVG(total) as promedio
                      FROM pedidos 
                      WHERE DATE(fecha_hora) = CURDATE() AND estado != 'Cancelado'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $resultado]);
            break;

        case 'ventas_periodo':
            // Ventas por rango de fechas
            $fechaInicio = isset($_GET['inicio']) ? $_GET['inicio'] : date('Y-m-d');
            $fechaFin = isset($_GET['fin']) ? $_GET['fin'] : date('Y-m-d');
            
            $query = "SELECT 
                        DATE(fecha_hora) as fecha,
                        COUNT(*) as total_ventas,
                        SUM(total) as monto_total
                      FROM pedidos 
                      WHERE DATE(fecha_hora) BETWEEN :inicio AND :fin AND estado != 'Cancelado'
                      GROUP BY DATE(fecha_hora)
                      ORDER BY fecha DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":inicio", $fechaInicio);
            $stmt->bindParam(":fin", $fechaFin);
            $stmt->execute();
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $resultado]);
            break;

        case 'productos_top':
            // Top 10 productos más vendidos
            $query = "SELECT 
                        p.nombre,
                        SUM(dp.cantidad) as total_vendido,
                        SUM(dp.subtotal_item) as monto_total
                      FROM detalle_pedidos dp
                      INNER JOIN productos p ON dp.id_producto = p.id_producto
                      INNER JOIN pedidos ped ON dp.id_pedido = ped.id_pedido
                      WHERE ped.estado != 'Cancelado'
                      GROUP BY p.id_producto, p.nombre
                      ORDER BY total_vendido DESC
                      LIMIT 10";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $resultado]);
            break;

        case 'metodos_pago':
            // Resumen por método de pago (hoy)
            $query = "SELECT 
                        metodo_pago,
                        COUNT(*) as cantidad,
                        SUM(monto) as total
                      FROM pagos 
                      WHERE DATE(fecha_hora) = CURDATE() AND estado = 'Exitoso'
                      GROUP BY metodo_pago";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $resultado]);
            break;

        default:
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Tipo de reporte no válido."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>