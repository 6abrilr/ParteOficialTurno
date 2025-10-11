<?php
declare(strict_types=1);
require_once __DIR__ . '/php/auth/bootstrap.php';

$sent = false; $link = null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_verify();
  $email = trim($_POST['email'] ?? '');
  if ($email) {
    $token = password_reset_request($email);
    // Aquí podrías enviar email. Para pruebas mostramos el enlace:
    if ($token) {
      $link = (isset($_SERVER['HTTPS'])?'https':'http') . '://' . $_SERVER['HTTP_HOST'] . '/reset.php?token=' . urlencode($token);
    }
  }
  $sent = true;
}
?>
<!doctype html><meta charset="utf-8">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container py-5" style="max-width:520px">
  <h3>Recuperar contraseña</h3>
  <?php if($sent): ?>
    <div class="alert alert-info">Si el email existe y está activo, se generó un enlace de recuperación.</div>
    <?php if($link): ?><p><strong>Enlace (pruebas):</strong> <a href="<?=$link?>"><?=$link?></a></p><?php endif; ?>
  <?php endif; ?>
  <form method="post">
    <?= csrf_input() ?>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input name="email" type="email" class="form-control" required>
    </div>
    <button class="btn btn-primary">Enviar enlace</button>
  </form>
</div>
