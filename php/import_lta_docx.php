<?php
require_once __DIR__.'/db.php';
header('Content-Type: application/json; charset=utf-8');

try{
  if(!isset($_FILES['docx'])) throw new Exception('No se recibió el DOCX');
  $dry = ($_POST['dry'] ?? '1')==='1';

  $xml = read_docx_xml($_FILES['docx']['tmp_name']);             // texto plano con saltos
  $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $xml))));

  // Buscar secciones
  $sec = detect_sections($lines);
  $bloques = [
    'NOVEDADES DEL SISTEMA' => $sec['NOVEDADES DEL SISTEMA'] ?? [],
    'RADIOENLACES'          => $sec['RADIOENLACES'] ?? [],
    'INTEGRACIONES'         => $sec['INTEGRACIONES'] ?? [],
  ];

  // Previsualización
  if($dry){
    echo json_encode(['ok'=>true,'preview'=>$bloques], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Insertar como novedades (una por renglón no vacío)
  $pdo = pdo();
  $insN = $pdo->prepare("INSERT INTO novedad (titulo,descripcion,categoria_id,unidad_id,servicio,ticket,prioridad,estado,creado_por)
                         VALUES (?,?,?,?,?,?,?,?,?)");
  $insE = $pdo->prepare("INSERT INTO novedad_evento (novedad_id,tipo,detalle,usuario) VALUES (?,?,?,?)");

  $count=0;
  foreach($bloques as $tituloBloque=>$renglones){
    foreach($renglones as $ln){
      if($ln==='') continue;
      [$catId,$unidad,$serv,$ticket] = map_line_to_fields($tituloBloque, $ln);
      $titulo = build_title($tituloBloque,$ln);
      $insN->execute([$titulo,$ln,$catId,$unidad,$serv,$ticket,'MEDIA','ABIERTO','IMPORTADOR DOCX']);
      $nid = $pdo->lastInsertId();
      $insE->execute([$nid,'CREADA',$ln,'IMPORTADOR DOCX']);
      $count++;
    }
  }

  echo json_encode(['ok'=>true,'insertadas'=>$count], JSON_UNESCAPED_UNICODE);

}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/* ===== helpers ===== */
function read_docx_xml(string $path): string {
  $zip = new ZipArchive();
  if($zip->open($path)!==TRUE) throw new Exception('No se pudo abrir el DOCX');
  $xml = $zip->getFromName('word/document.xml');
  $zip->close();
  if(!$xml) throw new Exception('No se encontró document.xml');

  // Quitar tags, dejando cada <w:p> (párrafo) en una línea
  $xml = preg_replace('/<w:tab\\/>/',' ', $xml);
  $xml = preg_replace('/<w:br\\/>/',' ', $xml);
  $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
  $xml = strip_tags($xml);
  $xml = html_entity_decode($xml, ENT_QUOTES|ENT_HTML5, 'UTF-8');
  // Normalizar espacios
  $xml = preg_replace('/\\s{2,}/',' ', $xml);
  return trim($xml);
}

// Devuelve ['SECCIÓN' => [lineas...]]
function detect_sections(array $lines): array {
  $out=[]; $cur=null;
  $heads = ['NOVEDADES DEL SISTEMA','RADIOENLACES','INTEGRACIONES'];
  foreach($lines as $l){
    $u = mb_strtoupper($l,'UTF-8');
    if(in_array($u,$heads,true)){ $cur=$u; $out[$cur]=[]; continue; }
    // Cortes típicos de firma/otros bloques
    if($cur && preg_match('/^CABA|^EJ[ÉE]RCITO|^PARTE DE|^PERSONAL DE SERVICIO/i',$l)){ $cur=null; continue; }
    if($cur) $out[$cur][] = $l;
  }
  // Limpiar encabezados de subtítulos “RED COMANDOAF:”, “IDIRECT:”, etc.
  foreach($out as $k=>$arr){
    $arr = array_map(fn($x)=>preg_replace('/^(RED\\s+COMANDOAF|IDIRECT|VOZ\\/DATOS|CCIG\\/GPO\\s*COM|SARE|POLICIA.*?|INTEGRACIONES)\\s*:?\\s*/i','',$x), $arr);
    $out[$k] = array_values(array_filter($arr, fn($x)=>$x!==''));
  }
  return $out;
}

// Regla simple de mapeo -> (categoria_id, unidad_id, servicio, ticket)
function map_line_to_fields(string $bloque, string $l): array {
  $cat = 2; // default: “Sistemas – Servicios”
  $unidad = null; $serv=null; $ticket=null;

  // Si inicia con “CCIG xxx” lo mando a categoría 1 (Radioeléctricos – Nodos REDISE)
  if (preg_match('/^CCIG\\s+([A-ZÁÉÍÓÚÑ\\s]+):?/i',$l,$m)) { $cat=1; /* opcional: resolver unidad por catálogo */ }

  // ISP Edificio Libertador
  if (stripos($l,'ISP')!==false || stripos($l,'Edificio Libertador')!==false) $cat=3;

  // SITELPAR
  if (stripos($l,'SITELPAR')!==false) $cat=4;

  // Data Center
  if (stripos($l,'Data Center')!==false) $cat=5;

  // Ticket GLPI/MM si viene “MM”, “NC”, “GLPI”, “N°”
  if (preg_match('/\\b(GLPI|MM|NC)\\s*([\\w\\-\\/\\.]+)/i',$l,$m)) $ticket = trim($m[1].' '.$m[2]);

  // Servicio (palabras clave)
  if (preg_match('/\\b(HF|VHF|UHF|WEBMAIL|INTERNET|FIBRA|RADIOENLACE|IDIRECT|VOZ\\/DATOS)\\b/i',$l,$m)) $serv = strtoupper($m[1]);

  return [$cat,$unidad,$serv,$ticket];
}

function build_title(string $bloque, string $l): string {
  $t = $l;
  $t = preg_replace('/^CCIG\\s+([A-ZÁÉÍÓÚÑ\\s]+):?\\s*/i','CCIG $1 – ',$t);
  return mb_strimwidth($t, 0, 128, '…','UTF-8');
}
