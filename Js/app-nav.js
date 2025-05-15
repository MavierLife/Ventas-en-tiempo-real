// Js/app-nav.js
$(document).ready(function() {
    const $mainContentContainer = $('#dynamic-content-container');
    const $sidebarNav = $('#main-nav');
    const $globalLoadingOverlay = $('#globalLoadingOverlay'); // Referencia al loader global
    const $globalLoadingStatus = $('#globalLoadingStatus');
    const $globalLoadingSubStatus = $('#globalLoadingSubStatus');

    let currentVista = ''; // Para saber qué vista está cargada

    function showMainContentLoader(message = 'Cargando vista...') {
        $mainContentContainer.html('<div class="loading-placeholder" style="display: flex; justify-content: center; align-items: center; height: calc(100vh - 200px); font-size: 1.5em; color: #777;"><i class="fas fa-spinner fa-spin" style="margin-right:10px;"></i>' + message + '</div>');
    }

    function loadView(vista, pushToHistory = true, isPopState = false) {
        if (!vista) {
            // console.warn('Intento de cargar vista nula, redirigiendo a dashboard.');
            vista = 'dashboard'; // Vista por defecto
        }

        // Evitar recargar la misma vista si no es por popstate (navegación historial)
        if (vista === currentVista && !isPopState) {
            // console.log('La vista ' + vista + ' ya está cargada.');
            // Podrías decidir recargarla o simplemente no hacer nada.
            // Por ahora, no hacemos nada si es la misma vista y no es por historial.
            return;
        }
        
        currentVista = vista; // Marcar la vista actual intentando cargarla
        showMainContentLoader(`Cargando ${vista}...`);
        updateActiveLink(vista);


        $.ajax({
            url: `index.php?ajax_get_view=true&vista=${encodeURIComponent(vista)}`,
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                $mainContentContainer.html(response);
                // Los scripts dentro de la respuesta HTML (como en dashboard.php o configuracion.php) se ejecutarán aquí.
                
                if (pushToHistory) {
                    history.pushState({ vista: vista }, '', `index.php?vista=${vista}`);
                }
                
                // Llama a la función de inicialización específica si existe
                if (vista === 'dashboard' && typeof initializeDashboardControls === 'function') {
                    initializeDashboardControls();
                } else if (vista === 'configuracion' && typeof initializeConfiguracionPage === 'function') {
                    // initializeConfiguracionPage(); // Si tuvieras una función específica
                }

                // Re-inicializar cualquier widget global de AdminLTE si es necesario después de la carga
                // AdminLTE podría auto-detectar algunos, pero a veces es bueno forzar:
                // $(document).trigger('load.lte.CardWidget'); // O similar, revisar documentación AdminLTE v3
                // $('[data-card-widget="collapse"]').CardWidget('init'); //Ya está en los parciales
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMsg = `<div class='alert alert-danger m-3'><h4>Error al cargar la vista '${vista}'</h4>`;
                if (jqXHR.status === 404) {
                    errorMsg += `<p>El archivo de la vista no fue encontrado en el servidor.</p>`;
                } else if (jqXHR.status === 403) {
                    errorMsg += `<p>No tienes permiso para acceder a esta vista.</p>`;
                } else {
                    errorMsg += `<p>Detalles: ${textStatus} - ${errorThrown}.</p>`;
                }
                if (jqXHR.responseText && jqXHR.responseText.length < 500) { // Mostrar respuesta corta si es útil
                    errorMsg += `<div class='mt-2 p-2 bg-light border rounded' style='font-size:0.9em;'>${jqXHR.responseText}</div>`;
                }
                errorMsg += `</div>`;
                $mainContentContainer.html(errorMsg);
                currentVista = 'error'; // Marcar que la vista actual es un error
            },
            complete: function() {
                // Ocultar el loader global si lo estuvieras usando para esto.
                // Por ahora, el loader es solo el placeholder en $mainContentContainer.
            }
        });
    }

    function updateActiveLink(vista) {
        $sidebarNav.find('.nav-link').removeClass('active');
        $sidebarNav.find(`.nav-link[data-vista="${vista}"]`).addClass('active');
        // También el brand-link si es clicado
        if ($('.brand-link').data('vista') === vista) {
            $('.brand-link').addClass('active');
        } else {
             $('.brand-link').removeClass('active');
        }
    }

    // Manejar clics en los enlaces del sidebar y el brand-link
    $sidebarNav.on('click', '.nav-link', function(e) {
        e.preventDefault();
        const vista = $(this).data('vista');
        if (vista) {
            loadView(vista, true);
        }
    });
    $('.brand-link').on('click', function(e) { // Para que el logo también funcione con AJAX
        e.preventDefault();
        const vista = $(this).data('vista');
        if (vista) {
            loadView(vista, true);
        }
    });


    // Manejar botones de atrás/adelante del navegador
    $(window).on('popstate', function(event) {
        const state = event.originalEvent.state;
        if (state && state.vista) {
            loadView(state.vista, false, true); // No pushear al historial, es popstate
        } else {
            // Si no hay estado, podría ser la URL inicial o una URL sin "?vista="
            const params = new URLSearchParams(window.location.search);
            const initialVista = params.get('vista') || 'dashboard'; // Cargar dashboard por defecto
            loadView(initialVista, false, true);
        }
    });

    // Carga inicial de la vista basada en la URL actual
    const params = new URLSearchParams(window.location.search);
    let initialVista = params.get('vista');
    
    if (!initialVista) { // Si no hay ?vista= en la URL
        initialVista = 'dashboard'; // Carga dashboard por defecto
        // Actualiza la URL para reflejar la vista 'dashboard' sin recargar, si no está ya
        if (window.location.search.indexOf('vista=') === -1) {
             history.replaceState({ vista: initialVista }, '', `index.php?vista=${initialVista}`);
        }
    }
    loadView(initialVista, false); // Cargar sin pushear al historial, replaceState ya lo hizo si fue necesario
});