{{-- GEST-102/105: ficha del producto con fotos, variantes y venta por kilo --}}
<template x-if="ficha">
    <div class="velo" @click.self="cerrarFicha()" @keydown.escape.window="cerrarFicha()">
        <div class="hoja ficha" role="dialog" aria-modal="true" :aria-label="ficha.nombre">
            <button type="button" class="x" @click="cerrarFicha()" aria-label="Cerrar">✕</button>
            <div class="ficha-grid">
                <div class="galeria">
                    <template x-if="ficha.fotos.length"><img :src="ficha.fotos[foto]" :alt="ficha.nombre" class="g-main"></template>
                    <template x-if="! ficha.fotos.length"><div class="g-main ph" x-text="ficha.nombre.charAt(0)"></div></template>
                    <div class="g-mini" x-show="ficha.fotos.length > 1">
                        <template x-for="(f, k) in ficha.fotos" :key="k"><button type="button" :class="{ on: foto === k }" @click="foto = k"><img :src="f" alt=""></button></template>
                    </div>
                </div>
                <div class="ficha-info">
                    <h2 x-text="ficha.nombre"></h2>
                    <div class="precio grande">
                        <b x-text="dinero(precioFicha())"></b>
                        <s x-show="! sel.variante && ficha.normal" x-text="dinero(ficha.normal)"></s>
                        <s x-show="sel.variante && sel.variante.normal" x-text="dinero(sel.variante?.normal)"></s>
                    </div>
                    <p class="desc" x-show="ficha.descripcion" x-text="ficha.descripcion"></p>

                    <template x-if="ficha.variantes.length">
                        <div>
                            <div class="sub">Elige una opción</div>
                            <div class="opciones">
                                <template x-for="v in ficha.variantes" :key="v.id">
                                    <button type="button" class="op" :class="{ on: sel.variante?.id === v.id }" :disabled="v.agotado" @click="sel.variante = v; sel.cantidad = 1">
                                        <span x-text="v.nombre"></span><small x-text="v.agotado ? 'Agotado' : dinero(v.precio)"></small>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="ficha.kilo">
                        <div>
                            <div class="sub">¿Cuánto quieres? <span class="muted" x-text="dinero(ficha.kilo.precio_kg) + ' el kilo'"></span></div>
                            <div class="opciones">
                                <template x-for="g in ficha.kilo.presentaciones" :key="g">
                                    <button type="button" class="op" :class="{ on: Number(sel.peso) === g }" @click="sel.peso = g">
                                        <span x-text="textoPeso(g)"></span><small x-text="dinero(g * ficha.kilo.precio_kg)"></small>
                                    </button>
                                </template>
                            </div>
                            <label class="peso-libre" x-show="ficha.kilo.peso_libre">Otro peso (kg)
                                <input type="number" inputmode="decimal" step="0.05" :min="ficha.kilo.peso_minimo" max="50" x-model.number="sel.peso">
                            </label>
                            <p class="muted chico">El peso final puede variar un poco; si cambia, ajustamos el precio al despachar.</p>
                        </div>
                    </template>

                    <div class="comprar">
                        <div class="stepper" x-show="! ficha.kilo">
                            <button type="button" @click="sel.cantidad = Math.max(1, sel.cantidad - 1)" aria-label="Menos">−</button>
                            <span x-text="sel.cantidad"></span>
                            <button type="button" @click="sel.cantidad = Math.min(stockSel(), sel.cantidad + 1)" aria-label="Más">+</button>
                        </div>
                        <button type="button" class="btn-main" :disabled="! listoFicha()" @click="agregarFicha($event)">
                            <span x-text="listoFicha() ? 'Agregar · ' + dinero(precioFicha() * (ficha.kilo ? 1 : sel.cantidad)) : (ficha.variantes.length ? 'Elige una opción' : 'Elige el peso')"></span>
                        </button>
                    </div>
                    <p class="muted chico" x-show="! ficha.kilo && stockSel() <= 5 && stockSel() > 0" x-text="'¡Quedan solo ' + stockSel() + '!'"></p>
                </div>
            </div>
        </div>
    </div>
</template>
