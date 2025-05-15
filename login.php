<?php
session_start(); // Iniciar sesión al principio del script

// Información de la base de datos
$db_host    = '127.0.0.1';
$db_name    = 'helensystem_data';
$db_user    = 'access_permit';
$db_pass    = '3VTnUWWQaIp!YgHB';
$db_charset = 'utf8mb4';

$login_error = ''; // Variable para almacenar mensajes de error

// Verificar si el usuario ya está logueado y redirigir si es así
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit;
}

// Ruta al archivo de permisos
define('PERMISSIONS_FILE_PATH', __DIR__ . '/Permisos/permisos.txt');

// Función modificada: verifica permisos y lee hasta 3 campos (CódigoEMP, Rol, PreferenciaFiltroProducto)
function verificar_permisos_usuario($codigo_emp) {
    if (!file_exists(PERMISSIONS_FILE_PATH) || !is_readable(PERMISSIONS_FILE_PATH)) {
        error_log("Error: El archivo de permisos no existe o no se puede leer en: " . PERMISSIONS_FILE_PATH);
        return ['permitido' => false, 'error' => 'Error interno del servidor al verificar permisos.'];
    }

    $handle = fopen(PERMISSIONS_FILE_PATH, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue; // Ignorar líneas vacías o comentarios
            }
            // Separar en hasta tres partes: CódigoEMP, Rol, PreferenciaFiltroProducto
            $parts = explode(',', $line, 3);
            if (count($parts) >= 2) {
                $file_codigo_emp    = trim($parts[0]);
                $rol                = trim($parts[1]);
                $preferencia_filtro = (isset($parts[2]) && trim($parts[2]) !== '')
                    ? trim($parts[2])
                    : "QUALITY%"; // Valor por defecto

                if ($file_codigo_emp === $codigo_emp) {
                    fclose($handle);
                    // Incluir ahora el rol "Vendedor" como válido
                    if (in_array($rol, ['Administrador', 'Supervisor', 'Vendedor'], true)) {
                        return [
                            'permitido'          => true,
                            'rol'                => $rol,
                            'preferencia_filtro' => $preferencia_filtro
                        ];
                    } else {
                        return ['permitido' => false, 'error' => 'Rol no válido o sin permisos suficientes asignados.'];
                    }
                }
            }
        }
        fclose($handle);
    } else {
        error_log("Error: No se pudo abrir el archivo de permisos en: " . PERMISSIONS_FILE_PATH);
        return ['permitido' => false, 'error' => 'Error interno del servidor al leer permisos.'];
    }

    return ['permitido' => false, 'error' => 'No tiene los permisos necesarios para acceder al sistema.'];
}

// Procesamiento del formulario de login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST['codigo_emp'])) || empty(trim($_POST['clave_acceso']))) {
        $login_error = "El código de empleado y la contraseña son obligatorios.";
    } else {
        $codigo_emp_ingresado = trim($_POST['codigo_emp']);
        $clave_ingresada      = trim($_POST['clave_acceso']);

        $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"
        ];

        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
            $stmt = $pdo->prepare(
                "SELECT CodigoEMP, Nombres, Apellidos, ClaveAcceso, Estado
                 FROM tblregistrodeempleados
                 WHERE CodigoEMP = :codigo_emp"
            );
            $stmt->bindParam(':codigo_emp', $codigo_emp_ingresado, PDO::PARAM_STR);
            $stmt->execute();
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($empleado) {
                if ((int)$empleado['Estado'] !== 1) {
                    $login_error = "Este usuario está inactivo. Por favor, contacte al administrador.";
                } elseif (password_verify($clave_ingresada, $empleado['ClaveAcceso'])) {
                    // Verificar permisos y obtener preferencia de filtro
                    $permisos_check = verificar_permisos_usuario($empleado['CodigoEMP']);

                    if ($permisos_check['permitido']) {
                        // Guardar datos en sesión, incluyendo la preferencia de filtro
                        $_SESSION['loggedin']                  = true;
                        $_SESSION['codigo_emp']                = $empleado['CodigoEMP'];
                        $_SESSION['nombres_usuario']           = $empleado['Nombres'] . ' ' . $empleado['Apellidos'];
                        $_SESSION['rol_usuario']               = $permisos_check['rol'];
                        $_SESSION['product_filter_preference'] = $permisos_check['preferencia_filtro'];
                        session_regenerate_id(true);
                        header("Location: index.php");
                        exit;
                    } else {
                        $login_error = $permisos_check['error'];
                    }
                } else {
                    $login_error = "Código de empleado o contraseña incorrectos.";
                }
            } else {
                $login_error = "Código de empleado o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            error_log("Error de BD en login: " . $e->getMessage());
            $login_error = "Ocurrió un error en el servidor. Intente más tarde.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <style>
        /* CSS Proporcionado por el usuario */
        body { /* Añadido para centrar el formulario en la página */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #eef1f4; /* Un color de fondo suave */
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        .form {
          display: flex;
          flex-direction: column;
          gap: 10px;
          background-color: #ffffff;
          padding: 30px;
          width: 400px; /* Ajustado para que no sea demasiado ancho */
          max-width: 90%; /* Para responsividad en pantallas pequeñas */
          border-radius: 20px;
          box-shadow: 0 4px 12px rgba(0,0,0,0.1); /* Sombra sutil */
        }

        ::placeholder {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        .form button.button-submit { /* Especificidad para el botón de submit */
          align-self:initial; /* Para que ocupe todo el ancho disponible */
        }

        .flex-column > label {
          color: #151717;
          font-weight: 600;
          margin-bottom: 5px; /* Espacio debajo de la etiqueta */
        }

        .inputForm {
          border: 1.5px solid #ecedec;
          border-radius: 10px;
          height: 50px;
          display: flex;
          align-items: center;
          padding-left: 10px;
          transition: 0.2s ease-in-out;
          background-color: #fff; /* Asegurar fondo blanco */
        }

        .inputForm svg { /* Estilo para el SVG dentro de inputForm */
            fill: #9a9a9a; /* Color del icono SVG */
            margin-right: 5px; /* Espacio entre icono y input */
        }

        .input {
          margin-left: 10px;
          border-radius: 10px;
          border: none;
          width: 100%;
          height: 100%;
          background-color: transparent; /* Input transparente */
        }

        .input:focus {
          outline: none;
        }

        .inputForm:focus-within {
          border: 1.5px solid #2d79f3;
        }

        .flex-row {
          display: flex;
          flex-direction: row;
          align-items: center;
          gap: 10px;
          justify-content: space-between;
          margin-top: 10px; /* Espacio antes de "Remember me" */
        }

        .flex-row > div > label {
          font-size: 14px;
          color: black;
          font-weight: 400;
        }

        .span { /* Para enlaces como "Forgot password?" y "Sign Up" */
          font-size: 14px;
          margin-left: 5px;
          color: #2d79f3;
          font-weight: 500;
          cursor: pointer;
        }
        .span:hover {
            text-decoration: underline;
        }

        .button-submit {
          margin: 20px 0 10px 0;
          background-color: #151717;
          border: none;
          color: white;
          font-size: 15px;
          font-weight: 500;
          border-radius: 10px;
          height: 50px;
          width: 100%;
          cursor: pointer;
          transition: background-color 0.2s ease-in-out;
        }
        .button-submit:hover {
            background-color: #2d79f3;
        }

        .p {
          text-align: center;
          color: black;
          font-size: 14px;
          margin: 15px 0; /* Aumentado margen */
        }

        .p.line {
            margin-top: 20px;
            margin-bottom: 15px;
            position: relative;
            text-align: center;
        }
        .p.line::before,
        .p.line::after {
            content: "";
            position: absolute;
            top: 50%;
            width: calc(50% - 30px); /* Ajustar ancho de las líneas */
            height: 1px;
            background-color: #ecedec;
        }
        .p.line::before {
            left: 0;
        }
        .p.line::after {
            right: 0;
        }

        .btn { /* Para botones de Google y Apple */
          margin-top: 10px;
          width: 100%;
          height: 50px;
          border-radius: 10px;
          display: flex;
          justify-content: center;
          align-items: center;
          font-weight: 500;
          gap: 10px;
          border: 1px solid #ededef;
          background-color: white;
          cursor: pointer;
          transition: 0.2s ease-in-out;
        }
        .btn svg {
            margin-right: 5px;
        }

        .btn:hover {
          border: 1px solid #2d79f3;
        }

        /* Estilo para mensajes de error */
        .login-error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <form class="form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
        <p style="font-size: 1.5em; font-weight: bold; text-align: center; margin-bottom: 20px; color: #333;">
            Inicio de Sesión
        </p>
        <?php if (!empty($login_error)): ?>
            <div class="login-error-message">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>

        <div class="flex-column">
          <label>Código de Empleado</label>
        </div>
        <div class="inputForm">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" viewBox="0 0 24 24" height="20" fill="currentColor" class="bi bi-person-badge">
            <path d="M6.5 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3zM11 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
            <path d="M4.5 0A2.5 2.5 0 0 0 2 2.5V14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2.5A2.5 2.5 0 0 0 11.5 0h-7zM3 2.5A1.5 1.5 0 0 1 4.5 1h7A1.5 1.5 0 0 1 13 2.5v10.795a4.2 4.2 0 0 0-.776-.492C11.392 12.387 10.063 12 8 12s-3.392.387-4.224.803a4.2 4.2 0 0 0-.776.492V2.5z"/>
          </svg>
          <input placeholder="Ingrese su Código de Empleado" class="input" type="text" name="codigo_emp" id="codigo_emp" value="<?php echo isset($_POST['codigo_emp']) ? htmlspecialchars($_POST['codigo_emp']) : ''; ?>" required>
        </div>

        <div class="flex-column">
          <label>Contraseña</label>
        </div>
        <div class="inputForm">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" viewBox="-64 0 512 512" height="20" fill="currentColor">
            <path d="m336 512h-288c-26.453125 0-48-21.523438-48-48v-224c0-26.476562 21.546875-48 48-48h288c26.453125 0 48 21.523438 48 48v224c0 26.476562-21.546875 48-48 48zm-288-288c-8.8125 0-16 7.167969-16 16v224c0 8.832031 7.1875 16 16 16h288c8.8125 0 16-7.167969 16-16v-224c0-8.832031-7.1875-16-16-16zm0 0"/>
            <path d="m304 224c-8.832031 0-16-7.167969-16-16v-80c0-52.929688-43.070312-96-96-96s-96 43.070312-96 96v80c0 8.832031-7.167969 16-16 16s-16-7.167969-16-16v-80c0-70.59375 57.40625-128 128-128s128 57.40625 128 128v80c0 8.832031-7.167969 16-16 16zm0 0"/>
          </svg>
          <input placeholder="Ingrese su Contraseña" class="input" type="password" name="clave_acceso" id="clave_acceso" required>
        </div>

        <button class="button-submit" type="submit">Ingresar</button>
    </form>
</body>
</html>
