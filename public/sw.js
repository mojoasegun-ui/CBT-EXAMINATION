/* ExamCore service worker.
 *
 * - App files (CSS, JS, fonts, images, icons) are served from the device instantly
 *   (cache-first). Their URLs change with every deploy (?v=…), so updates are never stale.
 * - Pages always come from the network so data is live and private. Signed-in HTML is never
 *   stored on the device, which matters on shared school computers.
 * - When there is no connection, navigations show a friendly offline page instead of the
 *   browser's error screen.
 * - Anything that is not a GET (sign-in, saving answers, submitting) bypasses the worker.
 */
const VERSION = "examcore-2026-11-10b";
const STATIC_CACHE = `${VERSION}-static`;
const MAX_STATIC_ENTRIES = 200;
const scope = self.registration.scope; // e.g. https://cbt.example.ng/ or http://localhost/CBT-EXAMINATION/
const OFFLINE_URL = new URL("offline.html", scope).href;
const PRECACHE = [
    OFFLINE_URL,
    new URL("assets/fonts/inter-var.woff2", scope).href,
    new URL("assets/fonts/jakarta-var.woff2", scope).href,
    new URL("assets/icons/icon-192.png", scope).href,
    new URL("assets/images/art/exam-screen.svg", scope).href,
];

self.addEventListener("install", (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener("activate", (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((key) => key.startsWith("examcore-") && !key.startsWith(VERSION)).map((key) => caches.delete(key)));
        if (self.registration.navigationPreload) await self.registration.navigationPreload.enable();
        await self.clients.claim();
    })());
});

// School logos are cached; student photos never are (shared school computers).
const isStatic = (url) => url.origin === self.location.origin
    && (url.pathname.includes("/assets/")
        || (url.pathname.includes("/uploads/") && !url.pathname.includes("/students/"))
        || url.pathname.endsWith("/manifest.webmanifest"));

async function trim(cache) {
    const keys = await cache.keys();
    for (let i = 0; i < keys.length - MAX_STATIC_ENTRIES; i++) await cache.delete(keys[i]);
}

async function cacheFirst(request) {
    const cache = await caches.open(STATIC_CACHE);
    const hit = await cache.match(request);
    if (hit) return hit;
    const response = await fetch(request);
    if (response.ok && response.type === "basic") {
        cache.put(request, response.clone()).then(() => trim(cache));
    }
    return response;
}

async function networkPage(event) {
    try {
        const preloaded = await event.preloadResponse;
        return preloaded || await fetch(event.request);
    } catch {
        const cache = await caches.open(STATIC_CACHE);
        return (await cache.match(OFFLINE_URL)) || new Response("You are offline.", { status: 503, headers: { "Content-Type": "text/plain" } });
    }
}

self.addEventListener("fetch", (event) => {
    const { request } = event;
    if (request.method !== "GET") return;
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;
    if (request.mode === "navigate") {
        event.respondWith(networkPage(event));
        return;
    }
    if (isStatic(url)) {
        event.respondWith(cacheFirst(request));
    }
    // Everything else (JSON, downloads) goes straight to the network.
});

// Sign-out clears anything cached for the current device user.
self.addEventListener("message", (event) => {
    if (event.data === "skip-waiting") self.skipWaiting();
    if (event.data === "clear-uploads") {
        event.waitUntil(caches.open(STATIC_CACHE).then(async (cache) => {
            for (const request of await cache.keys()) if (request.url.includes("/uploads/")) await cache.delete(request);
        }));
    }
});


/* ---- Phone notifications -------------------------------------------------------------------
 * A push is only a wake-up. We then ask the site (using the signed-in cookie) what to show, so the
 * words never pass through the push provider. Each kind of notice buzzes and behaves differently.
 * (The alert sound is the phone's own notification sound; websites cannot choose a custom one.)
 */
const KINDS = {
    exam: { vibrate: [500, 150, 500, 150, 500], requireInteraction: true },
    result: { vibrate: [120, 80, 120, 80, 400] },
    announcement: { vibrate: [200, 100, 200] },
    engage: { vibrate: [100, 60, 100] },
    general: { vibrate: [150] },
};
const show = (n) => {
    const kind = KINDS[n.type] || KINDS.general;
    return self.registration.showNotification(n.title, {
        body: n.body,
        icon: new URL("assets/icons/icon-192.png", scope).href,
        badge: new URL("assets/icons/icon-192.png", scope).href,
        tag: `${n.type}-${n.id || Date.now()}`,
        renotify: true,
        vibrate: kind.vibrate,
        requireInteraction: !!kind.requireInteraction,
        silent: false,
        data: { url: new URL(String(n.url || "/").replace(/^\//, ""), scope).href },
    });
};
self.addEventListener("push", (event) => {
    event.waitUntil((async () => {
        // Normal case: the message arrives encrypted inside the push itself.
        let inline = null;
        try { inline = event.data ? event.data.json() : null; } catch (error) { inline = null; }
        if (inline && inline.title) {
            await show(inline);
            return;
        }
        // Plain wake-up ping: ask the site what to show (the signed-in or visitor cookie says who is asking).
        let items = [];
        try {
            const response = await fetch(new URL("push/pending", scope).href, { credentials: "same-origin", cache: "no-store" });
            if (response.ok) items = (await response.json()).items || [];
        } catch (error) { /* offline: fall back to a generic alert below */ }
        if (!items.length) items = [{ id: Date.now(), type: "general", title: "ExamCore", body: "You have a new update. Tap to open.", url: "/" }];
        for (const n of items) await show(n);
    })());
});
self.addEventListener("notificationclick", (event) => {
    event.notification.close();
    const target = event.notification.data?.url || scope;
    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: "window", includeUncontrolled: true });
        for (const client of windows) {
            if (client.url.startsWith(scope) && "focus" in client) {
                await client.focus();
                if ("navigate" in client) await client.navigate(target).catch(() => null);
                return;
            }
        }
        await self.clients.openWindow(target);
    })());
});
