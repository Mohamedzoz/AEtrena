// sw.js - Service Worker for Aeterna PWA (Advanced Caching)
const CACHE_NAME = 'aeterna-pwa-v4';
const STATIC_ASSETS = [
  'manifest.json',
  'https://cdn.tailwindcss.com',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
  'https://unpkg.com/lenis@1.0.45/dist/lenis.css',
  'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
  'https://unpkg.com/lenis@1.0.45/dist/lenis.min.js'
];

// 1. Install - Cache core assets
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

// 2. Activate - Cleanup old caches
self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
    ))
  );
  self.clients.claim();
});

// 3. Fetch Strategy
self.addEventListener('fetch', e => {
  const { request } = e;
  const url = new URL(request.url);

  // Skip non-GET requests (like form submissions)
  if (request.method !== 'GET') return;

  // Skip external scripts not in our list (optional)
  if (!url.origin.includes(self.location.hostname) && !STATIC_ASSETS.includes(request.url)) return;

  // Dynamic Content Strategy: Stale-While-Revalidate
  // This serves cached content immediately but updates it in the background
  e.respondWith(
    caches.open(CACHE_NAME).then(cache => {
      return cache.match(request).then(cachedResponse => {
        const fetchPromise = fetch(request).then(networkResponse => {
          // Update cache with fresh version
          if (networkResponse && networkResponse.status === 200) {
            cache.put(request, networkResponse.clone());
          }
          return networkResponse;
        }).catch(() => {
          // If network fails, we already have the cached version (if any)
        });

        // Return cached version immediately, or wait for network if nothing in cache
        return cachedResponse || fetchPromise;
      });
    })
  );
});

// Push notifications
self.addEventListener('push', e => {
  const data = e.data ? e.data.json() : {};
  const title = data.title || 'إشعار Aeterna';
  const options = {
    body: data.body || 'لديك إشعار جديد.',
    icon: '/uploads/assets/icon-192.png',
    badge: '/uploads/assets/icon-48.png'
  };
  e.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  e.waitUntil(clients.openWindow('/'));
});
