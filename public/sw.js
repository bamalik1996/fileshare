/* AirToShare service worker — shell cache v3 (Requirement 19) */
const CACHE_VERSION = 3;
const CACHE_NAME = 'airtoshare-shell-v' + CACHE_VERSION;

const PRECACHE = [
    '/',
    '/manifest.webmanifest',
    '/assets/css/bulma.min.css',
    '/assets/css/custom.css',
    '/assets/js/theme-manager.js',
    '/assets/js/clipboard.js',
    '/android-chrome-192x192.png',
    '/android-chrome-512x512.png',
];

const NEVER_CACHE = [
    /^\/api\/v1\//,
    /^\/api\/v2\//,
    /^\/p\//,
    /^\/download\//,
    /^\/broadcasting\//,
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(caches.open(CACHE_NAME).then((c) => c.addAll(PRECACHE).catch(() => undefined)));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((k) => k.startsWith('airtoshare-shell-v') && k !== CACHE_NAME)
                    .map((k) => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    if (NEVER_CACHE.some((re) => re.test(url.pathname))) {
        event.respondWith(fetch(req));
        return;
    }

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match('/'))
        );
    }
});
