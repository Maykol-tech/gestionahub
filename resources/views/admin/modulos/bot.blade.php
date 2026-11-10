@extends('layouts.app')

@section('titulo', 'Bot WhatsApp')
@section('subtitulo', 'Atención automática para tus clientes')

@section('acciones')
    <span class="pronto"><x-icon name="clock" size="11" /> Conexión con WhatsApp próximamente</span>
@endsection

@section('contenido')
<div x-data="botPage(@js($ajustes))" class="g-21">
    <form class="panel fade-in" @submit.prevent="guardar()" novalidate>
        <div class="panel-hd">
            <div>
                <div class="panel-ttl">Configuración básica</div>
                <div class="panel-sub">Estos mensajes usará el bot cuando conectes tu número</div>
            </div>
            <label class="switch" style="margin-left:auto;padding-top:0;">
                <input type="checkbox" x-model="form.bot_activo">
                <span class="track"><span class="thumb"></span></span>
                <span x-text="form.bot_activo ? 'Activado' : 'Desactivado'" style="font-size:13px;font-weight:600;"></span>
            </label>
        </div>
        <div style="padding:18px 16px 4px;">
            <div class="field-row">
                <div class="field">
                    <label class="lbl">Número de WhatsApp</label>
                    <input class="inp" :class="{ err: errores.bot_numero }" x-model="form.bot_numero" maxlength="20" placeholder="987 654 321" inputmode="tel">
                    <div class="ferr" x-show="errores.bot_numero" x-text="errores.bot_numero?.[0]"></div>
                </div>
                <div class="field">
                    <label class="lbl">Horario de atención</label>
                    <input class="inp" :class="{ err: errores.bot_horario }" x-model="form.bot_horario" maxlength="120" placeholder="Lunes a sábado, 9:00 a. m. a 9:00 p. m.">
                    <div class="ferr" x-show="errores.bot_horario" x-text="errores.bot_horario?.[0]"></div>
                </div>
            </div>
            <div class="field">
                <label class="lbl">Mensaje de bienvenida <span class="req">*</span></label>
                <textarea class="inp" :class="{ err: errores.bot_bienvenida }" x-model="form.bot_bienvenida" rows="3" maxlength="500"></textarea>
                <div class="hint" x-text="(form.bot_bienvenida || '').length + ' / 500'"></div>
                <div class="ferr" x-show="errores.bot_bienvenida" x-text="errores.bot_bienvenida?.[0]"></div>
            </div>
            <div class="field">
                <label class="lbl">Mensaje fuera de horario</label>
                <textarea class="inp" :class="{ err: errores.bot_ausencia }" x-model="form.bot_ausencia" rows="2" maxlength="500"></textarea>
                <div class="ferr" x-show="errores.bot_ausencia" x-text="errores.bot_ausencia?.[0]"></div>
            </div>
        </div>
        <div class="modal-ft" style="border-radius:0 0 10px 10px;">
            <button type="button" class="btn btn-g" @click="form = { ...original }; errores = {}">Descartar</button>
            <button type="submit" class="btn btn-p" :disabled="guardando">
                <x-icon name="loader" size="15" class="spin" x-show="guardando" />
                <x-icon name="check" size="15" stroke="2.4" x-show="!guardando" />
                <span x-text="guardando ? 'Guardando¦' : 'Guardar configuración'"></span>
            </button>
        </div>
    </form>

    <div class="fade-in" style="animation-delay:.08s">
        <div class="sec-ttl">Vista previa</div>
        <div class="wa">
            <div class="wa-hd">
                <span class="wa-av">{{ mb_strtoupper(mb_substr($negocio, 0, 1)) }}</span>
                <div>
                    <div style="font-weight:600;font-size:13px;">{{ $negocio }}</div>
                    <div style="font-size:11px;opacity:.8;" x-text="form.bot_activo ? 'Respuesta automática' : 'Bot desactivado'"></div>
                </div>
            </div>
            <div class="wa-bd">
                <div class="wa-msg wa-in">Hola, Â¿tienen delivery?</div>
                <div class="wa-msg wa-out" x-text="form.bot_bienvenida || 'Escribe tu mensaje de bienvenida'"></div>
                <div class="wa-msg wa-out wa-menu">
                    1. Ver la carta ({{ $productos }} {{ $productos === 1 ? 'producto' : 'productos' }})<br>
                    2. Hacer un pedido<br>
                    3. Hablar con un asesor
                </div>
                <div class="wa-hint" x-show="form.bot_horario" x-text="'Horario: ' + form.bot_horario"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .switch{display:flex;align-items:center;gap:10px;cursor:pointer;}
        .switch input{display:none;}
        .track{width:38px;height:22px;border-radius:99px;background:var(--border);position:relative;transition:background .2s;}
        .thumb{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:transform .25s var(--ease);}
        .switch input:checked + .track{background:var(--green);}
        .switch input:checked + .track .thumb{transform:translateX(16px);}
        .wa{border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow);}
        .wa-hd{display:flex;align-items:center;gap:10px;padding:12px 14px;background:#075E54;color:#fff;}
        .wa-av{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;}
        .wa-bd{background:#EFEAE2;padding:14px;display:flex;flex-direction:column;gap:8px;min-height:280px;}
        .wa-msg{max-width:85%;padding:8px 10px;border-radius:8px;font-size:12.5px;line-height:1.45;box-shadow:0 1px 1px rgba(0,0,0,.08);white-space:pre-wrap;word-break:break-word;}
        .wa-in{background:#fff;align-self:flex-start;}
        .wa-out{background:#D9FDD3;align-self:flex-end;}
        .wa-menu{white-space:normal;}
        .wa-hint{align-self:center;font-size:11px;color:#54656F;background:rgba(255,255,255,.7);padding:3px 10px;border-radius:6px;}
    </style>
@endpush

@push('scripts')
<script>
    gh.ready(() => {
        Alpine.data('botPage', (ajustes) => ({
            form: { ...ajustes }, original: { ...ajustes }, errores: {}, guardando: false,
            async guardar() {
                this.guardando = true; this.errores = {};
                const r = await gh.api('{{ route('admin.bot.guardar') }}', { method: 'POST', body: this.form });
                this.guardando = false;
                if (r.status === 422) { this.errores = r.data.errors || {}; return; }
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar', 'error'); return; }
                this.form = { ...r.data.data }; this.original = { ...r.data.data };
                Alpine.store('toasts').push(r.data.message);
            },
        }));
    });
</script>
@endpush
