const cacheName = 'dukalangu-v1';
const assets = [
  './',
  './index.php',
  './manifest.json',
  // Ongeza hapa picha au CSS files kama unazo tofauti
];

// Sakinisha Service Worker na hifadhi files (Cache)
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(cacheName).then(cache => {
      return cache.addAll(assets);
    })
  );
});

// Fanya app ifunguke haraka kwa kutumia Cache kwanza
self.addEventListener('fetch', e => {
  e.respondWith(
    caches.match(e.request).then(response => {
      return response || fetch(e.request);
    })
  );
});