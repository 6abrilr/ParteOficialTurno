<?php
// public/index.php
date_default_timezone_set('America/Argentina/Buenos_Aires');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Parte de Novedades – B Com 602</title>

  <link rel="icon" type="image/png" href="img/escudo602.png">
  <link rel="shortcut icon" href="img/escudo602.png">

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body{ background:#fbfbfc; }
    .card{ border-radius:12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .section-title{ font-weight:600; font-size:18px; }
    .estado-badge{ font-size:12px; color:#6c757d; }
    .tr-estado-NUEVA   { background:#fff7e6; }
    .tr-estado-RESUELTA{ background:#eefaf1; }
    .tr-estado-ACTUALIZADA{}
    /* estilos visuales para celdas editables */
    #tblLTA td[contenteditable="true"],
    .table-editable td[contenteditable="true"]{
      outline: 1px dashed #ced4da;
    }
    #tblLTA td[contenteditable="true"]:focus,
    .table-editable td[contenteditable="true"]:focus{
      outline: 2px solid #86b7fe;
      background: #f8fbff;
    }
  </style>
</head>
<body>
<div class="container my-4">

  <h1 class="h4 mb-3">PARTE DE NOVEDADES – Batallón de Comunicaciones 602</h1>

  <!-- =============== GENERADOR (ÚNICO BLOQUE) =============== -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="section-title">Generar Parte del Arma (automático)</div>
      </div>

      <!-- Encabezado del parte (obligatorio) -->
      <div class="row g-3">
        <div class="col-sm-6 col-lg-3">
          <label class="form-label">Desde</label>
          <input type="datetime-local" class="form-control" id="g_desde" required>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label">Hasta</label>
          <input type="datetime-local" class="form-control" id="g_hasta" required>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label">Oficial de turno</label>
          <input type="text" class="form-control" id="g_oficial" placeholder="Ej: ST ROJAS">
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label">Suboficial de turno</label>
          <input type="text" class="form-control" id="g_subof" placeholder="Ej: CB MARTINEZ">
        </div>
      </div>

      <div class="mt-3">
        <button class="btn btn-primary btn-sm" id="btnGuardarEncGen">Guardar encabezado</button>
        <small class="text-muted ms-2">Primero guardá el encabezado; luego seguí con LTA/CENOPE y sistemas.</small>
      </div>

      <hr class="my-4">

      <!-- ===== LTA ===== -->
      <div class="mb-3">
        <div class="section-title mb-2">Archivo LTA (DOCX/PDF)</div>
        <div class="row g-2 align-items-center">
          <div class="col-md-6">
            <input type="file" class="form-control" id="p_lta" accept=".doc,.docx,.pdf,application/pdf">
          </div>
          <div class="col-auto">
            <button class="btn btn-outline-secondary btn-sm" id="btnPrevLTA">Previsualizar</button>
          </div>
          <div class="col-auto">
            <button class="btn btn-success btn-sm" id="btnImpLTA" disabled>Guardar LTA</button>
          </div>
          <div class="col">
            <div id="ltaInfo" class="small text-muted"></div>
          </div>
        </div>

        <!-- Previsualización LTA editable -->
        <div class="mt-3" id="ltaPreview"></div>
      </div>

      <hr class="my-4">

      <!-- ===== CENOPE ===== -->
      <div class="mb-3">
        <div class="section-title mb-2">Archivo CENOPE (PDF)</div>
        <div class="row g-2 align-items-center">
          <div class="col-md-6">
            <input type="file" class="form-control" id="p_cenope" accept=".pdf,application/pdf">
          </div>
          <div class="col-auto">
            <button class="btn btn-outline-secondary btn-sm" id="btnPrevCenope">Previsualizar</button>
          </div>
          <div class="col-auto">
            <button class="btn btn-success btn-sm" id="btnImpCenope" disabled>Guardar CENOPE</button>
          </div>
          <div class="col">
            <div id="cenopeInfo" class="small text-muted"></div>
          </div>
        </div>

        <!-- Previsualización CENOPE (se llena desde app.js en #previewTables) -->
        <div class="mt-3" id="previewTables"></div>
      </div>

      <hr class="my-4">

      <!-- ===== Sistemas ===== -->
      <div class="section-title mb-2">Sistemas</div>

      <div class="mb-3">
        <h6 class="mb-2">Sistemas – Servicios</h6>
        <div id="tblServ"></div>
      </div>

      <div class="mb-3">
        <h6 class="mb-2">Sistemas – ISP Edificio Libertador</h6>
        <div id="tblIsp"></div>
      </div>

      <div class="mb-3">
        <h6 class="mb-2">SITELPAR</h6>
        <div id="tblSitelpar"></div>
      </div>

      <div class="mb-3">
        <h6 class="mb-2">Sistemas – Data Center</h6>
        <div id="tblDC"></div>
      </div>

      <div class="mb-4">
        <h6 class="mb-2">Sistemas – SITM2</h6>
        <div id="tblSITM2"></div>
      </div>

      <div class="mb-4">
        <button class="btn btn-outline-primary btn-sm" id="btnConfirmSistemas">Confirmar sistemas (revisado)</button>
      </div>

      <hr class="my-4">

      <!-- ===== Generar Parte ===== -->
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary" id="btnGenerarParte" disabled>Generar Parte</button>
        <a id="lnkParteHTML" class="btn btn-outline-secondary d-none" target="_blank">Ver HTML</a>
        <a id="lnkPartePDF"  class="btn btn-outline-secondary d-none" target="_blank">Descargar PDF</a>
        <span id="parteInfo" class="ms-2 small text-muted"></span>
      </div>
    </div>
  </div>

  <!-- Panel de pendientes (opcional: se puede mantener) -->
  <div class="card">
    <div class="card-body">
      <div class="section-title mb-2">Pendientes (oculta resueltas)</div>
      <div class="small text-muted">Refresca automáticamente al resolver.</div>
      <table class="table table-sm mt-2">
        <thead>
          <tr>
            <th>Fecha</th><th>Título</th><th>Categoría</th><th>Unidad</th><th>Prioridad</th>
          </tr>
        </thead>
        <tbody id="tblNovedades">
          <!-- se llena desde app.js si mantenés esa parte -->
        </tbody>
      </table>
      <div id="ts" class="small text-muted"></div>
    </div>
  </div>

</div>

<script src="app.js"></script>
</body>
</html>
