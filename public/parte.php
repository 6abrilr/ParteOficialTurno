<?php
// public/parte.php
require_once __DIR__ . '/../php/db.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
if (!$desde || !$hasta) { http_response_code(400); die('Faltan parámetros desde/hasta'); }

$pdo = pdo();

// ===== Encabezado (último guardado) =====
$enc = $pdo->query("SELECT * FROM parte_encabezado ORDER BY id DESC LIMIT 1")->fetch() ?: [
  'oficial_turno'   => '',
  'suboficial_turno'=> '',
  'fecha_desde'     => $desde,
  'fecha_hasta'     => $hasta,
];

// ===== Eventos de novedades dentro del rango =====
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

// ===== Pendientes al cierre =====
$sqlPend = "SELECT n.*, c.nombre AS categoria
            FROM novedad n
            JOIN categoria c ON c.id = n.categoria_id
            WHERE n.estado <> 'RESUELTO'
            ORDER BY n.prioridad DESC, n.fecha_inicio DESC";
$pendientes = $pdo->query($sqlPend)->fetchAll();

// ===== CENOPE: Personal =====
function col($r, $a, $b=null) { return isset($r[$a]) ? $r[$a] : ($b && isset($r[$b]) ? $r[$b] : null); }

$ordenGrado = "FIELD(grado,'TG','GD','GB','CY','CR','TC','MY','CT','TP','TT','ST','SM','SP','SA','SI','SG','CI','CB','VP','VS','SV','SOLD')";
$pi = $pdo->query("SELECT * FROM personal_internado ORDER BY categoria, $ordenGrado, COALESCE(apellido_nombre,apellidoNombre)")->fetchAll();
$pa = $pdo->query("SELECT * FROM personal_alta ORDER BY categoria, $ordenGrado, COALESCE(apellido_nombre,apellidoNombre)")->fetchAll();
$pf = $pdo->query("SELECT * FROM personal_fallecido ORDER BY id DESC")->fetchAll();

function groupByKey(array $rows, string $key): array { $g=[]; foreach($rows as $r){ $g[$r[$key]][]=$r; } return $g; }
$piG = groupByKey($pi, 'categoria');  // OFICIALES / SUBOFICIALES / SOLDADOS VOLUNTARIOS
$paG = groupByKey($pa, 'categoria');

// ===== Estados de Sistemas (Servicios/ISP/SITELPAR) =====
$estados = $pdo->query("SELECT * FROM sistema_estado ORDER BY categoria_id, nombre")->fetchAll();
$estGrp  = [];
foreach ($estados as $r) { $estGrp[$r['categoria_id']][] = $r; }

// ===== Helpers HTML =====
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function render_estado_tabla(array $rows, array $cols) {
  if (!$rows){ echo "<div class='text-muted'>SIN NOVEDAD</div>"; return; }
  echo "<div class='table-responsive'><table class='table table-sm table-bordered'><thead><tr>";
  foreach($cols as $c) echo "<th>".h($c)."</th>";
  echo "</tr></thead><tbody>";
  foreach($rows as $r){
    $nov = ($r['estado']==='EN LINEA' && empty($r['novedad'])) ? 'EN LÍNEA' : ($r['novedad'] ?: $r['estado']);
    printf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>",
      h($r['nombre']),
      h(date('d/m/Y', strtotime($r['actualizado_en']))),
      h($nov . ($r['ticket'] ? " (".$r['ticket'].")" : ""))
    );
  }
  echo "</tbody></table></div>";
}

// Separar eventos por categoría:
$evtCat = [1=>[],2=>[],3=>[],4=>[],5=>[],6=>[]];
foreach ($eventos as $e) { $evtCat[$e['categoria_id']][] = $e; }

// ===== Documento HTML =====
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Parte de Novedades – B Com 602</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{ padding:24px; font-size:14px; }
  .top-motto{ font-size:12px; color:#6c757d; }
  h1{ font-size:22px; margin:.25rem 0; }
  h2{ font-size:18px; margin-top:1rem; }
  h3{ font-size:16px; margin-top:.8rem; }
  h4{ font-size:15px; margin-top:.8rem; }
  table{ font-size:13px; }
  .ref-box span{ display:inline-block; margin-right:10px }
  .watermark{
    position: fixed; inset: 0; z-index:-1;
    background: url('img/escudo602.png') no-repeat center 35%;
    background-size: 420px auto; opacity:.08;
  }
  @media print {
    .no-print{ display:none !important }
    body{ padding:18mm; }
    .watermark{ opacity:0.12; }
    @page { size: A4; margin: 14mm 12mm; }
  }
</style>
</head>
<body>
<div class="watermark"></div>

<div class="top-motto">Ejército Argentino – “Año de la Reconstrucción de la Nación Argentina”</div>
<h1>Batallón de Comunicaciones 602</h1>
<h2>PARTE DE NOVEDADES DEL ARMA DE COMUNICACIONES</h2>
<p><strong>Del día:</strong> <?=h(date('d/m/Y H:i', strtotime($desde)))?> <strong>al</strong> <?=h(date('d/m/Y H:i', strtotime($hasta)))?></p>

<p>
  <strong>OFICIAL DE TURNO:</strong> <?=h($enc['oficial_turno'])?>.<br>
  <strong>SUBOFICIAL DE TURNO:</strong> <?=h($enc['suboficial_turno'])?>.
</p>
<hr>

<h3>PERSONAL – SEGÚN PARTE DEL CENOPE</h3>

<h4>PERSONAL INTERNADO</h4>
<?php foreach (['OFICIALES','SUBOFICIALES','SOLDADOS VOLUNTARIOS'] as $cat): $rows=$piG[$cat]??[]; ?>
  <h5 class="mt-2"><?=h($cat)?></h5>
  <?php if(!$rows): ?>
    <div class="text-muted">SIN NOVEDAD</div>
  <?php else: ?>
  <div class="table-responsive"><table class="table table-sm table-bordered">
    <thead><tr>
      <th style="width:46px">Nro</th><th style="width:64px">Grado</th>
      <th>Apellido y Nombre</th><th>Arma</th><th>Unidad</th><th>Prom</th>
      <th style="width:86px">Fecha</th><th>Habitación</th><th>Hospital</th>
    </tr></thead>
    <tbody>
      <?php $n=1; foreach ($rows as $r): ?>
      <tr>
        <td><?=h($r['nro'] ?: sprintf('%02d',$n++))?></td>
        <td><?=h($r['grado'])?></td>
        <td><?=h(col($r,'apellido_nombre','apellidoNombre'))?></td>
        <td><?=h($r['arma'] ?? '')?></td>
        <td><?=h($r['unidad'] ?? '')?></td>
        <td><?=h($r['prom'] ?? '')?></td>
        <td><?=h($r['fecha'] ?? '')?></td>
        <td><?=h(col($r,'habitacion','habitación'))?></td>
        <td><?=h($r['hospital'] ?? '')?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
<?php endforeach; ?>

<h4 class="mt-3">PERSONAL ALTA</h4>
<?php foreach (['OFICIALES','SUBOFICIALES','SOLDADOS VOLUNTARIOS'] as $cat): $rows=$paG[$cat]??[]; ?>
  <h5 class="mt-2"><?=h($cat)?></h5>
  <?php if(!$rows): ?>
    <div class="text-muted">SIN NOVEDAD</div>
  <?php else: ?>
  <div class="table-responsive"><table class="table table-sm table-bordered">
    <thead><tr>
      <th style="width:46px">Nro</th><th style="width:64px">Grado</th>
      <th>Apellido y Nombre</th><th>Arma</th><th>Unidad</th><th>Prom</th>
      <th style="width:86px">Fecha</th><th>Hospital</th>
    </tr></thead>
    <tbody>
      <?php $n=1; foreach ($rows as $r): ?>
      <tr>
        <td><?=sprintf('%02d',$n++)?></td>
        <td><?=h($r['grado'])?></td>
        <td><?=h(col($r,'apellido_nombre','apellidoNombre'))?></td>
        <td><?=h($r['arma'] ?? '')?></td>
        <td><?=h($r['unidad'] ?? '')?></td>
        <td><?=h($r['prom'] ?? '')?></td>
        <td><?=h($r['fecha'] ?? '')?></td>
        <td><?=h($r['hospital'] ?? '')?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
<?php endforeach; ?>

<h4 class="mt-3">PERSONAL FALLECIDO</h4>
<?php if(!$pf): ?>
  <div class="text-muted">SIN NOVEDAD</div>
<?php else: ?>
  <ul class="mb-3"><?php foreach($pf as $f): ?><li><?=h($f['detalle'])?></li><?php endforeach; ?></ul>
<?php endif; ?>

<h4 class="mt-4">REFERENCIA</h4>
<div class="ref-box mb-2">
  <span>■ NOVEDAD NUEVA</span>
  <span>■ NOVEDAD ACTUALIZADA</span>
  <span>■ NOVEDAD RESUELTA</span>
</div>

<!-- SISTEMAS – RADIOELÉCTRICOS – NODOS REDISE (cat=1) -->
<h4 class="mt-3">SISTEMAS – RADIOELÉCTRICOS – NODOS REDISE</h4>
<div class="table-responsive"><table class="table table-sm table-bordered">
  <thead><tr>
    <th style="width:150px">Nodo</th>
    <th style="width:86px">Desde</th>
    <th>Novedades</th>
    <th style="width:120px">Fecha evento</th>
    <th style="width:110px">Servicio</th>
    <th style="width:120px">N° Ticket</th>
  </tr></thead>
  <tbody>
    <?php if(empty($evtCat[1])): ?>
      <tr><td colspan="6" class="text-muted">SIN NOVEDAD</td></tr>
    <?php else: foreach($evtCat[1] as $e): ?>
      <tr>
        <td><!-- Si tenés unidad_id -> imprimir nombre de unidad aquí --></td>
        <td><?=h($e['fecha_inicio'] ? date('d/m/Y', strtotime($e['fecha_inicio'])) : '')?></td>
        <td><strong><?=h($e['titulo'])?></strong><?= $e['detalle']? ' – '.h($e['detalle']) : '' ?></td>
        <td><?=h(date('d/m/Y H:i', strtotime($e['creado_en'])))?></td>
        <td><?=h($e['servicio'] ?? '')?></td>
        <td><?=h($e['ticket'] ?? '')?></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table></div>

<!-- SISTEMAS – SERVICIOS (cat=2) -->
<h4 class="mt-3">SISTEMAS – SERVICIOS</h4>
<?php render_estado_tabla($estGrp[2] ?? [], ['SISTEMA','FECHA','NOVEDADES']); ?>

<!-- SISTEMAS – ISP EDIFICIO LIBERTADOR (cat=3) -->
<h4 class="mt-3">SISTEMAS – ISP EDIFICIO LIBERTADOR</h4>
<?php render_estado_tabla($estGrp[3] ?? [], ['PRESTATARIA / VÍNCULO','FECHA','NOVEDADES']); ?>

<!-- SITELPAR (cat=4) -->
<h4 class="mt-3">SITELPAR</h4>
<?php render_estado_tabla($estGrp[4] ?? [], ['SERVICIO','FECHA','NOVEDADES']); ?>

<!-- DATA CENTER (cat=5) -->
<h4 class="mt-3">SISTEMAS – DATA CENTER</h4>
<div class="table-responsive"><table class="table table-sm table-bordered">
  <thead><tr><th>Novedades</th><th style="width:140px">Fecha evento</th><th style="width:120px">Servicio</th><th style="width:120px">Nº Ticket GLPI</th></tr></thead>
  <tbody>
    <?php if(empty($evtCat[5])): ?>
      <tr><td colspan="4" class="text-muted">SIN NOVEDAD</td></tr>
    <?php else: foreach($evtCat[5] as $e): ?>
      <tr>
        <td><strong><?=h($e['titulo'])?></strong><?= $e['detalle']? ' – '.h($e['detalle']) : '' ?></td>
        <td><?=h(date('d/m/Y H:i', strtotime($e['creado_en'])))?></td>
        <td><?=h($e['servicio'] ?? '')?></td>
        <td><?=h($e['ticket'] ?? '')?></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table></div>

<!-- SITM2 (cat=6) -->
<h4 class="mt-3">SISTEMAS – SITM2</h4>
<div class="table-responsive"><table class="table table-sm table-bordered">
  <thead><tr><th>Novedades</th><th style="width:140px">Fecha evento</th><th style="width:120px">Servicio</th><th style="width:120px">Nº Ticket GLPI</th></tr></thead>
  <tbody>
    <?php if(empty($evtCat[6])): ?>
      <tr><td colspan="4" class="text-muted">SIN NOVEDAD</td></tr>
    <?php else: foreach($evtCat[6] as $e): ?>
      <tr>
        <td><strong><?=h($e['titulo'])?></strong><?= $e['detalle']? ' – '.h($e['detalle']) : '' ?></td>
        <td><?=h(date('d/m/Y H:i', strtotime($e['creado_en'])))?></td>
        <td><?=h($e['servicio'] ?? '')?></td>
        <td><?=h($e['ticket'] ?? '')?></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table></div>

<!-- Pendientes al cierre (opcional) -->
<h4 class="mt-4">PENDIENTES AL CIERRE</h4>
<div class="table-responsive"><table class="table table-sm table-bordered">
  <thead><tr><th>Inicio</th><th>Categoría</th><th>Título</th><th>Prioridad</th><th>Estado</th></tr></thead>
  <tbody>
    <?php if(!$pendientes): ?>
      <tr><td colspan="5" class="text-muted">SIN NOVEDAD</td></tr>
    <?php else: foreach($pendientes as $p): ?>
      <tr>
        <td><?=h(date('d/m/Y H:i', strtotime($p['fecha_inicio'])))?></td>
        <td><?=h($p['categoria'])?></td>
        <td><?=h($p['titulo'])?></td>
        <td><?=h($p['prioridad'])?></td>
        <td><?=h($p['estado'])?></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table></div>

<div class="no-print mt-3">
  <button class="btn btn-outline-secondary" onclick="window.print()">Imprimir / Guardar PDF</button>
</div>

</body>
</html>
