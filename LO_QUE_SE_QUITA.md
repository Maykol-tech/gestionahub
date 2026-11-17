# Lo que la carpeta limpia ya no trae

Comparado con tu repositorio actual (commit 88f1c00). Nada de esto borra datos: las tablas siguen en tu base de datos.

## Carpetas de bloques y respaldos (copias sueltas que causaban choques) (13)

- `GestionaHub_Bloque1_CRUDs_Demo_v1/`
- `GestionaHub_Bloque1_Seguridad_Excel/`
- `GestionaHub_Bloque2_Sucursales_Mesas_Cocina_Prospectos_v1/`
- `GestionaHub_Bloque2_Sucursales_Mesas_Cocina_Prospectos_v2/`
- `GestionaHub_Bloque3_Landing_SEO_v1/`
- `GestionaHub_ModuloA_Limpieza_v1/`
- `GestionaHub_Parte1_ModuloA_Seguridad/`
- `GestionaHub_Proveedores_CRUD/`
- `GestionaHub_Reportes_GEST40_41/`
- `GestionaHub_Reportes_GEST40_41_v2/`
- `respaldo_parte1_20261110_114705/`
- `respaldo_reportes_20261110_150616/`
- `respaldo_reportes_20261110_151008/`

## Controladores duplicados o vacíos de DeepSeek (24)

- `app/Http/Controllers/.php`
- `app/Http/Controllers/Admin/LandingEditorController.php`
- `app/Http/Controllers/Admin/MesasController.php`
- `app/Http/Controllers/Api/PedidoApiController.php`
- `app/Http/Controllers/BotMenusController.php`
- `app/Http/Controllers/BotWhatsappController.php`
- `app/Http/Controllers/CajaController.php`
- `app/Http/Controllers/ClientesPanelController.php`
- `app/Http/Controllers/CobrosController.php`
- `app/Http/Controllers/ConfiguracionController.php`
- `app/Http/Controllers/EntregasController.php`
- `app/Http/Controllers/FinanzasController.php`
- `app/Http/Controllers/PagosManualesController.php`
- `app/Http/Controllers/PersonalizarController.php`
- `app/Http/Controllers/PlanesController.php`
- `app/Http/Controllers/PopupEditorController.php`
- `app/Http/Controllers/ProspectarController.php`
- `app/Http/Controllers/ProveedoresController.php`
- `app/Http/Controllers/RepartidoresController.php`
- `app/Http/Controllers/ResenaController.php`
- `app/Http/Controllers/SucursalesController.php`
- `app/Http/Controllers/VitrinaController.php`
- `app/Http/Controllers/WhatsappWebhookController.php`
- `app/Services/WhatsappBotService.php`

## Modelos duplicados (apuntaban a tablas que no usa el sistema) (6)

- `app/Models/Horario.php`
- `app/Models/LandingConfig.php`
- `app/Models/Lead.php`
- `app/Models/PlanSaaS.php`
- `app/Models/Reclamacion.php`
- `app/Models/WhatsappConversation.php`

## Migraciones duplicadas o "stub" de DeepSeek (23)

- `database/migrations/2026_11_10_139095_add_logo_url_to_tenants_table.php`
- `database/migrations/2026_11_10_333337_crear_tablas_gestion_admin.php`
- `database/migrations/2026_11_10_338417_crear_gastos_sucursales.php`
- `database/migrations/2026_11_10_441050_soft_deletes_repartidores_proveedores.php`
- `database/migrations/2026_11_10_530795_landing_leads_oferta.php`
- `database/migrations/2026_11_10_555984_pedidos_canal_tienda.php`
- `database/migrations/2026_11_10_734755_repartos_zonas_tracking.php`
- `database/migrations/2026_11_10_738928_tienda_pagos_saas.php`
- `database/migrations/2026_11_10_867628_pos_facturacion_modulo_a.php`
- `database/migrations/2026_11_10_996481_repartidores_placa.php`
- `database/migrations/2026_11_11_10i14_create_or_update_repartidores_table.php`
- `database/migrations/2026_11_11_10i34_create_whatsapp_conversations_table.php`
- `database/migrations/2026_11_11_15i47_add_canal_oferta_to_leads.php`
- `database/migrations/2026_11_11_15i47_add_popup_fields_to_landing_configs.php`
- `database/migrations/2026_11_11_22i54_add_performance_indexes.php`
- `database/migrations/2026_11_11_23i18_create_horarios_table.php`
- `database/migrations/2026_11_11_23i20_add_chatbot_fields_to_landing_leads.php`
- `database/migrations/2026_11_11_23i4_create_notifications_table.php`
- `database/migrations/2026_11_11_23i4_create_resenas_table.php`
- `database/migrations/2026_11_11_9i14_create_landing_configs_table.php`
- `database/migrations/2026_11_11_9i28_create_leads_table.php`
- `database/migrations/2026_11_11_9i28_create_reclamaciones_table.php`
- `database/migrations/2026_11_11_9i6_create_whatsapp_conversations_table.php`

## Vistas sueltas reemplazadas por las de admin/modulos (20)

- `resources/views/admin/cocina.blade.php`
- `resources/views/admin/configuracion.blade.php`
- `resources/views/admin/fidelizacion.blade.php`
- `resources/views/admin/finanzas.blade.php`
- `resources/views/admin/galeria.blade.php`
- `resources/views/admin/mesas.blade.php`
- `resources/views/admin/mesas_qr.blade.php`
- `resources/views/admin/modulos/mesas_modales.blade.php`
- `resources/views/admin/partials/campana.blade.php`
- `resources/views/admin/preparacion.blade.php`
- `resources/views/admin/produccion.blade.php`
- `resources/views/admin/proveedores.blade.php`
- `resources/views/admin/repartidores.blade.php`
- `resources/views/admin/resenas.blade.php`
- `resources/views/admin/servicios.blade.php`
- `resources/views/admin/sucursales.blade.php`
- `resources/views/landing/index.blade.php`
- `resources/views/landing/reclamaciones.blade.php`
- `resources/views/sitemap.blade.php`
- `resources/views/superadmin/popup_editor.blade.php`

## Otros (4)

- `INSTRUCCIONES.txt`
- `app/Http/Requests/Auth/LoginRequest.php`
- `database/seeders/PlanesSaaSSeeder.php`
- `public/manifest.json`

## Sobre las migraciones

Las migraciones de DeepSeek tenían nombres con fechas inventadas (por ejemplo `2026_11_11_9i28`, con una letra dentro de la hora) y varias solo creaban la tabla con `id` y fechas. Por eso módulos como Caja fallaban: la tabla `gastos` existía pero vacía de columnas, y la migración real se saltaba.

La carpeta limpia trae las migraciones originales y una nueva, `2026_11_12_110000_reparar_tablas_vacias_deepseek.php`, que arregla eso sin borrar nada. Lo probé así: ejecuté tus migraciones actuales en una base vacía, luego `migrate` con la carpeta limpia, y el esquema resultante quedó igual al de una instalación limpia (0 tablas o columnas faltantes).

Las tablas que solo creó DeepSeek (`leads`, `landing_configs`, `reclamaciones`, `horarios`, `whatsapp_conversations`, `notifications`) se quedan en tu base sin usarse. No molestan; si algún día quieres, se pueden borrar a mano.
