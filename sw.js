/**
 * Buffalo Marathon 2025 - Service Worker
 * Production Ready - 2025-08-08 14:16:14 UTC
 * Progressive Web App Support
 */

const CACHE_NAME = 'buffalo-marathon-2025-v1.0.0';
const urlsToCache = [
    '/',
    '/categories.php',
    '/schedule.php',
    '/info.php',
    '/faq.php',
    '/contact.php',
    '/assets/css/style.css',
    '/assets/js/main.js',
    '/assets/images/logo.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'
];

// Install event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version or fetch from network
                return response || fetch(event.request);
            }
        )
    );
});

// Activate event
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});