/*
 * Offline use of OpenKIS installed as an application: the code shared with the service
 * worker (openkis_sw.js loads this file with importScripts) and, in the pages, the
 * registration of the worker, the offline badge and the page "offline"
 * (sections/offline: downloads, offline search). Server side:
 * extra/openkis/openkis_offline.inc.php, openkis_offline.php;
 * plan: docs/piano_offline_webapp_2026-11-03.md.
 *
 * Caches (Cache Storage, named okis-<name>:<path of the site>, e.g. okis-core:/liguria/):
 * every site of the same origin (www.catastogrotte.net/liguria/, /piemonte/) has its own,
 * so that removing the offline copy, the limits and the clean-up of a site leave the
 * others alone. Before 2026-11-05 the names had no path and were shared by every site:
 * OKIS.migrateLegacy() moves the entries of a site out of those.
 *  okis-core     home, offline page, map pages, theme shells (fnshell=1), record indexes,
 *                metadata of the downloads (okis-meta/*.json)
 *  okis-pages    sheets downloaded from the offline page, as fragments (fnajax=1)
 *  okis-visited  pages and fragments kept while browsing (the last LIMITS.visited)
 *  okis-static   css, js, fonts, images of the site, map page
 *  okis-data     map layers (kml) and the other map data
 *  okis-media    images seen while browsing (thumbnails, photos)
 *  okis-images   images of the downloaded sheets
 *  okis-tiles    map tiles seen while browsing
 * Only the answers marked "X-Finis-Visitor: public" (what any anonymous visitor gets) are
 * kept: what a logged user sees never ends up on the device.
 */
(function (root) {
    'use strict';
    var OKIS = root.OKIS = root.OKIS || {};

    // names before 2026-11-05, shared by every site of the origin
    OKIS.LEGACY_CACHES = {
        core: 'okis-core', pages: 'okis-pages', visited: 'okis-visited', static: 'okis-static',
        data: 'okis-data', media: 'okis-media', images: 'okis-images', tiles: 'okis-tiles'
    };
    // names of this site, set by OKIS.setScope() (the object is changed in place: the
    // worker keeps a reference to it)
    OKIS.CACHES = {};
    OKIS.SCOPE = '';
    OKIS.LIMITS = {visited: 400, static: 1500, data: 200, media: 2000, tiles: 4000};

    /**
     * Gives the caches the names of a site (see above).
     * @param {string} scope address or path of the scope of the worker, e.g. /liguria/
     */
    OKIS.setScope = function (scope) {
        var path = new URL(scope || '/', root.location ? root.location.href : undefined).pathname;
        OKIS.SCOPE = path;
        Object.keys(OKIS.LEGACY_CACHES).forEach(function (k) {
            OKIS.CACHES[k] = OKIS.LEGACY_CACHES[k] + ':' + path;
        });
    };
    OKIS.setScope('/');
    /** @returns {boolean} a cache of this site, of any version of this code */
    OKIS.isOwnCache = function (name) {
        var suffix = ':' + OKIS.SCOPE;
        return name.indexOf('okis-') === 0 && name.length > suffix.length && name.slice(-suffix.length) === suffix;
    };
    /** @returns {string|null} the shared name (before 2026-11-05) of a cache of this site */
    OKIS.legacyName = function (name) {
        var k = Object.keys(OKIS.CACHES).filter(function (key) {
            return OKIS.CACHES[key] === name;
        })[0];
        return k ? OKIS.LEGACY_CACHES[k] : null;
    };
    /**
     * Moves the entries of this site out of the caches shared by every site before
     * 2026-11-05: the copies of this site go to its own caches (or are dropped, with drop),
     * the copies from other origins (map tiles, CDN) are copied and left for the other
     * sites; a shared cache with nothing of this origin left is deleted. Safe to run more
     * than once and from more pages at the same time.
     * @param {boolean} [drop] remove the entries of this site instead of moving them
     */
    OKIS.migrateLegacy = async function (drop) {
        var origin = root.location.origin;
        var prefix = origin + OKIS.SCOPE;
        var kinds = Object.keys(OKIS.LEGACY_CACHES);
        for (var i = 0; i < kinds.length; i++) {
            var name = OKIS.LEGACY_CACHES[kinds[i]];
            if (!(await caches.has(name))) {
                continue;
            }
            var legacy = await caches.open(name);
            var own = drop ? null : await caches.open(OKIS.CACHES[kinds[i]]);
            var keys = await legacy.keys();
            var others = 0;
            for (var j = 0; j < keys.length; j++) {
                var url = keys[j].url;
                var mine = url.indexOf(prefix) === 0;
                var foreign = url.indexOf(origin + '/') !== 0;
                if (!mine && !foreign) {
                    others++; // another site of this origin: it moves its entries by itself
                    continue;
                }
                if (own) {
                    var resp = await legacy.match(keys[j], {ignoreVary: true});
                    if (resp) {
                        await own.put(keys[j], resp);
                    }
                }
                if (mine) {
                    await legacy.delete(keys[j]);
                }
            }
            if (!others) {
                await caches.delete(name);
            }
        }
    };
    // parameters of the map exports that only move the map view: not part of the cache key
    OKIS.MAP_VIEW_PARAMS = ['lat', 'lon', 'zoom', 'point', 'baselayer', 'history', 'layers', 'table', 'num', 'nocache', 'fnajax'];

    /**
     * Cache key of an address: no hash, parameters sorted, so that the worker and the
     * downloads write the same address the same way.
     * @param {string} url
     * @param {string} [base]
     * @returns {string}
     */
    OKIS.key = function (url, base) {
        var u = new URL(url, base || (root.location ? root.location.href : undefined));
        var params = [];
        u.searchParams.forEach(function (v, k) {
            params.push([k, v]);
        });
        params.sort(function (a, b) {
            if (a[0] !== b[0]) {
                return a[0] < b[0] ? -1 : 1;
            }
            return a[1] < b[1] ? -1 : (a[1] > b[1] ? 1 : 0);
        });
        var q = params.map(function (p) {
            return encodeURIComponent(p[0]) + '=' + encodeURIComponent(p[1]);
        }).join('&');
        return u.origin + u.pathname + (q ? '?' + q : '');
    };
    /** @returns {string} key of the fragment (fnajax=1) of a page */
    OKIS.fragmentKey = function (url) {
        var u = new URL(url, root.location ? root.location.href : undefined);
        u.searchParams.set('fnajax', '1');
        return OKIS.key(u.href);
    };
    /** @returns {boolean} */
    OKIS.isFragment = function (url) {
        return new URL(url).searchParams.get('fnajax') === '1';
    };
    /** @returns {string} key of the theme shell of a section */
    OKIS.shellKey = function (siteurl, mod, lang) {
        return OKIS.key(siteurl + 'index.php?mod=' + encodeURIComponent(mod) + '&lang=' + encodeURIComponent(lang) + '&fnshell=1');
    };
    /** @returns {string} key of a metadata file of the downloads */
    OKIS.metaKey = function (siteurl, name) {
        return siteurl + 'okis-meta/' + name + '.json';
    };
    /** @returns {string} key of a map export, without the parameters of the view */
    OKIS.dataKey = function (url) {
        var u = new URL(url, root.location ? root.location.href : undefined);
        if (/openkis_(kml|markers\.js|json)\.php$/.test(u.pathname)) {
            OKIS.MAP_VIEW_PARAMS.forEach(function (p) {
                u.searchParams.delete(p);
            });
        }
        return OKIS.key(u.href);
    };
    /**
     * An answer that may be kept: complete, from the site, the same for every anonymous visitor.
     * @param {Response} resp
     * @returns {boolean}
     */
    OKIS.isPublic = function (resp) {
        return !!resp && resp.status === 200 && !resp.redirected
                && (resp.type === 'basic' || resp.type === 'default')
                && resp.headers.get('X-Finis-Visitor') === 'public';
    };
    OKIS.readJson = function (cacheName, key) {
        return caches.open(cacheName).then(function (c) {
            return c.match(key);
        }).then(function (r) {
            return r ? r.json() : null;
        }).catch(function () {
            return null;
        });
    };
    OKIS.writeJson = function (cacheName, key, data) {
        return caches.open(cacheName).then(function (c) {
            return c.put(key, new Response(JSON.stringify(data), {headers: {'Content-Type': 'application/json'}}));
        });
    };
    /**
     * Keeps the last "max" entries of a cache (the keys come in insertion order).
     */
    OKIS.trim = function (cacheName, max) {
        return caches.open(cacheName).then(function (c) {
            return c.keys().then(function (keys) {
                var extra = keys.length - max;
                var jobs = [];
                for (var i = 0; i < extra; i++) {
                    jobs.push(c.delete(keys[i]));
                }
                return Promise.all(jobs);
            });
        });
    };
    /**
     * Puts the section of a fragment (fnajax=1) back in the theme shell (fnshell=1) of its
     * section: the page as the server would send it.
     * @param {string} shell html of the shell, with <!--fnsection-->
     * @param {string} fragment html of the fragment
     * @returns {string|null} null when the shell has no place for the section
     */
    OKIS.compose = function (shell, fragment) {
        if (shell.indexOf('<!--fnsection-->') < 0) {
            return null;
        }
        var start = fragment.indexOf('<body>');
        var end = fragment.lastIndexOf('</body>');
        var body = (start >= 0 && end > start) ? fragment.substring(start + 6, end) : fragment;
        var html = shell.replace('<!--fnsection-->', function () {
            return body;
        });
        var title = /<title>([\s\S]*?)<\/title>/.exec(fragment);
        if (title) {
            html = html.replace(/<title>[\s\S]*?<\/title>/, function () {
                return '<title>' + title[1] + '</title>';
            });
        }
        return html;
    };
    /**
     * Adds a script to the head of a page.
     * @param {string} html
     * @param {object} vars window variables
     * @returns {string}
     */
    OKIS.inject = function (html, vars) {
        var js = '';
        Object.keys(vars).forEach(function (k) {
            js += 'window.' + k + '=' + JSON.stringify(vars[k]).replace(/</g, '\\u003c') + ';';
        });
        var tag = '<script>' + js + '</script>';
        var i = html.indexOf('</head>');
        return i >= 0 ? html.substring(0, i) + tag + html.substring(i) : tag + html;
    };
    /** @returns {string} the record address of an index with the id in place of {id} */
    OKIS.recordUrl = function (index, id) {
        return index.url.split('{id}').join(encodeURIComponent(id));
    };
    /** lower case, no accents: for the offline search */
    OKIS.normalize = function (s) {
        s = String(s === undefined || s === null ? '' : s).toLowerCase();
        return s.normalize ? s.normalize('NFD').replace(/[̀-ͯ]/g, '') : s;
    };

    if (typeof document === 'undefined') {
        return; // service worker: only the shared code above
    }

    //=========================================================================
    // pages of the site
    //=========================================================================
    var cfg = root.OPENKIS_OFFLINE;
    if (!cfg) {
        return;
    }
    OKIS.setScope(cfg.scope);
    var T = function (key) {
        return (cfg.texts && cfg.texts[key]) || key;
    };
    var supported = ('serviceWorker' in navigator) && ('caches' in root) && root.isSecureContext !== false;
    OKIS.supported = supported;
    OKIS.cfg = cfg;

    /** the site was opened as an installed application */
    OKIS.isApp = function () {
        return (root.matchMedia && root.matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true
                || (root.matchMedia && root.matchMedia('(display-mode: fullscreen)').matches);
    };

    // turned off by the administrator: the worker and the caches of this site leave this
    // browser (the worker keeps answering this page until it is closed: the caches are
    // dropped again when the page has loaded); the other sites of the origin keep theirs
    if (!cfg.enabled) {
        if (supported) {
            var dropCaches = function () {
                return caches.keys().then(function (names) {
                    return Promise.all(names.filter(OKIS.isOwnCache).map(function (n) {
                        return caches.delete(n);
                    }));
                }).then(function () {
                    return OKIS.migrateLegacy(true);
                }).catch(function () {});
            };
            navigator.serviceWorker.getRegistrations().then(function (regs) {
                return Promise.all(regs.filter(function (reg) {
                    return new URL(reg.scope).pathname === cfg.scope;
                }).map(function (reg) {
                    return reg.unregister();
                }));
            }).catch(function () {}).then(dropCaches);
            root.addEventListener('load', function () {
                setTimeout(dropCaches, 3000);
            });
        }
        return;
    }

    //---- network helpers ---------------------------------------------------
    var sleep = function (ms) {
        return new Promise(function (resolve) {
            setTimeout(resolve, ms);
        });
    };
    /**
     * GET without cookies (the content of an anonymous visitor), a few tries when the
     * server is busy.
     * @returns {Promise<Response>}
     */
    var getPublic = async function (url, tries) {
        tries = tries || 3;
        var last = null;
        for (var i = 0; i < tries; i++) {
            try {
                var resp = await fetch(url, {credentials: 'omit', cache: 'no-store', redirect: 'follow'});
                if (resp.status !== 503 && resp.status !== 502 && resp.status !== 504) {
                    return resp;
                }
                last = new Error('HTTP ' + resp.status);
            } catch (e) {
                last = e;
                if (!navigator.onLine) {
                    break;
                }
            }
            await sleep(1500 * (i + 1));
        }
        throw last || new Error('network');
    };
    var getJson = async function (url) {
        var resp = await getPublic(url);
        if (!resp.ok) {
            throw new Error('HTTP ' + resp.status);
        }
        return resp.json();
    };
    /** runs worker(item) on every item, "parallel" at a time */
    var pool = async function (items, parallel, worker, isCancelled) {
        var next = 0;
        var run = async function () {
            while (next < items.length && !(isCancelled && isCancelled())) {
                var item = items[next++];
                await worker(item);
            }
        };
        var runners = [];
        for (var i = 0; i < Math.max(1, parallel); i++) {
            runners.push(run());
        }
        await Promise.all(runners);
    };
    /** same-origin addresses of the css, scripts and images of a page */
    var assetsOf = function (html, pageurl) {
        var urls = [];
        var re = /<(link|script|img)\b[^>]*?\b(href|src)\s*=\s*["']([^"']+)["'][^>]*>/gi;
        var m;
        while ((m = re.exec(html))) {
            var tag = m[0];
            if (m[1].toLowerCase() === 'link' && !/rel\s*=\s*["']?(stylesheet|icon|shortcut icon|apple-touch-icon|manifest)/i.test(tag)) {
                continue;
            }
            try {
                var u = new URL(m[3].replace(/&amp;/g, '&'), pageurl);
                if (u.href.indexOf(cfg.siteurl) === 0) {
                    urls.push(u.href);
                }
            } catch (e) {
            }
        }
        return urls.filter(function (u, i) {
            return urls.indexOf(u) === i;
        });
    };
    /** stores the static files of a page (from the http cache when there) */
    var keepAssets = async function (urls) {
        var stat = await caches.open(OKIS.CACHES.static);
        var data = await caches.open(OKIS.CACHES.data);
        await pool(urls, 4, async function (url) {
            var path = url.substring(cfg.siteurl.length).split('?')[0];
            if (/^(index\.php|openkis_file\.php)$/.test(path)) {
                return; // record images: they belong to the sheets
            }
            try {
                if (/\.php$/.test(path)) {
                    var r = await getPublic(url, 1);
                    if (OKIS.isPublic(r)) {
                        await data.put(OKIS.dataKey(url), r);
                    }
                    return;
                }
                var resp = await fetch(url, {credentials: 'same-origin'});
                if (resp.ok && !resp.redirected) {
                    await stat.put(OKIS.key(url), resp);
                }
            } catch (e) {
            }
        });
    };
    /** opens a page in a hidden frame and waits for it (and its frames) to settle */
    var warmUp = function (url, settle) {
        return new Promise(function (resolve) {
            var frame = document.createElement('iframe');
            frame.setAttribute('aria-hidden', 'true');
            frame.tabIndex = -1;
            frame.style.cssText = 'position:absolute;left:-10000px;top:0;width:1024px;height:768px;border:0;visibility:hidden';
            var done = function () {
                setTimeout(function () {
                    frame.remove();
                    resolve();
                }, settle);
            };
            var guard = setTimeout(function () {
                frame.remove();
                resolve();
            }, settle + 30000);
            frame.onload = function () {
                clearTimeout(guard);
                done();
            };
            frame.src = url;
            document.body.appendChild(frame);
        });
    };

    //---- core: home, offline page, shells, indexes, map ------------------------
    /**
     * Everything needed to use the application offline except the sheets: the home, this
     * page, the theme shells of the sections, the record indexes (offline search), the map
     * page with its scripts and layers. Done by the installed application and by the
     * offline page; refreshed when the version of the site changes or once a day.
     * @param {boolean} force
     * @param {function} [progress] (done, total)
     * @returns {Promise<object>} the metadata of the core
     */
    OKIS.prepareCore = async function (force, progress) {
        var metaKey = OKIS.metaKey(cfg.siteurl, 'core-' + cfg.lang);
        var meta = await OKIS.readJson(OKIS.CACHES.core, metaKey);
        var fresh = meta && meta.version === cfg.version && (Date.now() - meta.time) < 24 * 3600 * 1000;
        if (fresh && !force) {
            return meta;
        }
        if (!navigator.onLine) {
            return meta;
        }
        var info = await getJson(cfg.info);
        var core = await caches.open(OKIS.CACHES.core);
        var data = await caches.open(OKIS.CACHES.data);
        var pages = [info.home, info.offline].concat(info.map.pages);
        var shells = info.shells.slice();
        var steps = pages.length + shells.length + info.sections.length + info.map.data.length + 2;
        var done = 0;
        var step = function () {
            done++;
            if (progress) {
                progress(done, steps);
            }
        };
        var assets = [];
        var keepPage = async function (url, key) {
            try {
                var resp = await getPublic(url);
                if (OKIS.isPublic(resp)) {
                    var html = await resp.clone().text();
                    assets = assets.concat(assetsOf(html, url));
                    await core.put(key, resp);
                }
            } catch (e) {
            }
            step();
        };
        await pool(pages, info.concurrency, function (url) {
            return keepPage(url, OKIS.key(url));
        });
        await pool(shells, info.concurrency, function (url) {
            return keepPage(url, OKIS.key(url));
        });
        // record indexes, for the offline search and the downloads
        await pool(info.sections.filter(function (s) {
            return s.count > 0;
        }), info.concurrency, async function (s) {
            try {
                var resp = await getPublic(s.index);
                if (OKIS.isPublic(resp)) {
                    await core.put(OKIS.key(s.index), resp);
                }
            } catch (e) {
            }
            step();
        });
        // map: page, scripts, layers
        try {
            var resp = await fetch(info.mapentry, {credentials: 'same-origin', cache: 'no-cache'});
            if (resp.ok) {
                var html = await resp.clone().text();
                await (await caches.open(OKIS.CACHES.static)).put(OKIS.key(info.mapentry), resp);
                assets = assets.concat(assetsOf(html, info.mapentry));
            }
        } catch (e) {
        }
        step();
        await pool(info.map.data, info.concurrency, async function (url) {
            try {
                var r = await getPublic(url);
                if (OKIS.isPublic(r)) {
                    await data.put(OKIS.dataKey(url), r);
                }
            } catch (e) {
            }
            step();
        });
        await keepAssets(assets.filter(function (u, i) {
            return assets.indexOf(u) === i;
        }));
        step();
        // the map loads more scripts and layers by itself (language, surveys): the map
        // pages are opened once in a hidden frame, the worker keeps what they ask
        if (navigator.serviceWorker && navigator.serviceWorker.controller) {
            for (var w = 0; w < info.map.pages.length; w++) {
                await warmUp(info.map.pages[w], 6000);
            }
        }
        meta = {version: info.version, pages: info.pages, lang: info.lang, time: Date.now(), info: info};
        await OKIS.writeJson(OKIS.CACHES.core, metaKey, meta);
        return meta;
    };

    /** metadata of the core, without network */
    OKIS.coreMeta = function () {
        return OKIS.readJson(OKIS.CACHES.core, OKIS.metaKey(cfg.siteurl, 'core-' + cfg.lang));
    };
    /** the index of a section kept in the core */
    OKIS.index = function (section) {
        return OKIS.readJson(OKIS.CACHES.core, OKIS.key(section.index));
    };

    //---- downloads of the sheets ---------------------------------------------
    var sectionMetaKey = function (mod) {
        return OKIS.metaKey(cfg.siteurl, 'section-' + mod + '-' + cfg.lang);
    };
    OKIS.sectionMeta = function (mod) {
        return OKIS.readJson(OKIS.CACHES.core, sectionMetaKey(mod));
    };
    /** same-origin images of a fragment */
    var imagesOf = function (html, pageurl) {
        var urls = [];
        var re = /<img\b[^>]*?\bsrc\s*=\s*["']([^"']+)["']/gi;
        var m;
        while ((m = re.exec(html))) {
            try {
                var u = new URL(m[1].replace(/&amp;/g, '&'), pageurl);
                if (u.href.indexOf(cfg.siteurl) === 0 && urls.indexOf(u.href) < 0) {
                    urls.push(u.href);
                }
            } catch (e) {
            }
        }
        return urls;
    };
    var running = null;
    OKIS.cancel = function () {
        if (running) {
            running.cancelled = true;
        }
    };
    OKIS.isRunning = function () {
        return !!running;
    };
    /**
     * Downloads (or brings up to date) the sheets of a section: only the new and changed
     * records (recordupdate), all of them when the code that renders the sheets changed or
     * when "full" is set; the sheets of the records that left the index are removed.
     * @param {object} section from the info: mod, title, index, list
     * @param {object} opts {images: bool, full: bool, progress: function(done, total, errors)}
     * @returns {Promise<object>} the metadata of the section
     */
    OKIS.downloadSection = async function (section, opts) {
        opts = opts || {};
        var job = {cancelled: false};
        running = job;
        try {
            return await downloadSection(section, opts, job);
        } finally {
            if (running === job) {
                running = null;
            }
        }
    };
    var downloadSection = async function (section, opts, job) {
        var core = await caches.open(OKIS.CACHES.core);
        var pagesCache = await caches.open(OKIS.CACHES.pages);
        var imagesCache = await caches.open(OKIS.CACHES.images);
        var coreMeta = await OKIS.coreMeta();
        var info = coreMeta && coreMeta.info;
        if (!info || coreMeta.version !== cfg.version) {
            coreMeta = await OKIS.prepareCore(true);
            info = coreMeta.info;
        }
        var resp = await getPublic(section.index);
        if (!OKIS.isPublic(resp)) {
            throw new Error('index');
        }
        await core.put(OKIS.key(section.index), resp.clone());
        var index = await resp.json();
        // the shell of the section, to put its sheets back in the theme
        var shellUrl = cfg.siteurl + 'index.php?mod=' + encodeURIComponent(section.mod) + '&lang=' + encodeURIComponent(cfg.lang) + '&fnshell=1';
        if (!(await core.match(OKIS.key(shellUrl)))) {
            var s = await getPublic(shellUrl);
            if (OKIS.isPublic(s)) {
                await core.put(OKIS.key(shellUrl), s);
            }
        }
        var meta = (await OKIS.sectionMeta(section.mod)) || {records: {}, images: false};
        // everything again: new rendering code, or the images asked now and not before; the
        // automatic update of the application (incremental) leaves a new rendering code to
        // the button "update" (the page shows "updates available"), not to load the server
        // with every sheet after each deploy
        var newCode = meta.pages !== info.pages;
        var full = !!opts.full || (newCode && !(opts.incremental && meta.time)) || (!!opts.images && !meta.images);
        var withImages = !!opts.images;
        var updatedAt = index.fields.indexOf('recordupdate');
        var ids = {};
        var todo = [];
        index.records.forEach(function (r) {
            var id = String(r[0]);
            var updated = updatedAt >= 0 ? String(r[updatedAt]) : '';
            ids[id] = updated;
            if (full || meta.records[id] === undefined || meta.records[id] !== updated || updated === '') {
                todo.push(id);
            }
        });
        // records gone or no longer public
        var removed = Object.keys(meta.records).filter(function (id) {
            return ids[id] === undefined;
        });
        for (var i = 0; i < removed.length; i++) {
            await pagesCache.delete(OKIS.fragmentKey(OKIS.recordUrl(index, removed[i])));
            delete meta.records[removed[i]];
        }
        // first page of the list of the section
        try {
            var listResp = await getPublic(OKIS.fragmentKey(index.list));
            if (OKIS.isPublic(listResp)) {
                await pagesCache.put(OKIS.fragmentKey(index.list), listResp);
            }
        } catch (e) {
        }
        var done = 0;
        var errors = 0;
        var report = function () {
            if (opts.progress) {
                opts.progress(done, todo.length, errors);
            }
        };
        report();
        var imagesSeen = {};
        await pool(todo, info.concurrency, async function (id) {
            var url = OKIS.recordUrl(index, id);
            try {
                var r = await getPublic(OKIS.fragmentKey(url));
                if (OKIS.isPublic(r)) {
                    var html = withImages ? await r.clone().text() : '';
                    await pagesCache.put(OKIS.fragmentKey(url), r);
                    meta.records[id] = ids[id];
                    if (withImages) {
                        var imgs = imagesOf(html, url);
                        for (var k = 0; k < imgs.length; k++) {
                            if (imagesSeen[imgs[k]] || (await imagesCache.match(OKIS.key(imgs[k])))) {
                                continue;
                            }
                            imagesSeen[imgs[k]] = true;
                            try {
                                var ir = await getPublic(imgs[k], 1);
                                if (OKIS.isPublic(ir) || (ir.ok && !/\.php$/.test(new URL(imgs[k]).pathname))) {
                                    await imagesCache.put(OKIS.key(imgs[k]), ir);
                                }
                            } catch (e) {
                            }
                        }
                    }
                } else {
                    errors++;
                }
            } catch (e) {
                errors++;
            }
            done++;
            if (done % 25 === 0) {
                await OKIS.writeJson(OKIS.CACHES.core, sectionMetaKey(section.mod), meta);
            }
            report();
        }, function () {
            return job.cancelled;
        });
        meta.mod = section.mod;
        meta.title = section.title;
        meta.count = Object.keys(meta.records).length;
        meta.total = index.records.length;
        meta.images = withImages;
        if (!job.cancelled) {
            if (full || !newCode) {
                meta.pages = info.pages;
            }
            meta.time = Date.now();
        }
        meta.errors = errors;
        await OKIS.writeJson(OKIS.CACHES.core, sectionMetaKey(section.mod), meta);
        if (navigator.storage && navigator.storage.persist) {
            navigator.storage.persist().catch(function () {});
        }
        return meta;
    };
    /** removes the downloaded sheets of a section (the index stays, for the search) */
    OKIS.removeSection = async function (section) {
        var meta = await OKIS.sectionMeta(section.mod);
        var index = await OKIS.index(section);
        var pagesCache = await caches.open(OKIS.CACHES.pages);
        if (meta && index) {
            var ids = Object.keys(meta.records);
            for (var i = 0; i < ids.length; i++) {
                await pagesCache.delete(OKIS.fragmentKey(OKIS.recordUrl(index, ids[i])));
            }
            await pagesCache.delete(OKIS.fragmentKey(index.list));
        }
        await (await caches.open(OKIS.CACHES.core)).delete(sectionMetaKey(section.mod));
    };
    /** removes every offline copy of this site from this browser (not of the other sites) */
    OKIS.removeAll = async function () {
        var names = await caches.keys();
        for (var i = 0; i < names.length; i++) {
            if (OKIS.isOwnCache(names[i])) {
                await caches.delete(names[i]);
            }
        }
        await OKIS.migrateLegacy(true);
    };
    /**
     * Brings the downloaded sections up to date (new and changed sheets only).
     * @param {function} [progress] (section, done, total)
     */
    OKIS.updateAll = async function (progress) {
        var core = await OKIS.prepareCore(true);
        if (!core || !core.info) {
            return;
        }
        for (var i = 0; i < core.info.sections.length; i++) {
            var s = core.info.sections[i];
            var meta = await OKIS.sectionMeta(s.mod);
            if (meta && meta.time) {
                await OKIS.downloadSection(s, {
                    images: !!meta.images, incremental: true, progress: function (done, total) {
                        if (progress) {
                            progress(s, done, total);
                        }
                    }
                });
            }
        }
    };

    //---- offline search ------------------------------------------------------
    var searchData = null;
    /** entries of every index kept in the core */
    OKIS.loadSearch = async function () {
        if (searchData) {
            return searchData;
        }
        var core = await OKIS.coreMeta();
        if (!core || !core.info) {
            return [];
        }
        var entries = [];
        for (var i = 0; i < core.info.sections.length; i++) {
            var s = core.info.sections[i];
            var index = await OKIS.index(s);
            if (!index || !index.records) {
                continue;
            }
            var titles = index.titlefields.map(function (f) {
                return index.fields.indexOf(f);
            });
            index.records.forEach(function (r) {
                var label = titles.map(function (t) {
                    return t >= 0 ? r[t] : '';
                }).filter(Boolean).join(' ');
                var values = {};
                index.fields.forEach(function (f, n) {
                    values[f] = r[n];
                });
                entries.push({
                    section: s, index: index, id: String(r[0]), label: label || String(r[0]), values: values,
                    code: OKIS.normalize(values.code || ''),
                    text: OKIS.normalize(r.join(' '))
                });
            });
        }
        searchData = entries;
        return entries;
    };
    /**
     * @param {string} q words, all of them must be found
     * @param {string} [mod] only this section
     * @returns {Promise<Array>}
     */
    OKIS.search = async function (q, mod) {
        var entries = await OKIS.loadSearch();
        var words = OKIS.normalize(q).split(/\s+/).filter(Boolean);
        var found = entries.filter(function (e) {
            if (mod && e.section.mod !== mod) {
                return false;
            }
            for (var i = 0; i < words.length; i++) {
                if (e.text.indexOf(words[i]) < 0) {
                    return false;
                }
            }
            return true;
        });
        var qn = words.join(' ');
        var rank = function (e) {
            if (qn && e.code === qn) {
                return 0;
            }
            if (qn && e.code.indexOf(qn) === 0) {
                return 1;
            }
            if (qn && OKIS.normalize(e.label).indexOf(qn) >= 0) {
                return 2;
            }
            return 3;
        };
        found.sort(function (a, b) {
            var d = rank(a) - rank(b);
            if (d) {
                return d;
            }
            return a.label.localeCompare(b.label, undefined, {numeric: true});
        });
        return found;
    };
    /** keys of the sheets kept on the device */
    OKIS.keptKeys = async function () {
        var set = {};
        var names = [OKIS.CACHES.pages, OKIS.CACHES.visited];
        for (var i = 0; i < names.length; i++) {
            var keys = await (await caches.open(names[i])).keys();
            keys.forEach(function (k) {
                set[k.url] = true;
            });
        }
        return set;
    };

    //---- registration and badge ----------------------------------------------
    var esc = function (s) {
        return String(s === undefined || s === null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    };
    /** date of a kept page (Date header; a proxy may send it twice, joined by a comma) */
    var parseDate = function (s) {
        var d = new Date(s);
        if (isNaN(d.getTime())) {
            d = new Date(String(s).split(/,\s*(?=[A-Z][a-z]{2},)/)[0]);
        }
        return d;
    };
    var formatDate = function (d) {
        if (isNaN(d.getTime())) {
            return '';
        }
        try {
            return d.toLocaleString(cfg.lang, {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'});
        } catch (e) {
            return d.toISOString();
        }
    };
    var ucfirst = function (s) {
        s = String(s);
        return s.charAt(0).toUpperCase() + s.substring(1);
    };
    var style = function () {
        if (document.getElementById('okis-style')) {
            return;
        }
        var css = '#okis-badge{position:fixed;left:10px;bottom:10px;z-index:3000;background:#333;color:#fff;border-radius:18px;'
                + 'padding:5px 14px;font:13px/1.4 sans-serif;box-shadow:0 1px 4px rgba(0,0,0,.4);max-width:calc(100vw - 20px)}'
                + '#okis-badge a{color:#9cf;text-decoration:underline;margin-left:8px}'
                + '#okis-badge.okis-online{background:#1b5e20}'
                + '.okis-app .okis-row{display:flex;flex-wrap:wrap;align-items:center;gap:6px;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.1)}'
                + '.okis-app .okis-grow{flex:1 1 200px;min-width:0}'
                + '.okis-app .okis-muted{color:#6c757d;font-size:.875em}'
                + '.okis-app .okis-bar{height:6px;background:#e9ecef;border-radius:3px;overflow:hidden;margin-top:4px}'
                + '.okis-app .okis-bar>div{height:100%;background:#0d6efd;width:0}'
                + '.okis-app .okis-tag{display:inline-block;font-size:.75em;padding:1px 6px;border-radius:8px;background:#e9ecef;color:#333;margin-left:4px}'
                + '.okis-app .okis-tag.okis-ok{background:#d1e7dd;color:#0f5132}'
                + '.okis-app .okis-result{display:block;padding:6px 4px;border-bottom:1px solid rgba(0,0,0,.08);text-decoration:none}'
                + '.okis-app .okis-result:hover{background:rgba(0,0,0,.04)}'
                + '.okis-app .okis-card{border:1px solid rgba(0,0,0,.15);border-radius:6px;padding:12px;margin-bottom:14px}'
                + '.okis-app dl{display:grid;grid-template-columns:max-content 1fr;gap:2px 12px;margin:0}'
                + '.okis-app dt{font-weight:600}.okis-app dd{margin:0}';
        var el = document.createElement('style');
        el.id = 'okis-style';
        el.textContent = css;
        document.head.appendChild(el);
    };
    /** the badge "offline" at the bottom of the page */
    var badge = function () {
        var copy = root.OKIS_OFFLINE_COPY;
        var el = document.getElementById('okis-badge');
        var offline = !navigator.onLine;
        if (!offline && !copy) {
            if (el) {
                el.remove();
            }
            return;
        }
        style();
        if (!el) {
            el = document.createElement('div');
            el.id = 'okis-badge';
            el.setAttribute('role', 'status');
            document.body.appendChild(el);
        }
        var html;
        if (offline) {
            el.className = '';
            html = esc(ucfirst(T('offline')));
            if (copy) {
                var when = formatDate(parseDate(copy));
                if (when) {
                    html += ' · ' + esc(T('copy of')) + ' ' + esc(when);
                }
            }
            if (cfg.offline && location.href.indexOf(cfg.offline) !== 0) {
                html += '<a href="' + esc(cfg.offline) + '">' + esc(ucfirst(T('search'))) + '</a>';
            }
        } else {
            el.className = 'okis-online';
            html = esc(ucfirst(T('online again'))) + '<a href="" onclick="location.reload();return false">' + esc(ucfirst(T('reload'))) + '</a>';
        }
        el.innerHTML = html;
    };

    var register = function () {
        if (!supported) {
            return Promise.resolve(null);
        }
        return navigator.serviceWorker.register(cfg.sw, {scope: cfg.scope, updateViaCache: 'none'}).catch(function () {
            return null;
        });
    };

    var start = function () {
        badge();
        root.addEventListener('online', badge);
        root.addEventListener('offline', badge);
        // the entries of this site still in the caches shared before 2026-11-05 move to
        // its own caches, after the new worker has taken over (in the page: in the worker
        // it would hold up the pages; the worker reads the old caches meanwhile)
        OKIS.migration = register().then(function () {
            return supported ? OKIS.migrateLegacy() : null;
        }).catch(function () {});
        OKIS.migration.then(function () {
            // the installed application keeps the core ready and the downloads up to date
            if (supported && OKIS.isApp() && navigator.onLine && !document.getElementById('okis-offline-app')) {
                return OKIS.coreMeta().then(function (meta) {
                    var old = !meta || meta.version !== cfg.version || (Date.now() - meta.time) > 24 * 3600 * 1000;
                    if (old) {
                        return OKIS.updateAll();
                    }
                });
            }
        }).catch(function () {});
        var app = document.getElementById('okis-offline-app');
        if (app) {
            OKIS.offlinePage(app);
        }
    };

    //=========================================================================
    // page "offline" (sections/offline)
    //=========================================================================
    OKIS.offlinePage = async function (app) {
        style();
        app.classList.add('okis-app');
        if (!supported) {
            app.innerHTML = '<div class="alert alert-warning">' + esc(ucfirst(T('not supported'))) + '</div>';
            return;
        }
        app.innerHTML = '<div id="okis-missing"></div>'
                + '<div class="okis-card"><form id="okis-search-form" role="search" onsubmit="return false">'
                + '<label for="okis-q" class="h5 d-block">' + esc(ucfirst(T('search'))) + '</label>'
                + '<div style="display:flex;gap:6px"><select id="okis-mod" class="form-control" style="max-width:40%"></select>'
                + '<input id="okis-q" type="search" class="form-control" autocomplete="off" placeholder="' + esc(T('search placeholder')) + '"></div>'
                + '</form><div id="okis-results" class="mt-2"></div></div>'
                + '<div class="okis-card"><div class="h5">' + esc(ucfirst(T('offline use'))) + '</div>'
                + '<p class="okis-muted" id="okis-hint"></p><div id="okis-status" class="okis-muted"></div>'
                + '<div id="okis-sections"></div>'
                + '<div class="mt-2" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">'
                + '<label class="mb-0"><input type="checkbox" id="okis-images"> ' + esc(ucfirst(T('images'))) + '</label>'
                + '<span class="okis-grow"></span>'
                + '<button type="button" class="btn btn-sm btn-primary" id="okis-update">' + esc(ucfirst(T('update'))) + '</button>'
                + '<button type="button" class="btn btn-sm btn-outline-danger" id="okis-remove-all">' + esc(ucfirst(T('remove all'))) + '</button>'
                + '</div><div id="okis-message" class="mt-2"></div></div>';
        var $ = function (id) {
            return document.getElementById(id);
        };
        $('okis-hint').textContent = ucfirst(OKIS.isApp() ? T('keep open') : T('install hint')) + (cfg.logged ? '. ' + ucfirst(T('logged hint')) + '.' : '.');
        var message = function (html, kind) {
            $('okis-message').innerHTML = html ? '<div class="alert alert-' + (kind || 'info') + ' mb-0">' + html + '</div>' : '';
        };

        //---- status and sections --------------------------------------------
        if (OKIS.migration) {
            await OKIS.migration;
        }
        var core = await OKIS.coreMeta();
        var busy = false;
        var renderStatus = async function () {
            var parts = [];
            parts.push(navigator.onLine ? 'online' : esc(T('offline')));
            if (core && core.time) {
                parts.push(esc(T('offline data of')) + ' ' + esc(formatDate(new Date(core.time))));
            }
            if (navigator.storage && navigator.storage.estimate) {
                try {
                    var est = await navigator.storage.estimate();
                    parts.push(esc(T('space used')) + ': ' + (est.usage / 1048576).toFixed(1) + ' MB');
                } catch (e) {
                }
            }
            $('okis-status').innerHTML = parts.join(' · ');
        };
        var renderSections = async function () {
            var box = $('okis-sections');
            if (!core || !core.info) {
                box.innerHTML = '<p class="okis-muted">' + esc(ucfirst(navigator.onLine ? '…' : T('page not available offline'))) + '</p>';
                return;
            }
            var html = '';
            for (var i = 0; i < core.info.sections.length; i++) {
                var s = core.info.sections[i];
                if (!s.count) {
                    continue;
                }
                var meta = await OKIS.sectionMeta(s.mod);
                var state = '';
                if (meta && meta.count) {
                    state = '<span class="okis-tag okis-ok">' + esc(T('downloaded')) + ' ' + meta.count + '/' + s.count
                            + (meta.time ? ' · ' + esc(formatDate(new Date(meta.time))) : '') + '</span>';
                    if (meta.pages && meta.pages !== core.info.pages) {
                        state += '<span class="okis-tag">' + esc(T('updates available')) + '</span>';
                    }
                }
                html += '<div class="okis-row" data-mod="' + esc(s.mod) + '"><div class="okis-grow"><strong>' + esc(s.title) + '</strong> '
                        + '<span class="okis-muted">' + s.count + ' ' + esc(T('sheets')) + '</span>' + state
                        + '<div class="okis-bar" hidden><div></div></div><div class="okis-muted okis-progress"></div></div>'
                        + '<button type="button" class="btn btn-sm btn-outline-primary okis-dl">' + esc(ucfirst(meta && meta.count ? T('update') : T('download'))) + '</button>'
                        + (meta && meta.count ? ' <button type="button" class="btn btn-sm btn-outline-secondary okis-rm">' + esc(ucfirst(T('remove'))) + '</button>' : '')
                        + '</div>';
            }
            box.innerHTML = html;
            box.querySelectorAll('.okis-dl').forEach(function (b) {
                b.onclick = function () {
                    download(b.closest('[data-mod]').getAttribute('data-mod'));
                };
            });
            box.querySelectorAll('.okis-rm').forEach(function (b) {
                b.onclick = async function () {
                    var mod = b.closest('[data-mod]').getAttribute('data-mod');
                    await OKIS.removeSection(sectionOf(mod));
                    await renderSections();
                    await renderStatus();
                };
            });
        };
        var sectionOf = function (mod) {
            return core.info.sections.filter(function (s) {
                return s.mod === mod;
            })[0];
        };
        var download = async function (mod, inUpdate) {
            if ((busy && !inUpdate) || !navigator.onLine) {
                return;
            }
            busy = true;
            message('');
            var row = document.querySelector('#okis-sections [data-mod="' + mod + '"]');
            var bar = row.querySelector('.okis-bar');
            var text = row.querySelector('.okis-progress');
            bar.hidden = false;
            row.querySelectorAll('button').forEach(function (b) {
                b.disabled = true;
            });
            try {
                var meta = await OKIS.downloadSection(sectionOf(mod), {
                    images: $('okis-images').checked,
                    progress: function (done, total, errors) {
                        bar.firstChild.style.width = (total ? Math.round(100 * done / total) : 100) + '%';
                        text.textContent = done + '/' + total + (errors ? ' (' + errors + ' !)' : '');
                    }
                });
                if (meta.errors) {
                    message(esc(ucfirst(T('download error'))), 'warning');
                }
            } catch (e) {
                message(esc(ucfirst(T('download error'))), 'warning');
            }
            busy = !!inUpdate;
            core = await OKIS.coreMeta();
            searchData = null;
            await renderSections();
            await renderStatus();
        };
        $('okis-update').onclick = async function () {
            if (busy || !navigator.onLine) {
                return;
            }
            busy = true;
            message(esc(ucfirst(T('keep open'))) + '…');
            try {
                core = await OKIS.prepareCore(true);
                for (var i = 0; i < core.info.sections.length; i++) {
                    var s = core.info.sections[i];
                    var meta = await OKIS.sectionMeta(s.mod);
                    if (meta && meta.time) {
                        await download(s.mod, true);
                    }
                }
                message(esc(ucfirst(T('up to date'))), 'success');
            } catch (e) {
                message(esc(ucfirst(T('download error'))), 'warning');
            }
            busy = false;
            searchData = null;
            core = await OKIS.coreMeta();
            await renderSections();
            await renderStatus();
        };
        $('okis-remove-all').onclick = async function () {
            if (!confirm(ucfirst(T('confirm remove')))) {
                return;
            }
            await OKIS.removeAll();
            core = null;
            searchData = null;
            await renderSections();
            await renderStatus();
        };

        //---- search ------------------------------------------------------------
        var kept = {};
        var renderSelect = function () {
            var sel = $('okis-mod');
            var html = '<option value="">' + esc(ucfirst(T('all sections'))) + '</option>';
            if (core && core.info) {
                core.info.sections.forEach(function (s) {
                    if (s.count) {
                        html += '<option value="' + esc(s.mod) + '">' + esc(s.title) + '</option>';
                    }
                });
            }
            sel.innerHTML = html;
        };
        var describe = function (e) {
            var v = e.values;
            var bits = [];
            ['comune', 'provincia', 'areas'].forEach(function (f) {
                if (v[f]) {
                    bits.push(v[f]);
                }
            });
            if (v.elevation) {
                bits.push(v.elevation + ' m s.l.m.');
            }
            if (v.depth_total) {
                bits.push('-' + v.depth_total + ' m');
            }
            if (v.lenght_total) {
                bits.push(v.lenght_total + ' m');
            }
            return bits.join(' · ');
        };
        var doSearch = async function () {
            var q = $('okis-q').value.trim();
            var mod = $('okis-mod').value;
            var box = $('okis-results');
            if (!q && !mod) {
                box.innerHTML = '';
                return;
            }
            var found = await OKIS.search(q, mod);
            var html = '<div class="okis-muted mb-1">' + found.length + ' ' + esc(T('results')) + '</div>';
            found.slice(0, 200).forEach(function (e) {
                var url = OKIS.recordUrl(e.index, e.id);
                var offline = kept[OKIS.key(url)] || kept[OKIS.fragmentKey(url)];
                html += '<a class="okis-result" href="' + esc(url) + '"><strong>' + esc(e.label) + '</strong>'
                        + (e.values.synonyms ? ' <span class="okis-muted">(' + esc(e.values.synonyms) + ')</span>' : '')
                        + (offline ? '<span class="okis-tag okis-ok">' + esc(T('available offline')) + '</span>' : '')
                        + (mod ? '' : '<span class="okis-tag">' + esc(e.section.title) + '</span>')
                        + '<div class="okis-muted">' + esc(describe(e)) + '</div></a>';
            });
            if (!found.length) {
                html = '<div class="okis-muted">' + esc(ucfirst(T('no results'))) + '</div>';
            }
            box.innerHTML = html;
        };
        var timer = null;
        $('okis-q').addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(doSearch, 200);
        });
        $('okis-mod').addEventListener('change', doSearch);

        //---- page asked offline and not kept -----------------------------------
        var showMissing = async function () {
            var missing = root.OKIS_MISSING;
            if (!missing || !core || !core.info) {
                return;
            }
            var box = $('okis-missing');
            var html = '<div class="alert alert-warning">' + esc(ucfirst(T('page not available offline'))) + '</div>';
            var path = missing.split('#')[0];
            var found = null;
            var section = null;
            for (var i = 0; i < core.info.sections.length && !found; i++) {
                var s = core.info.sections[i];
                var index = await OKIS.index(s);
                if (!index) {
                    continue;
                }
                var parts = index.url.split('{id}');
                var base = path.split('?')[0];
                if (parts.length === 2 && base.indexOf(parts[0]) === 0 && base.substring(base.length - parts[1].length) === parts[1]) {
                    var id = decodeURIComponent(base.substring(parts[0].length, base.length - parts[1].length));
                    if (id && id.indexOf('/') < 0) {
                        found = {index: index, id: id, section: s};
                    }
                }
                if (!found && !section && base.indexOf(index.list.split('?')[0]) === 0) {
                    section = s;
                }
            }
            if (found) {
                var rec = null;
                found.index.records.forEach(function (r) {
                    if (String(r[0]) === found.id) {
                        rec = r;
                    }
                });
                if (rec) {
                    html += '<div class="okis-card"><div class="h5">' + esc(found.index.titlefields.map(function (f) {
                        return rec[found.index.fields.indexOf(f)];
                    }).join(' ')) + ' <span class="okis-tag">' + esc(found.section.title) + '</span></div><dl>';
                    found.index.fields.forEach(function (f, n) {
                        if (n > 0 && rec[n] !== '' && f !== 'hasimage' && found.index.titlefields.indexOf(f) < 0) {
                            var label = (found.index.labels && found.index.labels[f]) || f.replace(/_/g, ' ');
                            html += '<dt>' + esc(ucfirst(label)) + '</dt><dd>' + esc(rec[n]) + '</dd>';
                        }
                    });
                    html += '</dl></div>';
                }
            }
            box.innerHTML = html;
            // a list of a section, or a search of the theme (q=...): the offline search
            var q = new URL(missing).searchParams.get('q');
            if (section) {
                $('okis-mod').value = section.mod;
            }
            if (q) {
                $('okis-q').value = q;
            }
            if (section || q) {
                doSearch();
            }
        };

        // offline: what is kept; online: the core is made or brought up to date first
        renderSelect();
        await renderSections();
        await renderStatus();
        if (navigator.onLine && (!core || core.version !== cfg.version || (Date.now() - core.time) > 24 * 3600 * 1000)) {
            $('okis-status').textContent = '…';
            busy = true;
            // first visit: the sections at once (a short answer of the server), the
            // downloads wait for the core, which takes a while (map pages, indexes)
            if (!core || !core.info) {
                try {
                    core = {info: await getJson(cfg.info)};
                    renderSelect();
                    await renderSections();
                    document.querySelectorAll('#okis-sections button').forEach(function (b) {
                        b.disabled = true;
                    });
                } catch (e) {
                }
            }
            try {
                core = (await OKIS.prepareCore(true, function (done, total) {
                    $('okis-status').textContent = T('map and search') + ' ' + done + '/' + total;
                })) || core;
            } catch (e) {
            }
            busy = false;
            renderSelect();
            await renderSections();
            await renderStatus();
        }
        kept = await OKIS.keptKeys();
        await showMissing();
        $('okis-q').focus();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}(typeof self !== 'undefined' ? self : this));
