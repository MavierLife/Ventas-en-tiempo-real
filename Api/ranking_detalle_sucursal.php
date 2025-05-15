<?php
// Api/ranking_detalle_sucursal.php
session_start(); // Necesario para acceder a $_SESSION['rol_usuario']

// Incluir los nuevos archivos de configuración
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/filters.php';

// --- INICIO SECCIÓN DE DEBUG ---
// Comenta o elimina estas líneas para producción
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/php-error-details.log');
// --- FIN SECCIÓN DE DEBUG ---

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');
// ini_set('display_errors', 0); // Ya controlado arriba o para producción

// Determinar rol y si es administrador
$rol_usuario = $_SESSION['rol_usuario'] ?? 'Invitado';
$es_administrador = ($rol_usuario === 'Administrador');

try {
    $pdo = getPDOConnection(); // Usar la función centralizada

    $sucursalId = $_GET['sucursal'] ?? null;
    $startDate  = $_GET['startDate'] ?? date('Y-m-d');
    $endDate    = $_GET['endDate']   ?? date('Y-m-d');

    if (empty($sucursalId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de sucursal no proporcionado.']);
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Formato de fecha inválido. Use YYYY-MM-DD.']);
        exit;
    }

    // Obtener el filtro de producto usando la función centralizada
    // El alias 'p' corresponde a la tabla tblcatalogodeproductos en la consulta de este archivo.
    $product_filter_sql = get_product_filter_sql('p');

    $sql = "
        SELECT
            p.CodigoPROD,
            p.Descripcion,
            SUM(
                CASE
                    WHEN d.TV = 2 THEN d.Cantidad * p.Unidades
                    ELSE d.Cantidad
                END
            ) AS total_unidades_prod,
            SUM(d.Cantidad * d.PrecioVenta) AS monto_total_prod,
            ps.Existencia AS existencia,
            p.Unidades AS unidades_fardo_catalogo,
            (SELECT d2.FechaRegistro
             FROM tblnotasdeentregadetalle d2
             INNER JOIN tblnotasdeentrega e2 ON d2.UUIDVenta = e2.UUIDVenta
             WHERE e2.UUIDSucursal = :sucursalId_limit AND d2.CodigoPROD = p.CodigoPROD AND DATE(e2.FechaHoraGeneracion) BETWEEN :startDate_limit AND :endDate_limit
             ORDER BY d2.FechaRegistro DESC LIMIT 1) AS ultima_venta,
            (SELECT d3.UsuarioRegistro
             FROM tblnotasdeentregadetalle d3
             INNER JOIN tblnotasdeentrega e3 ON d3.UUIDVenta = e3.UUIDVenta
             WHERE e3.UUIDSucursal = :sucursalId_limit2 AND d3.CodigoPROD = p.CodigoPROD AND DATE(e3.FechaHoraGeneracion) BETWEEN :startDate_limit2 AND :endDate_limit2
             ORDER BY d3.FechaRegistro DESC LIMIT 1) AS usuario_venta";

    if ($es_administrador) {
        $sql .= ",
            p.PrecioCosto AS costo_fardo_catalogo";
    }

    $sql .= "
        FROM tblnotasdeentregadetalle d
        INNER JOIN tblnotasdeentrega e
            ON d.UUIDVenta = e.UUIDVenta
        INNER JOIN tblcatalogodeproductos p
            ON d.CodigoPROD = p.CodigoPROD
        LEFT JOIN tblproductossucursal ps
            ON p.CodigoPROD = ps.CodigoPROD AND ps.UUIDSucursal = e.UUIDSucursal
        WHERE e.UUIDSucursal = :sucursalId
          AND DATE(e.FechaHoraGeneracion) BETWEEN :startDate AND :endDate
          {$product_filter_sql} -- Aplicar el filtro aquí
        GROUP BY
            p.CodigoPROD,
            p.Descripcion,
            ps.Existencia,
            p.Unidades" . ($es_administrador ? ", p.PrecioCosto" : "") . "
        ORDER BY
            monto_total_prod DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':sucursalId', $sucursalId, PDO::PARAM_STR);
    $stmt->bindValue(':startDate', $startDate, PDO::PARAM_STR);
    $stmt->bindValue(':endDate', $endDate, PDO::PARAM_STR);

    // Bindings para las subconsultas (necesario si el filtro principal no se aplica a ellas)
    // Si el filtro de producto DEBE aplicarse también dentro de las subconsultas de ultima_venta y usuario_venta,
    // necesitarías añadir {$product_filter_sql} (con el alias correcto para 'p' en esa subconsulta) dentro de ellas también.
    // Por ahora, asumo que el filtro principal es suficiente.
    $stmt->bindValue(':sucursalId_limit', $sucursalId, PDO::PARAM_STR);
    $stmt->bindValue(':startDate_limit', $startDate, PDO::PARAM_STR);
    $stmt->bindValue(':endDate_limit', $endDate, PDO::PARAM_STR);

    $stmt->bindValue(':sucursalId_limit2', $sucursalId, PDO::PARAM_STR);
    $stmt->bindValue(':startDate_limit2', $startDate, PDO::PARAM_STR);
    $stmt->bindValue(':endDate_limit2', $endDate, PDO::PARAM_STR);

    $stmt->execute();
    $product_details_raw = $stmt->fetchAll();

    $detalle_final = [];
    foreach ($product_details_raw as $r) {
        $cantidad_vendida_prod = (int)($r['total_unidades_prod'] ?? 0);
        $monto_venta_prod = (float)($r['monto_total_prod'] ?? 0.0);
        $unidades_por_fardo_prod = (int)($r['unidades_fardo_catalogo'] ?? 1); // Evitar división por cero si es 0 o null

        $item_detalle = [
            'CodigoPROD'          => $r['CodigoPROD'],
            'Descripcion'         => $r['Descripcion'],
            'total_unidades_prod' => $cantidad_vendida_prod,
            'monto_total_prod'    => $monto_venta_prod,
            'existencia'          => (int)($r['existencia'] ?? 0),
            'unidades_fardo_info' => $unidades_por_fardo_prod,
            'ultima_venta'        => $r['ultima_venta'],
            'usuario'             => $r['usuario_venta']
        ];

        if ($es_administrador) {
            $costo_fardo_prod = (float)($r['costo_fardo_catalogo'] ?? 0.0);
            $costo_unitario_prod = 0.0;
            if ($unidades_por_fardo_prod > 0) {
                $costo_unitario_prod = $costo_fardo_prod / $unidades_por_fardo_prod;
            }
            $cogs_prod_actual = $cantidad_vendida_prod * $costo_unitario_prod;

            $item_detalle['costo_unitario_prod'] = $costo_unitario_prod;
            $item_detalle['cogs_prod'] = $cogs_prod_actual;
            $item_detalle['margen_bruto_prod'] = $monto_venta_prod - $cogs_prod_actual;
        }
        $detalle_final[] = $item_detalle;
    }

    echo json_encode(['detalle' => $detalle_final, 'user_is_admin' => $es_administrador]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    $error_message = 'Error de conexión o consulta de detalles. Por favor, intente más tarde.';
    if (ini_get('display_errors') == 1) { // Si estamos en modo debug, añadir más info
        $error_message .= ' PDO: ' . $e->getMessage();
        if (isset($sql)) { $error_message .= " | SQL (aprox): " . $sql; } // Puede no ser el SQL exacto si los bindings fallan
    }
    error_log("Error de base de datos (ranking_detalle_sucursal.php): " . $e->getMessage() . (isset($sql) ? " | SQL: " . $sql : "No SQL"));
    echo json_encode(['error' => $error_message]);
    exit;
} catch (Exception $e) {
    http_response_code(500); // O 400 si es un error de input del usuario
    $error_message = $e->getMessage();
    if (ini_get('display_errors') == 1 && isset($sql)) { // Si estamos en modo debug, añadir más info
         $error_message .= " | SQL (aprox): " . $sql;
    }
    error_log("Error general (ranking_detalle_sucursal.php): " . $e->getMessage());
    echo json_encode(['error' => $error_message]);
    exit;
}
?>