<?php
declare(strict_types=1);
require_once __DIR__.'/db.php';
try {
  db()->query('SELECT 1');
  echo json_encode(['ok'=>true,'msg'=>'DB OK'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
