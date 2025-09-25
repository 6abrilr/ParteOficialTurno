// ===== Endpoints =====
const API_NOV     = '../php/api_novedades.php';
const API_PARTE   = '../php/api_parte.php';
const API_CENOPE  = '../php/import_cenope.php';
const API_LTA     = '../php/import_lta_docx.php';
const API_SIST    = '../php/api_sistemas.php';

// ===== Helpers =====
const qs  = (id) => document.getElementById(id);
const fmt = (d) => new Date(d).toLocaleString('es-AR');
const esc = (s) => String(s ?? '')
  .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
  .replaceAll('"','&quot;').replaceAll("'","&#039;");

// ===== Fecha por defecto: HOY 08:00 → MAÑANA 08:00 =====
(function setDefaultDates0800(){
  const fDesde = qs('desde'), fHasta = qs('hasta');
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

// ===== Guardar encabezado (fechas + firmas) =====
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
    const a = qs('linkParte');
    if (a) {
      a.href = `parte.php?desde=${encodeURIComponent(payload.fecha_desde)}&hasta=${encodeURIComponent(payload.fecha_hasta)}`;
      a.classList.remove('d-none');
    }
    alert('Encabezado guardado ✔');
  }catch(err){
    alert('Error al guardar encabezado: ' + err.message);
  }
});

// ===== Novedades: cargar pendientes (oculta resueltas) =====
async function cargarPendientes(){
  if (!qs('tblNovedades')) return;
  try{
    const r = await fetch(`${API_NOV}?action=lista`);
    const data = await r.json();
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
        <td class="text-nowrap">
          <button class="btn btn-sm btn-outline-success" data-resolver="${n.id}">Resolver</button>
        </td>`;
      tb.appendChild(tr);
    });
    tb.querySelectorAll('[data-resolver]').forEach(b=>{
      b.onclick = async ()=>{
        await fetch(`${API_NOV}?action=resolver`,{
          method:'POST', body: JSON.stringify({id:+b.dataset.resolver})
        });
        cargarPendientes();
      };
    });
    const ts = qs('ts');
    if (ts) ts.textContent = 'Actualizado: ' + new Date().toLocaleString('es-AR');
  }catch(e){
    console.error(e);
  }
}
cargarPendientes();

// ===== Form de alta/edición de novedad =====
qs('btnNuevo')?.addEventListener('click', ()=>{
  ['id','titulo','descripcion','servicio','ticket','unidad_txt','usuario'].forEach(i=>{ if(qs(i)) qs(i).value=''; });
  if (qs('categoria_id')) qs('categoria_id').value = 1;
  if (qs('prioridad'))    qs('prioridad').value    = 'MEDIA';
});

qs('frmNovedad')?.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const pay = {
    id: qs('id')?.value,
    titulo: qs('titulo')?.value.trim(),
    descripcion: qs('descripcion')?.value.trim(),
    categoria_id: parseInt(qs('categoria_id')?.value ?? '1',10),
    unidad_id: null, // si luego mapeás unidad_txt → unidad_id, actualizar aquí
    servicio: (qs('servicio')?.value || '').trim(),
    ticket: (qs('ticket')?.value || '').trim(),
    prioridad: qs('prioridad')?.value || 'MEDIA',
    usuario: (qs('usuario')?.value || '').trim()
  };
  const accion = pay.id ? 'actualizar' : 'crear';
  await fetch(`${API_NOV}?action=${accion}`, {method:'POST', body: JSON.stringify(pay)});
  cargarPendientes();
  qs('btnNuevo')?.click();
});

// ===== Generar / Abrir Parte =====
qs('frmParte')?.addEventListener('submit', (e)=>{
  e.preventDefault();
  const d = qs('desde').value;
  const h = qs('hasta').value;
  const a = qs('linkParte');
  if (a) {
    a.href = `parte.php?desde=${encodeURIComponent(d)}&hasta=${encodeURIComponent(h)}`;
    a.classList.remove('d-none');
    a.click();
  }
});

// ==========================
//   IMPORTADOR CENOPE (PDF)
// ==========================
qs('btnPrevCenope')?.addEventListener('click', async ()=>{
  const f = qs('pdfCenope')?.files?.[0];
  if(!f) return alert('Elegí el PDF del CENOPE');
  const fd = new FormData(); fd.append('pdf', f); fd.append('dry','1');
  if (qs('cenopeInfo')) qs('cenopeInfo').textContent = 'Procesando PDF (previsualización)...';
  try{
    const r = await fetch(API_CENOPE,{method:'POST', body: fd});
    const data = await r.json();
    if(!data.ok) throw new Error(data.error||'Error al procesar');
    if (qs('cenopeInfo')) qs('cenopeInfo').textContent =
      `Internados: ${data.internado.length} | Altas: ${data.alta.length} | Fallecidos: ${data.fallecido.length}`;
    const btn = qs('btnImpCenope'); if (btn) btn.disabled = false;
    renderPreviewCenope(data);
  }catch(err){
    if (qs('cenopeInfo')) qs('cenopeInfo').textContent = 'Error: '+ err.message;
  }
});

qs('btnImpCenope')?.addEventListener('click', async ()=>{
  const f = qs('pdfCenope')?.files?.[0];
  if(!f) return;
  const fd = new FormData(); fd.append('pdf', f); fd.append('dry','0');
  if (qs('cenopeInfo')) qs('cenopeInfo').textContent = 'Importando a la base...';
  try{
    const r = await fetch(API_CENOPE,{method:'POST', body: fd});
    const data = await r.json();
    if(!data.ok) throw new Error(data.error||'Error al importar');
    if (qs('cenopeInfo')) qs('cenopeInfo').textContent =
      `Importado. Internados: ${data.internado} | Altas: ${data.alta} | Fallecidos: ${data.fallecido}`;
  }catch(err){
    if (qs('cenopeInfo')) qs('cenopeInfo').textContent = 'Error: '+ err.message;
  }
});

function renderPreviewCenope(data){
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
}

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

    // Guardado automático
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

// ==========================
//   IMPORTADOR LTA (DOCX)
// ==========================
qs('btnPrevLTA')?.addEventListener('click', async ()=>{
  const f = qs('docxLta')?.files?.[0];
  if(!f) return alert('Elegí el DOCX del LTA');
  const fd = new FormData(); fd.append('docx', f); fd.append('dry','1');
  if (qs('ltaInfo')) qs('ltaInfo').textContent = 'Procesando DOCX (previsualización)...';
  try{
    const r = await fetch(API_LTA,{method:'POST', body: fd});
    const data = await r.json();
    if(!data.ok) throw new Error(data.error||'Error al procesar DOCX');
    qs('ltaInfo').textContent = `Internados: ${data.internado.length} | Altas: ${data.alta.length} | Fallecidos: ${data.fallecido.length}`;
    qs('btnImpLTA').disabled = false;
    qs('ltaPreview').textContent = JSON.stringify(data, null, 2).slice(0,2000)+'...';
  }catch(err){
    qs('ltaInfo').textContent = 'Error: '+ err.message;
  }
});

qs('btnImpLTA')?.addEventListener('click', async ()=>{
  const f = qs('docxLta')?.files?.[0];
  if(!f) return;
  const fd = new FormData(); fd.append('docx', f); fd.append('dry','0');
  qs('ltaInfo').textContent = 'Importando a la base...';
  try{
    const r = await fetch(API_LTA,{method:'POST', body: fd});
    const data = await r.json();
    if(!data.ok) throw new Error(data.error||'Error al importar DOCX');
    qs('ltaInfo').textContent = `Importado. Internados: ${data.internado} | Altas: ${data.alta} | Fallecidos: ${data.fallecido}`;
  }catch(err){
    qs('ltaInfo').textContent = 'Error: '+ err.message;
  }
});
