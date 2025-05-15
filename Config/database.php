<?php
// Config/database.php

if (!function_exists('getPDOConnection')) {
    /**
     * Establece y devuelve una conexión PDO a la base de datos.
     *
     * @return PDO La instancia de PDO para la conexión.
     * @throws PDOException Si la conexión falla.
     */
    function getPDOConnection(): PDO
    {
        $db_host = '127.0.0.1';
        $db_name = 'helensystem_data';
        $db_user = 'access_permit';
        $db_pass = '3VTnUWWQaIp!YgHB'; // ¡Considera usar variables de entorno para las credenciales!
        $db_charset = 'utf8mb4';

        $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Esencial para un buen manejo de errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Usa preparaciones reales del servidor
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'" // Asegura UTF8MB4 en la conexión
        ];

        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
            return $pdo;
        } catch (PDOException $e) {
            // En un entorno de producción, loguear este error en lugar de mostrarlo directamente.
            // Por ahora, para facilitar la depuración si algo sale mal al configurar:
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            // Podrías lanzar una excepción más genérica o manejarla como prefieras.
            // Para las APIs que devuelven JSON, es mejor capturar esto en la API y devolver un error JSON.
            throw $e; // Relanzar la excepción para que el script que llama pueda manejarla.
        }
    }
}
?>