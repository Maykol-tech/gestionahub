// Service worker de GestionaHub: instalable y con pagina de respaldo sin conexion.
// No guarda en cache paginas del panel ni respuestas de la API, porque contienen datos de cada negocio.
const VERSION = 'gh-v1';
const SHELL = ['/icons/icon.svg', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((c) => c.addAll(SHELL)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Solo navegaciones GET y recursos del shell; lo demas va directo a la red
    if (req.method !== 'GET') {
        return;
    }

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => new Response(
                '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
                '<body style="font-family:system-ui;padding:24px"><h1>Sin conexión</h1><p>Vuelve a intentarlo cuando tengas internet.</p></body>',
                { headers: { 'Content-Type': 'text/html; charset=utf-8' }, status: 503 }
            ))
        );
        return;
    }

    if (SHELL.some((p) => new URL(req.url).pathname === p)) {
        event.respondWith(caches.match(req).then((hit) => hit || fetch(req)));
    }
});
