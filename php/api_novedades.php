<?php
// php/api_novedades.php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/db.php';

function json_out($arr, int $code=200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db(); // PDO con ERRMODE_EXCEPTION

  $action = $_GET['action'] ?? '';
  $raw = file_get_contents('php://input') ?: '';
  $pay = $raw ? (json_decode($raw, true) ?: []) : [];

  if ($action === 'lista') {
    $sql = "SELECT n.id,
                   n.fecha_inicio,
                   n.titulo,
                   n.descripcion,
                   COALESCE(c.nombre,'') AS categoria,
                   COALESCE(u.nombre,'') AS unidad,
                   n.prioridad
            FROM novedad n
            LEFT JOIN categoria c ON c.id = n.categoria_id
            LEFT JOIN unidad    u ON u.id = n.unidad_id
            WHERE n.estado <> 'RESUELTO'
            ORDER BY n.fecha_inicio DESC";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    json_out(array_map(function($r){
      return [
        'id'           => (int)$r['id'],
        'fecha_inicio' => $r['fecha_inicio'],
        'titulo'       => $r['titulo'],
        'descripcion'  => $r['descripcion'],
        'categoria'    => $r['categoria'],
        'unidad'       => $r['unidad'],
        'prioridad'    => $r['prioridad'],
      ];
    }, $rows));
  }

  if ($action === 'crear') {
    $st = $pdo->prepare(
      "INSERT INTO novedad
         (titulo, descripcion, categoria_id, unidad_id, servicio, ticket, prioridad, estado, creado_por, fecha_inicio)
       VALUES
         (:titulo, :descripcion, :categoria_id, :unidad_id, :servicio, :ticket, :prioridad, 'ABIERTO', :creado_por, NOW())"
    );
    $st->execute([
      ':titulo'       => trim($pay['titulo'] ?? ''),
      ':descripcion'  => trim($pay['descripcion'] ?? ''),
      ':categoria_id' => (int)($pay['categoria_id'] ?? 1),
      ':unidad_id'    => ($pay['unidad_id'] ?? null) !== null ? (int)$pay['unidad_id'] : null,
      ':servicio'     => trim($pay['servicio'] ?? ''),
      ':ticket'       => trim($pay['ticket'] ?? ''),
      ':prioridad'    => in_array($pay['prioridad'] ?? 'MEDIA', ['BAJA','MEDIA','ALTA'], true) ? $pay['prioridad'] : 'MEDIA',
      ':creado_por'   => trim($pay['usuario'] ?? ''),
    ]);
    json_out(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
  }

  if ($action === 'actualizar') {
    $st = $pdo->prepare(
      "UPDATE novedad
          SET titulo=:titulo,
              descripcion=:descripcion,
              categoria_id=:categoria_id,
              unidad_id=:unidad_id,
              servicio=:servicio,
              ticket=:ticket,
              prioridad=:prioridad
        WHERE id=:id"
    );
    $st->execute([
      ':titulo'       => trim($pay['titulo'] ?? ''),
      ':descripcion'  => trim($pay['descripcion'] ?? ''),
      ':categoria_id' => (int)($pay['categoria_id'] ?? 1),
      ':unidad_id'    => ($pay['unidad_id'] ?? null) !== null ? (int)$pay['unidad_id'] : null,
      ':servicio'     => trim($pay['servicio'] ?? ''),
      ':ticket'       => trim($pay['ticket'] ?? ''),
      ':prioridad'    => in_array($pay['prioridad'] ?? 'MEDIA', ['BAJA','MEDIA','ALTA'], true) ? $pay['prioridad'] : 'MEDIA',
      ':id'           => (int)($pay['id'] ?? 0),
    ]);
    json_out(['ok'=>true]);
  }

  if ($action === 'resolver') {
    $st = $pdo->prepare("UPDATE novedad SET estado='RESUELTO', fecha_resolucion=NOW() WHERE id=:id");
    $st->execute([':id'=>(int)($pay['id'] ?? 0)]);
    json_out(['ok'=>true]);
  }

  json_out(['ok'=>false,'error'=>'Acción no válida'], 400);

} catch (Throwable $e) {
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
