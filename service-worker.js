const CACHE_NAME = 'portaria-digital-v1';
const ASSETS = [
  'index.html',
  'portaria.html',
  'condomino.html',
  'assets/css/style.css',
  'assets/js/audio-helper.js',
  'manifest.json'
];

// Install Event
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(ASSETS);
    })
  );
});

// Activate Event
self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
});

// Fetch Event
self.addEventListener('fetch', e => {
  // Check if request is SSE or external WebRTC, bypass cache
  if (e.request.url.includes('/functions/api') || e.request.url.includes('peerjs')) {
    return fetch(e.request);
  }
  
  e.respondWith(
    caches.match(e.request).then(cachedResponse => {
      return cachedResponse || fetch(e.request);
    })
  );
});

// Handle real Web Push notifications
self.addEventListener('push', event => {
  let data = { title: 'Portaria Digital', body: 'Nova chamada de áudio.' };
  
  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data = { title: 'Portaria Digital', body: event.data.text() };
    }
  }

  const options = {
    body: data.body,
    icon: 'https://cdn-icons-png.flaticon.com/512/1048/1048953.png',
    badge: 'https://cdn-icons-png.flaticon.com/512/1048/1048953.png',
    vibrate: [200, 100, 200, 100, 200, 100, 400],
    data: {
      url: data.url || 'condomino.html',
      action: data.action || 'incoming_call'
    },
    actions: [
      { action: 'accept', title: 'Atender', icon: '' },
      { action: 'decline', title: 'Recusar', icon: '' }
    ],
    tag: 'call-notification',
    requireInteraction: true // Keep notification active until user interacts
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Handle notification actions (clicks)
self.addEventListener('notificationclick', event => {
  event.notification.close();

  const urlToOpen = new URL(event.notification.data.url, self.location.origin).href;

  if (event.action === 'accept') {
    // Open the app and auto-answer
    event.waitUntil(
      clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
        for (let client of windowClients) {
          if (client.url === urlToOpen && 'focus' in client) {
            client.postMessage({ action: 'accept_call' });
            return client.focus();
          }
        }
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen + '?action=accept');
        }
      })
    );
  } else {
    // Decline action or clicking general notification body
    event.waitUntil(
      clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
        for (let client of windowClients) {
          if (client.url === urlToOpen && 'focus' in client) {
            client.postMessage({ action: 'decline_call' });
            return client.focus();
          }
        }
      })
    );
  }
});
