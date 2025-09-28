<?php
/**
 * Importa Anexo 2 del CENOPE (PDF) → tablas personal_*.
 * Usa Smalot o Poppler, parsea multilínea y filtra Comunicaciones.
 * Persiste a columnas: categoria, nro, grado, apellido_nombre, apellidoNombre,
 * arma, unidad, prom, fecha, habitacion, hospital, detalle
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) { require_once $autoload; }

// Ruta a pdftotext si se usa Poppler
$PDFTOTEXT = 'C:\\Release-25.07.0-0\\Library\\bin\\pdftotext.exe';

/* ====== Debug ====== */
ob_start();
$DEBUG_ENABLED = (($_POST['debug'] ?? '0') === '1');
function dbg(string $m): void { echo "[DBG] $m\n"; }

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
  dbg('Inicio import_cenope');

  if (!isset($_FILES['pdf'])) {
    dbg('No vino $_FILES[pdf]');
    throw new Exception('No se recibió el PDF');
  }
  $dry = (($_POST['dry'] ?? '1') === '1');
  dbg('Flags => dry=' . ($dry ? '1' : '0') . ' debug=' . ($DEBUG_ENABLED ? '1' : '0'));

  // ===== archivo temporal =====
  $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cenope_' . uniqid('', true) . '.pdf';
  if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $tmp)) {
    dbg('move_uploaded_file falló');
    throw new Exception('No se pudo guardar el archivo temporal');
  }
  dbg("PDF temporal: $tmp");

  // ===== extracción texto =====
  $text = ''; $src = '';
  if (class_exists(\Smalot\PdfParser\Parser::class)) {
    try {
      $parser = new \Smalot\PdfParser\Parser();
      $pdf    = $parser->parseFile($tmp);
      $text   = $pdf->getText();
      $src    = 'smalot/pdfparser';
      dbg('Smalot devolvió ' . strlen($text) . ' bytes');
    } catch (\Throwable $e) {
      dbg('Smalot falló: '.$e->getMessage());
    }
  } else {
    dbg('Smalot no disponible');
  }

  if (trim($text) === '') {
    dbg('Intentando Poppler');
    [$t2, $err] = pdf_to_text_poppler($tmp, $PDFTOTEXT);
    if ($t2 !== '') { $text = $t2; $src = 'pdftotext'; }
    dbg('Poppler bytes='.strlen($text).' err=' . ($err ?: '(sin err)'));
  }
  if (trim($text) === '') throw new Exception('No se pudo extraer texto. Exportá el PDF con texto o pasalo por OCR.');

  // ===== parseo =====
  $clean  = normalize_text($text);
  dbg('Texto normalizado len=' . strlen($clean));
  $parsed = parse_cenope($clean);
  dbg('Parseado => internado='.(count($parsed['internado'] ?? [])).' alta='.(count($parsed['alta'] ?? [])).' fallecido='.(count($parsed['fallecido'] ?? [])));

  // Solo previsualización
  if ($dry) {
    $out = ['ok'=>true,'_src'=>$src] + $parsed;
    $dbg = ob_get_clean();
    if ($DEBUG_ENABLED) $out['debug'] = $dbg;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    return;
  }

  // ===== persistencia =====
  $pdo = db();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

  $pdo->beginTransaction();
  dbg('Transacción iniciada');

  // ⚠️ Usar DELETE en lugar de TRUNCATE (TRUNCATE hace commit implícito)
  $pdo->exec("DELETE FROM personal_internado");
  $pdo->exec("DELETE FROM personal_alta");
  $pdo->exec("DELETE FROM personal_fallecido");
  dbg('Tablas vaciadas con DELETE');

  // Normalizadores
  $toNull = fn($v)=>($v===null||$v==='')?null:$v;
  $toInt  = function($v){
    if ($v === null || $v === '') return null;
    $v = is_string($v) ? $v : (string)$v;
    $v = str_replace(['.',',',' '], '', $v);
    return is_numeric($v) ? (int)$v : null;
  };
  $toDate = fn($v)=>norm_date($v??null) ?: null;

  $cols = "(categoria,nro,grado,apellido_nombre,apellidoNombre,arma,unidad,prom,fecha,habitacion,hospital,detalle)";

  // --- INTERNADOS ---
  $stI = $pdo->prepare("INSERT INTO personal_internado $cols VALUES (:cat,:nro,:gr,:ap1,:ap2,:ar,:un,:pr,:fe,:hb,:ho,:de)");
  $insI = 0;
  foreach (($parsed['internado'] ?? []) as $r) {
    $row = [
      ':cat' => $toNull($r['categoria'] ?? null),
      ':nro' => $toInt($r['Nro'] ?? null),
      ':gr'  => (string)($r['Grado'] ?? ''),
      ':ap1' => (string)($r['Apellido y Nombre'] ?? ''),
      ':ap2' => (string)($r['Apellido y Nombre'] ?? ''),
      ':ar'  => $toNull($r['Arma'] ?? null),
      ':un'  => $toNull($r['Unidad'] ?? null),
      ':pr'  => $toNull($r['Prom'] ?? null),
      ':fe'  => $toDate($r['Fecha'] ?? null),
      ':hb'  => $toNull($r['Habitación'] ?? null),
      ':ho'  => $toNull($r['Hospital'] ?? null),
      ':de'  => null,
    ];
    $stI->execute($row);
    $insI++;
  }
  dbg("Insertados internado=$insI");

  // --- ALTAS ---
  $stA = $pdo->prepare("INSERT INTO personal_alta $cols VALUES (:cat,:nro,:gr,:ap1,:ap2,:ar,:un,:pr,:fe,:hb,:ho,:de)");
  $insA = 0;
  foreach (($parsed['alta'] ?? []) as $r) {
    $row = [
      ':cat' => $toNull($r['categoria'] ?? null),
      ':nro' => $toInt($r['Nro'] ?? null),
      ':gr'  => (string)($r['Grado'] ?? ''),
      ':ap1' => (string)($r['Apellido y Nombre'] ?? ''),
      ':ap2' => (string)($r['Apellido y Nombre'] ?? ''),
      ':ar'  => $toNull($r['Arma'] ?? null),
      ':un'  => $toNull($r['Unidad'] ?? null),
      ':pr'  => $toNull($r['Prom'] ?? null),
      ':fe'  => $toDate($r['Fecha'] ?? null),
      ':hb'  => $toNull($r['Habitación'] ?? null),
      ':ho'  => $toNull($r['Hospital'] ?? null),
      ':de'  => null,
    ];
    $stA->execute($row);
    $insA++;
  }
  dbg("Insertados alta=$insA");

  // --- FALLECIDOS ---
  $stF = $pdo->prepare("INSERT INTO personal_fallecido $cols VALUES (:cat,:nro,:gr,:ap1,:ap2,:ar,:un,:pr,:fe,:hb,:ho,:de)");
  $insF = 0;
  foreach (($parsed['fallecido'] ?? []) as $txt) {
    $row = [
      ':cat'=>null, ':nro'=>null, ':gr'=>'', ':ap1'=>null, ':ap2'=>null,
      ':ar'=>null, ':un'=>null, ':pr'=>null, ':fe'=>null, ':hb'=>null, ':ho'=>null,
      ':de'=>(string)$txt
    ];
    $stF->execute($row);
    $insF++;
  }
  dbg("Insertados fallecido=$insF");

  if ($pdo->inTransaction()) {
    $pdo->commit();
    dbg('Commit OK');
  } else {
    dbg('No había transacción activa al momento del commit (evitado)');
  }

  $out = [
    'ok'        => true,
    '_src'      => $src,
    'internado' => $insI,
    'alta'      => $insA,
    'fallecido' => $insF,
  ];
  $dbg = ob_get_clean();
  if ($DEBUG_ENABLED) $out['debug'] = $dbg;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
    try { $pdo->rollBack(); dbg('Rollback ejecutado'); } catch (\Throwable $e2) { dbg('Rollback falló: '.$e2->getMessage()); }
  }
  http_response_code(500);
  $dbg = ob_get_clean();
  echo json_encode([
    'ok'    => false,
    'type'  => get_class($e),
    'error' => $e->getMessage(),
    'debug' => $dbg,
  ], JSON_UNESCAPED_UNICODE);
}

/* =================== Helpers =================== */

function pdf_to_text_poppler(string $pdf, string $bin): array {
  if (!$bin || !is_file($bin)) return ['', 'pdftotext.exe no disponible'];
  $out = $pdf . '.txt';
  @unlink($out);
  $spec = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
  $cmd  = escapeshellcmd($bin) . ' -layout -nopgbrk ' . escapeshellarg($pdf) . ' ' . escapeshellarg($out);
  $p = proc_open($cmd, $spec, $pipes);
  if (is_resource($p)) {
    fclose($pipes[0]);
    stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    proc_close($p);
    $txt = is_file($out) ? (string)@file_get_contents($out) : '';
    return [$txt, trim($err)];
  }
  return ['', 'No se pudo ejecutar pdftotext'];
}

function normalize_text(string $t): string {
  $t = preg_replace('/B\\s*C\\s*O\\s*M\\s*\\d+\\s*T\\s*R\\s*A\\s*F/i','',$t);
  $t = preg_replace('/RESERVADO/i','',$t);
  $t = preg_replace('/Powered\\s+by\\s+TCPDF.*/i','',$t);
  $t = preg_replace('/Página\\s+\\d+\\s+de\\s+\\d+/i','', $t);
  $t = preg_replace('/\\s{2,}/',' ', $t);
  $lines = array_map('rtrim', preg_split('/\\r?\\n/', $t));
  $lines = array_values(array_filter($lines, fn($l)=>$l!=='')); // sin líneas vacías
  return implode("\n", $lines);
}

function parse_cenope(string $txt): array {
  $outI=[]; $outA=[]; $outF=[]; $cat=null; $modo=null;
  $GRADOS='(TG|GD|GB|CY|CR|TC|MY|CT|TP|TT|ST|SM|SP|SA|SI|SG|CI|CB|VP|VS|SV|SOLD)';
  $lines = preg_split('/\\n/', $txt); $n = count($lines);

  for ($i=0; $i<$n; $i++) {
    $l = $lines[$i];

    if (preg_match('/\\b(OFICIALES|SUBOFICIALES|SOLDADOS\\s+VOLUNTARIOS)\\b/i',$l,$m)) { $cat = strtoupper($m[1]); $modo=null; continue; }
    if (preg_match('/\\b(1\\)\\s*INTERNAD(?:O|OS)|2\\)\\s*ALTAS?)\\b/i',$l,$m)) { $modo = stripos($m[1],'ALTA')!==false ? 'ALTAS' : 'INTERNADOS'; continue; }
    if (!$cat || !$modo) continue;

    if (preg_match('/\\b'.$GRADOS.'\\b/i',$l,$gm, PREG_OFFSET_CAPTURE)) {
      $buf = $l; for ($k=1;$k<=5 && ($i+$k)<$n;$k++) $buf .= ' '.$lines[$i+$k];
      $buf = preg_replace('/\\s{2,}/',' ',$buf);

      $fecha = extrae_fecha($buf);
      $hab   = extrae_hab($buf);
      $hosp  = guess_hospital(preg_split('/\\s+/',$buf));

      if (!preg_match('/\\b'.$GRADOS.'\\b/i',$buf,$m2, PREG_OFFSET_CAPTURE)) continue;
      $gradoRaw = $m2[0][0];
      $grado = strtoupper(preg_replace('/[^A-Z]/','',$gradoRaw));
      $post = trim(substr($buf, $m2[0][1] + strlen($m2[0][0])));

      $revCut  = preg_split('/\\b(En Actividad|Retirado)\\b/i', $post, 2, PREG_SPLIT_DELIM_CAPTURE);
      $pre     = trim($revCut[0] ?? ''); $postRev = trim($revCut[2] ?? '');

      $destino = '';
      if ($postRev !== '' && preg_match('/-\\s*([^0-9]{2,}?)(?:\\s*\\(U\\d+\\))?/i',$postRev,$md)) $destino = trim($md[1]);

      $tokens = preg_split('/\\s+/', $pre);
      $arma = array_pop($tokens) ?? '';
      while (!empty($tokens) && mb_strlen($arma)<=4 && mb_strlen(end($tokens))<=4) { $arma = array_pop($tokens).' '.$arma; }
      $nombre = trim(implode(' ', $tokens));
      if ($nombre==='') continue;

      $row = [
        'categoria' => map_cat($cat),
        'Nro' => null,
        'Grado' => $grado,
        'Apellido y Nombre' => $nombre,
        'Arma' => $arma ?: null,
        'Unidad' => $destino ?: null,
        'Prom' => null,
        'Fecha' => $fecha,
        'Habitación' => $hab,
        'Hospital' => $hosp,
      ];
      if (pasaFiltroCom($row)) { if ($modo==='INTERNADOS') $outI[]=$row; else $outA[]=$row; }
    }

    if (preg_match('/\\bFALLECID[OA]S?\\b/i',$l)) $outF[] = trim($l);
  }

  $rank = [
    'TG'=>0,'GD'=>0.1,'GB'=>0.2,'CY'=>0.5,
    'CR'=>1,'TC'=>2,'MY'=>3,'CT'=>4,'TP'=>5,'TT'=>6,'ST'=>7,
    'SM'=>10,'SP'=>11,'SA'=>12,'SI'=>13,'SG'=>14,'CI'=>15,'CB'=>16,
    'VP'=>30,'VS'=>31,'SV'=>32,'SOLD'=>33
  ];
  $ord = function($a,$b) use($rank){
    $ra = $rank[$a['Grado']] ?? 999;
    $rb = $rank[$b['Grado']] ?? 999;
    return $ra === $rb
      ? strcmp($a['Apellido y Nombre'], $b['Apellido y Nombre'])
      : ($ra <=> $rb);
  };
  usort($outI,$ord); usort($outA,$ord);
  $i=1; foreach($outI as &$r){ $r['Nro']=$i++; }

  return ['internado'=>$outI,'alta'=>$outA,'fallecido'=>$outF];
}

function pasaFiltroCom(array $r): bool {
  $cat = $r['categoria']; $arma = strtoupper($r['Arma'] ?? ''); $dest = strtoupper($r['Unidad'] ?? '');
  if ($cat==='OFICIALES' || $cat==='SUBOFICIALES') return (strpos($arma,'COM') !== false);
  if ($cat==='SOLDADOS VOLUNTARIOS') return esUnidadCom($dest);
  return false;
}
function esUnidadCom(string $dest): bool {
  $pat = '/\\b(B\\s*MANT\\s*COM|BAT\\s*COM|BATALL[ÓO]N\\s+DE\\s+COM|BCOM|CA\\s*COM|CIA\\s*COM|BRIG\\s*COM|COMUNICACIONES\\b)\\b/u';
  return (bool)preg_match($pat, $dest);
}

function extrae_fecha(string &$s): ?string {
  if (preg_match('/(\\d{1,2}[A-Za-z]{3}\\d{2}|\\d{1,2}[\\/\\-]\\d{1,2}[\\/\\-]\\d{2,4})\\b/',$s,$m,PREG_OFFSET_CAPTURE)) {
    $pos=$m[0][1]; $len=strlen($m[0][0]); $s = trim(substr($s,0,$pos).' '.substr($s,$pos+$len)); return $m[0][0];
  } return null;
}
function extrae_hab(string &$s): ?string {
  if (preg_match('/\\b(UTI|PI|UCO|PAB|\\d+[A-Z]?)\\b/i',$s,$m,PREG_OFFSET_CAPTURE)) {
    $pos=$m[0][1]; $len=strlen($m[0][0]); $s = trim(substr($s,0,$pos).' '.substr($s,$pos+$len)); return $m[0][0];
  } return null;
}
function map_cat(string $s): string {
  $s=strtoupper($s);
  if (str_starts_with($s,'OFICIA')) return 'OFICIALES';
  if (str_starts_with($s,'SUBOFI')) return 'SUBOFICIALES';
  return 'SOLDADOS VOLUNTARIOS';
}
function guess_hospital(array $cols): ?string {
  $s = strtoupper(implode(' ', array_slice($cols, -5)));
  foreach ([
    'CENTRAL','CAMPO DE MAYO','CORDOBA','MENDOZA','PARANA','BAHIA BLANCA','RIO GALLEGOS',
    'SALTA','CURUZU CUATIA','COMODORO RIVADAVIA','HOSPITAL REGIONAL','SANATORIO COLEGIALES'
  ] as $h) { if (str_contains($s,$h)) return $h; }
  return null;
}
function norm_date(?string $s): ?string {
  if(!$s) return null;
  if (preg_match('/^(\\d{1,2})([A-Za-z]{3})(\\d{2})$/',$s,$m)) {
    $mm = [
      'ENE'=>'01','JAN'=>'01','FEB'=>'02','MAR'=>'03','ABR'=>'04','APR'=>'04','MAY'=>'05',
      'JUN'=>'06','JUL'=>'07','AGO'=>'08','AUG'=>'08','SEP'=>'09','OCT'=>'10','NOV'=>'11','DIC'=>'12','DEC'=>'12'
    ][strtoupper($m[2])] ?? '01';
    return '20'.$m[3].'-'.$mm.'-'.str_pad($m[1],2,'0',STR_PAD_LEFT);
  }
  if (preg_match('/^(\\d{1,2})[\\/-](\\d{1,2})[\\/-](\\d{2,4})$/',$s,$m)) {
    $yy = strlen($m[3])===2 ? ('20'.$m[3]) : $m[3];
    return $yy.'-'.str_pad($m[2],2,'0',STR_PAD_LEFT).'-'.str_pad($m[1],2,'0',STR_PAD_LEFT);
  }
  return null;
}
