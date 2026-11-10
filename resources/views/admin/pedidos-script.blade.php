<script>
    gh.ready(() => {
        Alpine.store('pedidos', { buscar: '', activos: 0, vista: localStorage.getItem('gh-pedidos-vista') || 'tablero' });
        const URL_BASE = '{{ url('admin/pedidos') }}';
        const URL_LISTA = '{{ route('admin.pedidos.lista') }}';
        const toast = (t, tipo) => Alpine.store('toasts').push(t, tipo);
        let uid = 0;

        let d = null;   // arrastre en curso (fuera de la reactividad de Alpine)

        Alpine.data('pedidosPage', (inicial, columnas, clientes, catalogo) => ({
            pedidos: inicial, columnas, clientes, catalogo,
            arrastrado: null, sobre: null, movido: null, cargando: false, reloj: null, moviendo: false,
            menu: null, clickBloqueado: 0,
            filtro: 'activos', pagina: 1, porPagina: 15,
            filtros: [
                { clave: 'activos', titulo: 'En curso' }, { clave: 'hoy', titulo: 'Hoy' },
                { clave: 'entregados', titulo: 'Entregados' }, { clave: 'cancelados', titulo: 'Cancelados' },
                { clave: 'todos', titulo: 'Todos' },
            ],
            pagos: { efectivo: 'Efectivo', yape: 'Yape', plin: 'Plin', tarjeta: 'Tarjeta', mercadopago: 'Mercado Pago', transferencia: 'Transferencia' },
            pasos: [['pendiente', 'Pendiente'], ['confirmado', 'Confirmado'], ['preparando', 'Preparando'], ['listo', 'Listo'], ['en_camino', 'En camino'], ['entregado', 'Entregado']],
            detalle: null, cargandoDetalle: false,
            confirmar: null, motivo: '',
            guardando: false, errores: {},
            form: { abierto: false, items: [] },
            buscador: { q: '', abierto: false },

            init() {
                const contar = () => {
                    const activos = this.pedidos.filter(p => ['pendiente', 'preparando', 'en_camino'].includes(p.columna));
                    Alpine.store('pedidos').activos = activos.length;
                    gh.badgePedidos(this.pedidos.filter(p => p.columna === 'pendiente').length);
                };
                this.$watch('pedidos', contar); contar();
                this.$watch('$store.pedidos.vista', v => { try { localStorage.setItem('gh-pedidos-vista', v); } catch (e) {} });
                this.$watch('$store.pedidos.buscar', () => this.pagina = 1);
                this.reloj = setInterval(() => {
                    if (!this.arrastrado && !this.menu && !this.moviendo && !this.form.abierto && !this.detalle && !this.confirmar && !document.hidden) this.recargar();
                }, 30000);
                const q = new URLSearchParams(location.search);
                if (q.get('nuevo')) this.$nextTick(() => this.abrirForm());
            },
            destroy() { clearInterval(this.reloj); this.terminarArrastre(); },

            // ---------- Filtros y totales ----------
            coincide(p) {
                const q = gh.norm(Alpine.store('pedidos').buscar.trim());
                return !q || gh.norm(p.numero).includes(q) || gh.norm(p.cliente).includes(q) || String(p.telefono || '').includes(q);
            },
            deColumna(clave) {
                return this.pedidos.filter(p => p.columna === clave && this.coincide(p) && (clave !== 'entregado' || p.reciente));
            },
            esHoy(p) { return new Date(p.creado).toDateString() === new Date().toDateString(); },
            deHoy() { return this.pedidos.filter(p => this.esHoy(p) && p.estado !== 'cancelado'); },
            totalDe(lista) { return lista.reduce((s, p) => s + Number(p.total || 0), 0); },
            filtrar(f) {
                return this.pedidos.filter(p => this.coincide(p) && ({
                    activos: () => ['pendiente', 'preparando', 'en_camino'].includes(p.columna),
                    hoy: () => this.esHoy(p),
                    entregados: () => p.estado === 'entregado',
                    cancelados: () => p.estado === 'cancelado',
                    todos: () => true,
                })[f]());
            },
            get enLista() { return this.filtrar(this.filtro); },
            get paginas() { return Math.max(1, Math.ceil(this.enLista.length / this.porPagina)); },
            get paginaLista() { return this.enLista.slice((this.pagina - 1) * this.porPagina, this.pagina * this.porPagina); },
            irAColumna(clave) {
                Alpine.store('pedidos').vista = 'tablero';
                this.$nextTick(() => document.getElementById('col-' + clave)?.scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' }));
            },

            // ---------- Textos ----------
            tituloDe(clave) { return (this.columnas.find(c => c.clave === clave) || {}).titulo; },
            siguiente(p) {
                if (!p || p.estado === 'cancelado') return null;
                const i = this.columnas.findIndex(c => c.clave === p.columna);
                return i >= 0 && i < this.columnas.length - 1 ? this.columnas[i + 1].clave : null;
            },
            editable(p) { return p && !['entregado', 'cancelado'].includes(p.estado); },
            tipoTexto(t) { return { delivery: 'Delivery', recojo: 'Recojo', mesa: 'En mesa' }[t] || t || ''; },
            pagoTexto(m) { return this.pagos[m] || m || ''; },
            canalTexto(c) { return { web: 'Web', whatsapp: 'WhatsApp', pos: 'POS', app: 'App', manual: 'Manual' }[c] || c || ''; },
            indicePaso(e) { return this.pasos.findIndex(x => x[0] === e); },
            whatsapp(p) {
                if (!p?.telefono) return '#';
                let n = String(p.telefono).replace(/\D/g, '');
                if (n.length === 9) n = '51' + n;
                const txt = `Hola ${p.cliente}, tu pedido ${p.numero} está: ${p.estado_texto}. Total: ${gh.soles(p.total)}`;
                return 'https://wa.me/' + n + '?text=' + encodeURIComponent(txt);
            },
            exportar() {
                const filas = this.enLista.map(p => [p.numero, p.fecha, p.cliente, p.telefono, this.tipoTexto(p.tipo), this.pagoTexto(p.metodo_pago), p.pagado ? 'Sí' : 'No', p.total, p.estado_texto]);
                gh.csv('pedidos.csv', [['Pedido', 'Fecha', 'Cliente', 'Teléfono', 'Entrega', 'Pago', 'Pagado', 'Total', 'Estado'], ...filas]);
                toast('Se descargó pedidos.csv', 'info');
            },

            // ---------- Kanban: arrastrar y soltar (mouse, tactil y lapiz) ----------
            // Con mouse el arrastre empieza al mover 5px; en pantallas tactiles, al mantener
            // presionada la tarjeta 220 ms (asi el dedo puede seguir desplazando la pagina).
            iniciarArrastre(e, p) {
                if (e.button > 0 || p.estado === 'cancelado' || this.moviendo) return;
                if (e.target.closest('button, a, input, select, textarea')) return;
                this.terminarArrastre();
                const tactil = e.pointerType !== 'mouse';
                d = { p, el: e.currentTarget, x0: e.clientX, y0: e.clientY, x: e.clientX, y: e.clientY, activo: false, tactil, timer: null, fantasma: null, scroll: null };
                d.mover = (ev) => this.moverArrastre(ev);
                d.soltar = (ev) => this.soltarArrastre(ev);
                d.cancelar = () => this.terminarArrastre();
                d.bloquearScroll = (ev) => { if (d && d.activo) ev.preventDefault(); };
                d.tecla = (ev) => { if (ev.key === 'Escape') this.terminarArrastre(); };
                d.menuContexto = (ev) => ev.preventDefault();   // la pulsacion larga no abre el menu del navegador
                window.addEventListener('pointermove', d.mover);
                window.addEventListener('pointerup', d.soltar);
                window.addEventListener('pointercancel', d.cancelar);
                window.addEventListener('keydown', d.tecla);
                window.addEventListener('touchmove', d.bloquearScroll, { passive: false });
                if (tactil) window.addEventListener('contextmenu', d.menuContexto);
                if (tactil) d.timer = setTimeout(() => d && this.empezarArrastre(), 220);
            },
            empezarArrastre() {
                const r = d.el.getBoundingClientRect();
                d.activo = true;
                d.ox = d.x0 - r.left; d.oy = d.y0 - r.top;
                const f = d.el.cloneNode(true);
                f.setAttribute('x-ignore', '');           // Alpine no debe procesar la copia
                f.classList.add('kghost');
                f.classList.remove('kcard-moved', 'kcard-drag');
                Object.assign(f.style, { width: r.width + 'px', left: '0px', top: '0px', animation: 'none' });
                document.body.appendChild(f);
                d.fantasma = f;
                document.body.classList.add('kdragging');
                this.arrastrado = d.p.id;
                this.menu = null;
                if (d.tactil) navigator.vibrate?.(12);
                this.ubicarFantasma();
                // Desplaza el tablero o la pagina al acercarse a los bordes
                d.scroll = setInterval(() => {
                    if (!d || !d.activo) return;
                    const board = this.$refs.board, br = board?.getBoundingClientRect();
                    if (br) {
                        if (d.x > br.right - 60) board.scrollLeft += 14;
                        else if (d.x < br.left + 60) board.scrollLeft -= 14;
                    }
                    if (d.y > innerHeight - 50) window.scrollBy(0, 14);
                    else if (d.y < 70) window.scrollBy(0, -14);
                    const lista = document.elementFromPoint(d.x, d.y)?.closest('.klist');
                    if (lista) {
                        const lr = lista.getBoundingClientRect();
                        if (d.y > lr.bottom - 40) lista.scrollTop += 10; else if (d.y < lr.top + 40) lista.scrollTop -= 10;
                    }
                }, 16);
            },
            moverArrastre(e) {
                if (!d) return;
                d.x = e.clientX; d.y = e.clientY;
                const dist = Math.hypot(d.x - d.x0, d.y - d.y0);
                if (!d.activo) {
                    if (d.tactil) { if (dist > 8) this.terminarArrastre(); }   // el dedo esta desplazando la pagina
                    else if (dist > 5) this.empezarArrastre();
                    return;
                }
                e.preventDefault();
                this.ubicarFantasma();
                const zona = document.elementFromPoint(d.x, d.y)?.closest('[data-col]');
                const clave = zona ? zona.dataset.col : null;
                this.sobre = clave && this.puedeSoltar(d.p, clave) ? clave : null;
            },
            ubicarFantasma() {
                if (d?.fantasma) d.fantasma.style.transform = `translate(${d.x - d.ox}px, ${d.y - d.oy}px) rotate(2deg) scale(1.03)`;
            },
            puedeSoltar(p, clave) {
                if (clave === 'cancelar') return this.editable(p);
                return clave !== p.columna;
            },
            soltarArrastre() {
                if (!d) return;
                const p = d.p, activo = d.activo, destino = this.sobre;
                this.terminarArrastre();
                if (!activo) return;                       // fue un clic: lo maneja @click
                this.clickBloqueado = Date.now();          // evita abrir el detalle al soltar
                if (!destino) return;
                if (destino === 'cancelar') this.pedirCancelar(p);
                else this.mover(p, destino);
            },
            terminarArrastre() {
                if (d) {
                    clearTimeout(d.timer); clearInterval(d.scroll);
                    window.removeEventListener('pointermove', d.mover);
                    window.removeEventListener('pointerup', d.soltar);
                    window.removeEventListener('pointercancel', d.cancelar);
                    window.removeEventListener('keydown', d.tecla);
                    window.removeEventListener('touchmove', d.bloquearScroll);
                    window.removeEventListener('contextmenu', d.menuContexto);
                    d.fantasma?.remove();
                }
                d = null;
                document.body.classList.remove('kdragging');
                this.arrastrado = null; this.sobre = null;
            },
            abrirTarjeta(p) {
                if (Date.now() - this.clickBloqueado < 350) return;
                this.verDetalle(p);
            },

            // ---------- Selector rapido de estado (tarjetas, bandeja y detalle) ----------
            abrirMenu(e, p) {
                const r = e.currentTarget.getBoundingClientRect();
                if (this.menu && this.menu.p.id === p.id) { this.menu = null; return; }
                const ancho = 220, alto = 56 + this.columnas.length * 38 + (this.editable(p) ? 46 : 0);
                const x = Math.max(8, Math.min(r.left, innerWidth - ancho - 8));
                const y = r.bottom + alto + 8 > innerHeight ? Math.max(8, r.top - alto - 6) : r.bottom + 6;
                this.menu = { p: this.pedidos.find(x => x.id === p.id) || p, x, y };
                this.$nextTick(() => this.$refs.menuEstado?.querySelector('button:not([disabled])')?.focus());
            },
            async elegirEstado(clave) {
                const p = this.menu?.p;
                this.menu = null;
                if (!p) return;
                if (clave === 'cancelar') { this.pedirCancelar(p); return; }
                if (clave === p.columna) return;
                await this.mover(p, clave);
                if (this.detalle && this.detalle.id === p.id) await this.cargarDetalle(p.id);
            },
            reemplazar(dato) {
                const i = this.pedidos.findIndex(x => x.id === dato.id);
                if (i >= 0) this.pedidos.splice(i, 1, dato); else this.pedidos.unshift(dato);
                if (this.detalle && this.detalle.id === dato.id) Object.assign(this.detalle, dato);
                this.movido = dato.id; setTimeout(() => this.movido = null, 600);
            },
            async mover(p, clave) {
                const enLista = this.pedidos.find(x => x.id === p.id) || p;
                if (!clave || enLista.columna === clave) return;
                const anterior = { ...enLista };
                const col = this.columnas.find(c => c.clave === clave);
                // Movimiento optimista: la tarjeta cambia de columna al instante
                Object.assign(enLista, { columna: clave, estado_texto: col.titulo, badge: col.badge });
                this.moviendo = true;
                const r = await gh.api(URL_BASE + '/' + p.id + '/estado', { method: 'PATCH', body: { estado: clave } });
                this.moviendo = false;
                if (!r.ok) { Object.assign(enLista, anterior); toast(r.data.message || (r.data.errors ? Object.values(r.data.errors)[0][0] : 'No se pudo mover el pedido'), 'error'); return; }
                this.reemplazar(r.data.data);
                toast(r.data.message);
            },
            async cambiarEstado(p, estado) {
                if (!p || p.estado === estado) return;
                this.moviendo = true;
                const r = await gh.api(URL_BASE + '/' + p.id + '/estado', { method: 'PATCH', body: { estado } });
                this.moviendo = false;
                if (!r.ok) { toast(r.data.message || 'No se pudo cambiar el estado', 'error'); return; }
                this.reemplazar(r.data.data);
                await this.cargarDetalle(p.id);
                toast(r.data.message);
            },
            async alternarPago(p) {
                const r = await gh.api(URL_BASE + '/' + p.id + '/pagado', { method: 'PATCH' });
                if (!r.ok) { toast(r.data.message || 'No se pudo actualizar el pago', 'error'); return; }
                this.reemplazar(r.data.data);
                toast(r.data.message);
            },
            async recargar(manual = false) {
                this.cargando = true;
                const r = await gh.api(URL_LISTA);
                this.cargando = false;
                if (r.ok) {
                    this.pedidos = r.data.data;
                    if (manual) toast('Pedidos actualizados', 'info');
                }
            },

            // ---------- Detalle ----------
            async verDetalle(p) {
                this.detalle = { ...p, lineas: [], historial: [] };
                await this.cargarDetalle(p.id);
            },
            async cargarDetalle(id) {
                this.cargandoDetalle = true;
                const r = await gh.api(URL_BASE + '/' + id);
                this.cargandoDetalle = false;
                if (r.ok && this.detalle && this.detalle.id === id) this.detalle = r.data.data;
                else if (!r.ok) toast(r.data.message || 'No se pudo cargar el pedido', 'error');
            },

            // ---------- Cancelar ----------
            pedirCancelar(p) { this.confirmar = p; this.motivo = ''; },
            async cancelar() {
                const p = this.confirmar;
                this.guardando = true;
                const r = await gh.api(URL_BASE + '/' + p.id + '/cancelar', { method: 'PATCH', body: { motivo: this.motivo } });
                this.guardando = false;
                if (!r.ok) { toast(r.data.message || 'No se pudo cancelar', 'error'); return; }
                this.confirmar = null;
                this.catalogo = r.data.catalogo;
                this.reemplazar(r.data.data);
                if (this.detalle && this.detalle.id === p.id) await this.cargarDetalle(p.id);
                toast(r.data.message);
            },

            // ---------- Formulario ----------
            vacio() {
                return { abierto: true, id: null, numero: '', cliente_id: '', cliente_nuevo: false, cliente_nombre: '', cliente_telefono: '',
                    tipo: 'delivery', direccion_entrega: '', costo_envio: '', descuento: '', metodo_pago: 'yape', pagado: false, notas: '', items: [] };
            },
            abrirForm() {
                this.errores = {};
                this.buscador = { q: '', abierto: false };
                this.form = this.vacio();
            },
            async editar(p) {
                const r = await gh.api(URL_BASE + '/' + p.id);
                if (!r.ok) { toast(r.data.message || 'No se pudo abrir el pedido', 'error'); return; }
                const d = r.data.data;
                this.detalle = null;
                this.errores = {};
                this.buscador = { q: '', abierto: false };
                this.form = {
                    ...this.vacio(), id: d.id, numero: d.numero, cliente_id: d.cliente_id || '', tipo: d.tipo,
                    direccion_entrega: d.direccion || '', costo_envio: d.costo_envio || '', descuento: d.descuento || '',
                    metodo_pago: d.metodo_pago, pagado: d.pagado, notas: d.notas || '',
                    // Al editar, lo que ya tenia el pedido cuenta como disponible
                    items: d.lineas.filter(l => l.producto_id).map(l => ({ uid: ++uid, producto_id: l.producto_id, variante_id: l.variante_id || '', cantidad: l.cantidad, notas: l.notas, reservado: l.cantidad })),
                };
            },
            alElegirCliente() {
                const c = this.clientes.find(x => String(x.id) === String(this.form.cliente_id));
                if (c && c.direccion && !this.form.direccion_entrega) this.form.direccion_entrega = c.direccion;
            },
            get sugerencias() {
                const q = gh.norm(this.buscador.q.trim());
                return this.catalogo.filter(p => !q || gh.norm(p.nombre).includes(q) || gh.norm(p.categoria).includes(q)).slice(0, 8);
            },
            prod(l) { return this.catalogo.find(p => p.id === Number(l.producto_id)) || {}; },
            variante(l) { return (this.prod(l).variantes || []).find(v => String(v.id) === String(l.variante_id)); },
            stockDe(p) { return p.variantes.length ? p.variantes.reduce((s, v) => s + v.stock, 0) : p.stock; },
            precioDesde(p) { return p.variantes.length ? Math.min(...p.variantes.map(v => v.precio)) : p.precio; },
            precio(l) { const v = this.variante(l); return v ? v.precio : (this.prod(l).variantes?.length ? 0 : (this.prod(l).precio || 0)); },
            disponible(l, v = null) {
                v = v || this.variante(l);
                const base = v ? v.stock : (this.prod(l).variantes?.length ? 0 : (this.prod(l).stock || 0));
                const mismo = !v || String(v.id) === String(l.variante_id);
                return base + (mismo ? (l.reservado || 0) : 0);
            },
            agregar(p) {
                if (this.stockDe(p) <= 0) return;
                const variante = p.variantes.length === 1 ? p.variantes[0].id : (p.variantes.find(v => v.stock > 0)?.id || '');
                const existe = this.form.items.find(l => l.producto_id === p.id && (!p.variantes.length || String(l.variante_id) === String(variante)));
                if (existe) existe.cantidad++;
                else this.form.items.push({ uid: ++uid, producto_id: p.id, variante_id: variante, cantidad: 1, notas: '', reservado: 0 });
                this.buscador = { q: '', abierto: false };
                delete this.errores.items;
                this.$nextTick(() => this.$refs.buscarProd?.focus());
            },
            get resumen() {
                const subtotal = this.form.items.reduce((s, l) => s + this.precio(l) * (Number(l.cantidad) || 0), 0);
                const descuento = Math.min(Number(this.form.descuento) || 0, subtotal);
                const envio = this.form.tipo === 'delivery' ? (Number(this.form.costo_envio) || 0) : 0;
                return { subtotal, descuento, envio, total: Math.max(0, subtotal - descuento + envio) };
            },
            async guardar() {
                this.guardando = true; this.errores = {};
                const f = this.form;
                const body = {
                    cliente_id: f.cliente_nuevo ? null : f.cliente_id, cliente_nuevo: f.cliente_nuevo,
                    cliente_nombre: f.cliente_nombre, cliente_telefono: f.cliente_telefono || null,
                    tipo: f.tipo, metodo_pago: f.metodo_pago, pagado: f.pagado, notas: f.notas || null,
                    direccion_entrega: f.tipo === 'delivery' ? f.direccion_entrega : null,
                    costo_envio: f.costo_envio || 0, descuento: f.descuento || 0,
                    items: f.items.map(l => ({ producto_id: l.producto_id, variante_id: l.variante_id || null, cantidad: Number(l.cantidad) || 0, notas: l.notas || null })),
                };
                const r = await gh.api(f.id ? URL_BASE + '/' + f.id : URL_BASE, { method: f.id ? 'PUT' : 'POST', body });
                this.guardando = false;
                if (!r.ok) {
                    this.errores = r.data.errors || {};
                    toast(r.status === 422 && r.data.errors ? 'Revisa los campos marcados' : (r.data.message || 'No se pudo guardar'), 'error');
                    return;
                }
                this.form.abierto = false;
                this.catalogo = r.data.catalogo;
                if (r.data.clientes) this.clientes = r.data.clientes;
                this.reemplazar(r.data.data);
                toast(r.data.message);
                // GEST-20: aviso de stock minimo tras la venta
                (r.data.alertas || []).forEach((a, i) => setTimeout(() => toast(a, 'error'), 400 + i * 300));
            },
        }));
    });
</script>
