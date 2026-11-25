# GestionaHub

Plataforma SaaS multinegocio para negocios pequeños del Perú (restaurantes, pollerías, bodegas, tiendas, salones y gimnasios). Cada negocio maneja desde un solo panel sus ventas, caja, boletas, inventario, tienda online, delivery, clientes y WhatsApp, y solo ve sus propios datos.

**Tecnología:** Laravel 12 · PHP 8.2 o superior · MySQL 8.4 · Blade + Alpine.js · Docker · servicio de prospección en Python (FastAPI)

## Módulos

| Menú | Módulos |
|---|---|
| Principal | Inicio, Pedidos (tablero), Productos y variantes, Clientes, Fidelización (puntos y canje) y Servicios (agenda) |
| Control | Caja del día (apertura y cierre), Reportes, Cobros y Pagos, Repartos y app del repartidor, Producción (recetas y lotes), Publicidad y Proveedores |
| Presencia | Mi Vitrina (plantillas y galería), Tienda pública (carrito, pagos y entregas), Bot WhatsApp (bot y campañas) y Prospectar |
| Cuenta | Sucursales e inventario por sucursal, Planes y facturación, Personalizar, Seguridad y Asistente de inicio |
| Plataforma (superadmin) | Negocios y planes, Leads, Pagos de suscripción y Editor de la landing |

El menú es el mismo del prototipo (GestionaHub_Panel_v3). Seguridad y Asistente de inicio se mantienen por pedido del profesor.

Páginas públicas: landing de GestionaHub (`/landing`), registro con 14 días de prueba (`/registro`), web del negocio (`/web/{negocio}`), tienda online (`/tienda/{negocio}`) y app del repartidor.

### Módulos ocultos por pedido del profesor

Estos módulos no están en el prototipo, así que no aparecen en el menú y sus páginas responden 404. El código, las tablas y las pruebas siguen en el proyecto; no se borró nada.

| Módulo | Tareas |
|---|---|
| Punto de venta | GEST-36, GEST-37 |
| Comprobantes SUNAT (Nubefact) | GEST-38, GEST-39, GEST-64 |
| Mesas con QR y Cocina | GEST-46, GEST-47 |
| Reseñas y Google Reviews | GEST-55, GEST-56 |
| Membresías | GEST-57 |
| Lista de espera | GEST-86 |
| API y conexiones (página del panel) | GEST-62 |

Para volver a mostrarlos, agrega `MODULOS_EXTRA=true` al `.env` y ejecuta `php artisan optimize:clear`.

## Tareas realizadas

Las 119 tareas del tablero del proyecto (GEST-6 a GEST-112 y GEST-116 a GEST-127) están terminadas. Las de los módulos ocultos están listadas arriba.

| Tarea | Descripción | Dónde está en el sistema |
|---|---|---|
| GEST-6 | Configurar proyecto Laravel 12 con estructura multi-tenant | Base del proyecto, `TenantScope` |
| GEST-7 | Crear migraciones de tablas SaaS (tenants, planes, suscripciones) | `database/migrations` |
| GEST-8 | Implementar registro y onboarding de nuevos tenants | `/registro` y Asistente de inicio |
| GEST-9 | Integrar Mercado Pago para cobro de suscripciones | Planes y facturación |
| GEST-10 | Panel superadmin: gestión de tenants y planes | Plataforma > Negocios y planes |
| GEST-11 | Configurar Spatie Laravel Permission con roles del proyecto | Roles admin, vendedor, cajero, repartidor |
| GEST-12 | Login y autenticación con Sanctum | `/login` y API con tokens |
| GEST-13 | Sidebar con 19 módulos y control por plan | Menú lateral del panel |
| GEST-14 | Dashboard principal con KPIs en tiempo real | Inicio |
| GEST-15 | CRUD completo de clientes CRM | Clientes |
| GEST-16 | Importación masiva de clientes desde CSV/Excel | Clientes > Importar |
| GEST-17 | Seguimiento de interacciones por cliente | Clientes > ficha del cliente |
| GEST-18 | CRUD de categorías y productos con variantes | Productos |
| GEST-19 | Galería de imágenes por producto | Productos > imágenes |
| GEST-20 | Control de stock con alertas de mínimo | Productos e Inicio |
| GEST-21 | CRUD de pedidos con cambio de estado | Pedidos |
| GEST-22 | Vista kanban de pedidos por estado | Pedidos > tablero |
| GEST-23 | Asignación de pedido a repartidor | Pedidos y Repartos |
| GEST-24 | Tracking en tiempo real de delivery en mapa Leaflet | Repartos > mapa |
| GEST-25 | Zonas de delivery con costo por zona | Repartos > zonas |
| GEST-26 | Tienda virtual pública por tenant | `/tienda/{negocio}` |
| GEST-27 | Pasarela de pago en tienda virtual (MP + Niubiz) | Tienda > pago |
| GEST-28 | Yape QR y Plin en checkout | Tienda > pago |
| GEST-29 | Integración Meta Cloud API WhatsApp | WhatsApp > conexión |
| GEST-30 | Flujo chatbot: bienvenida, menú, pedidos y CRM | WhatsApp > bot |
| GEST-31 | Sesiones y contexto de conversación WhatsApp | WhatsApp |
| GEST-32 | Panel de conversaciones WhatsApp (inbox) | WhatsApp > bandeja |
| GEST-33 | CRUD de servicios y agenda de citas | Servicios |
| GEST-34 | Reserva de cita por cliente (portal público) | Página pública de reservas |
| GEST-35 | Recordatorio automático de citas por WhatsApp | Servicios |
| GEST-36 | POS: venta rápida con búsqueda de producto | Punto de venta |
| GEST-37 | POS: métodos de pago efectivo, tarjeta, Yape, Plin | Punto de venta |
| GEST-38 | Emisión de boleta y factura electrónica SUNAT | Comprobantes |
| GEST-39 | Notas de crédito y anulaciones SUNAT | Comprobantes |
| GEST-40 | Reportes de ventas por período y módulo | Reportes |
| GEST-41 | Reporte de delivery: tiempos y repartidores | Reportes |
| GEST-42 | Módulo de prospección IA con FastAPI | Servicio `prospeccion/` |
| GEST-43 | Listado y gestión de prospectos scrapeados | Prospectos |
| GEST-44 | Campañas de WhatsApp masivo a segmentos | WhatsApp > campañas |
| GEST-45 | Programación de campañas (fecha y hora) | WhatsApp > campañas |
| GEST-46 | Gestión de mesas y QR por mesa | Mesas y QR |
| GEST-47 | Comandas desde mesa al panel de cocina | Cocina |
| GEST-48 | Gestión de sucursales por tenant | Sucursales |
| GEST-49 | Landing page administrable desde panel | `/landing` y Editor de la landing |
| GEST-50 | SEO con IA para landing (OpenAI) | Editor de la landing |
| GEST-51 | Libro de Reclamaciones INDECOPI en landing | `/landing` |
| GEST-52 | Popup por tiempo en página y salida (landing SaaS) | `/landing` |
| GEST-53 | Chatbot de captura de leads en landing | `/landing` y Leads |
| GEST-54 | Sección de precios con toggle mensual/anual | `/landing` |
| GEST-55 | Sistema de reseñas de clientes | Reseñas |
| GEST-56 | Integración Google Reviews (lectura) | Reseñas |
| GEST-57 | Suscripciones recurrentes para clientes finales | Membresías |
| GEST-58 | Notificaciones en panel (bell icon) | Campana del panel |
| GEST-59 | PWA: manifest y service worker básico | App instalable |
| GEST-60 | Configuración de cuenta y perfil del negocio | Datos del negocio |
| GEST-61 | Configuración de horarios de atención | Datos del negocio |
| GEST-62 | API pública de pedidos para integración externa | API |
| GEST-63 | Exportación de clientes a CSV/Excel | Clientes > Exportar |
| GEST-64 | Integración Nubefact producción | Comprobantes |
| GEST-65 | Tests unitarios y feature tests Laravel | `tests/`: 225 pruebas |
| GEST-66 | Módulo finanzas: registro de ingresos y egresos | Caja y Cobros |
| GEST-67 | Apertura y cierre de caja diaria | Caja |
| GEST-68 | Gráfico de rentabilidad mensual | Reportes |
| GEST-69 | CRUD de proveedores | Proveedores |
| GEST-70 | Órdenes de compra a proveedores | Proveedores |
| GEST-71 | Alerta de stock mínimo por insumo | Producción y Proveedores |
| GEST-72 | Recetas y fichas técnicas de productos | Producción |
| GEST-73 | Cálculo automático de costo de producción | Producción |
| GEST-74 | Lotes de producción con trazabilidad | Producción |
| GEST-75 | Configuración de programa de puntos | Fidelización |
| GEST-76 | Acumulación de puntos en pedidos y ventas POS | Fidelización |
| GEST-77 | Canje de puntos como descuento | Fidelización y Punto de venta |
| GEST-78 | Boletín automático a clientes inactivos | Fidelización |
| GEST-79 | Ranking de clientes frecuentes | Fidelización |
| GEST-80 | CRUD galería de trabajos o portafolio | Mi página > galería |
| GEST-81 | Galería visible en landing del negocio | `/web/{negocio}` |
| GEST-82 | Onboarding: selección de rubro al registrarse | Registro y Asistente de inicio |
| GEST-83 | Catálogo de plantillas por rubro | Mi página |
| GEST-84 | Personalización básica de plantilla (logo, colores, textos) | Mi página |
| GEST-85 | Recuperación de carrito abandonado por WhatsApp | Tienda online |
| GEST-86 | Lista de espera automática en citas canceladas | Lista de espera |
| GEST-87 | Comparativo de ventas mes anterior en reportes | Reportes |
| GEST-88 | Migraciones v3.4: campos para landing SaaS y tienda CMS | `database/migrations` |
| GEST-89 | Landing SaaS: maquetar en Blade desde el prototipo | `/landing` |
| GEST-90 | Leads de la landing al CRM de la plataforma | Plataforma > Leads |
| GEST-91 | Registro con prueba gratis de 14 días | `/registro` |
| GEST-92 | Registro e ingreso con Google | `/registro` y `/login` |
| GEST-93 | Checkout de suscripciones desde la landing | `/landing` > precios |
| GEST-94 | Integrar Niubiz para cobro recurrente de suscripciones | Planes y facturación |
| GEST-95 | Bandeja de pagos manuales (Yape, Plin, transferencia) | Plataforma > Pagos de suscripción |
| GEST-96 | Lead de checkout abandonado | Plataforma > Leads |
| GEST-97 | Bloque de blog WordPress con filtros | `/landing` > blog |
| GEST-98 | Editor de landing SaaS en el panel del superadmin | Plataforma > Editor de la landing |
| GEST-99 | Analítica de la landing (GTM y UTM) | Editor de la landing |
| GEST-100 | Barra de superadmin en la landing | `/landing` con sesión de superadmin |
| GEST-101 | Contenido real y editable de la landing (cifras y datos de la empresa) | Editor de la landing |
| GEST-102 | Tienda CMS: maquetar en Blade desde la plantilla | `/tienda/{negocio}` |
| GEST-103 | Datos de la tienda para la plantilla (TENANT_CONFIG) | Tienda online > configuración |
| GEST-104 | Estado abierto o cerrado y pedidos programados | Tienda online |
| GEST-105 | Variantes de producto y venta por kilo | Productos y Tienda online |
| GEST-106 | Carrito y checkout en 3 pasos | Tienda online |
| GEST-107 | Registrar pedido desde la tienda | Tienda online y Pedidos |
| GEST-108 | Confirmación del pedido por WhatsApp y aviso en el panel | Pedidos |
| GEST-109 | Formas de entrega configurables | Tienda online > entregas |
| GEST-110 | Editor de contenido de la tienda en el CMS | Tienda online > contenido |
| GEST-111 | Ajuste de peso real al despachar productos por kilo | Pedidos |
| GEST-112 | SEO de la tienda del cliente | Productos y Tienda online |
| GEST-116 | Configuración inicial del framework | Laravel 12, `composer.json` |
| GEST-117 | Programación de la capa de seguridad multi-tenant | `TenantScope` y middleware |
| GEST-118 | Vinculación del código a GitHub | Repositorio del curso |
| GEST-119 | Diseño del esquema de base de datos | 88 tablas |
| GEST-120 | Ejecución y verificación de migraciones | `php artisan esquema:revisar` |
| GEST-121 | Creación de modelos y relaciones | `app/Models`: 61 modelos |
| GEST-122 | Programación de la lógica de registro | `RegistroController` |
| GEST-123 | Pruebas de la API | `ApiPedidosTest` |
| GEST-124 | Instalación de dependencias | `composer install` y Docker |
| GEST-125 | Creación de la estructura del Webhook | Webhooks de WhatsApp, Mercado Pago y API |
| GEST-126 | Creación de controlador y rutas | `app/Http/Controllers` y `routes/` |
| GEST-127 | Pruebas de integración y respuesta de datos | `tests/Feature` |

## Acceso de prueba

| Rol | Correo | Contraseña | Qué ve |
|---|---|---|---|
| Administrador | `admin@gestionahub.pe` | `Admin2026!` | Todo el panel del negocio |
| Vendedor | `vendedor@gestionahub.pe` | `Vendedor2026!` | Pedidos, productos, clientes, fidelización, servicios y tienda |
| Cajero | `cajero@gestionahub.pe` | `Cajero2026!` | Caja del día, cobros y pagos, pedidos y reportes |
| Repartidor | `repartidor@gestionahub.pe` | `Repartidor2026!` | Solo su app con los pedidos asignados |

El seeder crea estos usuarios y un negocio de ejemplo. Si la base ya existía, crea los usuarios por rol con `php artisan db:seed --class=RolesDemoSeeder --force` (no borra nada). **Cambia las contraseñas antes de publicar en un servidor real.**

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
