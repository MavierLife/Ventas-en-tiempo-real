// Js/ranking-ui.js

function formatCurrency(amount, currencyCode = 'USD') {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: currencyCode,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

function showGlobalLoader(globalLoadingOverlay, mainStatusEl, subStatusEl, mainMessage, subMessage, grandTotalElementsRef, tbodyRef) {
    const isAdminUser = typeof IS_ADMIN !== 'undefined' ? IS_ADMIN : true; // Asumir admin si no está definido, para no romper la UI vieja
    const colSpan = isAdminUser ? 7 : 4; // Ajustar colspan según el rol

    if (globalLoadingOverlay && mainStatusEl) {
        mainStatusEl.textContent = mainMessage;
        if (subStatusEl) {
            subStatusEl.textContent = subMessage || "";
        }
        globalLoadingOverlay.style.display = "flex";
    } else {
        if (tbodyRef) {
            let messageHTML = `<p style="text-align:center; padding:20px;">${mainMessage}`;
            if (subMessage) {
                messageHTML += `<br><small style="color:#555;">${subMessage}</small>`;
            }
            messageHTML += `</p>`;
            tbodyRef.innerHTML = `<tr class="loading-row"><td colspan="${colSpan}">${messageHTML}</td></tr>`;
        }
    }

    for (const key in grandTotalElementsRef) {
        if (grandTotalElementsRef[key]) {
            // Ocultar elementos específicos si no es admin y el elemento existe
            if (!isAdminUser) {
                if (key === 'grandTotalCOGsEl' || key === 'grandTotalGrossProfitAmountEl' || key === 'grandTotalGrossProfitPercentEl') {
                    if (grandTotalElementsRef[key].closest('.col-lg-3, .col-lg-4')) { // Buscar el contenedor de columna
                        grandTotalElementsRef[key].closest('.col-lg-3, .col-lg-4').style.display = 'none';
                    }
                    continue; // No establecer 'Calculando...' para estos si no es admin
                }
            }
            if (key === 'vendedorEstrellaUnidadesEl' || key === 'vendedorOroMontoEl') {
                 grandTotalElementsRef[key].textContent = '(Calculando...)';
            } else {
                 grandTotalElementsRef[key].textContent = 'Calculando...';
            }
        }
    }
}

function updateGlobalLoaderMessage(globalLoadingOverlay, subStatusEl, newSubMessage, tbodyRef) {
    if (globalLoadingOverlay && globalLoadingOverlay.style.display !== "none") {
        if (subStatusEl) {
            subStatusEl.textContent = newSubMessage;
        }
    } else {
        const loadingRowSmall = tbodyRef ? tbodyRef.querySelector(".loading-row small") : null;
        if (loadingRowSmall) {
            loadingRowSmall.textContent = newSubMessage;
        } else {
            const loadingRowP = tbodyRef ? tbodyRef.querySelector(".loading-row p") : null;
            if (loadingRowP) loadingRowP.textContent = newSubMessage;
        }
    }
}

function hideGlobalLoader(globalLoadingOverlay, mainStatusEl, subStatusEl) {
    if (globalLoadingOverlay) {
        globalLoadingOverlay.style.display = "none";
        if (mainStatusEl) mainStatusEl.textContent = "Procesando Solicitud...";
        if (subStatusEl) subStatusEl.textContent = "";
    }
}

function renderSummariesTable(tbody, summaryData, grandTotalElements, handleToggleDetails) {
    const isAdminUser = summaryData.user_is_admin; // Usar el flag de la respuesta de la API

    // Totales Generales
    grandTotalElements.grandTotalUnitsEl.textContent = Number(summaryData.grand_total_unidades || 0).toLocaleString("es-ES");
    grandTotalElements.grandTotalSalesAmountEl.textContent = formatCurrency(summaryData.grand_total_monto_ventas || 0);

    if (isAdminUser) {
        if (grandTotalElements.grandTotalCOGsEl) grandTotalElements.grandTotalCOGsEl.textContent = formatCurrency(summaryData.grand_total_cogs || 0);
        if (grandTotalElements.grandTotalGrossProfitAmountEl) grandTotalElements.grandTotalGrossProfitAmountEl.textContent = formatCurrency(summaryData.grand_total_margen_bruto_monto || 0);
        if (grandTotalElements.grandTotalGrossProfitPercentEl) grandTotalElements.grandTotalGrossProfitPercentEl.textContent = `${Number(summaryData.grand_total_margen_bruto_porcentaje || 0).toFixed(2)}%`;
    } else {
        // Asegurarse de que los contenedores de estos elementos estén ocultos si no es admin (ya manejado por showGlobalLoader y CSS en index.php)
    }

    // Vendedor Estrella
    if (grandTotalElements.vendedorEstrellaNombreEl) {
        grandTotalElements.vendedorEstrellaNombreEl.textContent = summaryData.vendedor_estrella_nombre || "N/A";
    }
    if (grandTotalElements.vendedorEstrellaUnidadesEl) {
        const unidadesText = (summaryData.vendedor_estrella_nombre && summaryData.vendedor_estrella_nombre !== "N/A")
            ? `(${Number(summaryData.vendedor_estrella_unidades || 0).toLocaleString("es-ES")} unidades)`
            : "(0 unidades)";
        grandTotalElements.vendedorEstrellaUnidadesEl.textContent = unidadesText;
    }

    // Vendedor Oro
    if (grandTotalElements.vendedorOroNombreEl) {
        grandTotalElements.vendedorOroNombreEl.textContent = summaryData.vendedor_oro_nombre || "N/A";
    }
    if (grandTotalElements.vendedorOroMontoEl) {
         const montoText = (summaryData.vendedor_oro_nombre && summaryData.vendedor_oro_nombre !== "N/A")
            ? `(${formatCurrency(summaryData.vendedor_oro_monto || 0)})`
            : "($0.00)";
        grandTotalElements.vendedorOroMontoEl.textContent = montoText;
    }

    // Tabla de Sucursales
    tbody.innerHTML = "";
    const colSpan = isAdminUser ? 7 : 4;
    if (!summaryData.data || summaryData.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center p-3">No hay resúmenes disponibles para el rango de fechas seleccionado.</td></tr>`;
    } else {
        summaryData.data.forEach((item, idx) => {
            const tr = document.createElement("tr");
            tr.dataset.sucursalId = item.sucursal;

            let medalIcon = '';
            if (idx === 0) { medalIcon = '<i class="fas fa-medal medal-icon gold-medal" title="Primer Lugar"></i>'; }
            else if (idx === 1) { medalIcon = '<i class="fas fa-medal medal-icon silver-medal" title="Segundo Lugar"></i>'; }
            else if (idx === 2) { medalIcon = '<i class="fas fa-medal medal-icon bronze-medal" title="Tercer Lugar"></i>'; }

            let rowHTML = `
                <td>${idx + 1}</td>
                <td class="branch-cell" style="cursor:pointer;">${item.sucursal} ${medalIcon} <i class="fas fa-chevron-down fa-xs ms-1"></i></td>
                <td class="numeric text-end">${Number(item.total_unidades).toLocaleString("es-ES")}</td>
                <td class="numeric text-end">${formatCurrency(item.total_monto_venta)}</td>
            `;
            if (isAdminUser) {
                rowHTML += `
                    <td class="numeric text-end">${formatCurrency(item.total_cogs_sucursal)}</td>
                    <td class="numeric text-end">${formatCurrency(item.margen_bruto_sucursal_monto)}</td>
                    <td class="numeric text-end">${Number(item.margen_bruto_sucursal_porcentaje).toFixed(2)}%</td>
                `;
            }
            tr.innerHTML = rowHTML;
            tbody.appendChild(tr);

            const branchCell = tr.querySelector(".branch-cell");
            branchCell.addEventListener("click", () => handleToggleDetails(tr, item.sucursal));
        });
    }
}

function renderDetailsRow(tr, sucursalId, detalleProductos, isAdminUserDetail) { // isAdminUserDetail pasado desde la respuesta de detalle
    const isAdmin = typeof isAdminUserDetail !== 'undefined' ? isAdminUserDetail : (typeof IS_ADMIN !== 'undefined' ? IS_ADMIN : true);
    const colSpan = isAdmin ? 7 : 4; // Colspan de la fila principal
    const detailTableColSpan = isAdmin ? 10 : 7; // Colspan para "no hay detalle" dentro de la tabla de detalle

    const detailsRow = document.createElement("tr");
    detailsRow.classList.add("details-row");
    const detailsCell = document.createElement("td");
    detailsCell.colSpan = colSpan; // Usa el colspan de la fila principal
    detailsCell.style.padding = "0";

    let html = `
      <div class="p-2" style="background-color: #eef5ff;">
        <h6 class="mt-1 mb-2 ps-2">Detalle de Productos para ${sucursalId}:</h6>
        <div class="table-responsive">
        <table class="table table-sm table-hover table-bordered mb-0">
          <thead class="thead-light">
            <tr>
              <th>Código</th><th>Descripción</th>
              <th class="text-end">Últ.Venta</th><th>Usuario</th>
              <th class="text-end">Stock(F-U)</th><th class="text-end">Unid.Vend.</th>
              <th class="text-end">Monto Vta.</th>`;
    if (isAdmin) {
        html += `
              <th class="text-end">Costo Unit.</th>
              <th class="text-end">COGS Prod.</th><th class="text-end">Margen($)</th>`;
    }
    html += `</tr>
          </thead>
          <tbody>`;

    if (detalleProductos && detalleProductos.length > 0) {
        detalleProductos.forEach((prod) => {
            const stockFardos = prod.unidades_fardo_info > 0 ? Math.floor(prod.existencia / prod.unidades_fardo_info) : 0;
            const stockSobrante = prod.unidades_fardo_info > 0 ? prod.existencia % prod.unidades_fardo_info : prod.existencia;
            const stockDisplay = `${stockFardos}-${stockSobrante}`;
            html += `
            <tr>
                <td>${prod.CodigoPROD}</td><td>${prod.Descripcion}</td>
                <td class="text-end">${prod.ultima_venta ? new Date(prod.ultima_venta).toLocaleString('es-SV', {dateStyle:'short', timeStyle:'short'}) : 'N/A'}</td>
                <td>${prod.usuario || 'N/A'}</td>
                <td class="text-end">${stockDisplay}</td>
                <td class="text-end">${Number(prod.total_unidades_prod).toLocaleString("es-ES")}</td>
                <td class="text-end">${formatCurrency(prod.monto_total_prod)}</td>`;
            if (isAdmin) {
                html += `
                <td class="text-end">${formatCurrency(prod.costo_unitario_prod)}</td>
                <td class="text-end">${formatCurrency(prod.cogs_prod)}</td>
                <td class="text-end">${formatCurrency(prod.margen_bruto_prod)}</td>`;
            }
            html += `</tr>`;
        });
    } else {
        html += `<tr><td colspan="${detailTableColSpan}" class="text-center p-3">No hay detalle de productos para esta sucursal en el rango seleccionado.</td></tr>`;
    }
    html += `</tbody></table></div></div>`;
    detailsCell.innerHTML = html;
    detailsRow.appendChild(detailsCell);
    tr.parentNode.insertBefore(detailsRow, tr.nextSibling);
}

function renderErrorInTable(tbody, message, grandTotalElementsRef) {
    const isAdminUser = typeof IS_ADMIN !== 'undefined' ? IS_ADMIN : true;
    const colSpan = isAdminUser ? 7 : 4;

    tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center text-danger p-3">${message}</td></tr>`;
    for (const key in grandTotalElementsRef) {
        if (grandTotalElementsRef[key]) {
            if (!isAdminUser) {
                 if (key === 'grandTotalCOGsEl' || key === 'grandTotalGrossProfitAmountEl' || key === 'grandTotalGrossProfitPercentEl') {
                    if (grandTotalElementsRef[key].closest('.col-lg-3, .col-lg-4')) {
                        grandTotalElementsRef[key].closest('.col-lg-3, .col-lg-4').style.display = 'none';
                    }
                    continue;
                 }
            }
             if (key === 'vendedorEstrellaUnidadesEl' || key === 'vendedorOroMontoEl') {
                grandTotalElementsRef[key].textContent = '(Error)';
            } else {
                grandTotalElementsRef[key].textContent = 'Error';
            }
        }
    }
}

function createDetailsLoadingRow(tr, sucursalId) {
    const isAdminUser = typeof IS_ADMIN !== 'undefined' ? IS_ADMIN : true;
    const colSpan = isAdminUser ? 7 : 4;

    const detailsLoadingRow = document.createElement("tr");
    detailsLoadingRow.classList.add("details-loading-row");
    detailsLoadingRow.innerHTML = `
        <td colspan="${colSpan}">
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:15px 0px 15px 0px; background-color: #f0f0f0;">
                <div class="details-loading-animation">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <span style="margin-top:10px; font-size:0.9em; color:#333;">Cargando detalles para ${sucursalId}...</span>
            </div>
        </td>`;
    tr.parentNode.insertBefore(detailsLoadingRow, tr.nextSibling);
    return detailsLoadingRow;
}

function renderErrorInDetailsRow(detailsLoadingRow, message) {
    const isAdminUser = typeof IS_ADMIN !== 'undefined' ? IS_ADMIN : true;
    const colSpan = isAdminUser ? 7 : 4; // Debe coincidir con el colspan de la fila de carga
    detailsLoadingRow.innerHTML = `<td colspan="${colSpan}" class="text-center text-danger p-3">${message}</td>`;
}