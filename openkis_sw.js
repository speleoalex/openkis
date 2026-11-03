/*
 * Service worker of OpenKIS: the site keeps working without network for consultation
 * (docs/piano_offline_webapp_2026-11-03.md). Registered by openkis_offline.js from every
 * page (openkis_sw.js?v=<version>, extra/openkis/openkis_offline.inc.php); it sits in the
 * site root so that its scope is the whole site. The caches are described in
 * openkis_offline.js, which also holds the code shared with this worker.
 *
 * Only GET requests of the site are handled; forms, logins, the control center, the
 * exports and the APIs always go to the network untouched. Pages and data are kept only
 * when the server marks them "X-Finis-Visitor: public" (the same for every anonymous
 * visitor), so nothing a logged user sees is written on the device.
 *
 *  pages (navigations, fnajax fragments)  network first; offline: the kept page, or the
 *                                          downloaded fragment put back in the theme
 *                                          shell of its section, or the offline page
 *  map page, css, js, fonts, images        kept copy at once, refreshed in the background
 *  map layers (kml) and map data           network first, kept copy offline
 *  record images (thumbnails, files)       kept copy at once, refreshed in the background
 *  map tiles                               kept copy, else network (kept, up to a limit)
 *  css, scripts, fonts of public CDNs      kept copy at once, refreshed in the background
 */
var OKIS_VERSION = new URL(self.location.href).searchParams.get('v') || '0';
importScripts('openkis_offline.js?v=' + encodeURIComponent(OKIS_VERSION));

var SCOPE = self.registration.scope;
var C = OKIS.CACHES;
// a slow network gives the kept copy after this time (the page keeps loading behind)
var NAV_TIMEOUT = 12000;
// requests never handled (forms, APIs, exports, administration)
var NEVER = /^(openkis_mcp\.php|openkis_API\.php|openkis_offline\.php|openkis_insert\.php|openkis_gpx\.php|openkis_shapefile\.php|openkis_karstlink\.php|openkis_las\.php|openkis_photos_server\.php|openkis_update_sections\.php|wms_download\.php|captcha\.php|notifications\.php|refresh-cache\.php|clear_cache\.php|splx_geocode\.php|bs_request\.php|controlcenter\/)/;
// map data: network first, kept copy offline
var DATA = /^(openkis_kml\.php|openkis_kml_history\.php|openkis_markers\.js\.php|openkis_json\.php|openkis_map_access\.js\.php|bs_surveys\.js\.php|openkis_map_coordinates\.php|openkis_tracings\.php|openkis_temperatures\.php)$/;
var STATIC = /\.(css|js|mjs|json|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|otf|eot|kml|kmz|xml|htm|html|map)$/i;
// libraries and fonts of the themes from public CDNs (Font Awesome, Google Fonts...)
var CDN_HOSTS = /(^|\.)(cdnjs\.cloudflare\.com|cdn\.jsdelivr\.net|unpkg\.com|fonts\.googleapis\.com|fonts\.gstatic\.com|code\.jquery\.com|stackpath\.bootstrapcdn\.com|maxcdn\.bootstrapcdn\.com|use\.fontawesome\.com)$/i;
var TILE_HOSTS = /(^|\.)(tile\.opentopomap\.org|tile\.openstreetmap\.org|arcgisonline\.com|maptiler\.com|thunderforest\.com|tile\.osm\.org|wmflabs\.org|stamen\.com|cartocdn\.com)$/i;
var OFFLINE_HTML = '<!doctype html><html lang="it"><head><meta charset="utf-8">'
        + '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Offline</title></head>'
        + '<body style="font-family:sans-serif;padding:2em;text-align:center;color:#333">'
        + '<h1>Connessione non disponibile</h1><p>Questa pagina non &egrave; disponibile offline: controlla la connessione e riprova.</p>'
        + '<p><em>No connection: this page is not available offline, check the network and try again.</em></p>'
        + '<p><a href="" onclick="location.reload();return false">Riprova / Retry</a></p></body></html>';

self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    // caches of older versions of this worker (any okis-* name not in use)
    var used = Object.keys(C).map(function (k) {
        return C[k];
    });
    extend(event, caches.keys().then(function (names) {
        return Promise.all(names.filter(function (n) {
            return n.indexOf('okis-') === 0 && used.indexOf(n) < 0;
        }).map(function (n) {
            return caches.delete(n);
        }));
    }).then(function () {
        return self.clients.claim();
    }));
});

self.addEventListener('fetch', function (event) {
    var req = event.request;
    if (req.method !== 'GET') {
        return;
    }
    var url = new URL(req.url);
    if (url.origin !== self.location.origin) {
        if (req.destination === 'image' && TILE_HOSTS.test(url.hostname)) {
            event.respondWith(tile(event));
        } else if (CDN_HOSTS.test(url.hostname) && ['style', 'script', 'font'].indexOf(req.destination) >= 0) {
            event.respondWith(staticAsset(event, req.url, true));
        }
        return;
    }
    // the downloads of openkis_offline.js (no cookies) store their answers by themselves
    if (req.url.indexOf(SCOPE) !== 0 || req.credentials === 'omit') {
        return;
    }
    var path = req.url.substring(SCOPE.length).split('#')[0].split('?')[0];
    if (NEVER.test(path) || url.searchParams.has('export') || url.searchParams.has('fnlogin') || url.searchParams.has('fnshell')) {
        return;
    }
    var fnapp = url.searchParams.get('fnapp');
    if (fnapp === 'thumb') {
        event.respondWith(media(event));
        return;
    }
    if (fnapp) {
        return;
    }
    if (path === 'openkis_file.php' || path === 'openkis_icon.php') {
        if (req.destination === 'image') {
            event.respondWith(media(event));
        }
        return;
    }
    if (path === 'bs_map.htm') {
        // the map reads its parameters from the address: one copy for every view
        event.respondWith(staticAsset(event, OKIS.key(SCOPE + 'bs_map.htm')));
        return;
    }
    if (DATA.test(path)) {
        event.respondWith(data(event));
        return;
    }
    if (path.indexOf('openkis_proxy.php/') === 0) {
        event.respondWith(/kml|kmz/i.test(req.url) ? data(event) : tile(event));
        return;
    }
    if (req.mode === 'navigate' || url.searchParams.get('fnajax') === '1') {
        event.respondWith(page(event));
        return;
    }
    if (STATIC.test(path) && path.indexOf('misc/fndatabase/') !== 0) {
        event.respondWith(staticAsset(event));
    }
});

//---- helpers -------------------------------------------------------------------
/**
 * Keeps the worker alive for a background job; after the answer has gone the browser may
 * refuse (InvalidStateError): the job runs anyway.
 */
function extend(event, promise) {
    try {
        event.waitUntil(promise);
    } catch (e) {
    }
    return promise;
}
var putCount = 0;
/** stores an answer, and now and then trims the cache to its limit */
function keep(cacheName, key, resp) {
    return caches.open(cacheName).then(function (c) {
        return c.put(key, resp);
    }).then(function () {
        putCount++;
        var limit = OKIS.LIMITS[Object.keys(C).filter(function (k) {
            return C[k] === cacheName;
        })[0]];
        if (limit && putCount % 40 === 0) {
            return OKIS.trim(cacheName, limit);
        }
    }).catch(function () {});
}
/** the first of some caches holding the key */
function lookup(names, key) {
    var i = 0;
    var next = function () {
        if (i >= names.length) {
            return Promise.resolve(null);
        }
        var name = names[i++];
        return caches.open(name).then(function (c) {
            return c.match(key);
        }).then(function (r) {
            return r || next();
        });
    };
    return next();
}
function timeout(ms) {
    return new Promise(function (resolve, reject) {
        setTimeout(function () {
            reject(new Error('timeout'));
        }, ms);
    });
}
/** a kept page, marked for openkis_offline.js (badge "offline copy of ...") */
function marked(resp, extra) {
    return resp.text().then(function (html) {
        var vars = {OKIS_OFFLINE_COPY: resp.headers.get('Date') || new Date().toUTCString()};
        if (extra) {
            Object.keys(extra).forEach(function (k) {
                vars[k] = extra[k];
            });
        }
        return new Response(OKIS.inject(html, vars), {headers: {'Content-Type': 'text/html; charset=utf-8'}});
    });
}

//---- pages -----------------------------------------------------------------------
/**
 * The kept copy of a page: the page itself (browsing, home, offline page, map pages), or
 * its downloaded fragment put back in the theme shell of its section.
 * @returns {Promise<Response|null>}
 */
function keptPage(key, isFragment) {
    return lookup([C.visited, C.core, C.pages], key).then(function (found) {
        if (found) {
            return isFragment ? found : marked(found);
        }
        if (isFragment) {
            return null;
        }
        var fkey = OKIS.fragmentKey(key);
        return lookup([C.pages, C.visited], fkey).then(function (fragment) {
            if (!fragment) {
                return null;
            }
            var mod = fragment.headers.get('X-Finis-Section') || '';
            var lang = fragment.headers.get('X-Finis-Lang') || '';
            return lookup([C.core], OKIS.shellKey(SCOPE, mod, lang)).then(function (shell) {
                if (!shell) {
                    return null;
                }
                return Promise.all([shell.text(), fragment.text()]).then(function (texts) {
                    var html = OKIS.compose(texts[0], texts[1]);
                    if (html === null) {
                        return null;
                    }
                    return new Response(OKIS.inject(html, {OKIS_OFFLINE_COPY: fragment.headers.get('Date') || new Date().toUTCString()}),
                            {headers: {'Content-Type': 'text/html; charset=utf-8'}});
                });
            });
        });
    });
}
/** what a page asked offline and never kept gets: the offline page, with the search */
function missingPage(req) {
    return caches.open(C.core).then(function (c) {
        return c.keys().then(function (keys) {
            // the offline page of the language of the request, else any
            var candidates = keys.filter(function (k) {
                return /okis-meta\/core-/.test(k.url);
            });
            var read = function (i) {
                if (i >= candidates.length) {
                    return Promise.resolve(null);
                }
                return c.match(candidates[i]).then(function (r) {
                    return r.json();
                }).then(function (meta) {
                    if (meta && meta.info && meta.info.offline) {
                        return c.match(OKIS.key(meta.info.offline)).then(function (r) {
                            return r || read(i + 1);
                        });
                    }
                    return read(i + 1);
                });
            };
            return read(0);
        });
    }).then(function (offline) {
        if (offline) {
            return marked(offline, {OKIS_MISSING: req.url});
        }
        return new Response(OFFLINE_HTML, {status: 503, headers: {'Content-Type': 'text/html; charset=utf-8'}});
    }).catch(function () {
        return new Response(OFFLINE_HTML, {status: 503, headers: {'Content-Type': 'text/html; charset=utf-8'}});
    });
}
function page(event) {
    var req = event.request;
    var key = OKIS.key(req.url);
    var isFragment = OKIS.isFragment(req.url);
    var network = fetch(req).then(function (resp) {
        if (OKIS.isPublic(resp)) {
            extend(event, keep(C.visited, key, resp.clone()));
        }
        return resp;
    });
    // no unhandled rejection when the kept copy answers first
    network.catch(function () {});
    return keptPage(key, isFragment).catch(function () {
        return null;
    }).then(function (kept) {
        if (!kept) {
            return network.catch(function () {
                if (isFragment) {
                    return new Response('', {status: 504});
                }
                return missingPage(req);
            });
        }
        return Promise.race([network, timeout(NAV_TIMEOUT)]).catch(function () {
            return kept;
        });
    });
}

//---- static files, map page --------------------------------------------------------
function staticAsset(event, forcedKey, crossOrigin) {
    var req = event.request;
    var key = forcedKey || OKIS.key(req.url);
    return lookup([C.static], key).then(function (cached) {
        var network = fetch(req).then(function (resp) {
            // a CDN file may come without CORS (opaque, status 0): kept anyway, they are few
            var good = crossOrigin ? (resp.type === 'opaque' || (resp.ok && resp.type === 'cors')) : (resp.ok && !resp.redirected && resp.type === 'basic');
            if (good) {
                extend(event, keep(C.static, key, resp.clone()));
            }
            return resp;
        });
        if (cached) {
            extend(event, network.catch(function () {}));
            return cached;
        }
        // offline, a file asked with another version (?v=...) than the kept one: a page
        // kept before a deploy still finds its scripts and styles
        return network.catch(function (err) {
            return caches.open(C.static).then(function (c) {
                return c.match(req.url, {ignoreSearch: true});
            }).then(function (r) {
                if (r) {
                    return r;
                }
                throw err;
            });
        });
    });
}

//---- map data ----------------------------------------------------------------------
function data(event) {
    var req = event.request;
    var key = OKIS.dataKey(req.url);
    return fetch(req).then(function (resp) {
        if (OKIS.isPublic(resp)) {
            extend(event, keep(C.data, key, resp.clone()));
        }
        return resp;
    }).catch(function (err) {
        return lookup([C.data], key).then(function (cached) {
            if (cached) {
                return cached;
            }
            // a layer with filters, offline: the whole layer of the section
            var u = new URL(key);
            var mod = u.searchParams.get('mod');
            if (mod && /openkis_(kml|markers\.js|json)\.php$/.test(u.pathname)) {
                return lookup([C.data], OKIS.key(u.origin + u.pathname + '?mod=' + encodeURIComponent(mod))).then(function (r) {
                    if (r) {
                        return r;
                    }
                    throw err;
                });
            }
            throw err;
        });
    });
}

//---- record images -----------------------------------------------------------------
function media(event) {
    var req = event.request;
    var key = OKIS.key(req.url);
    return lookup([C.images, C.media], key).then(function (cached) {
        var network = fetch(req).then(function (resp) {
            if (OKIS.isPublic(resp)) {
                extend(event, keep(C.media, key, resp.clone()));
            }
            return resp;
        });
        if (cached) {
            extend(event, network.catch(function () {}));
            return cached;
        }
        return network;
    });
}

//---- map tiles ---------------------------------------------------------------------
function tile(event) {
    var req = event.request;
    var key = req.url;
    return lookup([C.tiles], key).then(function (cached) {
        if (cached) {
            return cached;
        }
        return fetch(req).then(function (resp) {
            // opaque answers (no CORS) are not kept: the browser counts them as very big
            if (resp.ok && (resp.type === 'cors' || resp.type === 'basic')) {
                extend(event, keep(C.tiles, key, resp.clone()));
            }
            return resp;
        });
    });
}
