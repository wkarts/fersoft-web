self.addEventListener('install', function (event) {
    console.log('Service Worker instalado.');
    event.waitUntil(
        caches.open('app-v1').then(function (cache) {
            return cache.addAll([
                '/',
                '/css/app.css',
                '/js/app.js',
            ]);
        })
    );
});

self.addEventListener('fetch', function (event) {
    event.respondWith(
        caches.match(event.request).then(function (response) {
            return response || fetch(event.request);
        })
    );
});
