<?php
// tools/import_hist.php
declare(strict_types=1);
date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once __DIR__ . '/../php/auth/bootstrap.php';
require_role('admin'); // sólo admin

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$DIR = realpath(__DIR__ . '/../files/partes');
if (!$DIR) {
  http_response_code(500);
  echo "No existe la carpeta files/partes";
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Intenta inferir fechas del nombre del archivo. */
function infer_range(string $name): ?array {
  $base = preg_replace('/\.[^.]+$/', '', $name);

  // 1) ISO: Parte_2025-09-24_al_2025-09-25(.pdf/.html)
  if (preg_match('/(\d{4}-\d{2}-\d{2})\s*_al_\s*(\d{4}-\d{2}-\d{2})/i', $base, $m)) {
    return [$m[1], $m[2], 'iso_range'];
  }

  // 2) Español: "dia 10 al 11 de octubre de 2025" (también "día")
  $meses = [
    'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
    'julio'=>7,'agosto'=>8,'septiembre'=>9,'setiembre'=>9,'octubre'=>10,
    'noviembre'=>11,'diciembre'=>12
  ];
  if (preg_match('/\b(d[ií]a|del)\s*(\d{1,2})\s*al\s*(\d{1,2})\s*de\s*([a-záéíóúñ]+)\s*de\s*(\d{4})/iu', $base, $m)) {
    $mesTxt = strtolower($m[4]);
    $mesNum = $meses[$mesTxt] ?? null;
    if (!$mesNum) return null;
    $y = (int)$m[5];
    $d1 = str_pad($m[2], 2, '0', STR_PAD_LEFT);
    $d2 = str_pad($m[3], 2, '0', STR_PAD_LEFT);
    $desde = sprintf('%04d-%02d-%02d', $y, $mesNum, $d1);
    $hasta = sprintf('%04d-%02d-%02d', $y, $mesNum, $d2);
    return [$desde, $hasta, 'es_range'];
  }

  return null;
}

// Parámetros
$dry   = isset($_GET['dry']) ? (int)$_GET['dry'] : 1;   // 1 = simulación
$ext   = strtolower((string)($_GET['ext']  ?? ''));     // 'pdf', 'html' o vacío (todos)
$only  = trim((string)($_GET['only'] ?? ''));           // patrón(es) coma-separados
$skip  = trim((string)($_GET['skip'] ?? ''));           // patrón(es) a excluir
$forceSameRange = !empty($_GET['allow_same_range']);    // si querés permitir duplicar mismo rango
$creator = (int)($_GET['uid'] ?? (user()['id'] ?? 0));
if ($creator <= 0) $creator = null;

// Helpers de filtrado por patrón estilo comodines
$match = function(string $f, string $patternList): bool {
  if ($patternList === '') return true;
  foreach (explode(',', $patternList) as $p) {
    $p = trim($p);
    if ($p === '') continue;
    $regex = '#^' . str_replace(['\*','\?'], ['.*','.'], preg_quote($p, '#')) . '$#i';
    if (preg_match($regex, $f)) return true;
  }
  return false;
};

// Armar lista de archivos
$files = array_values(array_filter(scandir($DIR) ?: [], function($f) use($DIR, $ext) {
  if ($f === '.' || $f === '..') return false;
  $full = $DIR . DIRECTORY_SEPARATOR . $f;
  if (!is_file($full)) return false;
  if ($ext !== '') {
    $e = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if ($e !== $ext) return false;
  }
  return true;
}));

// Aplicar only/skip
$files = array_values(array_filter($files, fn($f) => $match($f, $only)));
if ($skip !== '') {
  $files = array_values(array_filter($files, fn($f) => !$match($f, $skip)));
}

$importados = 0;
$omitidos   = [];

foreach ($files as $fname) {
  $full = $DIR . DIRECTORY_SEPARATOR . $fname;
  $rel  = 'files/partes/' . $fname;
  $extF = strtolower(pathinfo($fname, PATHINFO_EXTENSION));

  $rng = infer_range($fname);
  if (!$rng) { $omitidos[] = "- $fname (no pude inferir fechas)"; continue; }
  [$desde, $hasta, $how] = $rng;

  // ¿ya existe el archivo en DB?
  $st = $pdo->prepare("SELECT id FROM partes WHERE file_rel_path = ?");
  $st->execute([$rel]);
  if ($st->fetchColumn()) {
    $omitidos[] = "- $fname (ya existe en DB por archivo)";
    continue;
  }

  // ¿ya existe un parte con el mismo rango de fechas?
  if (!$forceSameRange) {
    $st = $pdo->prepare("SELECT id FROM partes WHERE fecha_desde = ? AND fecha_hasta = ?");
    $st->execute([$desde, $hasta]);
    if ($st->fetchColumn()) {
      $omitidos[] = "- $fname (ya existe un parte con ese rango de fechas)";
      continue;
    }
  }

  $titulo = preg_replace('/\.(pdf|html)$/i', '', $fname);

  if ($dry) {
    echo "Importaría: $fname | $desde → $hasta ($how) [$extF]\n";
    continue;
  }

  try {
    $ins = $pdo->prepare("
      INSERT INTO partes (fecha_desde, fecha_hasta, titulo, file_rel_path, created_by, created_at)
      VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $ins->execute([$desde, $hasta, $titulo, $rel, $creator]);
    $importados++;
    echo "Importado: $fname\n";
  } catch (PDOException $e) {
    // Si se nos escapó un duplicado, lo saltamos sin romper todo
    if ($e->getCode() === '23000') {
      $omitidos[] = "- $fname (duplicado en DB)";
      continue;
    }
    throw $e;
  }
}

// Salida
if ($dry) {
  echo "\n(Eso fue una SIMULACIÓN. Ejecutá sin ?dry o con ?dry=0 para insertar.)\n";
  exit;
}
echo "Importados: {$importados}\n";
if ($omitidos) {
  echo "Omitidos:\n" . implode("\n", $omitidos) . "\n";
}
