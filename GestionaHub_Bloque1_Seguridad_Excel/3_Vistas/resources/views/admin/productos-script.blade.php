<script>
    gh.ready(() => {
        Alpine.store('productos', { activos: 0, vista: 'productos' });
        const URL_BASE = '{{ url('admin/productos') }}';
        const URL_CAT = '{{ url('admin/categorias') }}';
        let clave = 0;

        Alpine.data('productosPage', (inicial, categorias) => ({
            productos: inicial,
            categorias: categorias,
            vista: 'productos',
            filtro: 'todos', categoria: '', buscar: '', pagina: 1, porPagina: 10, entrada: true, _tEntrada: null,
            orden: { campo: null, dir: 1 },
            modal: false, pestana: 'general', guardando: false, cargando: false, arrastrando: false, nuevaCat: false,
            editando: null, resaltado: null, resaltadoCat: null,
            form: {}, errores: {}, errorGeneral: '',
            galeria: [], nuevas: [], subiendo: false, errGaleria: '', arrastroImg: null,
            modalCat: false, cat: {}, erroresCat: {},
            repo: { abierto: false, producto: null, variante_id: '', cantidad: '', errores: {} },
            confirmar: null,

            animar() { this.entrada = true; clearTimeout(this._tEntrada); this._tEntrada = setTimeout(() => this.entrada = false, 900); },
            init() {
                this.animar();
                this.resetForm();
                const contarActivos = () => Alpine.store('productos').activos = this.productos.filter(p => p.activo).length;
                this.$watch('productos', contarActivos); contarActivos();
                this.$watch('vista', v => Alpine.store('productos').vista = v);
                Alpine.store('productos').vista = this.vista;

                const qs = new URLSearchParams(location.search);
                if (qs.has('nuevo')) this.abrir();
                if (qs.has('editar')) { const p = this.productos.find(x => String(x.id) === qs.get('editar')); if (p) this.abrir(p); }
                if (qs.get('filtro') === 'bajo') this.filtro = 'bajo';
            },
            destroy() { this.nuevas.forEach(n => URL.revokeObjectURL(n.url)); },

            // ---------- Listado ----------
            cumple(p, c) {
                if (c === 'activos') return p.activo;
                if (c === 'bajo') return p.activo && ['bajo', 'sin_stock'].includes(p.estado);
                if (c === 'pausado') return !p.activo;
                return true;
            },
            contar(c) { return this.productos.filter(p => this.cumple(p, c)).length; },
            get bajos() { return this.productos.filter(p => this.cumple(p, 'bajo')).sort((a, b) => a.stock - b.stock); },
            get valorInventario() { return this.productos.reduce((t, p) => t + (p.precio_oferta ?? p.precio) * Math.max(p.stock, 0), 0); },
            get filtrados() {
                const q = gh.norm(this.buscar.trim());
                let lista = this.productos.filter(p => this.cumple(p, this.filtro)
                    && (!this.categoria || String(p.categoria_id) === String(this.categoria))
                    && (!q || [p.nombre, p.sku, p.categoria, ...p.variantes.map(v => v.nombre)].some(v => gh.norm(v).includes(q))));
                const { campo, dir } = this.orden;
                if (campo) lista = [...lista].sort((a, b) => (campo === 'nombre' ? a.nombre.localeCompare(b.nombre) : (a[campo] ?? 0) - (b[campo] ?? 0)) * dir);
                return lista;
            },
            ordenar(campo) { this.orden = { campo, dir: this.orden.campo === campo ? -this.orden.dir : (campo === 'nombre' ? 1 : -1) }; },
            get paginas() { return Math.max(1, Math.ceil(this.filtrados.length / this.porPagina)); },
            get desde() { return (this.pagina - 1) * this.porPagina + 1; },
            get hasta() { return Math.min(this.pagina * this.porPagina, this.filtrados.length); },
            get visibles() { return this.filtrados.slice(this.desde - 1, this.hasta); },
            barra(p) {
                const minimo = p.tiene_variantes ? p.variantes.reduce((t, v) => t + v.stock_minimo, 0) : p.stock_minimo;
                return Math.min(100, Math.round(p.stock * 100 / Math.max(minimo * 3, 1)));
            },
            textoEstado(e) { return { activo: 'Activo', bajo: 'Stock bajo', sin_stock: 'Sin stock', pausado: 'Pausado' }[e]; },
            badgeEstado(e) { return { activo: 'b-ok', bajo: 'b-a', sin_stock: 'b-r', pausado: 'b-n' }[e]; },
            exportar() {
                if (this.vista === 'categorias') {
                    gh.excel('Categorías', [['Categoría'], ['Productos', 'numero'], ['Estado']],
                        this.categorias.map(c => [c.nombre, c.productos, c.activo ? 'Visible' : 'Oculta']), { totales: false });
                    return;
                }
                const filas = [];
                this.filtrados.forEach(p => {
                    if (p.tiene_variantes) p.variantes.forEach(v => filas.push([p.nombre, v.nombre, v.sku || p.sku, p.categoria, v.precio ?? p.precio, '', v.stock, v.stock_minimo, this.textoEstado(p.estado)]));
                    else filas.push([p.nombre, '', p.sku, p.categoria, p.precio, p.precio_oferta ?? '', p.stock, p.stock_minimo, this.textoEstado(p.estado)]);
                });
                gh.excel('Productos', [['Producto'], ['Variante'], ['SKU'], ['Categoría'], ['Precio', 'moneda'], ['Precio oferta', 'moneda'], ['Stock', 'numero'], ['Stock mínimo', 'numero'], ['Estado']],
                    filas, { totales: false });
            },

            // ---------- Modal de producto ----------
            resetForm() {
                this.form = { nombre: '', categoria_id: '', nueva_categoria: '', precio: '', precio_oferta: '', stock: '', stock_minimo: 5, sku: '', descripcion: '', activo: true, tiene_variantes: false, variantes: [] };
                this.errores = {}; this.errorGeneral = ''; this.nuevaCat = false; this.errGaleria = '';
                this.nuevas.forEach(n => URL.revokeObjectURL(n.url)); this.nuevas = []; this.galeria = [];
            },
            abrir(p = null, pestana = 'general') {
                this.resetForm();
                this.editando = p ? p.id : null;
                this.pestana = pestana;
                if (p) {
                    Object.assign(this.form, {
                        nombre: p.nombre, categoria_id: p.categoria_id ?? '', precio: p.precio, precio_oferta: p.precio_oferta ?? '',
                        stock: p.stock, stock_minimo: p.stock_minimo, sku: p.sku ?? '', descripcion: p.descripcion ?? '', activo: p.activo,
                        tiene_variantes: p.tiene_variantes,
                        variantes: p.variantes.map(v => ({ ...v, precio: v.precio ?? '', sku: v.sku ?? '', _k: ++clave })),
                    });
                    this.galeria = [...p.imagenes];
                }
                this.modal = true;
                if (pestana === 'general') this.$nextTick(() => setTimeout(() => this.$refs.primero?.focus(), 80));
            },
            cerrar() { if (!this.guardando) this.modal = false; },
            agregarVariante() { this.form.variantes.push({ id: null, nombre: '', precio: '', stock: '', stock_minimo: 0, sku: '', activo: true, _k: ++clave }); },
            errEn(tab) {
                const k = Object.keys(this.errores).filter(x => this.errores[x]);
                if (tab === 'variantes') return k.some(x => x.startsWith('variantes'));
                if (tab === 'galeria') return k.some(x => x.startsWith('imagenes'));
                return k.some(x => !x.startsWith('variantes') && !x.startsWith('imagenes'));
            },

            async guardar() {
                this.guardando = true; this.errores = {}; this.errorGeneral = '';
                const fd = new FormData();
                ['nombre', 'categoria_id', 'nueva_categoria', 'sku', 'descripcion'].forEach(k => { if (this.form[k] !== '' && this.form[k] != null) fd.append(k, this.form[k]); });
                fd.append('activo', this.form.activo ? '1' : '0');
                fd.append('tiene_variantes', this.form.tiene_variantes ? '1' : '0');
                if (this.form.tiene_variantes) {
                    this.form.variantes.forEach((v, i) => {
                        if (v.id) fd.append(`variantes[${i}][id]`, v.id);
                        ['nombre', 'precio', 'stock', 'stock_minimo', 'sku'].forEach(k => fd.append(`variantes[${i}][${k}]`, v[k] ?? ''));
                        fd.append(`variantes[${i}][activo]`, v.activo ? '1' : '0');
                    });
                } else {
                    ['precio', 'precio_oferta', 'stock', 'stock_minimo'].forEach(k => { if (this.form[k] !== '' && this.form[k] != null) fd.append(k, this.form[k]); });
                }
                this.nuevas.forEach(n => fd.append('imagenes[]', n.archivo));

                const r = await gh.api(this.editando ? URL_BASE + '/' + this.editando : '{{ route('admin.productos.guardar') }}', { method: 'POST', body: fd });
                this.guardando = false;

                if (r.status === 422) {
                    this.errores = r.data.errors || {};
                    if (!this.errEn(this.pestana)) this.pestana = ['general', 'variantes', 'galeria'].find(t => this.errEn(t)) || this.pestana;
                    if (!r.data.errors) this.errorGeneral = r.data.message;
                    return;
                }
                if (!r.ok) { this.errorGeneral = r.data.message || 'No se pudo guardar. Intenta de nuevo.'; return; }

                const nuevo = !this.editando;
                this.modal = false;
                this.nuevas.forEach(n => URL.revokeObjectURL(n.url)); this.nuevas = [];
                Alpine.store('toasts').push(r.data.message || 'Producto guardado');
                if (r.data.categorias) this.categorias = r.data.categorias;
                this.ponerProducto(r.data.data);
                if (nuevo) { this.filtro = 'todos'; this.categoria = ''; this.buscar = ''; this.orden = { campo: null, dir: 1 }; this.pagina = 1; }
                this.avisarStock(r.data.data);
            },
            ponerProducto(d) {
                const i = this.productos.findIndex(x => x.id === d.id);
                if (i >= 0) this.productos.splice(i, 1, d); else this.productos.unshift(d);
                this.resaltado = d.id; setTimeout(() => this.resaltado = null, 2400);
            },
            avisarStock(p) {
                if (p.activo && ['bajo', 'sin_stock'].includes(p.estado)) {
                    Alpine.store('toasts').push(p.estado === 'sin_stock' ? `«${p.nombre}» está sin stock` : `«${p.nombre}» está en su stock mínimo`, 'error');
                }
            },
            async alternar(p) {
                const r = await gh.api(URL_BASE + '/' + p.id + '/estado', { method: 'PATCH' });
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo cambiar el estado', 'error'); return; }
                await this.recargar();
                Alpine.store('toasts').push(r.data.message, 'info');
            },
            async recargar() {
                this.cargando = true;
                const r = await gh.api('{{ route('admin.productos.lista') }}');
                this.cargando = false;
                if (r.ok) { this.productos = r.data.data; this.categorias = r.data.categorias; }
            },

            // ---------- Galeria (GEST-19) ----------
            agregarImagenes(lista) {
                this.errGaleria = '';
                const archivos = [...(lista || [])];
                for (const f of archivos) {
                    const tipos = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!tipos.includes(f.type)) { this.errGaleria = `«${f.name}» no es JPG, PNG o WEBP.`; continue; }
                    if (f.size > 4 * 1024 * 1024) { this.errGaleria = `«${f.name}» pesa más de 4 MB.`; continue; }
                    if (this.galeria.length + this.nuevas.length >= 8) { this.errGaleria = 'Cada producto admite hasta 8 imágenes.'; break; }
                    this.nuevas.push({ k: ++clave, archivo: f, url: URL.createObjectURL(f) });
                }
                // Si el producto ya existe, las fotos se suben de inmediato
                if (this.editando && this.nuevas.length) this.subirNuevas();
            },
            async subirNuevas() {
                const fd = new FormData();
                this.nuevas.forEach(n => fd.append('imagenes[]', n.archivo));
                this.subiendo = true;
                const r = await gh.api(URL_BASE + '/' + this.editando + '/imagenes', { method: 'POST', body: fd });
                this.subiendo = false;
                if (!r.ok) { this.errGaleria = r.data.errors ? Object.values(r.data.errors)[0][0] : (r.data.message || 'No se pudieron subir las imágenes'); return; }
                this.nuevas.forEach(n => URL.revokeObjectURL(n.url)); this.nuevas = [];
                this.actualizarGaleria(r.data);
            },
            actualizarGaleria(data) {
                this.galeria = [...data.data.imagenes];
                this.ponerProducto(data.data);
                Alpine.store('toasts').push(data.message);
            },
            async principalImg(img) {
                const r = await gh.api(URL_BASE + '/' + this.editando + '/imagenes/' + img.id + '/principal', { method: 'PATCH' });
                if (r.ok) this.actualizarGaleria(r.data); else Alpine.store('toasts').push(r.data.message || 'No se pudo actualizar', 'error');
            },
            async borrarImg(img) {
                const r = await gh.api(URL_BASE + '/' + this.editando + '/imagenes/' + img.id, { method: 'DELETE' });
                if (r.ok) this.actualizarGaleria(r.data); else Alpine.store('toasts').push(r.data.message || 'No se pudo eliminar', 'error');
            },
            async soltarImg(destino) {
                const origen = this.arrastroImg; this.arrastroImg = null;
                if (origen === null || origen === destino) return;
                const [movida] = this.galeria.splice(origen, 1);
                this.galeria.splice(destino, 0, movida);
                const r = await gh.api(URL_BASE + '/' + this.editando + '/imagenes/orden', { method: 'PUT', body: { orden: this.galeria.map(g => g.id) } });
                if (r.ok) this.actualizarGaleria(r.data);
            },

            // ---------- Categorias (GEST-18) ----------
            abrirCat(c = null) {
                this.cat = c ? { id: c.id, nombre: c.nombre, activo: c.activo } : { id: null, nombre: '', activo: true };
                this.erroresCat = {};
                this.modalCat = true;
                this.$nextTick(() => setTimeout(() => this.$refs.catNombre?.focus(), 80));
            },
            async guardarCat() {
                this.guardando = true; this.erroresCat = {};
                const r = await gh.api(this.cat.id ? URL_CAT + '/' + this.cat.id : URL_CAT, { method: 'POST', body: { nombre: this.cat.nombre, activo: this.cat.activo } });
                this.guardando = false;
                if (r.status === 422) { this.erroresCat = r.data.errors || {}; return; }
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar', 'error'); return; }
                this.categorias = r.data.categorias;
                this.modalCat = false;
                this.resaltadoCat = r.data.data.id; setTimeout(() => this.resaltadoCat = null, 2400);
                Alpine.store('toasts').push(r.data.message);
                if (this.cat.id) this.recargar();
            },

            // ---------- Reponer (GEST-20) ----------
            reponer(p) {
                const baja = p.tiene_variantes ? (p.variantes.find(v => v.activo && v.stock <= v.stock_minimo) || {}).id : '';
                this.repo = { abierto: true, producto: p, variante_id: baja ? String(baja) : '', cantidad: '', errores: {} };
                this.$nextTick(() => setTimeout(() => this.$refs.repoCant?.focus(), 80));
            },
            async guardarReposicion() {
                this.guardando = true;
                const body = { cantidad: this.repo.cantidad };
                if (this.repo.variante_id) body.variante_id = this.repo.variante_id;
                const r = await gh.api(URL_BASE + '/' + this.repo.producto.id + '/stock', { method: 'PATCH', body });
                this.guardando = false;
                if (r.status === 422) { this.repo.errores = r.data.errors || {}; return; }
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo reponer', 'error'); return; }
                this.repo.abierto = false;
                this.ponerProducto(r.data.data);
                Alpine.store('toasts').push(r.data.message);
            },

            // ---------- Eliminar ----------
            async eliminar() {
                const { tipo, item } = this.confirmar || {};
                if (!item) return;
                this.guardando = true;
                const r = await gh.api((tipo === 'categoria' ? URL_CAT : URL_BASE) + '/' + item.id, { method: 'DELETE' });
                this.guardando = false;
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo eliminar', 'error'); return; }
                this.confirmar = null;
                if (tipo === 'categoria') { this.categorias = r.data.categorias; if (String(this.categoria) === String(item.id)) this.categoria = ''; await this.recargar(); }
                else { this.productos = this.productos.filter(p => p.id !== item.id); this.modal = false; }
                Alpine.store('toasts').push(r.data.message, 'info');
            },
        }));
    });
</script>
