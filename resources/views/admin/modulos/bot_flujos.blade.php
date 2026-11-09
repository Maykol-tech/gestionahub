@extends('layouts.app')

@section('titulo', 'Bot de WhatsApp')
@section('subtitulo', 'Respuestas automáticas por palabra clave')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .chip { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; white-space:nowrap; }
        .disp { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size:12px; background:var(--surface-2, #F1F5F9); border-radius:6px; padding:2px 6px; }
        .acciones { display:flex; gap:6px; justify-content:flex-end; align-items:center; flex-wrap:wrap; }
        .burbuja { background:#DCF8C6; border-radius:14px 14px 14px 4px; padding:10px 14px; font-size:14px; white-space:pre-wrap; max-width:520px; box-shadow:0 1px 2px rgba(0,0,0,.06); }
        .campos-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media (max-width: 640px) { .campos-2 { grid-template-columns:1fr; } }
    </style>
@endpush

@section('contenido')
<div x-data="botFlujos(@js($flujos), @js($coincidencias))" @keydown.escape.window="modal = null">

    {{-- Conexion del numero propio del negocio --}}
    <div class="panel fade-in" style="padding:16px;margin-bottom:16px;">
        <div style="font-weight:700;margin-bottom:4px;">Número de WhatsApp del negocio</div>
        <div style="font-size:13px;color:var(--fg3);margin-bottom:12px;">
            Pega el ID del número y el token de acceso de tu app de Meta. Luego configura en Meta este webhook: <span class="disp">{{ $webhook }}</span>
        </div>
        <form @submit.prevent="guardarConexion()" novalidate class="campos-2">
            <div class="field">
                <label class="lbl" for="w-phone">ID del número (phone_number_id) *</label>
                <input class="inp" id="w-phone" inputmode="numeric" maxlength="25" x-model="conexion.phone_id" :class="{ err: errores.phone_id }">
                <div class="ferr" x-show="errores.phone_id" x-text="errores.phone_id ? errores.phone_id[0] : ''"></div>
            </div>
            <div class="field">
                <label class="lbl" for="w-visible">Número para mostrar</label>
                <input class="inp" id="w-visible" maxlength="30" placeholder="+51 999 999 999" x-model="conexion.numero_visible">
            </div>
            <div class="field" style="grid-column:1 / -1;">
                <label class="lbl" for="w-token">Token de acceso {{ $conexion && $conexion['tiene_token'] ? '(ya guardado; escribe uno nuevo solo si cambia)' : '*' }}</label>
                <input class="inp" id="w-token" type="password" autocomplete="off" maxlength="600" x-model="conexion.token" :class="{ err: errores.token }" placeholder="{{ $conexion && $conexion['tiene_token'] ? 'â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢' : 'EAAGâ€¦' }}">
                <div class="ferr" x-show="errores.token" x-text="errores.token ? errores.token[0] : ''"></div>
            </div>
            <div style="grid-column:1 / -1;">
                <button class="btn btn-p" type="submit" :disabled="guardandoConexion" x-text="guardandoConexion ? 'Guardandoâ€¦' : 'Guardar número'"></button>
            </div>
        </form>
    </div>

    {{-- Probador --}}
    <div class="panel fade-in" style="padding:16px;margin-bottom:16px;">
        <div style="font-weight:700;margin-bottom:10px;">Probar el bot</div>
        <form @submit.prevent="probar()" style="display:flex;gap:8px;flex-wrap:wrap;">
            <input class="inp" style="flex:1;min-width:200px;" maxlength="500" placeholder="Escribe como lo haría un cliente, por ejemplo: hola, precio, horario" x-model="prueba" aria-label="Mensaje de prueba">
            <button class="btn btn-p" type="submit" :disabled="probando">Enviar prueba</button>
        </form>
        <div style="margin-top:12px;" x-show="resultado" x-cloak>
            <div class="burbuja" x-text="resultado"></div>
        </div>
    </div>

    {{-- Flujos --}}
    <div class="panel fade-in" style="animation-delay:.05s;">
        <div class="panel-hd">
            <span style="font-weight:700;">Flujos de respuesta</span>
            <button class="btn btn-p" type="button" style="margin-left:auto;" @click="abrirNuevo()">
                <x-icon name="plus" size="15" stroke="2.4" /> Nuevo flujo
            </button>
        </div>
        <div class="tbl-wrap" x-show="flujos.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Flujo</th>
                        <th>Cuando el cliente escribe</th>
                        <th>El bot responde</th>
                        <th>Estado</th>
                        <th style="width:150px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(f, i) in flujos" :key="f.id">
                        <tr class="row-in" :style="'--d:' + (i * 35) + 'ms'">
                            <td>
                                <div style="font-weight:600;" x-text="f.nombre"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="'Orden ' + f.orden"></div>
                            </td>
                            <td>
                                <span class="disp" x-text="f.disparador"></span>
                                <div style="font-size:11px;color:var(--fg3);margin-top:4px;" x-text="f.coincidencia_label"></div>
                            </td>
                            <td style="font-size:13px;color:var(--fg2);max-width:360px;" x-text="f.respuesta"></td>
                            <td>
                                <button type="button" class="chip" :style="f.activo ? 'background:#DCFCE7;color:#15803D' : 'background:#E2E8F0;color:#475569'" @click="alternar(f)" :aria-label="f.activo ? 'Pausar flujo' : 'Activar flujo'" x-text="f.activo ? 'Activo' : 'Pausado'"></button>
                            </td>
                            <td>
                                <div class="acciones">
                                    <button class="btn btn-g btn-sm" type="button" @click="abrirEditar(f)">Editar</button>
                                    <button class="icon-btn sm" type="button" @click="eliminar(f)" aria-label="Eliminar flujo"><x-icon name="x" size="14" /></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="empty" x-show="!flujos.length" x-cloak>
            <x-icon name="activity" size="34" />
            <div style="font-weight:600;color:var(--fg2);">Aún no hay flujos</div>
            <div style="font-size:13px;color:var(--fg3);max-width:440px;text-align:center;">Crea uno, por ejemplo: si el cliente escribe Â«horarioÂ», el bot responde con tus horas de atención.</div>
        </div>
    </div>

    {{-- Ventana: flujo --}}
    <div class="modal-bg" x-show="modal" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="modal = null">
        <form class="modal" style="max-width:560px;" @submit.prevent="guardar()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="activity" size="18" /></div>
                <div>
                    <div class="modal-ttl" x-text="editando ? 'Editar flujo' : 'Nuevo flujo'"></div>
                    <div class="modal-sub">Cuando el mensaje coincida, el bot responde con el texto</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modal = null" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="b-nombre">Nombre del flujo *</label>
                        <input class="inp" id="b-nombre" maxlength="120" x-model="f.nombre" :class="{ err: errores.nombre }">
                        <div class="ferr" x-show="errores.nombre" x-text="errores.nombre ? errores.nombre[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="b-orden">Prioridad (0 es la primera)</label>
                        <input class="inp" id="b-orden" type="number" min="0" max="999" step="1" x-model.number="f.orden">
                    </div>
                </div>
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="b-disparador">Palabra o frase *</label>
                        <input class="inp" id="b-disparador" maxlength="120" placeholder="horario" x-model="f.disparador" :class="{ err: errores.disparador }">
                        <div class="ferr" x-show="errores.disparador" x-text="errores.disparador ? errores.disparador[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="b-coincidencia">Cómo comparar *</label>
                        <select class="inp" id="b-coincidencia" x-model="f.coincidencia">
                            <template x-for="(texto, clave) in coincidencias" :key="clave"><option :value="clave" x-text="texto"></option></template>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label class="lbl" for="b-respuesta">Respuesta del bot *</label>
                    <textarea class="inp" id="b-respuesta" rows="4" maxlength="1000" x-model="f.respuesta" :class="{ err: errores.respuesta }"></textarea>
                    <div class="ferr" x-show="errores.respuesta" x-text="errores.respuesta ? errores.respuesta[0] : ''"></div>
                </div>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;">
                    <input type="checkbox" x-model="f.activo"> Flujo activo
                </label>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="modal = null">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardandoâ€¦' : 'Guardar flujo'"></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.botFlujos = window.botFlujos || function (flujos, coincidencias) {
        const base = @js(url('admin/bot/flujos'));
        const probarUrl = @js(route('admin.bot.flujos.probar'));
        const conexionUrl = @js(route('admin.bot.conexion.guardar'));
        const vacio = () => ({ nombre: '', disparador: '', coincidencia: 'contiene', respuesta: '', orden: 0, activo: true });

        return {
            flujos, coincidencias,
            conexion: { phone_id: @js($conexion['phone_id'] ?? ''), numero_visible: @js($conexion['numero_visible'] ?? ''), token: '' },
            guardandoConexion: false,
            modal: null, editando: null, f: vacio(), errores: {}, guardando: false,
            prueba: '', resultado: '', probando: false,

            async guardarConexion() {
                this.guardandoConexion = true; this.errores = {};
                const r = await gh.api(conexionUrl, { method: 'POST', body: this.conexion });
                this.guardandoConexion = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.conexion.token = ''; gh.ir(location.pathname, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar el número.', 'error'); }
            },

            abrirNuevo() { this.editando = null; this.f = vacio(); this.errores = {}; this.modal = 'flujo'; },
            abrirEditar(x) {
                this.editando = x.id; this.errores = {};
                this.f = { nombre: x.nombre, disparador: x.disparador, coincidencia: x.coincidencia, respuesta: x.respuesta, orden: x.orden, activo: x.activo };
                this.modal = 'flujo';
            },

            async guardar() {
                this.guardando = true; this.errores = {};
                const url = this.editando ? base + '/' + this.editando : base;
                const r = await gh.api(url, { method: this.editando ? 'PUT' : 'POST', body: this.f });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.modal = null; gh.ir(location.pathname, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar el flujo.', 'error'); }
            },

            async alternar(x) {
                const r = await gh.api(base + '/' + x.id + '/activo', { method: 'PATCH' });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo cambiar el flujo.', 'error'); }
            },

            async eliminar(x) {
                if (!confirm('Â¿Eliminar el flujo Â«' + x.nombre + 'Â»?')) return;
                const r = await gh.api(base + '/' + x.id, { method: 'DELETE' });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo eliminar el flujo.', 'error'); }
            },

            async probar() {
                this.probando = true;
                const r = await gh.api(probarUrl, { method: 'POST', body: { mensaje: this.prueba } });
                this.probando = false;
                this.resultado = r.ok ? r.data.message : (r.data.message || 'No se pudo probar el mensaje.');
            },
        };
    };
    gh.ready(() => Alpine.data('botFlujos', window.botFlujos));
</script>
@endpush
@endsection
