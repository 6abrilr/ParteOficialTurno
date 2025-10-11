// ========= app.js v15 =========

// ====== FORMATEOS/UTILS ======
function toDDMMMYY(d){
  if (/^\d{2}[A-Z]{3}\d{2}(?:\s+[0-2]\d[0-5]\d)?$/.test(d)) return d;
  const MES = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
  const m = String(d||'').trim();
  // ddMMMaa
  if (/^\d{1,2}[A-Z]{3}\d{2}$/.test(m)) return (m.length===7?('0'+m):m).toUpperCase();
  // dd/mm/aa o aa-mm-dd
  let x = m.match(/^(\d{1,4})[\/\-](\d{1,2})[\/\-](\d{1,4})(?:[ T](\d{1,2})[:hH\.]?(\d{2}))?$/);
  if (x){
    let yyyy,mm,dd;
    if (x[1].length===4){ yyyy=x[1]; mm=x[2]; dd=x[3]; } else { dd=x[1]; mm=x[2]; yyyy=x[3]; }
    const mon = MES[(+mm||1)-1];
    const hhmm = (x[4]&&x[5]) ? (' '+String(+x[4]).padStart(2,'0')+String(+x[5]).padStart(2,'0')) : '';
    return `${String(+dd).padStart(2,'0')}${mon}${String(yyyy).slice(-2)}${hhmm}`;
  }
  // parse estándar
  const dt = new Date(m);
  if (!isNaN(dt)){
    const dd  = String(dt.getDate()).padStart(2,'0');
    const mon = MES[dt.getMonth()];
    const yy  = String(dt.getFullYear()).slice(-2);
    return `${dd}${mon}${yy}`;
  }
  return '';
}

// saneado
const esc = (s) => String(s ?? '')
  .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
  .replaceAll('"','&quot;').replaceAll("'","&#039;");
const get = (id) => document.getElementById(id);

// ====== ENDPOINTS ======
const API_NOV     = '../php/api_novedades.php';
const API_PARTE   = '../php/api_parte.php';
const API_CENOPE  = '../php/import_cenope.php';
const API_LTA     = '../php/import_lta_docx.php';
const API_SIST    = '../php/api_sistemas.php';
const API_REDISE  = '../php/api_redise.php';
const API_GENERAR_PARTE = '../php/generar_parte.php';

console.log('[APP] v15 cargado');

// ====== GATING GENERAR ======
let encabezadoGuardado = false;
let archivosCargados   = false;
let sistemasConfirmado = false;
function refreshGenerarEnabled(){
  const btn = get('btnGenerarParte');
  if (!btn) return;
  const ok = (encabezadoGuardado && archivosCargados && sistemasConfirmado);
  btn.disabled = !ok;
}
refreshGenerarEnabled();

// ====== Defaults 08:00 → 08:00 +1 ======
(function setDefaultDates0800(){
  const fDesde = get('g_desde'), fHasta = get('g_hasta');
  if (!fDesde || !fHasta) return;
  const now = new Date();
  const base0800 = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 8, 0, 0, 0);
  const next0800 = new Date(base0800.getTime() + 24*60*60*1000);
  const toLocal = (d) => {
    const off = d.getTimezoneOffset()*60000;
    return new Date(d.getTime() - off).toISOString().slice(0,16);
  };
  if (!fDesde.value) fDesde.value = toLocal(base0800);
  if (!fHasta.value) fHasta.value = toLocal(next0800);
})();

// ====== Guardar encabezado ======
get('btnGuardarEncGen')?.addEventListener('click', async ()=>{
  const payload = {
    fecha_desde: get('g_desde')?.value || '',
    fecha_hasta: get('g_hasta')?.value || '',
    oficial_turno: (get('g_oficial')?.value || '').trim(),
    suboficial_turno: (get('g_subof')?.value || '').trim()
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

// ====== LTA ======
get('btnPrevLTA')?.addEventListener('click', async ()=>{
  const f = get('p_lta')?.files?.[0];
  if(!f) return alert('Elegí el DOCX o PDF del LTA');
  const fd = new FormData(); fd.append('file', f); fd.append('dry','1');
  get('ltaInfo') && (get('ltaInfo').textContent = 'Procesando archivo (previsualización)...');
  try{
    const r = await fetch(API_LTA,{method:'POST', body: fd});
    const data = await r.json();
    if(!data.ok) throw new Error(data.error||'Error al procesar');
    renderPreviewLTA(data.redise || []);
    get('ltaInfo').textContent = `Filas REDISE: ${(data.redise||[]).length}` + (data._src ? `  [${data._src}]` : '');
    get('btnImpLTA') && (get('btnImpLTA').disabled = false);
  }catch(err){
    get('ltaInfo') && (get('ltaInfo').textContent = 'Error: '+ err.message);
    get('ltaPreview') && (get('ltaPreview').innerHTML = '');
  }
});

get('btnImpLTA')?.addEventListener('click', async ()=>{
  const f = get('p_lta')?.files?.[0];
  if(!f) return;
  const fd = new FormData(); fd.append('file', f); fd.append('dry','0');
  get('ltaInfo') && (get('ltaInfo').textContent = 'Importando...');
  try{
    const r = await fetch(API_LTA,{method:'POST', body: fd});
    const data = await r.json();
    if(!data.ok) throw new Error(data.error||'Error al importar');
    get('ltaInfo') && (get('ltaInfo').textContent = `Importado ✔  Filas REDISE: ${(data.redise||[]).length}`);
    archivosCargados = (get('p_lta')?.files?.length>0) && (get('p_cenope')?.files?.length>0);
    refreshGenerarEnabled();
  }catch(err){
    get('ltaInfo') && (get('ltaInfo').textContent = 'Error: '+ err.message);
  }
});

// ====== CENOPE ======
function getCenopeInput() {
  const input = get('p_cenope');
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

get('btnPrevCenope')?.addEventListener('click', async () => {
  const info = get('cenopeInfo');
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

    get('btnImpCenope') && (get('btnImpCenope').disabled = false);
    renderPreviewCenope({internado, alta, fallecido});

    archivosCargados = (get('p_lta')?.files?.length>0) && (get('p_cenope')?.files?.length>0);
    refreshGenerarEnabled();
  } catch (err) {
    console.error('[CENOPE] Previsualizar falló:', err);
    if (info) info.textContent = 'Error: ' + err.message;
  }
});

get('btnImpCenope')?.addEventListener('click', async () => {
  const info = get('cenopeInfo');
  const fd = buildCenopeFD('0');
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
    archivosCargados = (get('p_lta')?.files?.length>0) && (get('p_cenope')?.files?.length>0);
    refreshGenerarEnabled();
  } catch (err) {
    console.error('[CENOPE] Guardar falló:', err);
    if (info) info.textContent = 'Error: ' + err.message;
    alert('Importar CENOPE falló:\n' + err.message);
  }
});

// ====== Previsualización CENOPE (EDITABLE) ======

// Heurística para separar nombre de dirección/lugar (caso FALLECIDOS).
function cleanNombre(nombre, apoyo){
  const ADDRESS_TRIG = [" AV", " AV.", " AVENIDA", " CALLE", " RUTA", " PJE", " PASAJE", " KM ", " B° ", " BARRIO", " DIAG", " DIAGONAL"];
  let s = String(nombre||'').trim();
  const sup = (" "+String(apoyo||'').toUpperCase());
  // Si el “apoyo” (usualmente Lugar/Detalle) contiene un gatillo, cortamos el nombre al encontrar el mismo gatillo dentro del nombre.
  for(const trig of ADDRESS_TRIG){
    const p = sup.indexOf(trig);
    if(p>0){
      const key = sup.slice(p, p+14).trim();
      const i2 = (" "+s.toUpperCase()).indexOf(key);
      if(i2>5){ s = s.slice(0, i2-1).trim(); break; }
    }
  }
  // back-up: cortar si dentro del nombre aparece un gatillo común
  if (/[ ,;\-–•·]\s*(AV\.?|AVENIDA|CALLE|PJE|RUTA|KM|DIAG)/i.test(s)){
    s = s.replace(/([ ,;\-–•·]\s*(AV\.?|AVENIDA|CALLE|PJE|RUTA|KM|DIAG).*)$/i,'').trim();
  }
  // limpiar una fecha si quedó pegada
  s = s.replace(/\b\d{1,2}(ENE|FEB|MAR|ABR|MAY|JUN|JUL|AGO|SEP|OCT|NOV|DIC)\d{2}\b.*$/i,'').trim();
  return s;
}

// Toma un objeto “fila” y saca el campo por alias
function pick(obj, keys){
  for (const k of keys){
    if (k in obj && obj[k]!=null && String(obj[k]).trim()!=='') return String(obj[k]);
  }
  return '';
}

// Render principal
window.renderPreviewCenope = function renderPreviewCenope(data){
  const div = get('previewTables'); if(!div) return;

  const cols = [
    ['Nro','Nro'],
    ['Grado','Grado'],
    ['Apellido y Nombre','Apellido y Nombre'],
    ['Arma','Arma'],
    ['Unidad/Destino','Unidad/Destino'],
    ['Fecha','Fecha'],
    ['Habitación','Habitación'],
    ['Hospital','Hospital'],
    ['Detalle','Detalle'],
  ];

  const mkEditable = (title, rows, kind)=>{
    const rowsObj = Array.isArray(rows)? rows.filter(r=>r && typeof r==='object') : [];
    const thead = `<thead><tr>${cols.map(([,lab])=>`<th>${esc(lab)}</th>`).join('')}</tr></thead>`;

    const tbody = rowsObj.slice(0,400).map((r, i)=>{
      // Normalización por alias (lo más tolerante posible)
      const grado = pick(r, ['Grado','GRADO']);
      const arma  = pick(r, ['Arma','ARMA','ESP/SER','ARMA / ESP / SER','ARMA / ESP /SER']);
      const unidad= pick(r, [
        'Unidad/Destino','SITUACIÓN DE REVISTA / DESTINO','SITUACION DE REVISTA / DESTINO',
        'DESTINO','Situación de Revista / Destino','Unidad'
      ]);
      let nombre = pick(r, ['Apellido y Nombre','APELLIDO Y NOMBRE','Nombre']);
      let fecha  = toDDMMMYY( pick(r, ['Fecha','FECHA']) );
      let habit  = pick(r, ['Habitación','HABITACIÓN','HABITACION','PISO/HAB/CAMA','PISO / HAB / CAMA']);
      let hosp   = pick(r, ['Hospital','HOSPITAL']);
      let detalle= pick(r, ['Detalle','DETALLE','LUGAR Y FECHA','Lugar y fecha','Lugar','LUGAR']);

      // Limpieza especial para FALLECIDOS (nombre + dirección)
      if (kind==='FALLECIDOS'){
        // si “detalle” viene vacío pero hay un campo lugar y fecha crudo, úsalo
        if (!detalle) detalle = pick(r, ['LUGAR Y FECHA','Lugar y fecha','LUGAR','Lugar']);
        const sepFecha = detalle.match(/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{1,2}\s*(?:ENE|FEB|MAR|ABR|MAY|JUN|JUL|AGO|SEP|OCT|NOV|DIC)\s*\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})/i);
        if (sepFecha && !fecha) fecha = toDDMMMYY(sepFecha[1]);
        if (sepFecha) detalle = detalle.replace(sepFecha[0],'').replace(/^[\s,\-:;]+/,'').trim();
        const limpio = cleanNombre(nombre, detalle);
        nombre = limpio || nombre;
        if (detalle) detalle = `Falleció en – ${detalle}`;
        if (!unidad) unidad = 'Retirado';
      }

      const td = (k,val) => `<td contenteditable="true">${esc(val)}</td>`;
      const celdas = [
        `<td>${String(i+1)}</td>`,
        td('Grado', grado),
        td('Apellido y Nombre', nombre),
        td('Arma', arma),
        td('Unidad/Destino', unidad),
        td('Fecha', fecha),
        td('Habitación', habit),
        td('Hospital', hosp),
        td('Detalle', detalle),
      ].join('');

      return `<tr>${celdas}</tr>`;
    }).join('');

    const empty = (!rowsObj.length) ? `<div class='text-muted'>SIN NOVEDAD</div>` : `
      <div class='table-responsive'>
        <table class='table table-sm table-bordered table-editable'>
          ${thead}<tbody>${tbody}</tbody>
        </table>
      </div>`;

    return `<h6 class='mt-3'>${esc(title)} <span class="estado-badge">(editable)</span></h6>${empty}`;
  };

  div.innerHTML =
      mkEditable('INTERNADOS', data?.internado, 'INTERNADOS')
    + mkEditable('ALTAS',      data?.alta,      'ALTAS')
    + mkEditable('FALLECIDOS', data?.fallecido, 'FALLECIDOS');
};

// ============================
//   ESTADOS DE SISTEMAS (UI)
// ============================
const ESTADOS = ['EN LINEA','SIN SERVICIO','NOVEDAD'];

async function cargarSistemaTabla(divId, catId){
  const div = get(divId); if(!div) return;
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
    div.innerHTML = '<div class="text-muted">No se pudo cargar.</div>';
  }
}

cargarSistemaTabla('tblServ',2);
cargarSistemaTabla('tblIsp',3);
cargarSistemaTabla('tblSitelpar',4);
cargarSistemaTabla('tblDC',5);
cargarSistemaTabla('tblSITM2',6);

// Botón "Confirmar sistemas"
get('btnConfirmSistemas')?.addEventListener('click', (ev)=>{
  const btn = ev.currentTarget;
  sistemasConfirmado = true;
  refreshGenerarEnabled();
  btn.disabled = true;
  btn.classList.remove('btn-outline-primary');
  btn.classList.add('btn-success');
  btn.textContent = 'Sistemas confirmados ✔';
});

// ===== Helpers LTA =====
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

// ===== LTA – Preview y diff =====
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

function renderPreviewLTA(rows){
  const div = get('ltaPreview'); if(!div) return;
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
  const div = get('ltaPreview'); if(!div) return;
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
      <td contenteditable="true" class="ce-nov">${esc((r.novedad||'').replace(/\s{2,}/g,' ').trim())}</td>
      <td contenteditable="true" class="ce-fecha text-nowrap">${esc(r.fecha||hoy)}</td>
      <td contenteditable="true" class="ce-serv text-nowrap">${esc((r.servicio||'').toUpperCase().replace('VHF/HF','HF/VHF'))}</td>
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
  const info = get('ltaDiffInfo');
  if (info) {
    const cnt = rows.reduce((a,r)=>{ a[r.estado||'ACTUALIZADA']=(a[r.estado||'ACTUALIZADA']||0)+1; return a; },{});
    info.textContent = `NUEVAS: ${cnt['NUEVA']||0} | ACTUALIZADAS: ${cnt['ACTUALIZADA']||0} | RESUELTAS: ${cnt['RESUELTA']||0}`;
  }
}

function ltaLeerTablaActual(){
  const tbl = get('tblLTA'); if(!tbl) return [];
  return Array.from(tbl.querySelectorAll('tbody tr')).map(tr=>({
    nodo:     (tr.querySelector('.ce-nodo')?.textContent ?? '').trim(),
    desde:    (tr.querySelector('.ce-desde')?.textContent ?? '').trim(),
    novedad:  (tr.querySelector('.ce-nov')?.textContent ?? '').trim(),
    fecha:    (tr.querySelector('.ce-fecha')?.textContent ?? '').trim(),
    servicio: (tr.querySelector('.ce-serv')?.textContent ?? '').trim(),
    ticket:   (tr.querySelector('.ce-tic')?.textContent ?? '').trim(),
    estado:   tr.querySelector('.sel-estado')?.value || 'ACTUALIZADA'
  }));
}

// ====== GENERAR PARTE ======
get('btnGenerarParte')?.addEventListener('click', async () => {
  const fd = new FormData();
  fd.append('desde',     get('g_desde')?.value || '');
  fd.append('hasta',     get('g_hasta')?.value || '');
  fd.append('oficial',   (get('g_oficial')?.value || '').trim());
  fd.append('suboficial',(get('g_subof')?.value || '').trim());

  const ltaRows = (typeof ltaLeerTablaActual === 'function') ? ltaLeerTablaActual() : [];
  if (ltaRows && ltaRows.length) fd.append('lta_json', JSON.stringify(ltaRows));

  const info = get('parteInfo');
  if (info) info.textContent = 'Generando Parte...';

  try {
    const r   = await fetch(API_GENERAR_PARTE, { method: 'POST', body: fd });
    const raw = await r.text();
    let data;
    try { data = JSON.parse(raw); } 
    catch { throw new Error('Respuesta no JSON de generar_parte: ' + raw.slice(0,400)); }
    if (!data.ok) throw new Error(data.error || 'No se pudo generar el parte');

    const aH = get('lnkParteHTML');
    const aP = get('lnkPartePDF');
    if (aH) { aH.href = data.html; aH.classList.remove('d-none'); }
    if (aP) {
      if (data.pdf) { aP.href = data.pdf; aP.classList.remove('d-none'); }
      else { aP.classList.add('d-none'); }
    }
    if (info) info.textContent = `Parte generado ✔ – Turno ${data.turno || ''}`;
  } catch (err) {
    console.error('[PARTE] generar falló:', err);
    if (get('parteInfo')) get('parteInfo').textContent = 'Error: ' + err.message;
    alert('No se pudo generar el parte:\n' + err.message);
  }
});
