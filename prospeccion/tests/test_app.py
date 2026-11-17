import json

import httpx
from fastapi.testclient import TestClient

import app as servicio
from rubros import interpretar

NOMINATIM = [{"boundingbox": ["-12.14", "-12.10", "-77.05", "-77.01"]}]
OVERPASS = {"elements": [
    {"type": "node", "id": 1, "tags": {"name": "Pollería Sin Teléfono", "amenity": "restaurant"}},
    {"type": "node", "id": 2, "tags": {"name": "Pollos El Sol", "amenity": "restaurant", "cuisine": "chicken", "phone": "+51 999 111 222", "addr:street": "Av. Larco", "addr:housenumber": "123"}},
    {"type": "way", "id": 3, "tags": {"name": "Pollos El Sol"}},
    {"type": "node", "id": 4, "tags": {"amenity": "restaurant"}},
]}


def falso(peticiones):
    def manejar(req: httpx.Request):
        peticiones.append(req)
        if "nominatim" in str(req.url):
            return httpx.Response(200, json=NOMINATIM)
        return httpx.Response(200, json=OVERPASS)
    return manejar


def preparar(monkeypatch, token=""):
    peticiones = []
    monkeypatch.setattr(servicio, "_cliente", lambda: httpx.Client(transport=httpx.MockTransport(falso(peticiones))))
    monkeypatch.setattr(servicio, "TOKEN", token)
    servicio._zonas.clear()
    return TestClient(servicio.app), peticiones


def test_interpreta_rubros_con_y_sin_tildes():
    assert interpretar("Pollerías")[0] == "Pollería"
    assert interpretar("peluqueria y barberia")[0] == "Salón de belleza"
    assert interpretar("algo raro")[1] is None


def test_devuelve_prospectos_en_el_formato_de_laravel(monkeypatch):
    cliente, peticiones = preparar(monkeypatch)
    r = cliente.post("/prospectar", json={"rubro": "pollerias", "zona": "Miraflores, Lima", "limite": 5})
    assert r.status_code == 200
    prospectos = r.json()["prospectos"]
    # Sin repetidos ni negocios sin nombre, y primero el que tiene telefono
    assert [p["nombre"] for p in prospectos] == ["Pollos El Sol", "Pollería Sin Teléfono"]
    assert prospectos[0]["telefono"] == "+51 999 111 222"
    assert prospectos[0]["rubro"] == "Pollería"
    assert prospectos[0]["fuente_url"] == "https://www.openstreetmap.org/node/2"
    consulta = dict(httpx.QueryParams(peticiones[1].content.decode()))["data"]
    assert '["cuisine"~"chicken",i]' in consulta and "-12.14,-77.05,-12.1,-77.01" in consulta


def test_respeta_el_limite(monkeypatch):
    cliente, _ = preparar(monkeypatch)
    r = cliente.post("/prospectar", json={"rubro": "pollerias", "zona": "Miraflores", "limite": 1})
    assert len(r.json()["prospectos"]) == 1


def test_pide_token_si_esta_configurado(monkeypatch):
    cliente, _ = preparar(monkeypatch, token="secreto")
    assert cliente.post("/prospectar", json={"rubro": "cafe", "zona": "Lima"}).status_code == 401
    ok = cliente.post("/prospectar", json={"rubro": "cafe", "zona": "Lima"}, headers={"Authorization": "Bearer secreto"})
    assert ok.status_code == 200


def test_zona_inexistente_da_mensaje_claro(monkeypatch):
    cliente, _ = preparar(monkeypatch)
    monkeypatch.setattr(servicio, "NOMINATIM", None, raising=False)
    monkeypatch.setattr(servicio, "_cliente", lambda: httpx.Client(transport=httpx.MockTransport(lambda r: httpx.Response(200, json=[]))))
    r = cliente.post("/prospectar", json={"rubro": "cafe", "zona": "Zzzz"})
    assert r.status_code == 422 and "No encontré la zona" in r.json()["detail"]
