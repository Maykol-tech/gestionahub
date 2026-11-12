{{-- Campana de notificaciones del panel. Incluir en la barra superior del layout. Requiere Alpine y gh.ready --}}
<div x-data="campanaNotif()" class="campana-wrap" style="position:relative;">
    <button type="button" class="btn btn-g" @click="abierto = !abierto" :aria-expanded="abierto" aria-label="Notificaciones" style="position:relative;padding:6px 10px;">
        <span aria-hidden="true">””</span>
        <span x-show="total > 0" x-text="total" style="position:absolute;top:-4px;right:-4px;background:#DC2626;color:#fff;border-radius:99px;font-size:11px;font-weight:700;padding:1px 6px;"></span>
    </button>

    <div x-show="abierto" x-cloak @click.outside="abierto = false" class="panel fade-in"
         style="position:absolute;right:0;top:calc(100% + 8px);width:300px;max-width:90vw;padding:0;z-index:50;">
        <div style="padding:10px 12px;font-weight:700;border-bottom:1px solid var(--border);">Notificaciones</div>
        <template x-if="items.length === 0">
            <div style="padding:14px;font-size:13px;color:var(--fg3);">Todo al día.</div>
        </template>
        <template x-for="it in items" :key="it.tipo">
            <a :href="it.url" style="display:block;padding:10px 12px;border-bottom:1px solid var(--border);color:inherit;text-decoration:none;font-size:13px;" x-text="it.texto"></a>
        </template>
    </div>
</div>

@push('scripts')
<script>
    window.campanaNotif = window.campanaNotif || function () {
        return {
            abierto: false, total: 0, items: [],
            async cargar() {
                try {
                    const r = await fetch(@js(url('admin/notificaciones')), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                    if (!r.ok) return;
                    const d = await r.json();
                    this.total = d.total; this.items = d.items;
                } catch (e) { /* sin conexion: se reintenta en el siguiente ciclo */ }
            },
            init() {
                this.cargar();
                setInterval(() => this.cargar(), 60000);
            },
        };
    };
    gh.ready(() => Alpine.data('campanaNotif', window.campanaNotif));
</script>
@endpush
