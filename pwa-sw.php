<?php
require_once __DIR__ . '/app/init.php';
header('Content-Type: application/javascript; charset=UTF-8');
header('Cache-Control: no-cache');
$base = base_path();
$precache = [
    $base . '/assets/css/app.css',
    $base . '/assets/js/app.js',
    $base . '/assets/img/logo-icon.svg',
];
?>
const CACHE = 'smmturk-static-v1';
const PRECACHE = <?= json_encode($precache) ?>;

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE).then(function (cache) {
      return cache.addAll(PRECACHE.map(function (p) {
        return new Request(p, { credentials: 'same-origin' });
      })).catch(function () {});
    }).then(function () { return self.skipWaiting(); })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.filter(function (k) { return k !== CACHE; }).map(function (k) {
        return caches.delete(k);
      }));
    }).then(function () { return self.clients.claim(); })
  );
});

self.addEventListener('fetch', function (event) {
  var req = event.request;
  if (req.method !== 'GET') return;
  var url = new URL(req.url);
  if (url.pathname.indexOf('/assets/') === -1) return;
  event.respondWith(
    caches.match(req).then(function (cached) {
      if (cached) return cached;
      return fetch(req).then(function (res) {
        if (res && res.status === 200 && res.type === 'basic') {
          var copy = res.clone();
          caches.open(CACHE).then(function (cache) { cache.put(req, copy); });
        }
        return res;
      });
    })
  );
});
