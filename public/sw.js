/* ===============================
   Airtoshare Service Worker
   Optimized for Performance
   =============================== */

const CACHE_NAME = "airtoshare-v2";

// Cache ONLY minimal PWA / HTML assets
const CORE_ASSETS = ["/", "/site.webmanifest", "/logo.svg", "/icon.svg"];

/* ---------- INSTALL ---------- */
self.addEventListener("install", (event) => {
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(CORE_ASSETS);
        })
    );
});

/* ---------- ACTIVATE ---------- */
self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys.map((key) => {
                        if (key !== CACHE_NAME) {
                            return caches.delete(key);
                        }
                    })
                )
            )
            .then(() => self.clients.claim())
    );
});

/* ---------- FETCH ---------- */
self.addEventListener("fetch", (event) => {
    const req = event.request;
    const url = new URL(req.url);

    // Ignore cross-origin
    if (url.origin !== self.location.origin) return;

    // Ignore non-GET
    if (req.method !== "GET") return;

    // 🔥 IGNORE STATIC FILES (let browser + nginx handle them)
    if (
        url.pathname.startsWith("/assets/") ||
        url.pathname.endsWith(".css") ||
        url.pathname.endsWith(".js") ||
        url.pathname.endsWith(".woff2") ||
        url.pathname.endsWith(".woff") ||
        url.pathname.endsWith(".ttf") ||
        url.pathname.endsWith(".png") ||
        url.pathname.endsWith(".jpg") ||
        url.pathname.endsWith(".jpeg") ||
        url.pathname.endsWith(".svg") ||
        url.pathname.endsWith(".gif")
    ) {
        return;
    }

    // ✅ Handle ONLY HTML navigation requests
    if (req.mode === "navigate") {
        event.respondWith(fetch(req).catch(() => caches.match("/")));
    }
});
