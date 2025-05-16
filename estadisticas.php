<?php
// global $es_administrador; // Esta variable usualmente se define al inicio del scope de la vista o controlador principal.
                          // Si no, el JavaScript dependerá de la respuesta de la API ('es_administrador').
function getToday() { return date('Y-m-d'); }
function getSevenDaysAgo() { return date('Y-m-d', strtotime('-6 days')); }
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Estadísticas</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php?vista=dashboard">Inicio</a></li>
                    <li class="breadcrumb-item active">Estadísticas</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid px-3">

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Ventas Diarias</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3 align-items-end"> <div class="col-md-3">
                        <label for="fechaInicioSemanal">Fecha Inicio:</label>
                        <input type="date" id="fechaInicioSemanal" class="form-control form-control-sm" value="<?php echo getSevenDaysAgo(); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fechaFinSemanal">Fecha Fin:</label>
                        <input type="date" id="fechaFinSemanal" class="form-control form-control-sm" value="<?php echo getToday(); ?>">
                    </div>
                    <div class="col-md-6"> <div class="d-flex align-items-center"> <button id="btnActualizarGraficoSemanal" class="btn btn-sm btn-info me-3"> <i class="fas fa-sync-alt"></i> Aplicar
                            </button>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="chkAutoRefreshVentasDiarias">
                                <label class="form-check-label" for="chkAutoRefreshVentasDiarias">
                                    Auto Refresh
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="min-height: 350px; position: relative;">
                    <canvas id="graficoVentasSemanales"></canvas>
                    <div id="loadingSemanal" class="chart-loading-overlay" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header">
                 <h3 class="card-title">Ventas por Hora (Rango de Fechas)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3 align-items-end"> <div class="col-md-3">
                        <label for="fechaInicioHoras">Fecha Inicio:</label>
                        <input type="date" id="fechaInicioHoras" class="form-control form-control-sm" value="<?php echo getSevenDaysAgo(); ?>">
                    </div>
                    <div class="col-md-3">
                         <label for="fechaFinHoras">Fecha Fin:</label>
                        <input type="date" id="fechaFinHoras" class="form-control form-control-sm" value="<?php echo getToday(); ?>">
                    </div>
                    <div class="col-md-6"> <div class="d-flex align-items-center"> <button id="btnActualizarGraficoHoras" class="btn btn-sm btn-info me-3"> <i class="fas fa-sync-alt"></i> Aplicar
                            </button>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="chkAutoRefreshVentasPorHora">
                                <label class="form-check-label" for="chkAutoRefreshVentasPorHora">
                                    Auto Refresh
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="min-height: 350px; position: relative;">
                    <canvas id="graficoVentasPorHora"></canvas>
                    <div id="loadingHoras" class="chart-loading-overlay" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-success"> <?php // Card-success para diferenciar visualmente ?>
            <div class="card-header">
                <h3 class="card-title">Rentabilidad de Productos</h3> <?php // Título cambiado ?>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3 align-items-end"> <div class="col-md-3">
                        <label for="fechaInicioProductos">Fecha Inicio:</label> <?php // ID cambiado ?>
                        <input type="date" id="fechaInicioProductos" class="form-control form-control-sm" value="<?php echo getSevenDaysAgo(); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fechaFinProductos">Fecha Fin:</label> <?php // ID cambiado ?>
                        <input type="date" id="fechaFinProductos" class="form-control form-control-sm" value="<?php echo getToday(); ?>">
                    </div>
                     <div class="col-md-6"> <div class="d-flex align-items-center"> <button id="btnActualizarTablaProductos" class="btn btn-sm btn-info me-3"> <i class="fas fa-sync-alt"></i> Aplicar Filtro <?php // ID cambiado ?>
                            </button>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="chkAutoRefreshTablaProductos"> <?php // ID cambiado ?>
                                <label class="form-check-label" for="chkAutoRefreshTablaProductos">
                                    Auto Refresh
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="loadingProductos" class="chart-loading-overlay" style="display:none; min-height: 100px;"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</div> <?php // ID cambiado ?>
                <div id="tablaDesempenoProductosContainer" class="table-responsive"> <?php // ID cambiado ?>
                </div>
            </div>
        </div>

    </div>
</section>

<style>
    .form-check-label {
        font-weight: normal;
        font-size: 0.9em;
        white-space: nowrap;
    }
    .form-check {
        display: flex;
        align-items: center;
    }
    .form-check-input {
        margin-top: 0; /* Ajustado para mejor alineación vertical con su label */
    }
    .chart-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.75);
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2em;
        color: #333;
    }
    #tablaDesempenoProductosContainer th.sortable {
        cursor: pointer;
    }
    #tablaDesempenoProductosContainer th.sortable:hover {
        background-color: #f0f0f0; /* Un ligero feedback visual */
    }
</style>

<script>
(function() {
    'use strict';

    let chartVentasDiarias = null;
    let chartVentasPorHora = null;

    let ventasDiariasIntervalId = null;
    let ventasPorHoraIntervalId = null;
    let tablaProductosIntervalId = null;

    const defaultChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'top' },
            title: { display: true, font: { size: 16 } },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) label += ': ';
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('es-SV', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
            },
            y: {
                beginAtZero: true,
                ticks: {
                     callback: function(value) {
                        return new Intl.NumberFormat('es-SV', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
                    }
                }
            }
        }
    };

    function formatCurrency(value) {
        return new Intl.NumberFormat('es-SV', { style: 'currency', currency: 'USD', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('es-SV', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
    }
    
    function formatPercentage(value) {
        if (value === null || value === undefined) return 'N/A';
        return parseFloat(value).toFixed(2) + '%';
    }

    function showOverlayLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) $(element).fadeIn(200);
    }

    function hideOverlayLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) $(element).fadeOut(200);
    }

    function setButtonLoadingState(buttonId, isLoading) {
        const button = document.getElementById(buttonId);
        if (!button) return;
        const icon = button.querySelector('i.fas');
        if (!icon) return;

        if (isLoading) {
            icon.classList.remove('fa-sync-alt');
            icon.classList.add('fa-spinner', 'fa-spin');
        } else {
            icon.classList.remove('fa-spinner', 'fa-spin');
            icon.classList.add('fa-sync-alt');
        }
    }

    function displayMessage(containerIdOrElement, message, type = 'info') {
        const containerEl = (typeof containerIdOrElement === 'string') ? document.getElementById(containerIdOrElement) : containerIdOrElement;
        if (containerEl) {
            const domElement = (containerEl instanceof jQuery) ? containerEl[0] : containerEl;
            $(domElement).find('.alert').remove();
            $(domElement).prepend(`<div class="alert alert-${type} text-center m-0">${message}</div>`).find('.alert').hide().fadeIn(300);
        }
    }

    function clearMessage(containerIdOrElement) {
        const containerEl = (typeof containerIdOrElement === 'string') ? document.getElementById(containerIdOrElement) : containerIdOrElement;
        if (containerEl) {
            const domElement = (containerEl instanceof jQuery) ? containerEl[0] : containerEl;
            $(domElement).find('.alert').remove();
        }
    }

    async function cargarYRenderizarGraficoDiario(isAutoRefresh = false) {
        const ctxSemanales = document.getElementById('graficoVentasSemanales');
        const loadingOverlayId = 'loadingSemanal';
        const chartContainerParent = $(ctxSemanales).parent();
        const fechaInicioEl = document.getElementById('fechaInicioSemanal');
        const fechaFinEl = document.getElementById('fechaFinSemanal');
        const buttonId = 'btnActualizarGraficoSemanal';

        if (!ctxSemanales || !fechaInicioEl || !fechaFinEl) {
            console.error("Elementos del DOM para gráfico diario no encontrados.");
            return;
        }
        if (!$(fechaInicioEl).val() || !$(fechaFinEl).val()) {
            if (!isAutoRefresh) displayMessage(chartContainerParent, 'Por favor, seleccione ambas fechas.', 'warning');
            return;
        }
        if (new Date($(fechaInicioEl).val()) > new Date($(fechaFinEl).val())) {
             if (!isAutoRefresh) displayMessage(chartContainerParent, 'La fecha de inicio no puede ser posterior a la fecha de fin.', 'warning');
            return;
        }

        clearMessage(chartContainerParent);
        setButtonLoadingState(buttonId, true); 

        if (!isAutoRefresh) { 
            showOverlayLoading(loadingOverlayId);
        }

        try {
            const response = await $.ajax({
                url: 'Api/get_ventas_semanales_tienda.php',
                type: 'GET',
                data: { fecha_inicio: $(fechaInicioEl).val(), fecha_fin: $(fechaFinEl).val() },
                dataType: 'json'
            });

            if (chartVentasDiarias) chartVentasDiarias.destroy();

            if (response.error) {
                displayMessage(chartContainerParent, response.error, 'danger');
                if (ctxSemanales.getContext('2d')) { const ctx = ctxSemanales.getContext('2d'); ctx.clearRect(0, 0, ctxSemanales.width, ctxSemanales.height); }
                return;
            }
            if (!response.labels || response.labels.length === 0 || !response.datasets || response.datasets.length === 0 || (response.datasets[0].data && response.datasets[0].data.every(item => item === 0))) {
                displayMessage(chartContainerParent, 'No hay datos de ventas para el rango seleccionado.', 'info');
                if (ctxSemanales.getContext('2d')) {
                     const ctx = ctxSemanales.getContext('2d');
                     ctx.clearRect(0, 0, ctxSemanales.width, ctxSemanales.height);
                }
                return;
            }
            const fechaInicioTitulo = new Date($(fechaInicioEl).val() + 'T00:00:00').toLocaleDateString('es-SV', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const fechaFinTitulo = new Date($(fechaFinEl).val() + 'T00:00:00').toLocaleDateString('es-SV', { day: '2-digit', month: '2-digit', year: 'numeric' });

            chartVentasDiarias = new Chart(ctxSemanales, {
                type: 'bar',
                data: response,
                options: {
                    ...defaultChartOptions,
                    plugins: { ...defaultChartOptions.plugins, title: { ...defaultChartOptions.plugins.title, text: `Ventas Diarias (${fechaInicioTitulo} al ${fechaFinTitulo})` } }
                }
            });
        } catch (error) {
            console.error("Error al cargar gráfico diario:", error);
            displayMessage(chartContainerParent, `Error al cargar gráfico diario: ${error.statusText || error.message || 'Error desconocido'}`, 'danger');
        } finally {
            setButtonLoadingState(buttonId, false);
            if (!isAutoRefresh) {
                hideOverlayLoading(loadingOverlayId);
            }
        }
    }

    async function cargarDatosPorHora(isAutoRefresh = false) {
        const ctxHoras = document.getElementById('graficoVentasPorHora');
        const loadingOverlayId = 'loadingHoras';
        const chartContainerParent = $(ctxHoras).parent();
        const fechaInicioHorasEl = document.getElementById('fechaInicioHoras');
        const fechaFinHorasEl = document.getElementById('fechaFinHoras');
        const buttonId = 'btnActualizarGraficoHoras';

        if (!ctxHoras || !fechaInicioHorasEl || !fechaFinHorasEl) { console.error("Elementos DOM gráfico por hora no encontrados."); return; }
        if (!$(fechaInicioHorasEl).val() || !$(fechaFinHorasEl).val()) {
            if (!isAutoRefresh) displayMessage(chartContainerParent, 'Por favor, seleccione ambas fechas.', 'warning');
            return;
        }
        if (new Date($(fechaInicioHorasEl).val()) > new Date($(fechaFinHorasEl).val())) {
            if (!isAutoRefresh) displayMessage(chartContainerParent, 'La fecha de inicio no puede ser posterior a la fecha de fin.', 'warning');
            return;
        }
        clearMessage(chartContainerParent);
        setButtonLoadingState(buttonId, true);

        if (!isAutoRefresh) {
            showOverlayLoading(loadingOverlayId);
        }

        try {
            const response = await $.ajax({
                url: 'Api/get_ventas_por_hora_rango.php',
                type: 'GET',
                data: { fecha_inicio: $(fechaInicioHorasEl).val(), fecha_fin: $(fechaFinHorasEl).val() },
                dataType: 'json'
            });

            if (chartVentasPorHora) chartVentasPorHora.destroy();

            if (response.error) {
                displayMessage(chartContainerParent, response.error, 'danger');
                if (ctxHoras.getContext('2d')) { const ctx = ctxHoras.getContext('2d'); ctx.clearRect(0, 0, ctxHoras.width, ctxHoras.height); }
                return;
            }
            if (!response.labels || response.labels.length === 0 || !response.datasets || response.datasets.length === 0 || (response.datasets[0].data && response.datasets[0].data.every(item => item === 0))) {
                 displayMessage(chartContainerParent, 'No hay datos de ventas por hora para el rango seleccionado.', 'info');
                 if (ctxHoras.getContext('2d')) { const ctx = ctxHoras.getContext('2d'); ctx.clearRect(0, 0, ctxHoras.width, ctxHoras.height); }
                 return;
            }
            const labels = response.labels.map(horaStr => { // Formatear a AM/PM
                const horaNum = parseInt(horaStr.split(':')[0], 10);
                const ampm = horaNum >= 12 ? (horaNum === 24 ? 'AM' : 'PM') : 'AM'; 
                let h12 = horaNum % 12;
                if (h12 === 0) h12 = 12; 
                return `${h12} ${ampm}`;
            });
            const fechaInicioTitulo = new Date($(fechaInicioHorasEl).val() + 'T00:00:00').toLocaleDateString('es-SV', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const fechaFinTitulo = new Date($(fechaFinHorasEl).val() + 'T00:00:00').toLocaleDateString('es-SV', { day: '2-digit', month: '2-digit', year: 'numeric' });

            chartVentasPorHora = new Chart(ctxHoras, {
                type: 'bar', // Tipo de gráfico restaurado a 'bar'
                data: {
                    labels: labels, 
                    datasets: response.datasets.map(ds => ({
                        ...ds,
                        backgroundColor: ds.backgroundColor || 'rgba(75, 192, 192, 0.5)',
                        borderColor: ds.borderColor || 'rgba(75, 192, 192, 1)',
                        borderWidth: ds.borderWidth || 1,
                        fill: ds.fill !== undefined ? ds.fill : true 
                    }))
                },
                options: {
                    ...defaultChartOptions,
                    plugins: {
                        ...defaultChartOptions.plugins,
                        title: { ...defaultChartOptions.plugins.title, text: `Ventas por Hora (${fechaInicioTitulo} al ${fechaFinTitulo})` },
                        legend: { display: response.datasets.length > 1 }
                    }
                }
            });
        } catch (error) {
            console.error("Error al cargar gráfico por hora:", error);
            displayMessage(chartContainerParent, `Error al cargar gráfico por hora: ${error.statusText || error.message || 'Error desconocido'}`, 'danger');
        } finally {
            setButtonLoadingState(buttonId, false);
            if (!isAutoRefresh) {
                hideOverlayLoading(loadingOverlayId);
            }
        }
    }

    // --- SECCIÓN DE PRODUCTOS ---
    function generarTablaProductosHeader(esAdmin) {
        const tablaContainer = $('#tablaDesempenoProductosContainer');
        
        let adminHeader = '';
        if (esAdmin) {
            adminHeader = `<th class="sortable text-end" data-sort="porcentaje_utilidad" data-order="">Utilidad (%) <i class="fas fa-sort"></i></th>`;
        }

        const tableHtml = `
            <table class="table table-sm table-striped table-hover table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th class="sortable" data-sort="producto" data-order="">Producto <i class="fas fa-sort"></i></th>
                        <th class="sortable text-end" data-sort="cantidad" data-order="">Cantidad <i class="fas fa-sort"></i></th>
                        <th class="sortable text-end" data-sort="monto_vendido" data-order="">Monto Vendido <i class="fas fa-sort"></i></th>
                        ${adminHeader}
                    </tr>
                </thead>
                <tbody></tbody>
            </table>`;
        tablaContainer.html(tableHtml);
        setupTableSortingProductos(); 
    }

    function renderTablaProductos(data, sortColumn, sortOrder, esAdmin) {
        const tbody = $('#tablaDesempenoProductosContainer table tbody');
        tbody.empty(); 

        if (!data || data.length === 0) {
            return; // El mensaje de "no hay datos" se maneja en la función que llama
        }

        const sortedData = [...data].sort((a, b) => {
            let valA, valB;
            switch (sortColumn) {
                case 'producto':
                    valA = String(a.producto || '').toLowerCase();
                    valB = String(b.producto || '').toLowerCase();
                    break;
                case 'cantidad':
                    valA = parseInt(a.cantidad) || 0;
                    valB = parseInt(b.cantidad) || 0;
                    break;
                case 'porcentaje_utilidad':
                    valA = esAdmin ? (parseFloat(a.porcentaje_utilidad) || -Infinity) : -Infinity; 
                    valB = esAdmin ? (parseFloat(b.porcentaje_utilidad) || -Infinity) : -Infinity;
                    break;
                case 'monto_vendido': 
                default:
                    valA = parseFloat(a.monto_vendido) || 0;
                    valB = parseFloat(b.monto_vendido) || 0;
                    break;
            }

            if (valA < valB) return sortOrder === 'asc' ? -1 : 1;
            if (valA > valB) return sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        sortedData.forEach(item => {
            let utilidadCell = '';
            if (esAdmin) {
                utilidadCell = `<td class="text-end">${formatPercentage(item.porcentaje_utilidad)}</td>`;
            }
            const row = `<tr>
                <td>${item.producto || 'N/A'}</td>
                <td class="text-end">${formatNumber(item.cantidad)}</td>
                <td class="text-end">${formatCurrency(parseFloat(item.monto_vendido))}</td>
                ${utilidadCell}
            </tr>`;
            tbody.append(row);
        });
    }

    async function cargarYRenderizarTablaProductos(isAutoRefresh = false, sortColumnParam = null, sortOrderParam = null) {
        const tablaContainerId = 'tablaDesempenoProductosContainer';
        const tablaProductosContainerEl = document.getElementById(tablaContainerId);
        const loadingOverlayId = 'loadingProductos';
        const fechaInicioProductosEl = document.getElementById('fechaInicioProductos');
        const fechaFinProductosEl = document.getElementById('fechaFinProductos');
        const buttonId = 'btnActualizarTablaProductos';

        if (!tablaProductosContainerEl || !fechaInicioProductosEl || !fechaFinProductosEl) { console.error("Elementos DOM tabla productos no encontrados."); return; }
        if (!$(fechaInicioProductosEl).val() || !$(fechaFinProductosEl).val()) {
            if (!isAutoRefresh) {
                generarTablaProductosHeader(false); 
                displayMessage(tablaProductosContainerEl, 'Por favor, seleccione ambas fechas.', 'warning');
            }
            return;
        }
        if (new Date($(fechaInicioProductosEl).val()) > new Date($(fechaFinProductosEl).val())) {
            if (!isAutoRefresh){
                generarTablaProductosHeader(false); 
                displayMessage(tablaProductosContainerEl, 'La fecha de inicio no puede ser posterior a la fecha de fin.', 'warning');
            }
            return;
        }
        
        clearMessage(tablaProductosContainerEl);
        setButtonLoadingState(buttonId, true);

        if (!isAutoRefresh) {
            showOverlayLoading(loadingOverlayId);
        }
        
        let clientSortCol = sortColumnParam;
        let clientSortDir = sortOrderParam;
        let sortColApi;

        if (!clientSortCol) { 
            const activeSortTh = $('#' + tablaContainerId + ' th.sortable[data-order!=""][data-order]');
            if (activeSortTh.length > 0 && activeSortTh.data('sort')) {
                clientSortCol = activeSortTh.data('sort');
                clientSortDir = activeSortTh.data('order');
            } else {
                clientSortCol = 'monto_vendido';
                clientSortDir = 'desc';
            }
        }
        
        sortColApi = clientSortCol;

        try {
            const response = await $.ajax({
                url: 'Api/get_desempeno_productos.php',
                type: 'GET',
                data: {
                    fecha_inicio: $(fechaInicioProductosEl).val(),
                    fecha_fin: $(fechaFinProductosEl).val(),
                    orden: sortColApi 
                },
                dataType: 'json'
            });
            
            generarTablaProductosHeader(response.es_administrador); 

            $('#' + tablaContainerId + ' th.sortable i.fas').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
            $('#' + tablaContainerId + ' th.sortable').data('order', ''); 

            const targetTh = $('#' + tablaContainerId + ' th.sortable[data-sort="' + clientSortCol + '"]');
            if (targetTh.length) {
                targetTh.data('order', clientSortDir); 
                targetTh.find('i.fas').removeClass('fa-sort').addClass(clientSortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
            }

            if (response.error) {
                displayMessage(tablaProductosContainerEl, response.error, 'danger');
                $('#' + tablaContainerId + ' table tbody').empty();
                return;
            }

            if (!response.data || response.data.length === 0) {
                displayMessage(tablaProductosContainerEl, 'No hay datos de desempeño de productos para el rango seleccionado.', 'info');
                $('#' + tablaContainerId + ' table tbody').empty();
                return;
            }
            
            renderTablaProductos(response.data, clientSortCol, clientSortDir, response.es_administrador);

        } catch (error) {
            console.error("Error al cargar datos de productos:", error);
            generarTablaProductosHeader(false); 
            displayMessage(tablaProductosContainerEl, `Error al cargar datos de productos: ${error.statusText || error.message || 'Error desconocido'}`, 'danger');
            $('#' + tablaContainerId + ' table tbody').empty();
        } finally {
            setButtonLoadingState(buttonId, false);
            if (!isAutoRefresh) {
                hideOverlayLoading(loadingOverlayId);
            }
        }
    }

    function setupTableSortingProductos() {
        const container = $('#tablaDesempenoProductosContainer');
        container.off('click', 'th.sortable').on('click', 'th.sortable', function() {
            const clickedHeader = $(this);
            const sortColumn = clickedHeader.data('sort');
            let currentOrder = clickedHeader.data('order'); 

            if (currentOrder === 'asc') {
                currentOrder = 'desc';
            } else { 
                currentOrder = 'asc';
            }
            cargarYRenderizarTablaProductos(false, sortColumn, currentOrder); 
        });
    }

    function setupIndividualAutoRefresh(checkboxId, refreshFunction, intervalVarNameGlobal, buttonId) {
        const autoRefreshCheckbox = document.getElementById(checkboxId);
        if (!autoRefreshCheckbox) return;

        if (window[intervalVarNameGlobal]) {
            clearInterval(window[intervalVarNameGlobal]);
            window[intervalVarNameGlobal] = null;
        }
        
        const handlerKey = `_handleChange_${checkboxId}`;
        if (autoRefreshCheckbox[handlerKey]) {
            autoRefreshCheckbox.removeEventListener('change', autoRefreshCheckbox[handlerKey]);
        }

        autoRefreshCheckbox[handlerKey] = function() {
            if (this.checked) {
                if (window[intervalVarNameGlobal]) clearInterval(window[intervalVarNameGlobal]);
                refreshFunction(true); // Llama a la función de refresco con isAutoRefresh = true
                window[intervalVarNameGlobal] = setInterval(() => refreshFunction(true), 30000); 
            } else {
                clearInterval(window[intervalVarNameGlobal]);
                window[intervalVarNameGlobal] = null;
                setButtonLoadingState(buttonId, false); 
            }
        };
        autoRefreshCheckbox.addEventListener('change', autoRefreshCheckbox[handlerKey]);

        if (autoRefreshCheckbox.checked && !window[intervalVarNameGlobal]) {
             autoRefreshCheckbox[handlerKey].call(autoRefreshCheckbox);
        }
    }

    function initializeEstadisticasPage() {
        $('#btnActualizarGraficoSemanal').off('click').on('click', () => cargarYRenderizarGraficoDiario(false));
        $('#btnActualizarGraficoHoras').off('click').on('click', () => cargarDatosPorHora(false));
        
        $('#btnActualizarTablaProductos').off('click').on('click', () => {
            cargarYRenderizarTablaProductos(false, null, null); // Fuerza el orden default
        });

        cargarYRenderizarGraficoDiario(false);
        cargarDatosPorHora(false);
        cargarYRenderizarTablaProductos(false); 
        
        setupIndividualAutoRefresh('chkAutoRefreshVentasDiarias', cargarYRenderizarGraficoDiario, 'ventasDiariasIntervalId', 'btnActualizarGraficoSemanal');
        setupIndividualAutoRefresh('chkAutoRefreshVentasPorHora', cargarDatosPorHora, 'ventasPorHoraIntervalId', 'btnActualizarGraficoHoras');
        
        // Para el auto-refresh de productos, intentamos mantener el orden actual.
        setupIndividualAutoRefresh('chkAutoRefreshTablaProductos', () => {
            const activeSortTh = $('#tablaDesempenoProductosContainer th.sortable[data-order!=""][data-order]');
            let currentCol = 'monto_vendido'; // Default si no hay nada activo
            let currentDir = 'desc';
            if (activeSortTh.length > 0 && activeSortTh.data('sort')) {
                currentCol = activeSortTh.data('sort');
                currentDir = activeSortTh.data('order');
            }
            cargarYRenderizarTablaProductos(true, currentCol, currentDir);
        }, 'tablaProductosIntervalId', 'btnActualizarTablaProductos');
    }

    window.reinitializeEstadisticasPage = function() {
        if (ventasDiariasIntervalId) clearInterval(ventasDiariasIntervalId);
        if (ventasPorHoraIntervalId) clearInterval(ventasPorHoraIntervalId);
        if (tablaProductosIntervalId) clearInterval(tablaProductosIntervalId); 
        ventasDiariasIntervalId = null;
        ventasPorHoraIntervalId = null;
        tablaProductosIntervalId = null; 

        [
            {chkId: 'chkAutoRefreshVentasDiarias', btnId: 'btnActualizarGraficoSemanal'},
            {chkId: 'chkAutoRefreshVentasPorHora', btnId: 'btnActualizarGraficoHoras'},
            {chkId: 'chkAutoRefreshTablaProductos', btnId: 'btnActualizarTablaProductos'}
        ].forEach(item => {
            const chk = document.getElementById(item.chkId);
            if (chk) {
                 const handlerKey = `_handleChange_${item.chkId}`;
                if (chk[handlerKey]) {
                    chk.removeEventListener('change', chk[handlerKey]);
                    chk[handlerKey] = null;
                }
                // chk.checked = false; // Opcional: resetear
            }
            setButtonLoadingState(item.btnId, false);
        });

        if (chartVentasDiarias) { chartVentasDiarias.destroy(); chartVentasDiarias = null; }
        if (chartVentasPorHora) { chartVentasPorHora.destroy(); chartVentasPorHora = null; }
        $('#tablaDesempenoProductosContainer').empty();

        initializeEstadisticasPage();
    };

    $(document).ready(function() {
        if (document.getElementById('graficoVentasSemanales') || document.getElementById('tablaDesempenoProductosContainer')) { 
            initializeEstadisticasPage();
        }
    });

})();
</script>