<?php
global $es_administrador;
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

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Desempeño de Vendedores</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3 align-items-end"> <div class="col-md-3">
                        <label for="fechaInicioVendedores">Fecha Inicio:</label>
                        <input type="date" id="fechaInicioVendedores" class="form-control form-control-sm" value="<?php echo getSevenDaysAgo(); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fechaFinVendedores">Fecha Fin:</label>
                        <input type="date" id="fechaFinVendedores" class="form-control form-control-sm" value="<?php echo getToday(); ?>">
                    </div>
                     <div class="col-md-6"> <div class="d-flex align-items-center"> <button id="btnActualizarTablaVendedores" class="btn btn-sm btn-info me-3"> <i class="fas fa-sync-alt"></i> Aplicar Filtro
                            </button>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="chkAutoRefreshTablaVendedores">
                                <label class="form-check-label" for="chkAutoRefreshTablaVendedores">
                                    Auto Refresh
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="loadingVendedores" class="chart-loading-overlay" style="display:none; min-height: 100px;"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</div>
                <div id="tablaDesempenoVendedoresContainer" class="table-responsive">
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
</style>

<script>
(function() {
    'use strict';

    let chartVentasDiarias = null;
    let chartVentasPorHora = null;
    let ordenActualVendedores = 'monto'; // Default sort for sellers table

    // Store interval IDs globally within this IIFE scope
    let ventasDiariasIntervalId = null;
    let ventasPorHoraIntervalId = null;
    let tablaVendedoresIntervalId = null;

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
            icon.classList.add('fa-spin');
            // button.disabled = true; // Optionally disable button
        } else {
            icon.classList.remove('fa-spin');
            // button.disabled = false; // Re-enable button
        }
    }

    function displayMessage(containerIdOrElement, message, type = 'info') {
        const containerEl = (typeof containerIdOrElement === 'string') ? document.getElementById(containerIdOrElement) : containerIdOrElement;
        if (containerEl) {
            const domElement = (containerEl instanceof jQuery) ? containerEl[0] : containerEl;
             // Clear previous messages specifically, not the whole container if it's for a chart parent
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
        const chartContainerParent = $(ctxSemanales).parent(); // The div holding canvas and overlay
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

        if (isAutoRefresh) {
            setButtonLoadingState(buttonId, true);
        } else {
            showOverlayLoading(loadingOverlayId);
            setButtonLoadingState(buttonId, false); // Ensure button icon is not spinning on manual refresh
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
                return;
            }
            if (!response.labels || response.labels.length === 0 || !response.datasets || response.datasets.length === 0 || (response.datasets[0].data && response.datasets[0].data.every(item => item === 0))) {
                displayMessage(chartContainerParent, 'No hay datos de ventas para el rango seleccionado.', 'info');
                if (ctxSemanales.getContext('2d')) { // Clear canvas if no data
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
            if (isAutoRefresh) {
                setButtonLoadingState(buttonId, false);
            } else {
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

        if (isAutoRefresh) {
            setButtonLoadingState(buttonId, true);
        } else {
            showOverlayLoading(loadingOverlayId);
            setButtonLoadingState(buttonId, false);
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
                return;
            }
            if (!response.labels || response.labels.length === 0 || !response.datasets || response.datasets.length === 0 || (response.datasets[0].data && response.datasets[0].data.every(item => item === 0))) {
                 displayMessage(chartContainerParent, 'No hay datos de ventas por hora para el rango seleccionado.', 'info');
                 if (ctxHoras.getContext('2d')) { const ctx = ctxHoras.getContext('2d'); ctx.clearRect(0, 0, ctxHoras.width, ctxHoras.height); }
                 return;
            }
            const labels = response.labels.map(horaStr => {
                const horaNum = parseInt(horaStr.split(':')[0], 10);
                const ampm = horaNum >= 12 ? (horaNum === 24 ? 'AM' : 'PM') : 'AM'; // Handle 24 as 12 AM for display
                let h12 = horaNum % 12;
                if (h12 === 0) h12 = 12; // 0 and 12 become 12
                return `${h12} ${ampm}`;
            });
            const fechaInicioTitulo = new Date($(fechaInicioHorasEl).val() + 'T00:00:00').toLocaleDateString('es-SV', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const fechaFinTitulo = new Date($(fechaFinHorasEl).val() + 'T00:00:00').toLocaleDateString('es-SV', { day: '2-digit', month: '2-digit', year: 'numeric' });

            chartVentasPorHora = new Chart(ctxHoras, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: response.datasets.map(ds => ({ ...ds, backgroundColor: ds.backgroundColor || 'rgba(23, 162, 184, 0.7)', borderColor: ds.borderColor || 'rgba(23, 162, 184, 1)', borderWidth: 1 }))
                },
                options: { ...defaultChartOptions, plugins: { ...defaultChartOptions.plugins, title: { ...defaultChartOptions.plugins.title, text: `Ventas por Hora (${fechaInicioTitulo} al ${fechaFinTitulo})` }, legend: { display: response.datasets.length > 1 } } }
            });
        } catch (error) {
            console.error("Error al cargar gráfico por hora:", error);
            displayMessage(chartContainerParent, `Error al cargar gráfico por hora: ${error.statusText || error.message || 'Error desconocido'}`, 'danger');
        } finally {
            if (isAutoRefresh) {
                setButtonLoadingState(buttonId, false);
            } else {
                hideOverlayLoading(loadingOverlayId);
            }
        }
    }

    function generarTablaVendedoresHeader() {
        const tablaContainer = $('#tablaDesempenoVendedoresContainer');
        if (tablaContainer.find('table thead').length > 0) return; // Ya existe

        const tableHtml = `
            <table class="table table-sm table-striped table-hover table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th class="sortable" data-sort="vendedor" data-order="desc">Vendedor <i class="fas fa-sort"></i></th>
                        <th class="sortable text-end" data-sort="cantidad_ventas" data-order="desc">Cantidad <i class="fas fa-sort"></i></th>
                        <th class="sortable text-end" data-sort="monto_vendido" data-order="desc">Monto Vendido <i class="fas fa-sort"></i></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>`;
        tablaContainer.html(tableHtml);
        setupTableSorting(); // Llama a configurar el ordenamiento después de crear la cabecera
    }

    function renderTablaVendedores(data, sortColumn, sortOrder) {
        const tbody = $('#tablaDesempenoVendedoresContainer table tbody');
        tbody.empty();
        clearMessage($('#tablaDesempenoVendedoresContainer')); // Clear previous no-data messages

        if (!data || data.length === 0) {
            displayMessage($('#tablaDesempenoVendedoresContainer'), 'No hay datos de desempeño de vendedores para el rango seleccionado.', 'info');
            return;
        }

        // Sort data
        const sortedData = [...data].sort((a, b) => {
            let valA, valB;
            if (sortColumn === 'vendedor') {
                valA = String(a.vendedor || '').toLowerCase();
                valB = String(b.vendedor || '').toLowerCase();
            } else if (sortColumn === 'cantidad_ventas') { // Nombre corregido según la API
                valA = parseInt(a.cantidad_ventas) || 0;
                valB = parseInt(b.cantidad_ventas) || 0;
            } else { // Default to monto_vendido
                valA = parseFloat(a.monto_vendido) || 0; // Nombre corregido según la API
                valB = parseFloat(b.monto_vendido) || 0;
            }

            if (valA < valB) return sortOrder === 'asc' ? -1 : 1;
            if (valA > valB) return sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        sortedData.forEach(item => {
            const row = `<tr>
                <td>${item.vendedor || 'N/A'}</td>
                <td class="text-end">${formatNumber(item.cantidad_ventas)}</td>
                <td class="text-end">${formatCurrency(parseFloat(item.monto_vendido))}</td>
            </tr>`;
            tbody.append(row);
        });
    }

    async function cargarYRenderizarTablaVendedores(isAutoRefresh = false) {
        const tablaContainerId = 'tablaDesempenoVendedoresContainer';
        const tablaVendedoresContainerEl = document.getElementById(tablaContainerId);
        const loadingOverlayId = 'loadingVendedores'; // This overlay is outside the table container div
        const fechaInicioVendedoresEl = document.getElementById('fechaInicioVendedores');
        const fechaFinVendedoresEl = document.getElementById('fechaFinVendedores');
        const buttonId = 'btnActualizarTablaVendedores';

        if (!tablaVendedoresContainerEl || !fechaInicioVendedoresEl || !fechaFinVendedoresEl) { console.error("Elementos DOM tabla vendedores no encontrados."); return; }
        if (!$(fechaInicioVendedoresEl).val() || !$(fechaFinVendedoresEl).val()) {
            if (!isAutoRefresh) displayMessage(tablaVendedoresContainerEl, 'Por favor, seleccione ambas fechas.', 'warning');
            return;
        }
        if (new Date($(fechaInicioVendedoresEl).val()) > new Date($(fechaFinVendedoresEl).val())) {
            if (!isAutoRefresh) displayMessage(tablaVendedoresContainerEl, 'La fecha de inicio no puede ser posterior a la fecha de fin.', 'warning');
            return;
        }

        // Ensure header exists before any operation (idempotent)
        generarTablaVendedoresHeader();
        clearMessage(tablaVendedoresContainerEl); // Clear messages within the table container

        if (isAutoRefresh) {
            setButtonLoadingState(buttonId, true);
        } else {
            showOverlayLoading(loadingOverlayId); // Use the card-body overlay
            setButtonLoadingState(buttonId, false);
             // For manual refresh, we can clear the body to show loading is for new data
            $('#' + tablaContainerId + ' table tbody').empty();
        }

        // Determine current sort state from headers for the API call (optional)
        const activeSortTh = $('#' + tablaContainerId + ' th.sortable[data-order!=""]');
        let sortCol = activeSortTh.data('sort') || ordenActualVendedores; 
        let sortDir = activeSortTh.data('order') || 'desc'; 


        try {
            const response = await $.ajax({
                url: 'Api/get_desempeno_vendedores.php', // Nombres de campo en JSON de esta API ya fueron corregidos
                type: 'GET',
                data: {
                    fecha_inicio: $(fechaInicioVendedoresEl).val(),
                    fecha_fin: $(fechaFinVendedoresEl).val(),
                    // orden: sortCol === 'cantidad_ventas' ? 'unidades' : 'monto' // API usa 'unidades' o 'monto'
                },
                dataType: 'json'
            });

            if (response.error) {
                displayMessage(tablaVendedoresContainerEl, response.error, 'danger');
                $('#' + tablaContainerId + ' table tbody').empty();
                return;
            }
            // Data will be sorted client-side by renderTablaVendedores
            renderTablaVendedores(response.data, sortCol, sortDir);

        } catch (error) {
            console.error("Error al cargar datos de vendedores:", error);
            displayMessage(tablaVendedoresContainerEl, `Error al cargar datos de vendedores: ${error.statusText || error.message || 'Error desconocido'}`, 'danger');
            $('#' + tablaContainerId + ' table tbody').empty();
        } finally {
            if (isAutoRefresh) {
                setButtonLoadingState(buttonId, false);
            } else {
                hideOverlayLoading(loadingOverlayId);
            }
        }
    }

    function setupTableSorting() {
        const container = $('#tablaDesempenoVendedoresContainer');
        container.off('click', 'th.sortable').on('click', 'th.sortable', function() {
            const clickedHeader = $(this);
            const sortColumn = clickedHeader.data('sort');
            let currentOrder = clickedHeader.data('order');

            // Reset other headers
            container.find('th.sortable').not(clickedHeader).data('order', '').find('i.fas').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');

            // Toggle order for clicked header
            if (currentOrder === 'asc') {
                currentOrder = 'desc';
                clickedHeader.find('i.fas').removeClass('fa-sort fa-sort-up').addClass('fa-sort-down');
            } else { // Was 'desc' or unset
                currentOrder = 'asc';
                clickedHeader.find('i.fas').removeClass('fa-sort fa-sort-down').addClass('fa-sort-up');
            }
            clickedHeader.data('order', currentOrder);
            ordenActualVendedores = sortColumn; 

            cargarYRenderizarTablaVendedores(false); 
        });
    }

    function setupIndividualAutoRefresh(checkboxId, refreshFunction, intervalVarName, buttonId) {
        const autoRefreshCheckbox = document.getElementById(checkboxId);
        if (!autoRefreshCheckbox) return;

        // Clear existing interval and listener if re-initializing
        if (window[intervalVarName]) {
            clearInterval(window[intervalVarName]);
            window[intervalVarName] = null;
        }
        // Use a unique property to store the handler reference to avoid issues with anonymous functions
        const handlerKey = `_handleChange_${checkboxId}`;

        if (autoRefreshCheckbox[handlerKey]) {
            autoRefreshCheckbox.removeEventListener('change', autoRefreshCheckbox[handlerKey]);
        }

        autoRefreshCheckbox[handlerKey] = function() {
            if (this.checked) {
                if (window[intervalVarName]) clearInterval(window[intervalVarName]); // Defensive clear
                refreshFunction(true); // Initial call when checked
                window[intervalVarName] = setInterval(() => refreshFunction(true), 15000); // 5 seconds
                setButtonLoadingState(buttonId, false); 
            } else {
                clearInterval(window[intervalVarName]);
                window[intervalVarName] = null;
                setButtonLoadingState(buttonId, false); 
            }
        };
        autoRefreshCheckbox.addEventListener('change', autoRefreshCheckbox[handlerKey]);

        // Apply current state if checkbox is already checked on load
        if (autoRefreshCheckbox.checked && !window[intervalVarName]) {
             autoRefreshCheckbox[handlerKey].call(autoRefreshCheckbox);
        }
    }

    function initializeEstadisticasPage() {
        $('#btnActualizarGraficoSemanal').off('click').on('click', () => cargarYRenderizarGraficoDiario(false));
        $('#btnActualizarGraficoHoras').off('click').on('click', () => cargarDatosPorHora(false));
        $('#btnActualizarTablaVendedores').off('click').on('click', () => cargarYRenderizarTablaVendedores(false));

        // Initial loads (manual-like behavior)
        cargarYRenderizarGraficoDiario(false);
        cargarDatosPorHora(false);
        generarTablaVendedoresHeader(); 
        cargarYRenderizarTablaVendedores(false);
        
        // Setup auto-refresh mechanisms
        setupIndividualAutoRefresh('chkAutoRefreshVentasDiarias', cargarYRenderizarGraficoDiario, 'ventasDiariasIntervalId', 'btnActualizarGraficoSemanal');
        setupIndividualAutoRefresh('chkAutoRefreshVentasPorHora', cargarDatosPorHora, 'ventasPorHoraIntervalId', 'btnActualizarGraficoHoras');
        setupIndividualAutoRefresh('chkAutoRefreshTablaVendedores', cargarYRenderizarTablaVendedores, 'tablaVendedoresIntervalId', 'btnActualizarTablaVendedores');

    }

    window.reinitializeEstadisticasPage = function() {
        // Clear intervals
        if (ventasDiariasIntervalId) clearInterval(ventasDiariasIntervalId);
        if (ventasPorHoraIntervalId) clearInterval(ventasPorHoraIntervalId);
        if (tablaVendedoresIntervalId) clearInterval(tablaVendedoresIntervalId);
        ventasDiariasIntervalId = null;
        ventasPorHoraIntervalId = null;
        tablaVendedoresIntervalId = null;

        // Reset button spinners and remove listeners from checkboxes
        [
            {chkId: 'chkAutoRefreshVentasDiarias', btnId: 'btnActualizarGraficoSemanal'},
            {chkId: 'chkAutoRefreshVentasPorHora', btnId: 'btnActualizarGraficoHoras'},
            {chkId: 'chkAutoRefreshTablaVendedores', btnId: 'btnActualizarTablaVendedores'}
        ].forEach(item => {
            const chk = document.getElementById(item.chkId);
            if (chk) {
                 const handlerKey = `_handleChange_${item.chkId}`;
                if (chk[handlerKey]) {
                    chk.removeEventListener('change', chk[handlerKey]);
                    chk[handlerKey] = null;
                }
            }
            setButtonLoadingState(item.btnId, false);
        });

        if (chartVentasDiarias) { chartVentasDiarias.destroy(); chartVentasDiarias = null; }
        if (chartVentasPorHora) { chartVentasPorHora.destroy(); chartVentasPorHora = null; }
        $('#tablaDesempenoVendedoresContainer').empty(); 

        initializeEstadisticasPage();
    };

    $(document).ready(function() {
        // Verifica si los elementos clave de la página de estadísticas están presentes
        // Esto es para asegurar que este script solo se inicialice completamente si la vista actual es 'estadisticas'
        if (document.getElementById('graficoVentasSemanales') || document.getElementById('tablaDesempenoVendedoresContainer')) {
            initializeEstadisticasPage();
        }
    });

})();
</script>