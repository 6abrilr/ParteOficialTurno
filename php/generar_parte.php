<?php
// php/generar_parte.php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/db.php';

function out($arr, int $code=200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

/* ---------- Helpers ---------- */

// URL base del directorio /php (donde está este script)
function php_base_url(): string {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
  $proto = $https ? 'https://' : 'http://';
  $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $dir   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/php'), '/\\'); // ej: /php
  return $proto.$host.$dir;
}

// raíz del proyecto (un nivel arriba de /php)
function site_root(): string {
  return realpath(__DIR__ . '/..') ?: dirname(__DIR__);
}

function ensure_dir(string $path): void {
  if (!is_dir($path)) @mkdir($path, 0775, true);
}

function curl_import_local(string $url, string $fileField, string $tmpPath, string $filename, array $extraForm){
  $ch = curl_init();
  $cfile = new CURLFile($tmpPath, mime_content_type($tmpPath) ?: 'application/octet-stream', $filename);
  $post = array_merge([$fileField => $cfile], $extraForm);
  curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $post,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 45,
  ]);
  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($resp === false) throw new Exception("cURL error: $err");
  if ($code >= 400) throw new Exception("Importador devolvió HTTP $code: $resp");
  $json = json_decode($resp, true);
  if (!is_array($json)) throw new Exception("Respuesta no JSON del importador: $resp");
  if (isset($json['ok']) && $json['ok'] === false) throw new Exception($json['error'] ?? 'Error importador');
  return $json;
}

/* ---------- Main ---------- */

try{
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') out(['ok'=>false,'error'=>'POST requerido'],405);

  $desde      = $_POST['desde']      ?? '';
  $hasta      = $_POST['hasta']      ?? '';
  $oficial    = trim($_POST['oficial']    ?? '');
  $suboficial = trim($_POST['suboficial'] ?? '');

  if (!$desde || !$hasta || !$oficial) out(['ok'=>false,'error'=>'Faltan campos: desde/hasta/oficial'],400);
  if (empty($_FILES['lta']['tmp_name'])    || !is_uploaded_file($_FILES['lta']['tmp_name']))    out(['ok'=>false,'error'=>'Falta archivo LTA (DOCX o PDF)'],400);
  if (empty($_FILES['cenope']['tmp_name']) || !is_uploaded_file($_FILES['cenope']['tmp_name'])) out(['ok'=>false,'error'=>'Falta archivo CENOPE (PDF)'],400);

  // 1) Importar usando tus endpoints locales (dry=1)
  $phpBase = php_base_url();                 // ej: http(s)://host/php
  $jLta = curl_import_local($phpBase.'/import_lta_docx.php', 'file',  $_FILES['lta']['tmp_name'],    $_FILES['lta']['name'],    ['dry'=>'1']);
  $jCe  = curl_import_local($phpBase.'/import_cenope.php',   'pdf',   $_FILES['cenope']['tmp_name'], $_FILES['cenope']['name'], ['dry'=>'1']);

  $redise    = $jLta['redise']    ?? [];
  $internado = $jCe['internado']  ?? [];
  $alta      = $jCe['alta']       ?? [];
  $fallecido = $jCe['fallecido']  ?? [];

  // 2) Forzar fecha de turno y preparar texto CCC
  $MES = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
  $turno = sprintf('%02d%s%02d', (int)date('d'), $MES[(int)date('n')-1], (int)date('y'));

  $redise = array_map(function($r) use($turno){
    $r['fecha']  = $turno;
    $r['estado'] = $r['estado'] ?? 'ACTUALIZADA';
    return $r;
  }, $redise);

  // Generar texto CCC (igual criterio que front)
  $textoCCCParts = [];
  foreach ($redise as $r){
    $nodo = strtoupper(trim($r['nodo'] ?? ''));
    if (!$nodo) continue;
    if (strpos($nodo,'CCIG')===0){
      $parts = preg_split('/\s+/', $nodo);
      $head  = array_shift($parts);
      $nodo  = $head."\n".implode("\n", $parts);
    }
    $desdeNodo = strtoupper(trim($r['desde'] ?? ''));
    $nov       = trim(preg_replace('/\s{2,}/',' ', (string)($r['novedad'] ?? '')));
    if (!$nov) continue;
    $svc = strtoupper(trim($r['servicio'] ?? ''));
    if ($svc==='VHF/HF') $svc = 'HF/VHF';
    $tic = trim((string)($r['ticket'] ?? ''));
    $tail = ($svc ? ' '.$svc : ' ---') . ($tic ? ' '.$tic : ' ---');
    $bloque = implode("\n", array_values(array_filter([$nodo, $desdeNodo ?: null, $nov, $turno.$tail])));
    $textoCCCParts[] = $bloque;
  }
  $textoCCC = implode("\n", $textoCCCParts);

  // 3) HTML del Parte
  ob_start(); ?>
  <!doctype html><html lang="es"><head>
    <meta charset="utf-8"><title>Parte del Arma – <?=$turno?></title>
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
    <div class="muted">Desde: <?=htmlspecialchars($desde)?> &nbsp;|&nbsp; Hasta: <?=htmlspecialchars($hasta)?></div>
    <div class="muted">Oficial de turno: <?=htmlspecialchars($oficial)?> &nbsp;|&nbsp; Suboficial de turno: <?=htmlspecialchars($suboficial)?></div>

    <h2>SISTEMA RADIOELÉCTRICO – NODOS REDISE (formato CCC)</h2>
    <div class="mono"><?=nl2br(htmlspecialchars($textoCCC))?></div>

    <h2>REDISE (Tabla Resumen)</h2>
    <table>
      <thead><tr><th>Nodo</th><th>Desde</th><th>Novedad</th><th>Fecha</th><th>Servicio</th><th>Ticket</th></tr></thead>
      <tbody>
        <?php foreach($redise as $r): ?>
          <tr>
            <td><?=htmlspecialchars($r['nodo']??'')?></td>
            <td><?=htmlspecialchars($r['desde']??'')?></td>
            <td><?=htmlspecialchars($r['novedad']??'')?></td>
            <td><?=htmlspecialchars($r['fecha']??$turno)?></td>
            <td><?=htmlspecialchars($r['servicio']??'')?></td>
            <td><?=htmlspecialchars($r['ticket']??'')?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h2>CENOPE</h2>
    <div class="muted">Internados: <?=count($internado)?> &nbsp;|&nbsp; Altas: <?=count($alta)?> &nbsp;|&nbsp; Fallecidos: <?=count($fallecido)?></div>
    <?php
      $mk = function($title, $rows){
        echo "<h3>".htmlspecialchars($title)."</h3>";
        if (!$rows){ echo "<div class='muted'>SIN NOVEDAD</div>"; return; }
        echo "<table><thead><tr>
          <th>Nro</th><th>Grado</th><th>Apellido y Nombre</th><th>Arma</th>
          <th>Unidad/Destino</th><th>Fecha</th><th>Habitación</th><th>Hospital</th>
        </tr></thead><tbody>";
        foreach($rows as $r){
          echo "<tr><td>".htmlspecialchars($r['Nro']??'')."</td>
            <td>".htmlspecialchars($r['Grado']??'')."</td>
            <td>".htmlspecialchars($r['Apellido y Nombre']??'')."</td>
            <td>".htmlspecialchars($r['Arma']??'')."</td>
            <td>".htmlspecialchars($r['Unidad']??'')."</td>
            <td>".htmlspecialchars($r['Fecha']??'')."</td>
            <td>".htmlspecialchars($r['Habitación']??'')."</td>
            <td>".htmlspecialchars($r['Hospital']??'')."</td></tr>";
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

  // 4) Guardar HTML y PDF
  $root = site_root();
  $dir  = $root . '/files/partes';
  ensure_dir($dir);

  $slug = date('Ymd_His');
  $htmlFile = $dir."/parte_{$slug}.html";
  $pdfFile  = $dir."/parte_{$slug}.pdf";
  file_put_contents($htmlFile, $html);

  $pdfUrl  = null;
  // TCPDF por composer o carpeta local
  $tcpdfOk = false;
  @include_once __DIR__.'/../vendor/autoload.php';
  if (class_exists('TCPDF')) $tcpdfOk = true;
  if (!$tcpdfOk && file_exists(__DIR__.'/tcpdf_min/tcpdf.php')) { @include_once __DIR__.'/tcpdf_min/tcpdf.php'; $tcpdfOk = class_exists('TCPDF'); }

  if ($tcpdfOk) {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
    $pdf->SetCreator('ParteOficialTurno'); $pdf->SetAuthor($oficial); $pdf->SetTitle('Parte del Arma '.$turno);
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($pdfFile, 'F');
    $pdfUrl = $pdfFile;
  }

  // 5) Persistir en DB
  $pdo = db();
  $st = $pdo->prepare("INSERT INTO parte_arma (desde,hasta,oficial,suboficial,turno,html_path,pdf_path)
                       VALUES (:d,:h,:o,:s,:t,:hp,:pp)");
  $st->execute([
    ':d'=>$desde, ':h'=>$hasta, ':o'=>$oficial, ':s'=>$suboficial, ':t'=>$turno,
    ':hp'=>$htmlFile, ':pp'=>$pdfUrl ?? ''
  ]);
  $parteId = (int)$pdo->lastInsertId();

  $st2 = $pdo->prepare("INSERT INTO parte_arma_data (parte_id,cenope_json,redise_json,texto_ccc)
                        VALUES (:id,:c,:r,:x)");
  $st2->execute([
    ':id'=>$parteId,
    ':c'=>json_encode($jCe, JSON_UNESCAPED_UNICODE),
    ':r'=>json_encode($redise, JSON_UNESCAPED_UNICODE),
    ':x'=>$textoCCC
  ]);

  // URLs públicas
  $basePublic = rtrim((function(){
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $proto = $https ? 'https://' : 'http://';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $rootWeb = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/php')), '/\\'); // sube de /php a raíz del sitio
    return $proto.$host.$rootWeb;
  })(), '/');

  $htmlUrl = $basePublic.'/files/partes/'.basename($htmlFile);
  $pdfUrl  = $pdfUrl ? $basePublic.'/files/partes/'.basename($pdfFile) : null;

  out(['ok'=>true,'id'=>$parteId,'turno'=>$turno,'html'=>$htmlUrl,'pdf'=>$pdfUrl]);

}catch(Throwable $e){
  out(['ok'=>false,'error'=>$e->getMessage()],500);
}
