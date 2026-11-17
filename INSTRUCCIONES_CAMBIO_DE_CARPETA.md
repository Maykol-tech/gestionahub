# Cómo reemplazar tu carpeta por la versión limpia

Tiempo estimado: 10 minutos. No se borra nada de tu base de datos.

## 1. Guarda la carpeta actual (no la borres todavía)

1. Apaga Docker: en la carpeta actual ejecuta `docker compose down`.
2. Cambia el nombre de la carpeta `gestionahub` a `gestionahub_ANTERIOR`.

## 2. Pon la carpeta nueva fuera de OneDrive

OneDrive sincroniza cada archivo que Docker toca, y por eso el panel tarda en abrir los módulos.

1. Crea `C:\proyectos\` (o cualquier carpeta que no esté dentro de OneDrive, Escritorio ni Documentos).
2. Descomprime `GestionaHub_Limpio_12nov.zip` allí. Quedará `C:\proyectos\gestionahub`.

## 3. Copia lo que es tuyo desde la carpeta anterior

Desde `gestionahub_ANTERIOR` copia a la carpeta nueva:

- el archivo `.env` (tus claves y la conexión a la base de datos);
- la carpeta `.git` (así puedes seguir haciendo commit y push al mismo repositorio);
- `storage/app/public` (las imágenes que subiste), si tienes alguna.

## 4. Levanta el proyecto

Abre una terminal en `C:\proyectos\gestionahub` y ejecuta, en este orden:

```
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan optimize:clear
```

`migrate` solo agrega lo que falta. Además incluye una reparación: las tablas que una versión anterior dejó vacías (por ejemplo `gastos`, que solo tenía id y fechas) se renombran a `*_stub_respaldo` y se crean bien. No se borra ninguna tabla.

Si tu base está vacía y quieres los datos de demostración: `docker compose exec app php artisan db:seed`.

## 5. Prueba

- Panel: http://localhost:8000/login (admin@gestionahub.pe / Admin2026!)
- Landing: http://localhost:8000/landing

## 6. Sube los cambios al repositorio

```
git add -A
git commit -m "Versión limpia y funcional"
git push
```

Esto no necesita `--force`. Git verá que las carpetas de bloques y los archivos duplicados ya no están y los quitará del repositorio en ese commit. Revisa antes la lista en `LO_QUE_SE_QUITA.md`.

Cuando todo funcione, puedes borrar `gestionahub_ANTERIOR`.
