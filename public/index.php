<?php
// public/index.php
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Parte de Novedades – B Com 602</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f6f7f9}
    .card{box-shadow:0 2px 8px rgba(0,0,0,.04)}
    .estado-badge{font-size:.8rem;color:#6c757d}
    .tr-estado-NUEVA      {background:#fff9e6}
    .tr-estado-ACTUALIZADA{background:#eef7ff}
    .tr-estado-RESUELTA   {background:#f2f2f2}
  </style>
</head>
<body>
<div class="container my-4">

  <h1 class="h4 mb-3">PARTE DE NOVEDADES – Batallón de Comunicaciones 602</h1>

  <!-- =============== ÚNICA SECCIÓN =============== -->
  <div class="card">
    <div class="card-header fw-semibold">Generar Parte del Arma (automático)</div>
    <div class="card-body">

      <!-- Encabezado (obligatorio) -->
      <div class="row g-3 align-items-end mb-2">
        <div class="col-sm-3">
          <label class="form-label">Desde</label>
          <input type="datetime-local" id="g_desde" class="form-control" required>
        </div>
        <div class="col-sm-3">
          <label class="form-label">Hasta</label>
          <input type="datetime-local" id="g_hasta" class="form-control" required>
        </div>
        <div class="col-sm-3">
          <label class="form-label">Oficial de turno</label>
          <input type="text" id="g_oficial" class="form-control" placeholder="Ej: ST ROJAS">
        </div>
        <div class="col-sm-3">
          <label class="form-label">Suboficial de turno</label>
          <input type="text" id="g_subof" class="form-control" placeholder="Ej: CB MARTINEZZ">
        </div>
        <div class="col-12 d-flex gap-2">
          <button id="btnGuardarEncGen" class="btn btn-primary">Guardar encabezado</button>
          <span class="text-muted small">Primero guardá el encabezado; luego seguí con LTA/CENOPE y sistemas.</span>
        </div>
      </div>

      <hr class="my-4">

      <!-- LTA -->
      <h6 class="mb-2">Archivo LTA (DOCX/PDF)</h6>
      <div class="row g-2 align-items-center">
        <div class="col-sm-6">
          <input type="file" id="p_lta" class="form-control" accept=".doc,.docx,.pdf">
        </div>
        <div class="col-sm-6 d-flex gap-2">
          <button id="btnPrevLTA"  class="btn btn-outline-secondary btn-sm">Previsualizar</button>
          <button id="btnImpLTA"   class="btn btn-outline-success btn-sm" disabled>Guardar LTA</button>
          <span id="ltaInfo" class="small text-muted"></span>
        </div>
      </div>
      <div class="mt-3" id="ltaPreview"></div>

      <hr class="my-4">

      <!-- CENOPE -->
      <h6 class="mb-2">Archivo CENOPE (PDF)</h6>
      <div class="row g-2 align-items-center">
        <div class="col-sm-6">
          <input type="file" id="p_cenope" class="form-control" accept=".pdf">
        </div>
        <div class="col-sm-6 d-flex gap-2">
          <button id="btnPrevCenope" class="btn btn-outline-secondary btn-sm">Previsualizar</button>
          <button id="btnImpCenope"  class="btn btn-outline-success btn-sm" disabled>Guardar CENOPE</button>
          <span id="cenopeInfo" class="small text-muted"></span>
        </div>
      </div>
      <div class="mt-3" id="previewTables"></div>

      <hr class="my-4">

      <!-- Sistemas -->
      <h6 class="mb-2">Sistemas – Servicios</h6>
      <div id="tblServ" class="mb-3"></div>

      <h6 class="mb-2">Sistemas – ISP Edificio Libertador</h6>
      <div id="tblIsp" class="mb-3"></div>

      <h6 class="mb-2">SITELPAR</h6>
      <div id="tblSitelpar" class="mb-3"></div>

      <h6 class="mb-2">Sistemas – Data Center</h6>
      <div id="tblDC" class="mb-3"></div>

      <h6 class="mb-2">Sistemas – SITM2</h6>
      <div id="tblSITM2" class="mb-3"></div>

      <div class="d-flex gap-2">
        <button id="btnConfirmSistemas" class="btn btn-outline-primary btn-sm">Confirmar revisión de sistemas</button>
        <span class="small text-muted">Marcá que revisaste todas las tablas.</span>
      </div>

      <hr class="my-4">

      <!-- Acciones finales -->
      <div class="d-flex flex-wrap align-items-center gap-2">
        <button id="btnGenerarParte" class="btn btn-success" disabled>Generar Parte</button>
        <a id="lnkParteHTML" class="btn btn-outline-secondary d-none" target="_blank">Ver HTML</a>
        <a id="lnkPartePDF"  class="btn btn-outline-secondary d-none" target="_blank">Descargar PDF</a>
        <span id="parteInfo" class="ms-2 small text-muted"></span>
      </div>

    </div>
  </div>
  <!-- ============ /ÚNICA SECCIÓN ============ -->

  <div class="mt-4 text-muted small">Ejército Argentino – “Año de la Reconstrucción de la Nación Argentina”</div>

</div>

<script src="app.js"></script>
</body>
</html>
