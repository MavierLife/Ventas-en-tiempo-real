<?php
// Api/get_desempeno_productos.php
session_start();

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/filters.php'; // Asumiendo que este archivo contiene get_product_filter_sql()

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // Cambiar a 1 para depuración, 0 para producción
// error_reporting(E_ALL); // Para depuración
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/php-error-desempeno-productos.log');


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_GET['fecha_fin'] ?? null;
$orden_parametro_get = $_GET['orden'] ?? 'monto_vendido'; // Default para la API

if (!$fecha_inicio || !$fecha_fin ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
    http_response_code(400);
    echo json_encode(['error' => 'Fechas no válidas. Formato esperado YYYY-MM-DD.']);
    exit;
}

$es_administrador = (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'Administrador');

try {
    $pdo = getPDOConnection();
    // Asumimos que 'p' será el alias para tblcatalogodeproductos en la consulta principal
    $product_filter_sql = get_product_filter_sql('p');

    $sql_select_utilidad_fields = "";
    $sql_group_by_utilidad_fields = "";

    if ($es_administrador) {
        // Estos campos son necesarios para calcular la utilidad si se hace en SQL
        // o para tener los componentes para calcularla en PHP.
        // PrecioCosto se asume que es el costo del fardo/paquete.
        // Unidades es la cantidad de unidades individuales en ese fardo/paquete.
        $sql_select_utilidad_fields = ",
            p.PrecioCosto AS costo_fardo_producto,
            p.Unidades AS unidades_por_fardo_producto
        ";
        // Necesitamos agrupar por estos campos si se seleccionan y no son parte de una agregación
        $sql_group_by_utilidad_fields = ", p.PrecioCosto, p.Unidades";
    }

    // Determinar la columna por la cual ordenar en la consulta SQL
    $order_by_column_sql = "total_monto_vendido DESC"; // Default order

    if ($orden_parametro_get === 'cantidad') {
        $order_by_column_sql = "total_unidades_vendidas DESC";
    } elseif ($orden_parametro_get === 'producto') {
        $order_by_column_sql = "producto_descripcion ASC";
    } elseif ($orden_parametro_get === 'porcentaje_utilidad' && $es_administrador) {
        // Para ordenar por porcentaje de utilidad en SQL, necesitaríamos calcularlo directamente en la consulta
        // o ordenar por la utilidad bruta si el denominador (monto_vendido) es consistente.
        // Ejemplo ordenando por utilidad bruta (MontoVendido - CostoTotal):
        $order_by_column_sql = "(SUM(d.Cantidad * d.PrecioVenta) - SUM( (CASE WHEN d.TV = 2 THEN d.Cantidad * p.Unidades ELSE d.Cantidad END) * (p.PrecioCosto / (CASE WHEN p.Unidades > 0 THEN p.Unidades ELSE 1 END)) )) DESC";
        // Este cálculo puede hacer la consulta más compleja.
        // Alternativamente, se puede ordenar por monto_vendido y luego el cliente (o PHP aquí) puede reordenar si es necesario.
        // Por simplicidad, si se pide 'porcentaje_utilidad', podríamos seguir ordenando por 'total_monto_vendido' en SQL
        // y dejar que el cliente haga el ordenamiento final por el porcentaje calculado.
        // O, si la API DEBE devolverlo ordenado por utilidad %:
        // $order_by_column_sql = "((SUM(d.Cantidad * d.PrecioVenta) - SUM( (CASE WHEN d.TV = 2 THEN d.Cantidad * p.Unidades ELSE d.Cantidad END) * (p.PrecioCosto / (CASE WHEN p.Unidades > 0 THEN p.Unidades ELSE 1 END)) )) / SUM(d.Cantidad * d.PrecioVenta)) DESC";
        // (Asegurándose de manejar división por cero si SUM(d.Cantidad * d.PrecioVenta) es 0)

        // Para este ejemplo, si se pide utilidad, ordenaremos por la utilidad bruta descendente en SQL.
    }
    // Si $orden_parametro_get es 'monto_vendido', ya está cubierto por el default.


    $sql = "
        SELECT
            p.CodigoPROD AS producto_codigo, /* Agregado para tener una clave única si Descripcion no lo es */
            p.Descripcion AS producto_descripcion,
            SUM(
                CASE
                    WHEN d.TV = 2 THEN d.Cantidad * p.Unidades /* Venta por fardo/caja */
                    ELSE d.Cantidad /* Venta por unidad */
                END
            ) AS total_unidades_vendidas,
            SUM(d.Cantidad * d.PrecioVenta) AS total_monto_vendido
            {$sql_select_utilidad_fields}
        FROM tblnotasdeentregadetalle d
        INNER JOIN tblnotasdeentrega e ON d.UUIDVenta = e.UUIDVenta
        INNER JOIN tblcatalogodeproductos p ON d.CodigoPROD = p.CodigoPROD
        WHERE DATE(e.FechaHoraGeneracion) BETWEEN :fecha_inicio AND :fecha_fin
          /* AND d.UsuarioRegistro IS NOT NULL AND d.UsuarioRegistro != '' -- No relevante para productos */
          {$product_filter_sql} /* Aplicar filtros de producto si existen */
        GROUP BY p.CodigoPROD, p.Descripcion {$sql_group_by_utilidad_fields}
        ORDER BY {$order_by_column_sql}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $productos_data = [];
    foreach ($resultados as $row) {
        $monto_vendido_actual = (float)$row['total_monto_vendido'];
        $unidades_vendidas_actual = (int)$row['total_unidades_vendidas'];
        $porcentaje_utilidad_actual = null; // Default a null

        if ($es_administrador && isset($row['costo_fardo_producto']) && isset($row['unidades_por_fardo_producto'])) {
            $costo_fardo = (float)$row['costo_fardo_producto'];
            $unidades_por_fardo = (int)$row['unidades_por_fardo_producto'];

            if ($unidades_por_fardo > 0) {
                $costo_unitario_producto = $costo_fardo / $unidades_por_fardo;
            } else {
                $costo_unitario_producto = $costo_fardo; // Si unidades_por_fardo es 0 o no aplica, costo_fardo es el costo unitario.
            }
            
            $cogs_total_producto = $unidades_vendidas_actual * $costo_unitario_producto;
            $utilidad_bruta_monto = $monto_vendido_actual - $cogs_total_producto;

            if ($monto_vendido_actual > 0) {
                $porcentaje_utilidad_actual = ($utilidad_bruta_monto / $monto_vendido_actual) * 100;
            } else {
                $porcentaje_utilidad_actual = 0; // O null si se prefiere para montos de venta cero
            }
        }

        $productos_data[] = [
            // 'codigo' => $row['producto_codigo'], // Opcional, si lo necesitas en el frontend
            'producto' => $row['producto_descripcion'],
            'cantidad' => $unidades_vendidas_actual,
            'monto_vendido' => $monto_vendido_actual,
            'porcentaje_utilidad' => $porcentaje_utilidad_actual // Será null si no es admin o faltan datos de costo
        ];
    }
    
    // Si el ordenamiento de la API fue por algo como utilidad bruta,
    // y el cliente espera específicamente ordenado por el porcentaje calculado en PHP,
    // podrías re-ordenar aquí $productos_data antes de enviarlo si $orden_parametro_get fue 'porcentaje_utilidad'.
    if ($es_administrador && $orden_parametro_get === 'porcentaje_utilidad') {
        usort($productos_data, function($a, $b) {
            $utilA = $a['porcentaje_utilidad'] ?? -INF; // Tratar nulls como la menor utilidad
            $utilB = $b['porcentaje_utilidad'] ?? -INF;
            if ($utilA == $utilB) {
                return 0;
            }
            return ($utilA > $utilB) ? -1 : 1; // Descendente
        });
    }


    echo json_encode([
        'data' => $productos_data,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'es_administrador' => $es_administrador,
        'orden_aplicado_api' => $orden_parametro_get // Para depuración
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de BD en get_desempeno_productos.php: " . $e->getMessage() . " SQL: " . (isset($sql) ? $sql : "No SQL"));
    echo json_encode(['error' => 'Error de base de datos al procesar la solicitud de desempeño de productos.']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error general en get_desempeno_productos.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error procesando la solicitud de desempeño de productos: ' . $e->getMessage()]);
    exit;
}
?>