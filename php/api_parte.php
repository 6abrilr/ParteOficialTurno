<?php
// php/api_parte.php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/db.php';

function out($arr, int $code=200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

try{
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  $pdo = db(); // ← FIX: usar db(), no pdo()

  if ($method === 'POST') {
    $raw = file_get_contents('php://input') ?: '';
    $p = $raw ? (json_decode($raw, true) ?: []) : [];

    $fd = $p['fecha_desde']      ?? '';
    $fh = $p['fecha_hasta']      ?? '';
    $of = trim($p['oficial_turno']    ?? '');
    $sf = trim($p['suboficial_turno'] ?? '');

    if (!$fd || !$fh) out(['ok'=>false,'error'=>'Fechas requeridas'], 400);

    $st = $pdo->prepare("INSERT INTO parte_encabezado (fecha_desde,fecha_hasta,oficial_turno,suboficial_turno)
                         VALUES (:d,:h,:o,:s)");
    $st->execute([':d'=>$fd, ':h'=>$fh, ':o'=>$of, ':s'=>$sf]);

    out(['ok'=>true, 'id'=>(int)$pdo->lastInsertId()]);
  }

  // Opcional: traer el último encabezado guardado
  if ($method === 'GET' && ($_GET['action'] ?? '') === 'last') {
    $row = $pdo->query("SELECT * FROM parte_encabezado ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$row) out(['ok'=>true,'found'=>false]);
    out(['ok'=>true,'found'=>true,'data'=>$row]);
  }

  out(['ok'=>false,'error'=>'Método no soportado'], 405);

} catch (Throwable $e){
  out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
