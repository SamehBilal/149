const CACHE = 'building149-v4.9';
const ASSETS = ['./', './index.html', './manifest.json'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys =>
    Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
  ));
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  const req = e.request;
  const url = req.url;

  // Only handle same-origin http(s) GET requests.
  // Skips chrome-extension://, POST (save.php/upload.php), and cross-origin.
  if (req.method !== 'GET') return;
  if (!url.startsWith('http')) return;
  if (new URL(url).origin !== self.location.origin) return;

  // Never cache live data — always fetch fresh
  if (url.includes('building_data.json') || url.includes('save.php') || url.includes('upload.php')) {
    e.respondWith(fetch(req).catch(() => caches.match(req)));
    return;
  }

  // Cache-first for the app shell
  e.respondWith(
    caches.match(req).then(cached => cached || fetch(req).then(res => {
      // Only cache valid, basic (same-origin) responses
      if (!res || res.status !== 200 || res.type !== 'basic') return res;
      const copy = res.clone();
      caches.open(CACHE).then(c => c.put(req, copy));
      return res;
    }).catch(() => caches.match('./index.html')))
  );
});