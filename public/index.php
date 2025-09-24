<?php /* public/index.php */ ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Parte de Novedades – B Com 602</title>

  <link id="bs-cdn" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        onerror="this.remove();document.getElementById('bs-local').disabled=false;">
  <link id="bs-local" href="vendor/bootstrap/bootstrap.min.css" rel="stylesheet" disabled>
  <link href="styles.css" rel="stylesheet">
  <style>
    /* Marca de agua */
    body {
      background-image: url('img/escudo602.png');
      background-repeat: no-repeat;
      background-position: center 20%;
      background-attachment: fixed;
      background-size: 420px auto;
    }
    @media print {
      body { background: none; } /* algunos navegadores no imprimen bg; agregamos en parte.php */
    }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <header class="mb-4 text-center">
   <!--<img src="img/escudo602confondo.jpeg" alt="Escudo 602" style="height:90px; opacity:.85">-->
    <div class="ea-motto small text-muted mt-2">Ejército Argentino – “Año de la Reconstrucción de la Nación Argentina”</div>
    <h1 class="h3 fw-bold mt-2">PARTE DE NOVEDADES DEL ARMA DE COMUNICACIONES</h1>
    <div class="unit text-uppercase">Batallón de Comunicaciones 602</div>
  </header>

  <!-- Encabezado del Parte -->
  <div class="card shadow-sm mb-3">
    <div class="card-header">Encabezado del Parte</div>
    <div class="card-body">
      <form id="frmEnc" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Desde</label>
          <input type="datetime-local" id="desde" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Hasta</label>
          <input type="datetime-local" id="hasta" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Oficial de turno</label>
          <input type="text" id="oficial" class="form-control" placeholder="Grado Apellido, Nombres" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Suboficial de turno</label>
          <input type="text" id="suboficial" class="form-control" placeholder="Grado Apellido, Nombres" required>
        </div>
        <div class="col-12 d-flex gap-2 mt-2">
          <button class="btn btn-primary" id="btnGuardarEnc" type="button">Guardar encabezado</button>
          <a id="linkParte" class="btn btn-outline-success ms-auto d-none" target="_blank">Abrir Parte</a>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3">
    <!-- IZQ: CENOPE + LTA -->
    <div class="col-lg-5">
      <div class="card shadow-sm mb-3">
        <div class="card-header">Importar CENOPE (PDF) – Personal</div>
        <div class="card-body">
          <input type="file" id="pdfCenope" class="form-control" accept="application/pdf">
          <div class="hstack gap-2 mt-2">
            <button type="button" class="btn btn-outline-primary" id="btnPrevCenope">Previsualizar</button>
            <button type="button" class="btn btn-success" id="btnImpCenope" disabled>Importar</button>
          </div>
          <div id="cenopeInfo" class="small text-muted mt-1"></div>
          <div id="previewTables" class="mt-2"></div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header">Importar LTA / Parte (DOCX) – Novedades</div>
        <div class="card-body">
          <input type="file" id="docxLta" class="form-control"
                 accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
          <div class="hstack gap-2 mt-2">
            <button type="button" class="btn btn-outline-primary" id="btnPrevLta">Previsualizar</button>
            <button type="button" class="btn btn-success" id="btnImpLta" disabled>Importar</button>
          </div>
          <pre id="ltaPreview" class="small text-muted mb-0 mt-2" style="white-space:pre-wrap"></pre>
        </div>
      </div>
    </div>

    <!-- DER: Pendientes + Estados de Sistemas -->
    <div class="col-lg-7">
      <div class="card shadow-sm mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span>Pendientes (oculta resueltas)</span>
          <div class="small text-muted" id="ts"></div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle" id="tblNovedades">
              <thead>
                <tr>
                  <th>Fecha</th><th>Título</th><th>Categoría</th><th>Unidad</th><th>Prioridad</th><th>Acciones</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Estados de Sistemas -->
      <div class="card shadow-sm">
        <div class="card-header">Estados de Sistemas</div>
        <div class="card-body">
          <h6>SISTEMAS – SERVICIOS</h6>
          <div id="tblServ"></div>
          <h6 class="mt-3">SISTEMAS – ISP EDIFICIO LIBERTADOR</h6>
          <div id="tblIsp"></div>
          <h6 class="mt-3">SITELPAR</h6>
          <div id="tblSitelpar"></div>
          <h6 class="mt-3">DATA CENTER</h6>
          <div id="tblDC"></div>
          <h6 class="mt-3">SITM2</h6>
          <div id="tblSITM2"></div>
          <small class="text-muted d-block mt-2">Los estados se guardan al cambiar.</small>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        onerror="var s=document.createElement('script');s.src='vendor/bootstrap/bootstrap.bundle.min.js';document.body.appendChild(s);"></script>
<script src="app.js"></script>
</body>
</html>
