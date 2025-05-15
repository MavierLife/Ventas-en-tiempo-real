<?php
// Api/ranking_all.php
session_start(); // Necesario para acceder a $_SESSION['rol_usuario']

// Incluir los nuevos archivos de configuración
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/filters.php';

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // En producción, errores a logs
// error_reporting(E_ALL); // Para depuración
// ini_set('log_errors', 1);
// ini_set('error_log', '/ruta/a/tu/php-error.log'); // Ajusta la ruta

// Determinar rol y si es administrador
$rol_usuario = $_SESSION['rol_usuario'] ?? 'Invitado';
$es_administrador = ($rol_usuario === 'Administrador');

try {
    $pdo = getPDOConnection(); // Usar la función centralizada

    $startDate = $_GET['startDate'] ?? date('Y-m-d');
    $endDate   = $_GET['endDate']   ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Formato de fecha inválido. Use YYYY-MM-DD.']);
        exit;
    }

    // Obtener el filtro de producto usando la función centralizada
    // El alias 'p' corresponde a la tabla tblcatalogodeproductos en las consultas de este archivo.
    $product_filter_sql = get_product_filter_sql('p');

    // --- Consulta para Vendedor Estrella (más unidades) ---
    $sqlVendedorEstrella = "
        SELECT
            d.UsuarioRegistro,
            SUM(
                CASE
                    WHEN d.TV = 2 THEN d.Cantidad * p.Unidades
                    ELSE d.Cantidad
                END
            ) AS total_unidades_vendidas
        FROM tblnotasdeentregadetalle d
        INNER JOIN tblnotasdeentrega e ON d.UUIDVenta = e.UUIDVenta
        INNER JOIN tblcatalogodeproductos p ON d.CodigoPROD = p.CodigoPROD
        WHERE DATE(e.FechaHoraGeneracion) BETWEEN :startDate AND :endDate
          {$product_filter_sql}
          AND d.UsuarioRegistro IS NOT NULL AND d.UsuarioRegistro != ''
        GROUP BY d.UsuarioRegistro
        ORDER BY total_unidades_vendidas DESC
        LIMIT 1
    ";
    $stmtVendedorEstrella = $pdo->prepare($sqlVendedorEstrella);
    $stmtVendedorEstrella->bindValue(':startDate', $startDate);
    $stmtVendedorEstrella->bindValue(':endDate', $endDate);
    $stmtVendedorEstrella->execute();
    $vendedorEstrellaData = $stmtVendedorEstrella->fetch();

    $vendedorEstrellaNombre = "N/A";
    $vendedorEstrellaUnidades = 0;
    if ($vendedorEstrellaData) {
        $vendedorEstrellaNombre = $vendedorEstrellaData['UsuarioRegistro'];
        $vendedorEstrellaUnidades = (int)$vendedorEstrellaData['total_unidades_vendidas'];
    }

    // --- Consulta para Vendedor Oro (mayor monto de ventas) ---
    $sqlVendedorOro = "
        SELECT
            d.UsuarioRegistro,
            SUM(d.Cantidad * d.PrecioVenta) AS monto_total_ventas
        FROM tblnotasdeentregadetalle d
        INNER JOIN tblnotasdeentrega e ON d.UUIDVenta = e.UUIDVenta
        INNER JOIN tblcatalogodeproductos p ON d.CodigoPROD = p.CodigoPROD
        WHERE DATE(e.FechaHoraGeneracion) BETWEEN :startDate AND :endDate
          {$product_filter_sql}
          AND d.UsuarioRegistro IS NOT NULL AND d.UsuarioRegistro != ''
        GROUP BY d.UsuarioRegistro
        ORDER BY monto_total_ventas DESC
        LIMIT 1
    ";
    $stmtVendedorOro = $pdo->prepare($sqlVendedorOro);
    $stmtVendedorOro->bindValue(':startDate', $startDate);
    $stmtVendedorOro->bindValue(':endDate', $endDate);
    $stmtVendedorOro->execute();
    $vendedorOroData = $stmtVendedorOro->fetch();

    $vendedorOroNombre = "N/A";
    $vendedorOroMonto = 0.0;
    if ($vendedorOroData) {
        $vendedorOroNombre = $vendedorOroData['UsuarioRegistro'];
        $vendedorOroMonto = (float)$vendedorOroData['monto_total_ventas'];
    }

    // --- Consulta principal para resúmenes por sucursal ---
    $sql = "
        SELECT
            e.UUIDSucursal AS sucursal,
            SUM(
                CASE
                    WHEN d.TV = 2 THEN d.Cantidad * p.Unidades
                    ELSE d.Cantidad
                END
            ) AS total_unidades_sucursal,
            SUM(d.Cantidad * d.PrecioVenta) AS monto_total_venta_sucursal";

    if ($es_administrador) {
        $sql .= ",
            SUM(
                (CASE
                    WHEN d.TV = 2 THEN d.Cantidad * p.Unidades
                    ELSE d.Cantidad
                END) * (p.PrecioCosto / CASE WHEN p.Unidades > 0 THEN p.Unidades ELSE 1 END)
            ) AS total_cogs_sucursal";
    }

    $sql .= "
        FROM tblnotasdeentregadetalle d
        INNER JOIN tblnotasdeentrega e ON d.UUIDVenta = e.UUIDVenta
        INNER JOIN tblcatalogodeproductos p ON d.CodigoPROD = p.CodigoPROD
        WHERE DATE(e.FechaHoraGeneracion) BETWEEN :startDate AND :endDate
          {$product_filter_sql}
        GROUP BY e.UUIDSucursal
        ORDER BY monto_total_venta_sucursal DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':startDate', $startDate);
    $stmt->bindValue(':endDate', $endDate);
    $stmt->execute();
    $sucursal_summaries = $stmt->fetchAll();

    $ranking_summaries = [];
    $grand_total_unidades = 0;
    $grand_total_monto_ventas = 0.0;
    $grand_total_cogs = 0.0; // Solo para admin

    foreach ($sucursal_summaries as $summary) {
        $total_unidades = (int)($summary['total_unidades_sucursal'] ?? 0);
        $total_monto_venta = (float)($summary['monto_total_venta_sucursal'] ?? 0.0);
        
        $data_item = [
            'sucursal'           => $summary['sucursal'],
            'total_unidades'     => $total_unidades,
            'total_monto_venta'  => $total_monto_venta,
        ];

        if ($es_administrador) {
            $total_cogs = (float)($summary['total_cogs_sucursal'] ?? 0.0);
            $margen_bruto_monto = $total_monto_venta - $total_cogs;
            $margen_bruto_porcentaje = ($total_monto_venta > 0) ? ($margen_bruto_monto / $total_monto_venta) * 100 : 0.0;

            $data_item['total_cogs_sucursal']              = $total_cogs;
            $data_item['margen_bruto_sucursal_monto']      = $margen_bruto_monto;
            $data_item['margen_bruto_sucursal_porcentaje'] = $margen_bruto_porcentaje;
            
            $grand_total_cogs += $total_cogs;
        }
        
        $ranking_summaries[] = $data_item;

        $grand_total_unidades += $total_unidades;
        $grand_total_monto_ventas += $total_monto_venta;
    }

    $response_data = [
        'startDate'                   => $startDate,
        'endDate'                     => $endDate,
        'data'                        => $ranking_summaries,
        'grand_total_unidades'        => $grand_total_unidades,
        'grand_total_monto_ventas'    => $grand_total_monto_ventas,
        'vendedor_estrella_nombre'    => $vendedorEstrellaNombre,
        'vendedor_estrella_unidades'  => $vendedorEstrellaUnidades,
        'vendedor_oro_nombre'         => $vendedorOroNombre,
        'vendedor_oro_monto'          => $vendedorOroMonto,
        'user_is_admin'               => $es_administrador
    ];

    if ($es_administrador) {
        $grand_total_margen_bruto_monto = $grand_total_monto_ventas - $grand_total_cogs;
        $grand_total_margen_bruto_porcentaje = ($grand_total_monto_ventas > 0) ? ($grand_total_margen_bruto_monto / $grand_total_monto_ventas) * 100 : 0.0;
        
        $response_data['grand_total_cogs'] = $grand_total_cogs;
        $response_data['grand_total_margen_bruto_monto'] = $grand_total_margen_bruto_monto;
        $response_data['grand_total_margen_bruto_porcentaje'] = $grand_total_margen_bruto_porcentaje;
    }

    echo json_encode($response_data);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de base de datos (ranking_all.php): " . $e->getMessage());
    echo json_encode(['error' => 'Error de conexión o consulta a la base de datos. Por favor, intente más tarde.']);
    exit;
} catch (Exception $e) {
    http_response_code(500); // O 400 si es un error de input del usuario
    error_log("Error general (ranking_all.php): " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>