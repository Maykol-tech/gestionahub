{{-- GEST-81: galería pública con filtro por categoría, lightbox y carga diferida. Recibe $items (solo publicados). --}}
@php
    $galCats = $items->pluck('categoria')->filter()->unique()->sort()->values();
    $galData = $items->map(fn ($i) => ['src' => $i->imagen_url, 'titulo' => $i->titulo, 'desc' => $i->descripcion, 'cat' => $i->categoria])->values();
@endphp
<style>
    .ghg-filtros { display:flex; gap:6px; flex-wrap:wrap; justify-content:center; margin:0 0 16px; }
    .ghg-filtros button { border:1px solid var(--border); background:var(--card); color:var(--fg); border-radius:999px; padding:7px 14px; font:inherit; font-size:14px; cursor:pointer; transition: background .2s, color .2s; }
    .ghg-filtros button.on { background:var(--brand, #2B6EF6); border-color:var(--brand, #2B6EF6); color:#fff; }
    .ghg-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(210px, 1fr)); gap:12px; }
    .ghg-item { position:relative; margin:0; border-radius:14px; overflow:hidden; background:var(--card); border:1px solid var(--border); cursor:zoom-in; transition: transform .25s ease, box-shadow .25s ease, opacity .25s ease; }
    .ghg-item:hover { transform: translateY(-3px); box-shadow:0 14px 30px rgba(15,23,42,.14); }
    .ghg-item.fuera { display:none; }
    .ghg-item img { width:100%; aspect-ratio: 4 / 3; object-fit:cover; display:block; background:var(--bg); opacity:0; transition: opacity .4s ease; }
    .ghg-item img.cargada { opacity:1; }
    .ghg-item figcaption { padding:10px 12px; }
    .ghg-item figcaption strong { display:block; font-size:14px; }
    .ghg-item figcaption span { font-size:12px; color:var(--fg3); }
    .ghg-lb { position:fixed; inset:0; background:rgba(2,6,23,.92); display:none; align-items:center; justify-content:center; z-index:1000; padding:20px; }
    .ghg-lb.on { display:flex; animation: ghg-in .2s ease; }
    .ghg-lb img { max-width:min(1100px, 92vw); max-height:78vh; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,.5); }
    .ghg-lb .ghg-cap { color:#E2E8F0; text-align:center; margin-top:12px; font-size:15px; }
    .ghg-lb .ghg-cap small { display:block; color:#94A3B8; font-size:13px; margin-top:4px; }
    .ghg-lb button { position:absolute; background:rgba(255,255,255,.12); color:#fff; border:0; width:46px; height:46px; border-radius:50%; font-size:22px; cursor:pointer; }
    .ghg-lb button:hover { background:rgba(255,255,255,.25); }
    .ghg-cerrar { top:16px; right:16px; } .ghg-ant { left:16px; top:50%; } .ghg-sig { right:16px; top:50%; }
    @keyframes ghg-in { from { opacity:0; } to { opacity:1; } }
    @media (prefers-reduced-motion: reduce) { .ghg-item, .ghg-item img { transition:none; } }
</style>

<div class="ghg" id="galeria">
    @if ($galCats->count() > 1)
        <div class="ghg-filtros" role="tablist" aria-label="Filtrar trabajos">
            <button type="button" class="on" data-cat="">Todos</button>
            @foreach ($galCats as $cat)<button type="button" data-cat="{{ $cat }}">{{ $cat }}</button>@endforeach
        </div>
    @endif
    <div class="ghg-grid">
        @foreach ($items as $i => $item)
            <figure class="ghg-item" data-i="{{ $i }}" data-cat="{{ $item->categoria }}" tabindex="0" role="button" aria-label="Ver {{ $item->titulo }}">
                <img data-src="{{ $item->miniatura_url ?: $item->imagen_url }}" alt="{{ $item->titulo }}" loading="lazy" width="420" height="315">
                <figcaption><strong>{{ $item->titulo }}</strong>@if ($item->descripcion)<span>{{ $item->descripcion }}</span>@endif</figcaption>
            </figure>
        @endforeach
    </div>
</div>
<div class="ghg-lb" role="dialog" aria-modal="true" aria-label="Foto ampliada">
    <button type="button" class="ghg-cerrar" aria-label="Cerrar">×</button>
    <button type="button" class="ghg-ant" aria-label="Anterior">‹</button>
    <div><img alt=""><div class="ghg-cap"></div></div>
    <button type="button" class="ghg-sig" aria-label="Siguiente">›</button>
</div>
<script>
(() => {
    const datos = @json($galData);
    const raiz = document.getElementById('galeria'); if (!raiz) return;
    const items = [...raiz.querySelectorAll('.ghg-item')];
    const lb = document.querySelector('.ghg-lb'); const lbImg = lb.querySelector('img'); const lbCap = lb.querySelector('.ghg-cap');
    let actual = 0;

    // Carga diferida: la imagen se pide cuando se acerca a la pantalla
    const cargar = img => { if (img.dataset.src) { img.src = img.dataset.src; img.removeAttribute('data-src'); img.addEventListener('load', () => img.classList.add('cargada'), { once: true }); } };
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { cargar(e.target); io.unobserve(e.target); } }), { rootMargin: '200px' });
        raiz.querySelectorAll('img[data-src]').forEach(img => io.observe(img));
    } else raiz.querySelectorAll('img[data-src]').forEach(cargar);

    raiz.querySelectorAll('.ghg-filtros button').forEach(b => b.addEventListener('click', () => {
        raiz.querySelectorAll('.ghg-filtros button').forEach(x => x.classList.toggle('on', x === b));
        items.forEach(it => it.classList.toggle('fuera', !!b.dataset.cat && it.dataset.cat !== b.dataset.cat));
    }));

    const visibles = () => items.filter(it => !it.classList.contains('fuera')).map(it => +it.dataset.i);
    const mostrar = i => {
        actual = i; const d = datos[i];
        lbImg.src = d.src; lbImg.alt = d.titulo;
        lbCap.innerHTML = ''; lbCap.append(d.titulo); if (d.desc) { const s = document.createElement('small'); s.textContent = d.desc; lbCap.append(s); }
        lb.classList.add('on'); document.body.style.overflow = 'hidden';
    };
    const mover = paso => { const v = visibles(); const p = v.indexOf(actual); mostrar(v[(p + paso + v.length) % v.length]); };
    const cerrar = () => { lb.classList.remove('on'); document.body.style.overflow = ''; };
    items.forEach(it => { it.addEventListener('click', () => mostrar(+it.dataset.i)); it.addEventListener('keydown', e => { if (e.key === 'Enter') mostrar(+it.dataset.i); }); });
    lb.querySelector('.ghg-cerrar').onclick = cerrar; lb.querySelector('.ghg-ant').onclick = () => mover(-1); lb.querySelector('.ghg-sig').onclick = () => mover(1);
    lb.addEventListener('click', e => { if (e.target === lb) cerrar(); });
    document.addEventListener('keydown', e => { if (!lb.classList.contains('on')) return; if (e.key === 'Escape') cerrar(); if (e.key === 'ArrowLeft') mover(-1); if (e.key === 'ArrowRight') mover(1); });
})();
</script>
