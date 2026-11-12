{{-- Modales de Productos: producto (general, variantes, galeria), categoria, reponer y confirmar --}}
<div class="modal-bg" x-show="modal" x-cloak
     x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
     x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
     @keydown.escape.window="cerrar()" @mousedown.self="cerrar()">
    <form class="modal" style="max-width:720px;" @submit.prevent="guardar()" novalidate>
        <div class="modal-hd">
            <div class="modal-ico"><x-icon name="package" size="18" /></div>
            <div>
                <div class="modal-ttl" x-text="editando ? 'Editar producto' : 'Nuevo producto'"></div>
                <div class="modal-sub" x-text="editando ? 'Los cambios se ven al instante en tu tienda' : 'Aparecerá en tu catálogo y tienda virtual'"></div>
            </div>
            <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
        </div>

        <div class="tabs" style="padding:0 20px;">
            <button type="button" class="tab" :class="{ on: pestana === 'general' }" @click="pestana = 'general'">General <span class="tab-err" x-show="errEn('general')"></span></button>
            <button type="button" class="tab" :class="{ on: pestana === 'variantes' }" @click="pestana = 'variantes'">Variantes <span class="n" x-show="form.tiene_variantes" x-text="form.variantes.length"></span><span class="tab-err" x-show="errEn('variantes')"></span></button>
            <button type="button" class="tab" :class="{ on: pestana === 'galeria' }" @click="pestana = 'galeria'">Galería <span class="n" x-text="galeria.length + nuevas.length"></span><span class="tab-err" x-show="errEn('galeria')"></span></button>
        </div>

        <div class="modal-bd" style="min-height:330px;">
            {{-- General --}}
            <div x-show="pestana === 'general'">
                <div class="grid2">
                    <div class="field">
                        <label class="lbl">Nombre <span class="req">*</span></label>
                        <input class="inp" :class="{ err: errores.nombre }" x-model="form.nombre" x-ref="primero" maxlength="150" placeholder="Ej: Pollo a la brasa">
                        <div class="ferr" x-show="errores.nombre" x-text="errores.nombre?.[0]"></div>
                    </div>
                    <div class="field">
                        <label class="lbl">Categoría</label>
                        <div style="display:flex;gap:6px;">
                            <select class="inp" x-model="form.categoria_id" x-show="!nuevaCat">
                                <option value="">Sin categoría</option>
                                <template x-for="cat in categorias" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.nombre" :selected="String(cat.id) === String(form.categoria_id)"></option>
                                </template>
                            </select>
                            <input class="inp" x-show="nuevaCat" x-model="form.nueva_categoria" maxlength="80" placeholder="Nueva categoría">
                            <button type="button" class="btn btn-g" @click="nuevaCat = !nuevaCat; form.nueva_categoria = ''" x-text="nuevaCat ? 'Elegir' : '+ Nueva'"></button>
                        </div>
                        <div class="ferr" x-show="errores.categoria_id" x-text="errores.categoria_id?.[0]"></div>
                    </div>
                </div>

                <div class="form-sec" style="margin-top:4px;">Precio e inventario</div>
                <div class="nota" x-show="form.tiene_variantes" style="margin-bottom:12px;">
                    <x-icon name="layers" size="16" />
                    <div>Este producto tiene variantes: el precio y el stock se manejan en la pestaña <a href="#" class="link" @click.prevent="pestana = 'variantes'">Variantes</a>. El stock total es la suma de todas.</div>
                </div>
                <div class="grid2 g4" x-show="!form.tiene_variantes">
                    <div class="field">
                        <label class="lbl">Precio (S/) <span class="req">*</span></label>
                        <input class="inp" :class="{ err: errores.precio }" x-model="form.precio" type="number" min="0" step="0.10" placeholder="0.00">
                        <div class="ferr" x-show="errores.precio" x-text="errores.precio?.[0]"></div>
                    </div>
                    <div class="field">
                        <label class="lbl">Precio oferta</label>
                        <input class="inp" :class="{ err: errores.precio_oferta }" x-model="form.precio_oferta" type="number" min="0" step="0.10" placeholder="Opcional">
                        <div class="ferr" x-show="errores.precio_oferta" x-text="errores.precio_oferta?.[0]"></div>
                    </div>
                    <div class="field">
                        <label class="lbl">Stock <span class="req">*</span></label>
                        <input class="inp" :class="{ err: errores.stock }" x-model="form.stock" type="number" min="0" step="1" placeholder="0">
                        <div class="ferr" x-show="errores.stock" x-text="errores.stock?.[0]"></div>
                    </div>
                    <div class="field">
                        <label class="lbl">Stock mínimo</label>
                        <input class="inp" :class="{ err: errores.stock_minimo }" x-model="form.stock_minimo" type="number" min="0" step="1" placeholder="5">
                        <div class="hint">Te avisamos al llegar aquí</div>
                    </div>
                </div>
                <div class="grid2">
                    <div class="field">
                        <label class="lbl">Código / SKU</label>
                        <input class="inp" x-model="form.sku" maxlength="60" placeholder="Opcional">
                    </div>
                    <div class="field">
                        <label class="lbl">Estado</label>
                        <label class="switch">
                            <input type="checkbox" x-model="form.activo">
                            <span class="track"><span class="thumb"></span></span>
                            <span x-text="form.activo ? 'Publicado' : 'Pausado'" style="font-size:13px;"></span>
                        </label>
                    </div>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label class="lbl">Descripción</label>
                    <textarea class="inp" x-model="form.descripcion" rows="2" maxlength="2000" placeholder="Ingredientes, tamaño, presentación…"></textarea>
                </div>
            </div>

            {{-- Variantes (GEST-18) --}}
            <div x-show="pestana === 'variantes'" x-cloak>
                <label class="switch" style="padding:0 0 12px;">
                    <input type="checkbox" x-model="form.tiene_variantes" @change="if (form.tiene_variantes && !form.variantes.length) agregarVariante()">
                    <span class="track"><span class="thumb"></span></span>
                    <span style="font-size:13px;font-weight:600;">Este producto tiene variantes</span>
                    <span style="font-size:12px;color:var(--fg3);">(tamaño, presentación, sabor, talla…)</span>
                </label>
                <div class="ferr" x-show="errores.variantes" x-text="errores.variantes?.[0]" style="margin:-6px 0 10px;"></div>

                <div x-show="form.tiene_variantes">
                    <div class="var-grid var-hd">
                        <span>Nombre</span><span>Precio (S/)</span><span>Stock</span><span>Mínimo</span><span>SKU</span><span></span>
                    </div>
                    <template x-for="(v, i) in form.variantes" :key="v._k">
                        <div class="var-grid var-row" :class="{ 'var-off': !v.activo }">
                            <div>
                                <input class="inp" :class="{ err: errores['variantes.' + i + '.nombre'] }" x-model="v.nombre" maxlength="120" placeholder="Ej: 1/2 pollo">
                                <div class="ferr" x-show="errores['variantes.' + i + '.nombre']" x-text="errores['variantes.' + i + '.nombre']?.[0]"></div>
                            </div>
                            <input class="inp" :class="{ err: errores['variantes.' + i + '.precio'] }" x-model="v.precio" type="number" min="0" step="0.10" placeholder="0.00">
                            <div>
                                <input class="inp" :class="{ err: errores['variantes.' + i + '.stock'] }" x-model="v.stock" type="number" min="0" step="1" placeholder="0">
                                <div class="var-alert" x-show="v.stock !== '' && Number(v.stock) <= Number(v.stock_minimo || 0)">Bajo el mínimo</div>
                            </div>
                            <input class="inp" x-model="v.stock_minimo" type="number" min="0" step="1" placeholder="0">
                            <input class="inp" x-model="v.sku" maxlength="60" placeholder="Opcional">
                            <div style="display:flex;gap:4px;">
                                <button type="button" class="icon-btn sm" @click="v.activo = !v.activo" :title="v.activo ? 'Desactivar' : 'Activar'">
                                    <x-icon name="eye" size="14" x-show="v.activo" /><x-icon name="pause" size="14" x-show="!v.activo" />
                                </button>
                                <button type="button" class="icon-btn sm del" @click="form.variantes.splice(i, 1)" title="Quitar"><x-icon name="trash" size="14" /></button>
                            </div>
                        </div>
                    </template>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;flex-wrap:wrap;gap:8px;">
                        <button type="button" class="btn btn-g btn-sm" @click="agregarVariante()"><x-icon name="plus" size="14" /> Agregar variante</button>
                        <span style="font-size:12px;color:var(--fg2);">Stock total: <b x-text="form.variantes.filter(v => v.activo).reduce((t, v) => t + Number(v.stock || 0), 0) + ' und.'"></b></span>
                    </div>
                </div>
                <div class="empty" x-show="!form.tiene_variantes" style="padding:30px 10px;">
                    <x-icon name="layers" size="30" />
                    <div style="font-weight:600;color:var(--fg2);">Producto simple</div>
                    <div>Activa las variantes si vendes este producto en varias presentaciones, cada una con su precio y stock.</div>
                </div>
            </div>

            {{-- Galeria (GEST-19) --}}
            <div x-show="pestana === 'galeria'" x-cloak>
                <label class="drop" :class="{ 'drop-on': arrastrando }"
                       @dragover.prevent="arrastrando = true" @dragleave.prevent="arrastrando = false"
                       @drop.prevent="arrastrando = false; agregarImagenes($event.dataTransfer.files)">
                    <x-icon name="upload" size="22" />
                    <div><b style="color:var(--fg);">Arrastra tus fotos aquí</b> o haz clic para elegir</div>
                    <div style="font-size:11px;">JPG, PNG o WEBP · máximo 4 MB cada una · hasta 8 por producto</div>
                    <input type="file" accept="image/png,image/jpeg,image/webp" multiple hidden @change="agregarImagenes($event.target.files); $event.target.value = ''">
                </label>
                <div class="ferr" x-show="errGaleria" x-text="errGaleria" style="margin-top:8px;"></div>
                <div class="loadbar" x-show="subiendo" x-cloak style="margin-top:8px;"></div>

                <div class="gal" x-show="galeria.length || nuevas.length">
                    <template x-for="(img, i) in galeria" :key="img.id">
                        <div class="gal-item" :class="{ principal: img.principal }" draggable="true"
                             @dragstart="arrastroImg = i" @dragover.prevent @drop.prevent="soltarImg(i)">
                            <div class="gal-img" :style="'background-image:url(' + img.url + ')'"></div>
                            <span class="gal-badge" x-show="img.principal">Principal</span>
                            <div class="gal-acc">
                                <button type="button" x-show="!img.principal" @click="principalImg(img)" title="Usar como principal"><x-icon name="check-circle" size="14" /></button>
                                <button type="button" @click="borrarImg(img)" title="Eliminar"><x-icon name="trash" size="14" /></button>
                            </div>
                        </div>
                    </template>
                    <template x-for="(n, i) in nuevas" :key="n.k">
                        <div class="gal-item nueva">
                            <div class="gal-img" :style="'background-image:url(' + n.url + ')'"></div>
                            <span class="gal-badge nueva">Por subir</span>
                            <div class="gal-acc"><button type="button" @click="nuevas.splice(i, 1)" title="Quitar"><x-icon name="x" size="14" /></button></div>
                        </div>
                    </template>
                </div>
                <div class="hint" x-show="galeria.length > 1" style="margin-top:8px;">Arrastra las fotos para cambiar el orden. La principal es la que se ve en el catálogo.</div>
                <div class="hint" x-show="nuevas.length" style="margin-top:8px;">Las fotos marcadas «Por subir» se guardan al presionar el botón de abajo.</div>
            </div>

            <div class="ferr" x-show="errorGeneral" x-text="errorGeneral" style="margin-top:10px;"></div>
        </div>

        <div class="modal-ft">
            <button type="button" class="btn btn-g del-btn" x-show="editando" @click="confirmar = { tipo: 'producto', item: productos.find(p => p.id === editando) }" style="margin-right:auto;"><x-icon name="trash" size="14" /> Eliminar</button>
            <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
            <button type="submit" class="btn btn-p" :disabled="guardando">
                <x-icon name="loader" size="15" class="spin" x-show="guardando" />
                <x-icon name="check" size="15" stroke="2.4" x-show="!guardando" />
                <span x-text="guardando ? 'Guardando…' : (editando ? 'Guardar cambios' : 'Crear producto')"></span>
            </button>
        </div>
    </form>
</div>

{{-- Categoria --}}
<div class="modal-bg" x-show="modalCat" x-cloak
     x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
     x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
     @keydown.escape.window="modalCat = false" @mousedown.self="modalCat = false">
    <form class="modal" style="max-width:420px;" @submit.prevent="guardarCat()" novalidate>
        <div class="modal-hd">
            <div class="modal-ico" style="background:var(--acl);color:var(--accent);"><x-icon name="layers" size="18" /></div>
            <div><div class="modal-ttl" x-text="cat.id ? 'Editar categoría' : 'Nueva categoría'"></div><div class="modal-sub">Agrupa tus productos en el catálogo</div></div>
            <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modalCat = false" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
        </div>
        <div class="modal-bd">
            <div class="field">
                <label class="lbl">Nombre <span class="req">*</span></label>
                <input class="inp" :class="{ err: erroresCat.nombre }" x-model="cat.nombre" x-ref="catNombre" maxlength="80" placeholder="Ej: Bebidas">
                <div class="ferr" x-show="erroresCat.nombre" x-text="erroresCat.nombre?.[0]"></div>
            </div>
            <label class="switch" style="padding:0;">
                <input type="checkbox" x-model="cat.activo">
                <span class="track"><span class="thumb"></span></span>
                <span style="font-size:13px;" x-text="cat.activo ? 'Visible en la tienda' : 'Oculta en la tienda'"></span>
            </label>
        </div>
        <div class="modal-ft">
            <button type="button" class="btn btn-g" @click="modalCat = false">Cancelar</button>
            <button type="submit" class="btn btn-p" :disabled="guardando"><x-icon name="check" size="15" stroke="2.4" /> <span x-text="cat.id ? 'Guardar' : 'Crear categoría'"></span></button>
        </div>
    </form>
</div>

{{-- Reponer stock (GEST-20) --}}
<div class="modal-bg" x-show="repo.abierto" x-cloak
     x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
     x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
     @keydown.escape.window="repo.abierto = false" @mousedown.self="repo.abierto = false">
    <form class="modal" style="max-width:420px;" @submit.prevent="guardarReposicion()" novalidate>
        <div class="modal-hd">
            <div class="modal-ico" style="background:var(--greenl);color:var(--green);"><x-icon name="package" size="18" /></div>
            <div><div class="modal-ttl">Reponer stock</div><div class="modal-sub" x-text="repo.producto?.nombre"></div></div>
            <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="repo.abierto = false" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
        </div>
        <div class="modal-bd">
            <div class="field" x-show="repo.producto?.tiene_variantes">
                <label class="lbl">Variante <span class="req">*</span></label>
                <select class="inp" :class="{ err: repo.errores.variante_id }" x-model="repo.variante_id">
                    <option value="">Elige una variante</option>
                    <template x-for="v in (repo.producto?.variantes || [])" :key="v.id">
                        <option :value="v.id" :selected="String(v.id) === String(repo.variante_id)" x-text="v.nombre + ' · ' + v.stock + ' und. (mín. ' + v.stock_minimo + ')'"></option>
                    </template>
                </select>
                <div class="ferr" x-show="repo.errores.variante_id" x-text="repo.errores.variante_id?.[0]"></div>
            </div>
            <div class="repo-actual" x-show="!repo.producto?.tiene_variantes">
                <span>Stock actual</span><b x-text="(repo.producto?.stock ?? 0) + ' und.'"></b>
                <span>Mínimo</span><b x-text="(repo.producto?.stock_minimo ?? 0) + ' und.'"></b>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label class="lbl">Cantidad que ingresa <span class="req">*</span></label>
                <input class="inp" :class="{ err: repo.errores.cantidad }" x-model="repo.cantidad" x-ref="repoCant" type="number" min="1" step="1" placeholder="Ej: 20">
                <div class="ferr" x-show="repo.errores.cantidad" x-text="repo.errores.cantidad?.[0]"></div>
            </div>
        </div>
        <div class="modal-ft">
            <button type="button" class="btn btn-g" @click="repo.abierto = false">Cancelar</button>
            <button type="submit" class="btn btn-p" :disabled="guardando"><x-icon name="plus" size="15" stroke="2.4" /> Sumar al stock</button>
        </div>
    </form>
</div>

{{-- Confirmar eliminacion --}}
<div class="modal-bg" x-show="confirmar" x-cloak style="z-index:70;"
     x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
     x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
     @keydown.escape.window="confirmar = null" @mousedown.self="confirmar = null">
    <div class="modal" style="max-width:400px;">
        <div class="modal-bd" style="text-align:center;padding:26px 24px 18px;">
            <div class="conf-ico"><x-icon name="trash" size="20" /></div>
            <div class="modal-ttl" style="margin-bottom:6px;" x-text="confirmar?.tipo === 'categoria' ? '¿Eliminar la categoría?' : '¿Eliminar el producto?'"></div>
            <div style="font-size:13px;color:var(--fg2);">
                <b x-text="confirmar?.item?.nombre"></b>
                <span x-text="confirmar?.tipo === 'categoria' ? ' se eliminará. Sus productos no se borran: quedarán sin categoría.' : ' se eliminará junto con sus variantes e imágenes. Los pedidos anteriores no se modifican.'"></span>
            </div>
        </div>
        <div class="modal-ft" style="justify-content:center;">
            <button type="button" class="btn btn-g" @click="confirmar = null">Cancelar</button>
            <button type="button" class="btn btn-danger" :disabled="guardando" @click="eliminar()">Sí, eliminar</button>
        </div>
    </div>
</div>

<style>
    .g4{grid-template-columns:repeat(4,1fr);}
    .tab-err{display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--red);margin-left:5px;vertical-align:2px;}
    .switch{display:flex;align-items:center;gap:10px;cursor:pointer;padding-top:6px;flex-wrap:wrap;}
    .switch input{display:none;}
    .track{width:38px;height:22px;border-radius:99px;background:var(--border);position:relative;transition:background .2s;flex-shrink:0;}
    .thumb{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:transform .25s var(--ease);}
    .switch input:checked + .track{background:var(--green);}
    .switch input:checked + .track .thumb{transform:translateX(16px);}
    .var-grid{display:grid;grid-template-columns:minmax(0,2fr) 1fr 1fr .8fr 1fr 66px;gap:8px;align-items:start;}
    .var-hd{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--fg3);padding:0 2px 6px;}
    .var-row{padding:6px 0;border-top:1px solid var(--border);animation:fadeUp .25s var(--ease) backwards;}
    .var-off .inp{opacity:.5;}
    .var-alert{font-size:10px;font-weight:600;color:var(--amber);margin-top:3px;}
    .drop{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:22px;border:1.5px dashed var(--border);border-radius:10px;background:var(--surface3);cursor:pointer;color:var(--fg3);font-size:12px;text-align:center;transition:border-color .15s,background .15s;}
    .drop:hover,.drop-on{border-color:var(--accent);background:var(--acl);}
    .gal{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:14px;}
    .gal-item{position:relative;border-radius:10px;overflow:hidden;border:1px solid var(--border);aspect-ratio:1;background:var(--surface2);cursor:grab;animation:fadeUp .3s var(--ease) backwards;transition:box-shadow .2s,transform .2s var(--ease);}
    .gal-item:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(26,35,50,.1);}
    .gal-item.principal{border:2px solid var(--accent);}
    .gal-item.nueva{border-style:dashed;cursor:default;}
    .gal-img{position:absolute;inset:0;background:center/cover no-repeat;}
    .gal-badge{position:absolute;left:6px;top:6px;font-size:10px;font-weight:700;background:var(--accent);color:#fff;padding:2px 7px;border-radius:99px;}
    .gal-badge.nueva{background:var(--fg);}
    .gal-acc{position:absolute;right:6px;bottom:6px;display:flex;gap:4px;opacity:0;transition:opacity .15s;}
    .gal-item:hover .gal-acc,.gal-item.nueva .gal-acc{opacity:1;}
    .gal-acc button{width:28px;height:28px;border-radius:7px;border:none;background:rgba(255,255,255,.95);color:var(--fg2);display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,.15);}
    .gal-acc button:hover{color:var(--accent);}
    .repo-actual{display:grid;grid-template-columns:auto 1fr auto 1fr;gap:6px 10px;align-items:center;font-size:12px;color:var(--fg3);background:var(--surface3);border:1px solid var(--border);border-radius:8px;padding:10px 12px;margin-bottom:12px;}
    .repo-actual b{color:var(--fg);font-size:13px;}
    .conf-ico{width:46px;height:46px;border-radius:50%;background:var(--redl);color:var(--red);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;}
    .btn-danger{background:var(--red);color:#fff;border-color:var(--red);}
    .btn-danger:hover{filter:brightness(.93);}
    .del-btn:hover{color:var(--red);border-color:#FECACA;background:var(--redl);}
    @media (hover:none){ .gal-acc{opacity:1;} }
    @media (max-width:860px){
        .g4{grid-template-columns:1fr 1fr;}
        .gal{grid-template-columns:repeat(3,1fr);}
        .var-grid{grid-template-columns:1fr 1fr;}
        .var-hd{display:none;}
    }
</style>
