<?php
/**
 * Importa Anexo 2 del CENOPE (PDF) → tablas personal_*.
 * Requiere Poppler (pdftotext).
 */
require_once __DIR__.'/db.php';

/* === Config ===
 * Si falla la autodetección, dejar EXACTA la ruta al binario pdftotext.exe
 * OJO: ¡No confundir con pdfinfo.exe!
 */
$PDFTOTEXT = 'C:\\Release-25.07.0-0\\Library\\bin\\pdftotext.exe';

header('Content-Type: application/json; charset=utf-8');

try {
  if (!isset($_FILES['pdf'])) throw new Exception('No se recibió el PDF');
  $dry = ($_POST['dry'] ?? '1') === '1';

  $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cenope_'.uniqid().'.pdf';
  if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $tmp)) throw new Exception('No se pudo guardar el archivo');

  [$text, $err] = pdf_to_text($tmp);
  if (!$text) {
    $hint = 'No se pudo extraer texto. ';
    if ($err) $hint .= "pdftotext dijo: $err";
    $hint .= ' — Verifique Poppler (pdftotext) o que el PDF no sea un escaneo.';
    throw new Exception($hint);
  }

  $clean  = normalize_text($text);
  $parsed = parse_cenope($clean); // ['internado'=>[], 'alta'=>[], 'fallecido'=>[]]

  if ($dry) { echo json_encode(['ok'=>true]+$parsed, JSON_UNESCAPED_UNICODE); exit; }

  // Persistencia (reemplaza)
  $pdo = pdo();
  $pdo->beginTransaction();
  $pdo->exec("TRUNCATE personal_internado");
  $pdo->exec("TRUNCATE personal_alta");
  $pdo->exec("TRUNCATE personal_fallecido");

  $insI = $pdo->prepare("INSERT INTO personal_internado (categoria,nro,grado,apellido_nombre,arma,unidad,prom,fecha,habitacion,hospital) VALUES (?,?,?,?,?,?,?,?,?,?)");
  foreach ($parsed['internado'] as $r) {
    $insI->execute([
      $r['categoria'], $r['Nro']??null, $r['Grado']??null, $r['Apellido y Nombre']??null,
      $r['Arma']??null, $r['Unidad']??null, $r['Prom']??null,
      norm_date($r['Fecha']??null), $r['Habitación']??null, $r['Hospital']??null
    ]);
  }

  $insA = $pdo->prepare("INSERT INTO personal_alta (categoria,grado,apellido_nombre,arma,unidad,prom,fecha,hospital) VALUES (?,?,?,?,?,?,?,?)");
  foreach ($parsed['alta'] as $r) {
    $insA->execute([
      $r['categoria'], $r['Grado']??null, $r['Apellido y Nombre']??null,
      $r['Arma']??null, $r['Unidad']??null, $r['Prom']??null,
      norm_date($r['Fecha']??null), $r['Hospital']??null
    ]);
  }

  $insF = $pdo->prepare("INSERT INTO personal_fallecido (detalle) VALUES (?)");
  foreach ($parsed['fallecido'] as $txt) { $insF->execute([$txt]); }

  $pdo->commit();

  echo json_encode([
    'ok'=>true,
    'internado'=>count($parsed['internado']),
    'alta'=>count($parsed['alta']),
    'fallecido'=>count($parsed['fallecido'])
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/* ===== Helpers ===== */

function pdf_to_text(string $pdf): array {
  $bin = resolve_pdftotext();
  $out = $pdf.'.txt';
  @unlink($out);

  $spec = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
  $cmd  = escapeshellcmd($bin).' -layout -nopgbrk '.escapeshellarg($pdf).' '.escapeshellarg($out);
  $p = proc_open($cmd,$spec,$pipes);
  if (is_resource($p)) {
    fclose($pipes[0]);
    stream_get_contents($pipes[1]); // stdout no lo usamos
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    proc_close($p);
    $txt = is_file($out) ? (string)@file_get_contents($out) : '';
    return [$txt, trim($err)];
  }
  return ['', 'No se pudo ejecutar pdftotext'];
}

function resolve_pdftotext(): string {
  global $PDFTOTEXT;
  if ($PDFTOTEXT) {
    if (stripos($PDFTOTEXT, 'pdfinfo.exe') !== false) {
      throw new Exception('Configuraste "pdfinfo.exe". Debe ser "pdftotext.exe". Ruta esperada p.ej.: C:\\Release-25.07.0-0\\Library\\bin\\pdftotext.exe');
    }
    if (is_file($PDFTOTEXT)) return $PDFTOTEXT;
  }

  $candidatos = [
    'C:\\Release-25.07.0-0\\Library\\bin\\pdftotext.exe',
    'C:\\poppler-25.09.1\\Library\\bin\\pdftotext.exe',
    'C:\\poppler\\Library\\bin\\pdftotext.exe',
    'C:\\Program Files\\poppler\\Library\\bin\\pdftotext.exe',
    'C:\\Program Files\\poppler\\bin\\pdftotext.exe',
    'C:\\Program Files (x86)\\poppler\\bin\\pdftotext.exe',
    'C:\\tools\\poppler\\bin\\pdftotext.exe',
  ];
  foreach ($candidatos as $p) if (is_file($p)) return $p;

  $isWin = strtoupper(substr(PHP_OS_FAMILY ?? PHP_OS,0,3)) === 'WIN';
  $where = $isWin ? @shell_exec('where pdftotext 2>nul') : @shell_exec('which pdftotext 2>/dev/null');
  if ($where) {
    $line = trim(preg_split('/\r?\n/',$where)[0] ?? '');
    if ($line !== '' && is_file($line)) return $line;
  }
  throw new Exception('No se encontró pdftotext. Instale Poppler y agregue "...\\Library\\bin" al PATH, o complete $PDFTOTEXT con la ruta exacta.');
}

/** Limpieza agresiva (sellos, pies, dobles espacios) */
function normalize_text(string $t): string {
  $t = preg_replace('/B\\s*C\\s*O\\s*M\\s*\\d+\\s*T\\s*R\\s*A\\s*F/i','',$t);
  $t = preg_replace('/RESERVADO/i','',$t);
  $t = preg_replace('/Powered\\s+by\\s+TCPDF.*/i','',$t);
  $t = preg_replace('/Página\\s+\\d+\\s+de\\s+\\d+/i','', $t);
  $t = preg_replace('/\\s{2,}/',' ', $t);
  $lines = array_map('rtrim', preg_split('/\\r?\\n/', $t));
  $lines = array_values(array_filter($lines, fn($l)=>$l!==''));
  return implode("\n", $lines);
}

/** Parser (multi-línea), filtrado por Comunicaciones */
function parse_cenope(string $txt): array {
  $outI=[]; $outA=[]; $outF=[];
  $cat=null; $modo=null;

  $GRADOS='(TG|GD|GB|CY|CR|TC|MY|CT|TP|TT|ST|SM|SP|SA|SI|SG|CI|CB|VP|VS|SV|SOLD)';
  $lines = preg_split('/\\n/', $txt);
  $n = count($lines);

  for ($i=0; $i<$n; $i++) {
    $l = $lines[$i];

    if (preg_match('/\\b(OFICIALES|SUBOFICIALES|SOLDADOS\\s+VOLUNTARIOS)\\b/i',$l,$m)) { $cat = strtoupper($m[1]); $modo=null; continue; }
    if (preg_match('/\\b(1\\)\\s*INTERNAD(?:O|OS)|2\\)\\s*ALTAS?)\\b/i',$l,$m)) { $modo = stripos($m[1],'ALTA')!==false ? 'ALTAS' : 'INTERNADOS'; continue; }
    if (!$cat || !$modo) continue;

    if (preg_match('/\\b'.$GRADOS.'\\b/i',$l,$gm, PREG_OFFSET_CAPTURE)) {
      $buf = $l;
      for ($k=1;$k<=5 && ($i+$k)<$n;$k++) $buf .= ' '.$lines[$i+$k];
      $buf = preg_replace('/\\s{2,}/',' ',$buf);

      $fecha = extrae_fecha($buf);
      $hab   = extrae_hab($buf);
      $hosp  = guess_hospital(preg_split('/\\s+/',$buf));

      if (!preg_match('/\\b'.$GRADOS.'\\b/i',$buf,$m2, PREG_OFFSET_CAPTURE)) continue;
      $gradoRaw = $m2[0][0];
      $grado = strtoupper(preg_replace('/[^A-Z]/','',$gradoRaw));
      $post = trim(substr($buf, $m2[0][1] + strlen($m2[0][0])));

      $revCut = preg_split('/\\b(En Actividad|Retirado)\\b/i', $post, 2, PREG_SPLIT_DELIM_CAPTURE);
      $pre = trim($revCut[0] ?? '');
      $postRev = trim($revCut[2] ?? '');

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

      // Filtro Comunicaciones
      if (pasaFiltroCom($row)) {
        if ($modo==='INTERNADOS') $outI[]=$row; else $outA[]=$row;
      }
    }

    if (preg_match('/\\bFALLECID[OA]S?\\b/i',$l)) $outF[] = trim($l);
  }

  // Orden + Nro
  $rank = [
    'TG'=>0,'GD'=>0.1,'GB'=>0.2,'CY'=>0.5,
    'CR'=>1,'TC'=>2,'MY'=>3,'CT'=>4,'TP'=>5,'TT'=>6,'ST'=>7,
    'SM'=>10,'SP'=>11,'SA'=>12,'SI'=>13,'SG'=>14,
    'CI'=>15,'CB'=>16,'VP'=>30,'VS'=>31,'SV'=>32,'SOLD'=>33
  ];
  $ord = function($a,$b) use($rank){
    $ra=$rank[$a['Grado']]??999; $rb=$rank[$b['Grado']]??999;
    return $ra===$rb ? strcmp($a['Apellido y Nombre'],$b['Apellido y Nombre']) : $ra<=>$rb;
  };
  usort($outI,$ord); usort($outA,$ord);
  $i=1; foreach($outI as &$r){ $r['Nro']=$i++; }

  return ['internado'=>$outI,'alta'=>$outA,'fallecido'=>$outF];
}

function pasaFiltroCom(array $r): bool {
  $cat = $r['categoria'];
  $arma = strtoupper($r['Arma'] ?? '');
  $dest = strtoupper($r['Unidad'] ?? '');

  if ($cat==='OFICIALES' || $cat==='SUBOFICIALES') {
    return (strpos($arma, 'COM') !== false);
  }
  if ($cat==='SOLDADOS VOLUNTARIOS') {
    return esUnidadCom($dest);
  }
  return false;
}

function esUnidadCom(string $dest): bool {
  $pat = '/\\b('
       . 'B\\s*MANT\\s*COM'
       . '|BAT\\s*COM'
       . '|BATALL[ÓO]N\\s+DE\\s+COM'
       . '|BCOM'
       . '|CA\\s*COM'
       . '|CIA\\s*COM'
       . '|BRIG\\s*COM'
       . '|COMUNICACIONES\\b'
       . ')\\b/u';
  return (bool)preg_match($pat, $dest);
}

function extrae_fecha(string &$s): ?string {
  if (preg_match('/(\\d{1,2}[A-Za-z]{3}\\d{2}|\\d{1,2}[\\/\\-]\\d{1,2}[\\/\\-]\\d{2,4})\\b/',$s,$m,PREG_OFFSET_CAPTURE)) {
    $pos = $m[0][1]; $len = strlen($m[0][0]);
    $s = trim(substr($s,0,$pos).' '.substr($s,$pos+$len));
    return $m[0][0];
  }
  return null;
}
function extrae_hab(string &$s): ?string {
  if (preg_match('/\\b(UTI|PI|UCO|PAB|\\d+[A-Z]?)\\b/i',$s,$m,PREG_OFFSET_CAPTURE)) {
    $pos = $m[0][1]; $len = strlen($m[0][0]);
    $s = trim(substr($s,0,$pos).' '.substr($s,$pos+$len));
    return $m[0][0];
  }
  return null;
}

function map_cat(string $s): string {
  $s = strtoupper($s);
  if (str_starts_with($s,'OFICIA')) return 'OFICIALES';
  if (str_starts_with($s,'SUBOFI')) return 'SUBOFICIALES';
  return 'SOLDADOS VOLUNTARIOS';
}
function guess_hospital(array $cols): ?string {
  $s = strtoupper(implode(' ', array_slice($cols, -5)));
  foreach ([
    'CENTRAL','CAMPO DE MAYO','CORDOBA','MENDOZA','PARANA',
    'BAHIA BLANCA','RIO GALLEGOS','SALTA','CURUZU CUATIA',
    'COMODORO RIVADAVIA','HOSPITAL REGIONAL','SANATORIO COLEGIALES'
  ] as $h) { if (str_contains($s, $h)) return $h; }
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
