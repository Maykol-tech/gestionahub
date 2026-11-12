{{--
    Rediseño general del panel + Anime.js (CDN).
    - Anima KPIs (.stat), paneles, filas de tablas, modales (fade + slide-up) y menus desplegables.
    - Tablas redondeadas con sombra al pasar el mouse, badges de estado vivos y paginacion con estilo.
    - Formularios con bordes suaves, iconos dentro del campo y validacion en tiempo real (data-validar).
    Si Anime.js no carga, todo se ve igual pero sin animacion.
--}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.2/anime.min.js" defer></script>
<style>
/* ---------- Tablas ---------- */
.tbl-wrap{border-radius:12px;}
.panel > .tbl-wrap:last-child{border-radius:0 0 12px 12px;}
.tbl-card{border:1px solid var(--border);border-radius:14px;overflow:hidden;background:var(--surface);box-shadow:var(--shadow);}
.tbl tbody tr{transition:box-shadow .2s var(--ease),transform .2s var(--ease);}
.tbl tbody tr:hover{box-shadow:0 6px 18px rgba(26,35,50,.08);transform:translateY(-1px);position:relative;z-index:1;}
.tbl tbody tr:hover td{background:#fff;}
.tbl th:first-child{border-top-left-radius:12px;} .tbl th:last-child{border-top-right-radius:12px;}

/* ---------- Badges vivos ---------- */
.badge{padding:3px 9px;font-weight:700;letter-spacing:.01em;}
.b-ok{background:#DCFCE7;color:#15803D;box-shadow:inset 0 0 0 1px #86EFAC;}
.b-a{background:#FEF3C7;color:#B45309;box-shadow:inset 0 0 0 1px #FCD34D;}
.b-r{background:#FEE2E2;color:#B91C1C;box-shadow:inset 0 0 0 1px #FCA5A5;}
.b-g{background:#E0EAFF;color:#1D4ED8;box-shadow:inset 0 0 0 1px #A5C0FB;}
.b-n{background:#F1F5F9;color:#475569;box-shadow:inset 0 0 0 1px #CBD5E1;}
.b-v{background:#F3E8FF;color:#7E22CE;box-shadow:inset 0 0 0 1px #D8B4FE;}
.b-t{background:#CCFBF1;color:#0F766E;box-shadow:inset 0 0 0 1px #5EEAD4;}

/* ---------- Paginacion ---------- */
.pg{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 16px;border-top:1px solid var(--border);flex-wrap:wrap;}
.pg-info{font-size:12px;color:var(--fg3);}
.pg-list{display:flex;gap:4px;list-style:none;}
.pg-list a,.pg-list span{min-width:32px;height:32px;padding:0 8px;border-radius:9px;border:1px solid var(--border);background:#fff;color:var(--fg2);font-size:12px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;transition:all .18s var(--ease);}
.pg-list a:hover{border-color:var(--accent);color:var(--accent);transform:translateY(-1px);box-shadow:0 4px 10px rgba(75,124,245,.15);}
.pg-list .on span{background:var(--accent);border-color:var(--accent);color:#fff;box-shadow:0 4px 12px rgba(75,124,245,.35);}
.pg-list .off span{opacity:.45;}

/* ---------- Formularios ---------- */
.inp{border-radius:10px;border-color:#DCE3EC;transition:border-color .18s,box-shadow .18s,background-color .18s;}
.inp:hover:not(:focus):not(:disabled){border-color:#C5CFDC;}
.inp.ok{border-color:#22C55E;box-shadow:0 0 0 3px rgba(34,197,94,.12);}
.inp-ico{position:relative;}
.inp-ico > .ic{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--fg3);pointer-events:none;transition:color .18s;}
.inp-ico > .inp{padding-left:34px;}
.inp-ico:focus-within > .ic{color:var(--accent);}
.inp-ico .inp.err ~ .ic,.inp-ico:has(.inp.err) > .ic{color:var(--red);}
.inp-ico:has(.inp.ok) > .ic{color:#16A34A;}
.inp-ico .inp-fin{position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--fg3);pointer-events:none;}
.ferr:empty{display:none;}
.fok{font-size:11px;color:#15803D;margin-top:4px;}

/* ---------- Modales y menus ---------- */
.modal{border-radius:16px;}
.dropdown{border-radius:12px;}
.gh-anim{animation:none !important;}
</style>
<script>
(() => {
    const listo = () => typeof window.anime === 'function';
    const reducido = () => window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

    /** Anima la pagina actual: KPIs, paneles y filas de tablas. */
    function animar(raiz) {
        raiz = raiz || document.getElementById('gh-main');
        if (!raiz || !listo() || reducido()) return;

        const stats = raiz.querySelectorAll('.stat');
        stats.forEach(el => el.classList.add('gh-anim'));
        if (stats.length) {
            anime({ targets: stats, opacity: [0, 1], translateY: [18, 0], scale: [.98, 1], delay: anime.stagger(70), duration: 650, easing: 'easeOutCubic' });
            // Los numeros de los KPIs cuentan desde 0
            raiz.querySelectorAll('.stat-val').forEach(contar);
        }

        const paneles = raiz.querySelectorAll('.panel, .tbl-card');
        paneles.forEach(el => el.classList.add('gh-anim'));
        if (paneles.length) {
            anime({ targets: paneles, opacity: [0, 1], translateY: [14, 0], delay: anime.stagger(60, { start: 120 }), duration: 600, easing: 'easeOutCubic' });
        }

        setTimeout(() => filas(raiz), 40);
    }

    function filas(raiz) {
        if (!listo() || reducido()) return;
        const trs = [...(raiz || document).querySelectorAll('.tbl tbody tr')].slice(0, 25);
        trs.forEach(tr => tr.classList.add('gh-anim'));
        if (trs.length) anime({ targets: trs, opacity: [0, 1], translateX: [-10, 0], delay: anime.stagger(28, { start: 180 }), duration: 450, easing: 'easeOutQuad' });
    }

    /** "S/ 1,234.50" o "27" -> cuenta desde 0 respetando el formato. */
    function contar(el) {
        const texto = el.textContent.trim();
        const m = texto.match(/^([^\d-]*)(-?[\d,]+(?:\.\d+)?)(.*)$/);
        if (!m || el.dataset.contado) return;
        const valor = parseFloat(m[2].replace(/,/g, ''));
        if (!isFinite(valor) || valor === 0) return;
        el.dataset.contado = '1';
        const decimales = (m[2].split('.')[1] || '').length;
        const obj = { v: 0 };
        anime({
            targets: obj, v: valor, duration: 900, easing: 'easeOutExpo',
            update: () => {
                el.textContent = m[1] + obj.v.toLocaleString('en-US', { minimumFractionDigits: decimales, maximumFractionDigits: decimales }) + m[3];
            },
            complete: () => { el.textContent = texto; },
        });
    }

    /** Modal: fondo con fade y tarjeta con slide-up. */
    function modal(fondo) {
        if (!listo() || reducido()) return;
        const tarjeta = fondo.querySelector('.modal, .lock-card, [data-modal-card]');
        anime({ targets: fondo, opacity: [0, 1], duration: 260, easing: 'linear' });
        if (tarjeta) {
            tarjeta.style.transition = 'none';
            anime({
                targets: tarjeta, opacity: [0, 1], translateY: [36, 0], scale: [.97, 1], duration: 520, easing: 'easeOutBack',
                complete: () => { tarjeta.style.transition = ''; tarjeta.style.transform = ''; },
            });
        }
    }

    /** Menu desplegable: las opciones entran en cascada. */
    function menu(dd) {
        if (!listo() || reducido()) return;
        const items = dd.querySelectorAll('.dd-item, .kmenu-item, [data-dd-item], li, button');
        if (items.length) anime({ targets: [...items].slice(0, 12), opacity: [0, 1], translateY: [-6, 0], delay: anime.stagger(30), duration: 300, easing: 'easeOutQuad' });
    }

    // Observa cuando un modal o menu se vuelve visible (x-show cambia el style)
    const visibles = new WeakSet();
    const SEL_MODAL = '.modal-bg, [data-anime-modal]';
    const SEL_MENU = '.dropdown, .kmenu, [data-anime-menu]';
    const observador = new MutationObserver((cambios) => {
        for (const c of cambios) {
            const el = c.target;
            if (!(el instanceof HTMLElement)) continue;
            const esModal = el.matches(SEL_MODAL), esMenu = !esModal && el.matches(SEL_MENU);
            if (!esModal && !esMenu) continue;
            const ve = el.style.display !== 'none' && !el.hidden;
            if (ve && !visibles.has(el)) { visibles.add(el); esModal ? modal(el) : menu(el); }
            else if (!ve) visibles.delete(el);
        }
    });
    document.addEventListener('DOMContentLoaded', () => {
        observador.observe(document.body, { attributes: true, attributeFilter: ['style', 'hidden'], subtree: true });
        const inicio = () => animar();
        listo() ? inicio() : window.addEventListener('load', inicio, { once: true });
    });

    // ---------- Validacion en tiempo real ----------
    // Uso: <form data-validar> ... <input class="inp" required ...><div class="ferr"></div>
    // Extras: data-dni, data-ruc, data-doc (DNI o RUC), data-celular.
    const reglas = (inp) => {
        const v = (inp.value || '').trim();
        const d = v.replace(/\D/g, '');
        if (inp.required && !v) return inp.dataset.msgRequerido || 'Este campo es obligatorio.';
        if (!v) return '';
        if (inp.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return 'Escribe un correo válido (ej. nombre@negocio.pe).';
        if ('dni' in inp.dataset && d.length !== 8) return 'El DNI tiene 8 dígitos.';
        if ('ruc' in inp.dataset && (d.length !== 11 || !/^(10|15|17|20)/.test(d))) return 'El RUC tiene 11 dígitos y empieza con 10 o 20.';
        if ('doc' in inp.dataset && ![8, 11].includes(d.length)) return 'Escribe un DNI (8 dígitos) o RUC (11 dígitos).';
        if ('celular' in inp.dataset && !/^9\d{8}$/.test(d)) return 'El celular tiene 9 dígitos y empieza con 9.';
        if (inp.minLength > 0 && v.length < inp.minLength) return `Escribe al menos ${inp.minLength} caracteres.`;
        if (inp.type === 'number' && inp.min !== '' && Number(v) < Number(inp.min)) return `El valor mínimo es ${inp.min}.`;
        if (inp.pattern && !new RegExp('^(?:' + inp.pattern + ')$').test(v)) return inp.title || 'El formato no es válido.';
        return '';
    };
    const msgDe = (inp) => {
        let el = inp.closest('.field, .fg, .inp-ico')?.parentElement?.querySelector(`.ferr[data-para="${inp.name}"]`)
            || inp.closest('.field, .fg')?.querySelector('.ferr');
        if (!el) { el = document.createElement('div'); el.className = 'ferr'; (inp.closest('.inp-ico') || inp).after(el); }
        return el;
    };
    function validar(inp, mostrar = true) {
        const error = reglas(inp);
        if (mostrar) {
            inp.classList.toggle('err', !!error);
            inp.classList.toggle('ok', !error && !!inp.value.trim());
            msgDe(inp).textContent = error;
        }
        return !error;
    }
    const enForm = (e) => e.target instanceof HTMLElement && e.target.matches('.inp') && e.target.closest('[data-validar]');
    document.addEventListener('focusout', (e) => { if (enForm(e)) { e.target.dataset.tocado = '1'; validar(e.target); } });
    document.addEventListener('input', (e) => { if (enForm(e) && e.target.dataset.tocado) validar(e.target); });
    document.addEventListener('submit', (e) => {
        const f = e.target;
        if (!(f instanceof HTMLFormElement) || !f.matches('[data-validar]')) return;
        const campos = [...f.querySelectorAll('.inp')].filter(i => !i.disabled && i.offsetParent !== null);
        const malos = campos.filter(i => { i.dataset.tocado = '1'; return !validar(i); });
        if (malos.length) {
            e.preventDefault(); e.stopImmediatePropagation();
            malos[0].focus();
            if (listo()) anime({ targets: malos[0], translateX: [0, -6, 6, -4, 4, 0], duration: 380, easing: 'easeInOutSine' });
        }
    }, true);

    window.ghAnim = { animar, filas, modal, menu, contar, validar, reglas };
})();
</script>
