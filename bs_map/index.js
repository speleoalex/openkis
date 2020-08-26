/**
 * 
 * @param {type} name
 * @param {type} defaultValue
 * @returns {String}
 */
getUrlVar = function (name, defaultValue) {
    if (defaultValue === undefined)
    {
        defaultValue = "";
    }
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
    return results === null ? defaultValue : decodeURIComponent(results[1].replace(/\+/g, " "));
};

/**
 * 
 * @param {type} title
 * @param {type} lon
 * @param {type} lat
 * @returns {undefined}
 */
function navigate(title, lon, lat) {
    lon = parseFloat(lon);
    lat = parseFloat(lat);
    window.open("//www.google.it/maps/dir//" + lat + "," + lon + "/@" + lat + "," + lon + ",18z" + "");//daddr=
}
/**
 * 
 */
window.setTimeout(function () {
    OPS_Map.init("map");

    var lat = getUrlVar("lat", OPS_Map.defaultLat);
    var lon = getUrlVar("lon", OPS_Map.defaultLon);
    var zoom = getUrlVar("zoom", OPS_Map.defaultZoom);
    var point = getUrlVar("point", false);
    var num = getUrlVar("num", false);
    var layers = getUrlVar("layers", false);
    var baselayer = getUrlVar("baselayer", false);
    var table = getUrlVar("table", false);
    try {
        lat = lat.replace(",", ".");
        lon = lon.replace(",", ".");
    } catch (e) {

    }
    //console.log(lat+" - "+lon);
    OPS_Map.setCenter(parseFloat(lon), parseFloat(lat));
    OPS_Map.setZoom(zoom);
    OPS_Map.MyAddLayers();
    //OPS_Map.addKmlLayer("Storico coordinate", "history_coords.kml.php?num=" + num, false, true);
    if (point == "circle")
    {
        OPS_Map.drawCircleInMeter(20);
        //OPS_Map.addMarker(parseFloat(lon), parseFloat(lat), "images/down.png");
    }

    $("#map").mousedown(function () {
        OPS_Map.stopFollowing();
    });
    $(function () {
        OPS_Map.LayerSwitcherCollapse();
    });
    $("#rotation").change(function () {
        OPS_Map.map.getView().setRotation(parseFloat($("#rotation").val()));
    });
    //autocompletamento --->
    /**
     * 
     * @param {type} strs
     * @returns {Function}
     */
    var substringMatcher = function (strs) {
        return function findMatches(q, cb) {
            var matches, substringRegex;
            // an array that will be populated with substring matches
            matches = [];
            // regex used to determine if a string contains the substring `q`
            substrRegex = new RegExp(q, 'i');
            // iterate through the pool of strings and for any string that
            // contains the substring `q`, add it to the `matches` array
            $.each(strs, function (i, str) {
                console.log("splx " + str + " i " + i);
                var name = strs[i]['name'].toString();
                if (substrRegex.test(name)) {
                    matches.push(name);
                }
                ;
            });
            cb(matches);
        };
    };
    /**
     * 
     * @param {type} strs
     * @returns {Function}
     */
    var substringMatcherSingleLayer = function (strs) {

        return function findMatches(q, cb) {
            var matches, substringRegex;
            // an array that will be populated with substring matches
            matches = [];
            // regex used to determine if a string contains the substring `q`
            substrRegex = new RegExp(q, 'i');
            // iterate through the pool of strings and for any string that
            // contains the substring `q`, add it to the `matches` array
            $.each(strs, function (i, str) {

                var name = strs[i].getProperties()['name'].toString();
                if (substrRegex.test(name)) {
                    matches.push(name);
                }
            });
            cb(matches);
        };
    };
    var scaleLine = new ol.control.ScaleLine({
        units: "metric",
        minWidth: 100,
        maxWidth: 200,
        steps: 5,
        text: true,
        bar: false,
        target: document.getElementById('scaleline')
    });

    OPS_Map.map.addControl(scaleLine);
    var mouse_position = new ol.control.MousePosition({
        coordinateFormat: ol.coordinate.createStringXY(4),
        projection: 'EPSG:4326'
    });
    OPS_Map.map.addControl(mouse_position);

    /*
     var mousePositionControl = new MousePosition({
     coordinateFormat: createStringXY(4),
     projection: 'EPSG:4326',
     // comment the following two lines to have the mouse position
     // be placed within the map.
     className: 'custom-mouse-position',
     target: document.getElementById('mouse-position'),
     undefinedHTML: '&nbsp;'
     });*/
    try {
        OPS_Map.Custom();
    } catch (e) {

    }
    if (baselayer)
    {
        $("input[value='" + baselayer + "']").click();
    }
    for (var i in OPS_Map.toinclude)
    {
        splx.include(OPS_Map.toinclude[i]);
    }

    splx.include("bs_map/js/splx_i18n.js");
    splx.include("bs_map/languages/" + OPS_Map.lang + ".js");
    //window.addEventListener("load", function(){TranslateHtml()});
    //start autocomplete
    var fakeSearch = ['cave'];
    /*
     * 
     * @param {type} strs
     * @returns {Function}
     */
    var substringMatcher = function (strs) {
        return function findMatches(q, cb) {
            var matches, substringRegex;
            // an array that will be populated with substring matches
            matches = [];
            // regex used to determine if a string contains the substring `q`
            substrRegex = new RegExp(q, 'i');
            // iterate through the pool of strings and for any string that
            // contains the substring `q`, add it to the `matches` array
            $.each(strs, function (i, str) {
                OPS_Map.getKmlPoints();
                $.each(OPS_Map.KmlPoints, function (ii, str) {
                    var name = OPS_Map.KmlPoints[ii]['name'].toString();
                    if (substrRegex.test(name)) {
                        matches.push((name));
                    }
                });
            });
            cb(matches);
        };
    };

    $('.typeahead').typeahead({
        hint: true,
        highlight: true,
        minLength: 1
    },
            {
                name: 'items',
                source: substringMatcher(fakeSearch)
            });

    $(".typeahead").focus(function () {

        $(this).select();
    });                                                //autocompletamento ---<
    $(".typeahead").click(function () {
        $(this).select();
    });                                                //autocompletamento ---<

    $('.typeahead').on('typeahead:select', function (evt, item) {
        GoToMarker($('#searchText').val());
    });

    if (window.addEventListener) {
        window.addEventListener("load", function () {
            OPS_Map.switchBaseLayer();

        }, false);
    } else if (window.attachEvent) {
        window.attachEvent("onload", function () {
            OPS_Map.switchBaseLayer();

        });
    } else {
        window.onload = function () {
            OPS_Map.switchBaseLayer();

        };
    }


    window.addEventListener('resize', function () {

        ResizeLayerMenu();

    });
    ResizeLayerMenu() ;

    OPS_Map.addLayerSwitcher();
    OPS_Map.switchBaseLayer();

    //OPS_Map.SwitchMode("info");
}, 0);

function ResizeLayerMenu() {
    var h = $(window).height() - $("#navbar").height();
    //h = 800;
    $("#layersmenu").css("max-height", h + "px");
    //$("#layersmenu").css("height", h + "px");
}

/**
 * 
 * @returns {undefined}
 */
function SetDefaultPosition()
{
    var lonlat = ol.proj.transform(
            OPS_Map.map.getView().getCenter(),
            OPS_Map.map.getView().getProjection(),
            OPS_Map.inputProjectonEPSG
            );
    //console.log(OPS_Map.map.getView().getZoom());
    splx.SetConfig("lon", lonlat[0]);
    splx.SetConfig("lat", lonlat[1]);
    splx.SetConfig("zoom", OPS_Map.map.getView().getZoom());

}
/**
 * 
 * @param {type} html
 * @returns {.document@call;createElement.value|txt.value}
 */
function decodeHtml(html) {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
}
/**
 * 
 * @param {type} text
 * @returns {undefined}
 */
function SearchMarkers_(text) {
    alert(text);
    if (text == "")
    {
        for (var i in OPS_Map.markers)
        {
            OPS_Map.markersLayer[i].setVisible(true);
        }
        return;
    }
    var results = OPS_Map.searchMarkers(text);
    if (results[0] != undefined)
    {
        for (var i in OPS_Map.markers)
        {
            //OPS_Map.markersLayer[i].setVisible(false);
        }

        for (var i in results)
        {
            OPS_Map.markersLayer[results[i].idlayer].setVisible(true);
        }


        if (results[0] != undefined)
        {
            var lat = results[0].lat;
            var lon = results[0].lon;
            OPS_Map.setCenter(parseFloat(lon), parseFloat(lat));
            OPS_Map.setZoom(18);
        }
    }
}
/**
 * 
 * @param {type} text
 * @returns {undefined}
 */
function GoToMarker(text) {
    if (text == "")
    {
        return;
    }
    OPS_Map.getKmlPoints();
    var result = OPS_Map.getMarkerKmlByName(text);
    if (result)
    {
        var geometry = result['geometry'];
        if (geometry.flatCoordinates)
        {
            var latlon = ol.proj.transform(geometry.flatCoordinates, OPS_Map.viewMapProjectonEPSG, OPS_Map.inputProjectonEPSG);
            if (OPS_Map.SearchResultCircle !== undefined)
            {
                OPS_Map.map.removeLayer(OPS_Map.SearchResultCircle);
            }
            var lat = latlon[1];
            var lon = latlon[0];
            OPS_Map.setCenter(parseFloat(lon), parseFloat(lat));
            OPS_Map.setZoom(16);
            OPS_Map.SearchResultCircle = OPS_Map.drawPointCircleInMeter(50, lat, lon);
            OPS_Map.SearchResultCircle.setZIndex(0);

        } else
        {
            console.log("no coords");
        }
    }
}

/**
 * 
 * @param {type} evt
 * @returns {undefined}
 */
OPS_Map.onMapClick = function (evt)
{
    var coordinates = evt.coordinate;
    {
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
        } else
        {
            if (OPS_Map.mode == "info")
                GetInfoPoint(coordinates, evt);
        }
    }

};
/**
 * 
 * @param {type} coordinates
 * @param {type} evt
 * @returns {undefined}
 */
function GetInfoPoint(coordinates, evt)
{
    $.ajax({
        type: 'GET',
        url: OPS_Map.geocodepath + 'splx_geocode.php?lat=' + coordinates[1] + '&lon=' + coordinates[0],
        dataType: "text",
        // data: {lat: coordinates.lat,lon:coordinates.lon},
        success: function (data) {
            var json = $.parseJSON(data);
            //console.log(json);
            var html = "";
            if (json['title'] != "")
                html += "<em>" + json['title'] + "</em><br />";
            html += "Regione&nbsp;" + json['regione'] + "<br />";

            if (json['provincia'] != "")
                html += "Provincia&nbsp;di&nbsp;" + json['provincia'] + "<br />";
            if (json['comune'] != "")
                html += "Comune&nbsp;di&nbsp;" + json['comune'] + "<br />";
            if (json['localita'] != "")
                html += "Localita&nbsp;" + json['localita'] + "<br />";
            if (json['grotte_in_provincia'] != 0)
                html += json['grotte_in_provincia'] + "&nbsp;grotte&nbsp;in&nbsp;questa provincia<br />";
            if (json['grotte_in_comune'] != 0)
                html += json['grotte_in_comune'] + "&nbsp;in&nbsp;questo&nbsp;comune<br />";
            if (json['grotte_in_localita'] != 0)
                html += json['grotte_in_localita'] + "&nbsp;in&nbsp;questa&nbsp;localita<br />";

            var address = "";
            var sep = "";
            if (json['localita'])
            {
                address += json['localita'] + sep;
                sep = ",";
            }
            if (json['comune'])
            {
                address += sep + json['comune'];
                sep = ",";
            }
            if (json['provincia'])
            {
                address += sep + json['provincia'];
                sep = ",";
            }
            if (json['regione'])
            {
                address += sep + json['regione'];
                sep = ",";
            }
            //$("#infopoint").html();
            OPS_Map.popUp(coordinates, "Info", Math.floor(coordinates[0] * 100000) / 100000 + "&nbsp;E<br />" + Math.floor(coordinates[1] * 100000) / 100000 + "&nbsp;N<br />" + html);

        },
        error: function () {
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
        }
    });
}
