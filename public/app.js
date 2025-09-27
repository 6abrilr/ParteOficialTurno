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
  const lines = []; let row = '';
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
const fmt = (d) => new Date(d).toLocaleString('es-AR');
const esc = (s) => String(s ?? '')
  .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
  .replaceAll('"','&quot;').replaceAll("'","&#039;");

// ===== Habilitación de “Generar Parte” =====
let encOk = false, ltaOk = false, ceOk = false;
const tablasGuardadas = new Set();              // categorías guardadas
const REQUIERE_TABLAS = [2,3,4,5,6];

function allSystemsSaved(){ return REQUIERE_TABLAS.every(c => tablasGuardadas.has(c)); }
function updateGenerateBtn(){
  const btn = qs('btnGenerarParte');
  if (btn) btn.disabled = !(encOk && ltaOk && ceOk && allSystemsSaved());
}

// ===== Fecha por defecto: HOY 08:00 → MAÑANA 08:00 =====
(function setDefaultDates0800(){
  const fDesde = qs('desde'), fHasta = qs('hasta');
  if (!fDesde || !fHasta) return;

  const now = new Date();
  const hoy0800 = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 8, 0, 0, 0);
  const maniana0800 = new Date(hoy0800.getTime() + 86400000);

  const toLocal = (d) => {
    const off = d.getTimezoneOffset()*60000;
    return new Date(d.getTime() - off).toISOString().slice(0,16);
  };

  if (!fDesde.value) fDesde.value = toLocal(hoy0800);
  if (!fHasta.value) fHasta.value = toLocal(maniana0800);

  // sincroniza con campos ocultos/abajo si están vacíos
  if (qs('p_desde') && !qs('p_desde').value) qs('p_desde').value = toLocal(hoy0800);
  if (qs('p_hasta') && !qs('p_hasta').value) qs('p_hasta').value = toLocal(maniana0800);
  if (qs('p_oficial') && !qs('p_oficial').value) qs('p_oficial').value = qs('oficial')?.value || '';
  if (qs('p_subof')   && !qs('p_subof').value)   qs('p_subof').value   = qs('suboficial')?.value || '';

  // reflejo si están vacíos los de abajo
  qs('desde')?.addEventListener('change', () => { if (!qs('p_desde')?.value) qs('p_desde').value = qs('desde').value; });
  qs('hasta')?.addEventListener('change', () => { if (!qs('p_hasta')?.value) qs('p_hasta').value = qs('hasta').value; });
  qs('oficial')?.addEventListener('input',  () => { if (!qs('p_oficial')?.value) qs('p_oficial').value = qs('oficial').value; });
  qs('suboficial')?.addEventListener('input',()=> { if (!qs('p_subof')?.value)   qs('p_subof').value   = qs('suboficial').value; });
})();

// ===== Guardar encabezado =====
qs('btnGuardarEnc')?.addEventListener('click', async ()=>{
  const payload = {
    fecha_desde: qs('desde').value,
    fecha_hasta: qs('hasta').value,
    oficial_turno: (qs('oficial')?.value || '').trim(),
    suboficial_turno: (qs('suboficial')?.value || '').trim()
  };
  try{
    const r = await fetch(API_PARTE,{method:'POST', body: JSON.stringify(payload)});
    const data = await r.json();
    if (!data.ok) throw new Error(data.error||'No se pudo guardar');
    encOk = true;
    updateGenerateBtn();
    alert('Encabezado guardado ✔');
  }catch(err){
    alert('Error al guardar encabezado: ' + err.message);
  }
});

// ===== Novedades (solo para el widget lateral) =====
async function cargarPendientes(){
  if (!qs('tblNovedades')) return;
  try{
    const r = await fetch(`${API_NOV}?action=lista`);
    const raw = await r.text();
    let data;
    try { data = JSON.parse(raw); } catch{ console.error('API_NOV no JSON:\n', raw); return; }
    const tb = qs('tblNovedades').querySelector('tbody');
    tb.innerHTML = '';
    data.forEach(n=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${fmt(n.fecha_inicio)}</td>
        <td><strong>${esc(n.titulo)}</strong><br><small class="text-muted">${esc((n.descripcion||'').slice(0,140))}...</small></td>
        <td>${esc(n.categoria)}</td>
        <td>${esc(n.unidad ?? '')}</td>
        <td>${esc(n.prioridad)}</td>
        <td class="text-nowrap"><button class="btn btn-sm btn-outline-success" data-resolver="${n.id}">Resolver</button></td>`;
      tb.appendChild(tr);
    });
    tb.querySelectorAll('[data-resolver]').forEach(b=>{
      b.onclick = async ()=>{
        await fetch(`${API_NOV}?action=resolver`,{method:'POST', body: JSON.stringify({id:+b.dataset.resolver})});
        cargarPendientes();
      };
    });
    const ts = qs('ts');
    if (ts) ts.textContent = 'Actualizado: ' + new Date().toLocaleString('es-AR');
  }catch(e){ console.error(e); }
}
cargarPendientes();

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
  }catch(e){ console.error(e); }
}
cargarSistemaTabla('tblServ',2);
cargarSistemaTabla('tblIsp',3);
cargarSistemaTabla('tblSitelpar',4);
cargarSistemaTabla('tblDC',5);
cargarSistemaTabla('tblSITM2',6);

// Guardar por tabla (botones explícitos)
async function guardarTabla(divId, catId){
  const div = qs(divId); if(!div) return;
  const rows = Array.from(div.querySelectorAll('tr[data-id]'));
  for (const tr of rows){
    const id  = +tr.dataset.id;
    const sel = tr.querySelector('.estado');
    const nov = tr.querySelector('.nov');
    const tic = tr.querySelector('.tic');
    await fetch(`${API_SIST}?action=guardar`,{
      method:'POST',
      body: JSON.stringify({id, estado: sel.value, novedad: (nov.value||'').trim(), ticket: (tic.value||'').trim()})
    });
  }
  tablasGuardadas.add(catId);
  updateGenerateBtn();
  alert('Tabla guardada ✔');
}
qs('btnGuardarServ')    ?.addEventListener('click', ()=> guardarTabla('tblServ',2));
qs('btnGuardarIsp')     ?.addEventListener('click', ()=> guardarTabla('tblIsp',3));
qs('btnGuardarSitelpar')?.addEventListener('click', ()=> guardarTabla('tblSitelpar',4));
qs('btnGuardarDC')      ?.addEventListener('click', ()=> guardarTabla('tblDC',5));
qs('btnGuardarSITM2')   ?.addEventListener('click', ()=> guardarTabla('tblSITM2',6));

// ==========================
//   PREVISUALIZAR LTA+CENOPE
// ==========================
qs('docxLta')?.addEventListener('change', ()=>{
  ltaOk = !!qs('docxLta').files?.length;
  updateGenerateBtn();
});
qs('pdfCenope')?.addEventListener('change', ()=>{
  ceOk = !!qs('pdfCenope').files?.length;
  updateGenerateBtn();
});

// Render previsualización CENOPE (3 tablas compactas)
window.renderPreviewCenope = function renderPreviewCenope(data){
  const div = qs('previewTables'); if(!div) return;
  const mk = (title, rows)=>{
    if(!rows || !rows.length) return `<h6 class='mt-2'>${esc(title)}</h6><div class='text-muted'>SIN NOVEDAD</div>`;
    const head = `<thead><tr><th>Nro</th><th>Grado</th><th>Apellido y Nombre</th><th>Arma</th><th>Unidad/Destino</th><th>Fecha</th><th>Habitación</th><th>Hospital</th></tr></thead>`;
    const body = rows.slice(0,10).map(r=>`<tr>
      <td>${esc(r.Nro??'')}</td>
      <td>${esc(r.Grado??'')}</td>
      <td>${esc(r['Apellido y Nombre']??'')}</td>
      <td>${esc(r.Arma??'')}</td>
      <td>${esc(r.Unidad??'')}</td>
      <td>${esc(r.Fecha??'')}</td>
      <td>${esc(r['Habitación']??'')}</td>
      <td>${esc(r.Hospital??'')}</td>
    </tr>`).join('');
    return `<h6 class='mt-2'>${esc(title)}</h6><div class='table-responsive'><table class='table table-sm table-bordered'>${head}<tbody>${body}</tbody></table></div>`;
  };
  div.innerHTML = mk('INTERNADOS (muestra 10)', data.internado)
                + mk('ALTAS (muestra 10)', data.alta)
                + mk('FALLECIDOS (muestra 10)', data.fallecido);
};

// Render LTA editable (con colores/estados)
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
  const A = byKey(prevRows||[]); const B = byKey(currRows||[]); const result=[];
  B.forEach((curr,key)=>{
    const prev = A.get(key);
    if (!prev) result.push({...curr, estado:'NUEVA'});
    else {
      const changed =
        normText(curr.novedad)!==normText(prev.novedad) ||
        normText(curr.ticket)!==normText(prev.ticket) ||
        normText(curr.desde)!==normText(prev.desde);
      result.push({...curr, estado: changed ? 'ACTUALIZADA' : (curr.estado || 'ACTUALIZADA')});
      A.delete(key);
    }
  });
  A.forEach(prev=>{
    result.push({ nodo:prev.nodo, desde:prev.desde, novedad:prev.novedad,
      fecha: turnoHoyDDMMMYY(), servicio: prev.servicio, ticket: prev.ticket, estado:'RESUELTA' });
  });
  return result;
}
async function cargarUltimoSnapshot(){
  const r = await fetch(`${API_REDISE}?action=last`);
  const j = await r.json();
  if (!j.ok) throw new Error(j.error||'No se pudo leer snapshot');
  return j.found ? j.snapshot : null;
}
function renderLtaTabla(rows, hoy){
  const div = qs('ltaPreview'); if(!div) return;
  const head = `
    <thead><tr>
      <th style="min-width:180px">NODO</th>
      <th style="width:90px">DESDE</th>
      <th>NOVEDADES <span class="estado-badge">Hacé clic y editá el texto</span></th>
      <th style="width:90px">FECHA</th>
      <th style="width:90px">SERVICIO</th>
      <th style="width:110px">N° Ticket</th>
      <th style="width:140px">Estado</th>
    </tr></thead>`;
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
  div.innerHTML = `<div class="table-responsive"><table class="table table-sm table-bordered align-middle" id="tblLTA">${head}<tbody>${body}</tbody></table></div>`;
  div.querySelectorAll('.sel-estado').forEach(sel=>{
    sel.onchange = () => { const tr = sel.closest('tr'); applyRowEstado(tr, sel.value); };
  });
}

// Botón combinado de previsualización (ambos archivos)
qs('btnPrevisualizar')?.addEventListener('click', async ()=>{
  const fLTA = qs('docxLta')?.files?.[0];
  const fCE  = qs('pdfCenope')?.files?.[0];
  if (!fLTA || !fCE) { alert('Elegí los 2 archivos: LTA y CENOPE'); return; }

  qs('parteInfo').textContent = 'Procesando archivos...';
  try{
    const fdL = new FormData(); fdL.append('file', fLTA); fdL.append('dry','1');
    const fdC = new FormData(); fdC.append('pdf',  fCE ); fdC.append('dry','1');
    const [rL, rC] = await Promise.all([
      fetch(API_LTA,    {method:'POST', body: fdL}),
      fetch(API_CENOPE, {method:'POST', body: fdC})
    ]);
    const jL = await rL.json();
    const jC = await rC.json();
    if (!jL.ok) throw new Error(jL.error||'Error LTA');
    if (!jC.ok) throw new Error(jC.error||'Error CENOPE');

    // LTA: render editable + comparación con snapshot previo
    const hoy = turnoHoyDDMMMYY();
    try{
      const snap = await cargarUltimoSnapshot();
      const prev = snap ? (snap.data_json || []) : [];
      const curr = (jL.redise||[]).map(r => ({...r, fecha: hoy}));
      const merged = diffSnapshots(prev, curr);
      renderLtaTabla(merged, hoy);
    }catch(err){
      renderLtaTabla((jL.redise||[]).map(r=>({...r, fecha:hoy, estado:r.estado||'ACTUALIZADA'})), hoy);
    }

    // CENOPE: 3 tablas compactas
    renderPreviewCenope(jC);

    qs('parteInfo').textContent = 'Previsualización lista ✔';
  }catch(err){
    qs('parteInfo').textContent = 'Error: ' + err.message;
  }
});

// ==========================
//   GENERAR PARTE (final)
// ==========================
qs('btnGenerarParte')?.addEventListener('click', async ()=>{
  const lta = qs('docxLta')?.files?.[0];
  const ce  = qs('pdfCenope')?.files?.[0];
  if(!lta || !ce) return alert('Subí LTA y CENOPE');

  const fd = new FormData();
  fd.append('lta', lta);
  fd.append('cenope', ce);
  fd.append('desde', qs('p_desde')?.value || qs('desde')?.value || '');
  fd.append('hasta', qs('p_hasta')?.value || qs('hasta')?.value || '');
  fd.append('oficial', qs('p_oficial')?.value || qs('oficial')?.value || '');
  fd.append('suboficial', qs('p_subof')?.value || qs('suboficial')?.value || '');

  qs('parteInfo').textContent = 'Generando Parte...';
  try{
    const r = await fetch(API_GENERAR_PARTE, {method:'POST', body: fd});
    const raw = await r.text();
    let data;
    try{ data = JSON.parse(raw); }catch{ throw new Error('Respuesta no JSON: '+raw); }
    if(!data.ok) throw new Error(data.error||'No se pudo generar');

    const aH = qs('lnkParteHTML'), aP = qs('lnkPartePDF');
    if (aH) { aH.href = data.html; aH.classList.remove('d-none'); }
    if (aP) { if (data.pdf) { aP.href = data.pdf; aP.classList.remove('d-none'); } else { aP.classList.add('d-none'); } }
    qs('parteInfo').textContent = `Parte generado ✔ – Turno ${data.turno}`;
  }catch(err){
    qs('parteInfo').textContent = 'Error: ' + err.message;
  }
});

// Estado inicial
updateGenerateBtn();
