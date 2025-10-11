<?php
declare(strict_types=1);
require_once __DIR__ . '/php/auth/bootstrap.php';
require_role('admin');

$pdo = db();
$err = $ok = null;

// --- Acciones POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $action = $_POST['action'] ?? '';

  try {
    if ($action === 'crear') {
      $email  = trim((string)($_POST['email'] ?? ''));
      $nombre = trim((string)($_POST['nombre'] ?? ''));
      $pass   = (string)($_POST['pass'] ?? '');
      $roles  = array_values(array_filter((array)($_POST['roles'] ?? []))); // slugs

      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Email inválido');
      if (strlen($pass) < 8) throw new Exception('La contraseña debe tener 8+ caracteres');

      if (function_exists('user_create')) {
        user_create($email, $nombre, $pass, $roles ?: ['viewer']);
      } else {
        // Fallback directo (si no tenés user_create)
        $pdo->beginTransaction();
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (email, nombre, password, activo, created_at) VALUES (?,?,?,?,NOW())');
        $stmt->execute([$email, $nombre, $hash, 1]);

        $uid = (int)$pdo->lastInsertId();
        if ($roles) {
          $in  = implode(',', array_fill(0, count($roles), '?'));
          $rws = $pdo->prepare("SELECT id, slug FROM roles WHERE slug IN ($in)");
          $rws->execute($roles);
          $map = $rws->fetchAll(PDO::FETCH_KEY_PAIR); // slug => id

          $ins = $pdo->prepare('INSERT INTO user_role (user_id, role_id) VALUES (?,?)');
          foreach ($roles as $slug) {
            if (isset($map[$slug])) $ins->execute([$uid, (int)$map[$slug]]);
          }
        }
        $pdo->commit();
      }

      header('Location: ' . url('usuarios.php') . '?ok=1'); exit;
    }

    if ($action === 'toggle') {
      $uid    = (int)($_POST['uid'] ?? 0);
      $activo = (int)($_POST['activo'] ?? 0) ? 1 : 0;
      $pdo->prepare('UPDATE users SET activo=? WHERE id=?')->execute([$activo, $uid]);
      header('Location: ' . url('usuarios.php')); exit;
    }

    if ($action === 'update') {
      $uid    = (int)($_POST['uid'] ?? 0);
      $email  = trim((string)($_POST['email'] ?? ''));
      $nombre = trim((string)($_POST['nombre'] ?? ''));

      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Email inválido');

      $stmt = $pdo->prepare('UPDATE users SET email=?, nombre=? WHERE id=?');
      $stmt->execute([$email, $nombre, $uid]);

      header('Location: ' . url('usuarios.php') . '?ok=1'); exit;
    }

    if ($action === 'set_roles') {
      $uid   = (int)($_POST['uid'] ?? 0);
      $slugs = array_values(array_filter((array)($_POST['roles'] ?? [])));

      $pdo->beginTransaction();
      $pdo->prepare('DELETE FROM user_role WHERE user_id=?')->execute([$uid]);

      if ($slugs) {
        $in  = implode(',', array_fill(0, count($slugs), '?'));
        $rs  = $pdo->prepare("SELECT id, slug FROM roles WHERE slug IN ($in)");
        $rs->execute($slugs);
        $map = $rs->fetchAll(PDO::FETCH_KEY_PAIR); // slug => id

        $ins = $pdo->prepare('INSERT INTO user_role (user_id, role_id) VALUES (?,?)');
        foreach ($slugs as $slug) {
          if (isset($map[$slug])) $ins->execute([$uid, (int)$map[$slug]]);
        }
      }
      $pdo->commit();

      header('Location: ' . url('usuarios.php') . '?ok=1'); exit;
    }

    if ($action === 'reset_pass') {
      $uid     = (int)($_POST['uid'] ?? 0);
      $newpass = (string)($_POST['newpass'] ?? '');
      if (strlen($newpass) < 8) throw new Exception('La nueva contraseña debe tener 8+ caracteres');

      if (function_exists('user_set_password')) {
        user_set_password($uid, $newpass);
      } else {
        $hash = password_hash($newpass, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $uid]);
      }
      header('Location: ' . url('usuarios.php') . '?ok=1'); exit;
    }

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) { try { $pdo->rollBack(); } catch(Throwable $e2){} }
    $err = $e->getMessage();
  }
}

// --- Datos para la vista ---
$users = $pdo->query('SELECT id,email,nombre,activo,last_login,created_at FROM users ORDER BY id DESC')
             ->fetchAll(PDO::FETCH_ASSOC);

// roles: id, slug, nombre
$rolesAll = $pdo->query('SELECT id, slug, nombre FROM roles ORDER BY slug')->fetchAll(PDO::FETCH_ASSOC);

// roles por usuario (user_id => [slug, slug, ...])
$rolesSlug = $pdo->query('
  SELECT ur.user_id, r.slug
  FROM user_role ur
  JOIN roles r ON r.id = ur.role_id
')->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN);

// Para assets (logo)
$ASSETS = rtrim(app_base(), '/') . '/public';
$me = user();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Usuarios – B Com 602</title>
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
    html,body{ height:100% }
    body{ margin:0; color:#212529; background:#000 }

    .page-bg{
      position:fixed; inset:0; z-index:-2; pointer-events:none;
      background:
        radial-gradient(1200px 800px at 78% 24%, rgba(30,123,220,var(--glow-strength)) 0%, rgba(30,123,220,0) 60%),
        radial-gradient(1000px 700px at 12% 82%, rgba(30,123,220,.35) 0%, rgba(30,123,220,0) 60%),
        linear-gradient(160deg, var(--ink) 0%, var(--deep) 55%, #071020 100%);
      background-attachment: fixed,fixed,fixed;
      filter: saturate(1.05);
    }
    .page-bg::before{
      content:""; position:absolute; inset:0; z-index:-1; opacity:.22;
      background-image:
        radial-gradient(1.4px 1.4px at 18% 22%, #9cd1ff 20%, transparent 60%),
        radial-gradient(1.2px 1.2px at 63% 48%, #b7ddff 20%, transparent 60%),
        radial-gradient(1.2px 1.2px at 82% 70%, #b7ddff 20%, transparent 60%),
        radial-gradient(1.6px 1.6px at 34% 76%, #cbe8ff 20%, transparent 60%),
        radial-gradient(1.1px 1.1px at 72% 16%, #a7d6ff 20%, transparent 60%);
      background-repeat:no-repeat;
      background-size: 1200px 800px, 1400px 900px, 1100px 900px, 1400px 1000px, 1300px 800px;
      background-position: 0 0, 30% 40%, 80% 60%, 10% 90%, 70% 10%;
    }
    .mesh{
      position:fixed; right:-220px; top:-140px; width:1400px; height:900px; z-index:-1; opacity:var(--mesh-opacity);
      background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1400' height='900' viewBox='0 0 1400 900'%3E%3Cg fill='none' stroke='%23a6c9ff' stroke-opacity='.40' stroke-width='1.1'%3E%3Cpath d='M860 60 L1120 180 L980 300 L1260 360 L1360 240'/%3E%3Cpath d='M1020 520 L1240 430 L1360 580'/%3E%3Cpath d='M900 240 L1120 360 L1280 260'/%3E%3Cpath d='M940 720 L1200 600 L1340 740'/%3E%3C/g%3E%3Cg fill='%23e9f4ff' fill-opacity='.95'%3E%3Ccircle cx='860' cy='60' r='3'/%3E%3Ccircle cx='1120' cy='180' r='2.5'/%3E%3Ccircle cx='980' cy='300' r='2.5'/%3E%3Ccircle cx='1260' cy='360' r='3'/%3E%3Ccircle cx='1360' cy='240' r='2.5'/%3E%3Ccircle cx='1020' cy='520' r='2.6'/%3E%3Ccircle cx='1240' cy='430' r='2.4'/%3E%3Ccircle cx='1360' cy='580' r='2.6'/%3E%3Ccircle cx='900' cy='240' r='2.5'/%3E%3Ccircle cx='1120' cy='360' r='2.4'/%3E%3Ccircle cx='1280' cy='260' r='2.8'/%3E%3Ccircle cx='940' cy='720' r='2.4'/%3E%3Ccircle cx='1200' cy='600' r='2.8'/%3E%3Ccircle cx='1340' cy='740' r='2.5'/%3E%3C/g%3E%3C/svg%3E") no-repeat center/contain;
      mix-blend-mode:screen; filter:drop-shadow(0 0 35px rgba(124,196,255,.25)); pointer-events:none;
    }
    .mesh.mesh--left{ left:-260px; top:180px; right:auto; transform:scaleX(-1) rotate(3deg); }

    .brand-hero{ position:relative; padding:28px 0 30px; color:#e9f2ff; isolation:isolate; }
    .hero-inner{ display:flex; align-items:center; gap:14px; }
    .brand-logo{ width:56px; height:56px; object-fit:contain; flex:0 0 auto; filter:drop-shadow(0 2px 10px rgba(124,196,255,.30)); }
    .brand-title{ font-weight:800; letter-spacing:.4px; font-size:28px; line-height:1.1; text-shadow:0 2px 16px rgba(30,123,220,.45); }
    .brand-sub{ font-size:16px; opacity:.9; border-top:2px solid rgba(124,196,255,.35); display:inline-block; padding-top:4px; margin-top:2px; }
    .brand-year{ margin-left:auto; font-size:28px; font-weight:700; opacity:.85; }

    @media (min-width:1200px){ .container{ max-width:var(--container-max) !important; } }
    .card{ border-radius:14px; border:1px solid var(--card-border); box-shadow:var(--shadow); background:var(--card-bg); }
    .card .card-body{ padding:20px; }

    .form-label{ font-size:.9rem; text-transform:uppercase; letter-spacing:.04em; color:#495057; }
    .btn{ border-radius:10px; }
    .btn-primary{ background:var(--primary); border-color:var(--primary); }
    .btn-primary:hover{ background:var(--primary-2); border-color:var(--primary-2); }
  </style>
</head>
<body>

  <div class="page-bg"></div>
  <span class="mesh"></span>
  <span class="mesh mesh--left"></span>

  <!-- Hero -->
  <header class="brand-hero">
    <div class="hero-inner container">
      <img class="brand-logo" src="<?= h($ASSETS) ?>/img/escudo602sinfondo.png" alt="Escudo 602">
      <div>
        <div class="brand-title">Gestión de Usuarios</div>
        <div class="brand-sub">Batallón de Comunicaciones 602</div>
      </div>
      <div class="brand-year"><?= date('Y') ?></div>
    </div>
  </header>

  <main class="container my-4">

    <div class="d-flex justify-content-between align-items-center mb-3 text-light">
      <div>Hola, <strong><?= h($me['nombre'] ?? $me['email'] ?? 'admin') ?></strong></div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-light btn-sm" href="<?= h(url('admin.php')) ?>">Volver</a>
        <a class="btn btn-outline-light btn-sm" href="<?= h(url('logout.php')) ?>">Salir</a>
      </div>
    </div>

    <?php if(!empty($_GET['ok'])): ?>
      <div class="alert alert-success">Operación realizada correctamente.</div>
    <?php endif; ?>
    <?php if($err): ?>
      <div class="alert alert-danger"><?= h($err) ?></div>
    <?php endif; ?>

    <!-- Crear usuario -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="mb-3">Crear usuario</h5>
        <form method="post" class="row g-2">
          <?= csrf_input() ?>
          <input type="hidden" name="action" value="crear">
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" placeholder="Email" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Nombre</label>
            <input name="nombre" class="form-control" placeholder="Nombre">
          </div>
          <div class="col-md-3">
            <label class="form-label">Contraseña</label>
            <input name="pass" type="password" class="form-control" placeholder="Contraseña (8+)" minlength="8" required>
          </div>
          <div class="col-12 mt-2">
            <div class="mb-1">Roles</div>
            <?php foreach ($rolesAll as $r): ?>
              <label class="me-3">
                <input type="checkbox" name="roles[]" value="<?= h($r['slug']) ?>"> <?= h($r['nombre']) ?>
                <small class="text-muted">(<?= h($r['slug']) ?>)</small>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="col-12 mt-2">
            <button class="btn btn-primary">Crear</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabla usuarios -->
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Listado</h5>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>ID</th><th>Email</th><th>Nombre</th><th>Roles</th><th>Último acceso</th><th>Estado</th><th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach($users as $u): $r = $rolesSlug[$u['id']] ?? []; ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= h($u['email']) ?></td>
                <td><?= h($u['nombre'] ?? '') ?></td>
                <td><?= $r ? h(implode(', ',$r)) : '–' ?></td>
                <td><?= $u['last_login'] ? h($u['last_login']) : '—' ?></td>
                <td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
                <td class="text-end">
                  <!-- Activar/Desactivar -->
                  <form method="post" class="d-inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                    <input type="hidden" name="activo" value="<?= $u['activo']?0:1 ?>">
                    <button class="btn btn-sm <?= $u['activo']?'btn-outline-danger':'btn-outline-success'?>" onclick="return confirm('¿Seguro?')">
                      <?= $u['activo']?'Desactivar':'Activar'?>
                    </button>
                  </form>

                  <!-- Editar email/nombre (inline) -->
                  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit<?= (int)$u['id'] ?>">Editar</button>
                </td>
              </tr>
              <tr class="collapse" id="edit<?= (int)$u['id'] ?>">
                <td colspan="7">
                  <div class="p-3 border rounded bg-light">
                    <div class="row g-3">
                      <!-- Editar datos -->
                      <form method="post" class="col-md-6">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                        <div class="mb-2">
                          <label class="form-label">Email</label>
                          <input name="email" type="email" class="form-control" value="<?= h($u['email']) ?>" required>
                        </div>
                        <div class="mb-2">
                          <label class="form-label">Nombre</label>
                          <input name="nombre" class="form-control" value="<?= h($u['nombre'] ?? '') ?>">
                        </div>
                        <button class="btn btn-primary btn-sm">Guardar</button>
                      </form>

                      <!-- Reset pass -->
                      <form method="post" class="col-md-6">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="reset_pass">
                        <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                        <div class="mb-2">
                          <label class="form-label">Resetear contraseña</label>
                          <input name="newpass" type="password" class="form-control" placeholder="Nueva contraseña (8+)" minlength="8" required>
                        </div>
                        <button class="btn btn-warning btn-sm">Cambiar contraseña</button>
                      </form>
                    </div>

                    <hr>

                    <!-- Roles -->
                    <form method="post" class="mt-2">
                      <?= csrf_input() ?>
                      <input type="hidden" name="action" value="set_roles">
                      <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                      <div class="mb-2">Roles:</div>
                      <?php foreach ($rolesAll as $rAll): ?>
                        <?php $checked = in_array($rAll['slug'], $r, true) ? 'checked' : ''; ?>
                        <label class="me-3">
                          <input type="checkbox" name="roles[]" value="<?= h($rAll['slug']) ?>" <?= $checked ?>>
                          <?= h($rAll['nombre']) ?> <small class="text-muted">(<?= h($rAll['slug']) ?>)</small>
                        </label>
                      <?php endforeach; ?>
                      <div class="mt-2">
                        <button class="btn btn-outline-primary btn-sm">Actualizar roles</button>
                      </div>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach;?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
