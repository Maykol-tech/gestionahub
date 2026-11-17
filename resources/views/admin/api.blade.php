@extends('layouts.app')

@section('titulo', 'API y conexiones')
@section('subtitulo', 'Conecta tu web, app u otro sistema con tus pedidos')

@php
    $ejCrear = json_encode([
        'cliente' => ['nombre' => 'Ana Pérez', 'telefono' => '987654321'],
        'tipo' => 'delivery', 'direccion' => 'Av. Arequipa 123, Lince',
        'metodo_pago' => 'yape', 'costo_envio' => 5, 'referencia_externa' => 'WEB-1001',
        'items' => [['producto_id' => 12, 'cantidad' => 2], ['sku' => 'POLO-AZ-M', 'cantidad' => 1]],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ejAviso = json_encode([
        'evento' => 'pedido.estado', 'estado_anterior' => 'preparando',
        'pedido' => ['id' => 381, 'numero' => 'A-261112-K4PZ', 'estado' => 'en_camino', 'total' => 65.0, 'pagado' => false, 'items' => ['…']],
        'enviado' => now()->startOfMinute()->toIso8601String(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

@section('contenido')
<div x-data="apiNegocio(@js($tokens), @js($webhook))">
    <div class="panel fade-in">
        <div class="tabs">
            <button class="tab" :class="{ on: vista === 'tokens' }" @click="vista = 'tokens'">Tokens y aviso</button>
            <button class="tab" :class="{ on: vista === 'ejemplos' }" @click="vista = 'ejemplos'">Ejemplos para tu programador</button>
        </div>

        {{-- ===== TOKENS Y AVISO ===== --}}
        <div x-show="vista === 'tokens'" class="api-bd">
            <section>
                <div class="api-h">
                    <div><b>Tokens de acceso</b><p>Cada sistema que conectes usa su propio token. Si uno deja de usarse o se filtra, revócalo.</p></div>
                </div>

                <template x-if="nuevo">
                    <div class="api-nuevo">
                        <div style="font-size:13px;font-weight:700;">Copia tu token ahora. No lo volveremos a mostrar.</div>
                        <div class="api-copiar"><code x-text="nuevo"></code><button class="btn btn-p btn-sm" @click="copiar(nuevo)">Copiar</button></div>
                    </div>
                </template>

                <form class="api-form" @submit.prevent="crear()">
                    <input class="inp" x-model="nombre" maxlength="60" placeholder='Nombre, por ejemplo "Mi web" o "App de reparto"' :class="{ err: errores.nombre }">
                    <button class="btn btn-p" :disabled="guardando"><x-icon name="plus" size="15" stroke="2.4" /> Crear token</button>
                </form>
                <div class="ferr" x-show="errores.nombre" x-text="errores.nombre?.[0]"></div>

                <div class="tbl-wrap" x-show="tokens.length" style="margin-top:12px;">
                    <table class="tbl">
                        <thead><tr><th>Nombre</th><th>Creado</th><th>Último uso</th><th>Creado por</th><th></th></tr></thead>
                        <tbody>
                            <template x-for="t in tokens" :key="t.id">
                                <tr><td style="font-weight:600;" x-text="t.nombre"></td><td x-text="t.creado"></td><td x-text="t.usado"></td><td style="color:var(--fg3);" x-text="t.por"></td>
                                    <td style="text-align:right;"><button class="btn btn-g btn-sm" @click="revocar(t)">Revocar</button></td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div x-show="!tokens.length" class="api-vacio">Aún no tienes tokens. Crea uno cuando vayas a conectar otro sistema.</div>
            </section>

            <section>
                <div class="api-h"><div><b>Aviso cuando un pedido cambia de estado</b><p>Le enviamos a tu sistema los datos del pedido cada vez que cambia de estado (confirmado, en camino, entregado, cancelado…). Es opcional.</p></div></div>
                <form class="api-form" @submit.prevent="guardarAviso()">
                    <input class="inp" x-model="aviso.url" maxlength="255" placeholder="https://tusistema.com/avisos/gestionahub" :class="{ err: errores.url }">
                    <button class="btn btn-p" :disabled="guardando">Guardar</button>
                    <button type="button" class="btn btn-g" x-show="aviso.url" @click="probar()" :disabled="guardando">Enviar prueba</button>
                </form>
                <div class="ferr" x-show="errores.url" x-text="errores.url?.[0]"></div>
                <template x-if="aviso.secreto">
                    <div class="api-sec">
                        <span>Clave para comprobar la firma</span>
                        <div class="api-copiar"><code x-text="verSecreto ? aviso.secreto : '••••••••••••••••••••'"></code>
                            <button type="button" class="btn btn-g btn-sm" @click="verSecreto = !verSecreto" x-text="verSecreto ? 'Ocultar' : 'Ver'"></button>
                            <button type="button" class="btn btn-g btn-sm" @click="copiar(aviso.secreto)">Copiar</button></div>
                    </div>
                </template>
                <template x-if="aviso.ultimo">
                    <div class="api-ult" :class="aviso.ultimo.codigo >= 200 && aviso.ultimo.codigo < 300 ? 'ok' : 'mal'"
                         x-text="'Último aviso (' + aviso.ultimo.evento + ', ' + aviso.ultimo.fecha + '): ' + (aviso.ultimo.codigo ? 'tu sistema respondió ' + aviso.ultimo.codigo : 'sin respuesta')"></div>
                </template>
            </section>
        </div>

        {{-- ===== EJEMPLOS ===== --}}
        <div x-show="vista === 'ejemplos'" x-cloak class="api-bd api-doc">
            <section>
                <b>Cómo entrar</b>
                <p>Envía el token en cada llamada con la cabecera <code>Authorization: Bearer TU_TOKEN</code> y <code>Accept: application/json</code>. Dirección base: <code>{{ $base }}</code>. Límite: 60 llamadas por minuto.</p>
            </section>

            <section>
                <b>Ver pedidos</b>
                <p>Filtros opcionales: <code>estado</code> ({{ implode(', ', $estados) }}), <code>canal</code>, <code>desde</code> y <code>hasta</code> (AAAA-MM-DD), <code>actualizado_desde</code>, <code>por_pagina</code> (máx. 100) y <code>page</code>.</p>
<pre>curl "{{ $base }}/pedidos?estado=pendiente&amp;por_pagina=20" \
  -H "Authorization: Bearer TU_TOKEN" -H "Accept: application/json"</pre>
            </section>

            <section>
                <b>Ver un pedido</b>
                <p>Por su número (<code>T-261112-UDU2</code>) o por su id.</p>
<pre>curl "{{ $base }}/pedidos/T-261112-UDU2" \
  -H "Authorization: Bearer TU_TOKEN" -H "Accept: application/json"</pre>
            </section>

            <section>
                <b>Crear un pedido</b>
                <p>Los precios se toman de tu catálogo, no del que llama. Cada producto va por <code>producto_id</code> o por <code>sku</code>; si tiene variantes, añade <code>variante_id</code>. Se descuenta el stock y aparece en tu tablero de pedidos como pendiente.</p>
<pre>curl -X POST "{{ $base }}/pedidos" \
  -H "Authorization: Bearer TU_TOKEN" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{{ $ejCrear }}'</pre>
            </section>

            <section>
                <b>Cambiar el estado</b>
                <p>Sigue las mismas reglas del tablero: al entregar, un pago que no es en efectivo queda pagado; al cancelar se devuelve el stock.</p>
<pre>curl -X PATCH "{{ $base }}/pedidos/T-261112-UDU2/estado" \
  -H "Authorization: Bearer TU_TOKEN" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"estado": "en_camino"}'</pre>
            </section>

            <section>
                <b>El aviso que recibe tu sistema</b>
                <p>Un <code>POST</code> con este contenido. La cabecera <code>X-GestionaHub-Firma</code> trae <code>sha256=</code> más el HMAC-SHA256 del contenido con tu clave; compárala para saber que viene de nosotros. Responde con un código 200.</p>
<pre>{{ $ejAviso }}</pre>
<pre>// PHP: comprobar la firma
$firma = 'sha256=' . hash_hmac('sha256', file_get_contents('php://input'), 'TU_CLAVE');
if (! hash_equals($firma, $_SERVER['HTTP_X_GESTIONAHUB_FIRMA'] ?? '')) { http_response_code(401); exit; }</pre>
            </section>

            <section>
                <b>Respuestas de error</b>
                <p><code>401</code> token inválido o revocado · <code>403</code> el token no es de pedidos · <code>404</code> el pedido no existe · <code>422</code> datos incompletos (el detalle viene en <code>errors</code>) · <code>429</code> pasaste el límite por minuto.</p>
            </section>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tabs{display:flex;gap:4px;padding:0 12px;border-bottom:1px solid var(--border);overflow-x:auto;}
    .tab{padding:12px 10px;font-size:13px;font-weight:600;color:var(--fg3);background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-1px;cursor:pointer;font-family:inherit;white-space:nowrap;}
    .tab.on{color:var(--accent);border-bottom-color:var(--accent);}
    .api-bd { padding:18px; display:grid; gap:22px; }
    .api-bd section + section { border-top:1px solid var(--border); padding-top:20px; }
    .api-h p, .api-doc p { margin:4px 0 0; font-size:13px; color:var(--fg2); line-height:1.5; max-width:760px; }
    .api-form { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
    .api-form .inp { flex:1; min-width:220px; max-width:460px; }
    .api-nuevo { margin-top:12px; padding:12px; border-radius:12px; background:var(--okl, #DCFCE7); border:1px solid var(--ok, #16A34A); }
    .api-copiar { display:flex; gap:8px; align-items:center; margin-top:8px; flex-wrap:wrap; }
    .api-copiar code { flex:1; min-width:0; overflow-wrap:anywhere; font-size:12.5px; padding:8px 10px; border-radius:8px; background:var(--surface); border:1px solid var(--border); }
    .api-sec { margin-top:12px; font-size:12px; color:var(--fg3); max-width:620px; }
    .api-ult { margin-top:10px; font-size:12.5px; padding:8px 10px; border-radius:8px; display:inline-block; }
    .api-ult.ok { background:var(--okl, #DCFCE7); color:var(--ok, #16A34A); }
    .api-ult.mal { background:var(--redl); color:var(--red); }
    .api-vacio { font-size:13px; color:var(--fg3); margin-top:12px; }
    .api-doc code { font-size:12.5px; background:var(--surface2, #F1F5F9); padding:1px 5px; border-radius:5px; }
    .api-doc pre { margin:10px 0 0; padding:12px 14px; border-radius:10px; background:#0F172A; color:#E2E8F0; font-size:12.5px; line-height:1.55; overflow-x:auto; max-width:100%; }
</style>
@endpush

@push('scripts')
<script>
    window.apiNegocio = function (tokens, aviso) {
        const base = @js(url('admin/api'));
        return {
            tokens, aviso, vista: 'tokens', nombre: '', nuevo: null, guardando: false, errores: {}, verSecreto: false,
            async llamar(url, metodo, body) {
                this.guardando = true; this.errores = {};
                const r = await gh.api(url, { method: metodo, body });
                this.guardando = false;
                if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; return null; }
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar', 'error'); if (r.data.ultimo) this.aviso.ultimo = r.data.ultimo; return null; }
                Alpine.store('toasts').push(r.data.message);
                return r.data;
            },
            async crear() {
                const d = await this.llamar(base + '/tokens', 'POST', { nombre: this.nombre });
                if (d) { this.nuevo = d.token; this.tokens = d.tokens; this.nombre = ''; }
            },
            async revocar(t) {
                if (!confirm('¿Revocar el token "' + t.nombre + '"? Lo que lo use dejará de funcionar.')) return;
                const d = await this.llamar(base + '/tokens/' + t.id, 'DELETE', {});
                if (d) this.tokens = d.tokens;
            },
            async guardarAviso() {
                const d = await this.llamar(base + '/webhook', 'POST', { url: this.aviso.url });
                if (d) this.aviso.secreto = d.secreto;
            },
            async probar() {
                const d = await this.llamar(base + '/webhook/probar', 'POST', {});
                if (d) this.aviso.ultimo = d.ultimo;
            },
            copiar(t) { navigator.clipboard?.writeText(t).then(() => Alpine.store('toasts').push('Copiado')); },
        };
    };
    gh.ready(() => Alpine.data('apiNegocio', window.apiNegocio));
</script>
@endpush
