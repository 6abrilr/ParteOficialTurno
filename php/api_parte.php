<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $raw = file_get_contents('php://input') ?: '';
  $j = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

  $fd = trim((string)($j['fecha_desde'] ?? ''));
  $fh = trim((string)($j['fecha_hasta'] ?? ''));
  $of = trim((string)($j['oficial_turno'] ?? ''));
  $su = trim((string)($j['suboficial_turno'] ?? ''));

  if ($fd === '' || $fh === '') throw new InvalidArgumentException('Falta fecha_desde/fecha_hasta');
  if ($of === '' || $su === '') throw new InvalidArgumentException('Falta oficial/suboficial de turno');

  // Normalizar a 'Y-m-d H:i:s' (viene como local datetime en el input)
  $toDt = function(string $s): string {
    $dt = new DateTime($s);
    return $dt->format('Y-m-d H:i:s');
  };
  $fdSql = $toDt($fd);
  $fhSql = $toDt($fh);

  $pdo = db();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // UPSERT por la UNIQUE (fecha_desde, fecha_hasta)
  $sql = "
    INSERT INTO parte_encabezado (fecha_desde, fecha_hasta, oficial_turno, suboficial_turno)
    VALUES (:fd, :fh, :of, :su)
    ON DUPLICATE KEY UPDATE
      oficial_turno = VALUES(oficial_turno),
      suboficial_turno = VALUES(suboficial_turno)
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':fd'=>$fdSql, ':fh'=>$fhSql, ':of'=>$of, ':su'=>$su]);

  echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'type'=>get_class($e)], JSON_UNESCAPED_UNICODE);
}
