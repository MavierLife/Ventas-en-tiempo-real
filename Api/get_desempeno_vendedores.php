<?php
// Api/get_desempeno_vendedores.php
session_start();

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/filters.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // Cambiar a 1 para depurar, 0 para producción
// error_reporting(E_ALL); // Para depuración
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/php-error-desempeno-vendedores.log');


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_GET['fecha_fin'] ?? null;
// El parámetro 'orden' definirá por qué columna ordenar.
// Valores posibles: 'monto' (default), 'unidades'
// Nota: 'orden' en GET se refiere a la columna principal para la consulta SQL inicial.
// El JS maneja el ordenamiento más detallado (ASC/DESC y todas las columnas visibles) en el cliente.
$orden_sql_columna_principal = $_GET['orden'] ?? 'monto';


if (!$fecha_inicio || !$fecha_fin ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
    http_response_code(400);
    echo json_encode(['error' => 'Fechas no válidas. Formato esperado YYYY-MM-DD.']);
    exit;
}

// Validar el parámetro de orden para la consulta SQL
if (!in_array($orden_sql_columna_principal, ['monto', 'unidades'])) {
    // Si no es un valor válido, usar un default seguro.
    $orden_sql_columna_principal = 'monto';
}


try {
    $pdo = getPDOConnection();
    $product_filter_sql = get_product_filter_sql('p'); // Alias 'p' para tblcatalogodeproductos

    // Determinar la columna por la cual ordenar en la consulta SQL
    $order_by_column_sql = "";
    if ($orden_sql_columna_principal === 'monto') {
        $order_by_column_sql = "total_monto_vendido DESC";
    } elseif ($orden_sql_columna_principal === 'unidades') {
        $order_by_column_sql = "total_unidades_vendidas DESC";
    } else {
        // Fallback por si acaso, aunque ya se validó arriba.
        $order_by_column_sql = "total_monto_vendido DESC";
    }

    $sql = "
        SELECT
            d.UsuarioRegistro AS vendedor,
            SUM(
                CASE
                    WHEN d.TV = 2 THEN d.Cantidad * p.Unidades
                    ELSE d.Cantidad
                END
            ) AS total_unidades_vendidas,
            SUM(d.Cantidad * d.PrecioVenta) AS total_monto_vendido
        FROM tblnotasdeentregadetalle d
        INNER JOIN tblnotasdeentrega e ON d.UUIDVenta = e.UUIDVenta
        INNER JOIN tblcatalogodeproductos p ON d.CodigoPROD = p.CodigoPROD
        WHERE DATE(e.FechaHoraGeneracion) BETWEEN :fecha_inicio AND :fecha_fin
          AND d.UsuarioRegistro IS NOT NULL AND d.UsuarioRegistro != ''
          {$product_filter_sql}
        GROUP BY d.UsuarioRegistro
        ORDER BY {$order_by_column_sql}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $vendedores_data = [];
    foreach ($resultados as $row) {
        $vendedores_data[] = [
            'vendedor' => $row['vendedor'],
            'cantidad_ventas' => (int)$row['total_unidades_vendidas'], // CORREGIDO: 'unidades' a 'cantidad_ventas'
            'monto_vendido' => (float)$row['total_monto_vendido']    // CORREGIDO: 'monto' a 'monto_vendido'
        ];
    }

    echo json_encode([
        'data' => $vendedores_data,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'orden_aplicado_sql' => $orden_sql_columna_principal // Indica el orden usado en la consulta SQL
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de BD en get_desempeno_vendedores.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos al procesar la solicitud de desempeño de vendedores.']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error general en get_desempeno_vendedores.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error procesando la solicitud de desempeño de vendedores: ' . $e->getMessage()]);
    exit;
}
?>