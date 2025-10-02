<?php
// public/index.php
date_default_timezone_set('America/Argentina/Buenos_Aires');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Parte de Novedades – B Com 602</title>

  <link rel="icon" type="image/png" href="img/escudo602sinfondo.png">
  <link rel="shortcut icon" href="img/escudo602sinfondo.png">

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{
      /* === Paleta base del fondo === */
      --ink:#0b1326;          /* azul muy oscuro */
      --deep:#0a1830;         /* intermedio */
      --glow:#1e7bdc;         /* cian brillante */

      /* Intensidad de efectos del fondo */
      --mesh-opacity:.70;
      --glow-strength:.55;

      /* === UI === */
      --card-bg:#fff;
      --card-border:#e9ecef;
      --shadow:0 8px 24px rgba(33,37,41,.06);
      --primary:#0d6efd; --primary-2:#0b5ed7; --ring:#86b7fe;
      --thead:#f6f7f9; --row-sep:#e9ecef;
      --thead-text:#111;

      /* Thead de tablas detectadas (preview CENOPE) */
      --thead-internado:#ffe4c2;  /* naranja claro */
      --thead-alta:#d9f4e0;       /* verde claro  */
      --thead-fallecido:#d7ebff;  /* celeste      */

      /* Ancho del container en pantallas grandes */
      --container-max: 1280px;    /* subí/bajá (1240–1360) a gusto */
    }

    html,body{ height:100%; }
    body{
      margin:0;
      color:#212529;
      background:#000; /* base */
    }

    /* ======== FONDO GLOBAL (fijo) ======== */
    .page-bg{
      position:fixed; inset:0; z-index:-2; pointer-events:none;
      background:
        radial-gradient(1200px 800px at 78% 24%, rgba(30,123,220,var(--glow-strength)) 0%, rgba(30,123,220,0) 60%),
        radial-gradient(1000px 700px at 12% 82%, rgba(30,123,220,.35) 0%, rgba(30,123,220,0) 60%),
        linear-gradient(160deg, var(--ink) 0%, var(--deep) 55%, #071020 100%);
      background-attachment: fixed,fixed,fixed;
      filter: saturate(1.05);
    }
    /* puntitos tipo estrellas, sutiles */
    .page-bg::before{
      content:""; position:absolute; inset:0; z-index:-1; opacity:.22;
      background-image:
        radial-gradient(1.4px 1.4px at 18% 22%, #9cd1ff 20%, transparent 60%),
        radial-gradient(1.2px 1.2px at 63% 48%, #b7ddff 20%, transparent 60%),
        radial-gradient(1.2px 1.2px at 82% 70%, #b7ddff 20%, transparent 60%),
        radial-gradient(1.6px 1.6px at 34% 76%, #cbe8ff 20%, transparent 60%),
        radial-gradient(1.1px 1.1px at 72% 16%, #a7d6ff 20%, transparent 60%);
      background-repeat: no-repeat;
      background-size: 1200px 800px, 1400px 900px, 1100px 900px, 1400px 1000px, 1300px 800px;
      background-position: 0 0, 30% 40%, 80% 60%, 10% 90%, 70% 10%;
    }
    /* malla derecha */
    .mesh{
      position:fixed; right:-220px; top:-140px;
      width:1400px; height:900px; z-index:-1; opacity:var(--mesh-opacity);
      background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1400' height='900' viewBox='0 0 1400 900'%3E%3Cg fill='none' stroke='%23a6c9ff' stroke-opacity='.40' stroke-width='1.1'%3E%3Cpath d='M860 60 L1120 180 L980 300 L1260 360 L1360 240'/%3E%3Cpath d='M1020 520 L1240 430 L1360 580'/%3E%3Cpath d='M900 240 L1120 360 L1280 260'/%3E%3Cpath d='M940 720 L1200 600 L1340 740'/%3E%3C/g%3E%3Cg fill='%23e9f4ff' fill-opacity='.95'%3E%3Ccircle cx='860' cy='60' r='3'/%3E%3Ccircle cx='1120' cy='180' r='2.5'/%3E%3Ccircle cx='980' cy='300' r='2.5'/%3E%3Ccircle cx='1260' cy='360' r='3'/%3E%3Ccircle cx='1360' cy='240' r='2.5'/%3E%3Ccircle cx='1020' cy='520' r='2.6'/%3E%3Ccircle cx='1240' cy='430' r='2.4'/%3E%3Ccircle cx='1360' cy='580' r='2.6'/%3E%3Ccircle cx='900' cy='240' r='2.5'/%3E%3Ccircle cx='1120' cy='360' r='2.4'/%3E%3Ccircle cx='1280' cy='260' r='2.8'/%3E%3Ccircle cx='940' cy='720' r='2.4'/%3E%3Ccircle cx='1200' cy='600' r='2.8'/%3E%3Ccircle cx='1340' cy='740' r='2.5'/%3E%3C/g%3E%3C/svg%3E") no-repeat center/contain;
      mix-blend-mode:screen; filter:drop-shadow(0 0 35px rgba(124,196,255,.25));
      pointer-events:none;
    }
    /* malla izquierda (espejada) */
    .mesh.mesh--left{
      left:-260px; top:180px; right:auto;
      transform: scaleX(-1) rotate(3deg);
    }

    /* ======= HERO ======= */
    .brand-hero{
      position:relative; padding:28px 0 90px; color:#e9f2ff; isolation:isolate;
    }
    .hero-inner{ display:flex; align-items:center; gap:14px; }
    .brand-logo{ width:56px; height:56px; object-fit:contain; flex:0 0 auto;
      filter:drop-shadow(0 2px 10px rgba(124,196,255,.30)); }
    .brand-title{ font-weight:800; letter-spacing:.4px; font-size:28px; line-height:1.1;
      text-shadow:0 2px 16px rgba(30,123,220,.45); }
    .brand-sub{ font-size:16px; opacity:.9; border-top:2px solid rgba(124,196,255,.35);
      display:inline-block; padding-top:4px; margin-top:2px; }
    .brand-year{ margin-left:auto; font-size:28px; font-weight:700; opacity:.85; }

    /* El contenido “sube” un poco dentro del hero */
    .main-wrap{ margin-top:-46px; }

    /* ======= Tarjetas/UI ======= */
    @media (min-width: 1200px){
      .container{ max-width: var(--container-max) !important; }
    }
    .card{ border-radius:14px; border:1px solid var(--card-border); box-shadow:var(--shadow); background:var(--card-bg); }
    .card .card-body{ padding:18px; }
    .section-title{ font-weight:600; font-size:15px; text-transform:uppercase; letter-spacing:.03em; color:#212529; }
    .form-label{ font-size:.82rem; text-transform:uppercase; letter-spacing:.04em; color:#495057; margin-bottom:.25rem; }
    .form-control{ height:42px; font-size:.95rem; }
    .btn{ border-radius:10px; }
    .btn-primary{ background:var(--primary); border-color:var(--primary); }
    .btn-primary:hover{ background:var(--primary-2); border-color:var(--primary-2); }

    .table{ margin-bottom:.5rem; }
    .table thead th{ background:var(--thead); color:var(--thead-text); font-weight:600; border-top:1px solid #dee2e6; }
    .table-sm td, .table-sm th{ padding:.45rem .6rem; }
    .table tbody tr + tr td{ border-top:1px solid var(--row-sep); }

    /* thead coloreados por tipo (para la preview CENOPE) */
    .tbl-internado thead th{ background:var(--thead-internado) !important; }
    .tbl-alta      thead th{ background:var(--thead-alta) !important; }
    .tbl-fallecido thead th{ background:var(--thead-fallecido) !important; }

    /* editables (LTA/CENOPE) */
    #tblLTA td[contenteditable="true"], .table-editable td[contenteditable="true"]{
      outline:1px dashed #ced4da; border-radius:4px; transition:background .15s ease, outline-color .15s ease;
    }
    #tblLTA td[contenteditable="true"]:focus, .table-editable td[contenteditable="true"]:focus{
      outline:2px solid var(--ring); background:#f0f7ff;
    }

    #ltaPreview, #previewTables{ border:1px dashed #e2e6ea; border-radius:12px; padding:12px; background:#fff; }

    @media (max-width:576px){
      .brand-title{ font-size:22px; } .brand-year{ font-size:22px; }
      .card .card-body{ padding:14px; } .form-control{ height:40px; }
    }
  </style>
</head>
<body>

  <!-- Fondo global -->
  <div class="page-bg"></div>
  <span class="mesh"></span>
  <span class="mesh mesh--left"></span>

  <!-- ===== Hero ===== -->
  <header class="brand-hero">
    <div class="hero-inner container">
      <img class="brand-logo" src="img/escudo602sinfondo.png" alt="Escudo 602">
      <div>
        <div class="brand-title">Batallón de Comunicaciones 602</div>
        <div class="brand-sub">“Hogar de las Comunicaciones fijas del Ejército”</div>
      </div>
      <div class="brand-year"><?= date('Y'); ?></div>
    </div>
  </header>

  <!-- ===== Contenido principal ===== -->
  <main class="main-wrap">
    <div class="container">

      <div class="card mb-4">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="section-title">Generar Parte del Arma</div>
          </div>

          <!-- Encabezado del parte -->
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
              <input type="text" class="form-control" id="g_subof" placeholder="Ej: CB MARTÍNEZ">
            </div>
          </div>

          <div class="mt-3">
            <button class="btn btn-primary btn-sm" id="btnGuardarEncGen">Guardar encabezado</button>
            <small class="text-muted ms-2">Primero guardá el encabezado; luego seguí con CENOPE y LTA.</small>
          </div>

          <hr class="my-4">

          <!-- ===== CENOPE (ARRIBA) ===== -->
          <div class="mb-3">
            <div class="section-title mb-2">Archivo CENOPE (PDF)</div>
            <div class="row g-2 align-items-center">
              <div class="col-md-6">
                <input type="file" id="p_cenope" name="pdf[]" class="form-control" accept=".pdf" multiple>
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
            <!-- Previsualización CENOPE -->
            <div class="mt-3" id="previewTables"></div>
          </div>

          <hr class="my-4">

          <!-- ===== LTA (ABAJO) ===== -->
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

          <!-- Sistemas -->
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

          <!-- Generar Parte -->
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-primary" id="btnGenerarParte" disabled>Generar Parte</button>
            <a id="lnkParteHTML" class="btn btn-outline-secondary d-none" target="_blank">Ver Parte</a>
            <a id="lnkPartePDF"  class="btn btn-outline-secondary d-none" target="_blank">Descargar PDF</a>
            <span id="parteInfo" class="ms-2 small text-muted"></span>
          </div>

        </div>
      </div>

    </div>
  </main>

  <!-- Colorea automáticamente los thead en la previsualización CENOPE -->
  <script>
  (function(){
    const root = document.getElementById('previewTables');
    if(!root) return;
    function colorize(){
      const hs = root.querySelectorAll('h2,h3,h4,h5,strong,p');
      hs.forEach(h => {
        const txt = (h.textContent||'').trim().toUpperCase();
        let t = h.nextElementSibling;
        while(t && t.tagName!=='TABLE'){ t=t.nextElementSibling; }
        if(!t) return;
        if (txt.includes('PERSONAL INTERNADO')) t.classList.add('tbl-internado');
        else if (txt.includes('PERSONAL ALTA')) t.classList.add('tbl-alta');
        else if (txt.includes('PERSONAL FALLECIDO')) t.classList.add('tbl-fallecido');
      });
    }
    colorize();
    new MutationObserver(colorize).observe(root, {childList:true, subtree:true});
  })();
  </script>

  <!-- Fallback: habilita y abre el parte aunque app.js no enganche -->
  <script>
  (function () {
    const btnGen = document.getElementById('btnGenerarParte');
    const btnEnc = document.getElementById('btnGuardarEncGen');
    const btnSis = document.getElementById('btnConfirmSistemas');
    const fDesde  = document.getElementById('g_desde');
    const fHasta  = document.getElementById('g_hasta');
    const fOfi    = document.getElementById('g_oficial');
    const fSub    = document.getElementById('g_subof');

    if (!btnGen) return;

    function listo() {
      return [fDesde, fHasta, fOfi, fSub].every(el => el && el.value && el.value.trim() !== '');
    }
    function tryEnable() {
      if (listo()) btnGen.removeAttribute('disabled');
    }
    [fDesde, fHasta, fOfi, fSub].forEach(el => el && el.addEventListener('input', tryEnable));
    btnEnc && btnEnc.addEventListener('click', () => setTimeout(tryEnable, 150));
    btnSis && btnSis.addEventListener('click', () => setTimeout(tryEnable, 150));
    tryEnable();

    btnGen.addEventListener('click', function () {
      if (btnGen.hasAttribute('disabled')) return;
      if (!listo()) { alert('Completá fechas y encabezado.'); return; }
      const qs = new URLSearchParams({desde: fDesde.value, hasta: fHasta.value}).toString();
      window.open('parte.php?' + qs, '_blank');
    });
  })();
  </script>

  <!-- Cache-busting -->
  <script src="app.js?v=13"></script>
</body>
</html>
