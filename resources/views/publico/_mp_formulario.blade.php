{{--
    Formulario de tarjeta de Mercado Pago dentro de la página (Card Payment Brick).
    Los datos de la tarjeta los recibe Mercado Pago; aquí solo llega un token de un solo uso.
    Parámetros: $mpKey, $mpMonto, $mpUrl (POST que cobra), $mpColor, $mpAlternativa (enlace a la página de Mercado Pago, opcional)
--}}
<div class="mpf" id="mpf">
    <div class="mpf-carga" id="mpf-carga"><span class="mpf-spin"></span> Cargando el formulario seguro…</div>
    <div id="mpf-brick"></div>
    <div class="mpf-msg" id="mpf-msg" hidden></div>
    <div class="mpf-ok" id="mpf-ok" hidden>
        <div class="mpf-check">✓</div>
        <b>¡Pago aprobado!</b>
        <span id="mpf-ok-txt"></span>
    </div>
    <div class="mpf-seg">🔒 Pago seguro procesado por Mercado Pago. Los datos de tu tarjeta van cifrados y la tienda no los guarda.</div>
    @if (! empty($mpAlternativa))
        <a class="mpf-alt" href="{{ $mpAlternativa }}">¿No carga? Paga en la página de Mercado Pago</a>
    @endif
</div>
<style>
    .mpf { margin-top:12px; }
    .mpf [hidden] { display:none !important; }
    .mpf-carga { display:flex; align-items:center; justify-content:center; gap:10px; padding:26px 10px; font-size:14px; color:var(--fg3, #6B7280); }
    .mpf-spin { width:18px; height:18px; border:3px solid color-mix(in srgb, {{ $mpColor }} 25%, transparent); border-top-color:{{ $mpColor }}; border-radius:50%; animation:mpfg .8s linear infinite; }
    @keyframes mpfg { to { transform:rotate(360deg); } }
    .mpf-msg { margin-top:10px; padding:11px 13px; border-radius:12px; font-size:14px; font-weight:600; line-height:1.4; background:#FEE2E2; color:#991B1B; }
    .mpf-msg.espera { background:#FEF3C7; color:#92400E; }
    .mpf-ok { text-align:center; padding:18px 8px 8px; display:flex; flex-direction:column; align-items:center; gap:6px; }
    .mpf-ok b { font-size:19px; }
    .mpf-ok span { font-size:14px; color:var(--fg2, #4B5563); }
    .mpf-check { width:64px; height:64px; border-radius:50%; background:#16A34A; color:#fff; font-size:34px; font-weight:900; display:grid; place-items:center; animation:mpfp .5s cubic-bezier(.3,1.6,.5,1) both; }
    @keyframes mpfp { from { transform:scale(.3); opacity:0; } }
    .mpf-seg { display:flex; gap:6px; font-size:12px; color:var(--fg3, #6B7280); margin-top:10px; line-height:1.4; }
    .mpf-alt { display:block; text-align:center; font-size:12.5px; color:var(--fg3, #6B7280); margin-top:10px; }
</style>
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
(function () {
    const caja = document.getElementById('mpf');
    const carga = document.getElementById('mpf-carga');
    const msg = document.getElementById('mpf-msg');
    const ok = document.getElementById('mpf-ok');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const mostrar = (t, espera) => { msg.textContent = t; msg.className = 'mpf-msg' + (espera ? ' espera' : ''); msg.hidden = false; };

    function iniciar() {
        if (!window.MercadoPago) { carga.textContent = 'No se pudo cargar el formulario. Revisa tu conexión o usa el enlace de abajo.'; return; }
        const mp = new MercadoPago(@js($mpKey), { locale: 'es-PE' });
        mp.bricks().create('cardPayment', 'mpf-brick', {
            initialization: { amount: {{ round((float) $mpMonto, 2) }} },
            customization: {
                visual: { style: { customVariables: { baseColor: @js($mpColor), borderRadiusMedium: '12px', borderRadiusLarge: '16px' } }, texts: { formSubmit: 'Pagar' } },
                paymentMethods: { maxInstallments: 12 },
            },
            callbacks: {
                onReady: () => { carga.hidden = true; },
                onError: (e) => { console.warn('Mercado Pago', e); },
                onSubmit: (datos) => {
                    msg.hidden = true;
                    return fetch(@js($mpUrl), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify(datos),
                    }).then(r => r.json().catch(() => ({})).then(d => {
                        if (d.estado === 'approved') {
                            document.getElementById('mpf-brick').hidden = true;
                            document.getElementById('mpf-ok-txt').textContent = d.tarjeta ? 'Pagaste con tu tarjeta terminada en ' + d.tarjeta + '.' : (d.mensaje || '');
                            ok.hidden = false;
                            setTimeout(() => location.replace(location.pathname), 2200);
                        } else if (d.estado === 'in_process' || d.estado === 'pending') {
                            mostrar(d.mensaje, true);
                        } else {
                            mostrar(d.mensaje || d.message || 'No se pudo procesar el pago. Intenta de nuevo.');
                        }
                    })).catch(() => mostrar('Se cortó la conexión. Revisa si te llegó el cobro antes de intentar de nuevo.'));
                },
            },
        });
    }
    if (document.readyState === 'complete') iniciar(); else window.addEventListener('load', iniciar);
})();
</script>
