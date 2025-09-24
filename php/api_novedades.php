<?php
json($stmt->fetchAll());


case 'POST:crear':
$data = json_decode(file_get_contents('php://input'), true);
$sql = "INSERT INTO novedad (titulo, descripcion, categoria_id, unidad_id, servicio, ticket, prioridad, estado, creado_por)
VALUES (?,?,?,?,?,?,?,?,?)";
pdo()->prepare($sql)->execute([
$data['titulo'], $data['descripcion'], $data['categoria_id'], $data['unidad_id'] ?: null,
$data['servicio'] ?: null, $data['ticket'] ?: null, $data['prioridad'] ?? 'MEDIA', 'ABIERTO', $data['usuario'] ?? null
]);
$id = pdo()->lastInsertId();
pdo()->prepare("INSERT INTO novedad_evento (novedad_id, tipo, detalle, usuario) VALUES (?,?,?,?)")
->execute([$id,'CREADA',$data['descripcion'] ?? null,$data['usuario'] ?? null]);
json(['ok'=>true,'id'=>$id],201);


case 'POST:actualizar':
$data = json_decode(file_get_contents('php://input'), true);
$sql = "UPDATE novedad SET titulo=?, descripcion=?, categoria_id=?, unidad_id=?, servicio=?, ticket=?, prioridad=?, actualizado_en=NOW() WHERE id=?";
pdo()->prepare($sql)->execute([
$data['titulo'],$data['descripcion'],$data['categoria_id'],$data['unidad_id'] ?: null,
$data['servicio'] ?: null,$data['ticket'] ?: null,$data['prioridad'] ?? 'MEDIA',$data['id']
]);
pdo()->prepare("INSERT INTO novedad_evento (novedad_id, tipo, detalle, usuario) VALUES (?,?,?,?)")
->execute([$data['id'],'ACTUALIZADA',$data['detalle'] ?? null,$data['usuario'] ?? null]);
json(['ok'=>true]);


case 'POST:resolver':
$data = json_decode(file_get_contents('php://input'), true);
pdo()->prepare("UPDATE novedad SET estado='RESUELTO', fecha_resolucion=NOW(), actualizado_en=NOW() WHERE id=?")
->execute([$data['id']]);
pdo()->prepare("INSERT INTO novedad_evento (novedad_id, tipo, detalle, usuario) VALUES (?,?,?,?)")
->execute([$data['id'],'RESUELTA',$data['detalle'] ?? null,$data['usuario'] ?? null]);
json(['ok'=>true]);


case 'POST:reabrir':
$data = json_decode(file_get_contents('php://input'), true);
pdo()->prepare("UPDATE novedad SET estado='EN_PROCESO', fecha_resolucion=NULL, actualizado_en=NOW() WHERE id=?")
->execute([$data['id']]);
pdo()->prepare("INSERT INTO novedad_evento (novedad_id, tipo, detalle, usuario) VALUES (?,?,?,?)")
->execute([$data['id'],'REABIERTA',$data['detalle'] ?? null,$data['usuario'] ?? null]);
json(['ok'=>true]);


default:
json(['error'=>'Ruta no encontrada'],404);
}
} catch (Throwable $e) {
json(['error'=>$e->getMessage()],500);
}