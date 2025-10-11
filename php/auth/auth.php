<?php
// php/auth/auth.php
declare(strict_types=1);

function auth_login(string $email, string $password): bool {
  $pdo = db();
  $st = $pdo->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
  $st->execute([':e'=>$email]);
  $u = $st->fetch(PDO::FETCH_ASSOC);

  $success = false; $reason = null;
  if (!$u) {
    $reason = 'usuario_inexistente';
  } elseif (!(int)$u['activo']) {
    $reason = 'usuario_inactivo';
  } elseif (!password_verify($password, $u['password_hash'])) {
    $reason = 'password_invalido';
  } else {
    $success = true;
  }

  // auditoría
  $pdo->prepare('INSERT INTO login_audit (user_id,email,success,reason,ip,user_agent) VALUES (?,?,?,?,?,?)')
      ->execute([$u['id'] ?? null, $email, $success?1:0, $reason, $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255)]);

  if (!$success) return false;

  // registra sesión
  $_SESSION['uid']   = (int)$u['id'];
  $_SESSION['email'] = $u['email'];
  $_SESSION['name']  = $u['nombre'];
  $_SESSION['roles'] = user_roles((int)$u['id']);

  session_regenerate_id(true);
  $pdo->prepare('INSERT INTO user_sessions (user_id, session_id, ip, user_agent) VALUES (?,?,?,?)')
      ->execute([$u['id'], session_id(), $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255)]);
  $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$u['id']]);

  return true;
}

function auth_logout(): void {
  $pdo = db();
  if (session_status() === PHP_SESSION_ACTIVE) {
    $sid = session_id();
    if ($sid) {
      $pdo->prepare('DELETE FROM user_sessions WHERE session_id=?')->execute([$sid]);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
  }
}

function user(): ?array {
  if (empty($_SESSION['uid'])) return null;
  return ['id'=>$_SESSION['uid'], 'email'=>$_SESSION['email'] ?? null, 'nombre'=>$_SESSION['name'] ?? null, 'roles'=>$_SESSION['roles'] ?? []];
}
function user_roles(int $uid): array {
  $pdo = db();
  $st = $pdo->prepare('SELECT r.slug FROM user_role ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=?');
  $st->execute([$uid]);
  return $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
}
function has_role(string $role): bool {
  return in_array($role, $_SESSION['roles'] ?? [], true);
}
function require_login(): void {
  if (!user()) {
    header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/')); exit;
  }
}
function require_role(string $role): void {
  require_login();
  if (!has_role($role)) { http_response_code(403); exit('Acceso denegado'); }
}

// Alta/edición
function user_create(string $email, string $nombre, string $password, array $roles=['viewer'], bool $activo=true): int {
  $pdo = db();
  $pdo->beginTransaction();
  $pdo->prepare('INSERT INTO users (email,nombre,activo,password_hash) VALUES (?,?,?,?)')
      ->execute([$email, $nombre, $activo?1:0, password_hash($password, PASSWORD_DEFAULT)]);
  $uid = (int)$pdo->lastInsertId();

  // asignar roles
  $stRole = $pdo->prepare('SELECT id FROM roles WHERE slug=?');
  $stLink = $pdo->prepare('INSERT INTO user_role (user_id, role_id) VALUES (?,?)');
  foreach ($roles as $slug) {
    $stRole->execute([$slug]);
    if ($rid = $stRole->fetchColumn()) $stLink->execute([$uid, $rid]);
  }
  $pdo->commit();
  return $uid;
}

// Reset por token
function password_reset_request(string $email): ?string {
  $pdo = db();
  $st = $pdo->prepare('SELECT id FROM users WHERE email=? AND activo=1');
  $st->execute([$email]);
  $uid = $st->fetchColumn();
  if (!$uid) return null;

  $token = bin2hex(random_bytes(32));
  $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at, ip) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 60 MINUTE), ?)')
      ->execute([$uid,$token,$_SERVER['REMOTE_ADDR'] ?? null]);
  return $token; // envíalo por mail
}
function password_reset_apply(string $token, string $newpass): bool {
  $pdo = db();
  $st = $pdo->prepare('SELECT id,user_id,expires_at,used_at FROM password_resets WHERE token=?');
  $st->execute([$token]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  if (!$r || $r['used_at'] || strtotime((string)$r['expires_at']) < time()) return false;

  $pdo->beginTransaction();
  $pdo->prepare('UPDATE users SET password_hash=?, force_change=0 WHERE id=?')->execute([password_hash($newpass, PASSWORD_DEFAULT), $r['user_id']]);
  $pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=?')->execute([$r['id']]);
  $pdo->commit();
  return true;
}
