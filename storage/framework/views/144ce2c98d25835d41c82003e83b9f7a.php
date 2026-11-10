


<div class="modal-bg" x-show="form.abierto" x-cloak
     x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
     x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
     @keydown.escape.window="if (!confirmar) form.abierto = false" @mousedown.self="form.abierto = false">
    <form class="modal" style="max-width:860px;" @submit.prevent="guardar()" novalidate>
        <div class="modal-hd">
            <div class="modal-ico"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard','size' => '18']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard','size' => '18']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></div>
            <div>
                <div class="modal-ttl" x-text="form.id ? 'Editar pedido ' + form.numero : 'Nuevo pedido'"></div>
                <div class="modal-sub">El stock se descuenta al guardar y se devuelve si el pedido se cancela</div>
            </div>
            <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="form.abierto = false" aria-label="Cerrar"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'x','size' => '16']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '16']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></button>
        </div>

        <div class="modal-bd">
            <div class="pf-grid">
                
                <div>
                    <div class="form-sec" style="margin-top:0;">Cliente</div>
                    <div class="field" x-show="!form.cliente_nuevo">
                        <div style="display:flex;gap:6px;">
                            <select class="inp" :class="{ err: errores.cliente_id }" x-model="form.cliente_id" @change="alElegirCliente()">
                                <option value="">Elige un cliente¦</option>
                                <template x-for="c in clientes" :key="c.id">
                                    <option :value="c.id" x-text="c.nombre + (c.telefono ? ' · ' + c.telefono : '')" :selected="String(c.id) === String(form.cliente_id)"></option>
                                </template>
                            </select>
                            <button type="button" class="btn btn-g" @click="form.cliente_nuevo = true; form.cliente_id = ''">+ Nuevo</button>
                        </div>
                        <div class="ferr" x-show="errores.cliente_id" x-text="errores.cliente_id?.[0]"></div>
                    </div>
                    <div x-show="form.cliente_nuevo" class="grid2">
                        <div class="field">
                            <input class="inp" :class="{ err: errores.cliente_nombre }" x-model="form.cliente_nombre" maxlength="120" placeholder="Nombre del cliente">
                            <div class="ferr" x-show="errores.cliente_nombre" x-text="errores.cliente_nombre?.[0]"></div>
                        </div>
                        <div class="field">
                            <div style="display:flex;gap:6px;">
                                <input class="inp" :class="{ err: errores.cliente_telefono }" x-model="form.cliente_telefono" maxlength="20" placeholder="Teléfono (opcional)">
                                <button type="button" class="btn btn-g" @click="form.cliente_nuevo = false">Elegir</button>
                            </div>
                            <div class="ferr" x-show="errores.cliente_telefono" x-text="errores.cliente_telefono?.[0]"></div>
                        </div>
                    </div>

                    <div class="form-sec">Productos</div>
                    <div class="pf-buscar" @click.outside="buscador.abierto = false">
                        <div class="search" style="width:100%;">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'search','size' => '15']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','size' => '15']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                            <input class="inp" x-ref="buscarProd" x-model="buscador.q" @focus="buscador.abierto = true" @input="buscador.abierto = true"
                                   @keydown.enter.prevent="sugerencias[0] && agregar(sugerencias[0])" placeholder="Busca un producto para agregarlo¦">
                        </div>
                        <div class="pf-sug" x-show="buscador.abierto && sugerencias.length" x-cloak>
                            <template x-for="p in sugerencias" :key="p.id">
                                <button type="button" class="pf-sug-i" @click="agregar(p)" :disabled="stockDe(p) <= 0">
                                    <span class="pf-th" :style="p.imagen_url ? 'background-image:url(' + p.imagen_url + ')' : ''"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'package','size' => '14','xShow' => '!p.imagen_url']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'package','size' => '14','x-show' => '!p.imagen_url']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></span>
                                    <span style="flex:1;min-width:0;">
                                        <span style="font-weight:600;" x-text="p.nombre"></span>
                                        <span class="sub" x-text="(p.categoria || 'Sin categoría') + (p.variantes.length ? ' Â· ' + p.variantes.length + ' variantes' : '')"></span>
                                    </span>
                                    <span style="text-align:right;">
                                        <b x-text="(p.variantes.length > 1 ? 'desde ' : '') + gh.soles(precioDesde(p))"></b>
                                        <span class="sub" :style="stockDe(p) <= p.stock_minimo ? 'color:var(--amber)' : ''" x-text="stockDe(p) > 0 ? stockDe(p) + ' en stock' : 'Sin stock'"></span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="ferr" x-show="errores.items" x-text="errores.items?.[0]" style="margin-top:6px;"></div>

                    <div class="pf-lineas">
                        <template x-for="(l, i) in form.items" :key="l.uid">
                            <div class="pf-linea">
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:600;font-size:13px;" x-text="prod(l).nombre || 'Producto eliminado'"></div>
                                    <template x-if="prod(l).variantes?.length">
                                        <select class="inp inp-sm" :class="{ err: errores['items.' + i + '.variante_id'] }" x-model="l.variante_id">
                                            <option value="">Elige la variante¦</option>
                                            <template x-for="v in prod(l).variantes" :key="v.id">
                                                <option :value="v.id" :selected="String(v.id) === String(l.variante_id)" x-text="v.nombre + ' Â· ' + gh.soles(v.precio) + ' Â· ' + disponible(l, v) + ' disp.'"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <div class="sub" x-show="!prod(l).variantes?.length" x-text="gh.soles(precio(l)) + ' c/u Â· ' + disponible(l) + ' disponibles'"></div>
                                    <div class="ferr" x-text="errores['items.' + i + '.variante_id']?.[0] || errores['items.' + i + '.cantidad']?.[0] || errores['items.' + i + '.producto_id']?.[0]"></div>
                                    <div class="ferr" x-show="!errores['items.' + i + '.cantidad'] && l.cantidad > disponible(l)" x-text="'Solo hay ' + disponible(l) + ' disponibles'"></div>
                                </div>
                                <div class="qty">
                                    <button type="button" @click="l.cantidad = Math.max(1, l.cantidad - 1); delete errores['items.' + i + '.cantidad']" aria-label="Menos">âˆ’</button>
                                    <input type="number" min="1" max="999" x-model.number="l.cantidad" @input="delete errores['items.' + i + '.cantidad']">
                                    <button type="button" @click="l.cantidad++" aria-label="Más">+</button>
                                </div>
                                <div class="pf-sub" x-text="gh.soles(precio(l) * (l.cantidad || 0))"></div>
                                <button type="button" class="icon-btn sm del-btn" @click="form.items.splice(i, 1)" title="Quitar"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'trash','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash','size' => '14']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></button>
                            </div>
                        </template>
                        <div class="pf-vacio" x-show="!form.items.length">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'package','size' => '22']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'package','size' => '22']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                            <span>Busca y agrega los productos del pedido</span>
                        </div>
                    </div>
                </div>

                
                <div class="pf-der">
                    <div class="form-sec" style="margin-top:0;">Entrega</div>
                    <div class="pf-tipos">
                        <template x-for="t in [['delivery', 'Delivery', 'truck'], ['recojo', 'Recojo', 'home'], ['mesa', 'En mesa', 'grid']]" :key="t[0]">
                            <button type="button" :class="{ on: form.tipo === t[0] }" @click="form.tipo = t[0]" x-text="t[1]"></button>
                        </template>
                    </div>
                    <div class="field" x-show="form.tipo === 'delivery'">
                        <label class="lbl">Dirección <span class="req">*</span></label>
                        <input class="inp" :class="{ err: errores.direccion_entrega }" x-model="form.direccion_entrega" maxlength="255" placeholder="Calle, número, distrito">
                        <div class="ferr" x-show="errores.direccion_entrega" x-text="errores.direccion_entrega?.[0]"></div>
                    </div>
                    <div class="grid2">
                        <div class="field" x-show="form.tipo === 'delivery'">
                            <label class="lbl">Envío (S/)</label>
                            <input class="inp" type="number" min="0" step="0.5" x-model="form.costo_envio" placeholder="0.00">
                        </div>
                        <div class="field">
                            <label class="lbl">Descuento (S/)</label>
                            <input class="inp" type="number" min="0" step="0.5" x-model="form.descuento" placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-sec">Pago</div>
                    <div class="grid2">
                        <div class="field">
                            <select class="inp" x-model="form.metodo_pago">
                                <template x-for="(t, k) in pagos" :key="k"><option :value="k" x-text="t" :selected="k === form.metodo_pago"></option></template>
                            </select>
                        </div>
                        <label class="switch" style="padding-top:8px;">
                            <input type="checkbox" x-model="form.pagado">
                            <span class="track"><span class="thumb"></span></span>
                            <span style="font-size:13px;" x-text="form.pagado ? 'Ya pagó' : 'Por cobrar'"></span>
                        </label>
                    </div>
                    <div class="field">
                        <label class="lbl">Notas</label>
                        <textarea class="inp" rows="2" x-model="form.notas" maxlength="1000" placeholder="Sin cebolla, tocar el timbre¦"></textarea>
                    </div>

                    <div class="pf-res">
                        <div><span>Subtotal</span><span x-text="gh.soles(resumen.subtotal)"></span></div>
                        <div x-show="resumen.descuento > 0"><span>Descuento</span><span x-text="'âˆ’ ' + gh.soles(resumen.descuento)"></span></div>
                        <div x-show="form.tipo === 'delivery'"><span>Envío</span><span x-text="gh.soles(resumen.envio)"></span></div>
                        <div class="pf-tot"><span>Total</span><span x-text="gh.soles(resumen.total)"></span></div>
                        <div class="sub" style="text-align:right;" x-text="'Incluye IGV ' + gh.soles(resumen.total - resumen.total / 1.18)"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-ft">
            <span class="sub" style="margin-right:auto;" x-text="form.items.reduce((s, l) => s + Number(l.cantidad || 0), 0) + ' unidades'"></span>
            <button type="button" class="btn btn-g" @click="form.abierto = false">Cancelar</button>
            <button type="submit" class="btn btn-p" :disabled="guardando">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'loader','size' => '14','class' => 'spin','xShow' => 'guardando']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'loader','size' => '14','class' => 'spin','x-show' => 'guardando']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                <span x-text="form.id ? 'Guardar cambios' : 'Registrar pedido'"></span>
            </button>
        </div>
    </form>
</div>


<div class="modal-bg" x-show="detalle" x-cloak
     x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
     x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
     @keydown.escape.window="if (!confirmar && !form.abierto) detalle = null" @mousedown.self="detalle = null">
    <div class="modal" style="max-width:640px;">
        <div class="modal-hd">
            <div class="modal-ico"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard','size' => '18']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard','size' => '18']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></div>
            <div>
                <div class="modal-ttl" style="display:flex;align-items:center;gap:8px;">
                    <span x-text="'Pedido ' + (detalle?.numero || '')"></span>
                    <span class="badge" :class="detalle?.badge" x-text="detalle?.estado_texto"></span>
                </div>
                <div class="modal-sub" x-text="(detalle?.fecha || '') + ' Â· ' + canalTexto(detalle?.canal) + ' Â· ' + tipoTexto(detalle?.tipo)"></div>
            </div>
            <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="detalle = null" aria-label="Cerrar"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'x','size' => '16']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '16']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></button>
        </div>

        <div class="modal-bd">
            <div class="loadbar" x-show="cargandoDetalle"></div>

            
            <div class="pasos" x-show="detalle && detalle.estado !== 'cancelado'">
                <template x-for="(e, i) in pasos" :key="e[0]">
                    <button type="button" class="paso" :disabled="detalle?.estado === 'entregado' || moviendo"
                            :class="{ hecho: indicePaso(detalle?.estado) >= i, actual: detalle?.estado === e[0] }"
                            @click="cambiarEstado(detalle, e[0])">
                        <span class="paso-dot"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'check','size' => '11','stroke' => '3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check','size' => '11','stroke' => '3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></span>
                        <span x-text="e[1]"></span>
                    </button>
                </template>
            </div>
            <div class="nota" x-show="detalle?.estado === 'cancelado'" style="border-color:#FECACA;background:var(--redl);">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'alert','size' => '16']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'alert','size' => '16']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                <div>Este pedido fue cancelado y su stock volvió al inventario.</div>
            </div>

            <div class="det-cli">
                <span class="det-av" x-text="gh.iniciales(detalle?.cliente)"></span>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;" x-text="detalle?.cliente"></div>
                    <div class="sub" x-text="detalle?.tipo === 'delivery' ? (detalle?.direccion || 'Sin dirección') : tipoTexto(detalle?.tipo)"></div>
                </div>
                <a class="btn btn-wa btn-sm" x-show="detalle?.telefono" target="_blank" rel="noopener" :href="whatsapp(detalle)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'message','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'message','size' => '14']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> WhatsApp</a>
            </div>

            <div class="det-items">
                <template x-for="l in detalle?.lineas || []" :key="l.nombre + l.cantidad">
                    <div class="det-it">
                        <span class="det-q" x-text="l.cantidad + '—'"></span>
                        <span style="flex:1;"><span x-text="l.nombre"></span><span class="sub" x-show="l.notas" x-text="' Â· ' + l.notas"></span></span>
                        <span class="sub" x-text="gh.soles(l.precio_unit)"></span>
                        <b x-text="gh.soles(l.subtotal)"></b>
                    </div>
                </template>
                <div class="pf-res" style="margin-top:8px;">
                    <div><span>Subtotal</span><span x-text="gh.soles(detalle?.subtotal)"></span></div>
                    <div x-show="detalle?.descuento > 0"><span>Descuento</span><span x-text="'âˆ’ ' + gh.soles(detalle?.descuento)"></span></div>
                    <div x-show="detalle?.costo_envio > 0"><span>Envío</span><span x-text="gh.soles(detalle?.costo_envio)"></span></div>
                    <div class="pf-tot"><span>Total</span><span x-text="gh.soles(detalle?.total)"></span></div>
                </div>
            </div>

            <div class="grid2" style="margin-top:14px;gap:12px;">
                <div class="det-box">
                    <div class="lbl">Pago</div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <b x-text="pagoTexto(detalle?.metodo_pago)"></b>
                        <span class="badge" :class="detalle?.pagado ? 'b-ok' : 'b-a'" x-text="detalle?.pagado ? 'Pagado' : 'Por cobrar'"></span>
                    </div>
                    <button type="button" class="link" style="font-size:12px;margin-top:6px;background:none;border:none;padding:0;cursor:pointer;" x-show="detalle?.estado !== 'cancelado'" @click="alternarPago(detalle)" x-text="detalle?.pagado ? 'Marcar como no pagado' : 'Marcar como pagado'"></button>
                </div>
                <div class="det-box">
                    <div class="lbl">Historial</div>
                    <template x-for="h in detalle?.historial || []" :key="h.texto">
                        <div class="sub" style="margin-bottom:2px;"><b style="color:var(--fg2);" x-text="h.fecha"></b> <span x-text="h.texto"></span></div>
                    </template>
                </div>
            </div>
            <div class="det-box" style="margin-top:12px;" x-show="detalle?.notas">
                <div class="lbl">Notas</div>
                <div style="font-size:13px;white-space:pre-line;" x-text="detalle?.notas"></div>
            </div>
        </div>

        <div class="modal-ft">
            <button type="button" class="btn btn-g del-btn" style="margin-right:auto;" x-show="editable(detalle)" @click="pedirCancelar(detalle)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'x','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '14']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Cancelar pedido</button>
            <button type="button" class="btn btn-g" x-show="editable(detalle)" @click="editar(detalle)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'pen','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'pen','size' => '14']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Editar</button>
            <button type="button" class="btn btn-p" x-show="detalle && siguiente(detalle)" :disabled="moviendo" @click="mover(detalle, siguiente(detalle))">
                <span x-text="'Mover a ' + tituloDe(siguiente(detalle))"></span> <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'arrow-right','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-right','size' => '14']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
            </button>
        </div>
    </div>
</div>


<div class="modal-bg" x-show="confirmar" x-cloak x-transition.opacity @keydown.escape.window="confirmar = null" @mousedown.self="confirmar = null" style="z-index:70;">
    <div class="modal" style="max-width:420px;">
        <div class="modal-bd" style="text-align:center;padding-top:24px;">
            <div class="conf-ico"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'alert','size' => '22']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'alert','size' => '22']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></div>
            <div class="modal-ttl" style="margin-bottom:6px;" x-text="'Â¿Cancelar el pedido ' + (confirmar?.numero || '') + '?'"></div>
            <div style="font-size:13px;color:var(--fg2);margin-bottom:12px;">Los productos vuelven al stock. Esta acción no se puede deshacer.</div>
            <input class="inp" x-model="motivo" maxlength="200" placeholder="Motivo (opcional): cliente no contestó¦">
        </div>
        <div class="modal-ft" style="justify-content:center;">
            <button type="button" class="btn btn-g" @click="confirmar = null">Volver</button>
            <button type="button" class="btn btn-danger" :disabled="guardando" @click="cancelar()">Sí, cancelar pedido</button>
        </div>
    </div>
</div>

<style>
    .pf-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(0,1fr);gap:22px;}
    .pf-der{border-left:1px solid var(--border);padding-left:22px;}
    .sub{display:block;font-size:11px;color:var(--fg3);font-weight:400;}
    .pf-buscar{position:relative;}
    .pf-buscar .search .inp{width:100%;}
    .det-av{width:38px;height:38px;border-radius:50%;background:var(--acl);color:var(--accent);font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .pf-sug{position:absolute;left:0;right:0;top:calc(100% + 4px);background:#fff;border:1px solid var(--border);border-radius:10px;box-shadow:0 12px 30px rgba(26,35,50,.14);z-index:5;max-height:280px;overflow-y:auto;padding:4px;}
    .pf-sug-i{display:flex;align-items:center;gap:10px;width:100%;border:none;background:none;padding:8px;border-radius:8px;cursor:pointer;font:inherit;font-size:13px;text-align:left;color:var(--fg);}
    .pf-sug-i:hover:not(:disabled){background:var(--surface2);}
    .pf-sug-i:disabled{opacity:.45;cursor:not-allowed;}
    .pf-th{width:34px;height:34px;border-radius:7px;background:var(--surface2) center/cover no-repeat;display:flex;align-items:center;justify-content:center;color:var(--fg3);flex-shrink:0;}
    .pf-lineas{margin-top:10px;display:flex;flex-direction:column;gap:6px;}
    .pf-linea{display:flex;align-items:center;gap:10px;padding:9px 10px;border:1px solid var(--border);border-radius:9px;animation:fadeUp .25s var(--ease) backwards;}
    .pf-sub{width:84px;text-align:right;font-weight:700;font-size:13px;font-variant-numeric:tabular-nums;}
    .pf-vacio{display:flex;flex-direction:column;align-items:center;gap:6px;padding:26px;border:1.5px dashed var(--border);border-radius:10px;color:var(--fg3);font-size:12px;}
    .inp-sm{height:30px;font-size:12px;padding:4px 8px;margin-top:4px;}
    .qty{display:flex;align-items:center;border:1px solid var(--border);border-radius:8px;overflow:hidden;flex-shrink:0;}
    .qty button{width:28px;height:30px;border:none;background:var(--surface2);cursor:pointer;font-size:15px;color:var(--fg2);}
    .qty button:hover{background:var(--acl);color:var(--accent);}
    .qty input{width:38px;height:30px;border:none;text-align:center;font:inherit;font-weight:700;-moz-appearance:textfield;}
    .qty input::-webkit-inner-spin-button{-webkit-appearance:none;}
    .pf-tipos{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:12px;}
    .pf-tipos button{font:inherit;font-size:12px;font-weight:600;padding:8px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--fg2);cursor:pointer;transition:all .15s;}
    .pf-tipos button.on{border-color:var(--accent);background:var(--acl);color:var(--accent);}
    .pf-res{background:var(--surface3);border:1px solid var(--border);border-radius:10px;padding:10px 12px;font-size:13px;}
    .pf-res > div:not(.sub){display:flex;justify-content:space-between;padding:2px 0;font-variant-numeric:tabular-nums;}
    .pf-tot{border-top:1px solid var(--border);margin-top:4px;padding-top:6px !important;font-weight:800;font-size:16px;}
    .switch{display:flex;align-items:center;gap:10px;cursor:pointer;}
    .switch input{display:none;}
    .track{width:38px;height:22px;border-radius:99px;background:var(--border);position:relative;transition:background .2s;flex-shrink:0;}
    .thumb{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:transform .25s var(--ease);}
    .switch input:checked + .track{background:var(--green);}
    .switch input:checked + .track .thumb{transform:translateX(16px);}
    .pasos{display:flex;gap:4px;margin-bottom:16px;overflow-x:auto;}
    .paso{flex:1;display:flex;flex-direction:column;align-items:center;gap:5px;font:inherit;font-size:11px;font-weight:600;color:var(--fg3);background:none;border:none;cursor:pointer;padding:4px 2px;position:relative;min-width:64px;}
    .paso:not(:last-child)::after{content:'';position:absolute;top:13px;left:calc(50% + 14px);right:calc(-50% + 14px);height:2px;background:var(--border);}
    .paso.hecho:not(:last-child)::after{background:var(--green);}
    .paso-dot{width:22px;height:22px;border-radius:50%;border:2px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;color:transparent;transition:all .2s;}
    .paso.hecho .paso-dot{background:var(--green);border-color:var(--green);color:#fff;}
    .paso.actual{color:var(--fg);}
    .paso.actual .paso-dot{box-shadow:0 0 0 4px var(--greenl);}
    .paso:hover:not(:disabled) .paso-dot{border-color:var(--accent);}
    .paso:disabled{cursor:default;}
    .det-cli{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--border);border-radius:10px;margin-bottom:12px;}
    .det-items{border:1px solid var(--border);border-radius:10px;padding:8px 12px 12px;}
    .det-it{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px dashed var(--border);font-size:13px;}
    .det-q{font-weight:800;color:var(--accent);min-width:28px;}
    .det-box{border:1px solid var(--border);border-radius:10px;padding:10px 12px;}
    .link{color:var(--accent);font-weight:600;}
    .conf-ico{width:46px;height:46px;border-radius:50%;background:var(--redl);color:var(--red);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;}
    .btn-danger{background:var(--red);color:#fff;border-color:var(--red);}
    .btn-danger:hover{filter:brightness(.93);}
    @media (max-width:860px){
        .pf-grid{grid-template-columns:1fr;}
        .pf-der{border-left:none;padding-left:0;}
        .pf-linea{flex-wrap:wrap;}
        .pf-sub{width:auto;margin-left:auto;}
    }
</style>
<?php /**PATH /var/www/html/resources/views/admin/pedidos-modales.blade.php ENDPATH**/ ?>