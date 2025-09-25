<?php
/**
 * Importa LTA (DOCX o PDF) → tabla REDISE (Nodo, Desde, Novedades, Fecha, Servicio, Ticket)
 * Soporta:
 *  - Formato narrativo del CCC: "CCIG ...: texto ..."
 *  - Formato tabular (PDF/Word): columnas NODO/DESDE/NOVEDADES/FECHA/SERVICIO/TICKET
 * Devuelve: { ok:true, _src:"extractor", redise:[{nodo,desde,novedad,fecha,servicio,ticket}] }
 */
require_once __DIR__.'/db.php';

$autoload = dirname(__DIR__).'/vendor/autoload.php';
if (is_file($autoload)) require_once $autoload;

use PhpOffice\PhpWord\IOFactory;

$PDFTOTEXT = 'C:\\Release-25.07.0-0\\Library\\bin\\pdftotext.exe';

header('Content-Type: application/json; charset=utf-8');

try {
  if (!isset($_FILES['file'])) throw new Exception('No se recibió archivo (usa clave "file").');
  $dry = ($_POST['dry'] ?? '1') === '1';

  $orig = $_FILES['file']['name'] ?? '';
  $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
  $tmp  = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lta_'.uniqid().'.'.($ext ?: 'bin');
  if (!move_uploaded_file($_FILES['file']['tmp_name'], $tmp)) throw new Exception('No se pudo guardar el archivo');

  // === 1) Texto plano ===
  $src = ''; $text = '';
  if ($ext === 'docx')            { [$text,$src] = docx_to_text($tmp); }
  elseif ($ext === 'pdf')         { [$text,$src] = pdf_to_text($tmp, $PDFTOTEXT); }
  else {
    $mime = mime_content_type($tmp) ?: '';
    if (str_contains($mime,'officedocument') || str_contains($mime,'word')) { [$text,$src] = docx_to_text($tmp); }
    elseif (str_contains($mime,'pdf')) { [$text,$src] = pdf_to_text($tmp, $PDFTOTEXT); }
    else throw new Exception('Formato no soportado. Subí DOCX o PDF.');
  }
  if (trim($text)==='') throw new Exception('No se pudo extraer texto. Si es PDF escaneado, hacé OCR.');

  // === 2) Normalizar y tomar la sección REDISE ===
  $clean = normalize_lta($text);
  $scope = recorta_seccion_redise($clean);

  // === 3) Parseo (narrativo → tabular) ===
  $rows = parse_redise_narrativo($scope);
  if (empty($rows)) $rows = parse_redise_tabular($scope);

  // Limpieza final, quitar “FOLIO …” y compactar
  $rows = array_values(array_filter($rows, function($r){
    $n = strtoupper(trim($r['nodo'] ?? ''));
    return $n !== '' && !str_starts_with($n, 'FOLIO');
  }));

  // Resumen corto
  $redise = array_values(array_filter(array_map(function($r){
    $r['nodo']     = trim($r['nodo'] ?? '');
    $r['desde']    = trim($r['desde'] ?? '');
    $r['novedad']  = resumen_ccc(trim($r['novedad'] ?? ''));
    $r['fecha']    = trim($r['fecha'] ?? '');     // en UI la sobreescribimos con FECHA TURNO
    $r['servicio'] = trim($r['servicio'] ?? '');
    $r['ticket']   = trim($r['ticket'] ?? '');
    return $r['nodo'] && $r['novedad'] ? $r : null;
  }, $rows)));

  if ($dry) {
    echo json_encode(['ok'=>true,'_src'=>$src,'redise'=>$redise], JSON_UNESCAPED_UNICODE);
    exit;
  }

  echo json_encode(['ok'=>true,'_src'=>$src,'redise'=>$redise], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/* ======================== Extractores ======================== */

function docx_to_text(string $file): array {
  if (class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
    try {
      $pw = IOFactory::load($file);
      $t = '';
      foreach ($pw->getSections() as $sec) {
        foreach ($sec->getElements() as $el) {
          if (method_exists($el,'getElements')) {
            foreach ($el->getElements() as $ch) if (method_exists($ch,'getText')) $t .= $ch->getText()."\n";
          } elseif (method_exists($el,'getText')) $t .= $el->getText()."\n";
        }
      }
      if (trim($t)!=='') return [$t,'phpword'];
    } catch (Throwable $e) { /* fallback */ }
  }
  if (class_exists(\ZipArchive::class)) {
    $zip = new ZipArchive();
    if ($zip->open($file) === true) {
      $xml = $zip->getFromName('word/document.xml'); $zip->close();
      if ($xml) {
        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        $txt = trim(strip_tags($xml));
        if ($txt!=='') return [$txt,'zip(document.xml)'];
      }
    }
  }
  return ['','docx:sin_texto'];
}

function pdf_to_text(string $file, string $pdftotext): array {
  if (class_exists(\Smalot\PdfParser\Parser::class)) {
    try {
      $parser = new \Smalot\PdfParser\Parser();
      $pdf = $parser->parseFile($file);
      $t = $pdf->getText();
      if (trim($t)!=='') return [$t,'smalot/pdfparser'];
    } catch (Throwable $e) { /* fallback */ }
  }
  if ($pdftotext && is_file($pdftotext)) {
    $out = $file.'.txt'; @unlink($out);
    $spec = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
    $cmd  = escapeshellcmd($pdftotext).' -layout -nopgbrk '.escapeshellarg($file).' '.escapeshellarg($out);
    $p = proc_open($cmd,$spec,$pipes);
    if (is_resource($p)) {
      fclose($pipes[0]); stream_get_contents($pipes[1]); stream_get_contents($pipes[2]);
      fclose($pipes[1]); fclose($pipes[2]); proc_close($p);
      $t = is_file($out) ? (string)@file_get_contents($out) : '';
      if (trim($t)!=='') return [$t,'pdftotext'];
    }
  }
  return ['','pdf:sin_texto'];
}

/* ======================== Normalización ======================== */

function normalize_lta(string $t): string {
  $t = preg_replace('/Powered\\s+by\\s+TCPDF.*/i','',$t);
  $t = preg_replace('/Página\\s+\\d+\\s+de\\s+\\d+.*/i','',$t);
  $t = preg_replace('/\\xC2\\xA0/u',' ', $t);            // NBSP
  $t = preg_replace('/[ \\t]{2,}/',' ', $t);
  $t = preg_replace('/\\s*\\n\\s*/', "\n", $t);
  $t = preg_replace('/\\n{2,}/', "\n", $t);
  return trim($t);
}

function recorta_seccion_redise(string $t): string {
  // Desde el título REDISE hasta antes de la siguiente macro-sección
  $ini = preg_match('/SISTEMAS?\\s*[-–]\\s*RADIOELECTRICOS?\\s*[-–]\\s*NODOS?\\s*REDISE/i', $t, $m, PREG_OFFSET_CAPTURE)
    ? $m[0][1] : 0;
  $tail = substr($t, $ini);
  $tail = preg_split('/\\n\\s*SISTEMAS?\\s*[-–]\\s*(SERVICIOS|ISP|SITELPAR|DATA\\s*CENTER|SITM2)\\b/i', $tail)[0];
  return trim($tail);
}

/* ========================= Parsers ========================= */

/* A) Narrativo CCC: "CCIG XXX: texto..." */
function parse_redise_narrativo(string $txt): array {
  $out = [];
  // Forzar salto antes de cada "CCIG ...:" para detectar bloques
  $txt = preg_replace('/\\s+(CCIG\\s+[A-ZÁÉÍÓÚÑ ]{3,}:)/u', "\n$1", $txt);

  if (preg_match_all('/^\\s*(CCIG\\s+[A-ZÁÉÍÓÚÑ ]{3,})\\s*:\\s*(.+?)(?=\\n\\s*CCIG\\s+[A-ZÁÉÍÓÚÑ ]{3,}\\s*:|\\Z)/us', $txt, $m, PREG_SET_ORDER)) {
    foreach ($m as $mm) {
      $block = trim($mm[2]);

      $desde = detect_desde($block, true); // true => tomar la ÚLTIMA fecha corta
      $fecha = detect_fecha($block);
      $serv  = detect_servicio($block);
      $tic   = detect_ticket($block);

      $out[] = [
        'nodo'     => trim($mm[1]),
        'desde'    => $desde,
        'novedad'  => limpia_colas($block),
        'fecha'    => $fecha,
        'servicio' => $serv,
        'ticket'   => $tic,
      ];
    }
  }
  return $out;
}

/* B) Tabular: encabezados NODO/DESDE/... */
function parse_redise_tabular(string $txt): array {
  $lines = array_values(array_filter(explode("\n", $txt), fn($l)=>trim($l)!=='' ));

  // Sacar encabezados y “FOLIO …”
  $lines = array_values(array_filter($lines, fn($l)=>!preg_match('/^(NODO|DESDE|NOVEDAD|NOVEDADES|FECHA|SERVICIO|TICKET)\\b/i',$l)));
  $lines = array_values(array_filter($lines, fn($l)=>!preg_match('/^FOLIO\\b/i',$l)));

  $rows = [];
  $buf  = [];

  $isNodo = function($l){
    $l = trim($l);
    return (bool)preg_match('/^(CCIG\\s+[A-ZÁÉÍÓÚÑ ]{3,}|CA\\s*COM|CIA\\s*COM|BCOM|BRIG\\s*COM|CCIE)\\b/u', $l);
  };

  foreach ($lines as $l) {
    if ($isNodo($l)) {
      if ($buf) { $rows[] = implode(' ', $buf); $buf = []; }
      $buf[] = trim($l);
    } else {
      if ($buf) $buf[] = trim($l);
    }
  }
  if ($buf) $rows[] = implode(' ', $buf);

  $out = [];
  foreach ($rows as $r) {
    // Nodo
    $nodo = '';
    if (preg_match('/^(CCIG\\s+[A-ZÁÉÍÓÚÑ ]{3,}|CA\\s*COM|CIA\\s*COM|BCOM|BRIG\\s*COM|CCIE)\\b/u', $r, $mn)) $nodo = trim($mn[0]);

    // Fechas / servicio / ticket
    $desde = detect_desde($r, true); // última fecha corta del bloque (suele coincidir con DESDE real)
    $fecha = detect_fecha($r);
    $serv  = detect_servicio($r);
    $tic   = detect_ticket($r);

    // Novedad = quitar “nodo” al inicio y “fecha/serv/ticket” al final
    $nov = $r;
    if ($nodo)  $nov = preg_replace('/^'.preg_quote($nodo,'/').'\\b\\s*/u','', $nov);
    if ($desde) $nov = preg_replace('/^'.preg_quote($desde,'/').'\\b\\s*/u','', $nov);
    if ($fecha) $nov = preg_replace('/\\b'.preg_quote($fecha,'/').'\\b.*$/u','', $nov);
    $nov = trim($nov);

    $out[] = [
      'nodo'     => $nodo,
      'desde'    => $desde,
      'novedad'  => limpia_colas($nov),
      'fecha'    => $fecha,
      'servicio' => $serv,
      'ticket'   => $tic,
    ];
  }
  return $out;
}

/* ========================= Detectores ========================= */

function detect_desde(string $s, bool $takeLast=false): ?string {
  // ENE24 | 24FEB25 | 09OCT24 | 16MAY25 (preferimos la ÚLTIMA si $takeLast=true)
  if (preg_match_all('/\\b(\\d{1,2}[A-Z]{3}\\d{2}|[A-Z]{3}\\d{2})\\b/u', $s, $m) && !empty($m[1])) {
    return $takeLast ? end($m[1]) : $m[1][0];
  }
  return null;
}
function detect_fecha(string $s): ?string {
  if (preg_match_all('/\\b(\\d{1,2}[A-Z]{3}\\d{2})\\b/u', $s, $m) && !empty($m[1])) return end($m[1]);
  return null;
}
function detect_servicio(string $s): ?string {
  if (preg_match('/\\b(HF\\/?VHF|VHF\\/?HF|HF|VHF)\\b/u', $s, $m)) return str_replace('VHF/HF','HF/VHF',$m[1]);
  return null;
}
function detect_ticket(string $s): ?string {
  if (preg_match('/(?:Ticket|N°\\s*Ticket|GLPI|MM)\\s*[:#]?[\\s-]*([A-Z0-9\\/-]{3,})/iu', $s, $m)) return $m[1];
  return null;
}
function limpia_colas(string $s): string {
  $s = preg_replace('/\\s*—+\\s*$/','', $s);
  $s = preg_replace('/\\s{2,}/',' ', $s);
  return trim($s);
}
function resumen_ccc(string $s): string {
  // Resumen breve
  $s = preg_replace('/^De acuerdo (?:al|a)\\s+MM[^,]*\\,?\\s*/i','', $s);
  $s = preg_replace('/\\b(informan?|se informa(n)?)\\b/iu','', $s);
  $s = preg_replace('/\\s+por\\s+mantenimiento,?/i',' (mantenimiento) ', $s);
  $s = preg_replace('/\\bF\\/S\\b/i','F/S', $s);
  $s = preg_replace('/\\s{2,}/',' ', $s);
  return mb_substr(trim($s), 0, 240);
}
