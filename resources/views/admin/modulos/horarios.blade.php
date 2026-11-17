@extends('layouts.app')

@section('titulo', 'Horarios de atención')
@section('subtitulo', 'Cuándo atiende tu negocio. Se muestra en tu tienda y en el portal de citas')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .hora-fila { display:grid; grid-template-columns: 160px 110px 1fr 1fr; gap:12px; align-items:center; padding:10px 0; border-top:1px dashed var(--border); }
        .hora-fila:first-child { border-top:none; }
        .chip { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; padding:4px 12px; border-radius:99px; }
        @media (max-width: 640px) { .hora-fila { grid-template-columns: 1fr 1fr; } }
    </style>
@endpush

@section('contenido')
<div x-data="horarios(@js($horarios))">
    <div class="panel fade-in" style="padding:18px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <span class="chip" :style="abiertoAhora ? 'background:#DCFCE7;color:#15803D' : 'background:#E2E8F0;color:#475569'" x-text="estadoTexto"></span>
            <span style="font-size:13px;color:var(--fg3);">Estado según la hora actual y el horario guardado</span>
        </div>

        <template x-for="(h, i) in dias" :key="h.dia">
            <div class="hora-fila row-in" :style="'--d:' + (i * 30) + 'ms'">
                <div style="font-weight:600;" x-text="h.nombre"></div>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
                    <input type="checkbox" x-model="h.activo"> <span x-text="h.activo ? 'Abierto' : 'Cerrado'"></span>
                </label>
                <div>
                    <label class="lbl" :for="'abre-' + h.dia">Abre</label>
                    <input class="inp" type="time" :id="'abre-' + h.dia" x-model="h.abre" :disabled="!h.activo">
                </div>
                <div>
                    <label class="lbl" :for="'cierra-' + h.dia">Cierra</label>
                    <input class="inp" type="time" :id="'cierra-' + h.dia" x-model="h.cierra" :disabled="!h.activo">
                </div>
            </div>
        </template>

        <div class="ferr" x-show="error" x-text="error"></div>
        <button class="btn btn-p" type="button" style="margin-top:14px;" :disabled="guardando" @click="guardar()" x-text="guardando ? 'Guardando…' : 'Guardar horarios'"></button>
    </div>
</div>

@push('scripts')
<script>
    window.horarios = window.horarios || function (dias) {
        const url = @js(url('admin/horarios'));
        const ahora = () => {
            const d = new Date();
            const hh = String(d.getHours()).padStart(2, '0');
            const mm = String(d.getMinutes()).padStart(2, '0');
            return { dia: d.getDay(), hora: hh + ':' + mm };
        };
        return {
            dias, error: '', guardando: false,
            get abiertoAhora() {
                const a = ahora();
                const h = this.dias.find(x => x.dia === a.dia);
                return !!(h && h.activo && a.hora >= h.abre && a.hora < h.cierra);
            },
            get estadoTexto() { return this.abiertoAhora ? 'Abierto ahora' : 'Cerrado ahora'; },

            async guardar() {
                this.error = '';
                for (const h of this.dias) {
                    if (h.activo && h.abre >= h.cierra) { this.error = h.nombre + ': la hora de cierre debe ser después de la de apertura.'; return; }
                }
                this.guardando = true;
                const r = await gh.api(url, { method: 'POST', body: { horarios: this.dias } });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { this.error = r.data.message || 'No se pudieron guardar los horarios.'; }
            },
        };
    };
    gh.ready(() => Alpine.data('horarios', window.horarios));
</script>
@endpush
@endsection
