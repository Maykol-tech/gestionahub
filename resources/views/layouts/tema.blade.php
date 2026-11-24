{{-- Colores, tipografía y modo (claro/oscuro) elegidos en Personalizar (se guardan por negocio) --}}
@php
    $tema = \App\Support\AjustesNegocio::leer((int) (auth()->user()?->tenant_id ?? 1));
    $fuentes = ['Inter' => 'Inter', 'DM Sans' => 'DM+Sans', 'Nunito' => 'Nunito', 'Poppins' => 'Poppins'];
    $fuente = isset($fuentes[$tema['panel_fuente'] ?? '']) ? $tema['panel_fuente'] : 'Inter';
    $modo = in_array($tema['panel_modo'] ?? '', ['claro', 'oscuro', 'sistema'], true) ? $tema['panel_modo'] : 'claro';
@endphp
@if ($fuente !== 'Inter')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ $fuentes[$fuente] }}:wght@400;500;600;700;800&display=swap">
@endif
<script>
    (function () {
        var m = @js($modo), d = document.documentElement;
        d.dataset.modo = m;
        d.dataset.theme = m === 'sistema' ? (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'oscuro' : 'claro') : m;
    })();
</script>
<style id="gh-tema">
:root{--nav-bg:{{ $tema['color_menu'] }};--cta:{{ $tema['color_accion'] }};--fuente:'{{ $fuente }}';}
body{font-family:var(--fuente),'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;}
.btn-p:hover{background:var(--cta);filter:brightness(.93);}
/* Modo oscuro (Personalizar → Modo de visualización) */
html[data-theme="oscuro"]{color-scheme:dark;
  --bg:#0F1623;--surface:#161F2E;--surface2:#1C2738;--surface3:#1A2433;--surface-2:#1C2738;--border:#2A3648;--bd:#2A3648;
  --fg:#E6EBF2;--fg2:#B4C0D0;--fg3:#7D8BA0;--acl:#1E2B47;--ctl:#3A2614;--greenl:#12301F;--ambl:#33270F;--redl:#3A1A1A;--card:#161F2E;}
html[data-theme="oscuro"] .panel,html[data-theme="oscuro"] .stat,html[data-theme="oscuro"] .card,html[data-theme="oscuro"] .modal,
html[data-theme="oscuro"] .top,html[data-theme="oscuro"] header,html[data-theme="oscuro"] .inp,html[data-theme="oscuro"] .btn-g,
html[data-theme="oscuro"] .dd,html[data-theme="oscuro"] .tab-btn,html[data-theme="oscuro"] .icon-btn,html[data-theme="oscuro"] .dropdown,
html[data-theme="oscuro"] .pager button,html[data-theme="oscuro"] .chip,html[data-theme="oscuro"] .rk,html[data-theme="oscuro"] .met-card,
html[data-theme="oscuro"] .prod-card,html[data-theme="oscuro"] .kcard,html[data-theme="oscuro"] .khd,html[data-theme="oscuro"] .kmenu,html[data-theme="oscuro"] .seg,
html[data-theme="oscuro"] .plan,html[data-theme="oscuro"] .suc,html[data-theme="oscuro"] .shop-card,html[data-theme="oscuro"] .as-item,html[data-theme="oscuro"] .pf-sug,html[data-theme="oscuro"] .bot-stats>div,html[data-theme="oscuro"] .receta-card,html[data-theme="oscuro"] .nivel-card,html[data-theme="oscuro"] .search .inp{background-color:var(--surface)!important;color:var(--fg);border-color:var(--border);}
html[data-theme="oscuro"] .tbl th{background:var(--surface2)!important;color:var(--fg3);}
html[data-theme="oscuro"] .tbl td{border-color:var(--border);}
html[data-theme="oscuro"] [style*="background:#fff"],html[data-theme="oscuro"] [style*="background: #fff"],html[data-theme="oscuro"] [style*="background:#FFF"]{background:var(--surface)!important;}
html[data-theme="oscuro"] .m-efe,html[data-theme="oscuro"] .sa-kpi:not(.sa-mrr),html[data-theme="oscuro"] .alerta-stock{background-color:var(--surface2)!important;color:var(--fg);}
html[data-theme="oscuro"] .pc-img{background-color:var(--surface2);}
html[data-theme="oscuro"] .ia-box{background:#17233A!important;border-color:#2B3F66!important;}
html[data-theme="oscuro"] .content,html[data-theme="oscuro"] body{background:var(--bg)!important;}
html[data-theme="oscuro"] .tbl tbody tr:hover td{background:var(--surface2)!important;}
html[data-theme="oscuro"] .pg-list a,html[data-theme="oscuro"] .pg-list span{background:var(--surface);border-color:var(--border);color:var(--fg2);}
html[data-theme="oscuro"] .inp{border-color:var(--border)!important;}
html[data-theme="oscuro"] .tbl-card{background:var(--surface);}
html[data-theme="oscuro"] .k-ing,html[data-theme="oscuro"] .m-tar{background:var(--acl)!important;}
html[data-theme="oscuro"] .k-egr{background:var(--redl)!important;}
html[data-theme="oscuro"] .m-dig{background:var(--greenl)!important;}
html[data-theme="oscuro"] .cita-pasada td{background:var(--ambl)!important;}
html[data-theme="oscuro"] .ent-fila{background:var(--surface2)!important;}
html[data-theme="oscuro"] img{filter:brightness(.92);}
</style>
