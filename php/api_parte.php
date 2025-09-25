<?php
// php/api_parte.php
require_once __DIR__.'/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
  if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) throw new Exception('JSON inválido');

    $desde = trim($data['fecha_desde'] ?? '');
    $hasta = trim($data['fecha_hasta'] ?? '');
    $ofi   = trim($data['oficial_turno'] ?? '');
    $sub   = trim($data['suboficial_turno'] ?? '');

    if ($desde === '' || $hasta === '') throw new Exception('Faltan fecha_desde y/o fecha_hasta');
    if ($ofi === '' && $sub === '') throw new Exception('Cargá al menos Oficial o Suboficial de turno');

    // Normalizo a 'Y-m-d H:i:s' si vienen como datetime-local (sin TZ)
    $norm = function(string $s): string {
      // formatos típicos: "2025-09-24T08:00" | "2025-09-24 08:00" | "2025-09-24 08:00:00"
      $s = str_replace('T',' ',$s);
      if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/',$s)) $s .= ':00';
      return $s;
    };
    $desde = $norm($desde);
    $hasta = $norm($hasta);

    $sql = "INSERT INTO parte_encabezado (fecha_desde, fecha_hasta, oficial_turno, suboficial_turno)
            VALUES (?,?,?,?)";
    $st = pdo()->prepare($sql);
    $st->execute([$desde, $hasta, $ofi, $sub]);
    echo json_encode(['ok'=>true, 'id'=>pdo()->lastInsertId()], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // GET: devolver el último encabezado guardado (opcional, útil para precargar)
  if ($method === 'GET') {
    $row = pdo()->query("SELECT * FROM parte_encabezado ORDER BY id DESC LIMIT 1")->fetch();
    echo json_encode(['ok'=>true, 'data'=>$row ?: null], JSON_UNESCAPED_UNICODE);
    exit;
  }

  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Método no permitido'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
