<?php
// Este archivo es incluido por index.php (vía el gestor AJAX)
// La comprobación de $es_administrador ya se hace en index.php antes de incluir esta vista.

// Doble seguridad: si no se carga vía AJAX o si el rol no es Administrador (ya controlado en index.php)
if (
    (!isset($_GET['ajax_get_view']) && !isset($_SESSION['loggedin'])) ||
    (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] !== 'Administrador')
) {
    echo "<section class='content-header'><div class='container-fluid'><div class='row mb-2'><div class='col-sm-6'><h1>Acceso Denegado</h1></div></div></div></section>";
    echo "<section class='content'><div class='container-fluid'><p>No tienes permisos para acceder a esta sección.</p></div></section>";
    exit;
}
?>
<section class="content-header">
    <div class="container-fluid px-3">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Configuración del Sistema</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#" class="nav-link" data-vista="dashboard">Inicio</a></li>
                    <li class="breadcrumb-item active">Configuración</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid px-3">
        <div class="card card-outline card-warning collapsed-card">
            <div class="card-header">
                <h3 class="card-title">Opciones Generales (Ejemplo)</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-plus"></i> </button>
                </div>
            </div>
            <div class="card-body" style="display: none;"> <p>Aquí irían los formularios y opciones para configurar el sistema.</p>
                <form id="formConfiguracionGeneral">
                    <div class="form-group">
                        <label for="siteName">Nombre del Sitio:</label>
                        <input type="text" class="form-control" id="siteName" name="siteName" value="HelenSystem">
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Configuración General</button>
                </form>
            </div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Gestión de Permisos de Usuario (Archivo <code>Permisos/permisos.txt</code>)</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                </div>
            </div>
            <div class="card-body">
                <div id="permisosMessages" class="mb-3"></div> <button id="btnAnadirPermiso" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Añadir Nuevo Permiso</button>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tablaPermisos">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Código EMP</th>
                                <th style="width: 25%;">Rol</th>
                                <th style="width: 35%;">Filtro Producto Preferencia</th>
                                <th style="width: 15%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="bodyTablaPermisos">
                            <tr><td colspan="4" class="text-center">Cargando datos de permisos... <i class="fas fa-spinner fa-spin"></i></td></tr>
                        </tbody>
                    </table>
                </div>
                <button id="btnGuardarPermisos" class="btn btn-primary mt-3"><i class="fas fa-save"></i> Guardar Todos los Cambios</button>
                <p class="mt-2"><small>Nota: Los cambios se guardarán directamente en el archivo <code>Permisos/permisos.txt</code>. Asegúrese de que el servidor web tiene permisos de escritura sobre este archivo y su carpeta.</small></p>
                <p><small>Roles comunes: <code>Administrador</code>, <code>Supervisor</code>, <code>Vendedor</code>. El filtro puede ser un patrón como <code>PRODUCTO%</code> o un código/descripción específica. Un filtro vacío significa sin preferencia (mostrará todo según otros filtros).</small></p>
            </div>
        </div>

    </div>
</section>
<script>
$(document).ready(function() {
    // Inicializar CardWidgets específicamente para esta vista si es necesario
    // AdminLTE v3 maneja esto bien con data-api, pero una llamada explícita puede ayudar
    // si el contenido se carga y los listeners no se aplican automáticamente.
    if ($.fn.CardWidget) {
        $('#dynamic-content-container .card').CardWidget();
    }

    // --- INICIO: LÓGICA PARA GESTIÓN DE PERMISOS ---
    let permisosData = []; 
    let nextTempId = 0; 

    function displayMessage(message, type = 'success', container = '#permisosMessages') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        $(container).html(`<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas ${icon} mr-2"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`).find('.alert').alert(); // Asegurar que el dismiss de Bootstrap 5 funcione
    }

    function renderTablaPermisos() {
        const tbody = $('#bodyTablaPermisos');
        tbody.empty();
        if (permisosData.length === 0) {
            tbody.html('<tr><td colspan="4" class="text-center">No hay permisos definidos. Haga clic en "Añadir Nuevo Permiso" para comenzar.</td></tr>');
            return;
        }

        permisosData.forEach((permiso, index) => {
            const rowId = permiso.id !== undefined ? permiso.id : `temp-${index}`; 
            const row = `
                <tr data-id="${rowId}">
                    <td><input type="text" class="form-control codigo-emp" value="${permiso.codigo_emp || ''}" placeholder="Ej: EMP001" aria-label="Código de Empleado"></td>
                    <td><input type="text" class="form-control rol" value="${permiso.rol || ''}" placeholder="Ej: Vendedor" aria-label="Rol del Usuario"></td>
                    <td><input type="text" class="form-control filtro" value="${permiso.filtro || ''}" placeholder="Ej: PRODUCTO_X%" aria-label="Preferencia de Filtro de Producto"></td>
                    <td class="acciones-permiso text-center">
                        <button class="btn btn-danger btn-sm btnEliminarPermiso" title="Eliminar este permiso"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function loadPermisos() {
        $('#bodyTablaPermisos').html('<tr><td colspan="4" class="text-center">Cargando datos... <i class="fas fa-spinner fa-spin"></i></td></tr>');
        $.ajax({
            url: 'index.php?ajax_action=get_permisos_data',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    permisosData = response.data.map((item, index) => ({...item, id: item.id !== undefined ? item.id : `loaded-${index}` }));
                    nextTempId = permisosData.length; // Reset basado en los cargados
                    renderTablaPermisos();
                } else {
                    displayMessage('Error al cargar permisos: ' + (response.error || 'Respuesta no válida.'), 'danger');
                    $('#bodyTablaPermisos').html('<tr><td colspan="4" class="text-center">Error al cargar datos. Verifique la consola.</td></tr>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMsg = 'Error de conexión o servidor al cargar permisos.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
                    errorMsg = jqXHR.responseJSON.error;
                } else if (jqXHR.responseText) {
                    try {
                        const errResponse = JSON.parse(jqXHR.responseText);
                        errorMsg = errResponse.error || jqXHR.responseText;
                    } catch(e) { /* No hacer nada si no es JSON */ }
                }
                displayMessage(errorMsg, 'danger');
                $('#bodyTablaPermisos').html(`<tr><td colspan="4" class="text-center">Error al cargar: ${errorMsg}</td></tr>`);
            }
        });
    }

    $('#btnAnadirPermiso').on('click', function() {
        const newId = `temp-${nextTempId++}`;
        permisosData.push({ id: newId, codigo_emp: '', rol: '', filtro: '' });
        renderTablaPermisos();
        const newRow = $(`tr[data-id='${newId}']`);
        if (newRow.length > 0) {
            newRow.find('input.codigo-emp').focus();
            // Opcional: scroll hacia la nueva fila si la tabla es larga
            // newRow[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    $('#bodyTablaPermisos').on('click', '.btnEliminarPermiso', function() {
        if (!confirm('¿Está seguro de que desea eliminar esta fila de permiso? Los cambios se aplicarán al guardar.')) {
            return;
        }
        const row = $(this).closest('tr');
        const idToRemove = row.data('id');
        
        permisosData = permisosData.filter(p => p.id !== idToRemove);
        renderTablaPermisos(); 
        displayMessage('Fila marcada para eliminación. Guarde los cambios para aplicar.', 'success');
    });

    // Guardar el valor del input en el array JS en cuanto cambie
    $('#bodyTablaPermisos').on('input', 'input.codigo-emp, input.rol, input.filtro', function() {
        const row = $(this).closest('tr');
        const idToUpdate = row.data('id');
        const permisoIndex = permisosData.findIndex(p => p.id === idToUpdate);

        if (permisoIndex !== -1) {
            permisosData[permisoIndex].codigo_emp = row.find('input.codigo-emp').val();
            permisosData[permisoIndex].rol = row.find('input.rol').val();
            permisosData[permisoIndex].filtro = row.find('input.filtro').val();
        }
    });


    $('#btnGuardarPermisos').on('click', function() {
        // Re-leer los datos de la tabla para asegurar que tenemos lo último visualizado
        // (aunque el 'input' event debería mantener `permisosData` actualizado)
        let currentDataFromTable = [];
        $('#bodyTablaPermisos tr').each(function() {
            const fila = $(this);
            if (fila.find('input.codigo-emp').length) { 
                currentDataFromTable.push({
                    codigo_emp: fila.find('input.codigo-emp').val().trim(),
                    rol: fila.find('input.rol').val().trim(),
                    filtro: fila.find('input.filtro').val().trim() 
                });
            }
        });
        
        const datosParaEnviar = currentDataFromTable.filter(p => p.codigo_emp !== '' && p.rol !== '');

        if (datosParaEnviar.length === 0 && currentDataFromTable.length > 0) {
             if (!confirm('Algunas entradas están incompletas (Código EMP o Rol faltante) y no se guardarán. ¿Desea continuar guardando las entradas válidas (si las hay) o un archivo vacío si todas son inválidas?')) {
                return;
            }
        }
         if (datosParaEnviar.length === 0 && currentDataFromTable.length === 0) { 
            if(!confirm('No hay datos válidos para guardar. Si el archivo de permisos existe, esto podría vaciarlo. ¿Desea continuar?')){
                return;
            }
         }

        const $thisButton = $(this);
        $thisButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: 'index.php?ajax_action=save_permisos_data',
            type: 'POST',
            data: { permisos_data: JSON.stringify(datosParaEnviar) }, 
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayMessage(response.message || 'Permisos guardados correctamente.', 'success');
                    loadPermisos(); 
                } else {
                    displayMessage('Error al guardar: ' + (response.error || 'Error desconocido.'), 'danger');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMsg = 'Error de conexión o servidor al guardar permisos.';
                 if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
                    errorMsg = jqXHR.responseJSON.error;
                } else if (jqXHR.responseText) {
                    try {
                        const errResponse = JSON.parse(jqXHR.responseText);
                        errorMsg = errResponse.error || jqXHR.responseText;
                    } catch(e) { /* No hacer nada si no es JSON */ }
                }
                displayMessage(errorMsg, 'danger');
            },
            complete: function() {
                $thisButton.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Todos los Cambios');
            }
        });
    });

    loadPermisos();

    // --- FIN: LÓGICA PARA GESTIÓN DE PERMISOS ---

    $('#formConfiguracionGeneral').on('submit', function(e) {
        e.preventDefault();
        alert('Configuración general guardada (simulado).');
    });
});
</script>