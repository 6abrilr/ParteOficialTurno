<?php
declare(strict_types=1);

require_once __DIR__ . '/php/auth/bootstrap.php';   // url(), db(), h(), require_login(), etc.
require_role('admin');

$pdo = db();

/* =========================
   AJAX inline update (POST)
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upd') {
  header('Content-Type: application/json; charset=utf-8');

  $id    = (int)($_POST['id']    ?? 0);
  $field = (string)($_POST['field'] ?? '');
  $value = (string)($_POST['value'] ?? '');

  // Campos que permitimos editar en línea
  $whitelist = [
    'oficial_turno'    => 160,  // permitimos más por posibles saltos de línea
    'suboficial_turno' => 160,
    'titulo'           => 255,
  ];

  if ($id <= 0 || !isset($whitelist[$field])) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'Parámetros inválidos']);
    exit;
  }

  // recorte prudente por longitud (respetando saltos de línea)
  if (function_exists('mb_substr')) {
    $value = mb_substr($value, 0, $whitelist[$field]);
  } else {
    $value = substr($value, 0, $whitelist[$field]);
  }

  try {
    $sql = "UPDATE partes SET {$field} = :val WHERE id = :id";
    $st  = $pdo->prepare($sql);
    $st->execute([':val'=>$value, ':id'=>$id]);
    echo json_encode(['ok'=>true, 'value'=>$value]);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>'No se pudo guardar']);
  }
  exit;
}

/* =========================
   Filtros y consulta
   ========================= */
$BASE   = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); if ($BASE==='') $BASE='/';
$ASSETS = rtrim($BASE, '/') . '/public';

$fdia = trim((string)($_GET['fdia'] ?? ''));   // vacío por defecto → últimos 100
$uid  = (int)($_GET['uid'] ?? 0);

// Usuarios (para combo “Creado por”)
$users = $pdo->query("
  SELECT id, COALESCE(NULLIF(nombre,''), email) AS nom
  FROM users
  WHERE activo = 1
  ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);

// WHERE dinámico
$where  = [];
$params = [];

if ($fdia !== '') {
  $where[]  = "(DATE(p.fecha_desde) = ? OR DATE(p.fecha_hasta) = ?)";
  $params[] = $fdia;
  $params[] = $fdia;
}
if ($uid > 0) {
  $where[]  = "p.created_by = ?";
  $params[] = $uid;
}

$limit = ($fdia === '' && $uid === 0) ? 100 : 500;

$sql = "
  SELECT p.*,
         COALESCE(NULLIF(u.nombre,''), u.email) AS creador
  FROM partes p
  LEFT JOIN users u ON u.id = p.created_by
  " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
  ORDER BY p.id DESC
  LIMIT {$limit}
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
    :root{ --ink:#0b1326; --deep:#0a1830; --card-border:#e9ecef; --card-bg:#fff; --shadow:0 8px 24px rgba(33,37,41,.06); }
    html,body{height:100%} body{margin:0;background:#000;color:#212529}
    .page-bg{position:fixed;inset:0;z-index:-2;pointer-events:none;background:
      radial-gradient(1200px 800px at 78% 24%, rgba(30,123,220,.55) 0%, rgba(30,123,220,0) 60%),
      radial-gradient(1000px 700px at 12% 82%, rgba(30,123,220,.35) 0%, rgba(30,123,220,0) 60%),
      linear-gradient(160deg, var(--ink) 0%, var(--deep) 55%, #071020 100%); background-attachment:fixed; filter:saturate(1.05)}
    .brand-hero{padding:28px 0 30px;color:#e9f2ff}
    .brand-title{font-weight:800;letter-spacing:.4px;font-size:28px;line-height:1.1}
    .brand-sub{font-size:16px;opacity:.9;border-top:2px solid rgba(124,196,255,.35);display:inline-block;padding-top:4px;margin-top:2px}
    .brand-year{margin-left:auto;font-size:28px;font-weight:700;opacity:.85}

    .card{border-radius:14px;border:1px solid var(--card-border);box-shadow:var(--shadow);background:var(--card-bg)}
    .form-control,.form-select{height:42px;font-size:.95rem}
    .btn{border-radius:10px}

    /* Alineación y anchos de la tabla */
    .table th, .table td { vertical-align: middle; text-align:center; }   /* ← centrado horizontal y vertical */
    th:nth-child(1){ width:60px }        /* # */
    th:nth-child(2){ width:170px }       /* Rango */
    th:nth-child(5){ width:130px }       /* Abrir */
    th:nth-child(6){ width:165px }       /* Creado */

    /* Bordes NEGROS */
    .table, .table-bordered, .table th, .table td, .table thead th {
      border-color:#000 !important;
    }

    /* Estilo de celdas editables */
    .editable{
      outline:1px dashed rgba(0,0,0,.35);
      border-radius:4px;
      cursor:text;
      white-space: pre-line;   /* ← respeta saltos de línea (\n) */
      padding:2px 4px;
    }
    .editable:focus{ outline:2px solid #0d6efd; background:#eef6ff; }
    .editable.saved{ box-shadow:0 0 0 2px #198754 inset; background:#e8f5ee; }

    /* Columna Oficial/Suboficial: dos renglones */
    .os-col .editable{ display:block; margin:2px 0; }
    .os-col .os-of{ font-weight:700; }      /* Oficial en negrita */
  </style>
</head>
<body>
  <div class="page-bg"></div>

  <header class="brand-hero">
    <div class="container d-flex align-items-center gap-2">
      <img src="<?= h($ASSETS) ?>/img/escudo602sinfondo.png" width="56" height="56" alt="">
      <div>
        <div class="brand-title">Partes de Novedades</div>
        <div class="brand-sub">Batallón de Comunicaciones 602</div>
      </div>
      <div class="brand-year ms-auto"><?= date('Y') ?></div>
    </div>
  </header>

  <main class="container my-3">

    <!-- Barra superior -->
    <div class="d-flex justify-content-between align-items-center mb-3 text-light">
      <div class="d-flex gap-2">
        <a class="btn btn-outline-light btn-sm" href="<?= h(url('public/index.php')) ?>">← Volver</a>
        <a class="btn btn-outline-light btn-sm" href="<?= h(url('partes.php')) ?>">Refrescar</a>
      </div>
      <div>
        <a class="btn btn-outline-light btn-sm" href="<?= h(url('logout.php')) ?>">Salir</a>
      </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-3">
        <div class="card-body">
            <form class="row gy-3 gx-3 align-items-end filters" method="get">
            <div class="col-12 col-md-3">
                <label class="form-label mb-1" for="fdia">Día</label>
                <input class="form-control" type="date" id="fdia" name="fdia" value="<?= h($fdia) ?>">
            </div>

        <div class="col-12 col-md-5">
            <label class="form-label mb-1" for="uid">Creado por</label>
            <select class="form-select" id="uid" name="uid">
            <option value="0">Todos</option>
            <?php foreach($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= $uid===(int)$u['id']?'selected':'' ?>>
                <?= h($u['nom']) ?>
                </option>
            <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-2 ms-md-auto d-grid">
            <label class="form-label d-none d-md-block invisible">.</label>
            <button class="btn btn-primary">Buscar</button>
        </div>

        <!-- Ayuda en una fila completa para no desalinear -->
        <div class="col-12">
            <div class="form-text">
            Vacío: últimos 100. Si elegís un día: trae el parte de ese día.
            </div>
        </div>
        </form>
    </div>
    </div>

    <!-- Resultados -->
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Resultados</h5>
          <span class="text-muted"><?= count($rows) ?> registros</span>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle table-editable">
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
                  $fsPath  = __DIR__ . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileRel), DIRECTORY_SEPARATOR);
                  $exists  = $fileRel !== '' && is_file($fsPath);
                  $urlFile = rtrim($BASE,'/') . '/' . ltrim(str_replace('\\','/',$fileRel),'/');
                  $ext     = strtolower(pathinfo($fileRel, PATHINFO_EXTENSION));
                ?>
                <tr>
                  <td><?= (int)$r['id'] ?></td>
                  <td><?= h(date('d/m/Y', strtotime($r['fecha_desde']))) ?> – <?= h(date('d/m/Y', strtotime($r['fecha_hasta']))) ?></td>

                  <!-- Celda editable doble: oficial / suboficial -->
                  <td class="os-col">
                    <span class="editable os-of" contenteditable="plaintext-only"
                          data-id="<?= (int)$r['id'] ?>"
                          data-field="oficial_turno"
                          data-multiline="1"><?= h((string)($r['oficial_turno'] ?? '')) ?></span>
                    <span class="editable" contenteditable="plaintext-only"
                          data-id="<?= (int)$r['id'] ?>"
                          data-field="suboficial_turno"
                          data-multiline="1"><?= h((string)($r['suboficial_turno'] ?? '')) ?></span>
                  </td>

                  <!-- Título editable -->
                  <td>
                    <span class="editable" contenteditable="plaintext-only"
                          data-id="<?= (int)$r['id'] ?>"
                          data-field="titulo"><?= h((string)($r['titulo'] ?? '')) ?></span>
                  </td>

                  <td>
                    <?php if ($exists): ?>
                      <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= h($urlFile) ?>">
                        Abrir <?= $ext==='html' ? 'HTML' : 'PDF' ?>
                      </a>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark">No encontrado</span>
                    <?php endif; ?>
                  </td>
                  <td><?= h(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
                  <td><?= h((string)($r['creador'] ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </main>

  <script>
  // Guardado inline. Enter = guardar. Shift+Enter = salto de línea.
  (function(){
    function cleanFor(el){
      let s = el.innerText || '';
      if (el.dataset.multiline === '1') {
        // Conservar saltos de línea; normalizar espacios por línea
        s = s.replace(/\r/g,'');
        s = s.split('\n').map(line => line.trim()).join('\n');
        // Evitar demasiados saltos seguidos
        s = s.replace(/\n{3,}/g, '\n\n');
        return s;
      }
      // Campo de una línea
      return s.replace(/\s+/g,' ').trim();
    }

    function saveCell(el){
      const id    = parseInt(el.dataset.id || '0', 10);
      const field = el.dataset.field || '';
      const value = cleanFor(el);

      if (!id || !field) return;

      const body = new URLSearchParams({action:'upd', id:String(id), field, value});
      fetch('partes.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body
      })
      .then(r => r.json())
      .then(j => {
        if (!j || !j.ok) throw new Error(j && j.error ? j.error : 'Error');
        el.classList.add('saved');
        setTimeout(() => el.classList.remove('saved'), 700);
      })
      .catch(() => {
        el.classList.remove('saved');
        el.focus();
        alert('No se pudo guardar el cambio.');
      });
    }

    document.querySelectorAll('.editable').forEach(el => {
      el.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {  // Enter = guardar
          e.preventDefault();
          el.blur();
        }
        // Shift+Enter => permitimos salto de línea
      });
      el.addEventListener('paste', e => {
        e.preventDefault();
        const txt = (e.clipboardData || window.clipboardData).getData('text') || '';
        if (el.dataset.multiline === '1') {
          // Pegado multilinea: normalizamos cada renglón
          const cleaned = txt.replace(/\r/g,'')
                             .split('\n').map(t => t.trim()).join('\n')
                             .replace(/\n{3,}/g, '\n\n');
          document.execCommand('insertText', false, cleaned);
        } else {
          document.execCommand('insertText', false, txt.replace(/\s+/g,' ').trim());
        }
      });
      el.addEventListener('blur', () => saveCell(el));
    });
  })();
  </script>
</body>
</html>
