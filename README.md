# GestionaHub

Plataforma SaaS multi-negocio para restaurantes y comercios de Perú: pedidos, productos, clientes, delivery y ventas en un solo panel.

**Stack:** Laravel 12 · PHP 8.3 · MySQL 8.4 · Blade + Alpine.js · Docker

## Avance incluido (Sprint 3 · v1.0 MVP)

| Tarea | Qué hace |
|---|---|
| GEST-6 a GEST-11 | Proyecto multi-tenant, tablas SaaS (tenants, planes, suscripciones), registro de negocios, Mercado Pago, superadmin y roles (API en `routes/api.php`) |
| GEST-12 | Login con Sanctum (API) y login del panel con usuario administrador predeterminado |
| GEST-13 y 14 | Menú lateral con los 19 módulos y dashboard con KPIs reales |
| GEST-15 a 17 | CRM de clientes e interacciones |
| GEST-18 | Categorías y productos con variantes (tamaños, presentaciones) |
| GEST-19 | Galería de hasta 8 imágenes por producto, con imagen principal y orden |
| GEST-20 | Control de stock con alertas de mínimo (banner, campanita y aviso al vender) |
| GEST-21 | Pedidos: crear, editar, cancelar, cambiar estado y marcar pagado. Descuenta el stock al vender y lo devuelve al cancelar |
| GEST-22 | Tablero kanban con 4 columnas (Pendiente, En preparación, En camino, Entregado) y arrastrar y soltar |

Los demás módulos del menú (Caja, Reportes, Repartos, Bot WhatsApp, etc.) ya tienen su pantalla y se completan en los siguientes sprints.

## Acceso al panel

- URL: http://localhost:8000
- Correo: `admin@gestionahub.pe`
- Contraseña: `Admin2026!`

El seeder crea este usuario y el negocio de ejemplo "El Clásico" con 12 productos, 12 clientes y pedidos de los últimos 30 días. **Cambia la contraseña antes de publicar en un servidor real.**

## Instalación en Windows con Docker (PowerShell)

Desde la carpeta del proyecto:

```powershell
Copy-Item .env.example .env          # solo la primera vez
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate   # solo la primera vez
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan optimize:clear
```

Abre http://localhost:8000 e ingresa con el usuario de arriba.

Para volver a empezar con los datos de ejemplo:

```powershell
docker compose exec app php artisan migrate:fresh --seed --force
```

## Publicar en un servidor

1. Copia la carpeta al servidor y crea el `.env` con `APP_ENV=production`, `APP_DEBUG=false` y tu `APP_URL`.
2. Levanta solo la configuración base (sin el override de desarrollo):

```bash
docker compose -f docker-compose.yml up -d --build
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan storage:link
```

En producción el contenedor ejecuta las migraciones al arrancar y cachea configuración, rutas y vistas.

## Estructura principal

```
app/Http/Controllers/
  Auth/LoginController.php        Login y cierre de sesión
  DashboardController.php         Inicio, clientes y ayudantes comunes del panel
  ProductosPanelController.php    Productos, variantes, galería, stock y categorías
  PedidosPanelController.php      Pedidos, estados, stock y kanban
  ModulosController.php           Módulos de Control, Presencia y Cuenta
database/seeders/GestionaHubSeeder.php   Planes, roles, negocio demo, admin y datos de ejemplo
resources/views/admin/            Vistas del panel (Blade + Alpine)
resources/views/layouts/          Layout, estilos y menú lateral
routes/web.php                    Rutas del panel (protegidas con login)
```
