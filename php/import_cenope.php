<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) require_once $autoload;

$PDFTOTEXT = 'C:\\Release-25.07.0-0\\Library\\bin\\pdftotext.exe';

ob_start();
$DEBUG_ENABLED = (($_POST['debug'] ?? '0') === '1');
function dbg(string $m): void { echo "[DBG] $m\n"; }

ini_set('display_errors','0');
ini_set('log_errors','1');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

try {
  if (isset($_FILES['pdf_file'])) $_FILES['pdf'] = $_FILES['pdf_file'];
  if (!isset($_FILES['pdf'])) throw new Exception('No se recibió el/los PDF/s');
  $dry = (($_POST['dry'] ?? '1') === '1');

  $isMulti = is_array($_FILES['pdf']['name']);
  $idxMax  = $isMulti ? min(3, count($_FILES['pdf']['name'])) : 1;

  $agg = ['INTERNADOS'=>[], 'ALTAS'=>[], 'FALLECIDOS'=>[]];
  $src_used = null;

  for ($i = 0; $i < $idxMax; $i++) {
    $name     = $isMulti ? (string)$_FILES['pdf']['name'][$i]     : (string)$_FILES['pdf']['name'];
    $tmp_name = $isMulti ? (string)$_FILES['pdf']['tmp_name'][$i] : (string)$_FILES['pdf']['tmp_name'];
    $error    = $isMulti ? (int)$_FILES['pdf']['error'][$i]       : (int)$_FILES['pdf']['error'];
    if ($error !== UPLOAD_ERR_OK) throw new Exception("Error al subir: $name");

    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cenope_' . uniqid('', true) . '.pdf';
    if (!move_uploaded_file($tmp_name, $tmp)) throw new Exception("No se pudo guardar temporal: $name");

    $text = ''; $src = '';
    if (class_exists(\Smalot\PdfParser\Parser::class)) {
      try { $text = (new \Smalot\PdfParser\Parser())->parseFile($tmp)->getText(); $src='smalot/pdfparser'; } catch (\Throwable $e) {}
    }
    if (trim($text) === '') {
      [$t2] = pdf_to_text_poppler($tmp, $PDFTOTEXT);
      if ($t2 !== '') { $text = $t2; $src = 'pdftotext'; }
    }
    if (trim($text) === '') throw new Exception("No se pudo extraer texto de $name");

    $src_used = $src;
    $clean = normalize_text($text);

    $tipo  = detectar_tipo($name, $clean); // INTERNADOS | ALTAS | FALLECIDOS
    $parsed = parse_cenope($clean, $tipo);

    if ($tipo === 'INTERNADOS') $agg['INTERNADOS'] = array_merge($agg['INTERNADOS'], $parsed['internado'] ?? []);
    if ($tipo === 'ALTAS')      $agg['ALTAS']      = array_merge($agg['ALTAS'],      $parsed['alta']      ?? []);
    if ($tipo === 'FALLECIDOS') $agg['FALLECIDOS'] = array_merge($agg['FALLECIDOS'], $parsed['fallecido'] ?? []);

    @unlink($tmp);
  }

  if ($dry) {
    $out = ['ok'=>true,'_src'=>$src_used,'internado'=>$agg['INTERNADOS'],'alta'=>$agg['ALTAS'],'fallecido'=>$agg['FALLECIDOS']];
    $dbg = ob_get_clean(); if ($DEBUG_ENABLED) $out['debug'] = $dbg;
    echo json_encode($out, JSON_UNESCAPED_UNICODE); return;
  }

  $pdo = db();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->beginTransaction();

  $pdo->exec("DELETE FROM personal_internado");
  $pdo->exec("DELETE FROM personal_alta");
  $pdo->exec("DELETE FROM personal_fallecido");

  $toNull = fn($v)=>($v===null||$v==='')?null:$v;
  $toInt  = function($v){ if ($v === null || $v === '') return null; $v = is_string($v)?$v:(string)$v; $v = str_replace(['.',',',' '],'',$v); return is_numeric($v)?(int)$v:null; };
  $toDate = fn($v)=>norm_date($v??null) ?: null;
  $cols = "(categoria,nro,grado,apellido_nombre,apellidoNombre,arma,unidad,prom,fecha,habitacion,hospital,detalle)";

  $stI = $pdo->prepare("INSERT INTO personal_internado $cols VALUES (:cat,:nro,:gr,:ap1,:ap2,:ar,:un,:pr,:fe,:hb,:ho,:de)");
  $insI = 0;
  foreach ($agg['INTERNADOS'] as $r) {
    $stI->execute([
      ':cat'=>$toNull($r['categoria']??null), ':nro'=>$toInt($r['Nro']??null), ':gr'=>(string)($r['Grado']??''),
      ':ap1'=>(string)($r['Apellido y Nombre']??''), ':ap2'=>(string)($r['Apellido y Nombre']??''),
      ':ar'=>$toNull($r['Arma']??null), ':un'=>$toNull($r['Unidad']??null), ':pr'=>$toNull($r['Prom']??null),
      ':fe'=>$toDate($r['Fecha']??null), ':hb'=>$toNull($r['Habitación']??null), ':ho'=>$toNull($r['Hospital']??null),
      ':de'=>null,
    ]);
    $insI++;
  }

  $stA = $pdo->prepare("INSERT INTO personal_alta $cols VALUES (:cat,:nro,:gr,:ap1,:ap2,:ar,:un,:pr,:fe,:hb,:ho,:de)");
  $insA = 0;
  foreach ($agg['ALTAS'] as $r) {
    $stA->execute([
      ':cat'=>$toNull($r['categoria']??null), ':nro'=>$toInt($r['Nro']??null), ':gr'=>(string)($r['Grado']??''),
      ':ap1'=>(string)($r['Apellido y Nombre']??''), ':ap2'=>(string)($r['Apellido y Nombre']??''),
      ':ar'=>$toNull($r['Arma']??null), ':un'=>$toNull($r['Unidad']??null), ':pr'=>$toNull($r['Prom']??null),
      ':fe'=>$toDate($r['Fecha']??null), ':hb'=>$toNull($r['Habitación']??null), ':ho'=>$toNull($r['Hospital']??null),
      ':de'=>null,
    ]);
    $insA++;
  }

  $stF = $pdo->prepare("INSERT INTO personal_fallecido $cols VALUES (:cat,:nro,:gr,:ap1,:ap2,:ar,:un,:pr,:fe,:hb,:ho,:de)");
  $insF = 0;
  foreach ($agg['FALLECIDOS'] as $r) {
    $stF->execute([
      ':cat'=>$toNull($r['categoria']??null), ':nro'=>$toInt($r['Nro']??null), ':gr'=>(string)($r['Grado']??''),
      ':ap1'=>(string)($r['Apellido y Nombre']??''), ':ap2'=>(string)($r['Apellido y Nombre']??''),
      ':ar'=>$toNull($r['Arma']??null), ':un'=>$toNull($r['Unidad']??null),
      ':pr'=>null,
      ':fe'=>$toDate($r['Fecha']??null), ':hb'=>$toNull($r['Habitación']??null), ':ho'=>$toNull($r['Hospital']??null),
      ':de'=>$toNull($r['Detalle']??null)
    ]);
    $insF++;
  }

  if ($pdo->inTransaction()) $pdo->commit();

  $out = ['ok'=>true,'_src'=>$src_used,'internado'=>$insI,'alta'=>$insA,'fallecido'=>$insF];
  $dbg = ob_get_clean(); if ($DEBUG_ENABLED) $out['debug'] = $dbg;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) { try { $pdo->rollBack(); } catch (\Throwable $e2) {} }
  http_response_code(500);
  $dbg = ob_get_clean();
  echo json_encode(['ok'=>false,'type'=>get_class($e),'error'=>$e->getMessage(),'debug'=>$dbg], JSON_UNESCAPED_UNICODE);
}

/* --- Helpers --- */

// acepta string|array (arregla strtolower())
function detectar_tipo($filename, string $text): string {
  $f = is_array($filename) ? '' : strtolower((string)$filename);
  if ($f !== '') {
    if (str_contains($f,'fallec')) return 'FALLECIDOS';
    if (str_contains($f,'intern')) return 'INTERNADOS';
    if (str_contains($f,'alta'))   return 'ALTAS';
  }
  $t = strtoupper($text);
  if (preg_match('/ANEXO\s*3.*FALLECID/iu',$t)) return 'FALLECIDOS';
  $score = ['INTERNADOS'=>0,'ALTAS'=>0,'FALLECIDOS'=>0];
  $score['INTERNADOS'] += preg_match_all('/\bINTERNAD(?:O|OS)\b/u',$t,$m);
  $score['ALTAS']      += preg_match_all('/\bALTAS?\b/u',$t,$m);
  $score['FALLECIDOS'] += preg_match_all('/\bFALLECID[OA]S?\b/u',$t,$m);
  arsort($score);
  return array_key_first($score);
}

function pdf_to_text_poppler(string $pdf, string $bin): array {
  if (!$bin || !is_file($bin)) return ['', 'pdftotext.exe no disponible'];
  $out = $pdf . '.txt';
  @unlink($out);
  $spec = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
  $cmd  = escapeshellcmd($bin) . ' -layout -nopgbrk ' . escapeshellarg($pdf) . ' ' . escapeshellarg($out);
  $p = proc_open($cmd, $spec, $pipes);
  if (is_resource($p)) {
    fclose($pipes[0]); stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); proc_close($p);
    $txt = is_file($out) ? (string)@file_get_contents($out) : '';
    return [$txt, trim($err)];
  }
  return ['', 'No se pudo ejecutar pdftotext'];
}

function normalize_text(string $t): string {
  $t = preg_replace('/B\s*C\s*O\s*M\s*\d+\s*T\s*R\s*A\s*F/i','',$t);
  $t = preg_replace('/RESERVADO/i','',$t);
  $t = preg_replace('/Powered\s+by\s+TCPDF.*/i','',$t);
  $t = preg_replace('/Página\s+\d+\s+de\s+\d+/i','', $t);
  $t = preg_replace('/\s{2,}/',' ', $t); // mantenemos una sola separación
  $lines = array_map('rtrim', preg_split('/\r?\n/', $t));
  $lines = array_values(array_filter($lines, fn($l)=>$l!=='')); 
  return implode("\n", $lines);
}

function parse_cenope(string $txt, ?string $force = null): array {
  $outI=[]; $outA=[]; $outF=[]; $cat=null; $modo=null;
  $GRADOS='(TG|GD|GB|CY|CR|TC|MY|CT|TP|TT|ST|SM|SP|SA|SI|SG|CI|CB|VP|VS|SV|SOLD)';
  $lines = preg_split('/\n/', $txt); $n = count($lines);

  $inFallecidos = false;

  for ($i=0; $i<$n; $i++) {
    $l = $lines[$i];

    // —— Detectar inicio del Anexo 3 / Fallecidos
    if (preg_match('/ANEXO\s*3.*FALLECID/iu',$l) || preg_match('/\bFALLECID[OA]S?\b/iu',$l)) {
      if ($force === null || $force === 'FALLECIDOS') { $inFallecidos = true; $modo = 'FALLECIDOS'; }
    }

    // Categorías para internados/altas
    if (preg_match('/\b(OFICIALES|SUBOFICIALES|SOLDADOS\s+VOLUNTARIOS)\b/i',$l,$m)) {
      $cat = strtoupper($m[1]); 
      if ($modo !== 'FALLECIDOS') $modo=null; 
      continue;
    }
    if (preg_match('/\b(1\)\s*INTERNAD(?:O|OS)|2\)\s*ALTAS?)\b/i',$l,$m)) {
      $modo = stripos($m[1],'ALTA')!==false ? 'ALTAS' : 'INTERNADOS';
      if ($force && $force !== $modo) { $modo = null; }
      continue;
    }

    // —— Parse FALLECIDOS
    if (($force===null || $force==='FALLECIDOS') && $inFallecidos) {

      // fila de tabla: comienza con grado
      if (preg_match('/^\s*'.$GRADOS.'\b/iu',$l)) {

        // juntar varias líneas por si Lugar y Fecha están partidos
        $buf = $l;
        for ($k=1;$k<=4 && ($i+$k)<$n;$k++) {
          if (preg_match('/^\s*[3-9]\.\s*PERSONAL\b/iu',$lines[$i+$k])) break;
          $buf .= ' '.$lines[$i+$k];
        }
        $buf = preg_replace('/\s{2,}/',' ',$buf);

        // 1) grado
        preg_match('/^\s*'.$GRADOS.'\b/iu',$buf,$m1);
        $grado = strtoupper(preg_replace('/[^A-Z]/','',$m1[0]));
        $rest  = trim(substr($buf, strlen($m1[0])));

        // 2) arma (abreviatura conocida)
        $arma = null;
        if (preg_match('/^\s*(COM|INF|ART|CAB|ING|INT|SAN|AR|GNA|FAA)/iu',$rest,$ma, PREG_OFFSET_CAPTURE)) {
          $arma = strtoupper($ma[1][0]);
          $rest = trim(substr($rest, $ma[0][1] + strlen($ma[0][0])));
        }

        // 3) destino / situación de revista (Retirado | En Actividad | …)
        $destino = null;
        if (preg_match('/^\s*(Retirado|En Actividad|En actividad|Situaci[oó]n.*?|[A-Za-zÁÉÍÓÚÑ\/ ]{3,20})/u',$rest,$md, PREG_OFFSET_CAPTURE)) {
          $destino = trim($md[1][0]);
          $rest = trim(substr($rest, $md[0][1] + strlen($md[0][0])));
        }

        // 4) VGM (SI|NO) → lo saltamos
        if (preg_match('/^\s*(SI|NO)\b/iu',$rest,$mv, PREG_OFFSET_CAPTURE)) {
          $rest = trim(substr($rest, $mv[0][1] + strlen($mv[0][0])));
        }

        // 5) Apellido y Nombre (palabras Capitalizadas)
        $nombre = null;
        if (preg_match('/^([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+){1,5})/u',$rest,$mn, PREG_OFFSET_CAPTURE)) {
          $nombre = trim($mn[1][0]);
          $rest = trim(substr($rest, $mn[0][1] + strlen($mn[0][0])));
        }

        // 6) Lugar y Fecha: tomar última fecha y el lugar = resto sin esa fecha
        $fecha = extrae_fecha($rest); // remueve la fecha del $rest si existe
        $lugar = trim($rest);
        if ($fecha) {
          // borro posibles números pegados a la fecha (horario 251930Sep25 → 30Sep25)
          if (preg_match('/(\d{1,2}[A-Za-z]{3}\d{2})/',$fecha,$mf)) $fecha = $mf[1];
        }

        // 7) Velatorio y Sepelio → muchas veces “A determinar”
        $velatorio = (stripos($buf,'VELATORIO')!==false && stripos($buf,'A determinar')!==false) ? 'A determinar' : null;
        $sepelio   = (stripos($buf,'SEPELIO')!==false   && stripos($buf,'A determinar')!==false) ? 'A determinar' : null;

        // Detalle armado
        $detParts = [];
        if ($lugar) $detParts[] = 'Falleció en ' . $lugar . ($fecha ? " el $fecha" : '');
        if ($velatorio) $detParts[] = "Velatorio: $velatorio";
        if ($sepelio)   $detParts[] = "Sepelio: $sepelio";
        $detalle = $detParts ? implode('. ', $detParts) : null;

        $row = [
          'categoria' => 'FALLECIDOS',
          'Nro' => null,
          'Grado' => $grado,
          'Apellido y Nombre' => $nombre ?: null,
          'Arma' => $arma,
          'Unidad' => $destino ?: null,
          'Prom' => null,
          'Fecha' => $fecha,
          'Habitación' => null,
          'Hospital' => null,
          'Detalle' => $detalle,
        ];
        if (pasaFiltroCom($row)) $outF[] = $row;
      }

      // fin del bloque de tabla si aparece otra sección grande
      if (preg_match('/^\s*[3-9]\.\s*PERSONAL\b/iu',$l)) { $inFallecidos = false; }
      continue;
    }

    // —— INTERNADOS / ALTAS
    if (!$cat || !$modo) continue;
    if ($force && $force !== $modo) continue;

    if (preg_match('/\b'.$GRADOS.'\b/i',$l,$gm, PREG_OFFSET_CAPTURE)) {
      $buf = $l; for ($k=1;$k<=5 && ($i+$k)<$n;$k++) $buf .= ' '.$lines[$i+$k];
      $buf = preg_replace('/\s{2,}/',' ',$buf);

      $fecha = extrae_fecha($buf);
      $hab   = extrae_hab($buf);
      $hosp  = guess_hospital(preg_split('/\s+/',$buf));

      if (!preg_match('/\b'.$GRADOS.'\b/i',$buf,$m2, PREG_OFFSET_CAPTURE)) continue;
      $gradoRaw = $m2[0][0];
      $grado = strtoupper(preg_replace('/[^A-Z]/','',$gradoRaw));
      $post = trim(substr($buf, $m2[0][1] + strlen($m2[0][0])));

      $revCut  = preg_split('/\b(En Actividad|Retirado)\b/i', $post, 2, PREG_SPLIT_DELIM_CAPTURE);
      $pre     = trim($revCut[0] ?? ''); $postRev = trim($revCut[2] ?? '');

      $destino = '';
      if ($postRev !== '' && preg_match('/-\s*([^0-9]{2,}?)(?:\s*\(U\d+\))?/i',$postRev,$md)) $destino = trim($md[1]);

      $tokens = preg_split('/\s+/', $pre);
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
      if (pasaFiltroCom($row)) {
        if ($modo==='INTERNADOS') $outI[]=$row;
        if ($modo==='ALTAS')      $outA[]=$row;
      }
    }
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
    return $ra === $rb ? strcmp($a['Apellido y Nombre'] ?? '', $b['Apellido y Nombre'] ?? '') : ($ra <=> $rb);
  };
  usort($outI,$ord); usort($outA,$ord); usort($outF,$ord);
  $i=1; foreach($outI as &$r){ $r['Nro']=$i++; }
  $i=1; foreach($outF as &$r){ $r['Nro']=$i++; }

  return ['internado'=>$outI,'alta'=>$outA,'fallecido'=>$outF];
}

function pasaFiltroCom(array $r): bool {
  $cat = $r['categoria']; $arma = strtoupper($r['Arma'] ?? ''); $dest = strtoupper($r['Unidad'] ?? '');
  if ($cat==='OFICIALES' || $cat==='SUBOFICIALES' || $cat==='FALLECIDOS') return (strpos($arma,'COM') !== false);
  if ($cat==='SOLDADOS VOLUNTARIOS') return esUnidadCom($dest);
  return false;
}
function esUnidadCom(string $dest): bool {
  $pat = '/\b(B\s*MANT\s*COM|BAT\s*COM|BATALL[ÓO]N\s+DE\s+COM|BCOM|CA\s*COM|CIA\s*COM|BRIG\s*COM|COMUNICACIONES\b)\b/u';
  return (bool)preg_match($pat, $dest);
}
function extrae_fecha(string &$s): ?string {
  if (preg_match('/(\d{1,2}[A-Za-z]{3}\d{2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\b/',$s,$m,PREG_OFFSET_CAPTURE)) {
    $pos=$m[0][1]; $len=strlen($m[0][0]); $s = trim(substr($s,0,$pos).' '.substr($s,$pos+$len)); return $m[0][0];
  } return null;
}
function extrae_hab(string &$s): ?string {
  if (preg_match('/\b(UTI|PI|UCO|PAB|\d+[A-Z]?)\b/i',$s,$m,PREG_OFFSET_CAPTURE)) {
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
  foreach (['CENTRAL','CAMPO DE MAYO','CORDOBA','MENDOZA','PARANA','BAHIA BLANCA','RIO GALLEGOS','SALTA','CURUZU CUATIA','COMODORO RIVADAVIA','HOSPITAL REGIONAL','SANATORIO COLEGIALES'] as $h) {
    if (str_contains($s,$h)) return $h;
  }
  return null;
}
function norm_date(?string $s): ?string {
  if(!$s) return null;
  if (preg_match('/^(\d{1,2})([A-Za-z]{3})(\d{2})$/',$s,$m)) {
    $mm = ['ENE'=>'01','JAN'=>'01','FEB'=>'02','MAR'=>'03','ABR'=>'04','APR'=>'04','MAY'=>'05','JUN'=>'06','JUL'=>'07','AGO'=>'08','AUG'=>'08','SEP'=>'09','OCT'=>'10','NOV'=>'11','DIC'=>'12','DEC'=>'12'][strtoupper($m[2])] ?? '01';
    return '20'.$m[3].'-'.$mm.'-'.str_pad($m[1],2,'0',STR_PAD_LEFT);
  }
  if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{2,4})$/',$s,$m)) {
    $yy = strlen($m[3])===2 ? ('20'.$m[3]) : $m[3];
    return $yy.'-'.str_pad($m[2],2,'0',STR_PAD_LEFT).'-'.str_pad($m[1],2,'0',STR_PAD_LEFT);
  }
  return null;
}
