<?php
/**
 * Importar LTA (DOCX) → tablas personal_* (mismas reglas que CENOPE).
 * Intenta con PHPWord; si no está, usa ZipArchive leyendo word/document.xml.
 */
require_once __DIR__.'/db.php';

// Composer (PHPWord)
$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) require_once $autoload;

header('Content-Type: application/json; charset=utf-8');

try {
  if (!isset($_FILES['docx'])) throw new Exception('No se recibió el DOCX');
  $dry = ($_POST['dry'] ?? '1') === '1';

  $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lta_'.uniqid('', true).'.docx';
  if (!move_uploaded_file($_FILES['docx']['tmp_name'], $tmp)) throw new Exception('No se pudo guardar el archivo');

  // 1) Texto
  $text = '';
  if (class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
    $text = docx_to_text_phpword($tmp);
  }
  if ($text === '') {
    $text = docx_to_text_zip($tmp);
  }
  if ($text === '') {
    throw new Exception('No se pudo extraer texto del DOCX (PHPWord y ZipArchive fallaron).');
  }

  $clean  = normalize_text($text);
  $parsed = parse_cenope($clean); // Reutilizamos el parser del CENOPE

  if ($dry) { echo json_encode(['ok'=>true]+$parsed, JSON_UNESCAPED_UNICODE); exit; }

  // 2) Persistencia (reemplaza)
  $pdo = pdo();
  $pdo->beginTransaction();
  $pdo->exec("TRUNCATE personal_internado");
  $pdo->exec("TRUNCATE personal_alta");
  $pdo->exec("TRUNCATE personal_fallecido");

  $insI = $pdo->prepare("INSERT INTO personal_internado (categoria,nro,grado,apellido_nombre,arma,unidad,prom,fecha,habitacion,hospital)
                         VALUES (?,?,?,?,?,?,?,?,?,?)");
  foreach ($parsed['internado'] as $r) {
    $insI->execute([
      $r['categoria'],$r['Nro']??null,$r['Grado']??null,$r['Apellido y Nombre']??null,
      $r['Arma']??null,$r['Unidad']??null,$r['Prom']??null,
      norm_date($r['Fecha']??null),$r['Habitación']??null,$r['Hospital']??null
    ]);
  }

  $insA = $pdo->prepare("INSERT INTO personal_alta (categoria,grado,apellido_nombre,arma,unidad,prom,fecha,hospital)
                         VALUES (?,?,?,?,?,?,?,?)");
  foreach ($parsed['alta'] as $r) {
    $insA->execute([
      $r['categoria'],$r['Grado']??null,$r['Apellido y Nombre']??null,
      $r['Arma']??null,$r['Unidad']??null,$r['Prom']??null,
      norm_date($r['Fecha']??null),$r['Hospital']??null
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

/* ====== Extractores DOCX ====== */
function docx_to_text_phpword(string $file): string {
  try {
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($file);
    $txt = '';
    foreach ($phpWord->getSections() as $section) {
      foreach ($section->getElements() as $el) {
        if (method_exists($el,'getText')) {
          $txt .= $el->getText() . "\n";
        } elseif (method_exists($el,'getElements')) {
          foreach ($el->getElements() as $sub) {
            if (method_exists($sub,'getText')) $txt .= $sub->getText() . "\n";
          }
        }
      }
    }
    return $txt;
  } catch (\Throwable $e) { return ''; }
}
function docx_to_text_zip(string $file): string {
  $zip = new ZipArchive();
  if ($zip->open($file) === true) {
    $xml = $zip->getFromName('word/document.xml') ?: '';
    $zip->close();
    if ($xml) {
      // Reemplazar etiquetas por saltos, luego strip tags
      $xml = preg_replace('/<\/w:p>/', "\n", $xml);
      $xml = preg_replace('/<w:tab\/>/', " ", $xml);
      $xml = preg_replace('/<w:br[^>]*>/', "\n", $xml);
      return trim(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }
  }
  return '';
}

/* ====== Reuso de helpers del import CENOPE ====== */
function normalize_text(string $t): string {
  $t = preg_replace('/\\x{00A0}/u',' ',$t);
  $t = preg_replace('/\\s{2,}/',' ',$t);
  $lines = array_map('rtrim', preg_split('/\\r?\\n/',$t));
  $lines = array_values(array_filter($lines, fn($l)=>$l!==''));
  return implode("\n",$lines);
}

/* Copiamos mini-parser usado en import_cenope (idéntico) */
function parse_cenope(string $txt): array {
  // Para evitar duplicado largo, podés require_once el import_cenope.php y
  // llamar su parse_cenope(). Lo dejé inline para que sea standalone.
  // --- (MISMO CONTENIDO QUE EN import_cenope.php) ---
  $outI=[]; $outA=[]; $outF=[]; $cat=null; $modo=null;
  $GRADOS='(TG|GD|GB|CY|CR|TC|MY|CT|TP|TT|ST|SM|SP|SA|SI|SG|CI|CB|VP|VS|SV|SOLD)';
  $lines = preg_split('/\\n/',$txt);
  $n = count($lines);
  for ($i=0;$i<$n;$i++){
    $l=$lines[$i];
    if (preg_match('/\\b(OFICIALES|SUBOFICIALES|SOLDADOS\\s+VOLUNTARIOS)\\b/i',$l,$m)){ $cat=strtoupper($m[1]); $modo=null; continue; }
    if (preg_match('/\\b(1\\)\\s*INTERNAD(?:O|OS)|2\\)\\s*ALTAS?)\\b/i',$l,$m)){ $modo=stripos($m[1],'ALTA')!==false?'ALTAS':'INTERNADOS'; continue; }
    if(!$cat||!$modo) continue;
    if (preg_match('/\\b'.$GRADOS.'\\b/i',$l)){
      $buf=$l; for($k=1;$k<=5&&($i+$k)<$n;$k++) $buf.=' '.$lines[$i+$k];
      $buf=preg_replace('/\\s{2,}/',' ',$buf);
      $fecha=extrae_fecha($buf); $hab=extrae_hab($buf); $hosp=guess_hospital(preg_split('/\\s+/',$buf));
      if(!preg_match('/\\b'.$GRADOS.'\\b/i',$buf,$m2,PREG_OFFSET_CAPTURE)) continue;
      $grado=strtoupper(preg_replace('/[^A-Z]/','',$m2[0][0]));
      $post=trim(substr($buf,$m2[0][1]+strlen($m2[0][0])));
      $revCut=preg_split('/\\b(En Actividad|Retirado)\\b/i',$post,2,PREG_SPLIT_DELIM_CAPTURE);
      $pre=trim($revCut[0]??''); $postRev=trim($revCut[2]??'');
      $destino=''; if($postRev!=='' && preg_match('/-\\s*([^0-9]{2,}?)(?:\\s*\\(U\\d+\\))?/i',$postRev,$md)) $destino=trim($md[1]);
      $tokens=preg_split('/\\s+/',$pre); $arma=array_pop($tokens)??'';
      while(!empty($tokens)&&mb_strlen($arma)<=4&&mb_strlen(end($tokens))<=4){ $arma=array_pop($tokens).' '.$arma; }
      $nombre=trim(implode(' ',$tokens)); if($nombre==='') continue;
      $row=['categoria'=>map_cat($cat),'Nro'=>null,'Grado'=>$grado,'Apellido y Nombre'=>$nombre,'Arma'=>$arma?:null,'Unidad'=>$destino?:null,'Prom'=>null,'Fecha'=>$fecha,'Habitación'=>$hab,'Hospital'=>$hosp];
      if(pasaFiltroCom($row)){ if($modo==='INTERNADOS') $outI[]=$row; else $outA[]=$row; }
    }
    if (preg_match('/\\bFALLECID[OA]S?\\b/i',$l)) $outF[]=trim($l);
  }
  $rank=['TG'=>0,'GD'=>0.1,'GB'=>0.2,'CY'=>0.5,'CR'=>1,'TC'=>2,'MY'=>3,'CT'=>4,'TP'=>5,'TT'=>6,'ST'=>7,'SM'=>10,'SP'=>11,'SA'=>12,'SI'=>13,'SG'=>14,'CI'=>15,'CB'=>16,'VP'=>30,'VS'=>31,'SV'=>32,'SOLD'=>33];
  $ord=function($a,$b)use($rank){$ra=$rank[$a['Grado']]??999;$rb=$rank[$b['Grado']]??999;return $ra===$rb?strcmp($a['Apellido y Nombre'],$b['Apellido y Nombre']):$ra<=>$rb;};
  usort($outI,$ord); usort($outA,$ord); $i=1; foreach($outI as &$r){$r['Nro']=$i++;}
  return ['internado'=>$outI,'alta'=>$outA,'fallecido'=>$outF];
}

/* Helpers copiados del import CENOPE */
function extrae_fecha(string &$s): ?string { if (preg_match('/(\\d{1,2}[A-Za-z]{3}\\d{2}|\\d{1,2}[\\/\\-]\\d{1,2}[\\/\\-]\\d{2,4})\\b/',$s,$m,PREG_OFFSET_CAPTURE)){ $pos=$m[0][1];$len=strlen($m[0][0]);$s=trim(substr($s,0,$pos).' '.substr($s,$pos+$len));return $m[0][0]; } return null; }
function extrae_hab(string &$s): ?string { if (preg_match('/\\b(UTI|PI|UCO|PAB|\\d+[A-Z]?)\\b/i',$s,$m,PREG_OFFSET_CAPTURE)){ $pos=$m[0][1];$len=strlen($m[0][0]);$s=trim(substr($s,0,$pos).' '.substr($s,$pos+$len));return $m[0][0]; } return null; }
function map_cat(string $s): string { $s=strtoupper($s); if(str_starts_with($s,'OFICIA')) return 'OFICIALES'; if(str_starts_with($s,'SUBOFI')) return 'SUBOFICIALES'; return 'SOLDADOS VOLUNTARIOS'; }
function guess_hospital(array $cols): ?string { $s=strtoupper(implode(' ',array_slice($cols,-5))); foreach(['CENTRAL','CAMPO DE MAYO','CORDOBA','MENDOZA','PARANA','BAHIA BLANCA','RIO GALLEGOS','SALTA','CURUZU CUATIA','COMODORO RIVADAVIA','HOSPITAL REGIONAL','SANATORIO COLEGIALES'] as $h){ if(str_contains($s,$h)) return $h; } return null; }
function norm_date(?string $s): ?string { if(!$s)return null; if(preg_match('/^(\\d{1,2})([A-Za-z]{3})(\\d{2})$/',$s,$m)){ $mm=['ENE'=>'01','JAN'=>'01','FEB'=>'02','MAR'=>'03','ABR'=>'04','APR'=>'04','MAY'=>'05','JUN'=>'06','JUL'=>'07','AGO'=>'08','AUG'=>'08','SEP'=>'09','OCT'=>'10','NOV'=>'11','DIC'=>'12','DEC'=>'12'][strtoupper($m[2])]??'01'; return '20'.$m[3].'-'.$mm.'-'.str_pad($m[1],2,'0',STR_PAD_LEFT);} if(preg_match('/^(\\d{1,2})[\\/-](\\d{1,2})[\\/-](\\d{2,4})$/',$s,$m)){ $yy=strlen($m[3])===2?('20'.$m[3]):$m[3]; return $yy.'-'.str_pad($m[2],2,'0',STR_PAD_LEFT).'-'.str_pad($m[1],2,'0',STR_PAD_LEFT);} return null; }
function pasaFiltroCom(array $r): bool { $cat=$r['categoria']; $arma=strtoupper($r['Arma']??''); $dest=strtoupper($r['Unidad']??''); if($cat==='OFICIALES'||$cat==='SUBOFICIALES') return (strpos($arma,'COM')!==false); if($cat==='SOLDADOS VOLUNTARIOS') return esUnidadCom($dest); return false; }
function esUnidadCom(string $dest): bool { $pat='/\\b('.'B\\s*MANT\\s*COM'.'|BAT\\s*COM'.'|BATALL[ÓO]N\\s+DE\\s+COM'.'|BCOM'.'|CA\\s*COM'.'|CIA\\s*COM'.'|BRIG\\s*COM'.'|COMUNICACIONES\\b'.')\\b/u'; return (bool)preg_match($pat,$dest); }
