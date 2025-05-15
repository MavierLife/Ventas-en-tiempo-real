<?php
// Api/get_ventas_por_hora_rango.php
session_start();

// Incluir los nuevos archivos de configuración
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/filters.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // Cambiar a 1 para depurar, 0 para producción
// error_reporting(E_ALL); // Para depuración
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/php-error-ventas-hora.log');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

$fecha_inicio_str = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fecha_fin_str = $_GET['fecha_fin'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio_str) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin_str)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha inválido. Use YYYY-MM-DD.']);
    exit;
}

try {
    $pdo = getPDOConnection(); // Usar la función centralizada

    // Obtener el filtro de producto usando la función centralizada
    // El alias 'p' corresponde a la tabla tblcatalogodeproductos en la consulta de este archivo.
    $product_filter_sql = get_product_filter_sql('p');

    $sql = "
        SELECT
            HOUR(e.FechaHoraGeneracion) AS hora_venta,
            SUM(d.Cantidad * d.PrecioVenta) AS monto_total_venta
        FROM tblnotasdeentregadetalle d
        INNER JOIN tblnotasdeentrega e ON d.UUIDVenta = e.UUIDVenta
        INNER JOIN tblcatalogodeproductos p ON d.CodigoPROD = p.CodigoPROD
        WHERE DATE(e.FechaHoraGeneracion) BETWEEN :fecha_inicio AND :fecha_fin
          {$product_filter_sql} -- Aplicar el filtro aquí
        GROUP BY HOUR(e.FechaHoraGeneracion)
        ORDER BY hora_venta ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':fecha_inicio', $fecha_inicio_str, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_fin', $fecha_fin_str, PDO::PARAM_STR);
    $stmt->execute();
    $resultados = $stmt->fetchAll();

    $labels_horas = [];
    for ($h = 0; $h < 24; $h++) {
        $labels_horas[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
    }

    $datos_ventas_hora = array_fill(0, 24, 0.0); // Inicializar array de 24 horas con 0.0
    foreach ($resultados as $row) {
        $datos_ventas_hora[(int)$row['hora_venta']] = (float)$row['monto_total_venta'];
    }

    $datasets = [[
        'label' => 'Ventas por Hora',
        'data' => $datos_ventas_hora,
        'borderColor' => 'rgba(75, 192, 192, 1)',
        'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
        'fill' => true,
        // 'type' => 'bar', // Podría ser un gráfico de barras
    ]];

    echo json_encode([
        'labels' => $labels_horas,
        'datasets' => $datasets,
        'startDate' => $fecha_inicio_str,
        'endDate' => $fecha_fin_str
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de BD en get_ventas_por_hora_rango.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos al procesar la solicitud.']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error general en get_ventas_por_hora_rango.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error procesando la solicitud: ' . $e->getMessage()]);
    exit;
}
?>