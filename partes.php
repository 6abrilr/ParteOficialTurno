<?php
declare(strict_types=1);
require_once __DIR__ . '/php/auth/bootstrap.php';
require_role('admin');

$pdo = db();
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Base rutas
$BASE   = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); if ($BASE==='') $BASE='/';
$ASSETS = rtrim($BASE, '/') . '/public';

// Filtros: día único + usuario creador
$fdia = trim((string)($_GET['fdia'] ?? date('Y-m-d')));
$uid  = (int)($_GET['uid'] ?? 0);

// Select de usuarios activos (para “Creado por”)
$users = $pdo->query("
  SELECT id, COALESCE(NULLIF(nombre,''), email) AS nom
  FROM users
  WHERE activo = 1
  ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);

// Query
$where  = [];
$params = [];

if ($fdia !== '') {
  $where[]  = "DATE(p.fecha_desde) = ?";
  $params[] = $fdia;
}
if ($uid > 0) {
  $where[]  = "p.created_by = ?";
  $params[] = $uid;
}

$sql = "
  SELECT p.*,
         COALESCE(NULLIF(u.nombre,''), u.email) AS creador
  FROM partes p
  LEFT JOIN users u ON u.id = p.created_by
  " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
  ORDER BY p.id DESC
  LIMIT 500
";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Partes de Novedades – B Com 602</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?= h($ASSETS) ?>/img/escudo602sinfondo.png">
  <link rel="shortcut icon" href="<?= h($ASSETS) ?>/img/escudo602sinfondo.png">
  <style>
    :root{
      --ink:#0b1326; --deep:#0a1830; --mesh-opacity:.70; --glow-strength:.55;
      --card-border:#e9ecef; --card-bg:#fff; --shadow:0 8px 24px rgba(33,37,41,.06);
    }
    html,body{height:100%} body{margin:0;background:#000;color:#212529}
    .page-bg{position:fixed;inset:0;z-index:-2;pointer-events:none;background:
      radial-gradient(1200px 800px at 78% 24%, rgba(30,123,220,var(--glow-strength)) 0%, rgba(30,123,220,0) 60%),
      radial-gradient(1000px 700px at 12% 82%, rgba(30,123,220,.35) 0%, rgba(30,123,220,0) 60%),
      linear-gradient(160deg, var(--ink) 0%, var(--deep) 55%, #071020 100%);
      background-attachment:fixed; filter:saturate(1.05)}
    .mesh{position:fixed;right:-220px;top:-140px;width:1400px;height:900px;z-index:-1;opacity:.70;
      background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1400' height='900' viewBox='0 0 1400 900'%3E%3Cg fill='none' stroke='%23a6c9ff' stroke-opacity='.40' stroke-width='1.1'%3E%3Cpath d='M860 60 L1120 180 L980 300 L1260 360 L1360 240'/%3E%3Cpath d='M1020 520 L1240 430 L1360 580'/%3E%3Cpath d='M900 240 L1120 360 L1280 260'/%3E%3Cpath d='M940 720 L1200 600 L1340 740'/%3E%3C/g%3E%3Cg fill='%23e9f4ff' fill-opacity='.95'%3E%3Ccircle cx='860' cy='60' r='3'/%3E%3Ccircle cx='1120' cy='180' r='2.5'/%3E%3Ccircle cx='980' cy='300' r='2.5'/%3E%3Ccircle cx='1260' cy='360' r='3'/%3E%3Ccircle cx='1360' cy='240' r='2.5'/%3E%3Ccircle cx='1020' cy='520' r='2.6'/%3E%3Ccircle cx='1240' cy='430' r='2.4'/%3E%3Ccircle cx='1360' cy='580' r='2.6'/%3E%3Ccircle cx='900' cy='240' r='2.5'/%3E%3Ccircle cx='1120' cy='360' r='2.4'/%3E%3Ccircle cx='1280' cy='260' r='2.8'/%3E%3Ccircle cx='940' cy='720' r='2.4'/%3E%3Ccircle cx='1200' cy='600' r='2.8'/%3E%3Ccircle cx='1340' cy='740' r='2.5'/%3E%3C/g%3E%3C/svg%3E") no-repeat center/contain;
      mix-blend-mode:screen; filter:drop-shadow(0 0 35px rgba(124,196,255,.25)); pointer-events:none}
    .brand-hero{position:relative;padding:28px 0 30px;color:#e9f2ff;isolation:isolate}
    .hero-inner{display:flex;align-items:center;gap:14px}
    .brand-logo{width:56px;height:56px;object-fit:contain;filter:drop-shadow(0 2px 10px rgba(124,196,255,.30))}
    .brand-title{font-weight:800;letter-spacing:.4px;font-size:28px;line-height:1.1;text-shadow:0 2px 16px rgba(30,123,220,.45)}
    .brand-sub{font-size:16px;opacity:.9;border-top:2px solid rgba(124,196,255,.35);display:inline-block;padding-top:4px;margin-top:2px}
    .brand-year{margin-left:auto;font-size:28px;font-weight:700;opacity:.85}
    .card{border-radius:14px;border:1px solid var(--card-border);box-shadow:var(--shadow);background:var(--card-bg)}
    .form-control,.form-select{height:42px;font-size:.95rem}
    .btn{border-radius:10px}
  </style>
</head>
<body>
  <div class="page-bg"></div>
  <span class="mesh"></span>

  <header class="brand-hero">
    <div class="hero-inner container">
      <img class="brand-logo" src="<?= h($ASSETS) ?>/img/escudo602sinfondo.png" alt="Escudo 602">
      <div>
        <div class="brand-title">Partes de Novedades</div>
        <div class="brand-sub">Batallón de Comunicaciones 602</div>
      </div>
      <div class="brand-year"><?= date('Y') ?></div>
    </div>
  </header>

  <main class="container my-3">

    <div class="d-flex justify-content-between align-items-center mb-3 text-light">
      <div class="d-flex gap-2">
        <a class="btn btn-outline-light btn-sm" href="<?= h(url('admin.php')) ?>">← Volver al panel</a>
      </div>
      <div>
        <a class="btn btn-outline-light btn-sm" href="<?= h(url('logout.php')) ?>">Salir</a>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-3 align-items-end" method="get">
          <div class="col-12 col-md-3">
            <label class="form-label">Día</label>
            <input class="form-control" type="date" name="fdia" value="<?= h($fdia) ?>">
            <div class="form-text">Ej.: 11/10/2025 → trae el parte 11–12.</div>
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label">Creado por</label>
            <select class="form-select" name="uid">
              <option value="0">Todos</option>
              <?php foreach($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= $uid===(int)$u['id']?'selected':'' ?>>
                  <?= h($u['nom']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-2">
            <!-- label invisible para alinear -->
            <label class="form-label invisible">.</label>
            <button class="btn btn-primary w-100">Buscar</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Resultados</h5>
          <span class="text-muted"><?= count($rows) ?> registros</span>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Rango</th>
                <th>Oficial / Suboficial</th>
                <th>Título</th>
                <th>Archivo</th>
                <th>Creado</th>
                <th>Creador</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="7" class="text-center text-muted">Sin resultados</td></tr>
              <?php else: ?>
                <?php foreach($rows as $r):
                  $fileRel = (string)($r['file_rel_path'] ?? '');
                  $fsPath  = __DIR__ . '/' . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $fileRel), DIRECTORY_SEPARATOR);
                  $exists  = $fileRel !== '' && is_file($fsPath);
                  $url     = rtrim($BASE,'/') . '/' . ltrim($fileRel,'/');
                  $ext     = strtolower(pathinfo($fileRel, PATHINFO_EXTENSION));
                ?>
                <tr>
                  <td><?= (int)$r['id'] ?></td>
                  <td><?= h(date('d/m/Y', strtotime($r['fecha_desde']))) ?> – <?= h(date('d/m/Y', strtotime($r['fecha_hasta']))) ?></td>
                  <td>
                    <div><strong><?= h($r['oficial_turno'] ?? '') ?></strong></div>
                    <div class="text-muted small"><?= h($r['suboficial_turno'] ?? '') ?></div>
                  </td>
                  <td><?= h($r['titulo']) ?></td>
                  <td>
                    <?php if ($exists): ?>
                      <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= h($url) ?>">
                        Abrir <?= $ext==='html' ? 'HTML' : 'PDF' ?>
                      </a>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark">No encontrado</span>
                    <?php endif; ?>
                  </td>
                  <td><?= h(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
                  <td><?= h($r['creador'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </main>
</body>
</html>
