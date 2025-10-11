<?php
declare(strict_types=1);
require_once __DIR__ . '/php/auth/bootstrap.php';
require_role('admin');

$pdo = db();
$err = $ok = null;

// Utilidades
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

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

      // user_create(email, nombre, pass, roles_slugs[]) -> si la tenés en auth.php, usala:
      if (function_exists('user_create')) {
        user_create($email, $nombre, $pass, $roles ?: ['viewer']);
      } else {
        // fallback directo a SQL (requiere columnas: users.email, users.nombre, users.password, users.activo)
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

      $ok = 'Usuario creado.';
      header('Location: ' . url('usuarios.php') . '?ok=1');
      exit;
    }

    if ($action === 'toggle') {
      $uid   = (int)($_POST['uid'] ?? 0);
      $activo= (int)($_POST['activo'] ?? 0) ? 1 : 0;
      $pdo->prepare('UPDATE users SET activo=? WHERE id=?')->execute([$activo, $uid]);
      header('Location: ' . url('usuarios.php'));
      exit;
    }

    if ($action === 'update') {
      // actualizar email / nombre
      $uid    = (int)($_POST['uid'] ?? 0);
      $email  = trim((string)($_POST['email'] ?? ''));
      $nombre = trim((string)($_POST['nombre'] ?? ''));

      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Email inválido');

      $stmt = $pdo->prepare('UPDATE users SET email=?, nombre=? WHERE id=?');
      $stmt->execute([$email, $nombre, $uid]);

      header('Location: ' . url('usuarios.php') . '?ok=1');
      exit;
    }

    if ($action === 'set_roles') {
      // asigna roles por slugs
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

      header('Location: ' . url('usuarios.php') . '?ok=1');
      exit;
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
      header('Location: ' . url('usuarios.php') . '?ok=1');
      exit;
    }

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) { try { $pdo->rollBack(); } catch(Throwable $e2){} }
    $err = $e->getMessage();
  }
}

// --- Datos para la vista ---
$users = $pdo->query('SELECT id,email,nombre,activo,last_login,created_at FROM users ORDER BY id DESC')
             ->fetchAll(PDO::FETCH_ASSOC);

// roles: necesitamos id, slug, nombre
$rolesAll = $pdo->query('SELECT id, slug, nombre FROM roles ORDER BY slug')->fetchAll(PDO::FETCH_ASSOC);

// roles por usuario (user_id => [slug, slug, ...])
$rolesSlug = $pdo->query('
  SELECT ur.user_id, r.slug
  FROM user_role ur
  JOIN roles r ON r.id = ur.role_id
')->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN);
?>
<!doctype html>
<meta charset="utf-8">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Usuarios</h3>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?= h(url('admin.php')) ?>">Panel</a>
      <a class="btn btn-outline-secondary" href="<?= h(url('logout.php')) ?>">Cerrar sesión</a>
    </div>
  </div>

  <?php if(!empty($_GET['ok'])): ?>
    <div class="alert alert-success">Operación realizada correctamente.</div>
  <?php endif; ?>
  <?php if($err): ?>
    <div class="alert alert-danger"><?= h($err) ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-body">
      <h5 class="mb-2">Crear usuario</h5>
      <form method="post" class="row g-2">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="crear">
        <div class="col-md-4">
          <input name="email" type="email" class="form-control" placeholder="Email" required>
        </div>
        <div class="col-md-3">
          <input name="nombre" class="form-control" placeholder="Nombre">
        </div>
        <div class="col-md-3">
          <input name="pass" type="password" class="form-control" placeholder="Contraseña (8+)" minlength="8" required>
        </div>
        <div class="col-12">
          <?php foreach ($rolesAll as $r): ?>
            <label class="me-3">
              <input type="checkbox" name="roles[]" value="<?= h($r['slug']) ?>"> <?= h($r['nombre']) ?>
              <small class="text-muted">(<?= h($r['slug']) ?>)</small>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="col-12">
          <button class="btn btn-primary">Crear</button>
        </div>
      </form>
    </div>
  </div>

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
              <div class="row g-2">
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

<!-- Bootstrap JS (colapse) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
