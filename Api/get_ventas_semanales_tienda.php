<?php
// Api/get_ventas_semanales_tienda.php
session_start();

// Incluir los nuevos archivos de configuración
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/filters.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // Cambiar a 1 para depurar, 0 para producción
// error_reporting(E_ALL); // Para depuración
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/php-error-ventas-diarias.log'); // Nombre de log cambiado

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

$fecha_inicio_str = $_GET['fecha_inicio'] ?? null;
$fecha_fin_str = $_GET['fecha_fin'] ?? null;

if (!$fecha_inicio_str || !$fecha_fin_str ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio_str) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin_str)) {
    http_response_code(400);
    echo json_encode(['error' => 'Fechas no válidas. Formato esperado YYYY-MM-DD.']);
    exit;
}

try {
    $fecha_inicio_dt = new DateTime($fecha_inicio_str);
    $fecha_fin_dt = new DateTime($fecha_fin_str);

    if ($fecha_inicio_dt > $fecha_fin_dt) {
        http_response_code(400);
        echo json_encode(['error' => 'La fecha de inicio no puede ser posterior a la fecha de fin.']);
        exit;
    }

    $pdo = getPDOConnection();
    $product_filter_sql = get_product_filter_sql('p');

    // Consulta para obtener ventas agrupadas por fecha exacta
    $sql = "
        SELECT
            DATE(e.FechaHoraGeneracion) as fecha_venta,
            SUM(d.Cantidad * d.PrecioVenta) AS monto_total_venta_dia
        FROM tblnotasdeentregadetalle d
        INNER JOIN tblnotasdeentrega e ON d.UUIDVenta = e.UUIDVenta
        INNER JOIN tblcatalogodeproductos p ON d.CodigoPROD = p.CodigoPROD
        WHERE DATE(e.FechaHoraGeneracion) BETWEEN :fecha_inicio AND :fecha_fin
          {$product_filter_sql}
        GROUP BY DATE(e.FechaHoraGeneracion)
        ORDER BY fecha_venta ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':fecha_inicio', $fecha_inicio_str, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_fin', $fecha_fin_str, PDO::PARAM_STR);
    $stmt->execute();
    $resultados_query = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear un mapa de ventas por fecha para fácil acceso
    $ventas_por_fecha = [];
    foreach ($resultados_query as $row) {
        $ventas_por_fecha[$row['fecha_venta']] = (float)$row['monto_total_venta_dia'];
    }

    $labels = [];
    $datos = [];

    // Iterar sobre el rango de fechas para asegurar que todos los días estén presentes
    $intervalo = new DateInterval('P1D'); // Intervalo de 1 día
    $periodo_fechas = new DatePeriod($fecha_inicio_dt, $intervalo, $fecha_fin_dt->modify('+1 day')); // Incluir el día final

    foreach ($periodo_fechas as $fecha_dt) {
        $fecha_actual_str_ymd = $fecha_dt->format('Y-m-d'); // Formato YYYY-MM-DD para la clave
        $fecha_actual_str_dmy = $fecha_dt->format('d/m/Y'); // Formato DD/MM/YYYY para la etiqueta

        $labels[] = $fecha_actual_str_dmy;
        $datos[] = $ventas_por_fecha[$fecha_actual_str_ymd] ?? 0.0; // Usar 0.0 si no hay ventas
    }
    
    // Si el periodo es muy grande, podrías considerar un límite o agregación aquí,
    // pero por ahora, se mostrarán todas las barras individuales como solicitado.

    $datasets = [[
        'label' => 'Ventas Diarias', // Cambiado el label del dataset
        'data' => $datos,
        'backgroundColor' => 'rgba(54, 162, 235, 0.7)',
        'borderColor' => 'rgba(54, 162, 235, 1)',
        'borderWidth' => 1
    ]];

    echo json_encode([
        'labels' => $labels,
        'datasets' => $datasets,
        'startDate' => $fecha_inicio_str, // Mantener YYYY-MM-DD para referencia interna
        'endDate' => $fecha_fin_str    // Mantener YYYY-MM-DD para referencia interna
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de BD en get_ventas_semanales_tienda.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos al procesar la solicitud diaria.']); // Mensaje ajustado
    exit;
} catch (Exception $e) { // DateTime puede lanzar excepciones también
    http_response_code(500);
    error_log("Error general en get_ventas_semanales_tienda.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error procesando la solicitud diaria: ' . $e->getMessage()]); // Mensaje ajustado
    exit;
}
?>