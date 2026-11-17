"""Traduce el rubro que escribe el usuario a etiquetas de OpenStreetMap."""
import re
import unicodedata

# Cada rubro: palabras con las que se reconoce y etiquetas OSM (clave, valor) que lo representan.
RUBROS = [
    (["polleria", "pollo", "pollos a la brasa"], "Pollería", [("amenity", "restaurant", "cuisine~chicken")]),
    (["cevicheria", "ceviche", "marisco"], "Cevichería", [("amenity", "restaurant", "cuisine~seafood")]),
    (["chifa"], "Chifa", [("amenity", "restaurant", "cuisine~chinese")]),
    (["pizzeria", "pizza"], "Pizzería", [("amenity", "restaurant", "cuisine~pizza")]),
    (["restaurante", "restaurant", "comida", "menu"], "Restaurante", [("amenity", "restaurant", None), ("amenity", "fast_food", None)]),
    (["cafeteria", "cafe", "coffee"], "Cafetería", [("amenity", "cafe", None)]),
    (["panaderia", "pasteleria", "reposteria"], "Panadería", [("shop", "bakery", None), ("shop", "pastry", None)]),
    (["bodega", "minimarket", "abarrotes", "tienda de barrio"], "Bodega", [("shop", "convenience", None), ("shop", "supermarket", None)]),
    (["supermercado"], "Supermercado", [("shop", "supermarket", None)]),
    (["peluqueria", "barberia", "salon", "estetica"], "Salón de belleza", [("shop", "hairdresser", None), ("shop", "beauty", None)]),
    (["spa", "masaje", "unas", "manicure"], "Spa", [("shop", "beauty", None), ("leisure", "spa", None), ("shop", "massage", None)]),
    (["gimnasio", "gym", "crossfit", "fitness"], "Gimnasio", [("leisure", "fitness_centre", None), ("leisure", "sports_centre", None)]),
    (["bar", "bares", "discoteca"], "Bar", [("amenity", "bar", None), ("amenity", "pub", None), ("amenity", "nightclub", None)]),
    (["farmacia", "botica"], "Farmacia", [("amenity", "pharmacy", None), ("shop", "chemist", None)]),
    (["ferreteria"], "Ferretería", [("shop", "hardware", None), ("shop", "doityourself", None)]),
    (["veterinaria", "mascota"], "Veterinaria", [("amenity", "veterinary", None), ("shop", "pet", None)]),
    (["lavanderia"], "Lavandería", [("shop", "laundry", None), ("shop", "dry_cleaning", None)]),
    (["ropa", "boutique", "moda"], "Tienda de ropa", [("shop", "clothes", None), ("shop", "boutique", None)]),
    (["zapateria", "calzado"], "Zapatería", [("shop", "shoes", None)]),
    (["libreria", "papeleria", "utiles"], "Librería", [("shop", "books", None), ("shop", "stationery", None)]),
    (["celulares", "tecnologia", "computo"], "Tecnología", [("shop", "mobile_phone", None), ("shop", "electronics", None), ("shop", "computer", None)]),
    (["taller", "mecanico", "automotriz"], "Taller mecánico", [("shop", "car_repair", None)]),
    (["dentista", "odontologia", "dental"], "Dentista", [("amenity", "dentist", None)]),
    (["clinica", "consultorio", "medico"], "Consultorio", [("amenity", "clinic", None), ("amenity", "doctors", None)]),
    (["hotel", "hostal", "hospedaje"], "Hospedaje", [("tourism", "hotel", None), ("tourism", "hostel", None), ("tourism", "guest_house", None)]),
    (["floreria", "flores"], "Florería", [("shop", "florist", None)]),
    (["optica"], "Óptica", [("shop", "optician", None)]),
    (["tienda"], "Tienda", [("shop", None, None)]),
]


def normalizar(texto: str) -> str:
    """Minusculas y sin tildes, para comparar 'Pollería' con 'polleria'."""
    sin_tildes = unicodedata.normalize("NFKD", texto).encode("ascii", "ignore").decode()
    return " ".join(sin_tildes.lower().split())


def interpretar(rubro: str):
    """Devuelve (nombre_del_rubro, etiquetas) o (rubro, None) si no se reconoce."""
    texto = normalizar(rubro)
    for palabras, nombre, etiquetas in RUBROS:
        # La palabra debe empezar igual: "pollerias" cuenta como "polleria"
        if any(re.search(r"\b" + re.escape(p), texto) for p in palabras):
            return nombre, etiquetas
    return rubro.strip(), None
