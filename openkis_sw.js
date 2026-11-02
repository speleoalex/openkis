/*
 * Service worker of the OpenKIS web app (registered by the themes that can be
 * installed as an app, e.g. openkis_fslo). It sits in the site root so that its
 * scope is the whole site.
 *
 * Nothing is cached: pages always come from the network, the worker only
 * answers with an offline page when the site cannot be reached. Only GET
 * page loads are handled; forms, data exports and the map requests go to the
 * network untouched.
 */
var OPENKIS_OFFLINE_PAGE = '<!doctype html><html lang="it"><head><meta charset="utf-8">'
    + '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Offline</title></head>'
    + '<body style="font-family:sans-serif;padding:2em;text-align:center;color:#333">'
    + '<h1>Connessione non disponibile</h1><p>Il catasto non e\' raggiungibile: controlla la connessione e riprova.</p>'
    + '<p><em>No connection: check the network and try again.</em></p>'
    + '<p><a href="" onclick="location.reload();return false">Riprova / Retry</a></p></body></html>';

self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function (event) {
    var request = event.request;
    if (request.method !== 'GET' || request.mode !== 'navigate') {
        return;
    }
    event.respondWith(fetch(request).catch(function () {
        return new Response(OPENKIS_OFFLINE_PAGE, {headers: {'Content-Type': 'text/html; charset=utf-8'}});
    }));
});
