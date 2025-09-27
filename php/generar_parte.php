<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/db.php';

function out($arr, int $code=200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function disable_proxy_env(): void {
  foreach (['http_proxy','https_proxy','all_proxy','HTTP_PROXY','HTTPS_PROXY','ALL_PROXY','NO_PROXY','no_proxy'] as $v) {
    if (getenv($v) !== false) { putenv("$v="); }
  }
}
function php_base_url(): string {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
  $proto = $https ? 'https://' : 'http://';
  $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $dir   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/php'), '/\\');
  return $proto.$host.$dir;
}
function site_root(): string { return realpath(__DIR__ . '/..') ?: dirname(__DIR__); }
function ensure_dir(string $path): void { if (!is_dir($path)) @mkdir($path, 0775, true); }

function curl_import_local(string $url, string $fileField, string $tmpPath, string $filename, array $extraForm){
  disable_proxy_env();
  $ch = curl_init();
  $cfile = new CURLFile($tmpPath, mime_content_type($tmpPath) ?: 'application/octet-stream', $filename);
  $post = array_merge([$fileField => $cfile], $extraForm);
  curl_setopt_array($ch, [
    CURLOPT_URL=>$url, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$post,
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>45,
    CURLOPT_PROXY=>'', CURLOPT_NOPROXY=>'*',
  ]);
  $resp = curl_exec($ch); $err  = curl_error($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if ($resp === false) throw new Exception("cURL error: $err");
  if ($code >= 400) throw new Exception("Importador devolvió HTTP $code: $resp");
  $json = json_decode($resp, true);
  if (!is_array($json)) throw new Exception("Respuesta no JSON del importador: $resp");
  if (isset($json['ok']) && $json['ok']===false) throw new Exception($json['error'] ?? 'Error importador');
  return $json;
}
function month_es(int $m): string {
  static $MES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  return $MES[$m-1] ?? '';
}
function rango_nombre_pdf(string $desde, string $hasta): string {
  $d1 = new DateTime($desde); $d2 = new DateTime($hasta);
  $dia1 = (int)$d1->format('j'); $mes1 = month_es((int)$d1->format('n')); $anio1 = $d1->format('Y');
  $dia2 = (int)$d2->format('j'); $mes2 = month_es((int)$d2->format('n')); $anio2 = $d2->format('Y');
  if ($mes1 === $mes2 && $anio1 === $anio2) return "Parte de Novedades del Arma de Comunicaciones del $dia1 al $dia2 de $mes1 de $anio1";
  return "Parte de Novedades del Arma de Comunicaciones del $dia1 de $mes1 de $anio1 al $dia2 de $mes2 de $anio2";
}
function to_filename(string $s): string {
  $s = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
  $s = preg_replace('/[^A-Za-z0-9._ -]+/','',$s);
  $s = preg_replace('/\s+/','_',$s);
  return trim($s,'_');
}

try{
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') out(['ok'=>false,'error'=>'POST requerido'],405);

  $desde      = $_POST['desde']      ?? '';
  $hasta      = $_POST['hasta']      ?? '';
  $oficial    = trim($_POST['oficial']    ?? '');
  $suboficial = trim($_POST['suboficial'] ?? '');

  if (!$desde || !$hasta || !$oficial) out(['ok'=>false,'error'=>'Faltan campos: desde/hasta/oficial'],400);
  if (empty($_FILES['lta']['tmp_name'])    || !is_uploaded_file($_FILES['lta']['tmp_name']))    out(['ok'=>false,'error'=>'Falta archivo LTA (DOCX o PDF)'],400);
  if (empty($_FILES['cenope']['tmp_name']) || !is_uploaded_file($_FILES['cenope']['tmp_name'])) out(['ok'=>false,'error'=>'Falta archivo CENOPE (PDF)'],400);

  $phpBase = php_base_url();
  $jLta = curl_import_local($phpBase.'/import_lta_docx.php', 'file',  $_FILES['lta']['tmp_name'],    $_FILES['lta']['name'],    ['dry'=>'1']);
  $jCe  = curl_import_local($phpBase.'/import_cenope.php',   'pdf',   $_FILES['cenope']['tmp_name'], $_FILES['cenope']['name'], ['dry'=>'1']);

  $redise    = $jLta['redise']    ?? [];
  $internado = $jCe['internado']  ?? [];
  $alta      = $jCe['alta']       ?? [];
  $fallecido = $jCe['fallecido']  ?? [];

  $MES = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
  $turno = sprintf('%02d%s%02d', (int)date('d'), $MES[(int)date('n')-1], (int)date('y'));
  $redise = array_map(function($r) use($turno){ $r['fecha']=$turno; $r['estado']=$r['estado']??'ACTUALIZADA'; return $r; }, $redise);

  $textoCCCParts = [];
  foreach ($redise as $r){
    $nodo = strtoupper(trim($r['nodo'] ?? '')); if (!$nodo) continue;
    if (strpos($nodo,'CCIG')===0){ $parts = preg_split('/\s+/', $nodo); $head  = array_shift($parts); $nodo  = $head."\n".implode("\n", $parts); }
    $desdeNodo = strtoupper(trim($r['desde'] ?? ''));
    $nov       = trim(preg_replace('/\s{2,}/',' ', (string)($r['novedad'] ?? ''))); if (!$nov) continue;
    $svc = strtoupper(trim($r['servicio'] ?? '')); if ($svc==='VHF/HF') $svc = 'HF/VHF';
    $tic = trim((string)($r['ticket'] ?? ''));
    $tail = ($svc ? ' '.$svc : ' ---') . ($tic ? ' '.$tic : ' ---');
    $textoCCCParts[] = implode("\n", array_values(array_filter([$nodo, $desdeNodo ?: null, $nov, $turno.$tail])));
  }
  $textoCCC = implode("\n", $textoCCCParts);

  ob_start(); ?>
  <!doctype html><html lang="es"><head>
    <meta charset="utf-8"><title>Parte del Arma – <?=h($turno)?></title>
    <style>
      body{font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size:12px; color:#111}
      h1{font-size:18px;margin:0 0 6px}
      h2{font-size:15px;margin:16px 0 6px;border-bottom:1px solid #ddd;padding-bottom:4px}
      table{border-collapse:collapse;width:100%}
      th,td{border:1px solid #999;padding:5px;vertical-align:top}
      .mono{white-space:pre-wrap;font-family: Consolas, "DejaVu Sans Mono", monospace}
      .muted{color:#666}
    </style>
  </head><body>
    <h1>PARTE DEL ARMA – Batallón de Comunicaciones 602</h1>
    <div class="muted">Desde: <?=h($desde)?> &nbsp;|&nbsp; Hasta: <?=h($hasta)?></div>
    <div class="muted">Oficial de turno: <?=h($oficial)?> &nbsp;|&nbsp; Suboficial de turno: <?=h($suboficial)?></div>

    <h2>SISTEMA RADIOELÉCTRICO – NODOS REDISE (formato CCC)</h2>
    <div class="mono"><?=nl2br(h($textoCCC))?></div>

    <h2>REDISE (Tabla Resumen)</h2>
    <table>
      <thead><tr><th>Nodo</th><th>Desde</th><th>Novedad</th><th>Fecha</th><th>Servicio</th><th>Ticket</th></tr></thead>
      <tbody>
        <?php foreach($redise as $r): ?>
          <tr>
            <td><?=h($r['nodo']??'')?></td>
            <td><?=h($r['desde']??'')?></td>
            <td><?=h($r['novedad']??'')?></td>
            <td><?=h($r['fecha']??$turno)?></td>
            <td><?=h($r['servicio']??'')?></td>
            <td><?=h($r['ticket']??'')?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h2>CENOPE</h2>
    <div class="muted">Internados: <?=count($internado)?> &nbsp;|&nbsp; Altas: <?=count($alta)?> &nbsp;|&nbsp; Fallecidos: <?=count($fallecido)?></div>
    <?php
      $mk = function($title, $rows){
        echo "<h3>".h($title)."</h3>";
        if (!$rows){ echo "<div class='muted'>SIN NOVEDAD</div>"; return; }
        echo "<table><thead><tr>
          <th>Nro</th><th>Grado</th><th>Apellido y Nombre</th><th>Arma</th>
          <th>Unidad/Destino</th><th>Fecha</th><th>Habitación</th><th>Hospital</th>
        </tr></thead><tbody>";
        foreach($rows as $r){
          echo "<tr><td>".h($r['Nro']??'')."</td>
            <td>".h($r['Grado']??'')."</td>
            <td>".h($r['Apellido y Nombre']??'')."</td>
            <td>".h($r['Arma']??'')."</td>
            <td>".h($r['Unidad']??'')."</td>
            <td>".h($r['Fecha']??'')."</td>
            <td>".h($r['Habitación']??'')."</td>
            <td>".h($r['Hospital']??'')."</td></tr>";
        }
        echo "</tbody></table>";
      };
      $mk('Internados', $internado);
      $mk('Altas', $alta);
      $mk('Fallecidos', $fallecido);
    ?>
  </body></html>
  <?php
  $html = ob_get_clean();

  $root = site_root();
  $dir  = $root . '/files/partes';
  ensure_dir($dir);

  $niceTitle = rango_nombre_pdf($desde, $hasta);
  $baseName  = to_filename($niceTitle) . '_' . date('Ymd_His');

  $htmlFile = $dir . "/{$baseName}.html";
  $pdfFile  = $dir . "/{$baseName}.pdf";
  file_put_contents($htmlFile, $html);

  $pdfPathFs  = null;
  $tcpdfOk = false;
  @include_once __DIR__.'/../vendor/autoload.php';
  if (class_exists('TCPDF')) $tcpdfOk = true;
  if (!$tcpdfOk && file_exists(__DIR__.'/tcpdf_min/tcpdf.php')) { @include_once __DIR__.'/tcpdf_min/tcpdf.php'; $tcpdfOk = class_exists('TCPDF'); }
  if ($tcpdfOk) {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
    $pdf->SetCreator('ParteOficialTurno'); $pdf->SetAuthor($oficial); $pdf->SetTitle($niceTitle);
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($pdfFile, 'F');
    $pdfPathFs = $pdfFile;
  }

  $pdo = db();
  $pdo->beginTransaction();

  // actualizar si existe para ese rango, si no insertar
  $stChk = $pdo->prepare("SELECT id FROM parte_arma WHERE desde=:d AND hasta=:h");
  $stChk->execute([':d'=>$desde, ':h'=>$hasta]);
  $existe = $stChk->fetchColumn();

  if ($existe) {
    $parteId = (int)$existe;
    $pdo->prepare("UPDATE parte_arma SET oficial=:o, suboficial=:s, turno=:t, html_path=:hp, pdf_path=:pp WHERE id=:id")
        ->execute([':o'=>$oficial, ':s'=>$suboficial, ':t'=>$turno, ':hp'=>$htmlFile, ':pp'=>$pdfPathFs ?? '', ':id'=>$parteId]);
    $pdo->prepare("DELETE FROM parte_arma_data WHERE parte_id=:id")->execute([':id'=>$parteId]);
    $pdo->prepare("INSERT INTO parte_arma_data (parte_id,cenope_json,redise_json,texto_ccc) VALUES (:id,:c,:r,:x)")
        ->execute([':id'=>$parteId, ':c'=>json_encode($jCe, JSON_UNESCAPED_UNICODE), ':r'=>json_encode($redise, JSON_UNESCAPED_UNICODE), ':x'=>$textoCCC]);
  } else {
    $pdo->prepare("INSERT INTO parte_arma (desde,hasta,oficial,suboficial,turno,html_path,pdf_path) VALUES (:d,:h,:o,:s,:t,:hp,:pp)")
        ->execute([':d'=>$desde, ':h'=>$hasta, ':o'=>$oficial, ':s'=>$suboficial, ':t'=>$turno, ':hp'=>$htmlFile, ':pp'=>$pdfPathFs ?? '']);
    $parteId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO parte_arma_data (parte_id,cenope_json,redise_json,texto_ccc) VALUES (:id,:c,:r,:x)")
        ->execute([':id'=>$parteId, ':c'=>json_encode($jCe, JSON_UNESCAPED_UNICODE), ':r'=>json_encode($redise, JSON_UNESCAPED_UNICODE), ':x'=>$textoCCC]);
  }
  $pdo->commit();

  $basePublic = rtrim((function(){
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $proto = $https ? 'https://' : 'http://';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $rootWeb = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/php')), '/\\');
    return $proto.$host.$rootWeb;
  })(), '/');

  $htmlUrl = $basePublic.'/files/partes/'.basename($htmlFile);
  $pdfUrl  = $pdfPathFs ? $basePublic.'/files/partes/'.basename($pdfFile) : null;

  out(['ok'=>true,'id'=>$parteId,'turno'=>$turno,'html'=>$htmlUrl,'pdf'=>$pdfUrl,'title'=>$niceTitle]);

}catch(Throwable $e){
  out(['ok'=>false,'error'=>$e->getMessage()],500);
}
