<?php
// php/api_redise.php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/db.php';

function out($arr, int $code=200){ http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

try{
  $pdo    = db();
  $action = $_GET['action'] ?? '';
  $raw    = file_get_contents('php://input') ?: '';
  $body   = $raw ? (json_decode($raw, true) ?: []) : [];

  if ($action === 'save') {
    // Espera: { turno:"26SEP25", texto_ccc:"...", data:[...] }
    $turno = strtoupper(trim($body['turno'] ?? ''));
    $texto = (string)($body['texto_ccc'] ?? '');
    $data  = json_encode($body['data'] ?? [], JSON_UNESCAPED_UNICODE);

    if (!$turno || !$texto) out(['ok'=>false,'error'=>'Faltan datos (turno/texto)'],400);

    // UPSERT por turno
    $sql = "INSERT INTO redise_snapshot (turno,texto_ccc,data_json)
            VALUES (:t,:x,:j)
            ON DUPLICATE KEY UPDATE texto_ccc=VALUES(texto_ccc), data_json=VALUES(data_json), creado_en=NOW()";
    $st = $pdo->prepare($sql);
    $st->execute([':t'=>$turno, ':x'=>$texto, ':j'=>$data]);
    out(['ok'=>true,'turno'=>$turno]);
  }

  if ($action === 'last') {
    $st = $pdo->query("SELECT id, turno, creado_en, texto_ccc, data_json
                       FROM redise_snapshot ORDER BY creado_en DESC LIMIT 1");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) out(['ok'=>true,'found'=>false,'snapshot'=>null]);
    $row['data_json'] = json_decode($row['data_json'], true);
    out(['ok'=>true,'found'=>true,'snapshot'=>$row]);
  }

  out(['ok'=>false,'error'=>'Acción no válida'],400);

}catch(Throwable $e){
  out(['ok'=>false,'error'=>$e->getMessage()],500);
}
