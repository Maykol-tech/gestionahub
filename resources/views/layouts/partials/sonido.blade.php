{{--
    Sonido de avisos: cada 20 s pregunta al servidor si llegó un pedido o una comanda nueva.
    Si llegó, suena un "ding" (sin archivos de audio, se genera en el navegador) y aparece un aviso.
    El botón del parlante en la barra superior lo apaga o lo prende (se recuerda en este equipo).
--}}
@if (\Illuminate\Support\Facades\Route::has('admin.pulso'))
<script>
(function () {
    if (window.__ghPulso) return;
    window.__ghPulso = true;
    const URL_PULSO = @js(route('admin.pulso'));
    let ctx = null, base = null;

    const leerOn = () => { try { return localStorage.getItem('gh-sonido') !== '0'; } catch (e) { return true; } };
    const audio = () => {
        if (!ctx) { const C = window.AudioContext || window.webkitAudioContext; if (!C) return null; ctx = new C(); }
        if (ctx.state === 'suspended') ctx.resume();
        return ctx;
    };
    // Los navegadores solo permiten sonar después de un clic: se "despierta" el audio en el primer toque
    ['pointerdown', 'keydown'].forEach(ev => window.addEventListener(ev, () => audio(), { once: true, passive: true }));

    // notas: [frecuencia, inicio, duración]
    const tocar = (notas) => {
        const a = audio(); if (!a) return;
        const t0 = a.currentTime + 0.02;
        notas.forEach(([f, ini, dur]) => {
            const o = a.createOscillator(), g = a.createGain();
            o.type = 'sine'; o.frequency.value = f;
            g.gain.setValueAtTime(0.0001, t0 + ini);
            g.gain.exponentialRampToValueAtTime(0.35, t0 + ini + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, t0 + ini + dur);
            o.connect(g).connect(a.destination);
            o.start(t0 + ini); o.stop(t0 + ini + dur + 0.05);
        });
    };
    const SONIDOS = {
        pedido: [[880, 0, .25], [1318.5, .16, .45]],                 // ding-dong alegre
        cocina: [[1046.5, 0, .18], [1046.5, .22, .18], [1568, .44, .4]], // tres toques, como timbre de cocina
    };

    document.addEventListener('alpine:init', () => {
        Alpine.store('sonido', {
            on: leerOn(),
            alternar() {
                this.on = !this.on;
                try { localStorage.setItem('gh-sonido', this.on ? '1' : '0'); } catch (e) {}
                if (this.on) tocar(SONIDOS.pedido);
                Alpine.store('toasts').push(this.on ? 'Sonido de avisos activado' : 'Sonido de avisos apagado', 'info');
            },
            probar(tipo = 'pedido') { tocar(SONIDOS[tipo]); },
        });
    });

    const canal = { tienda: 'tienda online', whatsapp: 'WhatsApp', pos: 'punto de venta', mesa: 'mesa' };
    async function latido() {
        let r;
        try { r = await fetch(URL_PULSO, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }); } catch (e) { return; }
        if (!r.ok) return;
        const d = await r.json().catch(() => null); if (!d) return;
        const ahora = { pedido: d.pedido ? d.pedido.id : 0, comanda: d.comanda ? d.comanda.id : 0 };
        if (base) {
            const on = window.Alpine && Alpine.store('sonido') ? Alpine.store('sonido').on : leerOn();
            if (ahora.pedido > base.pedido && d.pedido) {
                if (on) tocar(SONIDOS.pedido);
                Alpine.store('toasts').push('🛎️ Nuevo pedido ' + d.pedido.numero + (canal[d.pedido.canal] ? ' por ' + canal[d.pedido.canal] : ''), 'ok');
                window.dispatchEvent(new CustomEvent('gh-pedido-nuevo', { detail: d.pedido }));
            } else if (ahora.comanda > base.comanda) {
                if (on) tocar(SONIDOS.cocina);
                Alpine.store('toasts').push('🍳 Nueva comanda en cocina', 'ok');
                window.dispatchEvent(new CustomEvent('gh-comanda-nueva'));
            }
            if (window.gh) gh.badgePedidos(d.pendientes);
        }
        base = ahora;
    }
    latido();
    setInterval(latido, 20000);
})();
</script>
@endif
