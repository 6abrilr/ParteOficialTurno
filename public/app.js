// ========= app.js v8.3 =========

// ========= Formateador REDISE → texto CCC =========
function toDDMMMYY(d){
  if (/^\d{2}[A-Z]{3}\d{2}$/.test(d)) return d;
  const MES = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
  const m = String(d||'').trim();
  if (/^\d{1,2}[A-Z]{3}\d{2}$/.test(m)) return (m.length===7?('0'+m):m).toUpperCase();
  const dt = new Date(m);
  if (isNaN(dt)) return '';
  const dd  = String(dt.getDate()).padStart(2,'0');
  const mon = MES[dt.getMonth()];
  const yy  = String(dt.getFullYear()).slice(-2);
  return `${dd}${mon}${yy}`;
}
function cleanNodo(n){
  n = (n||'').toUpperCase().replace(/\s+/g,' ').trim();
  if (!/^CCIG/.test(n)) return n;
  const parts = n.split(' ');
  const head = parts.shift();
  const rest = parts.join(' ');
  const lines = [];
  let row = '';
  rest.split(' ').forEach(w=>{
    if ((row + ' ' + w).trim().length > 15) {
      if (row) { lines.push(row.trim()); row=''; }
    }
    row += (row?' ':'') + w;
  });
  if (row) lines.push(row.trim());
  return head + '\n' + lines.join('\n');
}
function tailServicioTicket(servicio, ticket){
  const svc = (servicio||'').toUpperCase().replace('VHF/HF','HF/VHF');
  const svcTxt = svc ? ' ' + svc : '';
  const tic = (ticket||'').trim();
  const ticTxt = tic ? ' ' + tic : ' ---';
  return (svcTxt || ' ---') + ticTxt;
}
function rediseToTextoCCC(rows){
  const out = [];
  (rows||[]).forEach(r=>{
    const nodo  = cleanNodo(r.nodo);
    const desde = (r.desde||'').toUpperCase();
    const nov   = (r.novedad||'').replace(/\s{2,}/g,' ').trim();
    const fecha = toDDMMMYY(r.fecha || new Date());
    const tail  = tailServicioTicket(r.servicio, r.ticket);
    if (!nodo || !nov) return;
    const block = [ nodo, desde || '', nov, `${fecha}${tail}` ]
      .filter(x=>String(x).trim()!=='').join('\n');
    out.push(block);
  });
  return out.join('\n');
}

// ===== Endpoints =====
const API_NOV     = '../php/api_novedades.php';
const API_PARTE   = '../php/api_parte.php';
const API_CENOPE  = '../php/import_cenope.php';
const API_LTA     = '../php/import_lta_docx.php';
const API_SIST    = '../php/api_sistemas.php';
const API_REDISE  = '../php/api_redise.php';
const API_GENERAR_PARTE = '../php/generar_parte.php';

// ===== Helpers =====
const qs  = (id) => document.getElementById(id);
const esc = (s) => String(s ?? '')
  .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
  .replaceAll('"','&quot;').replaceAll("'","&#039;");
console.log('[APP] v8.3 cargado');

// ===== Estado de validaciones (gating del botón Generar) =====
let encabezadoGuardado = false;
let archivosCargados   = false;
let sistemasConfirmado = false;

function refreshGenerarEnabled(){
  const btn = qs('btnGenerarParte');
  if (!btn) return;
  const ok = (encabezadoGuardado && archivosCargados && sistemasConfirmado);
  console.log('[GATE] encab=',encabezadoGuardado,' files=',archivosCargados,' sist=',sistemasConfirmado,' => habilitado=',ok);
  btn.disabled = !ok;
}
refreshGenerarEnabled();

// ===== Fecha por defecto: HOY 08:00 → MAÑANA 08:00 (campos del generador) =====
(function setDefaultDates0800(){
  const fDesde = qs('g_desde'), fHasta = qs('g_hasta');
  if (!fDesde || !fHasta) return;
  const now = new Date();
  const hoy0800 = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 8, 0, 0, 0);
  const maniana0800 = new Date(hoy0800.getTime() + 24*60*60*1000);
  const toLocal = (d) => {
    const off = d.getTimezoneOffset()*60000;
    return new Date(d.getTime() - off).toISOString().slice(0,16);
  };
  if (!fDesde.value) fDesde.value = toLocal(hoy0800);
  if (!fHasta.value) fHasta.value = toLocal(maniana0800);
})();

// ===== Guardar encabezado =====
qs('btnGuardarEncGen')?.addEventListener('click', async ()=>{
  const payload = {
    fecha_desde: qs('g_desde')?.value || '',
    fecha_hasta: qs('g_hasta')?.value || '',
    oficial_turno: (qs('g_oficial')?.value || '').trim(),
    suboficial_turno: (qs('g_subof')?.value || '').trim()
  };
  try{
    const r = await fetch(API_PARTE,{method:'POST', body: JSON.stringify(payload)});
    const data = await r.json();
    if (!data.ok) throw new Error(data.error||'No se pudo guardar encabezado');
    encabezadoGuardado = true;
    refreshGenerarEnabled();
    alert('Encabezado guardado ✔');
  }catch(err){
    encabezadoGuardado = false;
    refreshGenerarEnabled();
    alert('Error al guardar encabezado: ' + err.message);
  }
});

// ==========================
//   IMPORTADOR LTA (DOCX/PDF)
// ==========================
qs('btnPrevLTA')?.addEventListener('click', async ()=>{
  const f = qs('p_lta')?.files?.[0];
  if(!f) return alert('Elegí el DOCX o PDF del LTA');
  const fd = new FormData(); fd.append('file', f); fd.append('dry','1');
  qs('ltaInfo') && (qs('ltaInfo').textContent = 'Procesando archivo (previsualización)...');
  try{
    const r = await fetch(API_LTA,{method:'POST', body: fd});
    const data = await r.json();
    if(!data.ok) throw new Error(data.error||'Error al procesar');
    renderPreviewLTA(data.redise || []);
    qs('ltaInfo').textContent =
      `Filas REDISE: ${(data.redise||[]).length}` + (data._src ? `  [${data._src}]` : '');
    qs('btnImpLTA') && (qs('btnImpLTA').disabled = false);
  }catch(err){
    qs('ltaInfo') && (qs('ltaInfo').textContent = 'Error: '+ err.message);
    qs('ltaPreview') && (qs('ltaPreview').innerHTML = '');
  }
});

qs('btnImpLTA')?.addEventListener('click', async ()=>{
  const f = qs('p_lta')?.files?.[0];
  if(!f) return;
  const fd = new FormData(); fd.append('file', f); fd.append('dry','0');
  qs('ltaInfo') && (qs('ltaInfo').textContent = 'Importando...');
  try{
    const r = await fetch(API_LTA,{method:'POST', body: fd});
    const data = await r.json();
    if(!data.ok) throw new Error(data.error||'Error al importar');
    qs('ltaInfo') && (qs('ltaInfo').textContent = `Importado ✔  Filas REDISE: ${(data.redise||[]).length}`);
    archivosCargados = (qs('p_lta')?.files?.length>0) && (qs('p_cenope')?.files?.length>0);
    refreshGenerarEnabled();
  }catch(err){
    qs('ltaInfo') && (qs('ltaInfo').textContent = 'Error: '+ err.message);
  }
});

// ==========================
//   IMPORTADOR CENOPE (PDF)
// ==========================

function getCenopeInput() {
  const input = qs('p_cenope');
  if (!input || !input.files || input.files.length === 0) return null;
  return input;
}
function buildCenopeFD(dry = '1') {
  const input = getCenopeInput();
  if (!input) return null;
  const fd = new FormData();
  Array.from(input.files).slice(0, 3).forEach(f => fd.append('pdf[]', f));
  fd.append('dry', dry);
  fd.append('debug', '1');
  return fd;
}

qs('btnPrevCenope')?.addEventListener('click', async () => {
  const info = qs('cenopeInfo');
  const fd = buildCenopeFD('1');
  if (!fd) return alert('Elegí el/los PDF del CENOPE');

  if (info) info.textContent = 'Procesando PDF(s) (previsualización)...';

  try {
    const r   = await fetch(API_CENOPE, { method:'POST', body: fd });
    const raw = await r.text();
    let data;
    try { data = JSON.parse(raw); }
    catch { throw new Error('Respuesta no JSON en previsualización: ' + raw.slice(0,400)); }

    if (!data.ok) {
      const dbg = data.debug ? '\n\n' + data.debug : '';
      throw new Error((data.error || 'Error al procesar') + dbg);
    }

    const onlyObjects = (arr) => Array.isArray(arr) ? arr.filter(x => x && typeof x === 'object' && !Array.isArray(x)) : [];
    const internado = onlyObjects(data.internado);
    const alta      = onlyObjects(data.alta);
    const fallecido = onlyObjects(data.fallecido);

    if (info) info.textContent =
      `Internados: ${internado.length} | Altas: ${alta.length} | Fallecidos: ${fallecido.length}`;

    qs('btnImpCenope') && (qs('btnImpCenope').disabled = false);
    renderPreviewCenope({internado, alta, fallecido});

    archivosCargados = (qs('p_lta')?.files?.length>0) && (qs('p_cenope')?.files?.length>0);
    refreshGenerarEnabled();

  } catch (err) {
    console.error('[CENOPE] Previsualizar falló:', err);
    if (info) info.textContent = 'Error: ' + err.message;
  }
});

qs('btnImpCenope')?.addEventListener('click', async () => {
  const info = qs('cenopeInfo');
  const fd = buildCenopeFD('0');  // guardar
  if (!fd) return alert('Elegí el/los PDF del CENOPE');

  if (info) info.textContent = 'Importando a la base...';

  try {
    const r   = await fetch(API_CENOPE, { method:'POST', body: fd });
    const raw = await r.text();
    let data;
    try { data = JSON.parse(raw); }
    catch { throw new Error('Respuesta no JSON: ' + raw.slice(0,400)); }

    if (!data.ok) {
      const dbg = data.debug ? '\n\n' + data.debug : '';
      throw new Error((data.error || 'Error al importar') + dbg);
    }

    if (info) info.textContent =
      `Importado. Internados: ${data.internado} | Altas: ${data.alta} | Fallecidos: ${data.fallecido}`;

    alert('CENOPE importado correctamente.');
    archivosCargados = (qs('p_lta')?.files?.length>0) && (qs('p_cenope')?.files?.length>0);
    refreshGenerarEnabled();

  } catch (err) {
    console.error('[CENOPE] Guardar falló:', err);
    if (info) info.textContent = 'Error: ' + err.message;
    alert('Importar CENOPE falló:\n' + err.message);
  }
});

// --- CENOPE: render de la previsualización (EDITABLE) ---
window.renderPreviewCenope = function renderPreviewCenope(data){
  const div = qs('previewTables'); if(!div) return;

  // columnas para internados/altas/fallecidos (objetos)
  const cols = [
    ['Nro','Nro'],
    ['Grado','Grado'],
    ['Apellido y Nombre','Apellido y Nombre'],
    ['Arma','Arma'],
    ['Unidad','Unidad/Destino'],
    ['Fecha','Fecha'],
    ['Habitación','Habitación'],
    ['Hospital','Hospital'],
    ['Detalle','Detalle'],        // <- NUEVA columna para Fallecidos
  ];

  const getVal = (obj, key) => {
    if (!obj || typeof obj !== 'object') return '';
    return Object.prototype.hasOwnProperty.call(obj, key) ? (obj[key] ?? '') : '';
  };

  const mkEditable = (title, rows)=>{
    const rowsObj = Array.isArray(rows)
      ? rows.filter(r => r && typeof r === 'object')
      : [];

    if (!rowsObj.length) {
      return `<h6 class='mt-2'>${esc(title)}</h6><div class='text-muted'>SIN NOVEDAD</div>`;
    }

    const thead = `<thead><tr>${
      cols.map(([,lab])=>`<th>${esc(lab)}</th>`).join('')
    }</tr></thead>`;

    const tbody = rowsObj.slice(0,200).map((r, i)=>{
      const tds = cols.map(([k])=>{
        const v = k === 'Nro'
          ? (String(getVal(r,k)).trim() || String(i+1))
          : getVal(r, k);
        return `<td contenteditable="true">${esc(v)}</td>`;
      }).join('');
      return `<tr>${tds}</tr>`;
    }).join('');

    return `
      <h6 class='mt-3'>${esc(title)} <span class="estado-badge">(editable)</span></h6>
      <div class='table-responsive'>
        <table class='table table-sm table-bordered table-editable'>
          ${thead}<tbody>${tbody}</tbody>
        </table>
      </div>`;
  };

  div.innerHTML =
      mkEditable('INTERNADOS', data?.internado)
    + mkEditable('ALTAS',      data?.alta)
    + mkEditable('FALLECIDOS', data?.fallecido);
};

// ============================
//   ESTADOS DE SISTEMAS (UI)
// ============================
const ESTADOS = ['EN LINEA','SIN SERVICIO','NOVEDAD'];

async function cargarSistemaTabla(divId, catId){
  const div = qs(divId); if(!div) return;
  try{
    const r = await fetch(`${API_SIST}?action=listar&cat=${catId}`);
    const rows = await r.json();
    if(!rows.length){ div.innerHTML = '<div class="text-muted">Sin elementos</div>'; return; }

    const head = `<thead><tr><th style="width:240px">Nombre</th><th style="width:160px">Estado</th><th>Novedad</th><th style="width:140px">Ticket</th></tr></thead>`;
    const body = rows.map(x=>`<tr data-id="${x.id}">
      <td>${esc(x.nombre)}</td>
      <td>
        <select class="form-select form-select-sm estado">
          ${ESTADOS.map(s=>`<option ${s===x.estado?'selected':''}>${s}</option>`).join('')}
        </select>
      </td>
      <td><input class="form-control form-control-sm nov" value="${esc(x.novedad??'')}" placeholder="Detalle (opcional)"></td>
      <td><input class="form-control form-control-sm tic" value="${esc(x.ticket??'')}" placeholder="GLPI / MM / ..."></td>
    </tr>`).join('');

    div.innerHTML = `<div class="table-responsive"><table class="table table-sm align-middle">${head}<tbody>${body}</tbody></table></div>`;

    // Guardado por cambio
    div.querySelectorAll('tr[data-id]').forEach(tr=>{
      const id = +tr.dataset.id;
      const sel = tr.querySelector('.estado');
      const nov = tr.querySelector('.nov');
      const tic = tr.querySelector('.tic');
      const save = async ()=>{
        await fetch(`${API_SIST}?action=guardar`,{
          method:'POST',
          body: JSON.stringify({id, estado: sel.value, novedad: nov.value.trim(), ticket: tic.value.trim()})
        });
      };
      sel.onchange = save; nov.onchange = save; tic.onchange = save;
    });
  }catch(e){
    console.error(e);
  }
}

cargarSistemaTabla('tblServ',2);
cargarSistemaTabla('tblIsp',3);
cargarSistemaTabla('tblSitelpar',4);
cargarSistemaTabla('tblDC',5);
cargarSistemaTabla('tblSITM2',6);

// Botón "Confirmar sistemas"
qs('btnConfirmSistemas')?.addEventListener('click', (ev)=>{
  const btn = ev.currentTarget;
  sistemasConfirmado = true;
  refreshGenerarEnabled();

  btn.disabled = true;
  btn.classList.remove('btn-outline-primary');
  btn.classList.add('btn-success');
  btn.textContent = 'Sistemas confirmados ✔';
});

// ===== Helpers (LTA / REDISE) =====
function turnoHoyDDMMMYY(){
  const d = new Date();
  const dd = String(d.getDate()).padStart(2,'0');
  const MES = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'][d.getMonth()];
  const yy = String(d.getFullYear()).slice(-2);
  return `${dd}${MES}${yy}`;
}
function applyRowEstado(tr, estado){
  tr.classList.remove('tr-estado-NUEVA','tr-estado-ACTUALIZADA','tr-estado-RESUELTA');
  if (estado) tr.classList.add('tr-estado-'+estado);
}
function valueOrEmpty(el){ return (el?.textContent ?? '').trim(); }

// ======== Snapshots/Comparación (para LTA) ========
function normNodo(n){ return String(n||'').toUpperCase().replace(/\s+/g,' ').trim(); }
function normText(s){ return String(s||'').toUpperCase().replace(/\s+/g,' ').trim(); }

function diffSnapshots(prevRows, currRows){
  const byKey = rows => {
    const map = new Map();
    (rows||[]).forEach(r=>{
      const key = normNodo(r.nodo) + '|' + normText(r.servicio||'');
      map.set(key, r);
    });
    return map;
  };
  const A = byKey(prevRows||[]);
  const B = byKey(currRows||[]);
  const result = [];
  B.forEach((curr, key)=>{
    const prev = A.get(key);
    if (!prev) {
      result.push({...curr, estado:'NUEVA'});
    } else {
      const changed =
        normText(curr.novedad)  !== normText(prev.novedad) ||
        normText(curr.ticket)   !== normText(prev.ticket) ||
        normText(curr.desde)    !== normText(prev.desde);
      result.push({...curr, estado: changed ? 'ACTUALIZADA' : (curr.estado || 'ACTUALIZADA')});
      A.delete(key);
    }
  });
  A.forEach(prev=>{
    result.push({
      nodo: prev.nodo, desde: prev.desde, novedad: prev.novedad,
      fecha: turnoHoyDDMMMYY(), servicio: prev.servicio, ticket: prev.ticket, estado:'RESUELTA'
    });
  });
  return result;
}

async function cargarUltimoSnapshot(){
  const r = await fetch(`${API_REDISE}?action=last`);
  const j = await r.json();
  if (!j.ok) throw new Error(j.error||'No se pudo leer snapshot');
  return j.found ? j.snapshot : null;
}

// ===== LTA – Render =====
function renderPreviewLTA(rows){
  const div = qs('ltaPreview'); if(!div) return;
  if(!rows || !rows.length){
    div.innerHTML = '<div class="text-muted">SIN NOVEDAD</div>';
    return;
  }
  const hoy = turnoHoyDDMMMYY();
  (async ()=>{
    try{
      const snap = await cargarUltimoSnapshot();
      const prev = snap ? (snap.data_json || []) : [];
      const curr = rows.map(r => ({...r, fecha: hoy}));
      const merged = diffSnapshots(prev, curr);
      renderLtaTabla(merged, hoy);
    }catch(err){
      console.error('No se pudo comparar con snapshot previo:', err);
      const curr = rows.map(r => ({...r, fecha: hoy, estado: r.estado || 'ACTUALIZADA'}));
      renderLtaTabla(curr, hoy);
    }
  })();
}
function renderLtaTabla(rows, hoy){
  const div = qs('ltaPreview'); if(!div) return;
  const head = `
    <thead>
      <tr>
        <th style="min-width:180px">NODO</th>
        <th style="width:90px">DESDE</th>
        <th>NOVEDADES <span class="estado-badge">Hacé clic y editá el texto</span></th>
        <th style="width:90px">FECHA</th>
        <th style="width:90px">SERVICIO</th>
        <th style="width:110px">N° Ticket</th>
        <th style="width:140px">Estado</th>
      </tr>
    </thead>`;
  const body = rows.map((r, idx)=>`
    <tr data-row="${idx}" class="tr-estado-${r.estado||'ACTUALIZADA'}">
      <td contenteditable="true" class="ce-nodo">${esc(r.nodo||'')}</td>
      <td contenteditable="true" class="ce-desde text-nowrap">${esc(r.desde||'')}</td>
      <td contenteditable="true" class="ce-nov">${esc(r.novedad||'')}</td>
      <td contenteditable="true" class="ce-fecha text-nowrap">${esc(r.fecha||hoy)}</td>
      <td contenteditable="true" class="ce-serv text-nowrap">${esc(r.servicio||'')}</td>
      <td contenteditable="true" class="ce-tic text-nowrap">${esc(r.ticket||'')}</td>
      <td>
        <select class="form-select form-select-sm sel-estado" data-row="${idx}">
          <option ${(r.estado==='NUEVA')?'selected':''}>NUEVA</option>
          <option ${(r.estado==='ACTUALIZADA'||!r.estado)?'selected':''}>ACTUALIZADA</option>
          <option ${(r.estado==='RESUELTA')?'selected':''}>RESUELTA</option>
        </select>
      </td>
    </tr>`).join('');
  div.innerHTML = `
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle" id="tblLTA">
        ${head}
        <tbody>${body}</tbody>
      </table>
    </div>
    <div class="small text-muted mt-1" id="ltaDiffInfo"></div>`;
  div.querySelectorAll('.sel-estado').forEach(sel=>{
    sel.onchange = () => {
      const tr = sel.closest('tr');
      applyRowEstado(tr, sel.value);
    };
  });
  const info = qs('ltaDiffInfo');
  if (info) {
    const cnt = rows.reduce((a,r)=>{ a[r.estado||'ACTUALIZADA']=(a[r.estado||'ACTUALIZADA']||0)+1; return a; },{});
    info.textContent = `NUEVAS: ${cnt['NUEVA']||0} | ACTUALIZADAS: ${cnt['ACTUALIZADA']||0} | RESUELTAS: ${cnt['RESUELTA']||0}`;
  }
}
function ltaLeerTablaActual(){
  const tbl = qs('tblLTA'); if(!tbl) return [];
  return Array.from(tbl.querySelectorAll('tbody tr')).map(tr=>({
    nodo:     (tr.querySelector('.ce-nodo')?.textContent ?? '').trim(),
    desde:    (tr.querySelector('.ce-desde')?.textContent ?? '').trim(),
    novedad:  (tr.querySelector('.ce-nov')?.textContent ?? '').trim(),
    fecha:    (tr.querySelector('.ce-fecha')?.textContent ?? '').trim(),
    servicio: (tr.querySelector('.ce-serv')?.textContent ?? '').trim(),
    ticket:   (tr.querySelector('.ce-tic')?.textContent ?? '').trim(),
    estado:   tr.querySelector('.sel-estado')?.value || 'ACTUALIZADA'
  }));
} // <-- cerrar la función AQUÍ

// ===== Generar Parte del Arma (usa lo guardado en la base) =====
qs('btnGenerarParte')?.addEventListener('click', async () => {
  const fd = new FormData();
  fd.append('desde',     qs('g_desde')?.value || '');
  fd.append('hasta',     qs('g_hasta')?.value || '');
  fd.append('oficial',   (qs('g_oficial')?.value || '').trim());
  fd.append('suboficial',(qs('g_subof')?.value || '').trim());

  const ltaRows = (typeof ltaLeerTablaActual === 'function') ? ltaLeerTablaActual() : [];
  if (ltaRows && ltaRows.length) fd.append('lta_json', JSON.stringify(ltaRows));

  const info = qs('parteInfo');
  if (info) info.textContent = 'Generando Parte...';

  try {
    const r   = await fetch(API_GENERAR_PARTE, { method: 'POST', body: fd });
    const raw = await r.text();
    let data;
    try { data = JSON.parse(raw); } 
    catch { throw new Error('Respuesta no JSON de generar_parte: ' + raw.slice(0,400)); }

    if (!data.ok) throw new Error(data.error || 'No se pudo generar el parte');

    const aH = qs('lnkParteHTML');
    const aP = qs('lnkPartePDF');
    if (aH) { aH.href = data.html; aH.classList.remove('d-none'); }
    if (aP) {
      if (data.pdf) { aP.href = data.pdf; aP.classList.remove('d-none'); }
      else { aP.classList.add('d-none'); }
    }
    if (info) info.textContent = `Parte generado ✔ – Turno ${data.turno || ''}`;
  } catch (err) {
    console.error('[PARTE] generar falló:', err);
    if (qs('parteInfo')) qs('parteInfo').textContent = 'Error: ' + err.message;
    alert('No se pudo generar el parte:\n' + err.message);
  }
});
