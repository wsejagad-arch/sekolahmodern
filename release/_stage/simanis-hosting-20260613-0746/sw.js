const CACHE_NAME = 'simanis-cache-v1';
const ASSETS = [
  'index.php',
  'login.php',
  'css/mycss.css',
  'css/sb-admin-2.min.css',
  'img/6695f027d063a.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS).catch(() => {});
    })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      return cachedResponse || fetch(event.request);
    })
  );
});
