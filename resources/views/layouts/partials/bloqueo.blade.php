{{--
    Pantalla de bloqueo por inactividad (suspension).
    Tras N minutos sin mover el mouse ni teclear, el panel se cubre con una capa oscura
    y pide la contraseña. La sesion no se cierra. Funciona en varias pestañas a la vez.
--}}
@php
    $Bloqueo = \App\Http\Controllers\Auth\PantallaBloqueoController::class;
    $usuarioBloqueo = auth()->user();
    $cfgBloqueo = [
        'minutos' => $Bloqueo::minutos(),
        'bloqueada' => (bool) session($Bloqueo::SESION, false),
        'urls' => [
            'bloquear' => route('bloqueo.bloquear'),
            'actividad' => route('bloqueo.actividad'),
            'desbloquear' => route('bloqueo.desbloquear'),
        ],
    ];
@endphp
<style>
    .lock-bg{position:fixed;inset:0;z-index:9999;background:radial-gradient(ellipse at top,rgba(30,41,82,.94),rgba(8,12,24,.97));backdrop-filter:blur(16px) saturate(.6);-webkit-backdrop-filter:blur(16px) saturate(.6);display:flex;align-items:center;justify-content:center;padding:16px;}
    .lock-card{width:100%;max-width:380px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:30px 26px 22px;color:#fff;text-align:center;box-shadow:0 30px 80px rgba(0,0,0,.45);}
    .lock-hora{font-size:44px;font-weight:800;letter-spacing:-1.5px;font-variant-numeric:tabular-nums;line-height:1;}
    .lock-fecha{font-size:13px;color:rgba(255,255,255,.6);margin:6px 0 22px;}
    .lock-av{width:64px;height:64px;border-radius:50%;margin:0 auto 10px;background:linear-gradient(135deg,#4B7CF5,#F47B20);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:22px;box-shadow:0 0 0 4px rgba(255,255,255,.12);}
    .lock-nom{font-weight:700;font-size:16px;}
    .lock-mail{font-size:12px;color:rgba(255,255,255,.55);margin-bottom:6px;}
    .lock-msg{font-size:12px;color:rgba(255,255,255,.7);display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.08);padding:5px 10px;border-radius:99px;margin-bottom:18px;}
    .lock-inp{position:relative;}
    .lock-inp .ic{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.5);}
    .lock-inp input{width:100%;height:46px;border-radius:12px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08);color:#fff;font:inherit;font-size:14px;padding:0 44px 0 38px;outline:none;transition:border-color .18s,box-shadow .18s;}
    .lock-inp input::placeholder{color:rgba(255,255,255,.4);}
    .lock-inp input:focus{border-color:#93B4F9;box-shadow:0 0 0 3px rgba(75,124,245,.35);}
    .lock-inp input.err{border-color:#F87171;box-shadow:0 0 0 3px rgba(248,113,113,.25);}
    .lock-ojo{position:absolute;right:6px;top:7px;width:32px;height:32px;border:none;background:none;color:rgba(255,255,255,.55);cursor:pointer;border-radius:8px;display:flex;align-items:center;justify-content:center;}
    .lock-ojo:hover{background:rgba(255,255,255,.08);color:#fff;}
    .lock-err{font-size:12px;color:#FCA5A5;margin-top:8px;min-height:16px;text-align:left;}
    .lock-btn{width:100%;height:46px;margin-top:10px;border:none;border-radius:12px;background:#F47B20;color:#fff;font:inherit;font-weight:700;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 10px 24px rgba(244,123,32,.35);transition:filter .15s,transform .15s;}
    .lock-btn:hover:not(:disabled){filter:brightness(1.05);transform:translateY(-1px);}
    .lock-btn:disabled{opacity:.7;cursor:wait;}
    .lock-salir{margin-top:14px;background:none;border:none;color:rgba(255,255,255,.55);font:inherit;font-size:12px;cursor:pointer;text-decoration:underline;}
    .lock-salir:hover{color:#fff;}
    .lock-aviso{position:fixed;left:50%;bottom:22px;transform:translateX(-50%);z-index:9998;background:#1A2332;color:#fff;border-radius:14px;padding:12px 14px 12px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 18px 40px rgba(15,23,42,.35);font-size:13px;max-width:calc(100% - 32px);}
    .lock-aviso b{color:#FBBF24;font-variant-numeric:tabular-nums;}
    .lock-aviso button{border:none;background:#4B7CF5;color:#fff;border-radius:9px;padding:7px 12px;font:inherit;font-weight:700;font-size:12px;cursor:pointer;white-space:nowrap;}
</style>

<div x-data="ghBloqueo(@js($cfgBloqueo))" x-init="iniciar()" @gh-bloquear.window="bloquear(false)">
    {{-- Aviso 30 segundos antes --}}
    <div class="lock-aviso" x-show="aviso && !activo" x-cloak x-transition.opacity role="status">
        <x-icon name="clock" size="18" />
        <span>Por inactividad, la pantalla se bloqueará en <b x-text="cuenta + ' s'"></b></span>
        <button type="button" @click="marcar()">Sigo aquí</button>
    </div>

    <div class="lock-bg" x-show="activo" @unless ($cfgBloqueo['bloqueada']) x-cloak @endunless x-ref="capa" role="dialog" aria-modal="true" aria-labelledby="lock-ttl"
         @keydown.tab.prevent="$refs.clave.focus()" @if ($cfgBloqueo['bloqueada']) style="display:flex" @endif>
        <form class="lock-card" x-ref="tarjeta" @submit.prevent="desbloquear()" novalidate>
            <div class="lock-hora" x-text="hora"></div>
            <div class="lock-fecha" x-text="fecha"></div>

            <div class="lock-av">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($usuarioBloqueo->name ?? 'U', 0, 1)) }}</div>
            <div class="lock-nom" id="lock-ttl">{{ $usuarioBloqueo->name ?? 'Usuario' }}</div>
            <div class="lock-mail">{{ $usuarioBloqueo->email ?? '' }}</div>
            <div class="lock-msg"><x-icon name="lock" size="13" /> Pantalla bloqueada por inactividad</div>

            <div class="lock-inp">
                <x-icon name="lock" size="16" />
                <input x-ref="clave" :type="ver ? 'text' : 'password'" x-model="clave" :class="{ err: error }"
                       placeholder="Tu contraseña" autocomplete="current-password" aria-label="Contraseña" @input="error = ''">
                <button type="button" class="lock-ojo" @click="ver = !ver" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'"><x-icon name="eye" size="16" /></button>
            </div>
            <div class="lock-err" x-text="error" aria-live="assertive"></div>

            <button class="lock-btn" type="submit" :disabled="enviando">
                <x-icon name="shield" size="16" /> <span x-text="enviando ? 'Verificando…' : 'Desbloquear'"></span>
            </button>
            <button type="button" class="lock-salir" @click="$refs.salir.submit()">¿No eres tú? Cerrar sesión</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" x-ref="salir" hidden>@csrf</form>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('ghBloqueo', (cfg) => ({
        activo: cfg.bloqueada, aviso: false, cuenta: 30,
        clave: '', ver: false, error: '', enviando: false,
        hora: '', fecha: '',
        ultimo: Date.now(), ultimoLatido: 0,

        get limite() { return cfg.minutos * 60 * 1000; },

        iniciar() {
            const marcar = () => this.marcar();
            let t = 0;
            const suave = () => { const n = Date.now(); if (n - t > 1000) { t = n; marcar(); } };
            ['keydown', 'pointerdown', 'touchstart', 'wheel'].forEach(ev => window.addEventListener(ev, marcar, { passive: true }));
            ['mousemove', 'scroll'].forEach(ev => window.addEventListener(ev, suave, { passive: true }));

            // Sincroniza varias pestañas abiertas
            window.addEventListener('storage', (e) => {
                if (e.key === 'gh-actividad' && e.newValue) this.ultimo = Math.max(this.ultimo, Number(e.newValue));
                if (e.key === 'gh-bloqueo' && e.newValue === '1' && !this.activo) this.bloquear(false);
                if (e.key === 'gh-bloqueo' && e.newValue === '0' && this.activo) this.cerrarCapa();
            });

            setInterval(() => this.tick(), 1000);
            this.reloj();
            if (this.activo) this.$nextTick(() => this.mostrarCapa());
        },

        marcar() {
            if (this.activo) return;
            this.ultimo = Date.now();
            this.aviso = false;
            try { localStorage.setItem('gh-actividad', String(this.ultimo)); } catch (e) {}
        },

        tick() {
            this.reloj();
            if (this.activo) return;
            const quieto = Date.now() - this.ultimo;
            if (quieto >= this.limite) { this.bloquear(true); return; }
            const resta = Math.ceil((this.limite - quieto) / 1000);
            this.aviso = resta <= 30;
            this.cuenta = resta;
            // Latido al servidor cada minuto mientras el usuario trabaja
            if (quieto < 60000 && Date.now() - this.ultimoLatido > 60000) {
                this.ultimoLatido = Date.now();
                gh.api(cfg.urls.actividad, { method: 'POST' }).then(r => { if (r.status === 423) this.bloquear(false); });
            }
        },

        reloj() {
            const d = new Date();
            this.hora = d.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
            const f = d.toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long' });
            this.fecha = f.charAt(0).toUpperCase() + f.slice(1);
        },

        bloquear(avisarServidor = true) {
            if (this.activo) return;
            this.activo = true; this.aviso = false; this.clave = ''; this.error = '';
            document.querySelectorAll('.modal-bg').forEach(m => m.blur && m.blur());
            if (avisarServidor) gh.api(cfg.urls.bloquear, { method: 'POST' });
            try { localStorage.setItem('gh-bloqueo', '1'); } catch (e) {}
            this.$nextTick(() => this.mostrarCapa());
        },

        mostrarCapa() {
            if (window.ghAnim) ghAnim.modal(this.$refs.capa);
            setTimeout(() => this.$refs.clave && this.$refs.clave.focus(), 120);
        },

        cerrarCapa() {
            const fin = () => { this.activo = false; this.ultimo = Date.now(); this.$refs.capa.style.opacity = ''; };
            if (typeof anime === 'function') {
                anime({ targets: this.$refs.tarjeta, opacity: [1, 0], translateY: [0, -24], scale: [1, .96], duration: 260, easing: 'easeInQuad' });
                anime({ targets: this.$refs.capa, opacity: [1, 0], duration: 320, delay: 80, easing: 'linear', complete: () => { fin(); this.$refs.tarjeta.style.cssText = ''; } });
            } else fin();
        },

        async desbloquear() {
            if (!this.clave) { this.error = 'Escribe tu contraseña para continuar.'; this.sacudir(); return; }
            this.enviando = true; this.error = '';
            const r = await gh.api(cfg.urls.desbloquear, { method: 'POST', body: { password: this.clave } });
            this.enviando = false;
            if (r.ok) {
                this.clave = '';
                try { localStorage.setItem('gh-bloqueo', '0'); } catch (e) {}
                this.cerrarCapa();
                if (r.data.message) Alpine.store('toasts').push(r.data.message);
                return;
            }
            if (r.data && r.data.cerrar_sesion) { location.href = r.data.redirect || '/login'; return; }
            this.error = (r.data.errors && r.data.errors.password && r.data.errors.password[0]) || r.data.message || 'No se pudo verificar la contraseña.';
            this.clave = '';
            this.sacudir();
            this.$refs.clave.focus();
        },

        sacudir() {
            if (typeof anime === 'function') anime({ targets: this.$refs.tarjeta, translateX: [0, -12, 12, -8, 8, -4, 0], duration: 460, easing: 'easeInOutSine' });
        },
    }));
});
</script>
