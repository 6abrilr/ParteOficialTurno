<?php /* public/index.php (compacto, sin duplicados) */ ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Parte de Novedades – B Com 602</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{ padding:24px }
    .card{ box-shadow:0 1px 6px rgba(0,0,0,.06) }
    .small-muted{ color:#6c757d; font-size:12px }
    details summary { cursor:pointer; user-select:none }
  </style>
</head>
<body class="bg-light">
<div class="container">
  <h1 class="h4 mb-3">PARTE DE NOVEDADES – Batallón de Comunicaciones 602</h1>

  <div class="row g-3">
    <!-- Encabezado del Parte (fecha + firmas) -->
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">Generar Parte</div>
        <div class="card-body">
          <form class="row g-3" id="frmParte">
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
              <input type="text" id="oficial" class="form-control" placeholder="Ej.: ST SCD Néstor Rojas">
            </div>
            <div class="col-md-3">
              <label class="form-label">Suboficial de turno</label>
              <input type="text" id="suboficial" class="form-control" placeholder="Ej.: CI Of Manuel Herrera">
            </div>
            <div class="col-12 d-flex justify-content-between align-items-end">
              <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Ver Parte</button>
                <a id="linkParte" class="btn btn-success d-none" target="_blank">Abrir Parte</a>
              </div>
              <button type="button" class="btn btn-outline-secondary" id="btnGuardarEnc">
                Guardar encabezado
              </button>
            </div>
          </form>
          <div class="small-muted mt-2">Se usa como encabezado por defecto en el Parte y en el generador automático.</div>
        </div>
      </div>
    </div>

    <!-- Pendientes -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <span>Pendientes (oculta resueltas)</span>
          <span id="ts" class="small-muted"></span>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle" id="tblNovedades">
              <thead><tr>
                <th>Fecha</th><th>Título</th><th>Categoría</th><th>Unidad</th><th>Prioridad</th><th></th>
              </tr></thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="small text-muted">Refresca automáticamente al resolver.</div>
        </div>
      </div>
    </div>

    <!-- Generar Parte del Arma (usa encabezado de arriba; solo pide archivos) -->
    <div class="col-12">
      <div class="card">
        <div class="card-header">Generar Parte del Arma (automático)</div>
        <div class="card-body">
          <div class="small-muted mb-2">
            Usa el <strong>encabezado de arriba</strong> (Desde/Hasta + Oficial/Suboficial). Si necesitás cambiarlo
            solo para este archivo, abrí “Opcional: editar encabezado para este parte”.
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Archivo LTA (DOCX/PDF)</label>
              <input id="p_lta" type="file" accept=".docx,.pdf" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Archivo CENOPE (PDF)</label>
              <input id="p_cenope" type="file" accept=".pdf" class="form-control">
            </div>
          </div>

          <details class="mt-3">
            <summary class="text-muted">Opcional: editar encabezado para este parte</summary>
            <div class="row g-2 mt-2">
              <div class="col-md-3">
                <label class="form-label">Desde</label>
                <input id="p_desde" type="datetime-local" class="form-control">
              </div>
              <div class="col-md-3">
                <label class="form-label">Hasta</label>
                <input id="p_hasta" type="datetime-local" class="form-control">
              </div>
              <div class="col-md-3">
                <label class="form-label">Oficial de turno</label>
                <input id="p_oficial" class="form-control" placeholder="Ej: ST SCD Néstor Rojas">
              </div>
              <div class="col-md-3">
                <label class="form-label">Suboficial de turno</label>
                <input id="p_subof" class="form-control" placeholder="Ej: CI Of Manuel ...">
              </div>
            </div>
          </details>

          <div class="mt-3 d-flex gap-2">
            <button id="btnGenerarParte" class="btn btn-primary">Generar Parte</button>
            <a id="lnkParteHTML" class="btn btn-outline-secondary d-none" target="_blank">Ver HTML</a>
            <a id="lnkPartePDF"  class="btn btn-outline-secondary d-none" target="_blank">Descargar PDF</a>
          </div>
          <div id="parteInfo" class="small text-muted mt-2"></div>
        </div>
      </div>
    </div>

    <!-- Tablas de Sistemas -->
    <div class="col-12">
      <div class="card"><div class="card-header">Sistemas – Servicios</div><div class="card-body" id="tblServ"></div></div>
    </div>
    <div class="col-12">
      <div class="card"><div class="card-header">Sistemas – ISP Edificio Libertador</div><div class="card-body" id="tblIsp"></div></div>
    </div>
    <div class="col-12">
      <div class="card"><div class="card-header">SITELPAR</div><div class="card-body" id="tblSitelpar"></div></div>
    </div>
    <div class="col-12">
      <div class="card"><div class="card-header">Sistemas – Data Center</div><div class="card-body" id="tblDC"></div></div>
    </div>
    <div class="col-12">
      <div class="card"><div class="card-header">Sistemas – SITM2</div><div class="card-body" id="tblSITM2"></div></div>
    </div>

    <!-- Nueva / Editar Novedad -->
    <div class="col-12">
      <div class="card">
        <div class="card-header">Nueva / Editar Novedad</div>
        <div class="card-body">
          <form id="frmNovedad" class="vstack gap-2">
            <input type="hidden" id="id" />
            <div class="row g-2">
              <div class="col-md-7">
                <label class="form-label">Título</label>
                <input class="form-control" id="titulo" maxlength="140" required>
              </div>
              <div class="col-md-2">
                <label class="form-label">Categoría</label>
                <select class="form-select" id="categoria_id">
                  <option value="1">Nodos REDISE</option>
                  <option value="2">Servicios</option>
                  <option value="3">ISP EL</option>
                  <option value="4">SITELPAR</option>
                  <option value="5">Data Center</option>
                  <option value="6">SITM2</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Prioridad</label>
                <select class="form-select" id="prioridad">
                  <option>MEDIA</option><option>ALTA</option><option>BAJA</option>
                </select>
              </div>
              <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-secondary w-100" id="btnNuevo">Nuevo</button>
              </div>
            </div>
            <div class="row g-2">
              <div class="col-md-8">
                <label class="form-label">Descripción</label>
                <textarea id="descripcion" class="form-control" rows="3" required></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label">Unidad</label>
                <input id="unidad_txt" class="form-control" placeholder="CCIG ...">
                <label class="form-label mt-2">Servicio</label>
                <input id="servicio" class="form-control" placeholder="HF / WEBMAIL / ...">
                <label class="form-label mt-2">Ticket</label>
                <input id="ticket" class="form-control" placeholder="GLPI / N°">
                <label class="form-label mt-2">Usuario</label>
                <input id="usuario" class="form-control" placeholder="ST SCD Rojas">
              </div>
            </div>
            <div><button class="btn btn-primary">Guardar</button></div>
          </form>
        </div>
      </div>
    </div>

  </div><!-- row -->
</div><!-- container -->

<script src="app.js"></script>
</body>
</html>
