/*
 * Service worker.
 * - Static assets (scripts, icons, manifest): cache-first, precached on install.
 * - Pages: network-first, so the reading is always for the current date; when offline, the last copy of
 *   that page (or an offline notice) is served.
 * - /api/ and calendar.php are never intercepted.
 * Bump VERSION on every release that changes a precached file.
 */
'use strict';

const VERSION = 'v1.5.0';
const STATIC_CACHE = 'biorrhythms-static-' + VERSION;
const PAGE_CACHE = 'biorrhythms-pages-' + VERSION;
const MAX_PAGES = 8;

const PRECACHE = [
    'manifest.webmanifest',
    'assets/profile.js',
    'assets/diary.js',
    'assets/chrono.js',
    'assets/notify.js',
    'assets/icons/icon-192.png',
    'assets/icons/icon-512.png',
    'assets/icons/icon-maskable-512.png',
    'assets/icons/apple-touch-icon.png',
    'assets/icons/icon.svg',
];

const OFFLINE_HTML = '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    + '<title>Sin conexión</title><body style="font:16px system-ui;background:#07111f;color:#ecf2ff;padding:2rem">'
    + '<h1>Sin conexión</h1><p>Esta página aún no está guardada en el dispositivo. Vuelve a intentarlo cuando tengas red.</p></body></html>';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('biorrhythms-') && k !== STATIC_CACHE && k !== PAGE_CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

function isStatic(url) {
    return url.pathname.includes('/assets/') || url.pathname.endsWith('/manifest.webmanifest');
}

async function trimPages(cache) {
    const keys = await cache.keys();
    await Promise.all(keys.slice(0, Math.max(0, keys.length - MAX_PAGES)).map((k) => cache.delete(k)));
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;
    if (url.pathname.includes('/api/') || url.pathname.endsWith('/calendar.php')) return;

    if (request.mode === 'navigate') {
        event.respondWith((async () => {
            const cache = await caches.open(PAGE_CACHE);
            try {
                const response = await fetch(request);
                if (response.ok) {
                    await cache.put(request, response.clone());
                    await trimPages(cache);
                }
                return response;
            } catch (error) {
                return (await cache.match(request))
                    || new Response(OFFLINE_HTML, { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
            }
        })());
        return;
    }

    if (isStatic(url)) {
        event.respondWith((async () => {
            const cached = await caches.match(request);
            if (cached) return cached;
            const response = await fetch(request);
            if (response.ok) (await caches.open(STATIC_CACHE)).put(request, response.clone());
            return response;
        })());
    }
});
