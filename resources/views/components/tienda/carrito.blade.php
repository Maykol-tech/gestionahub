{{-- GEST-106: carrito lateral y checkout en 3 pasos (datos y entrega → pago → confirmación) --}}
<template x-if="panel">
    <div class="velo" @click.self="panel = false" @keydown.escape.window="panel = false">
        <aside class="cajon" role="dialog" aria-modal="true" aria-label="Tu pedido">
            <header class="cajon-hd">
                <button type="button" class="volver" x-show="paso > 0" @click="paso--" aria-label="Atrás">←</button>
                <strong x-text="['Tu carrito', 'Tus datos y entrega', 'Pago', 'Confirma tu pedido'][paso]"></strong>
                <button type="button" class="x" @click="panel = false" aria-label="Cerrar">✕</button>
            </header>
            <div class="pasos" x-show="paso > 0">
                <template x-for="n in 3"><span :class="{ on: paso >= n }"></span></template>
            </div>

            <div class="cajon-body">
                <div class="aviso-ok" x-show="recuperado" x-cloak>Recuperamos tu carrito 🎉 <span x-show="cupon">Usa el cupón <b x-text="cupon"></b>.</span></div>
                <div class="error" x-show="error" x-text="error" x-cloak></div>

                {{-- 0. CARRITO --}}
                <div x-show="paso === 0">
                    <template x-if="! lineas.length">
                        <div class="vacio"><div class="vacio-i">🛒</div><b>Tu carrito está vacío</b><p class="muted">Agrega productos para hacer tu pedido.</p>
                            <button type="button" class="btn-sec" @click="panel = false">Ver productos</button></div>
                    </template>
                    <template x-for="(l, k) in lineas" :key="l.clave">
                        <div class="linea">
                            <div class="linea-img"><template x-if="l.foto"><img :src="l.foto" alt=""></template><template x-if="! l.foto"><span x-text="l.nombre.charAt(0)"></span></template></div>
                            <div class="linea-txt">
                                <b x-text="l.nombre"></b>
                                <small class="muted" x-text="l.detalle"></small>
                                <div class="linea-pie">
                                    <div class="stepper chico" x-show="! l.peso">
                                        <button type="button" @click="cambiar(k, -1)" aria-label="Menos">−</button><span x-text="l.cantidad"></span><button type="button" @click="cambiar(k, 1)" aria-label="Más">+</button>
                                    </div>
                                    <b x-text="dinero(l.precio * l.cantidad)"></b>
                                </div>
                            </div>
                            <button type="button" class="quitar" @click="quitar(k)" aria-label="Quitar">✕</button>
                        </div>
                    </template>
                </div>

                {{-- 1. DATOS Y ENTREGA --}}
                <div x-show="paso === 1">
                    <label class="lbl">Tu nombre<input class="inp" x-model.trim="form.nombre" maxlength="120" autocomplete="name" placeholder="Nombre y apellido"></label>
                    <label class="lbl">Celular (WhatsApp)<input class="inp" x-model.trim="form.telefono" inputmode="tel" maxlength="20" autocomplete="tel" placeholder="9 dígitos" @change="capturar(); cotizar()"></label>
                    <div class="sub">¿Cómo lo recibes?</div>
                    <div class="radios">
                        <template x-for="e in cfg.entregas" :key="e.id">
                            <label class="radio" :class="{ on: form.entrega_id === e.id, off: subtotalLocal() < e.minimo }">
                                <input type="radio" name="entrega" :value="e.id" :disabled="subtotalLocal() < e.minimo" @change="form.entrega_id = e.id; cotizar()">
                                <span class="r-ico" x-text="{ delivery: '🛵', recojo: '🏪', agencia: '📦', local: '🍽️' }[e.tipo] || '📦'"></span>
                                <span class="r-txt"><b x-text="e.nombre"></b>
                                    <small x-text="[e.tiempo, e.minimo > 0 ? 'Mínimo ' + dinero(e.minimo) : null, e.gratis_desde ? 'Gratis desde ' + dinero(e.gratis_desde) : null, e.descripcion].filter(Boolean).join(' · ')"></small></span>
                                <span class="r-precio" x-text="e.usa_zonas ? 'Según zona' : (e.costo > 0 && e.pide_direccion ? dinero(e.costo) : 'Gratis')"></span>
                            </label>
                        </template>
                        <template x-if="! cfg.entregas.length"><p class="muted">El negocio aún no configuró sus formas de entrega. Escríbele por WhatsApp.</p></template>
                    </div>
                    <template x-if="entregaSel()?.usa_zonas">
                        <label class="lbl">Tu zona
                            <select class="inp" x-model.number="form.zona_id" @change="cotizar()">
                                <option value="">Elige tu zona</option>
                                <template x-for="z in cfg.zonas" :key="z.id"><option :value="z.id" x-text="z.nombre + ' · ' + dinero(z.costo) + (z.tiempo ? ' · ' + z.tiempo : '')"></option></template>
                            </select>
                        </label>
                    </template>
                    <template x-if="entregaSel()?.pide_direccion">
                        <div>
                            <label class="lbl">Dirección de entrega<input class="inp" x-model.trim="form.direccion" maxlength="255" autocomplete="street-address" placeholder="Calle, número, referencia"></label>
                            <button type="button" class="btn-link" @click="ubicar()" :disabled="ubicando"><span x-text="form.lat ? '📍 Ubicación guardada' : (ubicando ? 'Buscando…' : '📍 Usar mi ubicación actual')"></span></button>
                            <a x-show="form.lat" class="btn-link" target="_blank" rel="noopener" :href="'https://www.google.com/maps?q=' + form.lat + ',' + form.lng">Ver en el mapa</a>
                        </div>
                    </template>
                    <template x-if="cfg.programar && cfg.franjas.length">
                        <div class="programar">
                            <label class="sw" x-show="cfg.abierta"><input type="checkbox" x-model="programar"> Programar para más tarde</label>
                            <div x-show="! cfg.abierta" class="muted chico">Ahora estamos cerrados. Elige para cuándo lo quieres:</div>
                            <select class="inp" x-show="programar || ! cfg.abierta" x-model="form.programado_para">
                                <option value="">Elige día y hora</option>
                                <template x-for="f in cfg.franjas" :key="f.valor"><option :value="f.valor" x-text="f.dia + ' · ' + f.hora"></option></template>
                            </select>
                        </div>
                    </template>
                </div>

                {{-- 2. PAGO --}}
                <div x-show="paso === 2">
                    <div class="radios">
                        <template x-for="m in cfg.pagos" :key="m.clave">
                            <label class="radio" :class="{ on: form.metodo_pago === m.clave }">
                                <input type="radio" name="pago" :value="m.clave" x-model="form.metodo_pago">
                                <span class="r-ico" x-text="m.icono"></span>
                                <span class="r-txt"><b x-text="m.nombre"></b><small x-text="m.numero ? m.nombre + ': ' + m.numero : m.detalle"></small></span>
                            </label>
                        </template>
                    </div>
                    <template x-if="pagoSel()?.qr && form.metodo_pago === 'yape'"><img class="qr" :src="pagoSel().qr" alt="QR de Yape"></template>
                    <div class="sub">¿Tienes un cupón?</div>
                    <div class="cupon">
                        <input class="inp" x-model.trim="cupon" maxlength="40" placeholder="CÓDIGO" @keydown.enter.prevent="cotizar()">
                        <button type="button" class="btn-sec" @click="cotizar()">Aplicar</button>
                    </div>
                    <div class="ok-chico" x-show="resumen?.cupon" x-text="'Cupón ' + resumen?.cupon?.codigo + ' aplicado: -' + dinero(resumen?.desc_cupon)"></div>
                    <template x-if="cfg.puntos && resumen?.puntos_disponibles?.puntos > 0">
                        <label class="puntos"><input type="checkbox" x-model="usarPuntos" @change="cotizar()">
                            <span><span>Usar <b x-text="resumen.puntos_disponibles.puntos"></b> puntos y ahorrar <b x-text="dinero(resumen.puntos_disponibles.soles)"></b></span>
                            <small class="muted" x-text="'Tienes ' + resumen.puntos_disponibles.saldo + ' puntos'"></small></span>
                        </label>
                    </template>
                    <label class="lbl">Notas para el negocio (opcional)<textarea class="inp" rows="2" x-model.trim="form.notas" maxlength="300" placeholder="Ej.: sin cebolla, tocar el timbre"></textarea></label>
                </div>

                {{-- 3. CONFIRMACIÓN --}}
                <div x-show="paso === 3">
                    <div class="conf">
                        <div><small class="muted">A nombre de</small><b x-text="form.nombre + ' · ' + form.telefono"></b></div>
                        <div><small class="muted">Entrega</small><b x-text="(entregaSel()?.nombre || '') + (form.direccion && entregaSel()?.pide_direccion ? ' · ' + form.direccion : '')"></b></div>
                        <div x-show="form.programado_para && (programar || ! cfg.abierta)"><small class="muted">Para</small><b x-text="(cfg.franjas.find(f => f.valor === form.programado_para) || {}).dia + ' · ' + (cfg.franjas.find(f => f.valor === form.programado_para) || {}).hora"></b></div>
                        <div><small class="muted">Pago</small><b x-text="pagoSel()?.nombre"></b></div>
                    </div>
                    <template x-for="l in (resumen?.lineas || [])"><div class="conf-l"><span x-text="(l.peso ? '' : l.cantidad + ' × ') + l.nombre"></span><b x-text="dinero(l.subtotal)"></b></div></template>
                </div>
            </div>

            <footer class="cajon-ft" x-show="lineas.length">
                <div class="tot-l"><span>Subtotal</span><span x-text="dinero(resumen?.subtotal ?? subtotalLocal())"></span></div>
                <div class="tot-l" x-show="paso > 0 && entregaSel()"><span>Envío</span><span x-text="resumen ? (resumen.envio > 0 ? dinero(resumen.envio) : 'Gratis') : '—'"></span></div>
                <div class="tot-l verde" x-show="resumen?.descuento > 0"><span>Descuentos</span><span x-text="'-' + dinero(resumen?.descuento)"></span></div>
                <div class="tot-l total"><span>Total</span><span x-text="dinero(resumen?.total ?? subtotalLocal())"></span></div>
                <button type="button" class="btn-main" @click="siguiente()" :disabled="enviando">
                    <span x-show="! enviando" x-text="['Continuar', 'Continuar al pago', 'Revisar pedido', 'Confirmar pedido'][paso]"></span>
                    <span x-show="enviando" x-cloak>Enviando tu pedido…</span>
                </button>
            </footer>
        </aside>
    </div>
</template>
