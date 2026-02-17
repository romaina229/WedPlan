/**
 * sw.js — Service Worker PWA — Budget Mariage PJPM v2.1
 */
'use strict';

const CACHE_NAME = 'wedding-pwa-v2.0.2';
const STATIC_ASSETS = [
    './',
    './index.php',
    './assets/css/style.css',
    './assets/js/script.js',
    './assets/js/charts.js',
    './assets/images/wedding.jpg',
    './manifest.json',
    'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=roboto:wght@300;400;600&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
];

self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache =>
            cache.addAll(STATIC_ASSETS).catch(e => console.warn('[SW] Cache partiel:', e))
        )
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    // Passer les API en réseau direct
    if (url.pathname.includes('/api/') || url.pathname.includes('.php')) {
        return event.respondWith(
            fetch(event.request).catch(() =>
                new Response(JSON.stringify({ success: false, message: 'Hors ligne' }),
                    { headers: { 'Content-Type': 'application/json' }})
            )
        );
    }
    // Stratégie Cache First pour assets statiques
    event.respondWith(
        caches.match(event.request).then(cached => {
            if (cached) return cached;
            return fetch(event.request).then(response => {
                if (response.ok) {
                    caches.open(CACHE_NAME).then(c => c.put(event.request, response.clone()));
                }
                return response.clone();
            });
        }).catch(() => caches.match('./'))
    );
});
