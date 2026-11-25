{{-- GEST-102/106: lógica de la tienda (catálogo, ficha, carrito en localStorage y checkout en 3 pasos) --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('tienda', (cat, cfg, urls, inicial) => ({
        cat, cfg, urls,
        q: '', categoria: null,
        ficha: null, foto: 0, sel: { variante: null, cantidad: 1, peso: null },
        panel: false, paso: 0, lineas: [],
        form: { nombre: '', telefono: '', entrega_id: null, zona_id: '', direccion: '', lat: null, lng: null, programado_para: '', metodo_pago: '', notas: '' },
        programar: false, cupon: '', usarPuntos: false,
        resumen: null, turnoCotizar: 0, error: '', recuperado: false, enviando: false, cotizando: false, ubicando: false,
        salto: false, capturado: '',

        clave() { return 'gh_carrito_' + (this.cfg.negocio.slug || ''); },
        dinero(n) { return (this.cfg.moneda?.simbolo || 'S/') + ' ' + Number(n || 0).toFixed(2); },
        textoPeso(kg) { kg = Number(kg || 0); return kg < 1 ? Math.round(kg * 1000) + ' g' : (Math.round(kg * 100) / 100) + ' kg'; },
        urlProducto(p) { return this.urls.base + '/producto/' + p.slug; },
        token() { return document.querySelector('meta[name=csrf-token]')?.content || ''; },
        normal(t) { return String(t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); },

        // ---------- Catálogo ----------
        visibles() {
            const q = this.normal(this.q).trim();
            return this.cat.productos.filter(p => (this.categoria === null || p.categoria_id === this.categoria)
                && (! q || this.normal(p.nombre + ' ' + (p.descripcion || '')).includes(q)));
        },
        destacados() { return this.cat.productos.filter(p => p.destacado && ! p.agotado).slice(0, 8); },
        tituloLista() {
            if (this.q) return 'Resultados';
            const c = this.cat.categorias.find(c => c.id === this.categoria);
            return c ? c.nombre : 'Todos los productos';
        },

        // ---------- Ficha ----------
        abrir(p, empujar = true) {
            this.ficha = p; this.foto = 0;
            const v = p.variantes.length === 1 && ! p.variantes[0].agotado ? p.variantes[0] : null;
            this.sel = { variante: v, cantidad: 1, peso: p.kilo ? (p.kilo.presentaciones[0] ?? null) : null };
            document.body.style.overflow = 'hidden';
            if (empujar) try { history.pushState({ producto: p.id }, '', this.urlProducto(p)); } catch (e) {}
            document.title = p.nombre + ' · ' + this.cfg.negocio.nombre;
        },
        cerrarFicha(volver = true) {
            if (! this.ficha) return;
            this.ficha = null;
            document.body.style.overflow = this.panel ? 'hidden' : '';
            if (volver) try { history.pushState({}, '', this.urls.base); } catch (e) {}
            document.title = this.cfg.negocio.nombre + ' · Tienda online';
        },
        rapido(p, ev) {
            if (p.agotado) return;
            if (p.variantes.length || p.kilo) return this.abrir(p);
            this.meter(p, null, 1, null, ev);
        },
        precioFicha() {
            const f = this.ficha; if (! f) return 0;
            if (f.kilo) return Math.round(Number(this.sel.peso || 0) * f.kilo.precio_kg * 100) / 100;
            return this.sel.variante ? this.sel.variante.precio : f.precio;
        },
        stockSel() {
            const f = this.ficha; if (! f) return 0;
            const s = this.sel.variante ? this.sel.variante.stock : f.stock;
            return Math.max(0, Math.min(99, s ?? 99));
        },
        listoFicha() {
            const f = this.ficha; if (! f || f.agotado) return false;
            if (f.variantes.length && ! this.sel.variante) return false;
            if (f.kilo) return Number(this.sel.peso) >= Number(f.kilo.peso_minimo || 0.05) && Number(this.sel.peso) <= 50;
            return this.stockSel() > 0;
        },
        agregarFicha(ev) {
            if (! this.listoFicha()) return;
            const f = this.ficha;
            this.meter(f, this.sel.variante, f.kilo ? 1 : this.sel.cantidad, f.kilo ? Number(this.sel.peso) : null, ev);
            this.cerrarFicha();
        },

        // ---------- Carrito ----------
        meter(p, v, cantidad, peso, ev) {
            const clave = p.id + '-' + (v ? v.id : 0) + '-' + (peso || 0);
            const stock = v ? v.stock : p.stock;
            const tope = peso ? 99 : Math.max(1, Math.min(99, stock ?? 99));
            const l = this.lineas.find(x => x.clave === clave);
            if (l) l.cantidad = Math.min(tope, l.cantidad + cantidad);
            else this.lineas.push({
                clave, producto_id: p.id, variante_id: v ? v.id : null, peso,
                nombre: p.nombre, foto: p.fotos[0] || null, cantidad: Math.min(tope, cantidad), tope,
                detalle: [v ? v.nombre : null, peso ? this.textoPeso(peso) : null].filter(Boolean).join(' · '),
                precio: peso ? Math.round(peso * p.kilo.precio_kg * 100) / 100 : (v ? v.precio : p.precio),
            });
            this.guardar(); this.resumen = null;
            this.salto = false; this.$nextTick(() => { this.salto = true; setTimeout(() => this.salto = false, 500); });
            this.aviso(p.nombre + ' agregado');
            if (ev?.target) { const b = ev.target.closest('button'); b?.classList.add('agregado'); setTimeout(() => b?.classList.remove('agregado'), 700); }
        },
        aviso(t) {
            const el = document.createElement('div'); el.className = 'toast-t'; el.textContent = '✓ ' + t;
            document.body.appendChild(el); setTimeout(() => el.remove(), 2200);
        },
        unidades() { return this.lineas.reduce((n, l) => n + (l.peso ? 1 : l.cantidad), 0); },
        subtotalLocal() { return Math.round(this.lineas.reduce((n, l) => n + l.precio * l.cantidad, 0) * 100) / 100; },
        cambiar(k, d) {
            const l = this.lineas[k]; if (! l) return;
            l.cantidad = Math.max(1, Math.min(l.tope || 99, l.cantidad + d));
            // Mientras llega la cotización nueva se muestra el total calculado aquí, nunca el anterior
            this.resumen = null; this.guardar(); this.cotizar();
        },
        quitar(k) {
            this.lineas.splice(k, 1); this.resumen = null; this.guardar();
            if (! this.lineas.length) { this.paso = 0; this.resumen = null; } else this.cotizar();
        },
        guardar() {
            try { localStorage.setItem(this.clave(), JSON.stringify({ lineas: this.lineas, form: { nombre: this.form.nombre, telefono: this.form.telefono, direccion: this.form.direccion }, t: Date.now() })); } catch (e) {}
        },
        cargar() {
            try {
                const d = JSON.parse(localStorage.getItem(this.clave()) || 'null');
                if (! d || Date.now() - (d.t || 0) > 7 * 864e5) return;
                // Solo restauramos líneas de productos que siguen disponibles, con su precio actual
                this.lineas = (d.lineas || []).map(l => {
                    const p = this.cat.productos.find(x => x.id === l.producto_id); if (! p || p.agotado) return null;
                    const v = l.variante_id ? p.variantes.find(x => x.id === l.variante_id) : null;
                    if (l.variante_id && (! v || v.agotado)) return null;
                    if (l.peso && ! p.kilo) return null;
                    return { ...l, nombre: p.nombre, foto: p.fotos[0] || null, precio: l.peso ? Math.round(l.peso * p.kilo.precio_kg * 100) / 100 : (v ? v.precio : p.precio) };
                }).filter(Boolean);
                Object.assign(this.form, d.form || {});
            } catch (e) {}
        },
        payload(extra = {}) {
            return {
                carrito: this.lineas.map(l => ({ producto_id: l.producto_id, variante_id: l.variante_id, cantidad: l.cantidad, peso: l.peso })),
                entrega_id: this.form.entrega_id, zona_id: this.form.zona_id || null, cupon: this.cupon || null,
                usar_puntos: this.usarPuntos, telefono: this.form.telefono || null, ...extra,
            };
        },
        async post(url, body) {
            const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.token(), 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) });
            let data = {}; try { data = await r.json(); } catch (e) {}
            return { ok: r.ok, status: r.status, data };
        },
        primerError(data) {
            const e = data?.errors; if (e) { const k = Object.keys(e)[0]; return { campo: k, texto: e[k][0] }; }
            return { campo: null, texto: data?.message || 'No pudimos procesar tu pedido. Intenta de nuevo.' };
        },

        // ---------- Checkout ----------
        entregaSel() { return this.cfg.entregas.find(e => e.id === this.form.entrega_id) || null; },
        pagoSel() { return this.cfg.pagos.find(m => m.clave === this.form.metodo_pago) || null; },
        abrirCarrito() {
            this.cerrarFicha(false); this.panel = true; this.error = '';
            document.body.style.overflow = 'hidden';
            if (this.lineas.length) this.cotizar();
        },
        ubicar() {
            if (! navigator.geolocation) { this.error = 'Tu navegador no permite compartir la ubicación.'; return; }
            this.ubicando = true;
            navigator.geolocation.getCurrentPosition(
                pos => { this.form.lat = +pos.coords.latitude.toFixed(6); this.form.lng = +pos.coords.longitude.toFixed(6); this.ubicando = false; },
                () => { this.ubicando = false; this.error = 'No pudimos obtener tu ubicación. Escribe tu dirección con una referencia.'; },
                { enableHighAccuracy: true, timeout: 10000 });
        },
        async cotizar() {
            if (! this.lineas.length) return;
            this.cotizando = true;
            // Solo vale la respuesta de la última cotización (si pulsan + rápido, una vieja puede llegar después)
            const turno = this.turnoCotizar = (this.turnoCotizar || 0) + 1;
            try {
                let r = await this.post(this.urls.cotizar, this.payload());
                if (turno !== this.turnoCotizar) return;
                if (! r.ok && r.data?.errors?.cupon) {
                    // Cupón no válido: avisamos y cotizamos sin él para no bloquear la compra
                    this.error = r.data.errors.cupon[0]; this.cupon = '';
                    r = await this.post(this.urls.cotizar, this.payload());
                    if (turno !== this.turnoCotizar) return;
                    if (r.ok) { this.resumen = r.data; return; }
                }
                if (r.ok) { this.resumen = r.data; this.error = ''; }
                else this.error = this.primerError(r.data).texto;
            } catch (e) { this.error = 'Sin conexión. Revisa tu internet.'; }
            finally { if (turno === this.turnoCotizar) this.cotizando = false; }
        },
        // GEST-85: si deja su celular y no termina, guardamos el carrito para recordárselo
        async capturar() {
            const tel = (this.form.telefono || '').replace(/\D/g, '');
            if (tel.length < 9 || ! this.lineas.length) return;
            this.guardar();
            const firma = tel + '|' + JSON.stringify(this.lineas.map(l => [l.clave, l.cantidad]));
            if (firma === this.capturado) return;
            this.capturado = firma;
            try {
                await this.post(this.urls.capturar, { nombre: this.form.nombre, telefono: tel, total: this.subtotalLocal(),
                    carrito: this.lineas.map(l => ({ producto_id: l.producto_id, variante_id: l.variante_id, nombre: l.nombre + (l.detalle ? ' (' + l.detalle + ')' : ''), cantidad: l.cantidad, precio: l.precio, peso: l.peso })) });
            } catch (e) { /* no bloquea la compra */ }
        },
        validarPaso() {
            if (this.paso === 0) return this.lineas.length ? '' : 'Tu carrito está vacío.';
            if (this.paso === 1) {
                if (this.form.nombre.length < 2) return 'Escribe tu nombre.';
                if (! /^[0-9 +()-]{9,20}$/.test(this.form.telefono) || this.form.telefono.replace(/\D/g, '').length < 9) return 'Escribe un celular válido (9 dígitos).';
                const e = this.entregaSel(); if (! e) return 'Elige cómo quieres recibir tu pedido.';
                if (this.subtotalLocal() < e.minimo) return 'El pedido mínimo para ' + e.nombre + ' es ' + this.dinero(e.minimo) + '.';
                if (e.usa_zonas && ! this.form.zona_id) return 'Elige tu zona de entrega.';
                if (e.pide_direccion && this.form.direccion.length < 5) return 'Escribe tu dirección con una referencia.';
                if ((! this.cfg.abierta || this.programar) && ! this.form.programado_para) return this.cfg.abierta ? 'Elige para cuándo lo quieres.' : 'Ahora estamos cerrados. Elige para cuándo lo quieres.';
                return '';
            }
            if (this.paso === 2) return this.pagoSel() ? '' : 'Elige cómo vas a pagar.';
            return '';
        },
        async siguiente() {
            this.error = this.validarPaso();
            if (this.error) { this.sacudir(); return; }
            if (this.paso === 1) this.capturar();
            if (this.paso < 3) {
                this.paso++; this.$nextTick(() => document.querySelector('.cajon-body')?.scrollTo({ top: 0, behavior: 'smooth' }));
                await this.cotizar();
                return;
            }
            this.enviando = true;
            try {
                const progr = (! this.cfg.abierta || this.programar) ? this.form.programado_para : null;
                const r = await this.post(this.urls.pedido, this.payload({ ...this.form, zona_id: this.form.zona_id || null, programado_para: progr || null }));
                if (r.ok && r.data.url) {
                    try { localStorage.removeItem(this.clave()); } catch (e) {}
                    location.href = r.data.url; return;
                }
                const e = this.primerError(r.data); this.error = e.texto;
                if (['nombre', 'telefono', 'entrega_id', 'zona_id', 'direccion', 'programado_para'].includes(e.campo)) this.paso = 1;
                else if (['metodo_pago', 'cupon'].includes(e.campo)) this.paso = 2;
                else if (e.campo && e.campo.startsWith('carrito')) this.paso = 0;
                if (r.status === 419) this.error = 'La página estuvo abierta mucho tiempo. Recárgala y vuelve a intentar (tu carrito se guarda).';
                this.sacudir();
            } catch (e) { this.error = 'Sin conexión. Revisa tu internet e intenta de nuevo.'; }
            this.enviando = false;
        },
        sacudir() { const c = document.querySelector('.cajon'); if (! c) return; c.classList.remove('sacude'); void c.offsetWidth; c.classList.add('sacude'); },

        async iniciar() {
            this.cargar();
            this.form.metodo_pago = this.cfg.pagos[0]?.clave || '';
            if (this.cfg.entregas.length === 1) this.form.entrega_id = this.cfg.entregas[0].id;
            if (! this.cfg.abierta && this.cfg.franjas.length) this.form.programado_para = this.cfg.franjas[0].valor;
            this.$watch('form.nombre', () => this.guardar());
            this.$watch('form.direccion', () => this.guardar());
            this.$watch('panel', v => { if (! v) document.body.style.overflow = this.ficha ? 'hidden' : ''; });
            this.$watch('programar', v => { if (v && ! this.form.programado_para && this.cfg.franjas.length) this.form.programado_para = this.cfg.franjas[0].valor; });
            window.addEventListener('popstate', () => {
                const m = location.pathname.match(/\/producto\/([^/]+)$/);
                const p = m ? this.cat.productos.find(x => x.slug === decodeURIComponent(m[1])) : null;
                if (p) this.abrir(p, false); else this.cerrarFicha(false);
            });
            if (inicial) { const p = this.cat.productos.find(x => x.id === inicial); if (p) this.abrir(p, false); }

            // GEST-85: recuperar un carrito desde el enlace de WhatsApp (?recuperar=token)
            const t = new URLSearchParams(location.search).get('recuperar');
            if (! t || ! /^[a-f0-9]{32}$/.test(t)) return;
            try {
                const r = await fetch(this.urls.recuperar + '/' + t, { headers: { 'Accept': 'application/json' } });
                if (! r.ok) return;
                const d = await r.json();
                this.lineas = [];
                for (const l of (d.carrito || [])) {
                    const p = this.cat.productos.find(x => x.id === l.producto_id); if (! p || p.agotado) continue;
                    const v = l.variante_id ? p.variantes.find(x => x.id === l.variante_id && ! x.agotado) : null;
                    if (p.variantes.length && ! v) continue;
                    if (p.kilo && ! l.peso) continue;
                    this.meter(p, v, Math.max(1, l.cantidad || 1), l.peso || null, null);
                }
                if (this.lineas.length) {
                    this.recuperado = true; this.cupon = d.cupon || '';
                    if (d.nombre && ! this.form.nombre) this.form.nombre = d.nombre;
                    if (d.telefono && ! this.form.telefono) this.form.telefono = d.telefono;
                    this.abrirCarrito(); this.paso = 1;
                }
                history.replaceState({}, '', this.urls.base);
            } catch (e) {}
        },
    }));
});
</script>
