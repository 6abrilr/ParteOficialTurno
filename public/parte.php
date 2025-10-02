<?php
// public/parte.php
require_once __DIR__ . '/../php/db.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
if (!$desde || !$hasta) { http_response_code(400); die('Faltan parámetros desde/hasta'); }

// PDO
$pdo = db();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Encabezado (toma el último guardado; si no, usa params)
$enc = $pdo->query("SELECT * FROM parte_encabezado ORDER BY id DESC LIMIT 1")->fetch() ?: [
  'oficial_turno'    => '',
  'suboficial_turno' => '',
  'fecha_desde'      => $desde,
  'fecha_hasta'      => $hasta,
];

// (queda por si hace falta)
$sqlEvt = "SELECT e.*, n.titulo, n.descripcion, n.servicio, n.ticket, n.fecha_inicio,
                  n.categoria_id, c.nombre AS categoria
           FROM novedad_evento e
           JOIN novedad n   ON n.id = e.novedad_id
           JOIN categoria c ON c.id = n.categoria_id
           WHERE e.creado_en BETWEEN ? AND ?
           ORDER BY e.creado_en ASC, n.prioridad DESC";
$st = $pdo->prepare($sqlEvt);
$st->execute([$desde, $hasta]);
$eventos = $st->fetchAll();

// Pendientes al cierre
$sqlPend = "SELECT n.*, c.nombre AS categoria
            FROM novedad n
            JOIN categoria c ON c.id = n.categoria_id
            WHERE n.estado <> 'RESUELTO'
            ORDER BY n.prioridad DESC, n.fecha_inicio DESC";
$pendientes = $pdo->query($sqlPend)->fetchAll();

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function col($r, $a, $b=null) { return isset($r[$a]) ? $r[$a] : ($b && isset($r[$b]) ? $r[$b] : null); }

$ordenGrado = "FIELD(grado,'TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST','SM','SP','SA','SI','SG','CI','CB','VP','VS','SV','SOLD')";
$pi = $pdo->query("SELECT * FROM personal_internado ORDER BY categoria, $ordenGrado, apellido_nombre, apellidoNombre")->fetchAll();
$pa = $pdo->query("SELECT * FROM personal_alta      ORDER BY categoria, $ordenGrado, apellido_nombre, apellidoNombre")->fetchAll();
$pf = $pdo->query("SELECT * FROM personal_fallecido ORDER BY id DESC")->fetchAll();

function groupByKey(array $rows, string $key): array { $g=[]; foreach($rows as $r){ $g[$r[$key]][]=$r; } return $g; }
$piG = groupByKey($pi, 'categoria');
$paG = groupByKey($pa, 'categoria');

// Estados de Sistemas
$estados = $pdo->query("SELECT * FROM sistema_estado ORDER BY categoria_id, nombre")->fetchAll();
$estGrp  = [];
foreach ($estados as $r) { $estGrp[$r['categoria_id']][] = $r; }

/** Render genérico de tabla con fallback SIN NOVEDAD */
function render_table(array $headers, callable $rowRenderer, array $rows, string $emptyLabel = 'SIN NOVEDAD'){
  echo "<div class='table-responsive'><table class='table table-sm table-bordered'><thead><tr>";
  foreach($headers as $h) echo "<th>".h($h)."</th>";
  echo "</tr></thead><tbody>";
  if (!$rows) {
    echo "<tr><td colspan='".count($headers)."' class='text-center text-muted'>".h($emptyLabel)."</td></tr>";
  } else {
    foreach ($rows as $r) { echo $rowRenderer($r); }
  }
  echo "</tbody></table></div>";
}

/** Render Estados de sistemas */
function render_estado_tabla(array $rows, array $cols){
  $headers = $cols;
  $rowRenderer = function($r){
    $nov = ($r['estado']==='EN LINEA' && empty($r['novedad'])) ? 'EN LÍNEA' : ($r['novedad'] ?: $r['estado']);
    $fecha = $r['actualizado_en'] ? date('d/m/Y', strtotime($r['actualizado_en'])) : '';
    $nov   = $nov . ($r['ticket'] ? " ({$r['ticket']})" : "");
    return "<tr><td>".h($r['nombre'])."</td><td>".h($fecha)."</td><td>".h($nov)."</td></tr>";
  };
  render_table($headers, $rowRenderer, $rows);
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Parte de Novedades – B Com 602</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" type="image/png" href="img/escudo602.png">
<link rel="shortcut icon" href="img/escudo602.png">
<style>
  /* ====== PARTE – Estilo de impresión ====== */
  @page { size: A4; margin: 18mm 16mm; }

  :root{
    --fg: #000;
    --grid: #000;
    --thead: #f2f2f2;

    /* TAMAÑOS AJUSTABLES */
    --fz-ea:     16pt;   /* “Ejército Argentino” (izq.) */
    --fz-bco:    14pt;   /* “Batallón de Comunicaciones 602” (izq.) */
    --fz-motto:  12pt;   /* “Año de la Reconstrucción…” (der.) */
    --fz-h2:     13pt;   /* Título principal */
    --fz-turno-fecha:    12pt;
    --fz-turno-personal: 12pt;

    /* Desplazamiento del bloque izquierdo del encabezado (mover a la derecha) */
    --enc-left-shift: 10mm; /* ajustá este valor */
  }

  html, body{ background:#fff !important; }

  body{
    margin:0;
    color:var(--fg);
    font-family:"Times New Roman", Times, serif;
    font-size:12pt;
    line-height:1.25;
    -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
    padding:24px;
  }

  /* ===== Encabezado PDF-like (izq/der) ===== */
  .enc-top{
    display:flex; align-items:flex-start; justify-content:space-between;
    margin-bottom:8px;
  }
  .enc-left{ line-height:1.2; margin-left:var(--enc-left-shift); }
  .enc-left .ea{ font-style:italic; font-size:var(--fz-ea); }
  .enc-left .bco{ font-style:normal; font-size:var(--fz-bco); }
  .enc-right{ font-style:italic; white-space:nowrap; text-align:right; font-size:var(--fz-motto); }

  h2{ text-align:center; text-transform:uppercase; margin:0; font-size:var(--fz-h2); font-weight:700; margin-top:2mm; }

  h3{ font-size:12.5pt; margin:6mm 0 2mm; font-weight:700; text-transform:uppercase; }
  h4{ font-size:12pt; margin:5mm 0 2mm; font-weight:700; text-transform:uppercase; }
  h5{ font-size:11.5pt; margin:3mm 0 2mm; font-weight:700; text-transform:uppercase; }

  .turno-fecha{ font-size:var(--fz-turno-fecha); margin:3mm 0 1mm; text-align:left; }
  .turno-personal{ font-size:var(--fz-turno-personal); margin:0 0 3mm; text-align:left; }

  /* Tablas base */
  .table{ width:100%; border-collapse:collapse; table-layout:fixed; }
  .table th, .table td{
    border:.6pt solid var(--grid) !important;
    padding:2.5mm 2mm;
    vertical-align:top;
    word-wrap:break-word;
  }
  .table thead th{
    background:var(--thead);
    font-weight:700;
    text-transform:uppercase;
  }
  /* Anchos típicos globales */
  .table tbody td:nth-child(1),
  .table tbody td:nth-child(2){ text-align:center; white-space:nowrap; width:12mm; }
  .table tbody td:nth-child(7),
  .table tbody td:nth-child(8){ white-space:nowrap; width:22mm; }

  /* ===== FALLECIDOS: columna Detalle fluida ===== */
  .tbl-fallecidos{ table-layout:auto; }
  .tbl-fallecidos th, .tbl-fallecidos td{ overflow:visible; vertical-align:top; }
  .tbl-fallecidos td:nth-child(7), .tbl-fallecidos th:nth-child(7){
    width:auto; white-space:normal; word-wrap:break-word; word-break:break-word;
  }
  .tbl-fallecidos td:nth-child(1){ width:10mm; white-space:nowrap; text-align:center; }
  .tbl-fallecidos td:nth-child(2){ width:14mm; white-space:nowrap; text-align:center; }
  .tbl-fallecidos td:nth-child(4),
  .tbl-fallecidos td:nth-child(5),
  .tbl-fallecidos td:nth-child(6){ width:22mm; white-space:nowrap; }

  /* ===== REFERENCIA – 3 celdas coloreadas ===== */
  .ref-table{ table-layout:fixed; width:100%; margin-top:2mm; }
  .ref-table td{ text-align:center; font-weight:700; vertical-align:middle; }
  .ref-table col{ width:33.333%; }
  .ref-table td.ref-new{ background:#f8d7da !important; color:#000; }
  .ref-table td.ref-upd{ background:#fff3cd !important; color:#000; }
  .ref-table td.ref-res{ background:#d1e7dd !important; color:#000; }
  .ref-box{ display:block; padding:3mm 2mm; text-transform:uppercase; line-height:1.1; }

  /* Afinado especial para IMPRESIÓN para que no “se rompa” el responsive */
  @media print{
    body{ font-size:10pt; }                 /* un poco más chico para entrar en A4 */
    .table{ table-layout:auto; }            /* deja que las columnas respiren */
    .table thead th{ background:#efefef !important; }
    .table-responsive{ overflow:visible !important; }
    .ref-table td{ -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  }
</style>
</head>
<body>

<!-- Encabezado PDF-like -->
<div class="enc-top">
  <div class="enc-left">
    <div class="ea">Ejército Argentino</div>
    <div class="bco">Batallón de Comunicaciones 602</div>
  </div>
  <div class="enc-right">“Año de la Reconstrucción de la Nación Argentina”</div>
</div>

<h2>PARTE DE NOVEDADES DEL ARMA DE COMUNICACIONES</h2>

<p class="turno-fecha">
  <strong>Del día:</strong> <?=h(date('d/m/Y', strtotime($desde)))?>
  <strong>al</strong> <?=h(date('d/m/Y', strtotime($hasta)))?>
</p>
<p class="turno-personal">
  <strong>OFICIAL DE TURNO:</strong> <?=h($enc['oficial_turno'])?>.<br>
  <strong>SUBOFICIAL DE TURNO:</strong> <?=h($enc['suboficial_turno'])?>.
</p>

<h3>PERSONAL – SEGÚN PARTE DEL CENOPE</h3>

<!-- INTERNADOS -->
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

<!-- ALTAS -->
<h3 class="mt-3">PERSONAL ALTA</h3>
<?php
$headersAlta = ['Nro','Grado','Apellido y Nombre','Arma','Unidad','Prom','Fecha','Hospital'];
foreach (['OFICIALES','SUBOFICIALES','SOLDADOS VOLUNTARIOS'] as $cat){
  $n = 0;
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

<!-- FALLECIDOS -->
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

<!-- REFERENCIA -->
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

<!-- SISTEMAS -->
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

<!-- PENDIENTES -->
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

<div class="no-print mt-3">
  <button class="btn btn-outline-secondary" onclick="window.print()">Imprimir / Guardar PDF</button>
</div>

</body>
</html>
