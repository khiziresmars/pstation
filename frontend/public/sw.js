/**
 * Phuket Yacht & Tours - Service Worker
 * Handles caching for offline support and performance
 */

const CACHE_NAME = 'phuket-yachts-v1';
const STATIC_CACHE = 'phuket-static-v1';
const DYNAMIC_CACHE = 'phuket-dynamic-v1';
const IMAGE_CACHE = 'phuket-images-v1';

// Static assets to cache on install
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/manifest.json',
  '/favicon.svg',
  '/offline.html'
];

// API endpoints to cache with network-first strategy
const API_CACHE_PATTERNS = [
  /\/api\/vessels/,
  /\/api\/tours/,
  /\/api\/settings/
];

// Image patterns to cache
const IMAGE_PATTERNS = [
  /\.(?:png|jpg|jpeg|svg|gif|webp)$/i,
  /\/uploads\//
];

// Cache limits
const CACHE_LIMITS = {
  dynamic: 50,
  images: 100
};

/**
 * Install event - cache static assets
 */
self.addEventListener('install', (event) => {
  console.log('[SW] Installing service worker...');

  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => {
        console.log('[SW] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => self.skipWaiting())
      .catch((error) => {
        console.error('[SW] Failed to cache static assets:', error);
      })
  );
});

/**
 * Activate event - cleanup old caches
 */
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating service worker...');

  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((name) => {
              return name !== STATIC_CACHE &&
                     name !== DYNAMIC_CACHE &&
                     name !== IMAGE_CACHE;
            })
            .map((name) => {
              console.log('[SW] Deleting old cache:', name);
              return caches.delete(name);
            })
        );
      })
      .then(() => self.clients.claim())
  );
});

/**
 * Fetch event - handle requests with appropriate strategy
 */
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip Telegram API and external requests
  if (url.origin !== location.origin) {
    return;
  }

  // Handle API requests with network-first strategy
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkFirstStrategy(request));
    return;
  }

  // Handle images with cache-first strategy
  if (isImageRequest(request)) {
    event.respondWith(cacheFirstStrategy(request, IMAGE_CACHE));
    return;
  }

  // Handle static assets with cache-first strategy
  event.respondWith(cacheFirstStrategy(request, STATIC_CACHE));
});

/**
 * Network-first strategy (for API calls)
 * Try network first, fall back to cache
 */
async function networkFirstStrategy(request) {
  const cache = await caches.open(DYNAMIC_CACHE);

  try {
    const networkResponse = await fetch(request);

    // Cache successful GET requests
    if (networkResponse.ok && shouldCacheApi(request)) {
      cache.put(request, networkResponse.clone());
      await trimCache(DYNAMIC_CACHE, CACHE_LIMITS.dynamic);
    }

    return networkResponse;
  } catch (error) {
    console.log('[SW] Network failed, trying cache:', request.url);

    const cachedResponse = await cache.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    // Return offline fallback for navigation requests
    if (request.mode === 'navigate') {
      return caches.match('/offline.html');
    }

    // Return error response for API
    return new Response(
      JSON.stringify({ error: 'Offline', message: 'No network connection' }),
      {
        status: 503,
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }
}

/**
 * Cache-first strategy (for static assets and images)
 * Try cache first, fall back to network
 */
async function cacheFirstStrategy(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cachedResponse = await cache.match(request);

  if (cachedResponse) {
    // Refresh cache in background
    refreshCache(request, cache);
    return cachedResponse;
  }

  try {
    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());

      if (cacheName === IMAGE_CACHE) {
        await trimCache(IMAGE_CACHE, CACHE_LIMITS.images);
      }
    }

    return networkResponse;
  } catch (error) {
    console.log('[SW] Network and cache failed:', request.url);

    // Return offline page for navigation
    if (request.mode === 'navigate') {
      return caches.match('/offline.html');
    }

    // Return placeholder for images
    if (isImageRequest(request)) {
      return caches.match('/icons/placeholder.png');
    }

    return new Response('Offline', { status: 503 });
  }
}

/**
 * Refresh cache in background (stale-while-revalidate)
 */
async function refreshCache(request, cache) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      cache.put(request, networkResponse);
    }
  } catch (error) {
    // Ignore refresh errors
  }
}

/**
 * Trim cache to limit
 */
async function trimCache(cacheName, maxItems) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();

  if (keys.length > maxItems) {
    const deleteCount = keys.length - maxItems;
    for (let i = 0; i < deleteCount; i++) {
      await cache.delete(keys[i]);
    }
  }
}

/**
 * Check if request is for an image
 */
function isImageRequest(request) {
  const url = request.url;
  return IMAGE_PATTERNS.some(pattern => pattern.test(url));
}

/**
 * Check if API response should be cached
 */
function shouldCacheApi(request) {
  const url = request.url;
  return API_CACHE_PATTERNS.some(pattern => pattern.test(url));
}

/**
 * Handle push notifications
 */
self.addEventListener('push', (event) => {
  if (!event.data) return;

  const data = event.data.json();

  const options = {
    body: data.body || 'New notification',
    icon: '/icons/icon-192x192.png',
    badge: '/icons/badge-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      url: data.url || '/',
      dateOfArrival: Date.now()
    },
    actions: data.actions || []
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'Phuket Yachts', options)
  );
});

/**
 * Handle notification clicks
 */
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = event.notification.data?.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Focus existing window if available
        for (const client of clientList) {
          if (client.url === url && 'focus' in client) {
            return client.focus();
          }
        }
        // Open new window
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
  );
});

/**
 * Background sync for offline bookings
 */
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-bookings') {
    event.waitUntil(syncPendingBookings());
  }
});

/**
 * Sync pending bookings when back online
 */
async function syncPendingBookings() {
  try {
    const db = await openIndexedDB();
    const pendingBookings = await getPendingBookings(db);

    for (const booking of pendingBookings) {
      try {
        const response = await fetch('/api/bookings', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(booking.data)
        });

        if (response.ok) {
          await removePendingBooking(db, booking.id);
        }
      } catch (error) {
        console.error('[SW] Failed to sync booking:', error);
      }
    }
  } catch (error) {
    console.error('[SW] Sync failed:', error);
  }
}

/**
 * IndexedDB helpers for offline bookings
 */
function openIndexedDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('phuket-yachts', 1);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);

    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('pending-bookings')) {
        db.createObjectStore('pending-bookings', { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

function getPendingBookings(db) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['pending-bookings'], 'readonly');
    const store = transaction.objectStore('pending-bookings');
    const request = store.getAll();

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
  });
}

function removePendingBooking(db, id) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['pending-bookings'], 'readwrite');
    const store = transaction.objectStore('pending-bookings');
    const request = store.delete(id);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve();
  });
}

console.log('[SW] Service worker loaded');
