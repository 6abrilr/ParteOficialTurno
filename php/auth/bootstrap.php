<?php
// php/auth/bootstrap.php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

/** Detecta HTTPS (incluye proxies) */
function is_https(): bool {
  if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') return true;
  if (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443') return true;
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') return true;
  return false;
}

/** Sesión endurecida */
function start_secure_session(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;

  session_name('POTSESSID');
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);

  ini_set('session.use_strict_mode', '1');
  ini_set('session.cookie_httponly', '1');
  ini_set('session.cookie_samesite', 'Lax');
  ini_set('session.use_only_cookies', '1');

  session_start();
}

/** Helpers de URL */
function app_base(): string {
  $dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
  $base = preg_replace('#/public$#', '', $dir) ?: '/';
  return $base;
}
function url(string $path = ''): string {
  $base = app_base();
  $path = ltrim($path, '/');
  return rtrim($base, '/') . '/' . $path;
}

/** Escapar HTML (solo si no existe ya) */
if (!function_exists('h')) {
  function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  }
}

start_secure_session();

/* ===== Auth/roles fallbacks (no pisan si ya existen) ===== */
if (!function_exists('user')) {
  function user(): ?array { return $_SESSION['user'] ?? null; }
}

if (!function_exists('require_login')) {
  function require_login(): void {
    if (!user()) {
      $next = $_SERVER['REQUEST_URI'] ?? '/';
      header('Location: ' . url('login.php') . '?next=' . urlencode($next));
      exit;
    }
  }
}

if (!function_exists('user_has_role')) {
  function user_has_role(string $role): bool {
    $u = user();
    if (!$u) return false;
    $roles = $u['roles'] ?? [];
    foreach ($roles as $r) {
      if (is_array($r) && ($r['slug'] ?? null) === $role) return true;
      if (is_string($r) && $r === $role) return true;
    }
    return false;
  }
}

if (!function_exists('require_role')) {
  function require_role(string $role): void {
    require_login();
    if (!user_has_role($role)) {
      http_response_code(403);
      echo 'Acceso denegado.';
      exit;
    }
  }
}
