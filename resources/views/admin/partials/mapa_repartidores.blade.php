{{-- GEST-24: mapa de repartidores con Leaflet.
     Uso: @include('admin.partials.mapa_repartidores', ['repartidores' => $repartidores])
     Acepta repartidores como objetos o arreglos con: nombre, telefono, vehiculo, disponible,
     lat_actual, lng_actual, updated_location_at.
     El mapa se crea con Alpine (x-init) y Leaflet se carga solo cuando hace falta, asi funciona
     tambien al entrar desde el menu sin recargar la pagina. --}}
@php
    $conUbicacion = collect($repartidores ?? [])
        ->filter(fn ($r) => data_get($r, 'lat_actual') !== null && data_get($r, 'lng_actual') !== null)
        ->map(function ($r) {
            $hace = data_get($r, 'updated_location_at');
            return [
                'nombre' => data_get($r, 'nombre'),
                'telefono' => data_get($r, 'telefono'),
                'vehiculo' => data_get($r, 'vehiculo'),
                'disponible' => (bool) data_get($r, 'disponible', true),
                'lat' => (float) data_get($r, 'lat_actual'),
                'lng' => (float) data_get($r, 'lng_actual'),
                'hace' => $hace ? \Illuminate\Support\Carbon::parse($hace)->diffForHumans() : null,
            ];
        })->values();
@endphp

<div class="panel fade-in mapa-rep" style="animation-delay:.12s">
    <div class="panel-hd">
        <div>
            <div class="panel-ttl">Repartidores en el mapa</div>
            <div class="panel-sub">{{ $conUbicacion->count() }} con ubicación</div>
        </div>
        <button type="button" class="btn btn-g btn-sm" style="margin-left:auto;" onclick="gh.ir ? gh.ir(location.href, false) : location.reload()">Actualizar</button>
    </div>
    <div class="mapa-rep-box">
        <div class="mapa-rep-el" x-data x-init="ghMapaRepartidores($el, @js($conUbicacion))"></div>
        @if ($conUbicacion->isEmpty())
            <div class="mapa-rep-aviso">Ningún repartidor ha compartido su ubicación todavía.</div>
        @endif
    </div>
</div>

<script>
    window.ghMapaRepartidores = function (el, datos) {
        const cargar = () => {
            if (window.L && window.L.map) return Promise.resolve();
            if (window._ghLeaflet) return window._ghLeaflet;
            window._ghLeaflet = new Promise((ok, mal) => {
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(css);
                const js = document.createElement('script');
                js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                js.onload = ok;
                js.onerror = () => { window._ghLeaflet = null; mal(new Error('No se pudo cargar el mapa')); };
                document.head.appendChild(js);
            });
            return window._ghLeaflet;
        };

        cargar().then(() => {
            if (!el.isConnected || el._mapa) return;
            const centro = datos.length ? [datos[0].lat, datos[0].lng] : [-12.0464, -77.0428];   // Lima
            const mapa = L.map(el, { scrollWheelZoom: false }).setView(centro, 13);
            el._mapa = mapa;
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(mapa);

            const puntos = [];
            datos.forEach(r => {
                const color = r.disponible ? '#16A34A' : '#94A3B8';
                const icono = L.divIcon({
                    className: '', iconSize: [16, 16], iconAnchor: [8, 8],
                    html: `<div style="width:16px;height:16px;border-radius:50%;background:${color};border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35);"></div>`,
                });
                const detalle = document.createElement('div');
                detalle.innerHTML = '<b></b><br><span></span><br><span></span><br><span style="color:#64748B"></span>';
                const [b, v, t, e] = detalle.querySelectorAll('b, span');
                b.textContent = r.nombre || ''; v.textContent = r.vehiculo || ''; t.textContent = r.telefono || 'Sin teléfono';
                e.textContent = (r.disponible ? 'Disponible' : 'Ocupado') + (r.hace ? ' · ' + r.hace : '');
                L.marker([r.lat, r.lng], { icon: icono }).addTo(mapa).bindPopup(detalle);
                puntos.push([r.lat, r.lng]);
            });
            if (puntos.length > 1) mapa.fitBounds(puntos, { padding: [30, 30] });
            setTimeout(() => mapa.invalidateSize(), 200);
        }).catch(err => {
            el.innerHTML = '<div style="display:flex;height:100%;align-items:center;justify-content:center;color:var(--fg3);font-size:13px;">' + err.message + '</div>';
        });
    };
</script>
