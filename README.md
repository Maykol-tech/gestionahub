# GestionaHub

Plataforma SaaS multinegocio para negocios pequeños del Perú (restaurantes, pollerías, bodegas, tiendas, salones y gimnasios). Cada negocio maneja desde un solo panel sus ventas, caja, boletas, inventario, tienda online, delivery, clientes y WhatsApp, y solo ve sus propios datos.

**Tecnología:** Laravel 12 · PHP 8.2 o superior · MySQL 8.4 · Blade + Alpine.js · Docker · servicio de prospección en Python (FastAPI)

## Módulos

| Menú | Módulos |
|---|---|
| Principal | Inicio, Pedidos (tablero), Productos y variantes, Clientes, Fidelización (puntos y canje), Membresías, Servicios (agenda) y Lista de espera |
| Control | Punto de venta, Caja (apertura y cierre), Comprobantes SUNAT (Nubefact), Cobros y pagos, Reportes, Repartos y app del repartidor, Mesas con QR, Cocina, Producción (recetas y lotes), Proveedores y Publicidad |
| Presencia | Mi página (plantillas y galería), Tienda online (carrito, pagos y entregas), Reseñas, WhatsApp (bot y campañas) y Prospectos |
| Cuenta | Datos del negocio, Seguridad, Sucursales e inventario por sucursal, Planes y facturación, API y Asistente de inicio |
| Plataforma (superadmin) | Negocios y planes, Leads, Pagos de suscripción y Editor de la landing |

Páginas públicas: landing de GestionaHub (`/landing`), registro con 14 días de prueba (`/registro`), web del negocio (`/web/{negocio}`), tienda online (`/tienda/{negocio}`), carta por QR de mesa, membresías y app del repartidor.

## Tareas realizadas

| Tareas | Qué se hizo |
|---|---|
| GEST-6 a 11 | Base multinegocio: negocios, planes y suscripciones, registro de negocios, Mercado Pago, superadmin, roles y API |
| GEST-12 | Login y seguridad: intentos permitidos, bloqueo e historial de accesos |
| GEST-13 y 14 | Menú lateral con todos los módulos y panel de inicio con indicadores |
| GEST-15 a 17 | Clientes e interacciones |
| GEST-18 a 20 | Productos, categorías, galería de imágenes y stock con alertas de mínimo |
| GEST-21 a 23 | Pedidos, estados y tablero |
| GEST-24 y 25 | Repartos: app del repartidor, mapa y zonas de entrega |
| GEST-26 a 28 | Tienda online por negocio y pagos en línea |
| GEST-29 a 32 | WhatsApp: conexión, bot, conversaciones y bandeja |
| GEST-33 a 35 | Servicios: agenda, reserva del cliente y recordatorio |
| GEST-36 y 37 | Punto de venta: venta rápida y métodos de pago |
| GEST-38 y 39 | Comprobantes SUNAT: boleta, factura, notas de crédito y anulaciones |
| GEST-40 y 41 | Reportes de ventas, delivery y repartidores, con exportación a Excel |
| GEST-42 y 43 | Prospección de clientes (servicio en Python) |
| GEST-44 y 45 | Campañas de WhatsApp y su programación |
| GEST-46 y 47 | Mesas con QR y comandas a cocina |
| GEST-48 | Sucursales con stock propio y traslados |
| GEST-49 a 54 | Landing: funciones, precios, preguntas, chat, aviso emergente y Libro de Reclamaciones |
| GEST-55 y 56 | Reseñas propias y de Google |
| GEST-57 | Membresías para clientes |
| GEST-58 y 59 | Notificaciones y aplicación instalable en el celular |
| GEST-60 y 61 | Datos del negocio y horarios de atención |
| GEST-62 | API con tokens y avisos de cambios en pedidos |
| GEST-63 | Exportación de clientes a Excel |
| GEST-64 | Nubefact en producción |
| GEST-66 y 67 | Caja: ingresos, egresos, apertura y cierre |
| GEST-68 | Rentabilidad mensual |
| GEST-69 a 71 | Proveedores, órdenes de compra y alerta de insumos |
| GEST-72 a 74 | Producción: recetas, costo de producción y lotes |
| GEST-75 a 79 | Fidelización: puntos, canje, boletín a clientes inactivos y ranking |
| GEST-80 a 84 | Mi página: galería, rubro, plantillas, logo y colores |
| GEST-85 | Carrito abandonado |
| GEST-86 | Lista de espera |
| GEST-87 | Comparación con el periodo anterior |
| GEST-88 y 97 a 99 | Editor de la landing: textos, precios, testimonios, blog y Google Tag Manager |
| GEST-89 | Botones de prueba gratis |
| GEST-90 y 96 | Leads y origen de campaña |
| GEST-91 y 92 | Registro con 14 días de prueba y con Google |
| GEST-93 a 95 | Pago del plan, Niubiz recurrente y pago manual |
| GEST-100 | Barra del superadmin sobre la landing |
| GEST-101 | Contenido real y editable de la landing (cifras y datos de la empresa) |
| GEST-102 y 103 | Ficha del producto y configuración de la tienda |
| GEST-104 | Horario de la tienda y pedidos programados |
| GEST-105 | Variantes y venta por kilo |
| GEST-106 y 107 | Carrito, pasos de compra y pedido registrado |
| GEST-108 | Aviso del pedido nuevo |
| GEST-109 | Formas de entrega |
| GEST-110 | Editor de contenido de la tienda |
| GEST-111 | Peso real en productos por kilo |
| GEST-112 | SEO del producto (título y descripción para Google) |

## Acceso de prueba

- Correo: `admin@gestionahub.pe`
- Contraseña: `Admin2026!`

El seeder crea este usuario y un negocio de ejemplo. **Cambia la contraseña antes de publicar en un servidor real.**

## Instalación con Docker (Windows, PowerShell)

Desde la carpeta del proyecto:

```
Copy-Item .env.example .env          # solo la primera vez
docker compose up -d --build
docker compose exec app php artisan key:generate   # solo la primera vez
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan storage:link
```

Abre http://localhost:8000. Para revisar que la base de datos tenga todas las tablas y columnas:

```
docker compose exec app php artisan esquema:revisar
```

Docker levanta cuatro contenedores: la app, la cola de tareas, MySQL y el servicio de prospección.

## Instalación con XAMPP

1. Enciende MySQL en XAMPP y crea la base `gestionahub`.
2. Copia `.env.example` a `.env` y pon `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_USERNAME=root` y `DB_PASSWORD=` (vacío).
3. Ejecuta:

```
composer install
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan serve --port=8001
```

Abre http://127.0.0.1:8001. Las migraciones solo agregan tablas y columnas; no borran datos.

## Pruebas

```
php artisan test
```

Hay pruebas automáticas de cada módulo: entre otras, que un negocio no vea los datos de otro, que un pago con tarjeta no se acepte si el monto no coincide y que el stock se descuente y se devuelva bien.

## Claves de servicios externos

Sin claves, el sistema funciona en modo de prueba. Para producción, completa en el `.env` las de Mercado Pago, Niubiz, Nubefact, WhatsApp Business (Meta) y Google. La lista está en `ENV_AGREGAR.txt`.

## Publicar en un servidor

1. Crea el `.env` con `APP_ENV=production`, `APP_DEBUG=false`, tu `APP_URL` y una `APP_KEY` nueva.
2. Ejecuta `composer install --no-dev`, `php artisan migrate --force` y `php artisan storage:link`.
3. Activa la tarea programada (`php artisan schedule:run` cada minuto) y el trabajador de la cola (`php artisan queue:work`).