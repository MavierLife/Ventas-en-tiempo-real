<?php
// Config/filters.php

if (!function_exists('get_product_filter_sql')) {
    /**
     * Devuelve la cadena SQL para el filtro de productos, basada en la preferencia del usuario en sesión.
     *
     * @param string $product_table_alias El alias de la tabla de productos (ej. 'p') en la consulta SQL.
     * @return string La condición SQL para el filtro (ej. "AND p.Descripcion LIKE 'VALOR%'").
     * Devuelve una cadena vacía si no hay preferencia de filtro o está vacía.
     */
    function get_product_filter_sql(string $product_table_alias = 'p'): string
    {
        // Definir la ruta al archivo de log en la raíz del proyecto
        // __DIR__ es el directorio actual (Config), '/../' sube un nivel a la raíz.
        $log_file_path = __DIR__ . '/../debug_filter.log';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user_code_for_log = $_SESSION['codigo_emp'] ?? 'Invitado/NoLogueado';
        $timestamp = date('[Y-m-d H:i:s]'); // Añadir timestamp al log

        if (isset($_SESSION['product_filter_preference']) && !empty(trim($_SESSION['product_filter_preference']))) {
            $user_filter_preference = trim($_SESSION['product_filter_preference']);
            $escaped_preference = str_replace("'", "''", $user_filter_preference);
            $filter_sql = "AND {$product_table_alias}.Descripcion LIKE '{$escaped_preference}'";
            
            $log_message = sprintf(
                "%s [DEBUG HelenSystem Filter] Usuario: %s | Preferencia Original Sesión: '%s' | Preferencia Usada (trim): '%s' | Fragmento SQL Generado: \"%s\"\n",
                $timestamp,
                $user_code_for_log,
                $_SESSION['product_filter_preference'],
                $user_filter_preference,
                $filter_sql
            );
            // Escribir al archivo de log personalizado (tipo 3 para error_log)
            error_log($log_message, 3, $log_file_path);
            
            return $filter_sql;
        }

        $log_message = sprintf(
            "%s [DEBUG HelenSystem Filter] Usuario: %s | No se encontró 'product_filter_preference' en sesión o estaba vacía después de trim(). No se aplica filtro de producto.\n",
            $timestamp,
            $user_code_for_log
        );
        error_log($log_message, 3, $log_file_path);

        return ""; 
    }
}
?>