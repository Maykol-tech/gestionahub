"""
Servicio de prospeccion de GestionaHub (GEST-42).

Busca negocios por rubro y zona en OpenStreetMap, que es libre y no necesita claves,
y responde con el formato que espera Laravel (App\\Services\\ProspeccionIaService):

    POST /prospectar  {"rubro": "pollerias", "zona": "Miraflores, Lima", "limite": 20}
    ->  {"prospectos": [{"nombre", "telefono", "email", "rubro", "fuente_url"}]}
"""
import os
import re

import httpx
from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

from rubros import interpretar

NOMINATIM_URL = os.getenv("NOMINATIM_URL", "https://nominatim.openstreetmap.org/search")
OVERPASS_URL = os.getenv("OVERPASS_URL", "https://overpass-api.de/api/interpreter")
TOKEN = os.getenv("PROSPECCION_TOKEN", "")
PAIS = os.getenv("PROSPECCION_PAIS", "pe")
# Nominatim y Overpass piden un nombre de aplicacion identificable
USER_AGENT = "GestionaHub-Prospeccion/1.0"

app = FastAPI(title="GestionaHub · Prospección")
_zonas: dict[str, tuple] = {}


class Pedido(BaseModel):
    rubro: str = Field(min_length=2, max_length=80)
    zona: str = Field(min_length=2, max_length=80)
    limite: int = Field(default=20, ge=1, le=50)


def _cliente() -> httpx.Client:
    return httpx.Client(timeout=45, headers={"User-Agent": USER_AGENT})


def ubicar_zona(cliente: httpx.Client, zona: str) -> tuple:
    """Convierte 'Miraflores, Lima' en un recuadro (sur, oeste, norte, este)."""
    clave = zona.lower().strip()
    if clave in _zonas:
        return _zonas[clave]
    r = cliente.get(NOMINATIM_URL, params={"q": zona, "format": "json", "limit": 1, "countrycodes": PAIS})
    r.raise_for_status()
    datos = r.json()
    if not datos:
        raise HTTPException(422, f"No encontré la zona '{zona}'. Prueba con el distrito y la ciudad, por ejemplo 'Miraflores, Lima'.")
    sur, norte, oeste, este = (float(x) for x in datos[0]["boundingbox"])
    _zonas[clave] = (sur, oeste, norte, este)
    return _zonas[clave]


def armar_consulta(etiquetas, texto_rubro: str, caja: tuple, limite: int) -> str:
    """Consulta de Overpass: negocios con esas etiquetas (o con el rubro en el nombre) dentro de la zona."""
    bbox = ",".join(str(v) for v in caja)
    partes = []
    if etiquetas:
        for clave, valor, extra in etiquetas:
            filtro = f'["{clave}"="{valor}"]' if valor else f'["{clave}"]'
            if extra:
                k, v = extra.split("~")
                filtro += f'["{k}"~"{v}",i]'
            partes.append(f"nwr{filtro}[\"name\"]({bbox});")
    else:
        # Rubro desconocido: busca el texto en el nombre del negocio
        seguro = re.sub(r'[^\w\s]', '', texto_rubro)
        partes.append(f'nwr["name"~"{seguro}",i]["amenity"]({bbox});')
        partes.append(f'nwr["name"~"{seguro}",i]["shop"]({bbox});')
    # Se piden mas de los necesarios porque luego se ordenan (primero los que tienen contacto)
    return f"[out:json][timeout:40];({''.join(partes)});out center tags {limite * 4};"


def contacto(tags: dict, *claves) -> str | None:
    for c in claves:
        if tags.get(c):
            return tags[c].split(";")[0].strip()
    return None


def convertir(elemento: dict, rubro: str) -> dict | None:
    tags = elemento.get("tags", {})
    nombre = (tags.get("name") or "").strip()
    if not nombre:
        return None
    telefono = contacto(tags, "phone", "contact:phone", "mobile", "contact:mobile", "contact:whatsapp")
    email = contacto(tags, "email", "contact:email")
    web = contacto(tags, "website", "contact:website", "contact:facebook", "contact:instagram")
    direccion = " ".join(filter(None, [tags.get("addr:street"), tags.get("addr:housenumber")])) or None
    return {
        "nombre": nombre,
        "telefono": telefono,
        "email": email,
        "rubro": rubro,
        "direccion": direccion,
        "web": web,
        "fuente_url": f"https://www.openstreetmap.org/{elemento['type']}/{elemento['id']}",
    }


def buscar(cliente: httpx.Client, pedido: Pedido) -> list[dict]:
    rubro, etiquetas = interpretar(pedido.rubro)
    caja = ubicar_zona(cliente, pedido.zona)
    r = cliente.post(OVERPASS_URL, data={"data": armar_consulta(etiquetas, pedido.rubro, caja, pedido.limite)})
    r.raise_for_status()

    vistos, resultado = set(), []
    for el in r.json().get("elements", []):
        p = convertir(el, rubro)
        if not p or p["nombre"].lower() in vistos:
            continue
        vistos.add(p["nombre"].lower())
        resultado.append(p)
    # Primero los que tienen telefono o correo: son los que se pueden contactar
    resultado.sort(key=lambda p: (not p["telefono"], not p["email"]))
    return resultado[: pedido.limite]


@app.get("/salud")
def salud():
    return {"ok": True}


@app.post("/prospectar")
def prospectar(pedido: Pedido, authorization: str | None = Header(default=None)):
    if TOKEN and authorization != f"Bearer {TOKEN}":
        raise HTTPException(401, "Token inválido")
    try:
        with _cliente() as cliente:
            return {"prospectos": buscar(cliente, pedido)}
    except httpx.HTTPError:
        raise HTTPException(502, "OpenStreetMap no respondió. Intenta de nuevo en un minuto.")
