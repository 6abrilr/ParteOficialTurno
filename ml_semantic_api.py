from fastapi import FastAPI
from pydantic import BaseModel
from typing import List, Dict, Optional
from sentence_transformers import SentenceTransformer, util
import re

app = FastAPI(title="CENOPE Semantic Helper")
model = SentenceTransformer("sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2")

# ===== Esquema de columnas y sinónimos =====
CANON: Dict[str, List[str]] = {
    "GRADO": ["grado", "jerarquía"],
    "ARMA": ["arma", "esp", "ser", "especialidad"],
    "APELLIDO_NOMBRE": ["apellido y nombre", "apellidos", "nombres", "nombre y apellido"],
    "SITUACION_DESTINO": ["situación de revista / destino", "situacion de revista", "destino", "revista"],
 python -m pip install <paquete>
   "LUGAR_FECHA": ["lugar y fecha", "lugar", "domicilio y fecha", "dirección y fecha", "direccion y fecha"],
    "HABITACION": ["piso / hab / cama", "habitación", "habitacion", "cama", "piso"],
    "FECHA": ["fecha", "fecha de internación", "fecha de alta", "fecha internacion"],
    "HOSPITAL": ["hospital", "sanatorio", "clínica", "clinica"],
    "DETALLE": ["detalle", "observaciones", "comentarios"],
    "PROM": ["prom"],
    "VGM": ["vgm"],
    # CENOPE servicios
    "NODO": ["nodo", "sitio", "sede", "ccig", "equipo"],
    "NOVEDADES": ["novedades", "incidencia", "observaciones servicio", "detalle servicio"],
    "SERVICIO": ["servicio", "hf", "vhf", "uhf", "sat", "f/s", "hf/vhf"],
    "TICKET": ["ticket", "glpi", "mm", "tkt", "inc"],
    "DESDE": ["desde", "vigencia", "inicio", "mes/año"],
    "ESTADO": ["estado", "situación", "status"],
}

# Pre-embed de etiquetas + sinónimos
canon_texts, canon_keys = [], []
for k, syns in CANON.items():
    for s in (syns + [k.replace("_", " ")]):
        canon_texts.append(s.lower())
        canon_keys.append(k)
canon_emb = model.encode(canon_texts, normalize_embeddings=True)

# ===== Utilidades =====
MES = ["ENE","FEB","MAR","ABR","MAY","JUN","JUL","AGO","SEP","OCT","NOV","DIC"]

def fmt_ea_fecha(text: str) -> str:
    if not text: return ""
    s = re.sub(r"\s+", " ", text.strip().upper())
    # dd MMM aa / ddMMMaa / dd/mm/aa / yyyy-mm-dd
    m = re.search(r"(\d{1,2}\s*(ENE|FEB|MAR|ABR|MAY|JUN|JUL|AGO|SEP|OCT|NOV|DIC)\s*\d{2,4})", s)
    if m:
        parts = re.findall(r"\d+|[A-Z]+", m.group(1))
        dd = f"{int(parts[0]):02d}"
        mon = parts[1][:3]
        yy = parts[2][-2:]
        return f"{dd}{mon}{yy}"
    m = re.search(r"(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})", s)
    if m:
        dd = f"{int(m.group(1)):02d}"
        mon = MES[int(m.group(2))-1]
        yy = m.group(3)[-2:]
        return f"{dd}{mon}{yy}"
    m = re.search(r"(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})", s)
    if m:
        dd = f"{int(m.group(3)):02d}"
        mon = MES[int(m.group(2))-1]
        yy = m.group(1)[-2:]
        return f"{dd}{mon}{yy}"
    return ""

def best_header_key(text: str, threshold: float = 0.35):
    q = (text or "").strip().lower()
    if not q:
        return None, 0.0
    q_emb = model.encode([q], normalize_embeddings=True)
    sims = util.cos_sim(q_emb, canon_emb).cpu().numpy()[0]
    i = sims.argmax()
    score = float(sims[i])
    key = canon_keys[i] if score >= threshold else None
    return key, score

ADDRESS_TRIG = [
    " AV", " AV.", " AVENIDA", " CALLE", " RUTA", " PJE", " PASAJE",
    " KM ", " B° ", " BARRIO", " PASO", " DIAG.", " DIAGONAL"
]

def clean_name_with_place(name_cell: str, lugar_cell: Optional[str] = None):
    name = (name_cell or "").strip()
    lugar = (lugar_cell or "").strip()
    if not name:
        return {"nombre": "", "lugar": lugar}
    base_name = name

    # (1) Si ya tenemos LUGAR, y comparte prefijo/sufijo con "name", cortamos ahí
    up_name = " " + name.upper()
    up_lugar = " " + lugar.upper()
    for trig in ADDRESS_TRIG:
        p = up_lugar.find(trig)
        if p > 0:
            key = up_lugar[p:p+14].strip()
            idx = up_name.find(key)
            if idx > 5:
                base_name = base_name[:idx-1].strip()
                break

    # (2) Si no alcanzó, probamos candidatos de corte por separadores comunes
    if base_name == name:
        seps = [" - ", " – ", "; ", ", ", " • ", " · "]
        candidates = []
        for sep in seps:
            if sep in name:
                pos = name.find(sep)
                left, right = name[:pos].strip(), name[pos+len(sep):].strip()
                if len(left) > 2 and len(right) > 2:
                    candidates.append((left, right))
        # Si no hay separadores, probamos palabras de gatillo dentro del propio "name"
        upn = " " + name.upper()
        for trig in ADDRESS_TRIG:
            pos = upn.find(trig)
            if pos > 0:
                left = name[:pos].strip()
                right = name[pos:].strip()
                if len(left) > 2 and len(right) > 2:
                    candidates.append((left, right))

        # Elegimos el split con mejor similitud:
        if candidates:
            # anclas semánticas
            anchor_person = model.encode(
                ["nombre y apellido de una persona", "apellidos y nombres"],
                normalize_embeddings=True
            ).mean(axis=0)
            anchor_place  = model.encode(
                ["dirección o lugar", "domicilio, calle y ciudad"],
                normalize_embeddings=True
            ).mean(axis=0)

            best, best_score = candidates[0], -999.0
            for left, right in candidates:
                e_left  = model.encode([left],  normalize_embeddings=True)
                e_right = model.encode([right], normalize_embeddings=True)
                sc = float(util.cos_sim(e_left, anchor_person)) + float(util.cos_sim(e_right, anchor_place))
                if sc > best_score:
                    best, best_score = (left, right), sc
            base_name = best[0]
            if not lugar:
                lugar = best[1]

    # Limpieza final de rezagos tipo fecha pegada
    base_name = re.sub(r"\b\d{1,2}(ENE|FEB|MAR|ABR|MAY|JUN|JUL|AGO|SEP|OCT|NOV|DIC)\d{2}\b.*$", "", base_name, flags=re.I).strip()
    return {"nombre": base_name, "lugar": lugar}

# ======== Schemas y Endpoints ========
class ClassifyReq(BaseModel):
    text: str

class BatchHeadersReq(BaseModel):
    headers: List[str]
    threshold: float = 0.35

class SplitReq(BaseModel):
    name_cell: str
    lugar_cell: Optional[str] = None

class FechaReq(BaseModel):
    text: str

@app.get("/health")
def health():
    return {"ok": True}

@app.post("/classify_header")
def classify_header(req: ClassifyReq):
    k, sc = best_header_key(req.text)
    return {"key": k, "score": sc}

@app.post("/classify_headers")
def classify_headers(req: BatchHeadersReq):
    out = []
    for h in req.headers:
        k, sc = best_header_key(h, req.threshold)
        out.append({"text": h, "key": k, "score": sc})
    return {"items": out}

@app.post("/best_split_name")
def best_split_name(req: SplitReq):
    res = clean_name_with_place(req.name_cell, req.lugar_cell)
    fecha = fmt_ea_fecha(req.lugar_cell or "")
    return {"nombre": res["nombre"], "lugar": res["lugar"], "fecha": fecha}

@app.post("/extract_fecha")
def extract_fecha(req: FechaReq):
    return {"ea": fmt_ea_fecha(req.text)}
