<?php
declare(strict_types=1);
require_once __DIR__ . '/php/auth/bootstrap.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_verify();
  $email  = trim($_POST['email'] ?? '');
  $nombre = trim($_POST['nombre'] ?? '');
  $pass   = (string)($_POST['pass'] ?? '');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
    $err = 'Datos inválidos';
  } else {
    try {
      $uid = user_create($email, $nombre, $pass, ['admin']);
      echo "<p>Admin creado (ID $uid). Eliminá este archivo.</p>";
      exit;
    } catch (Throwable $e) { $err = $e->getMessage(); }
  }
}
?>
<!doctype html><meta charset="utf-8">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container py-5" style="max-width:560px">
  <h3>Crear Administrador</h3>
  <?php if (!empty($err)): ?><div class="alert alert-danger"><?=htmlspecialchars($err)?></div><?php endif; ?>
  <form method="post">
    <?= csrf_input() ?>
    <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Nombre</label><input name="nombre" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Contraseña</label><input name="pass" type="password" class="form-control" minlength="8" required></div>
    <button class="btn btn-primary">Crear admin</button>
  </form>
</div>
