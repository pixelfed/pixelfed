const OFFLINE_VERSION = 2;
const CACHE_NAME = `offline-v${OFFLINE_VERSION}`;
const OFFLINE_URL = "/offline.html";

const BYPASS_PREFIXES = [
    "/oauth",
    "/login",
    "/logout",
    "/register",
    "/password",
    "/auth",
    "/authorize_interaction",
    "/i/auth",
    "/i/confirm-email",
    "/settings/security",
    "/api",
    "/storage",
];

const shouldBypass = (url) =>
    BYPASS_PREFIXES.some(
        (prefix) =>
            url.pathname === prefix || url.pathname.startsWith(prefix + "/"),
    );

self.addEventListener("install", (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(CACHE_NAME);
            await cache.add(new Request(OFFLINE_URL, { cache: "reload" }));
            await self.skipWaiting();
        })(),
    );
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        (async () => {
            if ("navigationPreload" in self.registration) {
                await self.registration.navigationPreload.disable();
            }
            const keys = await caches.keys();
            await Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key)),
            );
            await self.clients.claim();
        })(),
    );
});

self.addEventListener("fetch", (event) => {
    const request = event.request;

    if (request.mode !== "navigate" || request.method !== "GET") {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin || shouldBypass(url)) {
        return;
    }

    event.respondWith(
        (async () => {
            try {
                return await fetch(request);
            } catch (error) {
                const cache = await caches.open(CACHE_NAME);
                const cachedResponse = await cache.match(OFFLINE_URL);
                return cachedResponse || Response.error();
            }
        })(),
    );
});
