/* Service worker for panel PWA */
self.addEventListener('install', function () {
  self.skipWaiting();
});
self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function (event) {
  if (!event.data) return;
  let payload = { title: 'Notificação', body: '', url: null };
  try {
    const data = event.data.json();
    payload = { title: data.title ?? payload.title, body: data.body ?? payload.body, url: data.url ?? null };
  } catch (_) {
    try {
      payload.body = event.data.text();
    } catch (_) {}
  }
  const icon = '/icons/icon-192x192.png';
  event.waitUntil(
    self.registration.showNotification(payload.title, {
      body: payload.body,
      icon: icon,
      badge: icon,
      tag: payload.url || 'panel-push',
      data: { url: payload.url },
    })
  );
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  const url = event.notification.data?.url;
  if (!url) return;
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (let i = 0; i < clientList.length; i++) {
        const base = url.split('?')[0];
        if (clientList[i].url === url || clientList[i].url.startsWith(base)) {
          return clientList[i].focus();
        }
      }
      if (self.clients.openWindow) return self.clients.openWindow(url);
    })
  );
});
