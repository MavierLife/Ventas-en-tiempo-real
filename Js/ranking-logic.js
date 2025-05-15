// Js/ranking-logic.js

// Variables que podrían necesitar ser accesibles globalmente dentro de este script
// o que se reinician/revalúan en cada inicialización.
let timer = null; // Timer para el botón "Consultando" del dashboard, no para auto-refresh.
let openDetailsState = {};
let cachedDetails = {};
let dashboardAutoRefreshTimer = null;
let estadisticasAutoRefreshTimer = null;

// Referencias a elementos del DOM que se buscarán cuando se inicialice el dashboard.
let btnRanking, tbodyRanking, startDateInput, endDateInput;
let globalLoadingOverlayEl, mainLoadingStatusEl, subLoadingStatusEl; // Estos son globales de index.php
let grandTotalElementsRefs;

// Intervalos de Auto-Refresh (en milisegundos)
const DASHBOARD_REFRESH_INTERVAL = 15000; // 5 segundos (ajustar según necesidad)
const ESTADISTICAS_REFRESH_INTERVAL = 15000; // 5 segundos (ajustar según necesidad)


// Función principal para inicializar los controles y la lógica del dashboard
function initializeDashboardControls() {
    // console.log("Attempting to initialize dashboard controls...");

    btnRanking = document.getElementById("btnRanking");
    tbodyRanking = document.querySelector("#rankingTable tbody");
    startDateInput = document.getElementById("startDate");
    endDateInput = document.getElementById("endDate");

    globalLoadingOverlayEl = document.getElementById("globalLoadingOverlay");
    mainLoadingStatusEl = document.getElementById("globalLoadingStatus");
    subLoadingStatusEl = document.getElementById("globalLoadingSubStatus");

    grandTotalElementsRefs = {
        grandTotalUnitsEl: document.getElementById("grandTotalUnits"),
        grandTotalSalesAmountEl: document.getElementById("grandTotalSalesAmount"),
        grandTotalCOGsEl: document.getElementById("grandTotalCOGS"), // Puede ser null si no es admin
        grandTotalGrossProfitAmountEl: document.getElementById("grandTotalGrossProfitAmount"), // Puede ser null
        grandTotalGrossProfitPercentEl: document.getElementById("grandTotalGrossProfitPercent"), // Puede ser null
        vendedorEstrellaNombreEl: document.getElementById("vendedorEstrellaNombre"),
        vendedorEstrellaUnidadesEl: document.getElementById("vendedorEstrellaUnidades"),
        vendedorOroNombreEl: document.getElementById("vendedorOroNombre"),
        vendedorOroMontoEl: document.getElementById("vendedorOroMonto")
    };

    if (!btnRanking || !tbodyRanking || !startDateInput || !endDateInput) {
        // console.error("Error: No se encontraron todos los elementos necesarios para el dashboard. La inicialización falló.");
        return; 
    }
    // console.log("Dashboard elements found. Proceeding with initialization.");

    openDetailsState = {};
    cachedDetails = {};
    
    // Limpiar timer del botón "Consultando" (si existía para ese propósito)
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
    // Detener y limpiar el timer de auto-refresh del dashboard si se está reinicializando
    if (dashboardAutoRefreshTimer) {
        clearInterval(dashboardAutoRefreshTimer);
        dashboardAutoRefreshTimer = null; 
    }

    function getTodayCentral() {
        return new Date().toLocaleDateString("en-CA", { timeZone: "America/Guatemala" });
    }
    const today = getTodayCentral();
    startDateInput.value = today;
    endDateInput.value = today;
    if (startDateInput.max !== today) startDateInput.max = today;
    if (endDateInput.max !== today) endDateInput.max = today;
    endDateInput.min = startDateInput.value;

    $(startDateInput).off('change.dashboard').on('change.dashboard', () => {
        endDateInput.min = startDateInput.value;
        if (new Date(endDateInput.value) < new Date(startDateInput.value)) {
            endDateInput.value = startDateInput.value;
        }
    });

    $(endDateInput).off('change.dashboard').on('change.dashboard', () => {
        if (new Date(startDateInput.value) > new Date(endDateInput.value)) {
            startDateInput.value = endDateInput.value;
        }
        // Esta lógica es para el estado visual del botón "Consultar", no para el auto-refresh del checkbox
        if (endDateInput.value !== getTodayCentral() && timer) { 
            clearInterval(timer);
            timer = null;
            if (btnRanking) {
                 btnRanking.textContent = "Consultar";
                 btnRanking.classList.remove("consultando");
            }
        }
    });

    $(btnRanking).off('click.dashboard').on('click.dashboard', () => {
        fetchAndRenderMainData(); 
        if (timer) clearInterval(timer); // Limpiar timer del botón anterior
        if (endDateInput.value === getTodayCentral()) {
            btnRanking.classList.add("consultando");
            // Lógica original del timer para auto-actualización del BOTÓN (no el checkbox)
            // timer = setInterval(fetchAndRenderMainData, 60000); 
        } else {
            btnRanking.textContent = "Consultar";
            btnRanking.classList.remove("consultando");
        }
    });

    fetchAndRenderMainData();
    if (endDateInput.value === getTodayCentral() && btnRanking) {
        btnRanking.classList.add("consultando");
        // timer = setInterval(fetchAndRenderMainData, 60000); // idem
    }
    
    // Configurar el auto-refresh para el dashboard (basado en checkbox)
    setupDashboardAutoRefresh();

    // console.log("Dashboard controls initialized successfully.");
}

// --- LÓGICA DE AUTO-REFRESH PARA EL DASHBOARD ---
function setupDashboardAutoRefresh() {
    const autoRefreshCheckbox = document.getElementById("chkAutoRefreshDashboard");
    if (!autoRefreshCheckbox) {
        // console.warn("Checkbox 'chkAutoRefreshDashboard' no encontrado. El auto-refresh del dashboard no se activará.");
        return;
    }

    // Remover listener anterior para evitar duplicados si initializeDashboardControls se llama de nuevo
    autoRefreshCheckbox.removeEventListener('change', handleDashboardAutoRefreshChange);
    autoRefreshCheckbox.addEventListener('change', handleDashboardAutoRefreshChange);

    // Sincronizar el estado del timer con el estado actual del checkbox (al cargar/reinicializar)
    if (autoRefreshCheckbox.checked) {
        startDashboardRefreshInterval();
    } else {
        stopDashboardRefreshInterval(); // Asegura que esté detenido si no está marcado
    }
}

function handleDashboardAutoRefreshChange() { // 'this' se refiere al checkbox
    if (this.checked) {
        startDashboardRefreshInterval();
    } else {
        stopDashboardRefreshInterval();
    }
}

function startDashboardRefreshInterval() {
    if (dashboardAutoRefreshTimer) clearInterval(dashboardAutoRefreshTimer); // Limpiar si ya existe uno
    
    console.log(`Dashboard auto-refresh activado. Intervalo: ${DASHBOARD_REFRESH_INTERVAL}ms`);
    dashboardAutoRefreshTimer = setInterval(() => {
        console.log("Dashboard auto-refresh triggered by interval.");
        if (typeof fetchAndRenderMainData === 'function') {
            fetchAndRenderMainData();
        } else {
            console.error("fetchAndRenderMainData no está definida. Deteniendo auto-refresh del dashboard.");
            stopDashboardRefreshInterval();
            const chk = document.getElementById("chkAutoRefreshDashboard");
            if (chk) chk.checked = false; // Desmarcar para reflejar el problema
        }
    }, DASHBOARD_REFRESH_INTERVAL);
}

function stopDashboardRefreshInterval() {
    if (dashboardAutoRefreshTimer) {
        clearInterval(dashboardAutoRefreshTimer);
        dashboardAutoRefreshTimer = null;
        console.log("Dashboard auto-refresh desactivado.");
    }
}

// --- LÓGICA DE AUTO-REFRESH PARA ESTADÍSTICAS (llamada desde estadisticas.php) ---
function setupEstadisticasAutoRefresh() {
    const autoRefreshCheckbox = document.getElementById("chkAutoRefreshEstadisticas");
    if (!autoRefreshCheckbox) {
        console.warn("Checkbox 'chkAutoRefreshEstadisticas' no encontrado. El auto-refresh de estadísticas no se activará.");
        return;
    }
    
    // Limpiar cualquier timer de estadísticas existente al (re)configurar
    if (estadisticasAutoRefreshTimer) {
        clearInterval(estadisticasAutoRefreshTimer);
        estadisticasAutoRefreshTimer = null;
    }

    // Remover listener anterior para evitar duplicados
    autoRefreshCheckbox.removeEventListener('change', handleEstadisticasAutoRefreshChange);
    autoRefreshCheckbox.addEventListener('change', handleEstadisticasAutoRefreshChange);
    
    // Sincronizar el estado del timer con el estado actual del checkbox
    if (autoRefreshCheckbox.checked) {
        startEstadisticasRefreshInterval();
    } else {
        stopEstadisticasRefreshInterval(); // Asegura que esté detenido si no está marcado
    }
}

function handleEstadisticasAutoRefreshChange() { // 'this' se refiere al checkbox
    if (this.checked) {
        startEstadisticasRefreshInterval();
    } else {
        stopEstadisticasRefreshInterval();
    }
}

function startEstadisticasRefreshInterval() {
    if (estadisticasAutoRefreshTimer) clearInterval(estadisticasAutoRefreshTimer); // Limpiar si ya existe

    console.log(`Estadísticas auto-refresh activado. Intervalo: ${ESTADISTICAS_REFRESH_INTERVAL}ms`);
    estadisticasAutoRefreshTimer = setInterval(() => {
        console.log("Estadísticas auto-refresh triggered by interval.");
        let allFunctionsAvailable = true;
        if (typeof cargarDatosPorHora === 'function') {
            cargarDatosPorHora();
        } else {
            // console.warn("Función cargarDatosPorHora no encontrada para auto-refresh de estadísticas.");
            allFunctionsAvailable = false; 
            // No es un error crítico si la función no existe, podría ser intencional
            // que solo una parte de las estadísticas se refresque.
        }
        if (typeof cargarYRenderizarTablaVendedores === 'function') {
            cargarYRenderizarTablaVendedores();
        } else {
            // console.warn("Función cargarYRenderizarTablaVendedores no encontrada para auto-refresh de estadísticas.");
            allFunctionsAvailable = false;
        }

        // Si NINGUNA función relevante está disponible, detener el timer.
        // (Considerar si es mejor detenerlo incluso si solo una falta)
        if (!allFunctionsAvailable && typeof cargarDatosPorHora !== 'function' && typeof cargarYRenderizarTablaVendedores !== 'function') {
            console.error("Ninguna función de actualización de estadísticas encontrada. Deteniendo auto-refresh de estadísticas.");
            stopEstadisticasRefreshInterval();
            const chk = document.getElementById("chkAutoRefreshEstadisticas");
            if (chk) chk.checked = false; // Desmarcar
        }
    }, ESTADISTICAS_REFRESH_INTERVAL);
}

function stopEstadisticasRefreshInterval() {
    if (estadisticasAutoRefreshTimer) {
        clearInterval(estadisticasAutoRefreshTimer);
        estadisticasAutoRefreshTimer = null;
        console.log("Estadísticas auto-refresh desactivado.");
    }
}

// --- Resto de las funciones (fetchSummaries, fetchBranchDetails, fetchAndRenderMainData, handleToggleBranchDetails) ---
// (Estas funciones permanecen igual que en tu archivo original, asumiendo que son correctas para su propósito)
async function fetchSummaries(startDate, endDate) {
    const url = `Api/ranking_all.php?startDate=${encodeURIComponent(startDate)}&endDate=${encodeURIComponent(endDate)}`;
    if (typeof updateGlobalLoaderMessage === 'function') updateGlobalLoaderMessage(globalLoadingOverlayEl, subLoadingStatusEl, "Consultando al servidor: Resúmenes generales...", tbodyRanking);
    
    const res = await fetch(url);
    if (typeof updateGlobalLoaderMessage === 'function') updateGlobalLoaderMessage(globalLoadingOverlayEl, subLoadingStatusEl, "Procesando respuesta del servidor: Resúmenes generales...", tbodyRanking);
    
    if (!res.ok) {
        const errText = await res.text();
        let errorDetail = 'Error desconocido del servidor';
        try {
            const errData = JSON.parse(errText);
            errorDetail = errData.error || errText;
        } catch (e) {
            errorDetail = errText || errorDetail;
        }
        throw new Error(`HTTP ${res.status}: ${errorDetail}`);
    }
    const resp = await res.json();
    if (resp.error) {
        throw new Error(resp.error);
    }
    return resp;
}

async function fetchBranchDetails(sucursalId, startDate, endDate) {
    const detailsUrl = `Api/ranking_detalle_sucursal.php?sucursal=${encodeURIComponent(sucursalId)}&startDate=${encodeURIComponent(startDate)}&endDate=${encodeURIComponent(endDate)}`;
    const res = await fetch(detailsUrl);
    if (!res.ok) {
        const errText = await res.text();
        let errorDetail = 'Error desconocido al obtener detalles.';
        try { const errData = JSON.parse(errText); errorDetail = errData.error || errText; }
        catch (e) { errorDetail = errText || errorDetail; }
        throw new Error(`HTTP ${res.status}: ${errorDetail}`);
    }
    const resp = await res.json();
    if (resp.error) {
        throw new Error(resp.error);
    }
    return resp;
}

async function fetchAndRenderMainData() {
    if (!startDateInput || !endDateInput) {
        // console.error("Inputs de fecha no disponibles para fetchAndRenderMainData");
        return;
    }
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;

    if (!startDate || !endDate) {
        // alert("Por favor, seleccione ambas fechas."); // Evitar alert en auto-refresh
        console.warn("Fechas no seleccionadas para fetchAndRenderMainData. Refresco automático podría no mostrar datos esperados.");
        return;
    }

    // No mostrar el loader global en cada auto-refresh para que sea menos intrusivo,
    // a menos que sea una acción manual del usuario.
    // Podríamos añadir una bandera para diferenciar si es auto-refresh o no.
    // Por ahora, se mantiene como estaba, pero considera esto para la UX.
    if (typeof showGlobalLoader === 'function') {
        showGlobalLoader(
            globalLoadingOverlayEl,
            mainLoadingStatusEl,
            subLoadingStatusEl,
            "Actualizando Datos...", // Mensaje ligeramente diferente para refresh
            "Consultando servidor...",
            grandTotalElementsRefs,
            tbodyRanking 
        );
    }

    openDetailsState = {}; 
    cachedDetails = {}; 

    try {
        const summaryData = await fetchSummaries(startDate, endDate);
        if (typeof updateGlobalLoaderMessage === 'function') updateGlobalLoaderMessage(globalLoadingOverlayEl, subLoadingStatusEl, "Visualizando datos actualizados...", tbodyRanking);
        
        if (typeof renderSummariesTable === 'function') {
             renderSummariesTable(tbodyRanking, summaryData, grandTotalElementsRefs, handleToggleBranchDetails);
        }

        if (typeof updateGlobalLoaderMessage === 'function') updateGlobalLoaderMessage(globalLoadingOverlayEl, subLoadingStatusEl, "¡Actualización completada!", tbodyRanking);
    } catch (err) {
        // console.error("Error en fetchAndRenderMainData (auto-refresh?):", err);
        if (mainLoadingStatusEl) mainLoadingStatusEl.textContent = "Error en Actualización";
        
        let errorMessage = "Ocurrió un error durante la actualización.";
        if (err && err.message) errorMessage = err.message;
        else if (typeof err === 'string') errorMessage = err;

        if (typeof updateGlobalLoaderMessage === 'function') updateGlobalLoaderMessage(globalLoadingOverlayEl, subLoadingStatusEl, `Detalle: ${errorMessage}`, tbodyRanking);
        if (typeof renderErrorInTable === 'function') renderErrorInTable(tbodyRanking, `Error al actualizar resúmenes: ${errorMessage}`, grandTotalElementsRefs);
    } finally {
        if (typeof hideGlobalLoader === 'function') {
            setTimeout(() => {
                hideGlobalLoader(globalLoadingOverlayEl, mainLoadingStatusEl, subLoadingStatusEl);
            }, 500); // Tiempo reducido para ocultar el loader más rápido en refresh
        }
    }
}

async function handleToggleBranchDetails(tr, sucursalId) {
    if (!startDateInput || !endDateInput) {
        // console.error("Inputs de fecha no disponibles para handleToggleBranchDetails");
        return;
    }
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;
    const nextRow = tr.nextElementSibling;
    const icon = tr.querySelector('.branch-cell i.fa-chevron-down');

    if (nextRow && (nextRow.classList.contains("details-row") || nextRow.classList.contains("details-loading-row"))) {
        nextRow.remove();
        openDetailsState[sucursalId] = false;
        if(icon) icon.classList.remove('rotated');
        return;
    }

    if(icon) icon.classList.add('rotated');
    openDetailsState[sucursalId] = true;
    
    const detailsLoadingRow = typeof createDetailsLoadingRow === 'function' ? createDetailsLoadingRow(tr, sucursalId) : null;
    if (!detailsLoadingRow) return;

    if (cachedDetails[sucursalId]) {
        if (detailsLoadingRow.parentNode) detailsLoadingRow.remove();
        if (typeof renderDetailsRow === 'function') renderDetailsRow(tr, sucursalId, cachedDetails[sucursalId].detalle, cachedDetails[sucursalId].user_is_admin);
        return;
    }

    try {
        const detailsData = await fetchBranchDetails(sucursalId, startDate, endDate);
        if (detailsLoadingRow.parentNode) detailsLoadingRow.remove();
        if (openDetailsState[sucursalId]) { 
            cachedDetails[sucursalId] = detailsData;
            if (typeof renderDetailsRow === 'function') renderDetailsRow(tr, sucursalId, detailsData.detalle, detailsData.user_is_admin);
        }
    } catch (err) {
        // console.error(`Error fetching details for ${sucursalId}:`, err);
        if (detailsLoadingRow.parentNode && openDetailsState[sucursalId]) {
             if (typeof renderErrorInDetailsRow === 'function') renderErrorInDetailsRow(detailsLoadingRow, `Error al cargar detalles para ${sucursalId}.`);
        } else if (detailsLoadingRow.parentNode) {
            detailsLoadingRow.remove(); 
        }
    }
}

// NO llamar a initializeDashboardControls() aquí directamente.
// Será llamado por el script en dashboard.php después de cargar la vista.
// Los event listeners DOMContentLoaded y delegados para chkAutoRefreshEstadisticas que estaban
// al final del archivo original han sido eliminados, ya que su lógica ahora está integrada
// en las funciones setup...AutoRefresh y llamadas desde los respectivos PHP.