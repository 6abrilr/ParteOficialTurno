<?php
declare(strict_types=1);
require_once __DIR__ . '/php/auth/bootstrap.php';

$token = $_GET['token'] ?? '';
$ok = null; $err = null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_verify();
  $token = (string)($_POST['token'] ?? '');
  $p1 = (string)($_POST['p1'] ?? '');
  $p2 = (string)($_POST['p2'] ?? '');
  if ($p1 !== $p2 || strlen($p1) < 8) {
    $err = 'La contraseña debe coincidir y tener 8+ caracteres.';
  } else {
    $ok = password_reset_apply($token, $p1);
    if (!$ok) $err = 'Token inválido o expirado.';
  }
}
?>
<!doctype html><meta charset="utf-8">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container py-5" style="max-width:520px">
  <h3>Establecer nueva contraseña</h3>
  <?php if($ok): ?>
    <div class="alert alert-success">Contraseña actualizada. <a href="/login.php">Ingresar</a></div>
  <?php else: ?>
    <?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
      <div class="mb-3"><label class="form-label">Nueva contraseña</label><input class="form-control" name="p1" type="password" required></div>
      <div class="mb-3"><label class="form-label">Repetir contraseña</label><input class="form-control" name="p2" type="password" required></div>
      <button class="btn btn-primary">Guardar</button>
    </form>
  <?php endif; ?>
</div>
