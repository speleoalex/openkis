var touchDevice = ('ontouchstart' in document.documentElement);

var interactions;
if (touchDevice)
{
    interactions =  ol.interaction.defaults({dragPan: false, mouseWheelZoom: false}).extend([
        new ol.interaction.DragPan({
          condition: function (event) {
            return this.getPointerCount() === 2 || ol.events.condition.platformModifierKeyOnly(event);
          },
        }),
        new ol.interaction.MouseWheelZoom({
          condition: ol.events.condition.platformModifierKeyOnly,
        }),
      ])
    
}
function detectMob() {
    const toMatch = [
        /Android/i,
        /webOS/i,
        /iPhone/i,
        /iPad/i,
        /iPod/i,
        /BlackBerry/i,
        /Windows Phone/i
    ];
    return toMatch.some((toMatchItem) => {
        return navigator.userAgent.match(toMatchItem);
    });
}


var OPS_Map = {
    lat: 0,
    lon: 0,
    geocodepath: "",
    zoom: 7,
    markers: new Array(),
    markersLayer: new Array(),
    baselayers: new Array(),
    layers: new Array(),
    toinclude: new Array(),
    id_markers: 0,
    id_layers: 0,
    kmllayers: new Array(),
    id_baselayers: 0,
    map: null,
    view: null,
    kmllayerid: 0,
    TrackIsActive: false,
    FollowingIsActive: false,
    geolocationPositions: false,
    layergroups:[],
//    viewMapProjectonEPSG: 'EPSG:4326',
    viewMapProjectonEPSG: 'EPSG:900913',
    //   viewMapProjectonEPSG: 'EPSG:3857',

    inputProjectonEPSG: 'EPSG:4326',
    divID: false,
    popupElement: false,
    basepath: "./",
    defaultLat: 44,
    defaultLon: 48,
    defaultZoom: 5,
    popOver: null,
    mode: "",
    popupOverlay: null,

    /**
     * 
     * @param {type} divId
     * @returns {undefined}
     */
    init: function (divId)
    {
//        alert (window.location);
        if (geocodepath !== undefined)
            this.geocodepath = geocodepath;
        //--------------------------popup-------------------------------------->
        document.getElementById(divId).innerHTML = "<div id=\"opspopup\"><span class=\"\"></span></div>";
        this.popupElement = document.getElementById('opspopup');
        this.divID = divId;
        //--------------------------popup--------------------------------------<
        //------------------map object ---------------------------------------->
        this.view = new ol.View({
            center: ol.proj.transform([this.lon, this.lat], this.inputProjectonEPSG, this.viewMapProjectonEPSG),
            projection: this.viewMapProjectonEPSG,
            zoom: this.zoom});
        if (detectMob())
        {
            this.map = new ol.Map({
                target: document.getElementById(divId),
                view: this.view,
                 interactions : interactions
            });
        }
        else{
            // the wheel zooms only when wheelZoomCondition says so (see initWheelGate)
            this.map = new ol.Map({
                target: document.getElementById(divId),
                view: this.view,
                interactions: ol.interaction.defaults({mouseWheelZoom: false}).extend([
                    new ol.interaction.MouseWheelZoom({
                        condition: function (mapBrowserEvent) {
                            return OPS_Map.wheelZoomCondition(mapBrowserEvent);
                        }
                    })
                ])
            });
        }

        //------------------map object ----------------------------------------<
        //---------------- display popup on click------------------------------>
        this.map.on('click', function (evt) {
            OPS_Map.lastClickTime = Date.now();
            OPS_Map.onMapClick(evt);
        });
        this.addTapFallback();


        // change mouse cursor when over marker, the tooltip shows its name
        this.map.on('pointermove', function (e) {
            if (e.dragging) {
                OPS_Map.stopFollowing();
                //$(element).popover('destroy');
                return;
            }
            if (e.originalEvent && e.originalEvent.pointerType && e.originalEvent.pointerType !== 'mouse')
            {
                return;
            }
            var pixel = OPS_Map.map.getEventPixel(e.originalEvent);
            var hits = OPS_Map.getFeaturesNearPixel(pixel, OPS_Map.getHitTolerance(e.originalEvent));
            var target = OPS_Map.map.getTargetElement();
            target.style.cursor = hits.length > 0 ? 'pointer' : '';
            var names = [];
            for (var i = 0; i < hits.length && i < 5; i++)
            {
                if (hits[i].name)
                {
                    names.push(OPS_Map.decodeEntities(hits[i].name));
                }
            }
            if (hits.length > 5)
            {
                names.push("...");
            }
            target.title = names.join("\n");
        });
        //---------------- display popup on click------------------------------<
//------------------------ Geolocation marker---------------------------------->
        this.markerEl = new Image();
        this.markerEl.id = 'geolocation_marker';
        this.markerEl.src = OPS_Map.basepath + "images/geolocation_marker.png";
        this.markerEl.height = 21;
        this.GeolocationMarker = new ol.Overlay({
            positioning: 'center-center',
            element: this.markerEl,
            stopEvent: false
        });
        this.map.addOverlay(this.GeolocationMarker);
//------------------------ Geolocation marker----------------------------------<

        // no preventDefault of the wheel here: MouseWheelZoom does it when it zooms, and a
        // wheel it leaves alone must reach the page around the map, to scroll it
        this.initWheelGate();

//--------------------------- export PNG -------------------------------------->
        document.getElementById('export-png').addEventListener('click', function () {
            OPS_Map.map.once('rendercomplete', function () {
                var mapCanvas = document.createElement('canvas');
                var size = OPS_Map.map.getSize();
                mapCanvas.width = size[0];
                mapCanvas.height = size[1];
                mapCanvas.crossorigin = "anonymous";

                var mapContext = mapCanvas.getContext('2d');
                Array.prototype.forEach.call(document.querySelectorAll('.ol-layer canvas'), function (canvas) {
                    console.log(canvas);
                    if (canvas.width > 0) {
                        var opacity = canvas.parentNode.style.opacity;
                        mapContext.globalAlpha = opacity === '' ? 1 : Number(opacity);
                        var transform = canvas.style.transform;
                        // Get the transform parameters from the style's transform matrix
                        var matrix = transform.match(/^matrix\(([^\(]*)\)$/)[1].split(',').map(Number);
                        // Apply the transform to the export map context
                        CanvasRenderingContext2D.prototype.setTransform.apply(mapContext, matrix);
                        mapContext.drawImage(canvas, 0, 0);
                    }
                });
                if (navigator.msSaveBlob) {
                    // link download attribuute does not work on MS browsers
                    navigator.msSaveBlob(mapCanvas.msToBlob(), "export.png");
                } else {
//                    var link = document.getElementById('image-download');
                    var link = document.createElement('a');
                    link.href = mapCanvas.toDataURL();
                    link.download = "export.png";
                    link.click();
                }
            });
            OPS_Map.map.renderSync();
        });
//--------------------------- export PNG --------------------------------------<
//--------------------------- ZOOM HIDE LABELS--------------------------------->
        OPS_Map.map.on('moveend', function () {
            //console.log("on moveend")
            var zoom = OPS_Map.map.getView().getZoom();
            var showPointNames = (zoom > 16) ? true : false;
            var showPoints = (zoom > 10) ? true : false;
            for (var i in OPS_Map.kmllayers)
            {
                //OPS_Map.kmllayers[i].showLabels = true;
//                if (OPS_Map.kmllayers[i].getVisible() && OPS_Map.kmllayers[i].values_.source.format_.showPointNames_ != showPointNames)
                if (OPS_Map.kmllayers[i].isVisible == "auto")
                {
                    OPS_Map.kmllayers[i].setVisible(showPoints);
                }
                if (OPS_Map.kmllayers[i].isVisible == "!auto")
                {
                    OPS_Map.kmllayers[i].setVisible(!showPoints);
                }

                if (OPS_Map.kmllayers[i].getVisible() && OPS_Map.kmllayers[i].showPointNames != showPointNames)
                {

                    if (OPS_Map.kmllayers[i].showLabels === "auto")
                    {
                        console.log("change showPointNames in " + showPointNames);
                        OPS_Map.kmllayers[i].setSource(OPS_Map.scaleIconsWithZoom(new ol.source.Vector({
                            crossOrigin: "Anonymous",
                            url: OPS_Map.kmllayers[i].path,
                            projection: OPS_Map.view.getProjection(),
                            format: new ol.format.KML(
                                    {
                                        writeStyles: true,
                                        showPointNames: showPointNames
                                    })
                        })));
                        //OPS_Map.kmllayers[i].values_.source.format_.showPointNames_ = showPointNames;
                        // console.log(OPS_Map.kmllayers[i].getSource().setProperties({showPointNames: showPointNames}));//.setProperties({showPointNames: true});
                        //OPS_Map.kmllayers[i].setProperties({showPointNames: true});

                        OPS_Map.kmllayers[i].showPointNames = showPointNames;
                    }

                }

            }
            OPS_Map.UpdateOverlays();
        });
//--------------------------- ZOOM HIDE LABELS---------------------------------<        
    },

    MyAddLayers: function ()
    {
        OPS_Map.addOSMLayer("OSM Landscape", "https://a.tile.thunderforest.com/landscape/{z}/{x}/{y}.png", true, false);
        OPS_Map.addOSMLayer("OSM Cyclemap", 'https://a.tile.thunderforest.com/cycle/{z}/{x}/{y}.png', true, false);
    },
    popUpClose: function ()
    {
        if (OPS_Map.popupOverlay != null)
        {
            $(OPS_Map.popupElement).popover('destroy');
            return;
        }

    },
    popupClosingProcess: false,
    /** Hit tolerance around the pointer, in CSS pixels */
    hitToleranceMouse: 6,
    hitToleranceTouch: 16,
    /** Time of the last click emitted by OpenLayers */
    lastClickTime: 0,
    /** Features listed by the last multi-feature popup */
    hitList: [],
    /**
     * Hit tolerance for the pointer that generated the event: a finger
     * needs a much larger area than a mouse.
     * @param {Event} originalEvent
     * @returns {Number}
     */
    getHitTolerance: function (originalEvent)
    {
        var type = originalEvent ? originalEvent.pointerType : undefined;
        if (type === 'touch' || type === 'pen' || (!type && touchDevice))
        {
            return this.hitToleranceTouch;
        }
        return this.hitToleranceMouse;
    },
    /**
     * Features with a name or a description around a pixel, closest first.
     * A record shown by more layers is returned once.
     * @param {Array} pixel
     * @param {Number} tolerance
     * @returns {Array} [{feature, name, description, url, distance, coordinate}]
     */
    getFeaturesNearPixel: function (pixel, tolerance)
    {
        var map = this.map;
        var found = [];
        var seen = {};
        var clickCoordinate = map.getCoordinateFromPixel(pixel);
        if (!clickCoordinate)
        {
            return found;
        }
        map.forEachFeatureAtPixel(pixel, function (feature) {
            var name = feature.get('name');
            var description = feature.get('description');
            if (!name && !description)
            {
                return;
            }
            var url = OPS_Map.getFeatureUrl(feature);
            var key = url ? url : name + "|" + description;
            if (seen[key])
            {
                return;
            }
            seen[key] = true;
            var coordinate = clickCoordinate;
            var distance = 0;
            var geometry = feature.getGeometry();
            if (geometry)
            {
                coordinate = geometry.getClosestPoint(clickCoordinate);
                var p = map.getPixelFromCoordinate(coordinate);
                if (p)
                {
                    distance = Math.sqrt(Math.pow(p[0] - pixel[0], 2) + Math.pow(p[1] - pixel[1], 2));
                }
            }
            found.push({
                feature: feature,
                name: name,
                description: description,
                url: url,
                distance: distance,
                coordinate: coordinate
            });
        }, {hitTolerance: tolerance});
        found.sort(function (a, b) {
            return a.distance - b.distance;
        });
        return found;
    },
    /**
     * URL of the record of a feature: the "open" link of its KML
     * description (the one opened with window.open, or a ".../view/..." link).
     * @param {ol.Feature} feature
     * @returns {String|false}
     */
    getFeatureUrl: function (feature)
    {
        if (feature.opsRecordUrl !== undefined)
        {
            return feature.opsRecordUrl;
        }
        var url = false;
        var description = feature.get('description');
        if (typeof description === 'string' && description.indexOf('<a') !== -1 && window.DOMParser)
        {
            try {
                // DOMParser builds an inert document: images are not loaded
                var doc = new DOMParser().parseFromString(description, 'text/html');
                var links = doc.querySelectorAll('a[href]');
                for (var i = 0; i < links.length; i++)
                {
                    var onclick = links[i].getAttribute('onclick') || '';
                    var href = links[i].getAttribute('href');
                    if (onclick.indexOf('window.open') !== -1 || /\/view\//.test(href))
                    {
                        url = href;
                        break;
                    }
                }
            } catch (e) {
                url = false;
            }
        }
        feature.opsRecordUrl = url;
        return url;
    },
    /**
     * Opens a record in the "r" window, like the "open" link of the popup.
     * @param {String} url
     * @returns {Boolean} false if the browser blocked the window
     */
    openRecord: function (url)
    {
        var w = null;
        try {
            w = window.open(url, 'r', 'scrollbars=yes');
            if (w)
            {
                w.focus();
            }
        } catch (e) {
            w = null;
        }
        return w ? true : false;
    },
    /**
     * @param {String} text
     * @returns {String}
     */
    escapeHtml: function (text)
    {
        return String(text).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    },
    /**
     * Plain text of a KML name. The names arrive already escaped, sometimes
     * more than once ("DELL&amp;#039;EDERA"): the entities are decoded until
     * none is left, so that escapeHtml() escapes the text only once.
     * @param {String} text
     * @returns {String}
     */
    decodeEntities: function (text)
    {
        text = String(text);
        if (!window.DOMParser)
        {
            return text;
        }
        for (var i = 0; i < 3 && /&(#\d+|#x[0-9a-f]+|[a-z]+\d*);/i.test(text); i++)
        {
            // DOMParser builds an inert document: nothing in it runs or loads
            var decoded = new DOMParser().parseFromString(text, 'text/html').documentElement.textContent;
            if (decoded === text)
            {
                break;
            }
            text = decoded;
        }
        return text;
    },
    /**
     * Popup with the details of a feature found by getFeaturesNearPixel
     * @param {Object} hit
     * @returns {undefined}
     */
    showHitPopup: function (hit)
    {
        var coordinates = ol.proj.transform(hit.coordinate, OPS_Map.viewMapProjectonEPSG, OPS_Map.inputProjectonEPSG);
        OPS_Map.popUp(coordinates, hit.name ? OPS_Map.escapeHtml(OPS_Map.decodeEntities(hit.name)) : "", hit.description ? hit.description : "");
    },
    /**
     * Popup listing the features under the pointer: the name opens the
     * record, the icon shows the details.
     * @param {Array} coordinates click position (input projection)
     * @param {Array} hits result of getFeaturesNearPixel
     * @returns {undefined}
     */
    showHitListPopup: function (coordinates, hits)
    {
        var max = 20;
        OPS_Map.hitList = hits.slice(0, max);
        var html = "<div class=\"ops-popup-list\">";
        for (var i = 0; i < OPS_Map.hitList.length; i++)
        {
            var hit = OPS_Map.hitList[i];
            var label = OPS_Map.escapeHtml(hit.name ? OPS_Map.decodeEntities(hit.name) : "?");
            var details = "<a href=\"#\" title=\"Dettagli\" onclick=\"OPS_Map.showHitPopup(OPS_Map.hitList[" + i + "]);return false;\">";
            if (hit.url)
            {
                html += "<div>"
                        + "<a href=\"" + OPS_Map.escapeHtml(hit.url) + "\" target=\"_blank\" onclick=\"return !OPS_Map.openRecord(this.href);\">" + label + "</a> "
                        + details + "<span class=\"glyphicon glyphicon-info-sign\"></span></a></div>";
            } else
            {
                html += "<div>" + details + label + "</a></div>";
            }
        }
        if (hits.length > max)
        {
            html += "<div>... (" + (hits.length - max) + ")</div>";
        }
        html += "</div>";
        OPS_Map.popUp(coordinates, hits.length + " punti qui", html);
    },
    wheelGated: false,
    wheelArmed: false,
    wheelHint: null,
    wheelHintTimer: null,
    /**
     * A map inside a page (the iframe of a cave sheet, of a tracing, of a list)
     * must not take the wheel of who is scrolling that page: met under the
     * pointer, it zoomed and the page stopped. There the wheel zooms only after
     * a click on the map, until the pointer leaves it or the page around takes
     * the focus back, or with Ctrl/Cmd held (a trackpad pinch is a wheel with
     * ctrlKey, so it zooms too). Otherwise the wheel scrolls the page and a hint
     * over the map says how to zoom.
     * The wheel zooms at once, as before, on bs_map.htm opened on its own, on
     * the full page maps (iframe.openkis-map-frame: there the map is the page)
     * and in fullscreen.
     * @returns {undefined}
     */
    initWheelGate: function ()
    {
        var embedded = true;
        try {
            embedded = window.self !== window.top;
        } catch (e) {
        }
        var fullPage = false;
        try {
            // null when the page around is of another site
            var frame = window.frameElement;
            fullPage = !!(frame && frame.classList && frame.classList.contains('openkis-map-frame'));
        } catch (e) {
        }
        this.wheelGated = embedded && !fullPage;
        if (!this.wheelGated)
        {
            return;
        }
        document.addEventListener('pointerdown', function () {
            OPS_Map.wheelArmed = true;
            OPS_Map.hideWheelHint();
        }, true);
        var disarm = function () {
            OPS_Map.wheelArmed = false;
        };
        // relatedTarget null: the pointer left the iframe, not just an element in it
        document.addEventListener('mouseout', function (e) {
            if (!e.relatedTarget)
            {
                disarm();
            }
        });
        window.addEventListener('blur', disarm);
    },
    /**
     * condition of the MouseWheelZoom of the desktop map
     * @param {ol.MapBrowserEvent} mapBrowserEvent
     * @returns {Boolean}
     */
    wheelZoomCondition: function (mapBrowserEvent)
    {
        var e = mapBrowserEvent.originalEvent;
        if (!this.wheelGated || this.wheelArmed || e.ctrlKey || e.metaKey
                || document.fullscreenElement || document.webkitFullscreenElement)
        {
            return true;
        }
        if (mapBrowserEvent.type === 'wheel')
        {
            this.showWheelHint();
        }
        return false;
    },
    showWheelHint: function ()
    {
        if (!this.wheelHint)
        {
            this.wheelHint = document.createElement('div');
            this.wheelHint.className = 'ops-wheel-hint';
            this.map.getViewport().appendChild(this.wheelHint);
        }
        var text = "Click on the map to zoom with the wheel, or use Ctrl + wheel";
        if (typeof (_i18n) === "function")
        {
            text = _i18n(text);
        }
        if (/Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent))
        {
            text = text.replace("Ctrl", "⌘");
        }
        this.wheelHint.textContent = text;
        this.wheelHint.classList.add('visible');
        clearTimeout(this.wheelHintTimer);
        this.wheelHintTimer = setTimeout(function () {
            OPS_Map.hideWheelHint();
        }, 1500);
    },
    hideWheelHint: function ()
    {
        clearTimeout(this.wheelHintTimer);
        if (this.wheelHint)
        {
            this.wheelHint.classList.remove('visible');
        }
    },
    /**
     * Touch taps not turned into a click by OpenLayers are handled here.
     * When the canvas under a finger is removed during a pinch (a layer
     * crossing its min/max zoom, openlayers#11465 #13746) its pointerup is
     * lost and OpenLayers stops emitting clicks: the map looks dead.
     * @returns {undefined}
     */
    addTapFallback: function ()
    {
        var viewport = this.map.getViewport();
        var tap = null;
        var inControl = function (e) {
            return e.target && e.target.closest && e.target.closest('.ol-overlaycontainer-stopevent');
        };
        viewport.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'mouse' || inControl(e))
            {
                tap = null;
                return;
            }
            var now = Date.now();
            if (tap && !tap.ended && now - tap.time < 1000)
            {
                // a second finger: pinch, not a tap
                tap.multi = true;
                return;
            }
            tap = {id: e.pointerId, x: e.clientX, y: e.clientY, time: now, multi: false, ended: false};
        });
        var end = function (e) {
            if (!tap || tap.id !== e.pointerId)
            {
                return;
            }
            var t = tap;
            t.ended = true;
            tap = null;
            if (e.type !== 'pointerup' || t.multi || inControl(e))
            {
                return;
            }
            var moved = Math.abs(e.clientX - t.x) + Math.abs(e.clientY - t.y);
            if (moved > 12 || Date.now() - t.time > 700)
            {
                return;
            }
            var pixel = OPS_Map.map.getEventPixel(e);
            var coordinate = OPS_Map.map.getCoordinateFromPixel(pixel);
            window.setTimeout(function () {
                if (OPS_Map.lastClickTime >= t.time || !coordinate)
                {
                    return; // OpenLayers handled it
                }
                OPS_Map.lastClickTime = Date.now();
                OPS_Map.onMapClick({
                    type: 'click',
                    pixel: pixel,
                    coordinate: coordinate,
                    originalEvent: e,
                    map: OPS_Map.map
                });
            }, 50);
        };
        viewport.addEventListener('pointerup', end);
        viewport.addEventListener('pointercancel', end);
    },
    popUp: function (coordinates, name, description)
    {
        if (!this.popupElement) {
            alert("popupElement not defined");
            return;
        }
        if (OPS_Map.popupOverlay != null) {
            //console.log(OPS_Map.popupClosingProcess);
            if (!this.popupClosingProcess)
            {
                this.popupClosingProcess = true;
                this.popUpClose();
            }

            window.setTimeout(function () {
                OPS_Map.popUp(coordinates, name, description);
            }, 10);
            return;
        }
        if (OPS_Map.popupOverlay == null)
        {
            OPS_Map.popupOverlay = new ol.Overlay({
                element: OPS_Map.popupElement,
                // custom.css keeps it above the map controls
                className: 'ol-overlay-container ol-selectable ops-popup-overlay',
                positioning: 'auto',
                stopEvent: true,
                offset: [0, -8]
            });
        }
        this.map.addOverlay(this.popupOverlay);
        coordinates = ol.proj.transform(coordinates, OPS_Map.inputProjectonEPSG, OPS_Map.viewMapProjectonEPSG);
        OPS_Map.popupOverlay.setPosition(coordinates);
        var htmlclosePopup = "<button type=\"button\" class=\"ops-popup-close\" aria-label=\"Chiudi\" title=\"Chiudi\" onclick=\"OPS_Map.popUpClose()\">&times;</button>";
        if (OPS_Map.popOver == null)
        {
            OPS_Map.popOver = $(OPS_Map.popupElement).popover({
                // below the point when there is no room above it
                'placement': 'auto top',
                'html': true,
                'content': description,
                'title': name + htmlclosePopup
            });
            $(OPS_Map.popupElement).on('shown.bs.popover', function () {
                OPS_Map.panPopupIntoView();
            });
            $(OPS_Map.popupElement).on('hidden.bs.popover', function () {
                OPS_Map.map.removeOverlay(OPS_Map.popupOverlay);
                OPS_Map.popupOverlay = null;
                OPS_Map.popOver = null;
                OPS_Map.popupClosingProcess = false;
            });
        }
        $('.popover-title').html(name + htmlclosePopup);
        $('.popover-content').html(description);
        $(OPS_Map.popupElement).popover('show');
    },
    /** Free space kept between the popup and the edges of the map, in CSS pixels */
    popupMargin: 10,
    /** Height kept free at the bottom of the map for the scale line and the export and fullscreen buttons */
    popupBottomReserve: 50,
    /**
     * Moves the map so that the open popup is inside the visible part of the
     * map: a popup opened near an edge was cut off, and on a phone its top
     * went under the toolbar.
     * @returns {undefined}
     */
    panPopupIntoView: function ()
    {
        var popover = $(OPS_Map.popupElement).data('bs.popover');
        var tip = popover && popover.$tip ? popover.$tip[0] : null;
        if (!tip || !tip.getBoundingClientRect)
        {
            return;
        }
        var p = tip.getBoundingClientRect();
        var m = OPS_Map.map.getViewport().getBoundingClientRect();
        // the visible part of the map: inside the window and below the
        // toolbar, which floats over the top of the map
        var margin = OPS_Map.popupMargin;
        var left = Math.max(m.left, 0) + margin;
        var right = Math.min(m.right, window.innerWidth) - margin;
        var top = Math.max(m.top, 0) + margin;
        var toolbar = document.querySelector('#navbar .mytoolbar');
        if (toolbar && toolbar.getBoundingClientRect)
        {
            top = Math.max(top, toolbar.getBoundingClientRect().bottom + margin);
        }
        var bottom = Math.min(m.bottom, window.innerHeight) - margin - OPS_Map.popupBottomReserve;
        var dx = 0;
        var dy = 0;
        if (p.width > right - left || p.left < left)
        {
            dx = p.left - left;
        } else if (p.right > right)
        {
            dx = p.right - right;
        }
        if (p.height > bottom - top || p.top < top)
        {
            dy = p.top - top;
        } else if (p.bottom > bottom)
        {
            dy = p.bottom - bottom;
        }
        if (dx === 0 && dy === 0)
        {
            return;
        }
        var view = OPS_Map.map.getView();
        var size = OPS_Map.map.getSize();
        var center = OPS_Map.map.getCoordinateFromPixel([size[0] / 2 + dx, size[1] / 2 + dy]);
        if (center)
        {
            view.animate({center: center, duration: 250});
        }
    },
    onMapClick: function (evt)
    {
        var coordinates = evt.coordinate;

        coordinates = ol.proj.transform(coordinates, OPS_Map.viewMapProjectonEPSG, OPS_Map.inputProjectonEPSG);
        var hits = OPS_Map.getFeaturesNearPixel(evt.pixel, OPS_Map.getHitTolerance(evt.originalEvent));
        if (hits.length === 1)
        {
            OPS_Map.showHitPopup(hits[0]);
        } else if (hits.length > 1)
        {
            OPS_Map.showHitListPopup(coordinates, hits);
        } else {
            if (OPS_Map.popOver)
            {
                OPS_Map.popUpClose();
            }
        }
    },

    /**
     * 
     * @param {type} lon
     * @param {type} lat
     * @returns {undefined}
     */
    setCenter: function (lon, lat) {
        lat = parseFloat(lat);
        lon = parseFloat(lon);
        OPS_Map.map.getView().setCenter(ol.proj.transform([lon, lat], OPS_Map.inputProjectonEPSG, this.view.getProjection()));
    },
    /**
     * 
     * @param {type} zoom
     * @returns {undefined}
     */
    setZoom: function (zoom) {
        //alert(zoom);

        OPS_Map.map.getView().setZoom(zoom);
    },
    /**
     * 
     * @param {type} origin
     * @returns {undefined}
     */
    addRasterLayer: function (origin, title, isBaselayer) {
        var layer = new ol.layer.Tile({
            source: new ol.source.TileJSON({
                crossOrigin: "Anonymous",
                url: origin,
                crossOrigin: '',
                name: title
            })
        });
        this.addLayer(layer, isBaselayer);
    },
    addLayer: function (layer, isBaselayer) {
        if (isBaselayer)
        {
            this.baselayers[this.id_baselayers] = layer;
            this.map.addLayer(this.baselayers[this.id_baselayers]);
            this.id_baselayers++;
        } else
        {
            this.layers[this.id_layers] = layer;
            this.map.addLayer(this.layers[this.id_layers]);
            this.id_layers++;

        }

    },
    /**
     * 
     * @param {type} lon
     * @param {type} lat
     * @param {type} img
     * @returns {undefined}
     */
    addMarker: function (lon, lat, img, size) {
        OPS_Map.markers[OPS_Map.id_markers] = new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.transform(
                    [lon, lat],
                    OPS_Map.inputProjectonEPSG,
                    this.view.getProjection()
                    ))
        });
        var iconStyle = new ol.style.Style({
            image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                anchor: [0.5, 0.5],
                size: [size, size],
                anchorXUnits: 'fraction',
                anchorYUnits: 'fraction',
                src: img
            }))
        });
        OPS_Map.markers[OPS_Map.id_markers].setStyle(iconStyle);
        var vectorSource = new ol.source.Vector({
            crossOrigin: "Anonymous",
            features: [OPS_Map.markers[OPS_Map.id_markers]]
        });

        var vectorLayer = new ol.layer.Vector({
            source: vectorSource
                    // name: "Info window"
        });
        OPS_Map.map.addLayer(vectorLayer);
        OPS_Map.markersLayer[OPS_Map.id_markers] = vectorLayer;
        OPS_Map.markers[OPS_Map.id_markers].lon = lon;
        OPS_Map.markers[OPS_Map.id_markers].lat = lat;
//        this.markers[this.id_markers] = iconFeature;
        this.id_markers++;
    },
    /**
     * 
     * @param {type} mode
     * @returns {undefined}
     */
    SwitchMode: function (mode)
    {
        //console.log("new:"+mode+"="+"old:"+OPS_Map.mode);
        if (OPS_Map.mode.toString() != mode.toString())
        {
            console.log("add class bsactive");
            OPS_Map.mode = mode;
            $("#mode_" + mode).addClass("bsactive");
            return;
        }
        if (mode == OPS_Map.mode)
        {
            //console.log("remove "+"#mode_" + mode);
            $("#mode_" + mode).removeClass("bsactive");
            OPS_Map.mode = "";
        }
    },
    /**
     * 
     * @param {type} lon
     * @param {type} lat
     * @param {type} img
     * @param {type} name
     * @param {type} description
     * @returns {undefined}+
     */
    addMarkerPopup: function (lon, lat, img, name, description, size)
    {
        if (size === undefined)
        {
            size = 0.2;
        }
        OPS_Map.markers[OPS_Map.id_markers] = new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.transform(
                    [lon, lat],
                    OPS_Map.inputProjectonEPSG,
                    this.view.getProjection()
                    )),
            name: name,
            description: description
        });
        var iconStyle = new ol.style.Style({
            image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                anchor: [0.5, 0.5],
                scale: size,
                anchorXUnits: 'fraction',
                anchorYUnits: 'fraction',
                src: img
            }))
        });
        OPS_Map.markers[OPS_Map.id_markers].setStyle(iconStyle);

        var vectorSource = new ol.source.Vector({
            crossOrigin: "Anonymous",
            features: [OPS_Map.markers[OPS_Map.id_markers]]
        });

        var vectorLayer = new ol.layer.Vector({
            source: vectorSource
        });
        OPS_Map.map.addLayer(vectorLayer);
        OPS_Map.markersLayer[OPS_Map.id_markers] = vectorLayer;
        OPS_Map.markers[OPS_Map.id_markers].lon = lon;
        OPS_Map.markers[OPS_Map.id_markers].lat = lat;
//        this.markers[this.id_markers] = iconFeature;
        this.id_markers++;
    },
    /**
     * 
     * @param {type} title
     * @returns {undefined}
     */
    addOSMLayer: function (title, url, isBaselayer, visible, crossOrigin, maxZoom) {
        // crossOrigin=null for tile servers that do not send CORS headers
        // (e.g. OpenTopoMap), otherwise the browser discards the tiles
        crossOrigin = (typeof crossOrigin !== 'undefined') ? crossOrigin : "Anonymous";
        // maxZoom: last zoom level served, above it tiles are upscaled
        maxZoom = (typeof maxZoom !== 'undefined') ? maxZoom : 19;
        var layer = new ol.layer.Tile({name: title,
            visible: visible,

            source: new ol.source.OSM({
                maxZoom: maxZoom,
                crossOrigin: crossOrigin,
                url: url
            })});
        this.addLayer(layer, isBaselayer);
    },

    addJsLayer: function (path)
    {
        this.toinclude.push(path);
    },
    /**
     * 
     * @param {type} title
     * @param {type} path
     * @param {type} isBaselayer
     * @param {type} visible
     * @param {type} showPointNames
     * @param {type} searchable
     * @param {type} layergroup
     * @returns {undefined}
     */
    // false for a layer of this site's map data endpoints (openkis_kml.php,
    // openkis_kml_history.php) that the current user may not get: they would
    // answer 403. OPENKIS_MAP_ACCESS comes from openkis_map_access.js.php;
    // without it, or for a section it does not list, the layer is loaded.
    canLoadLayer: function (path)
    {
        var m = /^(openkis_kml|openkis_kml_history)\.php(\?.*)?$/.exec(String(path));
        if (!m || typeof OPENKIS_MAP_ACCESS !== "object" || OPENKIS_MAP_ACCESS === null)
        {
            return true;
        }
        var mod = /[?&]mod=([^&#]*)/.exec(m[2] || "");
        mod = (mod && mod[1] !== "") ? decodeURIComponent(mod[1]) : "caves";
        if (!OPENKIS_MAP_ACCESS.hasOwnProperty(mod))
        {
            return true;
        }
        var access = OPENKIS_MAP_ACCESS[mod];
        // the coordinate history has no public part
        return m[1] === "openkis_kml_history" ? access === "all" : access !== "";
    },
    addKmlLayer: function (title, path, isBaselayer, visible, showPointNames, searchable, layergroup)
    {
        if (!OPS_Map.canLoadLayer(path))
        {
            return;
        }
        var isVisible = visible;
        var _showPointNames;
        if (typeof visible === 'string' || visible instanceof String)
        {
            visible = true;
        }
        if (showPointNames === undefined)
        {
            showPointNames = false;
            _showPointNames = true;
        }
        if (showPointNames === "auto")
        {
            showPointNames = false;
            _showPointNames = "auto";
        }
        else
        {
            _showPointNames = showPointNames;
        }
        
        if (searchable === undefined)
        {
            searchable = true;
        }

        if (layergroup === undefined)
        {
            layergroup = false;
        }
        //---------------------------kml----------------------------------->
        if (layergroup)
        {
            if (OPS_Map.layergroups == undefined)
            {
                OPS_Map.layergroups =  [];
            }
            if (OPS_Map.layergroups[layergroup] == undefined)
            {
                OPS_Map.layergroups[layergroup] = new ol.layer.Group({
                    layers: [],
                    name: layergroup,
                    visible: visible
                });
            }
            var layer = new ol.layer.Vector({
                name: title,
                //visible: visible,
                source: OPS_Map.scaleIconsWithZoom(new ol.source.Vector({
                    crossOrigin: "Anonymous",
                    url: path,
                    projection: this.view.getProjection(),
                    format: new ol.format.KML(
                            {
                                writeStyles: true,
                                showPointNames: showPointNames
                            })
                }))
            });


            //var zindex=OPS_Map.kmllayerid+1
            layer.setZIndex(10);
            layer.showLabels = _showPointNames;
            layer.visible = visible;
            try{
                console.log("layergroup "+layergroup);
                OPS_Map.layergroups[layergroup].getLayers().push(layer);

            }
            catch(e){
                console.log(OPS_Map.layergroups[layergroup].getLayers());
                console.log(e);
            };
// this.map.get("markerGroup").getLayers() should return the same array
            OPS_Map.map.removeLayer(OPS_Map.layergroups[layergroup]);
            OPS_Map.map.addLayer(OPS_Map.layergroups[layergroup]);

        } else
        {
            var layer = new ol.layer.Vector({
                declutter: false,
                name: title,
                visible: visible,
                source: OPS_Map.scaleIconsWithZoom(new ol.source.Vector({
                    crossOrigin: "Anonymous",
                    url: path,
                    projection: this.view.getProjection(),
                    format: new ol.format.KML(
                            {
                                writeStyles: true,
                                showPointNames: showPointNames
                            })
                }))
            });
            //var zindex=OPS_Map.kmllayerid+1
            layer.setZIndex(10);
            layer.showLabels = _showPointNames;
            layer.isVisible = isVisible;
            this.kmllayers[OPS_Map.kmllayerid] = layer;
            this.kmllayers[OPS_Map.kmllayerid].path = path;
            this.kmllayers[OPS_Map.kmllayerid].searchable = searchable;
            this.kmllayers[OPS_Map.kmllayerid].showPointNames = showPointNames;
            OPS_Map.kmllayerid++;
            this.addLayer(layer, isBaselayer);
            OPS_Map.getKmlPoints();
        }
        //---------------------------kml-----------------------------------<
    },
    /**
     * Icon growth with the zoom: from iconZoomMin each zoom level adds
     * iconZoomStep to the KML scale, up to iconZoomMaxFactor times and never
     * beyond iconZoomMaxPx pixels.
     */
    iconZoomMin: 13,
    iconZoomStep: 0.3,
    iconZoomMaxFactor: 3,
    iconZoomMaxPx: 40,
    /**
     * Wraps the KML style of every point added to the source so that its
     * icon grows with the zoom (the KML icons have a fixed size in pixels
     * and are too small on close zoom levels).
     * @param {ol.source.Vector} source
     * @returns {ol.source.Vector}
     */
    scaleIconsWithZoom: function (source)
    {
        source.on('addfeature', function (event) {
            var feature = event.feature;
            var styleFunction = feature.getStyleFunction();
            var geometry = feature.getGeometry();
            if (!styleFunction || !geometry || geometry.getType() !== 'Point')
            {
                return;
            }
            feature.setStyle(function (feature, resolution) {
                var styles = styleFunction(feature, resolution);
                if (OPS_Map.setIconZoomScale(styles, resolution))
                {
                    // the label offset depends on the icon scale: build it again
                    styles = styleFunction(feature, resolution);
                }
                return styles;
            });
        });
        return source;
    },
    /**
     * Sets the scale of the icons in the styles for the given resolution.
     * @param {Array|ol.style.Style} styles
     * @param {Number} resolution map units per pixel
     * @returns {Boolean} true if a scale has changed
     */
    setIconZoomScale: function (styles, resolution)
    {
        var zoom = OPS_Map.map.getView().getZoomForResolution(resolution);
        var factor = Math.min(1 + Math.max(0, zoom - OPS_Map.iconZoomMin) * OPS_Map.iconZoomStep, OPS_Map.iconZoomMaxFactor);
        var changed = false;
        styles = Array.isArray(styles) ? styles : [styles];
        for (var i = 0; i < styles.length; i++)
        {
            var image = styles[i] ? styles[i].getImage() : null;
            if (!(image instanceof ol.style.Icon) || typeof image.getScale() !== 'number')
            {
                continue;
            }
            var scale = image.getScale();
            // the KML format rescales the icon when the image is loaded:
            // a scale not set here becomes the new base
            if (image.opsZoomScale !== scale)
            {
                image.opsBaseScale = scale;
            }
            var newScale = image.opsBaseScale * factor;
            var size = image.getSize();
            if (size)
            {
                newScale = Math.min(newScale, Math.max(OPS_Map.iconZoomMaxPx / Math.max(size[0], size[1]), image.opsBaseScale));
            }
            if (newScale !== scale)
            {
                image.setScale(newScale);
                changed = true;
            }
            image.opsZoomScale = newScale;
        }
        return changed;
    },
    /**
     * Colours of the tracer test arrows (openkis_tracings.php "kind").
     */
    tracingColors: {
        water: "#1e9be9",
        air_summer: "#0b2a80",
        air_winter: "#f07d00"
    },
    /**
     * Style of a tracer test: a line with a white casing, a dot on the
     * injection point and an arrow head just before the detection point (so
     * it does not cover the cave icon).
     * @param {ol.Feature} feature
     * @param {Number} resolution map units per pixel
     * @returns {Array} ol.style.Style
     */
    tracingStyle: function (feature, resolution)
    {
        var color = OPS_Map.tracingColors[feature.get("kind")] || OPS_Map.tracingColors.water;
        var coords = feature.getGeometry().getCoordinates();
        var start = coords[0];
        var end = coords[coords.length - 1];
        var styles = [
            new ol.style.Style({stroke: new ol.style.Stroke({color: "rgba(255,255,255,0.85)", width: 6})}),
            new ol.style.Style({stroke: new ol.style.Stroke({color: color, width: 3})}),
            new ol.style.Style({
                geometry: new ol.geom.Point(start),
                image: new ol.style.Circle({radius: 4, fill: new ol.style.Fill({color: color}), stroke: new ol.style.Stroke({color: "#ffffff", width: 1.5})})
            })
        ];
        var dx = end[0] - start[0];
        var dy = end[1] - start[1];
        var length = Math.sqrt(dx * dx + dy * dy);
        if (length > 0)
        {
            var radius = 9;
            // centre of the triangle: its tip lands 6 px before the end point
            var back = (6 + radius) * resolution;
            var head = [end[0] - dx / length * back, end[1] - dy / length * back];
            styles.push(new ol.style.Style({
                geometry: new ol.geom.Point(head),
                image: new ol.style.RegularShape({
                    points: 3,
                    radius: radius,
                    rotation: Math.atan2(dx, dy),
                    rotateWithView: true,
                    fill: new ol.style.Fill({color: color}),
                    stroke: new ol.style.Stroke({color: "#ffffff", width: 1.5})
                })
            }));
        }
        return styles;
    },
    /**
     * Legend of the tracer test arrows.
     * @param {Object} labels "legend" member of openkis_tracings.php
     * @returns {String} html
     */
    tracingLegendHtml: function (labels)
    {
        var esc = function (text) {
            return $("<div>").text(text === undefined ? "" : text).html();
        };
        var sample = function (color) {
            return "<svg width=\"36\" height=\"10\" style=\"vertical-align:middle;margin-right:4px\">" +
                    "<line x1=\"1\" y1=\"5\" x2=\"27\" y2=\"5\" stroke=\"" + color + "\" stroke-width=\"3\" />" +
                    "<polygon points=\"26,1 35,5 26,9\" fill=\"" + color + "\" /></svg>";
        };
        var c = OPS_Map.tracingColors;
        return "<b>" + esc(labels.title) + "</b><br />" +
                sample(c.water) + esc(labels.water) + "<br />" +
                sample(c.air_summer) + esc(labels.air_summer) + "<br />" +
                sample(c.air_winter) + esc(labels.air_winter);
    },
    /**
     * Tracer tests layer: arrows from the injection to the detection point,
     * read from openkis_tracings.php (GeoJSON). The legend is shown while the
     * layer is visible and has arrows.
     * @param {String} title layer name in the layer switcher
     * @param {String} path url of openkis_tracings.php with its filters
     * @param {Boolean} visible
     * @param {Boolean} fit zoom the view on the arrows once they are loaded
     * @returns {ol.layer.Vector}
     */
    addTracingsLayer: function (title, path, visible, fit)
    {
        var source = new ol.source.Vector();
        var layer = new ol.layer.Vector({
            name: title,
            visible: visible,
            source: source,
            style: OPS_Map.tracingStyle
        });
        // below the cave icons, which stay clickable
        layer.setZIndex(9);
        this.addLayer(layer, false);
        // fixed on the page like the other bottom buttons: the map viewport
        // is taller than the window (it starts below the toolbar)
        var legend = document.createElement("div");
        legend.className = "ol-unselectable ol-control";
        legend.style.cssText = "position:fixed;left:8px;bottom:40px;z-index:6;padding:4px 8px;background:rgba(255,255,255,0.9);color:#222;font-size:12px;line-height:18px;display:none";
        document.body.appendChild(legend);
        var updateLegend = function () {
            legend.style.display = (layer.getVisible() && source.getFeatures().length > 0) ? "block" : "none";
        };
        layer.on("change:visible", updateLegend);
        $.getJSON(path, function (data) {
            var features = new ol.format.GeoJSON().readFeatures(data, {
                dataProjection: OPS_Map.inputProjectonEPSG,
                featureProjection: OPS_Map.view.getProjection()
            });
            source.addFeatures(features);
            if (data.legend)
            {
                legend.innerHTML = OPS_Map.tracingLegendHtml(data.legend);
            }
            updateLegend();
            if (fit && features.length > 0)
            {
                OPS_Map.view.fit(source.getExtent(), {padding: [60, 60, 60, 60], maxZoom: 17});
            }
        });
        return layer;
    },
    /**
     * Tracer tests layer configured from the page url: hidden (all the
     * tracings, can be switched on) unless the url asks for them, in which
     * case it is shown on a light base layer and the view is framed on the
     * arrows:
     *   tracings=1            all the tracings
     *   tracing=id            one tracing
     *   tracing_cave=code     the tracings starting or ending in a cave
     *   tracing_spring=code   the tracings ending in a spring
     *   tracing_uncatalogued=code the tracings starting or ending in an
     *                         uncatalogued cavity
     * @param {String} title layer name in the layer switcher
     * @returns {ol.layer.Vector}
     */
    addTracingsLayerFromUrl: function (title)
    {
        var path = "openkis_tracings.php";
        var tracing = getUrlVar("tracing", "");
        var cave = getUrlVar("tracing_cave", "");
        var spring = getUrlVar("tracing_spring", "");
        var uncatalogued = getUrlVar("tracing_uncatalogued", "");
        if (tracing !== "")
        {
            path += "?id=" + encodeURIComponent(tracing);
        } else if (cave !== "")
        {
            path += "?cave=" + encodeURIComponent(cave);
        } else if (spring !== "")
        {
            path += "?spring=" + encodeURIComponent(spring);
        } else if (uncatalogued !== "")
        {
            path += "?uncatalogued=" + encodeURIComponent(uncatalogued);
        }
        var shown = (getUrlVar("tracings", "") !== "" || path !== "openkis_tracings.php");
        if (shown)
        {
            // the arrows read better on a light background
            OPS_Map.defaultBaseLayer = "light";
        }
        return this.addTracingsLayer(title, path, shown, shown);
    },
    /**
     * Colours of the temperature points (openkis_temperatures.php "kind":
     * classes of the mean air temperature of the point).
     */
    temperatureColors: {
        cold: "#313695",
        cool: "#4575b4",
        mild: "#74add1",
        warm: "#fdae61",
        hot: "#d73027",
        unknown: "#999999"
    },
    /**
     * Style of a measurement point: a disc coloured by its mean temperature,
     * with the value written next to it.
     * @param {ol.Feature} feature
     * @returns {ol.style.Style}
     */
    temperatureStyle: function (feature)
    {
        var color = OPS_Map.temperatureColors[feature.get("kind")] || OPS_Map.temperatureColors.unknown;
        var value = feature.get("value");
        var label = (value === "" || value === null || value === undefined) ? "" : String(value).replace(".", ",") + " °C";
        return new ol.style.Style({
            image: new ol.style.Circle({
                radius: 8,
                fill: new ol.style.Fill({color: color}),
                stroke: new ol.style.Stroke({color: "#ffffff", width: 2})
            }),
            text: new ol.style.Text({
                text: label,
                font: "bold 11px sans-serif",
                offsetX: 12,
                textAlign: "left",
                fill: new ol.style.Fill({color: "#222222"}),
                stroke: new ol.style.Stroke({color: "#ffffff", width: 3})
            })
        });
    },
    /**
     * Legend of the temperature points.
     * @param {Object} labels "legend" member of openkis_temperatures.php
     * @returns {String} html
     */
    temperatureLegendHtml: function (labels)
    {
        var esc = function (text) {
            return $("<div>").text(text === undefined ? "" : text).html();
        };
        var html = "<b>" + esc(labels.title) + "</b><br />";
        var classes = ["cold", "cool", "mild", "warm", "hot", "unknown"];
        for (var i = 0; i < classes.length; i++)
        {
            html += "<span style=\"display:inline-block;width:12px;height:12px;border-radius:6px;border:1px solid #fff;background:" + OPS_Map.temperatureColors[classes[i]] + ";vertical-align:middle;margin-right:4px\"></span>" + esc(labels[classes[i]]) + "<br />";
        }
        return html;
    },
    /**
     * Temperatures layer: the measurement points of openkis_temperatures.php
     * (GeoJSON), coloured by their mean temperature, with a popup listing
     * the measurements. The legend is shown while the layer is visible and
     * has points.
     * @param {String} title layer name in the layer switcher
     * @param {String} path url of openkis_temperatures.php with its filters
     * @param {Boolean} visible
     * @param {Boolean} fit zoom the view on the points once they are loaded
     * @returns {ol.layer.Vector}
     */
    addTemperaturesLayer: function (title, path, visible, fit)
    {
        var source = new ol.source.Vector();
        var layer = new ol.layer.Vector({
            name: title,
            visible: visible,
            source: source,
            style: OPS_Map.temperatureStyle
        });
        // above the cave icons: the point carries the measurements of the cave
        layer.setZIndex(11);
        this.addLayer(layer, false);
        var legend = document.createElement("div");
        legend.className = "ol-unselectable ol-control";
        legend.style.cssText = "position:fixed;left:8px;bottom:40px;z-index:6;padding:4px 8px;background:rgba(255,255,255,0.9);color:#222;font-size:12px;line-height:18px;display:none";
        document.body.appendChild(legend);
        var updateLegend = function () {
            legend.style.display = (layer.getVisible() && source.getFeatures().length > 0) ? "block" : "none";
        };
        layer.on("change:visible", updateLegend);
        $.getJSON(path, function (data) {
            var features = new ol.format.GeoJSON().readFeatures(data, {
                dataProjection: OPS_Map.inputProjectonEPSG,
                featureProjection: OPS_Map.view.getProjection()
            });
            source.addFeatures(features);
            if (data.legend)
            {
                legend.innerHTML = OPS_Map.temperatureLegendHtml(data.legend);
            }
            updateLegend();
            if (fit && features.length > 0)
            {
                OPS_Map.view.fit(source.getExtent(), {padding: [60, 60, 60, 60], maxZoom: 15});
            }
        });
        return layer;
    },
    /**
     * Temperatures layer configured from the page url: hidden (all the
     * points, can be switched on) unless the url asks for them, in which
     * case it is shown on a light base layer and the view is framed on the
     * points:
     *   temperatures=1          all the measurement points
     *   temperature=id          the point of one measurement
     *   temperature_cave=code   the measurements of a cave
     * @param {String} title layer name in the layer switcher
     * @returns {ol.layer.Vector}
     */
    addTemperaturesLayerFromUrl: function (title)
    {
        var path = "openkis_temperatures.php";
        var temperature = getUrlVar("temperature", "");
        var cave = getUrlVar("temperature_cave", "");
        if (temperature !== "")
        {
            path += "?id=" + encodeURIComponent(temperature);
        } else if (cave !== "")
        {
            path += "?cave=" + encodeURIComponent(cave);
        }
        var shown = (getUrlVar("temperatures", "") !== "" || path !== "openkis_temperatures.php");
        if (shown)
        {
            // the coloured points read better on a light background
            OPS_Map.defaultBaseLayer = "light";
        }
        return this.addTemperaturesLayer(title, path, shown, shown && temperature === "");
    },
    /**
     * Colours of the fauna points (openkis_fauna.php) by ecological category:
     * the category of the species most bound to the underground among those
     * observed in the cave, from the troglobites to the trogloxenes.
     */
    faunaCategoryColors: {
        obligate: "#5e3c99",
        eutroglophile: "#2b6cb0",
        troglophile: "#2a9d8f",
        trogloxene: "#d9a521",
        unknown: "#9e9e9e"
    },
    /**
     * Colours of the fauna points by taxonomic class (the most frequent
     * class among the species of the cave).
     */
    faunaClassColors: {
        mammalia: "#8c510a",
        amphibia: "#1b9e77",
        reptilia: "#7a9a01",
        insecta: "#e6550d",
        arachnida: "#c51b7d",
        crustacea: "#3182bd",
        gastropoda: "#e3b505",
        other: "#7570b3",
        unknown: "#9e9e9e"
    },
    /**
     * Colour mode of the fauna points: "category" or "class" (url parameter
     * fauna_mode, switched from the legend).
     */
    faunaMode: "category",
    /**
     * Keys hidden from the legend, by mode: a point is drawn when at least
     * one of its species has a visible key, and takes its colour.
     */
    faunaHidden: {category: {}, class: {}},
    /**
     * Legend of openkis_fauna.php (labels of the categories and classes in
     * order), once loaded.
     */
    faunaLegend: null,
    faunaLayer: null,
    /**
     * Most icons written over a fauna point, the others counted as "+n".
     */
    faunaIconsMax: 4,
    /**
     * Icon of each group (legend.groups of openkis_FaunaGroups()) checked once:
     * the fallback character replaces an emoji the browser cannot draw.
     */
    faunaIcons: null,
    /**
     * Name of the icon layer in the layer switcher by map language (the language
     * files of the map are loaded after the layers are created).
     */
    faunaIconsNames: {it: "icone degli organismi", en: "organism icons"},
    // letters and digits from the text font, the emoji from the colour fonts
    faunaEmojiFonts: "sans-serif,\"Apple Color Emoji\",\"Segoe UI Emoji\",\"Noto Color Emoji\"",
    /**
     * Colours of the current mode.
     * @returns {Object}
     */
    faunaColors: function ()
    {
        return OPS_Map.faunaMode === "class" ? OPS_Map.faunaClassColors : OPS_Map.faunaCategoryColors;
    },
    /**
     * Keys of the current mode in legend order.
     * @returns {Array}
     */
    faunaKeys: function ()
    {
        var labels = OPS_Map.faunaLegend ? OPS_Map.faunaLegend[OPS_Map.faunaMode === "class" ? "classes" : "categories"] : null;
        return labels ? Object.keys(labels) : Object.keys(OPS_Map.faunaColors());
    },
    /**
     * Visible key of a fauna point in the current mode (the first in legend
     * order) and the number of its species with a visible key.
     * @param {ol.Feature} feature
     * @returns {Object} {key, count}, key "" when every key is hidden
     */
    faunaVisible: function (feature)
    {
        var counts = feature.get(OPS_Map.faunaMode === "class" ? "classes" : "categories") || {};
        var hidden = OPS_Map.faunaHidden[OPS_Map.faunaMode] || {};
        var keys = OPS_Map.faunaKeys();
        var first = "";
        var count = 0;
        for (var i = 0; i < keys.length; i++)
        {
            if (counts[keys[i]] && !hidden[keys[i]])
            {
                if (first === "")
                {
                    first = keys[i];
                }
                count += counts[keys[i]];
            }
        }
        return {key: first, count: count};
    },
    /**
     * Style of a fauna point: colour of its visible key, radius growing with
     * the number of species (6 px for one, 14 px for seven or more), the
     * number written beside it when there is more than one species.
     * @param {ol.Feature} feature
     * @returns {ol.style.Style|null}
     */
    faunaStyle: function (feature)
    {
        var visible = OPS_Map.faunaVisible(feature);
        if (visible.key === "")
        {
            return null;
        }
        var color = OPS_Map.faunaColors()[visible.key] || "#9e9e9e";
        var n = visible.count;
        var radius = OPS_Map.faunaRadius(n);
        var style = {
            image: new ol.style.Circle({
                radius: radius,
                fill: new ol.style.Fill({color: color}),
                stroke: new ol.style.Stroke({color: "#ffffff", width: 2})
            })
        };
        if (n > 1)
        {
            style.text = new ol.style.Text({
                text: String(n),
                font: "bold 11px sans-serif",
                offsetX: radius + 4,
                textAlign: "left",
                fill: new ol.style.Fill({color: "#222222"}),
                stroke: new ol.style.Stroke({color: "#ffffff", width: 3})
            });
        }
        return new ol.style.Style(style);
    },
    /**
     * Radius of a fauna point: 6 px for one species, 14 px for seven or more.
     * @param {Number} n number of species
     * @returns {Number}
     */
    faunaRadius: function (n)
    {
        return 6 + Math.min(n - 1, 6) * 1.3;
    },
    /**
     * Icons of the groups (emoji, or the fallback character when the browser
     * draws the emoji as an empty box: colour emoji fonts paint coloured
     * pixels, the missing glyph box does not).
     * @returns {Object} group key => character
     */
    faunaGroupIcons: function ()
    {
        if (OPS_Map.faunaIcons)
        {
            return OPS_Map.faunaIcons;
        }
        var groups = (OPS_Map.faunaLegend && OPS_Map.faunaLegend.groups) || {};
        var icons = {};
        var canvas = document.createElement("canvas");
        canvas.width = canvas.height = 24;
        var ctx = canvas.getContext("2d");
        for (var key in groups)
        {
            icons[key] = groups[key].icon;
            if (!groups[key].fallback || !ctx)
            {
                continue;
            }
            ctx.clearRect(0, 0, 24, 24);
            ctx.font = "20px " + OPS_Map.faunaEmojiFonts;
            ctx.textBaseline = "top";
            ctx.fillStyle = "#000000";
            ctx.fillText(groups[key].icon, 0, 0);
            var pixels = ctx.getImageData(0, 0, 24, 24).data;
            var coloured = false;
            for (var i = 0; i < pixels.length && !coloured; i += 4)
            {
                coloured = pixels[i + 3] > 0 && (pixels[i] !== pixels[i + 1] || pixels[i + 1] !== pixels[i + 2]);
            }
            if (!coloured)
            {
                icons[key] = groups[key].fallback;
            }
        }
        OPS_Map.faunaIcons = icons;
        return icons;
    },
    /**
     * Style of the icons of a fauna point: one icon per group of its visible
     * species (legend order, at most faunaIconsMax, then "+n") on a label
     * above the point. The icon layer declutters: a label that would cover
     * another one is left out until the map is zoomed in, the point stays.
     * @param {ol.Feature} feature
     * @returns {ol.style.Style|null}
     */
    faunaIconStyle: function (feature)
    {
        var visible = OPS_Map.faunaVisible(feature);
        if (visible.key === "" || !OPS_Map.faunaLayer.getVisible())
        {
            return null;
        }
        var mode = OPS_Map.faunaMode === "class" ? "class" : "category";
        var hidden = OPS_Map.faunaHidden[OPS_Map.faunaMode] || {};
        var species = feature.get("species") || [];
        var present = {};
        for (var i = 0; i < species.length; i++)
        {
            if (!hidden[species[i][mode]])
            {
                present[species[i].group] = true;
            }
        }
        var icons = OPS_Map.faunaGroupIcons();
        var text = "";
        var shown = 0;
        var more = 0;
        for (var key in icons)
        {
            if (present[key])
            {
                if (shown < OPS_Map.faunaIconsMax)
                {
                    text += icons[key];
                    shown++;
                } else
                {
                    more++;
                }
            }
        }
        if (text === "")
        {
            return null;
        }
        if (more > 0)
        {
            text += " +" + more;
        }
        return new ol.style.Style({
            text: new ol.style.Text({
                text: text,
                font: "15px " + OPS_Map.faunaEmojiFonts,
                offsetY: -(OPS_Map.faunaRadius(visible.count) + 13),
                fill: new ol.style.Fill({color: "#222222"}),
                backgroundFill: new ol.style.Fill({color: "rgba(255,255,255,0.9)"}),
                backgroundStroke: new ol.style.Stroke({color: "#bbbbbb", width: 1}),
                padding: [2, 3, 0, 3]
            })
        });
    },
    /**
     * Legend of the fauna points: the mode switch, one line per key with a
     * checkbox that hides its points and the number of places, the note on
     * the size.
     * @param {Object} legend "legend" member of openkis_fauna.php
     * @param {Array} features the loaded points
     * @returns {String} html
     */
    faunaLegendHtml: function (legend, features)
    {
        var esc = function (text) {
            return $("<div>").text(text === undefined || text === null ? "" : text).html();
        };
        var mode = OPS_Map.faunaMode;
        var labels = legend[mode === "class" ? "classes" : "categories"] || {};
        var colors = OPS_Map.faunaColors();
        var hidden = OPS_Map.faunaHidden[mode] || {};
        var counts = {};
        for (var i = 0; i < features.length; i++)
        {
            var keys = features[i].get(mode === "class" ? "classes" : "categories") || {};
            for (var k in keys)
            {
                counts[k] = (counts[k] || 0) + 1;
            }
        }
        var html = "<b>" + esc(legend.title) + "</b><br />";
        var modes = [["category", legend.mode_category], ["class", legend.mode_class]];
        for (var m = 0; m < modes.length; m++)
        {
            var current = (modes[m][0] === mode);
            html += (m > 0 ? " | " : "") + (current ? "<b>" : "<a href=\"#\" data-fauna-mode=\"" + modes[m][0] + "\" style=\"color:#2b6cb0\">") + esc(modes[m][1]) + (current ? "</b>" : "</a>");
        }
        html += "<br />";
        for (var key in labels)
        {
            if (!counts[key])
            {
                continue;
            }
            html += "<label style=\"display:block;margin:0;font-weight:normal;cursor:pointer\"><input type=\"checkbox\" data-fauna-key=\"" + esc(key) + "\"" + (hidden[key] ? "" : " checked=\"checked\"") + " style=\"vertical-align:middle;margin:0 4px 0 0\" />";
            html += "<span style=\"display:inline-block;width:12px;height:12px;border-radius:6px;border:1px solid #fff;box-shadow:0 0 0 1px #bbb;background:" + colors[key] + ";vertical-align:middle;margin-right:4px\"></span>" + esc(labels[key]) + " <span style=\"color:#777\">(" + counts[key] + ")</span></label>";
        }
        var icons = OPS_Map.faunaGroupIcons();
        var groupCounts = {};
        for (var f = 0; f < features.length; f++)
        {
            var groups = features[f].get("groups") || {};
            for (var g in groups)
            {
                groupCounts[g] = (groupCounts[g] || 0) + 1;
            }
        }
        var summary = "";
        var items = "";
        for (var group in legend.groups || {})
        {
            if (groupCounts[group])
            {
                summary += icons[group];
                items += "<span style=\"display:inline-block;margin-right:8px;white-space:nowrap\">" + icons[group] + " " + esc(legend.groups[group].label) + " <span style=\"color:#777\">(" + groupCounts[group] + ")</span></span>";
            }
        }
        if (summary !== "")
        {
            html += "<details style=\"margin:2px 0\"><summary style=\"cursor:pointer\">" + esc(legend.groups_title) + " " + summary + "</summary>" + items + "</details>";
        }
        html += "<small style=\"color:#777\">" + esc(legend.size) + "</small>";
        return html;
    },
    /**
     * Fauna layer: the caves and artificial cavities with fauna sightings
     * of openkis_fauna.php (GeoJSON), coloured by ecological category or by
     * class of their species, with a popup listing the species. The legend
     * (shown while the layer is visible and has points) switches the mode
     * and hides the keys.
     * @param {String} title layer name in the layer switcher
     * @param {String} path url of openkis_fauna.php with its filters
     * @param {Boolean} visible
     * @param {Boolean} fit frame the view on the points once loaded
     * @returns {ol.layer.Vector}
     */
    addFaunaLayer: function (title, path, visible, fit)
    {
        var source = new ol.source.Vector();
        var layer = new ol.layer.Vector({
            name: title,
            visible: visible,
            source: source,
            style: OPS_Map.faunaStyle
        });
        // above the cave icons: the point carries the species of the cave
        layer.setZIndex(12);
        this.addLayer(layer, false);
        OPS_Map.faunaLayer = layer;
        // icons of the groups on their own decluttered layer, listed in the layer
        // switcher so that they can be switched off; switched on and off with the
        // points, drawn only while the points are visible
        var iconsName = title + ": " + (OPS_Map.faunaIconsNames[OPS_Map.lang] || OPS_Map.faunaIconsNames.en);
        var iconLayer = new ol.layer.Vector({
            name: iconsName,
            visible: visible,
            source: source,
            declutter: true,
            style: OPS_Map.faunaIconStyle,
            // the places with more species first: the first label drawn keeps its place
            renderOrder: function (a, b) {
                return b.get("nspecies") - a.get("nspecies");
            }
        });
        iconLayer.setZIndex(13);
        this.addLayer(iconLayer, false);
        var redraw = function () {
            layer.changed();
            iconLayer.changed();
        };
        // colours of the dots in the popups (one rule per key and mode)
        var css = ".openkis-fauna-dot{display:inline-block;width:10px;height:10px;border-radius:5px;border:1px solid #fff;box-shadow:0 0 0 1px #bbb;background:#9e9e9e;vertical-align:middle}";
        for (var c in OPS_Map.faunaCategoryColors)
        {
            css += ".openkis-fauna-dot[data-category=\"" + c + "\"]{background:" + OPS_Map.faunaCategoryColors[c] + "}";
        }
        for (var k in OPS_Map.faunaClassColors)
        {
            css += ".openkis-fauna-mode-class .openkis-fauna-dot[data-class=\"" + k + "\"]{background:" + OPS_Map.faunaClassColors[k] + "}";
        }
        var style = document.createElement("style");
        style.appendChild(document.createTextNode(css));
        document.head.appendChild(style);
        var legend = document.createElement("div");
        legend.className = "ol-unselectable ol-control";
        legend.style.cssText = "position:fixed;left:8px;bottom:40px;z-index:6;padding:4px 8px;background:rgba(255,255,255,0.92);color:#222;font-size:12px;line-height:18px;display:none;max-width:260px";
        document.body.appendChild(legend);
        var setMode = function (mode)
        {
            OPS_Map.faunaMode = (mode === "class") ? "class" : "category";
            $(document.body).toggleClass("openkis-fauna-mode-class", OPS_Map.faunaMode === "class");
        };
        setMode(OPS_Map.faunaMode);
        var updateLegend = function () {
            if (OPS_Map.faunaLegend)
            {
                legend.innerHTML = OPS_Map.faunaLegendHtml(OPS_Map.faunaLegend, source.getFeatures());
            }
            legend.style.display = (layer.getVisible() && source.getFeatures().length > 0) ? "block" : "none";
        };
        layer.on("change:visible", function () {
            iconLayer.setVisible(layer.getVisible());
            OPS_Map.UpdateOverlays();
            updateLegend();
        });
        $(legend).on("click", "a[data-fauna-mode]", function (e) {
            e.preventDefault();
            setMode($(this).attr("data-fauna-mode"));
            redraw();
            updateLegend();
        });
        $(legend).on("change", "input[data-fauna-key]", function () {
            var key = $(this).attr("data-fauna-key");
            if (this.checked)
            {
                delete OPS_Map.faunaHidden[OPS_Map.faunaMode][key];
            } else
            {
                OPS_Map.faunaHidden[OPS_Map.faunaMode][key] = true;
            }
            redraw();
        });
        $.getJSON(path, function (data) {
            var features = new ol.format.GeoJSON().readFeatures(data, {
                dataProjection: OPS_Map.inputProjectonEPSG,
                featureProjection: OPS_Map.view.getProjection()
            });
            if (data.legend)
            {
                OPS_Map.faunaLegend = data.legend;
                OPS_Map.faunaIcons = null;
            }
            source.addFeatures(features);
            updateLegend();
            if (fit && features.length > 0)
            {
                // the legend covers the bottom left corner: keep the points out of it
                // when the map is large enough, else leave the usual margin
                var padding = [60, 60, 60, 60];
                var size = OPS_Map.map.getSize();
                if (legend.style.display !== "none" && size && size[0] > 3 * legend.offsetWidth && size[1] > 2 * legend.offsetHeight)
                {
                    padding = [40, 40, legend.offsetHeight + 50, legend.offsetWidth + 20];
                }
                OPS_Map.view.fit(source.getExtent(), {padding: padding, maxZoom: 15});
            }
        });
        return layer;
    },
    /**
     * Fauna layer configured from the page url: hidden (all the places, can
     * be switched on) unless the url asks for it, in which case it is shown
     * on a light base layer and the view is framed on the points:
     *   fauna=1                all the caves with sightings
     *   fauna_cave=code        the point of one cave
     *   fauna_species=name     the caves where a species was observed
     *   fauna_category=key     the caves with a species of a category
     *   fauna_class=key        the caves with a species of a class
     *   fauna_mode=class       colours by class instead of by category
     * @param {String} title layer name in the layer switcher
     * @returns {ol.layer.Vector}
     */
    addFaunaLayerFromUrl: function (title)
    {
        var path = "openkis_fauna.php";
        var params = [];
        var names = {fauna_cave: "cave", fauna_species: "species", fauna_category: "category", fauna_class: "class"};
        for (var name in names)
        {
            var value = getUrlVar(name, "");
            if (value !== "")
            {
                params.push(names[name] + "=" + encodeURIComponent(value));
            }
        }
        if (params.length > 0)
        {
            path += "?" + params.join("&");
        }
        OPS_Map.faunaMode = (getUrlVar("fauna_mode", "") === "class") ? "class" : "category";
        var shown = (getUrlVar("fauna", "") !== "" || params.length > 0);
        if (shown)
        {
            // the coloured points read better on a light background
            OPS_Map.defaultBaseLayer = "light";
        }
        return this.addFaunaLayer(title, path, shown, shown && getUrlVar("fauna_cave", "") === "");
    },
    addJsonLayer: function (title, path, isBaselayer, visible)
    {
        //---------------------------json----------------------------------->
        var geojsonObject = data;
        var layer = new ol.layer.Vector({
            crossOrigin: "Anonymous",
            name: title,
            visible: visible,
            source: new ol.source.Vector({
                crossOrigin: "Anonymous",
                url: path,
                projection: this.view.getProjection(),
                features: new ol.Feature.GeoJSON()
            })
        });
        this.addLayer(layer, isBaselayer);

        //---------------------------json-----------------------------------<        
    },

    /**
     * 
     * @param {type} title
     * @param {type} path
     * @param {type} isBaselayer
     * @param {type} visible
     * @returns {undefined}
     */
    addlocalLayer: function (title, path, isBaselayer, visible) {
        var layer = new ol.layer.Tile({
            name: title,
            visible: visible,
            source: new ol.source.XYZ({
                crossOrigin: "Anonymous",
                url: path + "{z}/{x}/{y}.png",
                projection: this.view.getProjection(),
                format: new ol.format.KML()
            })
        });
        this.addLayer(layer, isBaselayer);

    },
    /**
     * 
     * @param {type} title
     * @param {type} style
     * @param {type} isBaselayer
     * @param {type} visible
     * @returns {undefined}
     */
    addBingLayer: function (title, style, key, isBaselayer, visible) {

        /*        
         'Road',
         'Aerial',
         'AerialWithLabels',
         'collinsBart',
         'ordnanceSurvey'
         */
        var layer = new ol.layer.Tile({
            name: title,
            visible: visible,
            //preload: Infinity,
            source: new ol.source.BingMaps({
                crossOrigin: "Anonymous",
                key: key,
                imagerySet: style
                        // use maxZoom 19 to see stretched tiles instead of the BingMaps
                        // "no photos at this zoom
            })
        });
        this.addLayer(layer, isBaselayer);
    },

    /**
     * 
     * @param {type} title
     * @param {type} url
     * @param {type} layername
     * @param {type} isBaselayer
     * @param {type} visible
     * @param {type} format
     * @returns {undefined}
     */
    addWMSLayer: function (title, url, layername, isBaselayer, visible, format, projecton, attributions) {

        format = (typeof format !== 'undefined') ? format : "png";
        attributions = (typeof attributions !== 'undefined') ? attributions : "";

        projecton = (typeof projecton !== 'undefined') ? projecton : this.viewMapProjectonEPSG;
        if (!projecton)
        {
            projecton = this.viewMapProjectonEPSG;
        }
        //console.log(projecton);
        if (format != "jpeg" && format != "png" && format != "image/jpeg" && format != "image/png")
        {
            format = "png";
        }
        if (isBaselayer)
        {
            if (format != "image/jpeg")
                format = "jpeg";
        }
        //console.log("name "+layername+" format "+format);
        var layer = new ol.layer.Tile({
            name: title,
            visible: visible,
            source: new ol.source.TileWMS({
                crossOrigin: "Anonymous",
                url: url,
                projection: projecton,
                attributions: attributions,
                params: {'LAYERS': layername, 'FORMAT': format, "PROJECTION": this.viewMapProjectonEPSG, "srs": this.viewMapProjectonEPSG},
                // params: {'layers': layername, 'format': format, "projecton": projecton},
                serverType: 'geoserver'
            })
        });
        try {
            this.addLayer(layer, isBaselayer);

        } catch (e) {
            console.log("error load layer");
        }
    },
    getKmlPoints: function ()
    {
        this.KmlPoints = new Array();
        for (var i in this.kmllayers)
        {

            // var Layeritems = this.kmllayers[i].values_.source['uidIndex_'];
            //console.log(this.kmllayers[i].getSource());
            if (this.kmllayers[i].searchable)
            {
                this.kmllayers[i].getSource().forEachFeature(function (feature) {
                    var tmp = new Array();
                    try {
                        tmp['name'] = feature.getProperties()['name'].toString();
                        tmp['geometry'] = feature.getProperties()['geometry'];

//                tmp['id'] = ni;
//                tmp['point'] = Layeritems[ni];
                        OPS_Map.KmlPoints.push(tmp);
                    } catch (e) {
                    }
                });
            }

        }
        //console.log(this.KmlPoints);
        return this.KmlPoints;
        // console.log(this.KmlPoints);
    },
    //OK    http://www.cartografiarl.regione.liguria.it/MapServer/4.10/mapserv.exe?MAP=E:/Progetti/mapfiles/repertoriocartografico/PIANIFICAZIONE/1162.map&LAYERS=M1162&FORMAT=png&TRANSPARENT=true&PROJECTION=EPSG%3A900913&VISIBILITY=false&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&SRS=EPSG%3A900913&BBOX=975947.97708793,5536486.832016,980839.9468975,5541378.8018256&WIDTH=256&HEIGHT=256
    /**
     * 
     * @returns {Boolean}
     */
    centerToMyPosition: function () {
        var c = OPS_Map.geolocationPositions.getCoordinates();
        if (c) {

            c = c[0];
            if (c && !isNaN(c[0])) {
                //console.log(c);
                OPS_Map.GeolocationMarker.setPosition(c);
                if (OPS_Map.FollowingIsActive)
                {
                    OPS_Map.view.animate({
                        center: c,
                        duration: 0
                    }
                    );
                }
            }
        }
    },

    initGeolocation: function ()
    {
        if (this.geolocationPositions)
            return true;
//---------------------------OL3 Geolocation----------------------------------->
        this.geolocationPositions = new ol.geom.LineString([],
                /** @type {ol.geom.GeometryLayout} */ ('XYZM'));
// Geolocation Control
        //console.log(this.view.getProjection());
        this.geolocation = new ol.Geolocation(/** @type {olx.GeolocationOptions} */ ({
            projection: this.view.getProjection(),
            trackingOptions: {
                maximumAge: 10000,
                enableHighAccuracy: true,
                timeout: 600000
            }
        }));
        var deltaMean = 500; // the geolocation sampling period mean in ms
        // Listen to position changes
        this.geolocation.on('change', function (evt) {
            var position = OPS_Map.geolocation.getPosition();
            var accuracy = OPS_Map.geolocation.getAccuracy();
            var heading = OPS_Map.geolocation.getHeading() || 0;
            var speed = OPS_Map.geolocation.getSpeed() || 0;
            var m = Date.now();
            OPS_Map.addPosition(position, heading, m, speed);
            var coords = OPS_Map.geolocationPositions.getCoordinates();
            var len = coords.length;
            if (len >= 2) {
                deltaMean = (coords[len - 1][3] - coords[0][3]) / (len - 1);
            }
            var renderPosition = ol.proj.transform(position, OPS_Map.viewMapProjectonEPSG, OPS_Map.inputProjectonEPSG);
            var html = [
                'Position: ' + renderPosition[0].toFixed(2) + ', ' + renderPosition[1].toFixed(2),
                'Accuracy: ' + accuracy + " m",
                'Heading: ' + Math.round(OPS_Map.radToDeg(heading)) + '&deg;',
                'Speed: ' + (speed * 3.6).toFixed(1) + ' km/h',
                'Delta: ' + Math.round(deltaMean) + 'ms'
            ].join('<br />');
            $('#gpsinfo').html(html);
            $('.gpsaccuracy').html(accuracy);
            $('.gpsspeed').html(speed);

            if (OPS_Map.FollowingIsActive || OPS_Map.TrackIsActive)
            {
                var c = OPS_Map.geolocationPositions.getCoordinates();
                if (c) {

                    c = c[0];
                    if (c && !isNaN(c[0])) {
                        //console.log(c);
                        OPS_Map.GeolocationMarker.setPosition(c);
                        if (OPS_Map.FollowingIsActive)
                        {
                            OPS_Map.view.animate({
                                center: c,
                                duration: 0
                            }
                            );
                        }
                    }
                }
            }

        });

        this.geolocation.on('error', function (e) {
            console.log("geolocation error:");
            console.log(e);
        });


        /*
         this.map.beforeRender(function (map, frameState) {
         
         if (OPS_Map.FollowingIsActive || OPS_Map.TrackIsActive)
         {
         if (frameState !== null) {
         // use sampling period to get a smooth transition
         var m = frameState.time - deltaMean * 1.5;
         m = Math.max(m, this.previousM);
         this.previousM = m;
         // interpolate position along positions LineString
         var c = OPS_Map.geolocationPositions.getCoordinates(m, true);
         if (c) {
         var view = frameState.viewState;
         c = c[0];
         if (c && !isNaN(c[0])) {
         //console.log(c);
         OPS_Map.GeolocationMarker.setPosition(c);
         if (OPS_Map.FollowingIsActive)
         {
         view.center = OPS_Map.getCenterWithHeading(c, -c[2], view.resolution);
         view.rotation = -c[2];
         
         OPS_Map.map.getView().setCenter(view.center);
         OPS_Map.map.getView().setRotation(view.rotation);
         
         }
         }
         }
         }
         }
         return true; // Force animation to continue
         });*/
//---------------------------OL3 Geolocation-----------------------------------<
    },
// convert radians to degrees
    radToDeg: function (rad) {
        return rad * 360 / (Math.PI * 2);
    },
// convert degrees to radians
    degToRad: function (deg) {
        return deg * Math.PI * 2 / 360;
    },
// modulo for negative values
    mod: function (n) {
        return ((n % (2 * Math.PI)) + (2 * Math.PI)) % (2 * Math.PI);
    },
    addPosition: function (position, heading, m, speed) {
        var x = position[0];
        var y = position[1];
        var fCoords = this.geolocationPositions.getCoordinates();
        var previous = fCoords[fCoords.length - 1];
        var prevHeading = previous && previous[2];
        if (prevHeading) {
            var headingDiff = heading - this.mod(prevHeading);

            // force the rotation change to be less than 180°
            if (Math.abs(headingDiff) > Math.PI) {
                var sign = (headingDiff >= 0) ? 1 : -1;
                headingDiff = -sign * (2 * Math.PI - Math.abs(headingDiff));
            }
            heading = prevHeading + headingDiff;
        }
        this.geolocationPositions.appendCoordinate([x, y, heading, m]);

        // only keep the 20 last coordinates
        this.geolocationPositions.setCoordinates(this.geolocationPositions.getCoordinates().slice(-20));

        // FIXME use speed instead
        if (heading && speed) {
            this.markerEl.src = OPS_Map.basepath + 'images/geolocation_marker_heading.png';
        } else {
            this.markerEl.src = OPS_Map.basepath + 'images/geolocation_marker.png';
        }
    },
    previousM: 0,
// recenters the view by putting the given coordinates at 3/4 from the top or
// the screen
    getCenterWithHeading: function (position, rotation, resolution) {
        var size = OPS_Map.map.getSize();
        var height = size[1];
        return [
            position[0] - Math.sin(rotation) * height * resolution * 1 / 4,
            position[1] + Math.cos(rotation) * height * resolution * 1 / 4
        ];
    },
    /**
     * 
     * @returns {undefined}
     */
    render: function () {
        OPS_Map.map.render();
    },
    /**
     * 
     * @returns {undefined}
     */
    startTracking: function () {
        this.initGeolocation();
        this.TrackIsActive = true;
        this.geolocation.setTracking(true); // Start position tracking
        this.map.on('postcompose', OPS_Map.render);
        this.map.render();
    },
    /**
     * 
     * @returns {undefined}
     */
    stopTracking: function () {
        this.TrackIsActive = false;
        this.map.on('postcompose', OPS_Map.render);
        this.map.render();

    },
    /**
     * 
     * @returns {undefined}
     */
    startFollowing: function () {
        this.initGeolocation();
        OPS_Map.FollowingIsActive = true;
        this.geolocation.setTracking(true); // Start position tracking
        //console.log (OPS_Map.FollowingIsActive || OPS_Map.TrackIsActive);
        this.map.on('postcompose', OPS_Map.render);
        this.map.render();
        $("#stopfollow").removeClass("btn-primary");
        $("#startfollow").addClass("btn-primary");
    },
    /**
     * 
     * @returns {undefined}
     */
    stopFollowing: function () {
        this.FollowingIsActive = false;
        this.map.on('postcompose', OPS_Map.render);
        this.map.render();
        $("#stopfollow").addClass("btn-primary");
        $("#startfollow").removeClass("btn-primary");

    },
    /**
     * 
     * @returns {undefined}
     */
    loadDefaultSettings: function () {
//--------------------------SETTINGS------------------------------------------->
        OPS_Map.setCenter(8, 45);
        OPS_Map.setZoom(10);
        OPS_Map.addOSMLayer("OSM TOPO");
        OPS_Map.initGeolocation();
        OPS_Map.startTracking();
        //OPS_Map.startFollowing();

//--------------------------SETTINGS-------------------------------------------<

    },
    //-------------------------gestione layer tree------------------------->
    /**
     * Build a tree layer from the map layers with visible and opacity 
     * options.
     * 
     * @param {type} layer
     * @returns {String}
     */
    inc_opacity: function (el, val) {
        var oldval = $(el).parent().children('input').val();
        var newvalue;
        if (val == undefined)
        {
            newvalue = el.value;
        } else
        {
            newvalue = parseFloat(oldval) + val;
        }
        if (isNaN(newvalue))
            newvalue = 0;
        if (newvalue > 1)
            newvalue = 1;
        if (newvalue < 0)
            newvalue = 0;
        $(el).parent().children('input').val(newvalue);
        var layername = $(el).parent().children('input').closest('li').data('layerid');
        var layer = this.findBy(OPS_Map.map.getLayerGroup(), 'name', layername);
        layer.setOpacity(parseFloat(newvalue));
    },
    findBy: function (layer, key, value) {
        if (layer.get(key) === value) {
            return layer;
        }
        // Find recursively if it is a group
        if (layer.getLayers) {
            var layers = layer.getLayers().getArray(),
                    len = layers.length, result;
            for (var i = 0; i < len; i++) {
                result = this.findBy(layers[i], key, value);
                if (result) {
                    return result;
                }
            }
        }
        return null;
    },
    /**
     * 
     * @param {type} layer
     * @returns {String}
     */
    buildLayerTree: function (layer, first) {

        var elem;
        var name = "";
        var HaveChilds = layer.getLayers;
        var ulclass = "";
        var trclass = "glyphicon-file";
        if (HaveChilds)
        {
            ulclass = "clickme";
            trclass = "glyphicon-plus";
        }
        var name = layer.get('name');
        if (name == undefined)
        {
            name = "layers";
            if (!HaveChilds)
                return "";
        }
        var hidden = layer.get("visible") ? "" : "display:none";
        var checked = layer.get("visible") ? "checked" : "unchecked";

        var div = "<li class='" + ulclass + "' data-layerid='" + name + "'>" +
                "<i class='iconcheck glyphicon glyphicon-" + checked + "' ></i><span><i class='glyphicon " + trclass + "'></i> " + name + "</span>" +
                "  ";
        if (!HaveChilds || !first)
        {
            var val = layer.get("opacity");
            div += "<input type='hidden' class='opacity'  value='" + val + "' /><input style='" + hidden + "' value='" + val + "' onchange=\"OPS_Map.inc_opacity(this);\"  type='range' min='0' max='1' step='0.1'/>";
        }
        if (layer.getLayers) {
            var sublayersElem = '';
            var layers = layer.getLayers().getArray(),
                    len = layers.length;
            for (var i = 0; i < len; i++) {
                {
                    if (first)
                        sublayersElem += OPS_Map.buildLayerTree(layers[i]);
                }
            }
            elem = div + " <ul >" + sublayersElem + "</ul></li>";
        } else {
            elem = div + " </li>";
        }
        return elem;
    },
    /**
     * 
     * @returns {undefined}
     */
    switchBaseLayer: function ()
    {
        var checkedLayer = $('#ops_baselayerswitcher input[name=layer]:checked').val();
        if (checkedLayer === undefined)
        {
//            checkedLayer="Topo";
        }
        for (i = 0, ii = this.baselayers.length; i < ii; ++i)
        {
            this.baselayers[i].setVisible(0);
            //-----legend----->
            try {
                var layername = this.baselayers[i].get('name');
                if (document.getElementById("LayerLegend") && OPS_Map.LayerLegend[layername]) {
                    document.getElementById("LayerLegend").innerHTML = "";
                }
            } catch (e) {
            }
            ;
            //-----legend-----<
        }
        var layer = OPS_Map.findBy(OPS_Map.map.getLayerGroup(), 'name', checkedLayer);
        layer.setVisible(1);
        layer.setOpacity(1);

        //-----legend----->
        var layername = layer.get('name');
        var html = "";
        try {
            if (OPS_Map.LayerLegend[layername] !== undefined)
                html = OPS_Map.LayerLegend[layername];
            document.getElementById("LayerLegend").innerHTML = html;
        } catch (e) {
            document.getElementById("LayerLegend").innerHTML = "";
        }
        if (document.getElementById("LayerLegend") !== undefined) {
            document.getElementById("LayerLegend").innerHTML = html;
        }
        //-----legend-----<
        OPS_Map.UpdateOverlays();
    },
    /**
     * 
     * @returns {undefined}
     */
    addLayerSwitcher: function () {
        // Use a plain function call instead of $(function(){}) to avoid
        // jQuery 3's async document.ready (Promise-based), which would defer
        // radio button creation past the immediate switchBaseLayer() call.
        (function ()
        {
            OPS_Map.printLayerSwitcher();
            OPS_Map.printLayerTree();
            $("#ops_baselayerswitcher input[name=layer]").change(function () {
                OPS_Map.switchBaseLayer();
            });
            OPS_Map.UpdateOverlays();
            $('i.iconcheck').on('click', function () {
                var layername = $(this).closest('li').data('layerid');
                var layer = OPS_Map.findBy(OPS_Map.map.getLayerGroup(), 'name', layername);
                if (layer)
                {
                    //console.log(layername);
                    layer.setVisible(!layer.getVisible());
                    if (layer.getVisible())
                    {
                        $(this).next().next().next().show();
                        var opacity = parseFloat($(this).next().next().next().val());
                        //console.log(opacity);
                        layer.setOpacity(opacity);
                        //-----legend----->
                        var html = "";
                        try {
                            html = OPS_Map.LayerLegend[layername];
                            document.getElementById("LayerLegend").innerHTML = html;
                        } catch (e) {
                            document.getElementById("LayerLegend").innerHTML = "";
                        }
                        if (document.getElementById("LayerLegend")) {
                            document.getElementById("LayerLegend").innerHTML = html;
                        }
                        //-----legend-----<
                    } else
                    {
                        $(this).next().next().next().hide();
                        if (document.getElementById("LayerLegend")) {
                            document.getElementById("LayerLegend").innerHTML = html;
                        }
                    }
                }
                OPS_Map.UpdateOverlays();
            });
        }
        )();

    },
    /**
     * Initialize the tree from the map layers
     * @returns {undefined}
     */
    printLayerTree: function () {

        var elem = OPS_Map.buildLayerTree(OPS_Map.map.getLayerGroup(), true);
        $('#ops_layertree').empty().append(elem);
        $('.tree li:has(ul)').addClass('parent_li').find(' > span').attr('title', 'Collapse this branch');
        $('.tree li.parent_li > span').on('click', function (e) {
            var children = $(this).parent('li.parent_li').find(' > ul > li');
            if (children.is(":visible")) {
                children.hide('fast');
                $(this).attr('title', 'Expand this branch').find(' > i').addClass('glyphicon-plus').removeClass('glyphicon-minus');
            } else {
                children.show('fast');
                $(this).attr('title', 'Collapse this branch').find(' > i').addClass('glyphicon-minus').removeClass('glyphicon-plus');
            }
            e.stopPropagation();
        });
        $(".tree li ul li").hide();
    },
    /**
     * 
     * @returns {undefined}
     */
    UpdateOverlays: function ()
    {
        $('.iconcheck').each(function () {
            var layername = $(this).closest('li').data('layerid');
            var layer = OPS_Map.findBy(OPS_Map.map.getLayerGroup(), 'name', layername);
            if (layer)
            {
                $(this).removeClass('glyphicon-unchecked');
                $(this).removeClass('glyphicon-check');
                $(this).removeClass('glyphicon-unchecked');
                $(this).removeClass('glyphicon-check');
                if (layer.getVisible()) {
                    $(this).addClass('glyphicon-check');
                } else {
                    $(this).addClass('glyphicon-unchecked');
                }
            }
        }
        );
    },
    /**
     * Names of the light base layers of the layer configurations, in order of
     * preference: "light" as base layer name picks the first one present.
     */
    lightBaseLayers: ["Bianco", "Light Gray"],
    /**
     * Base layer used when the url has no "basemap" parameter ("" = the first
     * one); a layer can ask for one, e.g. "light" for the temperatures.
     */
    defaultBaseLayer: "",
    /**
     * Name of the base layer to show at start: the "basemap" url parameter,
     * else defaultBaseLayer; "light" is resolved through lightBaseLayers.
     * Unknown names give "" (the first base layer).
     * @param {Array} tlayers the base layers
     * @returns {String}
     */
    startBaseLayerName: function (tlayers)
    {
        var wanted = this.getUrlVar("basemap", "");
        if (wanted === "")
        {
            wanted = OPS_Map.defaultBaseLayer;
        }
        var names = [];
        for (var i = 0; i < tlayers.length; i++)
        {
            if (!tlayers[i].getLayers && tlayers[i].get('name'))
            {
                names.push(tlayers[i].get('name'));
            }
        }
        var candidates = (wanted === "light") ? OPS_Map.lightBaseLayers : [wanted];
        for (var j = 0; j < candidates.length; j++)
        {
            if (names.indexOf(candidates[j]) !== -1)
            {
                return candidates[j];
            }
        }
        return "";
    },
    HtmlLayerItemsBaselayers: function (tlayers)
    {
        var html = "";
        var getname = this.startBaseLayerName(tlayers);
        for (var i = 0; i < tlayers.length; i++)
        {
            var layer = tlayers[i];
            var name = layer.get('name') ? layer.get('name') : "Group";
            if (layer.getLayers)
            {

            } else
            {
                var checked = "";
                if ((i == 0 && getname == "") || getname == name)
                {
                    checked = "checked";
                }
                html += "<li><label><input type=\"radio\" name=\"layer\" value=\"" + name + "\" " + checked + " >" + name + "</label></li>";
            }
        }
        return html;
    },
    LayerSwitcherCollapse: function () {
        $('#ops_tolbox').hide();
        $('#ops_tolboxopen').show();
    },
    LayerSwitcherOpen: function () {
        $('#ops_tolbox').show();
        $('#ops_tolboxopen').hide();
    },
    addMarkerText: function (lon, lat, img, text) {
        OPS_Map.markers[OPS_Map.id_markers] = new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.transform(
                    [lon, lat],
                    OPS_Map.inputProjectonEPSG,
                    this.view.getProjection()
                    ))
        });

        var iconStyle = [
            new ol.style.Style({
                image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                    anchor: [0.5, 0.5],
                    anchorXUnits: 'fraction',
                    anchorYUnits: 'fraction',
                    src: img
                }))
            }),
            new ol.style.Style({
                text: new ol.style.Text({
                    text: text,
                    offsetY: -25,
                    font: '12px Calibri,sans-serif',
                    fill: new ol.style.Fill({
                        color: '#000000',
                    })
                })
            })
        ];

        OPS_Map.markers[OPS_Map.id_markers].setStyle(iconStyle);
        var vectorSource = new ol.source.Vector({
            crossOrigin: "Anonymous",
            features: [OPS_Map.markers[OPS_Map.id_markers]]
        });

        var vectorLayer = new ol.layer.Vector({
            source: vectorSource
                    // name: "Info window"
        });


        OPS_Map.map.addLayer(vectorLayer);
        OPS_Map.markersLayer[OPS_Map.id_markers] = vectorLayer;
        OPS_Map.markers[OPS_Map.id_markers].lon = lon;
        OPS_Map.markers[OPS_Map.id_markers].lat = lat;
//        this.markers[this.id_markers] = iconFeature;
        this.id_markers++;
        return vectorLayer;
    },
    convertToRadian: function (numericDegree) {
        return numericDegree * Math.PI / 180;
    },
    calculateDistance: function (latitude1, longitude1, latitude2, longitude2) {
        // Calculate distance between mountain peak and current location
        // using the Haversine formula
        var earthRadius = 6373044.737; // Radius of the earth in km
        var dLatitude = this.convertToRadian(latitude2 - latitude1);
        var dLongitude = this.convertToRadian(longitude2 - longitude1);
        var a = Math.sin(dLatitude / 2) * Math.sin(dLatitude / 2) + Math.cos(this.convertToRadian(latitude1)) * Math.cos(this.convertToRadian(latitude2)) * Math.sin(dLongitude / 2) * Math.sin(dLongitude / 2);
        var greatCircleDistance = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        var distance = earthRadius * greatCircleDistance; // distance converted to m from radians
        return Math.round(distance);
    },
    drawCircleInMeter: function (radius) {
        var view = OPS_Map.map.getView();
        var projection = view.getProjection();
        var resolutionAtEquator = view.getResolution();
        var center = OPS_Map.map.getView().getCenter();
        /*
         var pointResolution = projection.getPointResolution(resolutionAtEquator, center);
         var resolutionFactor = resolutionAtEquator / pointResolution;
         radius = (radius / ol.proj.METERS_PER_UNIT.m) * resolutionFactor;
         */
        radius = (radius / projection.getMetersPerUnit()); //da sistemare

        var circle = new ol.geom.Circle(center, radius);

        var circleFeature = new ol.Feature(circle);

        // Source and vector layer
        var vectorSource = new ol.source.Vector({
            crossOrigin: "Anonymous",
            projection: 'EPSG:4326'
        });
        vectorSource.addFeature(circleFeature);
        var vectorLayer = new ol.layer.Vector({
            crossOrigin: "Anonymous",
            source: vectorSource
        });

        OPS_Map.map.addLayer(vectorLayer);
        return vectorLayer;
    },
    drawPointCircleInMeter: function (radius, lat, lon) {
        var view = OPS_Map.map.getView();
        var projection = view.getProjection();
        radius = (radius / projection.getMetersPerUnit()); //da sistemare
        var center = ol.proj.transform([lon, lat], this.inputProjectonEPSG, this.viewMapProjectonEPSG);

        var circle = new ol.geom.Circle(center, radius);
        var circleFeature = new ol.Feature(circle);
        // Source and vector layer
        var vectorSource = new ol.source.Vector({
            projection: 'EPSG:4326'
        });
        vectorSource.addFeature(circleFeature);
        var vectorLayer = new ol.layer.Vector({
            source: vectorSource
        });
        OPS_Map.map.addLayer(vectorLayer);
        return vectorLayer;
    },
    /**
     * 
     * @returns {undefined}		 
     */
    printLayerSwitcher: function ()
    {
        //alert(document.getElementById('ops_tolboxopen'));
        if (document.getElementById('ops_layerswitcher') == null)
        {

            var htmlswitcher = "";
            htmlswitcher += '<div  class="ops_layerswitcher" id="ops_layerswitcher">\n';
            htmlswitcher += '    <button id="ops_tolboxopen" class="glyphicon-plus" ';
            htmlswitcher += '        onclick="OPS_Map.LayerSwitcherOpen();"';
            htmlswitcher += '        style="display:none">\n';
            htmlswitcher += '    </button>\n';
            htmlswitcher += '    <div id="ops_tolbox">\n';
            htmlswitcher += '        <button onclick="OPS_Map.LayerSwitcherCollapse();" id="ops_tolboxclose" class="glyphicon-minus"></button>\n';
            htmlswitcher += '        Base Layers:\n';
            htmlswitcher += '        <div id ="ops_baselayers"></div>\n';
            htmlswitcher += '        Overlays:\n';
            htmlswitcher += '        <div id="ops_layertree" class="tree"></div>\n';
            htmlswitcher += '    </div>\n';
            htmlswitcher += '</div>\n';
            $("#" + OPS_Map.divID + " canvas").after(htmlswitcher);
        }

        var html = "";
        html += "<ul id=\"ops_baselayerswitcher\">";
        html += OPS_Map.HtmlLayerItemsBaselayers(OPS_Map.baselayers);
        html += "</ul>";
        document.getElementById("ops_baselayers").innerHTML = html;
    },
    getUrlVar: function (name, defaultValue) {
        if (defaultValue === undefined)
        {
            defaultValue = "";
        }
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                results = regex.exec(location.search);
        return results === null ? defaultValue : decodeURIComponent(results[1].replace(/\+/g, " "));
    },
    getMarkerKmlByName: function (text) {
        var result = new Array();
        var index = 0;
        for (var i in OPS_Map.KmlPoints) {
            if (OPS_Map.KmlPoints[i]['name'] == text)
            {

                return  OPS_Map.KmlPoints[i];
            }
        }
        return false;
    },
    searchMarkers: function (text) {
        var result = new Array();
        var index = 0;
        var title, description;
        for (var i in OPS_Map.markers) {
            try {
                title = OPS_Map.markers[i].getProperties()['name'].toString().toLowerCase();
                text = text.toLowerCase();
                if (title.indexOf(text) >= 0 /*|| description.indexOf(text) >= 0*/)
                {
                    result[index] = OPS_Map.markers[i];
                    result[index].idlayer = i;
                    index++;
                }
            } catch (e) {
                console.log(e);
            }
        }
        return result;
    },
    //-------------------------gestione layer tree-------------------------<		


    /* View in fullscreen */
    openFullscreen: function () {
        var elem = document.getElementsByTagName("body")[0];

        if (!window.screenTop && !window.screenY) {
            try {
                this.closeFullscreen();
            } catch (e) {
            }
        }

        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) { /* Firefox */
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari and Opera */
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { /* IE/Edge */
            elem.msRequestFullscreen();
        }
    },
    /* Close fullscreen */
    closeFullscreen: function () {
        var elem = document.getElementsByTagName("body")[0];
        if (document.exitFullscreen) {
            try {
                document.exitFullscreen();
            } catch (e) {
            }

        } else if (document.mozCancelFullScreen) { /* Firefox */
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) { /* Chrome, Safari and Opera */
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) { /* IE/Edge */
            document.msExitFullscreen();
        }
    }

};
