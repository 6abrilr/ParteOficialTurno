<?php
declare(strict_types=1);
require_once __DIR__ . '/php/auth/bootstrap.php';

// Si tenés helper require_role, usalo. Si no, usá require_login + chequeo manual.
if (function_exists('require_role')) {
  require_role('admin');
} else {
  require_login();
  if (!(function_exists('user_has_role') && user_has_role('admin'))) {
    http_response_code(403); echo 'Acceso denegado.'; exit;
  }
}

$u = function_exists('user') ? user() : null;

// Rutas base
$BASE   = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); if ($BASE==='') $BASE='/';
$ASSETS = rtrim($BASE, '/') . '/public';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel de Administración – B Com 602</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?= h($ASSETS) ?>/img/escudo602sinfondo.png">
  <link rel="shortcut icon" href="<?= h($ASSETS) ?>/img/escudo602sinfondo.png">
  <style>
    :root{
      --ink:#0b1326; --deep:#0a1830; --glow:#1e7bdc; --mesh-opacity:.70; --glow-strength:.55;
      --card-bg:#fff; --card-border:#e9ecef; --shadow:0 8px 24px rgba(33,37,41,.06);
      --primary:#0d6efd; --primary-2:#0b5ed7; --ring:#86b7fe; --container-max:1280px;
    }
    html,body{height:100%} body{margin:0;color:#212529;background:#000}
    .page-bg{position:fixed;inset:0;z-index:-2;pointer-events:none;background:
      radial-gradient(1200px 800px at 78% 24%, rgba(30,123,220,var(--glow-strength)) 0%, rgba(30,123,220,0) 60%),
      radial-gradient(1000px 700px at 12% 82%, rgba(30,123,220,.35) 0%, rgba(30,123,220,0) 60%),
      linear-gradient(160deg, var(--ink) 0%, var(--deep) 55%, #071020 100%);background-attachment:fixed,fixed,fixed;filter:saturate(1.05)}
    .page-bg::before{content:"";position:absolute;inset:0;z-index:-1;opacity:.22;background-image:
      radial-gradient(1.4px 1.4px at 18% 22%, #9cd1ff 20%, transparent 60%),
      radial-gradient(1.2px 1.2px at 63% 48%, #b7ddff 20%, transparent 60%),
      radial-gradient(1.2px 1.2px at 82% 70%, #b7ddff 20%, transparent 60%),
      radial-gradient(1.6px 1.6px at 34% 76%, #cbe8ff 20%, transparent 60%),
      radial-gradient(1.1px 1.1px at 72% 16%, #a7d6ff 20%, transparent 60%);
      background-repeat:no-repeat;background-size:1200px 800px,1400px 900px,1100px 900px,1400px 1000px,1300px 800px;background-position:0 0,30% 40%,80% 60%,10% 90%,70% 10%}
    .mesh{position:fixed;right:-220px;top:-140px;width:1400px;height:900px;z-index:-1;opacity:.70;
      background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1400' height='900' viewBox='0 0 1400 900'%3E%3Cg fill='none' stroke='%23a6c9ff' stroke-opacity='.40' stroke-width='1.1'%3E%3Cpath d='M860 60 L1120 180 L980 300 L1260 360 L1360 240'/%3E%3Cpath d='M1020 520 L1240 430 L1360 580'/%3E%3Cpath d='M900 240 L1120 360 L1280 260'/%3E%3Cpath d='M940 720 L1200 600 L1340 740'/%3E%3C/g%3E%3Cg fill='%23e9f4ff' fill-opacity='.95'%3E%3Ccircle cx='860' cy='60' r='3'/%3E%3Ccircle cx='1120' cy='180' r='2.5'/%3E%3Ccircle cx='980' cy='300' r='2.5'/%3E%3Ccircle cx='1260' cy='360' r='3'/%3E%3Ccircle cx='1360' cy='240' r='2.5'/%3E%3Ccircle cx='1020' cy='520' r='2.6'/%3E%3Ccircle cx='1240' cy='430' r='2.4'/%3E%3Ccircle cx='1360' cy='580' r='2.6'/%3E%3Ccircle cx='900' cy='240' r='2.5'/%3E%3Ccircle cx='1120' cy='360' r='2.4'/%3E%3Ccircle cx='1280' cy='260' r='2.8'/%3E%3Ccircle cx='940' cy='720' r='2.4'/%3E%3Ccircle cx='1200' cy='600' r='2.8'/%3E%3Ccircle cx='1340' cy='740' r='2.5'/%3E%3C/g%3E%3C/svg%3E") no-repeat center/contain;
      mix-blend-mode:screen;filter:drop-shadow(0 0 35px rgba(124,196,255,.25));pointer-events:none}
    .mesh.mesh--left{left:-260px;top:180px;right:auto;transform:scaleX(-1) rotate(3deg)}
    .brand-hero{position:relative;padding:28px 0 30px;color:#e9f2ff;isolation:isolate}
    .hero-inner{display:flex;align-items:center;gap:14px}
    .brand-logo{width:56px;height:56px;object-fit:contain;flex:0 0 auto;filter:drop-shadow(0 2px 10px rgba(124,196,255,.30))}
    .brand-title{font-weight:800;letter-spacing:.4px;font-size:28px;line-height:1.1;text-shadow:0 2px 16px rgba(30,123,220,.45)}
    .brand-sub{font-size:16px;opacity:.9;border-top:2px solid rgba(124,196,255,.35);display:inline-block;padding-top:4px;margin-top:2px}
    .brand-year{margin-left:auto;font-size:28px;font-weight:700;opacity:.85}
    @media (min-width:1200px){ .container{max-width:1280px !important} }
    .card{border-radius:14px;border:1px solid #e9ecef;box-shadow:0 8px 24px rgba(33,37,41,.06)}
  </style>
</head>
<body>
  <div class="page-bg"></div>
  <span class="mesh"></span>
  <span class="mesh mesh--left"></span>

  <header class="brand-hero">
    <div class="hero-inner container">
      <img class="brand-logo" src="<?= h($ASSETS) ?>/img/escudo602sinfondo.png" alt="Escudo 602">
      <div>
        <div class="brand-title">Panel de Administración</div>
        <div class="brand-sub">Batallón de Comunicaciones 602</div>
      </div>
      <div class="brand-year"><?= date('Y') ?></div>
    </div>
  </header>

  <main class="container my-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="text-light">
        Hola, <strong><?= h($u['nombre'] ?? $u['email'] ?? 'admin') ?></strong>
      </div>
      <div>
        <a class="btn btn-outline-light btn-sm" href="<?= h($BASE) ?>/logout.php">Salir</a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-sm-6 col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Generar Parte</h5>
            <p class="card-text">Ir a la aplicación principal para generar el Parte del Arma.</p>
            <a class="btn btn-primary" href="<?= h($ASSETS) ?>/index.php">Ir a Index</a>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Usuarios</h5>
            <p class="card-text">Alta, edición, reseteo de contraseña y administración de roles.</p>
            <a class="btn btn-primary" href="<?= h($BASE) ?>/usuarios.php">Gestionar usuarios</a>
          </div>
        </div>
      </div>

      <!-- Partes de Novedades -->
      <div class="col-sm-6 col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Partes de Novedades</h5>
            <p class="card-text">Listado de partes guardados, con filtros por fecha y oficial.</p>
            <a class="btn btn-primary" href="<?= h($BASE) ?>/partes.php">Ver partes</a>
          </div>
        </div>
      </div>


      <div class="col-sm-6 col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Mi cuenta</h5>
            <p class="card-text">Cambiar mi contraseña o mis datos.</p>
            <a class="btn btn-outline-primary" href="<?= h($BASE) ?>/reset.php">Cambiar contraseña</a>
          </div>
        </div>
      </div>
    </div>
  </main>

</body>
</html>
