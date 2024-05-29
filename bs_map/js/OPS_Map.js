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
            this.map = new ol.Map({
                target: document.getElementById(divId),
                view: this.view
            });
        }

        //------------------map object ----------------------------------------<
        //---------------- display popup on click------------------------------>
        this.map.on('click', function (evt) {
            OPS_Map.onMapClick(evt);
        });


        // change mouse cursor when over marker
        this.map.on('pointermove', function (e) {
            if (e.dragging) {
                OPS_Map.stopFollowing();
                //$(element).popover('destroy');
                return;
            }
            var pixel = OPS_Map.map.getEventPixel(e.originalEvent);
            var hit = OPS_Map.map.hasFeatureAtPixel(pixel);
            OPS_Map.map.getTarget().style.cursor = hit ? 'pointer' : '';
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

        // Listener per l'evento wheel
        this.map.getViewport().addEventListener('wheel', function(event) {
            event.preventDefault();
            try{
                document.getElementsByClassName("ol-mouse-position")[0].innerHTML='press Ctrl';
            }catch(e){

            }

        });

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
                        OPS_Map.kmllayers[i].setSource(new ol.source.Vector({
                            crossOrigin: "Anonymous",
                            url: OPS_Map.kmllayers[i].path,
                            projection: OPS_Map.view.getProjection(),
                            format: new ol.format.KML(
                                    {
                                        writeStyles: true,
                                        showPointNames: showPointNames
                                    })
                        }));
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
                positioning: 'auto',
                stopEvent: true,
                offset: [0, -8]
            });
        }
        this.map.addOverlay(this.popupOverlay);
        coordinates = ol.proj.transform(coordinates, OPS_Map.inputProjectonEPSG, OPS_Map.viewMapProjectonEPSG);
        OPS_Map.popupOverlay.setPosition(coordinates);
        htmlclosePopup = "<span style=\"position:absolute;top:2px;right:2px;cursor:pointer\" class=\"glyphicon glyphicon-remove-sign\" onclick=\"OPS_Map.popUpClose()\"></span>";
        if (OPS_Map.popOver == null)
        {
            OPS_Map.popOver = $(OPS_Map.popupElement).popover({
                'placement': 'top',
                'html': true,
                'content': description,
                'title': name + htmlclosePopup
            });
            $(OPS_Map.popupElement).on('hidden.bs.popover', function () {
                OPS_Map.map.removeOverlay(OPS_Map.popupOverlay);
                OPS_Map.popupOverlay = null;
                OPS_Map.popOver = null;
                OPS_Map.popupClosingProcess = false;
            });
        }
        $('.popover-title').html(name);
        $('.popover-content').html(description);
        $(OPS_Map.popupElement).popover('show');
    },
    onMapClick: function (evt)
    {
        var coordinates = evt.coordinate;

        coordinates = ol.proj.transform(coordinates, OPS_Map.viewMapProjectonEPSG, OPS_Map.inputProjectonEPSG);
        var feature = OPS_Map.map.forEachFeatureAtPixel(evt.pixel,
                function (feature) {
                    return feature;
                });
        var name = false;
        var description = false;
        if (feature) {
            name = feature.get('name');
            description = feature.get('description');
        }
        if (name || description)
        {
            var Fcoordinates = feature.getGeometry().getCoordinates();
            OPS_Map.popUp(coordinates, name, description);
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
    addOSMLayer: function (title, url, isBaselayer, visible) {
        var layer = new ol.layer.Tile({name: title,
            visible: visible,

            source: new ol.source.OSM({
                crossOrigin: "Anonymous",
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
    addKmlLayer: function (title, path, isBaselayer, visible, showPointNames, searchable, layergroup)
    {
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
                source: new ol.source.Vector({
                    crossOrigin: "Anonymous",
                    url: path,
                    projection: this.view.getProjection(),
                    format: new ol.format.KML(
                            {
                                writeStyles: true,
                                showPointNames: showPointNames
                            })
                })
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
                source: new ol.source.Vector({
                    crossOrigin: "Anonymous",
                    url: path,
                    projection: this.view.getProjection(),
                    format: new ol.format.KML(
                            {
                                writeStyles: true,
                                showPointNames: showPointNames
                            })
                })
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
        $(function ()
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
        );

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
    HtmlLayerItemsBaselayers: function (tlayers)
    {
        var html = "";
        for (var i = 0; i < tlayers.length; i++)
        {
            var layer = tlayers[i];
            var name = layer.get('name') ? layer.get('name') : "Group";
            if (layer.getLayers)
            {

            } else
            {
                var checked = "";
                var getname = this.getUrlVar("basemap", "");
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
