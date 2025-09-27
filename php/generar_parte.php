<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
  // Entradas
  $desde = trim($_POST['desde'] ?? '');
  $hasta = trim($_POST['hasta'] ?? '');
  $of    = trim($_POST['oficial'] ?? '');
  $sub   = trim($_POST['suboficial'] ?? '');
  if ($desde === '' || $hasta === '') throw new Exception('Faltan fechas (desde / hasta)');

  // Normalizo
  $desdeDt = date('Y-m-d H:i:s', strtotime($desde));
  $hastaDt = date('Y-m-d H:i:s', strtotime($hasta));

  $pdo = db();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  /* IMPORTANTE:
     Tu tabla `parte_encabezado` NO tiene created_at/updated_at.
     Hacemos UPSERT sólo con las columnas existentes.
     Requiere UNIQUE( fecha_desde, fecha_hasta ). Si aún no lo tenés,
     primero limpiá duplicados y luego creá el índice (SQL al final).
  */
  $sql = "
    INSERT INTO parte_encabezado
      (fecha_desde, fecha_hasta, oficial_turno, suboficial_turno)
    VALUES
      (:desde, :hasta, :of, :sub)
    ON DUPLICATE KEY UPDATE
      oficial_turno    = VALUES(oficial_turno),
      suboficial_turno = VALUES(suboficial_turno),
      id               = LAST_INSERT_ID(id)
  ";
  $st = $pdo->prepare($sql);
  $st->execute([
    ':desde' => $desdeDt,
    ':hasta' => $hastaDt,
    ':of'    => $of,
    ':sub'   => $sub,
  ]);
  $parteId = (int)$pdo->lastInsertId();

  // En vez de generar un HTML “minimal”, devolvemos tu vista completa:
  // public/parte.php toma ?desde=&hasta=
  $previewUrl = sprintf('../public/parte.php?desde=%s&hasta=%s',
                        urlencode($desdeDt), urlencode($hastaDt));

  echo json_encode([
    'ok'    => true,
    'turno' => date('d/M/y', strtotime($desdeDt)) . ' - ' . date('d/M/y', strtotime($hastaDt)),
    'html'  => $previewUrl,
    'pdf'   => null
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
