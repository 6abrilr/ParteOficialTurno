<?php
// php/api_parte.php
require_once __DIR__.'/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $row = pdo()->query("SELECT * FROM parte_encabezado ORDER BY id DESC LIMIT 1")->fetch();
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($row ?: [], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($method === 'POST') {
  $d = json_decode(file_get_contents('php://input'), true);
  if (!$d) $d = $_POST;
  $sql = "INSERT INTO parte_encabezado (fecha_desde, fecha_hasta, oficial_turno, suboficial_turno)
          VALUES (?,?,?,?)";
  pdo()->prepare($sql)->execute([
    $d['fecha_desde'], $d['fecha_hasta'],
    $d['oficial_turno'], $d['suboficial_turno']
  ]);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
  exit;
}

http_response_code(405);
