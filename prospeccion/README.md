# Servicio de prospección (GEST-42)

Busca negocios por rubro y zona en OpenStreetMap (datos libres, sin claves) y se los entrega a GestionaHub en **Prospectos → Buscar prospectos con IA**.

## Con Docker

Se enciende solo con `docker compose up -d --build`. No hay que configurar nada.

## Con XAMPP

1. Instala Python 3.12 desde python.org (marca "Add python.exe to PATH").
2. En una terminal nueva de VS Code:
   ```
   cd prospeccion
   pip install -r requirements.txt
   python -m uvicorn app:app --port 8090
   ```
3. En el `.env` de GestionaHub agrega `PROSPECCION_IA_URL=http://127.0.0.1:8090` y ejecuta `php artisan optimize:clear`.

Deja esa terminal abierta mientras lo uses.

## Pruebas

```
pip install pytest
python -m pytest tests
```
