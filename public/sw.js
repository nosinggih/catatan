// Service worker: keeps the app shell available offline and shows reminder
// notifications. API calls are never cached; the Pinia stores keep their own
// copy of the data in localStorage.
const CACHE = 'catatan-v2';
const SHELL_URL = '/';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/manifest.webmanifest', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Page loads: network first, then the cached shell (the SPA routes itself).
    if (request.mode === 'navigate') {
        if (url.pathname.startsWith('/auth/')) return;
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok && !response.redirected) {
                        const copy = response.clone();
                        caches.open(CACHE).then((cache) => cache.put(SHELL_URL, copy));
                    }
                    return response;
                })
                .catch(async () => (await caches.match(SHELL_URL)) || caches.match(OFFLINE_URL)),
        );
        return;
    }

    // Vite assets have hashed names, so a cached copy never goes stale.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();
                            caches.open(CACHE).then((cache) => cache.put(request, copy));
                        }
                        return response;
                    }),
            ),
        );
    }
});

self.addEventListener('push', (event) => {
    const payload = event.data ? event.data.json() : {};
    const title = payload.title || 'Catatan';
    event.waitUntil(
        self.registration.showNotification(title, {
            body: payload.body,
            icon: payload.icon || '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            tag: payload.tag,
            data: payload.data || {},
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/', self.location.origin).href;
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
            const existing = windows.find((w) => w.url.startsWith(self.location.origin));
            if (existing) {
                return existing.focus().then((w) => (w && 'navigate' in w ? w.navigate(target) : w));
            }
            return self.clients.openWindow(target);
        }),
    );
});
