<?php
// php/api_sistemas.php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/db.php';
function out($arr, int $code=200){ http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

try {
  $pdo = db();
  $action = $_GET['action'] ?? '';

  if ($action === 'listar') {
    $cat = (int)($_GET['cat'] ?? 0);
    if ($cat <= 0) out([]); // evita 500 si falta cat

    $st = $pdo->prepare(
      "SELECT id, nombre, estado, novedad, ticket
         FROM sistema_estado
        WHERE categoria_id = :c
        ORDER BY nombre"
    );
    $st->execute([':c'=>$cat]);
    $rows = $st->fetchAll() ?: [];
    out(array_map(fn($r) => [
      'id'      => (int)$r['id'],
      'nombre'  => $r['nombre'],
      'estado'  => $r['estado'] ?: 'EN LINEA',
      'novedad' => $r['novedad'] ?: '',
      'ticket'  => $r['ticket'] ?: '',
    ], $rows));
  }

  if ($action === 'guardar') {
    $raw = file_get_contents('php://input') ?: '{}';
    $p = json_decode($raw, true) ?: [];
    $st = $pdo->prepare(
      "UPDATE sistema_estado
          SET estado=:e, novedad=:n, ticket=:t
        WHERE id=:id"
    );
    $st->execute([
      ':e'  => $p['estado'] ?? 'EN LINEA',
      ':n'  => $p['novedad'] ?? '',
      ':t'  => $p['ticket'] ?? '',
      ':id' => (int)($p['id'] ?? 0),
    ]);
    out(['ok'=>true]);
  }

  out(['ok'=>false,'error'=>'Acción no válida'],400);

} catch (Throwable $e) {
  out(['ok'=>false,'error'=>$e->getMessage()],500);
}
