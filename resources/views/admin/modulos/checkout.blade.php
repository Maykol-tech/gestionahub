@extends('layouts.app')

@section('titulo', 'Activar plan ' . $plan->nombre)
@section('subtitulo', 'Paga con tarjeta, Yape, Plin o transferencia')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .ck { display:grid; grid-template-columns:minmax(0, 1fr) 340px; gap:16px; align-items:start; }
        .ck-res { position:sticky; top:16px; }
        .ck-tot { display:flex; justify-content:space-between; align-items:baseline; font-size:13px; padding:8px 0; border-bottom:1px dashed var(--border); }
        .ck-tot.big { font-size:22px; font-weight:800; border:0; padding-top:12px; }
        .metodos { display:grid; grid-template-columns:repeat(4, 1fr); gap:8px; margin-bottom:16px; }
        .met { border:1.5px solid var(--border); border-radius:12px; padding:12px 8px; text-align:center; cursor:pointer; background:#fff; font:inherit; font-size:13px; font-weight:700; color:var(--fg2); transition:border-color .2s, transform .2s, box-shadow .2s; }
        .met:hover { transform:translateY(-2px); box-shadow:0 6px 14px rgba(15,23,42,.06); }
        .met.on { border-color:var(--accent); color:var(--accent); box-shadow:0 0 0 3px var(--acl); }
        .met .e { font-size:22px; display:block; margin-bottom:4px; }
        .met[disabled] { opacity:.45; cursor:not-allowed; transform:none; }
        .datos-pago { background:var(--surface2); border:1px solid var(--border); border-radius:12px; padding:14px; font-size:13px; margin-bottom:14px; }
        .datos-pago b { font-size:16px; letter-spacing:.5px; }
        .copiar { border:0; background:none; color:var(--accent); font:inherit; font-size:12px; font-weight:700; cursor:pointer; }
        .zona { border:2px dashed var(--border); border-radius:12px; padding:18px; text-align:center; cursor:pointer; transition:border-color .2s, background .2s; }
        .zona.sobre, .zona:hover { border-color:var(--accent); background:var(--acl); }
        .zona img { max-height:180px; border-radius:8px; margin-top:8px; display:block; margin-inline:auto; }
        .zona { display:block; width:100%; box-sizing:border-box; }
        .seg-c { display:inline-flex; border:1px solid var(--border); border-radius:10px; overflow:hidden; margin-bottom:12px; }
        .seg-c a { padding:8px 14px; font-size:13px; font-weight:700; color:var(--fg2); text-decoration:none; }
        .seg-c a.on { background:var(--accent); color:#fff; }
        .err { color:#B91C1C; font-size:12px; margin-top:4px; }
        @media (max-width: 900px) { .ck { grid-template-columns:1fr; } .ck-res { position:static; } .metodos { grid-template-columns:repeat(2, 1fr); } }
    </style>
@endpush

@section('contenido')
<div class="ck" x-data="checkoutPlan(@js(['metodo' => old('metodo', $tarjeta ? 'tarjeta' : 'yape'), 'niubiz' => route('admin.planes.niubiz', $plan->slug), 'ciclo' => $ciclo]))">
    <div>
        @if ($errors->any())
            <div class="pl-aviso no fade-in" style="display:flex;gap:8px;padding:12px 14px;border-radius:10px;background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;margin-bottom:14px;font-size:13px;">
                <x-icon name="alert" size="16" /><div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            </div>
        @endif
        @if ($pendiente)
            <div class="nota fade-in"><x-icon name="clock" size="16" /><div><strong>Ya tienes un pago en revisión</strong> ({{ ucfirst($pendiente->metodo) }} · operación {{ $pendiente->numero_operacion }}). Te avisaremos apenas lo confirmemos; no necesitas pagar de nuevo.</div></div>
        @endif

        <div class="panel fade-in" style="padding:18px;">
            <div class="sec-ttl" style="margin-top:0;">¿Cómo quieres pagar?</div>
            <div class="metodos">
                <button type="button" class="met" :class="{ on: metodo === 'tarjeta' }" @click="metodo = 'tarjeta'" @disabled(! $tarjeta) title="{{ $tarjeta ? '' : 'Pago con tarjeta aún no disponible' }}"><span class="e">💳</span>Tarjeta</button>
                <button type="button" class="met" :class="{ on: metodo === 'yape' }" @click="metodo = 'yape'"><span class="e">🟣</span>Yape</button>
                <button type="button" class="met" :class="{ on: metodo === 'plin' }" @click="metodo = 'plin'"><span class="e">🔵</span>Plin</button>
                <button type="button" class="met" :class="{ on: metodo === 'transferencia' }" @click="metodo = 'transferencia'"><span class="e">🏦</span>Transferencia</button>
            </div>

            {{-- TARJETA (Niubiz) --}}
            <div x-show="metodo === 'tarjeta'" x-cloak>
                @if ($tarjeta)
                    <p style="font-size:13px;color:var(--fg2);margin-top:0;">Pago seguro con Niubiz (Visa, Mastercard, Amex, Diners). Tu plan se activa al instante. No guardamos el número de tu tarjeta.</p>
                    <label class="sw" style="display:flex;gap:8px;align-items:center;font-size:13px;margin-bottom:12px;"><input type="checkbox" x-model="renovar" style="width:auto;"> Renovar automáticamente cada {{ $ciclo === 'anual' ? 'año' : 'mes' }} (puedes apagarlo cuando quieras)</label>
                    <button class="btn btn-p" type="button" style="width:100%;justify-content:center;padding:12px;" @click="pagarTarjeta()" :disabled="cargando">
                        <span x-show="! cargando">Pagar S/ {{ number_format($precios[$ciclo], 2) }} con tarjeta</span><span x-show="cargando" x-cloak>Abriendo pago seguro…</span>
                    </button>
                @else
                    <div class="nota"><x-icon name="alert" size="16" /><div>El pago con tarjeta estará disponible pronto. Mientras tanto puedes pagar con Yape, Plin o transferencia.</div></div>
                @endif
            </div>

            {{-- MANUAL --}}
            <form x-show="metodo !== 'tarjeta'" x-cloak method="POST" action="{{ route('admin.planes.pagar', $plan->slug) }}" enctype="multipart/form-data" @submit="enviar($event)">
                @csrf
                <input type="hidden" name="metodo" :value="metodo">
                <input type="hidden" name="ciclo" value="{{ $ciclo }}">
                <input type="hidden" name="checkout_id" value="{{ $checkoutId }}">

                <div class="datos-pago">
                    <div style="font-weight:700;margin-bottom:6px;">1. Paga S/ {{ number_format($precios[$ciclo], 2) }} a:</div>
                    <template x-if="metodo === 'yape' || metodo === 'plin'">
                        <div>
                            @if ($cobros['qr_url'])<img src="{{ $cobros['qr_url'] }}" alt="QR de pago" style="width:140px;border-radius:8px;display:block;margin-bottom:8px;">@endif
                            <span x-text="metodo === 'yape' ? 'Yape' : 'Plin'"></span>:
                            <b x-text="metodo === 'yape' ? @js($cobros['yape'] ?: 'por confirmar') : @js($cobros['plin'] ?: 'por confirmar')"></b>
                            <button type="button" class="copiar" x-show="metodo === 'yape' ? @js((bool) $cobros['yape']) : @js((bool) $cobros['plin'])" @click="copiar(metodo === 'yape' ? @js($cobros['yape']) : @js($cobros['plin']))">Copiar</button>
                            <div style="color:var(--fg3);">A nombre de {{ $cobros['titular'] }}</div>
                        </div>
                    </template>
                    <template x-if="metodo === 'transferencia'">
                        <div>
                            {{ $cobros['banco'] ?: 'Banco por confirmar' }} · {{ $cobros['titular'] }}<br>
                            Cuenta: <b>{{ $cobros['cuenta'] ?: '—' }}</b> @if ($cobros['cuenta'])<button type="button" class="copiar" @click="copiar(@js($cobros['cuenta']))">Copiar</button>@endif<br>
                            CCI: <b>{{ $cobros['cci'] ?: '—' }}</b> @if ($cobros['cci'])<button type="button" class="copiar" @click="copiar(@js($cobros['cci']))">Copiar</button>@endif
                        </div>
                    </template>
                </div>

                <div style="font-weight:700;font-size:13px;margin-bottom:6px;">2. Cuéntanos tu pago</div>
                <label class="lbl" for="ck-op">Número de operación</label>
                <input class="inp" id="ck-op" name="numero_operacion" value="{{ old('numero_operacion') }}" maxlength="40" placeholder="Ej.: 12345678" required>
                @error('numero_operacion')<div class="err">{{ $message }}</div>@enderror

                <label class="lbl" style="margin-top:10px;">Captura del pago</label>
                <input type="file" name="captura" accept="image/*" x-ref="archivo" @change="ver($event.target.files[0])" style="display:none">
                <div class="zona" role="button" tabindex="0" :class="{ sobre }" @click="$refs.archivo.click()" @keydown.enter.prevent="$refs.archivo.click()"
                     @dragover.prevent="sobre = true" @dragleave="sobre = false" @drop.prevent="soltar($event)">
                    <div x-show="! vista"><x-icon name="upload" size="22" /><div style="font-size:13px;font-weight:600;margin-top:4px;">Toca para subir o arrastra la captura</div><div style="font-size:12px;color:var(--fg3);">Foto o pantallazo, hasta 5 MB</div></div>
                    <img x-show="vista" :src="vista" alt="Vista previa de la captura">
                    <div x-show="vista" x-cloak style="font-size:12px;color:var(--accent);font-weight:700;margin-top:6px;">Cambiar imagen</div>
                </div>
                @error('captura')<div class="err">{{ $message }}</div>@enderror

                <button class="btn btn-p" type="submit" style="width:100%;justify-content:center;padding:12px;margin-top:14px;" :disabled="enviando" @disabled($pendiente)>
                    <span x-show="! enviando">Enviar comprobante</span><span x-show="enviando" x-cloak>Enviando…</span>
                </button>
                <p style="font-size:12px;color:var(--fg3);text-align:center;margin:8px 0 0;">Revisamos tu pago y activamos tu plan, normalmente en menos de 1 hora hábil.</p>
            </form>
        </div>
    </div>

    <aside class="panel ck-res fade-in" style="padding:18px;animation-delay:.08s">
        <div class="plan-nom" style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--fg3);">Resumen</div>
        <h3 style="margin:6px 0 10px;font-size:20px;">Plan {{ $plan->nombre }}</h3>
        <div class="seg-c">
            <a href="{{ route('admin.planes.checkout', [$plan->slug, 'ciclo' => 'mensual']) }}" class="{{ $ciclo === 'mensual' ? 'on' : '' }}">Mensual</a>
            <a href="{{ route('admin.planes.checkout', [$plan->slug, 'ciclo' => 'anual']) }}" class="{{ $ciclo === 'anual' ? 'on' : '' }}">Anual</a>
        </div>
        @php($ahorro = max(0, $precios['mensual'] * 12 - $precios['anual']))
        <div class="ck-tot"><span>{{ $ciclo === 'anual' ? '12 meses' : '1 mes' }}</span><span>S/ {{ number_format($precios[$ciclo], 2) }}</span></div>
        @if ($ciclo === 'anual' && $ahorro > 0)
            <div class="ck-tot" style="color:var(--green);"><span>Ahorras</span><span>S/ {{ number_format($ahorro, 2) }}</span></div>
        @endif
        <div class="ck-tot"><span>IGV incluido</span><span>S/ {{ number_format($precios[$ciclo] - $precios[$ciclo] / 1.18, 2) }}</span></div>
        <div class="ck-tot big"><span>Total</span><span>S/ {{ number_format($precios[$ciclo], 2) }}</span></div>
        @if ($prueba['en_prueba'] && ! $prueba['vencida'])
            <p style="font-size:12px;color:var(--fg3);">Aún te {{ $prueba['dias'] === 1 ? 'queda 1 día' : 'quedan ' . $prueba['dias'] . ' días' }} de prueba. Al pagar, tu plan empieza hoy.</p>
        @endif
        <ul style="list-style:none;padding:0;margin:12px 0 0;font-size:12px;color:var(--fg2);display:grid;gap:6px;">
            <li>🔒 Pago seguro, no guardamos tu tarjeta</li>
            <li>📄 Tu información se mantiene siempre</li>
            <li>↩️ Cancela cuando quieras</li>
        </ul>
        <a href="{{ route('admin.modulo', 'planes') }}" style="display:block;text-align:center;margin-top:14px;font-size:13px;">← Ver todos los planes</a>
    </aside>
</div>
@endsection

@push('scripts')
<script>
window.checkoutPlan = window.checkoutPlan || function (cfg) {
    return {
        metodo: cfg.metodo, renovar: true, cargando: false, enviando: false, sobre: false, vista: null,
        enviar(e) {
            if (! this.vista) { e.preventDefault(); Alpine.store('toasts').push('Sube la captura de tu pago', 'error'); return; }
            this.enviando = true;
        },
        copiar(t) { if (! t) return; navigator.clipboard?.writeText(t); Alpine.store('toasts').push('Copiado', 'ok'); },
        ver(f) { if (! f) return; if (f.size > 5 * 1024 * 1024) { Alpine.store('toasts').push('La imagen pesa más de 5 MB', 'error'); return; } const r = new FileReader(); r.onload = e => this.vista = e.target.result; r.readAsDataURL(f); },
        soltar(e) { this.sobre = false; const f = e.dataTransfer.files[0]; if (! f) return; const dt = new DataTransfer(); dt.items.add(f); this.$refs.archivo.files = dt.files; this.ver(f); },
        cargarScript(src) {
            return new Promise((ok, no) => { if (window.VisanetCheckout) return ok(); const s = document.createElement('script'); s.src = src; s.onload = ok; s.onerror = no; document.head.appendChild(s); });
        },
        async pagarTarjeta() {
            this.cargando = true;
            const r = await gh.api(cfg.niubiz, { method: 'POST', body: { ciclo: cfg.ciclo, renovar: this.renovar } });
            if (! r.ok) { this.cargando = false; Alpine.store('toasts').push(r.data.message || 'No se pudo abrir el pago', 'error'); return; }
            try { await this.cargarScript(r.data.script); } catch (e) { this.cargando = false; Alpine.store('toasts').push('No se pudo cargar el formulario de Niubiz', 'error'); return; }
            const d = r.data;
            window.dataLayer = window.dataLayer || []; window.dataLayer.push({ event: 'pago_tarjeta_abierto' });
            VisanetCheckout.configure({
                sessiontoken: d.sessiontoken, channel: 'web', merchantid: d.merchantid, purchasenumber: d.purchasenumber,
                amount: d.amount, expirationminutes: '20', timeouturl: location.href, merchantlogo: '', formbuttoncolor: '#2B6EF6',
                action: d.action, complete: () => { this.cargando = false; },
            });
            VisanetCheckout.open();
            this.cargando = false;
        },
    };
};
</script>
@endpush
