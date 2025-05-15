<?php
session_start(); // Iniciar sesión al principio del script

// --- INICIO: GESTOR DE VISTAS Y ACCIONES AJAX ---
if (isset($_GET['ajax_get_view']) || isset($_GET['ajax_action'])) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        http_response_code(403); // Forbidden
        echo json_encode(['error' => 'Acceso no autorizado. Debes iniciar sesión.']); // Devolver JSON para AJAX
        exit;
    }

    // Variables de sesión para que estén disponibles
    $rol_usuario = $_SESSION['rol_usuario'] ?? 'Invitado';
    $es_administrador = ($rol_usuario === 'Administrador');
    $nombres_usuario_sesion = $_SESSION['nombres_usuario'] ?? 'Usuario';
    $vista_solicitada = ''; // Inicializar para evitar notice si solo es ajax_action

    if (isset($_GET['ajax_get_view']) && !empty($_GET['vista'])) {
        $vista_solicitada = basename($_GET['vista']);
    }


    // --- MANEJO DE ACCIONES AJAX ESPECÍFICAS ---
    if (isset($_GET['ajax_action'])) {
        $accion_solicitada = $_GET['ajax_action'];
        
        // Solo administradores pueden realizar acciones de configuración de permisos
        if (!$es_administrador && in_array($accion_solicitada, ['get_permisos_data', 'save_permisos_data'])) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permisos para realizar esta acción.']);
            exit;
        }

        $permisos_file_path = __DIR__ . '/Permisos/permisos.txt';

        if ($accion_solicitada === 'get_permisos_data') {
            header('Content-Type: application/json; charset=utf-8');
            if (file_exists($permisos_file_path) && is_readable($permisos_file_path)) {
                $permisos_array = [];
                $handle = fopen($permisos_file_path, "r");
                if ($handle) {
                    $id_counter = 0; // Para dar un ID único a cada fila para el frontend
                    while (($line = fgets($handle)) !== false) {
                        $line = trim($line);
                        if (empty($line) || strpos($line, '#') === 0) {
                            continue; // Ignorar líneas vacías o comentarios
                        }
                        $parts = explode(',', $line, 3);
                        $permisos_array[] = [
                            'id' => $id_counter++, // ID temporal para la gestión en frontend
                            'codigo_emp' => trim($parts[0] ?? ''),
                            'rol' => trim($parts[1] ?? ''),
                            'filtro' => trim($parts[2] ?? '')
                        ];
                    }
                    fclose($handle);
                    echo json_encode(['success' => true, 'data' => $permisos_array]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'No se pudo abrir el archivo de permisos.']);
                }
            } else {
                // Si el archivo no existe, devolver un array vacío en lugar de error,
                // para que la interfaz pueda manejar la creación desde cero.
                // Opcionalmente, podrías crear un archivo vacío aquí si lo prefieres.
                error_log("Advertencia: El archivo de permisos no existe en {$permisos_file_path}. Se devolverá una lista vacía.");
                echo json_encode(['success' => true, 'data' => []]);
            }
            exit;
        }

        if ($accion_solicitada === 'save_permisos_data') {
            header('Content-Type: application/json; charset=utf-8');
            if (!isset($_POST['permisos_data'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No se recibieron datos de permisos.']);
                exit;
            }

            $permisos_actualizados = json_decode($_POST['permisos_data'], true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($permisos_actualizados)) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos de permisos mal formados.']);
                exit;
            }
            
            $lineas_para_guardar = [];
            foreach ($permisos_actualizados as $permiso) {
                if (empty(trim($permiso['codigo_emp'])) || empty(trim($permiso['rol']))) {
                    continue; 
                }
                $codigo_emp = trim($permiso['codigo_emp']);
                $rol_p = trim($permiso['rol']);
                $filtro_p = isset($permiso['filtro']) ? trim($permiso['filtro']) : ''; 
                
                $lineas_para_guardar[] = "{$codigo_emp},{$rol_p},{$filtro_p}";
            }
            
            // Crear la carpeta Permisos si no existe
            if (!is_dir(__DIR__ . '/Permisos')) {
                if (!mkdir(__DIR__ . '/Permisos', 0755, true)) {
                    http_response_code(500);
                    echo json_encode(['error' => 'No se pudo crear la carpeta de Permisos. Verifique los permisos.']);
                    exit;
                }
            }
            
            if (file_put_contents($permisos_file_path, implode("\n", $lineas_para_guardar) . (empty($lineas_para_guardar) ? "" : "\n")) !== false) {
                echo json_encode(['success' => true, 'message' => 'Archivo de permisos guardado correctamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo escribir en el archivo de permisos. Verifique los permisos del archivo y la carpeta.']);
            }
            exit;
        }
        
        // --- Manejo de acción get_permisos_content_raw (para la <pre> original, ahora menos relevante) ---
        // Asegurarse que $vista_solicitada esté definida si se llega aquí por GET con action pero sin vista
        // Esto es más por robustez, ya que esta action específica se llama desde configuracion.php
        if (empty($vista_solicitada) && isset($_GET['vista_ref'])) { 
            $vista_solicitada = basename($_GET['vista_ref']); // Si la acción lo necesita
        }

        if ($vista_solicitada === 'configuracion' && isset($_GET['action']) && $_GET['action'] === 'get_permisos_content_raw') {
            if ($es_administrador) { // Esta variable $es_administrador se define al inicio del bloque AJAX
                $permisos_file = __DIR__ . '/Permisos/permisos.txt';
                if (file_exists($permisos_file) && is_readable($permisos_file)) {
                    header('Content-Type: text/plain; charset=utf-8');
                    readfile($permisos_file);
                } else {
                    http_response_code(500);
                    echo "Error: No se pudo leer el archivo de permisos.";
                }
            } else {
                http_response_code(403);
                echo "Acceso denegado a esta acción.";
            }
            exit; 
        }

    } // --- FIN MANEJO DE ACCIONES AJAX ESPECÍFICAS ---


    // --- GESTOR DE VISTAS AJAX (existente) ---
    if (isset($_GET['ajax_get_view']) && !empty($_GET['vista'])) {
        // $vista_solicitada ya está definida y sanitizada
        $archivo_vista = $vista_solicitada . '.php';
        $vistas_permitidas = ['dashboard', 'configuracion', 'estadisticas']; 

        if (in_array($vista_solicitada, $vistas_permitidas) && file_exists($archivo_vista)) {
            if ($vista_solicitada === 'configuracion' && !$es_administrador) {
                http_response_code(403);
                echo "<section class='content-header'><div class='container-fluid'><div class='row mb-2'><div class='col-sm-6'><h1>Acceso Denegado</h1></div></div></div></section>";
                echo "<section class='content'><div class='container-fluid'><p>No tienes permisos para acceder a esta sección.</p></div></section>";
                exit;
            }
            include $archivo_vista;
        } else {
            http_response_code(404);
            echo "<p style='padding:20px; text-align:center;'>Vista no encontrada: " . htmlspecialchars($vista_solicitada) . "</p>";
        }
        exit; 
    }
    // --- FIN GESTOR DE VISTAS AJAX ---

    // Si es una solicitud AJAX pero no coincide con ninguna acción o vista conocida.
    if(isset($_GET['ajax_action']) || isset($_GET['ajax_get_view'])){
        http_response_code(400);
        echo json_encode(['error' => 'Solicitud AJAX no válida o acción no encontrada.']);
        exit;
    }
}
// --- FIN: GESTOR DE VISTAS Y ACCIONES AJAX ---


// Verificar si el usuario NO está logueado (para la carga inicial de la página completa)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); 
    exit; 
}

$rol_usuario = $_SESSION['rol_usuario'] ?? 'Invitado';
$es_administrador = ($rol_usuario === 'Administrador');
$nombres_usuario_sesion = $_SESSION['nombres_usuario'] ?? 'Usuario';

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>HelenSystem - <?php echo ucfirst(htmlspecialchars($_GET['vista'] ?? 'Dashboard')); ?></title>
  <link rel="shortcut icon" href="favicon.ico">
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css" />
  <link rel="stylesheet" href="Css/datatable.css">
  <link rel="stylesheet" href="Css/layout.css">
  <link rel="stylesheet" href="Css/animation.css">
  
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    /* Estilos específicos de la página (copiados de tu index original y algunos añadidos) */
    .info-box-row > [class*="col-"] { display: flex; align-items: stretch; margin-bottom: 1rem; }
    .info-box { min-height: 100px; display: flex; width: 100%; border: 1px solid #dee2e6; box-shadow: 0 0 1px rgba(0,0,0,.125),0 1px 3px rgba(0,0,0,.2); background-color: #fff; border-radius: .25rem; }
    .info-box-icon { width: 70px; min-height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.875rem; border-top-left-radius: .25rem; border-bottom-left-radius: .25rem; }
    .info-box-content { padding: .5rem 1rem; margin-left: 0; flex-grow: 1; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
    .info-box-text, .info-box-number { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
    .info-box-text { font-size: 0.875rem; color: #6c757d; text-transform: uppercase; }
    .info-box-number { font-size: 1.25rem; font-weight: 700; color: #212529; }
    .info-box-vendedor-oro { box-shadow: 0 0 10px 3px rgba(255, 215, 0, 0.30); }
    .info-box-vendedor-estrella { box-shadow: 0 0 10px 3px rgba(192, 192, 192, 0.35); }
    .vendedor-detalle-subtext { font-size: 0.8em; font-weight: normal; color: #6c757d; display: block; }
    .medal-icon { margin-left: 8px; font-size: 1.1em; vertical-align: middle; }
    .gold-medal { color: #FFD700; text-shadow: 0 0 2px #b49b3b; }
    .silver-medal { color: #C0C0C0; text-shadow: 0 0 2px #8d8d8d; }
    .bronze-medal { color: #CD7F32; text-shadow: 0 0 2px #82501b; }
    #globalLoadingOverlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.85); z-index: 1050; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
    #globalLoadingStatus { font-size: 1.3em; font-weight: bold; color: #333; margin-top: 20px; margin-bottom: 5px; }
    #globalLoadingSubStatus { font-size: 1em; color: #555; min-height: 1.2em; }
    .branch-cell i.fa-chevron-down { transition: transform 0.2s ease-in-out; }
    .branch-cell i.fa-chevron-down.rotated { transform: rotate(180deg); }
    .info-box-icon.bg-warning i { color: #ffffff !important; }

    #dynamic-content-container .loading-placeholder {
        display: flex;
        justify-content: center;
        align-items: center;
        height: calc(100vh - 150px); /* Ajusta según sea necesario (header + footer height) */
        font-size: 1.5em;
        color: #777;
    }
    #dynamic-content-container .loading-placeholder i {
        margin-right: 10px;
    }
    /* Estilos para la tabla de permisos en configuracion.php */
    #tablaPermisos th, #tablaPermisos td { vertical-align: middle; }
    #tablaPermisos input[type="text"], #tablaPermisos select { width: 100%; } /* Aplicar a select también si se usa */
    .acciones-permiso button { margin-right: 5px; }
    /* Para el botón de cerrar en los alerts de Bootstrap 5 */
    .alert .btn-close {
      font-size: 0.75rem; /* Ajustar tamaño si es necesario */
      padding: 0.5rem;   /* Ajustar padding si es necesario */
    }
  </style>
</head>
<body class="hold-transition sidebar-mini sidebar-collapse" data-sidebar-no-expand="true">
  <div class="wrapper">

    <nav class="main-header navbar navbar-expand">
      <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto align-items-center">
          <li class="nav-item">
              <span class="nav-link user-name">
                <?php echo htmlspecialchars($nombres_usuario_sesion); ?>
                (<?php echo htmlspecialchars($rol_usuario); ?>)
              </span>
          </li>
          <li class="nav-item">
              <a class="nav-link" href="logout.php" role="button" title="Cerrar Sesión">
                  <i class="fas fa-sign-out-alt"></i>
              </a>
          </li>
      </ul>
    </nav>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="index.php?vista=dashboard" class="brand-link nav-link" data-vista="dashboard">
        <img src="Img/logo.png" alt="Logo HelenSystem" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">HelenSystem</span>
      </a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul id="main-nav" class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <li class="nav-item">
                <a href="index.php?vista=dashboard" class="nav-link" data-vista="dashboard">
                    <i class="nav-icon fas fa-home"></i>
                    <p>Inicio</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?vista=estadisticas" class="nav-link" data-vista="estadisticas">
                    <i class="nav-icon fas fa-chart-line"></i>
                    <p>Estadísticas</p>
                </a>
            </li>
            <?php if ($es_administrador): ?>
            <li class="nav-item">
                <a href="index.php?vista=configuracion" class="nav-link" data-vista="configuracion">
                    <i class="nav-icon fas fa-cog"></i>
                    <p>Configuración</p>
                </a>
            </li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
    </aside>

    <div class="content-wrapper">
      <div id="globalLoadingOverlay">
          <div class="loader"></div>
          <p id="globalLoadingStatus">Procesando Solicitud...</p> <p id="globalLoadingSubStatus"></p>
      </div>

      <div id="dynamic-content-container">
          <div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i>Cargando vista inicial...</div>
      </div>
    </div>

    <footer class="main-footer">
      <strong>Copyright &copy; 2012-<?php echo date("Y"); ?> <a href="#">HelenSystem</a>.</strong>
      Todos los derechos reservados.
      <div class="float-right d-none d-sm-inline-block">
        <b>Version</b> 1.1.3 </div> </footer>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
  <script>
    // Definir el rol del usuario en JavaScript (se mantiene, puede ser útil)
    const USER_ROLE = "<?php echo $rol_usuario; ?>";
    const IS_ADMIN = <?php echo $es_administrador ? 'true' : 'false'; ?>;

    // Configuración base de AdminLTE ANTES de cargar adminlte.js (se mantiene)
    var $ = $ || {}; 
    $.AdminLTE = $.AdminLTE || {};
    $.AdminLTE.options = $.AdminLTE.options || {};
    // Estas opciones parecen estar orientadas a AdminLTE v2 o configuraciones muy específicas.
    // Para AdminLTE v3, el comportamiento del sidebar es diferente.
    // $.AdminLTE.options.sidebarExpandOnHover = false; // No es una opción estándar en v3
    // $.AdminLTE.options.enableExpandOnHover = false; // Similar
    // $.AdminLTE.options.sidebarCollapseScreenSize = 0; // v3 usa clases CSS y data attributes
    // $.AdminLTE.options.animationSpeed = 0; 
    // $.AdminLTE.options.noTransitionAfterPush = true; 
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  
  <script defer src="Js/ranking-ui.js"></script>
  <script defer src="Js/ranking-logic.js"></script>
  <script defer src="Js/app-nav.js"></script>

  <script>
    $(document).ready(function() {
      // Asegurar que AdminLTE se inicialice correctamente
      // $('body').Layout('fixLayoutHeight'); // Método de AdminLTE v2
      // $('body').Layout('fixSidebar');     // Método de AdminLTE v2
      // Para AdminLTE v3, los widgets se inicializan con [data-widget="..."]
      // y el layout se ajusta automáticamente en muchos casos.
      // Si hay problemas de layout, se pueden llamar métodos específicos de v3 si es necesario.
      
      // Control del sidebar (AdminLTE v3)
      // Para iniciar colapsado y sin expandir al pasar el ratón
      if (!$('body').hasClass('sidebar-collapse')) {
         // $('body').addClass('sidebar-collapse'); // Comentado si quieres que inicie expandido por defecto
      }
      // Para deshabilitar la expansión al pasar el ratón (si se usaba alguna opción custom)
      // $('.main-sidebar').off('mouseenter mouseleave'); // Esto es más drástico

      // Pequeña mejora para el título de la página
      function updateTitle(vista) {
          document.title = 'HelenSystem - ' + vista.charAt(0).toUpperCase() + vista.slice(1);
      }
      
      $(window).on('vistaCargada', function(event, vistaNombre) {
          if (vistaNombre) {
              updateTitle(vistaNombre);
          }
          // Re-inicializar widgets de AdminLTE después de cargar contenido dinámico si es necesario
          // Por ejemplo, si las vistas cargadas contienen elementos como cards con [data-card-widget]
          // $('.card').CardWidget(); // Descomentar si es necesario para vistas cargadas
      });
      
      const initialParams = new URLSearchParams(window.location.search);
      const initialVista = initialParams.get('vista') || 'dashboard'; // Default to dashboard
      updateTitle(initialVista);

      // Inicializar los componentes de AdminLTE como PushMenu manualmente si es necesario
      // Esto es útil si el data-api no se activa correctamente en todas las situaciones.
      var $pushMenuWidget = $('[data-widget="pushmenu"]');
      if ($pushMenuWidget.length && $.fn.PushMenu) {
          $pushMenuWidget.PushMenu();
      }
       // Inicializar CardWidget para cualquier tarjeta ya presente o cargada dinámicamente
      if ($.fn.CardWidget) {
          $(document).on('expanded.lte.cardwidget collapsed.lte.cardwidget', function(event) {
              // console.log('CardWidget event:', event.type, event.target);
          });
          // $('.card').CardWidget(); // Se puede llamar aquí o después de cargar la vista
      }


    });
  </script>
</body>
</html>