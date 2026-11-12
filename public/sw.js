const CACHE = 'gh-v1';
const STATIC = ['/', '/img/icon-192.png', '/img/icon-512.png'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then(c => c.addAll(STATIC)));
});

self.addEventListener('fetch', (e) => {
    // No cachear rutas del panel ni CSRF
    if (e.request.url.includes('/admin') || e.request.headers.get('X-CSRF-TOKEN')) return;
    e.respondWith(
        caches.match(e.request).then(r => r || fetch(e.request).catch(() => caches.match('/offline')))
    );
});