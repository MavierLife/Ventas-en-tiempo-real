<?php
// logout.php
session_start(); // Iniciar o reanudar la sesión existente. Es importante para poder modificarla.

// 1. Destruir todas las variables de sesión.
// Esto elimina todos los datos almacenados en la sesión actual.
$_SESSION = array();

// 2. Borrar la cookie de sesión (opcional pero recomendado para una limpieza completa).
// Si la sesión utiliza cookies (lo cual es lo predeterminado en PHP),
// es una buena práctica eliminar también la cookie del navegador del cliente.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params(); // Obtener los parámetros de la cookie de sesión
    setcookie(
        session_name(),     // Nombre de la cookie de sesión (usualmente 'PHPSESSID')
        '',                 // Valor vacío para la cookie
        time() - 42000,     // Tiempo de expiración en el pasado (para eliminarla inmediatamente)
        $params["path"],     // Ruta de la cookie
        $params["domain"],   // Dominio de la cookie
        $params["secure"],   // Si la cookie solo debe enviarse por HTTPS
        $params["httponly"]  // Si la cookie no debe ser accesible por JavaScript
    );
}

// 3. Finalmente, destruir la sesión en el servidor.
// Esto invalida el ID de sesión actual.
session_destroy();

// 4. Redirigir al usuario a la página de login.
header("Location: login.php");
exit; // Detener la ejecución del script para asegurar que la redirección ocurra.
?>