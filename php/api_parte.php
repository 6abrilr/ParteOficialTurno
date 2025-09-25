<?php
// Guarda encabezado del parte (fechas y firmas)
require_once __DIR__.'/db.php';

if ($_SERVER['REQUEST_METHOD']!=='POST') { json_out(['error'=>'Método no permitido'],405); }

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$desde = $body['fecha_desde'] ?? null;
$hasta = $body['fecha_hasta'] ?? null;
$of    = trim($body['oficial_turno'] ?? '');
$sub   = trim($body['suboficial_turno'] ?? '');
if(!$desde || !$hasta){ json_out(['error'=>'Faltan fechas'],400); }

$st = pdo()->prepare("INSERT INTO parte_encabezado (fecha_desde,fecha_hasta,oficial_turno,suboficial_turno) VALUES (?,?,?,?)");
$st->execute([$desde,$hasta,$of,$sub]);

json_out(['ok'=>true,'id'=>pdo()->lastInsertId()]);
