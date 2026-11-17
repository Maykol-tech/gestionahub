{{-- GEST-102: tarjeta de producto (plantilla Alpine: espera la variable "p" del x-for) --}}
<article class="card-p" :class="{ agotado: p.agotado }" :style="'--d:' + Math.min(i, 12) * 40 + 'ms'">
    <a class="card-img" :href="urlProducto(p)" @click.prevent="abrir(p)" :aria-label="'Ver ' + p.nombre">
        <template x-if="p.fotos.length"><img :src="p.fotos[0]" :alt="p.nombre" loading="lazy" decoding="async"></template>
        <template x-if="! p.fotos.length"><span class="ph" x-text="p.nombre.charAt(0)"></span></template>
        <span class="tag oferta" x-show="p.normal" x-text="'-' + Math.round((1 - p.precio / p.normal) * 100) + '%'"></span>
        <span class="tag agot" x-show="p.agotado">Agotado</span>
        <span class="tag kilo" x-show="p.kilo">Por kilo</span>
    </a>
    <div class="card-body">
        <h3 x-text="p.nombre"></h3>
        <div class="precio">
            <span x-show="p.variantes.length" class="desde">Desde</span>
            <b x-text="dinero(p.precio)"></b><small x-show="p.kilo">/kg</small>
            <s x-show="p.normal" x-text="dinero(p.normal)"></s>
        </div>
        <button type="button" class="btn-add" :disabled="p.agotado" @click="rapido(p, $event)">
            <span x-text="p.agotado ? 'Agotado' : (p.variantes.length || p.kilo ? 'Elegir' : 'Agregar')"></span>
        </button>
    </div>
</article>
