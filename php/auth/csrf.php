<?php
// php/auth/csrf.php
declare(strict_types=1);

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}
function csrf_input(): string {
  $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
  return '<input type="hidden" name="csrf" value="'.$t.'">';
}
function csrf_verify(): void {
  $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
  if (!$ok) {
    http_response_code(400);
    exit('CSRF inválido');
  }
}
