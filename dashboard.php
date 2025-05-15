<?php
// Este archivo es incluido por index.php (vía el gestor AJAX)
// Las variables de sesión como $rol_usuario, $es_administrador ya están disponibles
// si fueron definidas en el script que incluye este (index.php en el bloque AJAX).
global $es_administrador; 
?>
<section class="content-header">
  <div class="container-fluid px-3">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Consulta de ventas por sucursal</h1>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid px-3">

    <div class="card card-outline card-primary">
      <div class="card-header">
        <h3 class="card-title">Filtrar por Fecha</h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="filters-container">
          <div class="form-group">
            <label for="startDate" class="form-label">Fecha de Inicio</label>
            <input type="date" id="startDate" class="form-control">
          </div>
          <div class="form-group">
            <label for="endDate" class="form-label">Fecha de Fin</label>
            <input type="date" id="endDate" class="form-control">
          </div>
          <div class="form-group mb-0 align-self-end">
            <button id="btnRanking" class="btn btn-primary">
              <i class="fas fa-chart-bar"></i> Consultar
            </button>
            <label style="margin-left:10px;">
              <input type="checkbox" id="chkAutoRefreshDashboard"> Auto Refresh
            </label>
          </div>
        </div>
      </div>
    </div>

    <div class="card card-outline card-info">
      <div class="card-header">
        <h3 class="card-title">Totales Generales</h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="row info-box-row">
          <?php if ($es_administrador): ?>
          <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-warehouse"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Costo Vendido</span>
                <span class="info-box-number" id="grandTotalCOGS">$0.00</span>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-success elevation-1"><i class="fas fa-dollar-sign"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Monto Ventas</span>
                <span class="info-box-number" id="grandTotalSalesAmount">$0.00</span>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-boxes"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Total Unidades</span>
                <span class="info-box-number" id="grandTotalUnits">0</span>
              </div>
            </div>
          </div>
          <?php if ($es_administrador): ?>
          <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-info elevation-1"><i class="fas fa-piggy-bank"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Margen Bruto ($)</span>
                <span class="info-box-number" id="grandTotalGrossProfitAmount">$0.00</span>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 col-sm-6"> <div class="info-box">
              <span class="info-box-icon bg-purple elevation-1"><i class="fas fa-percentage"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Margen Bruto (%)</span>
                <span class="info-box-number" id="grandTotalGrossProfitPercent">0.00%</span>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <div class="col-lg-4 col-md-6 col-sm-6">
            <div class="info-box info-box-vendedor-estrella">
              <span class="info-box-icon bg-info elevation-1">
                <i class="fas fa-star" style="color: #fff;"></i>
              </span>
              <div class="info-box-content">
                <span class="info-box-text">Vendedor Estrella (Unidades)</span>
                <span class="info-box-number" id="vendedorEstrellaNombre">N/A</span>
                <span class="vendedor-detalle-subtext" id="vendedorEstrellaUnidades">(0 unidades)</span>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 col-sm-6">
            <div class="info-box info-box-vendedor-oro">
              <span class="info-box-icon bg-warning elevation-1">
                <i class="fas fa-crown" style="color: #fff;"></i>
              </span>
              <div class="info-box-content">
                <span class="info-box-text">Vendedor Oro (Ventas)</span>
                <span class="info-box-number" id="vendedorOroNombre">N/A</span>
                <span class="vendedor-detalle-subtext" id="vendedorOroMonto">($0.00)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card card-outline card-success">
      <div class="card-header">
        <h3 class="card-title">Resultados por Sucursal</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="rankingTable" class="table table-hover table-striped table-head-fixed text-nowrap">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Sucursal</th>
                <th class="text-end">Total Unidades</th>
                <th class="text-end">Monto Venta</th>
                <?php if ($es_administrador): ?>
                <th class="text-end">COGS Suc.</th>
                <th class="text-end">Margen Suc. ($)</th>
                <th class="text-end">Margen Suc. (%)</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</section>
<script>
    // Este script se ejecutará cuando dashboard.php se cargue en el DOM.
    // Es un buen lugar para indicar que los controles del dashboard deben inicializarse.
    if (typeof initializeDashboardControls === 'function') {
        // console.log('Dashboard content fully loaded, calling initializeDashboardControls().');
        initializeDashboardControls(); // Esto llamará a setupDashboardAutoRefresh internamente
    } else {
        // console.warn('initializeDashboardControls function not found. Dashboard JS might need manual trigger or check load order.');
    }
    // Re-inicializar widgets de AdminLTE que puedan estar en este contenido cargado.
    $(function () {
      try {
        $('[data-card-widget="collapse"]').each(function() {
            const $this = $(this);
            if ($.fn.CardWidget && !$this.data('cardwidget_initialized')) { 
                $this.CardWidget('init');
                $this.data('cardwidget_initialized', true); 
            }
        });
      } catch(e) {
        console.error("Error inicializando CardWidget en dashboard.php:", e);
      }
    });
</script>