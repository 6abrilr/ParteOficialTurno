<?php
require_once __DIR__.'/config.php';

function pdo(): PDO {
  static $pdo=null;
  if($pdo===null){
    $dsn='mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
    $pdo=new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
  }
  return $pdo;
}
function json_out($data,int $code=200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
