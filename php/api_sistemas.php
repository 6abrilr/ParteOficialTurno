<?php
// php/api_sistemas.php
require_once __DIR__.'/db.php';

$action = $_GET['action'] ?? '';

header('Content-Type: application/json; charset=utf-8');

try {
  switch ($action) {
    case 'listar':
      // ?cat=2|3|4|5|6  (si no viene, trae todos)
      $cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
      $sql = "SELECT * FROM sistema_estado";
      $params = [];
      if ($cat) { $sql .= " WHERE categoria_id=?"; $params[] = $cat; }
      $sql .= " ORDER BY categoria_id, nombre";
      $st = pdo()->prepare($sql); $st->execute($params);
      echo json_encode($st->fetchAll(), JSON_UNESCAPED_UNICODE);
      break;

    case 'guardar':
      $d = json_decode(file_get_contents('php://input'), true);
      if (!$d || !isset($d['id'])) throw new Exception('Datos inválidos');
      $sql = "UPDATE sistema_estado SET estado=?, novedad=?, ticket=?, actualizado_en=NOW() WHERE id=?";
      pdo()->prepare($sql)->execute([
        $d['estado'], ($d['novedad'] ?? null), ($d['ticket'] ?? null), $d['id']
      ]);
      echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
      break;

    default:
      echo json_encode(['error'=>'acción inválida'], JSON_UNESCAPED_UNICODE);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
