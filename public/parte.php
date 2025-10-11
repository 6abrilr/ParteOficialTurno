<?php
// public/parte.php
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

/* ====== LOG habilitado ====== */
// Crea /logs si no existe
$logDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
ini_set('log_errors', '1');
ini_set('error_log', $logDir . DIRECTORY_SEPARATOR . 'parte.log');
error_reporting(E_ALL);

/* ===== Helpers ===== */
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function app_base_simple(): string {
  $dir  = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
  $base = preg_replace('#/public$#', '', $dir) ?: '/';
  return $base;
}
function mes_es(int $m): string {
  $n = [1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  return $n[$m] ?? '';
}
function rango_es(string $desde, string $hasta): string {
  $a = new DateTime($desde); $b = new DateTime($hasta);
  $d1=(int)$a->format('j'); $m1=(int)$a->format('n'); $y1=(int)$a->format('Y');
  $d2=(int)$b->format('j'); $m2=(int)$b->format('n'); $y2=(int)$b->format('Y');
  if ($y1 === $y2 && $m1 === $m2) return "{$d1} al {$d2} de ".mes_es($m1)." de {$y1}";
  if ($y1 === $y2)           return "{$d1} de ".mes_es($m1)." al {$d2} de ".mes_es($m2)." de {$y1}";
  return "{$d1} de ".mes_es($m1)." de {$y1} al {$d2} de ".mes_es($m2)." de {$y2}";
}
function filename_safe(string $s): string {
  $s = trim($s);
  if (function_exists('iconv')) { $t = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); if ($t!==false) $s=$t; }
  $s = preg_replace('/[\/\\\\:\*\?"<>\|]/', ' ', $s);
  $s = preg_replace('/\s+/', ' ', $s);
  return trim($s, ". ");
}
// usuario actual (si hay sesión)
function current_user_id(): ?int {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_name('POTSESSID');
    @session_start();
  }
  return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

/** Guardar/actualizar registro en partes (sin IGNORE, con UPSERT) */
function save_parte_record(PDO $pdo, array $enc, string $desde, string $hasta, string $titulo, string $fileRel): void {
  $sql = "INSERT INTO partes
            (fecha_desde, fecha_hasta, oficial_turno, suboficial_turno, titulo, file_rel_path, created_by)
          VALUES (?,?,?,?,?,?,?)
          ON DUPLICATE KEY UPDATE
            titulo = VALUES(titulo),
            oficial_turno = VALUES(oficial_turno),
            suboficial_turno = VALUES(suboficial_turno),
            created_by = VALUES(created_by)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    $desde, $hasta,
    $enc['oficial_turno'] ?? null,
    $enc['suboficial_turno'] ?? null,
    $titulo,
    $fileRel,
    current_user_id()
  ]);
  error_log("save_parte_record: OK file_rel_path={$fileRel}");
}

/* ===== Parámetros ===== */
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
if (!$desde || !$hasta) { http_response_code(400); die('Faltan parámetros desde/hasta'); }

$wantPdf  = !empty($_GET['pdf']);
$wantSave = !empty($_GET['save']);
$silent   = !empty($_GET['silent']);          // fetch silencioso desde la UI
$showUi   = !$wantPdf && !$wantSave;          // vista normal

$tituloCustom = trim((string)($_GET['titulo'] ?? ''));
$tituloHumano = $tituloCustom !== ''
  ? $tituloCustom
  : "Parte de novedades del Arma de Comunicaciones del dia " . rango_es($desde, $hasta);

error_log("parte.php init  desde={$desde}  hasta={$hasta}  wantPdf={$wantPdf} wantSave={$wantSave} silent={$silent}");

/* ===== DB ===== */
$pdo = db();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* Encabezado */
$enc = $pdo->prepare("
  SELECT * FROM parte_encabezado
  WHERE fecha_desde = ? AND fecha_hasta = ?
  ORDER BY id DESC LIMIT 1
");
$enc->execute([$desde, $hasta]);
$enc = $enc->fetch() ?: [
  'oficial_turno'    => '',
  'suboficial_turno' => '',
  'fecha_desde'      => $desde,
  'fecha_hasta'      => $hasta,
];

/* Pendientes */
$sqlPend = "SELECT n.*, c.nombre AS categoria
            FROM novedad n
            JOIN categoria c ON c.id = n.categoria_id
            WHERE n.estado <> 'RESUELTO'
            ORDER BY n.prioridad DESC, n.fecha_inicio DESC";
$pendientes = $pdo->query($sqlPend)->fetchAll();

/* CENOPE */
function col(array $r, string $a, ?string $b=null) { return $r[$a] ?? ($b && isset($r[$b]) ? $r[$b] : null); }
$ordenGrado = "FIELD(grado,'TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST','SM','SP','SA','SI','SG','CI','CB','VP','VS','SV','SOLD')";
$pi = $pdo->query("SELECT * FROM personal_internado ORDER BY categoria, $ordenGrado, apellido_nombre, apellidoNombre")->fetchAll();
$pa = $pdo->query("SELECT * FROM personal_alta      ORDER BY categoria, $ordenGrado, apellido_nombre, apellidoNombre")->fetchAll();
$pf = $pdo->query("SELECT * FROM personal_fallecido ORDER BY id DESC")->fetchAll();
function groupByKey(array $rows, string $key): array { $g=[]; foreach($rows as $r){ $g[$r[$key]][]=$r; } return $g; }
$piG = groupByKey($pi, 'categoria');
$paG = groupByKey($pa, 'categoria');

/* Estados */
$estados = $pdo->query("SELECT * FROM sistema_estado ORDER BY categoria_id, nombre")->fetchAll();
$estGrp  = []; foreach ($estados as $r) { $estGrp[$r['categoria_id']][] = $r; }

/* Render genérico */
function render_table(array $headers, callable $rowRenderer, array $rows, string $emptyLabel='SIN NOVEDAD'){
  echo "<div class='table-responsive'><table class='table table-sm table-bordered'><thead><tr>";
  foreach($headers as $h) echo "<th>".h($h)."</th>";
  echo "</tr></thead><tbody>";
  if (!$rows) echo "<tr><td colspan='".count($headers)."' class='text-center text-muted'>".h($emptyLabel)."</td></tr>";
  else foreach ($rows as $r) echo $rowRenderer($r);
  echo "</tbody></table></div>";
}
function render_estado_tabla(array $rows, array $cols){
  $headers = $cols;
  $rowRenderer = function($r){
    $nov   = ($r['estado']==='EN LINEA' && empty($r['novedad'])) ? 'EN LÍNEA' : ($r['novedad'] ?: $r['estado']);
    $fecha = !empty($r['actualizado_en']) ? date('d/m/Y', strtotime($r['actualizado_en'])) : '';
    $nov  .= !empty($r['ticket']) ? " ({$r['ticket']})" : "";
    return "<tr><td>".h($r['nombre'])."</td><td>".h($fecha)."</td><td>".h($nov)."</td></tr>";
  };
  render_table($headers, $rowRenderer, $rows);
}

/* ===== Buffer HTML ===== */
ob_start();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= h($tituloHumano) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" type="image/png" href="img/escudo602sinfondo.png">
<link rel="shortcut icon" href="img/escudo602sinfondo.png">
<style>
  @page { size: A4; margin: 18mm 16mm; }
  :root{
    --fg:#000; --grid:#000; --thead:#f2f2f2;
    --fz-ea:16pt; --fz-bco:14pt; --fz-motto:12pt; --fz-h2:13pt;
    --fz-turno-fecha:12pt; --fz-turno-personal:12pt;
    --enc-left-shift:10mm;
  }
  html,body{ background:#fff !important; }
  body{ margin:0; color:var(--fg); font-family:"Times New Roman", Times, serif; font-size:12pt; line-height:1.25; -webkit-print-color-adjust:exact; print-color-adjust:exact; padding:24px; }
  .enc-top{ display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:8px; }
  .enc-left{ line-height:1.2; margin-left:var(--enc-left-shift); }
  .enc-left .ea{ font-style:italic; font-size:var(--fz-ea); }
  .enc-left .bco{ font-size:var(--fz-bco); }
  .enc-right{ font-style:italic; white-space:nowrap; text-align:right; font-size:var(--fz-motto); }
  h2{ text-align:center; text-transform:uppercase; margin:0; font-size:var(--fz-h2); font-weight:700; margin-top:2mm; }
  h3{ font-size:12.5pt; margin:6mm 0 2mm; font-weight:700; text-transform:uppercase; }
  h4{ font-size:12pt;   margin:5mm 0 2mm; font-weight:700; text-transform:uppercase; }
  h5{ font-size:11.5pt; margin:3mm 0 2mm; font-weight:700; text-transform:uppercase; }
  .turno-fecha{ font-size:var(--fz-turno-fecha); margin:3mm 0 1mm; }
  .turno-personal{ font-size:var(--fz-turno-personal); margin:0 0 3mm; }
  .table{ width:100%; border-collapse:collapse; table-layout:fixed; }
  .table th,.table td{ border:.6pt solid var(--grid)!important; padding:2.5mm 2mm; vertical-align:top; word-wrap:break-word; }
  .table thead th{ background:var(--thead); font-weight:700; text-transform:uppercase; }
  .table-responsive{ overflow:visible !important; }
  .tbl-fallecidos{ table-layout:auto; }
  .tbl-fallecidos td:nth-child(7), .tbl-fallecidos th:nth-child(7){ width:auto; white-space:normal; word-break:break-word; }
  .ref-table{ table-layout:fixed; width:100%; margin-top:2mm; }
  .ref-table td{ text-align:center; font-weight:700; vertical-align:middle; }
  .ref-table col{ width:33.333%; }
  .ref-table td.ref-new{ background:#f8d7da !important; color:#000; }
  .ref-table td.ref-upd{ background:#fff3cd !important; color:#000; }
  .ref-table td.ref-res{ background:#d1e7dd !important; color:#000; }
  .ref-box{ display:block; padding:3mm 2mm; text-transform:uppercase; line-height:1.1; }
  @media print{
    body{ font-size:10pt; }
    .table{ table-layout:auto; }
    .table thead th{ background:#efefef !important; }
    .no-print{ display:none !important; }
  }
</style>
</head>
<body>

<div class="enc-top">
  <div class="enc-left">
    <div class="ea">Ejército Argentino</div>
    <div class="bco">Batallón de Comunicaciones 602</div>
  </div>
  <div class="enc-right">“Año de la Reconstrucción de la Nación Argentina”</div>
</div>

<h2>PARTE DE NOVEDADES DEL ARMA DE COMUNICACIONES</h2>

<p class="turno-fecha">
  <strong>Del dia:</strong> <?=h(date('d/m/Y', strtotime($desde)))?>
  <strong>al</strong> <?=h(date('d/m/Y', strtotime($hasta)))?>
</p>
<p class="turno-personal">
  <strong>OFICIAL DE TURNO:</strong> <?=h($enc['oficial_turno'])?>.<br>
  <strong>SUBOFICIAL DE TURNO:</strong> <?=h($enc['suboficial_turno'])?>.
</p>

<h3>PERSONAL – SEGÚN PARTE DEL CENOPE</h3>

<h3>PERSONAL INTERNADO</h3>
<?php
$headersIntern = ['Nro','Grado','Apellido y Nombre','Arma','Unidad','Prom','Fecha','Habitación','Hospital'];
$renderRowIntern = function($r){
  return "<tr>"
    ."<td>".h($r['nro'] ?? '')."</td>"
    ."<td>".h($r['grado'])."</td>"
    ."<td>".h(col($r,'apellidoNombre','apellido_nombre'))."</td>"
    ."<td>".h($r['arma'] ?? '')."</td>"
    ."<td>".h($r['unidad'] ?? '')."</td>"
    ."<td>".h($r['prom'] ?? '')."</td>"
    ."<td>".h($r['fecha'] ?? '')."</td>"
    ."<td>".h(col($r,'habitacion','habitación'))."</td>"
    ."<td>".h($r['hospital'] ?? '')."</td>"
  ."</tr>";
};
foreach (['OFICIALES','SUBOFICIALES','SOLDADOS VOLUNTARIOS'] as $cat){
  echo "<h5 class='mt-2'>".h($cat)."</h5>";
  render_table($headersIntern, $renderRowIntern, $piG[$cat] ?? []);
}
?>

<h3 class="mt-3">PERSONAL ALTA</h3>
<?php
$headersAlta = ['Nro','Grado','Apellido y Nombre','Arma','Unidad','Prom','Fecha','Hospital'];
foreach (['OFICIALES','SUBOFICIALES','SOLDADOS VOLUNTARIOS'] as $cat){
  $n=0;
  $renderRowAlta = function($r) use (&$n){
    $n++;
    return "<tr>"
      ."<td>".sprintf('%02d',$n)."</td>"
      ."<td>".h($r['grado'])."</td>"
      ."<td>".h(col($r,'apellidoNombre','apellido_nombre'))."</td>"
      ."<td>".h($r['arma'] ?? '')."</td>"
      ."<td>".h($r['unidad'] ?? '')."</td>"
      ."<td>".h($r['prom'] ?? '')."</td>"
      ."<td>".h($r['fecha'] ?? '')."</td>"
      ."<td>".h($r['hospital'] ?? '')."</td>"
    ."</tr>";
  };
  echo "<h5 class='mt-2'>".h($cat)."</h5>";
  render_table($headersAlta, $renderRowAlta, $paG[$cat] ?? []);
}
?>

<h3 class="mt-3">PERSONAL FALLECIDO</h3>
<?php
if (!$pf){
  echo "<div class='table-responsive'><table class='table table-sm table-bordered tbl-fallecidos'><thead><tr>"
     ."<th>Nro</th><th>Grado</th><th>Apellido y Nombre</th><th>Arma</th><th>Unidad/Destino</th><th>Fecha</th><th>Detalle</th>"
     ."</tr></thead><tbody><tr><td colspan='7' class='text-center text-muted'>SIN NOVEDAD</td></tr></tbody></table></div>";
} else {
  echo "<div class='table-responsive'><table class='table table-sm table-bordered tbl-fallecidos'><thead><tr>"
     ."<th>Nro</th><th>Grado</th><th>Apellido y Nombre</th><th>Arma</th><th>Unidad/Destino</th><th>Fecha</th><th>Detalle</th>"
     ."</tr></thead><tbody>";
  foreach ($pf as $r){
    echo "<tr>"
       ."<td>".h($r['nro'] ?? '')."</td>"
       ."<td>".h($r['grado'] ?? '')."</td>"
       ."<td>".h(col($r,'apellidoNombre','apellido_nombre'))."</td>"
       ."<td>".h($r['arma'] ?? '')."</td>"
       ."<td>".h($r['unidad'] ?? '')."</td>"
       ."<td>".h($r['fecha'] ?? '')."</td>"
       ."<td>".h($r['detalle'] ?? '')."</td>"
     ."</tr>";
  }
  echo "</tbody></table></div>";
}
?>

<!-- ===== Referencia ===== -->
<h4 class="mt-4">REFERENCIA</h4>
<table class="table table-sm table-bordered ref-table">
  <colgroup><col><col><col></colgroup>
  <tbody>
    <tr>
      <td class="ref-new"><span class="ref-box">NOVEDAD NUEVA</span></td>
      <td class="ref-upd"><span class="ref-box">NOVEDAD ACTUALIZADA</span></td>
      <td class="ref-res"><span class="ref-box">NOVEDAD RESUELTA</span></td>
    </tr>
  </tbody>
</table>

<!-- ===== Sistemas ===== -->
<h4 class="mt-3">SISTEMAS – SERVICIOS</h4>
<?php render_estado_tabla($estGrp[2] ?? [], ['SISTEMA','FECHA','NOVEDADES']); ?>

<h4 class="mt-3">SISTEMAS – ISP EDIFICIO LIBERTADOR</h4>
<?php render_estado_tabla($estGrp[3] ?? [], ['PRESTATARIA / VÍNCULO','FECHA','NOVEDADES']); ?>

<h4 class="mt-3">SITELPAR</h4>
<?php render_estado_tabla($estGrp[4] ?? [], ['SERVICIO','FECHA','NOVEDADES']); ?>

<h4 class="mt-3">SISTEMAS – DATA CENTER</h4>
<?php render_estado_tabla($estGrp[5] ?? [], ['NOMBRE','FECHA','NOVEDADES']); ?>

<h4 class="mt-3">SISTEMAS – SITM2</h4>
<?php render_estado_tabla($estGrp[6] ?? [], ['SISTEMA','FECHA','NOVEDADES']); ?>

<!-- ===== LTA (placeholder) ===== -->
<h3 class="mt-4">LTA</h3>
<?php
// AÚN NO hay backend para LTA; se deja placeholder para que aparezca la mesa.
render_table(['Detalle'], function($r){ return '<tr><td></td></tr>'; }, [], 'SIN NOVEDAD / Aún no cargado desde la UI');
?>

<!-- ===== Pendientes ===== -->
<h4 class="mt-4">PENDIENTES AL CIERRE</h4>
<?php
$headersPend = ['Inicio','Categoría','Título','Prioridad','Estado'];
$renderRowPend = function($p){
  return "<tr>"
    ."<td>".h(date('d/m/Y H:i', strtotime($p['fecha_inicio'])))."</td>"
    ."<td>".h($p['categoria'])."</td>"
    ."<td>".h($p['titulo'])."</td>"
    ."<td>".h($p['prioridad'])."</td>"
    ."<td>".h($p['estado'])."</td>"
  ."</tr>";
};
render_table($headersPend, $renderRowPend, $pendientes);
?>

<?php if ($showUi): ?>
<div class="no-print mt-3 d-flex gap-2">
  <button class="btn btn-outline-secondary" onclick="window.print()">Imprimir / Guardar PDF</button>
</div>
<?php
  // URL para auto-guardar silencioso
  $saveUrl = '?' . http_build_query([
    'desde'=>$desde, 'hasta'=>$hasta, 'titulo'=>$tituloHumano,
    'save'=>1, 'silent'=>1
  ]);
?>
<script>
  // Guarda una copia en servidor automáticamente, sin interrumpir al usuario
  (function(){
    fetch('<?= h($saveUrl) ?>', {credentials: 'same-origin'}).catch(()=>{});
  })();
</script>
<?php endif; ?>

</body>
</html>
<?php
/* ===== fin HTML ===== */
$html = ob_get_clean();

/* Si no pidieron PDF/guardar, devolvemos HTML */
if ($showUi) { echo $html; exit; }

/* ===== Rutas de salida ===== */
$base        = app_base_simple();
$rootFs      = realpath(__DIR__ . '/..');
$dirPartesFs = $rootFs . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'partes';
if (!is_dir($dirPartesFs)) { @mkdir($dirPartesFs, 0775, true); }

$fnBaseHuman = filename_safe($tituloHumano);
$pdfFs  = $dirPartesFs . DIRECTORY_SEPARATOR . $fnBaseHuman . '.pdf';
$htmlFs = $dirPartesFs . DIRECTORY_SEPARATOR . $fnBaseHuman . '.html';

$webPartes = rtrim($base, '/') . '/files/partes';
$pdfUrl    = $webPartes . '/' . rawurlencode($fnBaseHuman . '.pdf');

// Rutas **relativas** para DB
$fileRelPdf  = 'files/partes/' . $fnBaseHuman . '.pdf';
$fileRelHtml = 'files/partes/' . basename($htmlFs);

// chequeo permisos
$writable = is_dir($dirPartesFs) && is_writable($dirPartesFs);
error_log("Salida dir={$dirPartesFs}  writable=" . ($writable?'1':'0'));

/* ===== Motores PDF ===== */
function try_autoload_vendor(): void {
  $autoload = __DIR__ . '/../vendor/autoload.php';
  if (is_file($autoload)) { require_once $autoload; error_log('Autoload vendor OK'); }
  else { error_log('Autoload vendor NO encontrado'); }
}
function find_wkhtmltopdf(): ?string {
  $candidates = [
    'C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe',
    'C:\Program Files (x86)\wkhtmltopdf\bin\wkhtmltopdf.exe',
    '/usr/bin/wkhtmltopdf','/usr/local/bin/wkhtmltopdf',
  ];
  foreach ($candidates as $p) if (is_file($p) && is_executable($p)) return $p;
  $cmd = strtoupper(substr(PHP_OS,0,3))==='WIN' ? 'where wkhtmltopdf' : 'which wkhtmltopdf';
  @exec($cmd, $out, $code);
  if ($code===0 && !empty($out[0]) && is_file($out[0])) return $out[0];
  return null;
}
function save_pdf_with_wkhtml(string $html, string $pdfPath): bool {
  $wk = find_wkhtmltopdf(); if (!$wk) return false;
  $tmp = tempnam(sys_get_temp_dir(), 'parte_') . '.html';
  file_put_contents($tmp, $html);
  $cmd = escapeshellarg($wk) . ' --enable-local-file-access --encoding utf-8 '
       . escapeshellarg($tmp) . ' ' . escapeshellarg($pdfPath) . ' 2>&1';
  exec($cmd, $out, $code);
  @unlink($tmp);
  error_log("wkhtmltopdf code={$code}");
  return $code === 0 && is_file($pdfPath) && filesize($pdfPath)>0;
}
function save_pdf_with_dompdf(string $html, string $pdfPath): bool {
  try_autoload_vendor();
  if (!class_exists('\Dompdf\Dompdf')) { error_log('DOMPDF no está disponible'); return false; }
  $opts = new \Dompdf\Options();
  $opts->set('isRemoteEnabled', true);
  $opts->set('defaultFont', 'DejaVu Sans');
  $dompdf = new \Dompdf\Dompdf($opts);
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();
  $output = $dompdf->output();
  $ok = (bool)file_put_contents($pdfPath, $output);
  error_log("DOMPDF guardar={$ok}");
  return $ok;
}

/* ===== Generación ===== */
$okPdf = save_pdf_with_dompdf($html, $pdfFs) ?: save_pdf_with_wkhtml($html, $pdfFs);
error_log("okPdf=" . ($okPdf?'1':'0') . "  pdfFs={$pdfFs}");

/* Guardado silencioso (fetch desde la UI) */
if ($silent) {
  error_log("Guardado silencioso: okPdf={$okPdf}  desde={$desde}  hasta={$hasta}  titulo={$tituloHumano}");
  if ($okPdf) {
    // PDF OK → registro apuntando al PDF
    save_parte_record($pdo, $enc, $desde, $hasta, $tituloHumano, $fileRelPdf);
  } else {
    // Sin motor PDF → guardo HTML y registro igual
    file_put_contents($htmlFs, $html);
    save_parte_record($pdo, $enc, $desde, $hasta, $tituloHumano, $fileRelHtml);
  }
  http_response_code(204);
  exit;
}

/* Guardado explícito (?save=1) */
if ($wantSave) {
  if ($okPdf) {
    save_parte_record($pdo, $enc, $desde, $hasta, $tituloHumano, $fileRelPdf);
    echo "<!doctype html><meta charset='utf-8'><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "<div class='container py-4' style='max-width:720px'>";
    echo "<div class='alert alert-success'>PDF guardado en el servidor.</div>";
    echo "<p><strong>Archivo:</strong> ".h(basename($pdfFs))."</p>";
    echo "<p><a class='btn btn-primary' href='".h($pdfUrl)."' target='_blank'>Abrir PDF</a> ";
    echo "<a class='btn btn-outline-secondary' href='?".h(http_build_query(['desde'=>$desde,'hasta'=>$hasta,'titulo'=>$tituloHumano]))."'>Volver al Parte</a></p>";
    echo "</div>";
  } else {
    file_put_contents($htmlFs, $html);
    $htmlUrl = $webPartes . '/' . rawurlencode(basename($htmlFs));
    save_parte_record($pdo, $enc, $desde, $hasta, $tituloHumano, $fileRelHtml);
    echo "<!doctype html><meta charset='utf-8'><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "<div class='container py-4' style='max-width:720px'>";
    echo "<div class='alert alert-warning'>No se encontró un motor de PDF. Se guardó el HTML como respaldo.</div>";
    echo "<p><strong>Archivo:</strong> ".h(basename($htmlFs))."</p>";
    echo "<p><a class='btn btn-outline-primary' href='".h($htmlUrl)."' target='_blank'>Ver HTML guardado</a> ";
    echo "<a class='btn btn-outline-secondary' href='?".h(http_build_query(['desde'=>$desde,'hasta'=>$hasta,'titulo'=>$tituloHumano]))."'>Volver al Parte</a></p>";
    echo "</div>";
  }
  exit;
}

/* Visualización/descarga (?pdf=1) */
if ($wantPdf) {
  if ($okPdf) {
    save_parte_record($pdo, $enc, $desde, $hasta, $tituloHumano, $fileRelPdf);
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="'.basename($pdfFs).'"');
    header('Content-Length: ' . filesize($pdfFs));
    readfile($pdfFs);
  } else {
    header('Content-Type: text/html; charset=utf-8');
    echo "<div class='container py-4' style='max-width:720px'><div class='alert alert-warning'>No se pudo generar el PDF en el servidor. Usá el botón “Imprimir / Guardar PDF”.</div></div>";
    echo $html;
  }
  exit;
}
