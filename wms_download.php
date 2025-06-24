<?php ob_start(); ?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
    <title>WMS Downloader</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@master/en/v6.5.0/css/ol.css" type="text/css">
    <style>
        .map {
            height: 400px;
            width: 100%;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@master/en/v6.5.0/build/ol.js"></script>


    <style>.tt-query {
                -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
                -moz-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
                box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
            }

            .tt-hint {
                color: #999
            }

            .tt-menu {    /* used to be tt-dropdown-menu in older versions */
                width: 422px;
                margin-top: 4px;
                padding: 4px 0;
                background-color: #fff;
                border: 1px solid #ccc;
                border: 1px solid rgba(0, 0, 0, 0.2);
                -webkit-border-radius: 4px;
                -moz-border-radius: 4px;
                border-radius: 4px;
                -webkit-box-shadow: 0 5px 10px rgba(0,0,0,.2);
                -moz-box-shadow: 0 5px 10px rgba(0,0,0,.2);
                box-shadow: 0 5px 10px rgba(0,0,0,.2);
            }

            .tt-suggestion {
                padding: 3px 20px;
                line-height: 24px;
            }

            .tt-suggestion.tt-cursor,.tt-suggestion:hover {
                color: #fff;
                background-color: #0097cf;

            }

            .tt-suggestion p {
                margin: 0;
            }

            .popover-title{
                font-size: 11px;
                padding: 5px;
            }
            .popover-content{
                font-size: 11px;
                padding: 5px;

            }
            .popover{
                /*min-width: 150px;*/
            }
            .bsactive{
                color: greenyellow !important;
            }


            .popover-content .glyphicon{

            }
            .popover-content .glyphicon div a b{
                display:none
            }

        </style>    
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyASIqFkLBhPpSHxzFlsMgdxdrCpJbh3CkM&libraries=places"></script>

</head>

<body>
    
    <div class="container">
    <nav id="navbar" class="navbar navbar-inverse dropdown" role="navigation">
            <form onsubmit="return false;" class="mytoolbar navbar-left " role="search">
                <div class="form-group ">
                    <div class="btn-group" role="group" >
                        <input data-i18n='Aa' class="form-control typeahead" autocomplete="off" type="text" placeholder="Search" id="searchText" name="q"  value="" />
                    </div>
                </div>
            </form>
        </nav>         
        <h3>Seleziona il centro dalla mappa</h3>
        <div id="map" class="map"></div>
        <section class="">
            <?php
            require_once "extra/openkis_geoconverter/openkis_geoconverter.class.php";
            $lat = isset($_GET['lat']) ? $_GET['lat'] : 44.4425488;
            $lon = isset($_GET['lon']) ? $_GET['lon'] : 8.847569;
            $download = isset($_GET['download']) ? true : false;
            $download_txt = isset($_GET['download_txt']) ? true : false;

            //----------------------------------------------------------------------------->

            $dtm['url'] = "http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/QUOTE/1356.asp?";
            $dtm['layer'] = "M1356";
            $dtm['title'] = "DTM liguria";
            /*
                  $dtm['url'] = "http://wms.pcn.minambiente.it/ogc?map=/ms_ogc/WMS_v1.3/servizi-LiDAR/LIDAR_LIGURIA.map&";
                  $dtm['layer'] = "EL.LIDAR.LIGURIA.1x1.DSM_FIRST";
                  $dtm['title'] = "DTM liguria";

                 */




            $hsize = isset($_GET['hsize']) ? $_GET['hsize'] : 400;
            $wsize = isset($_GET['wsize']) ? $_GET['wsize'] : 400;
            $h = isset($_GET['h']) ? $_GET['h'] : 600;
            $w = isset($_GET['w']) ? $_GET['w'] : 600;
            $zoom = isset($_GET['zoom']) ? $_GET['zoom'] : 10;

            //-----------------------------------------------------------------------------<
            $format = isset($_GET['format']) ? $_GET['format'] : "image/gif";
            //calcolo le coordinate
            $coordinate = new openkis_geoconverter($lat, $lon, "GEOWGS84");
            $kmcoords = $coordinate->getUTM("WGS84");
            /*
                  dprint_r($kmcoords);
                 */
            $y = $kmcoords['northing'];
            $x = $kmcoords['easting'];
            //-------------------------formati immagine------------------------------------>
            $formats = array();
            $formats[] = ("image/tiff"); //georeferenziato
            $formats[] = ("image/gif");
            $formats[] = ("image/jpeg");
            $formats[] = ("image/png");
            //-------------------------formati immagine------------------------------------<
            $wms[0]['url'] = "http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/CARTE_TECNICHE/1238.asp?";
            $wms[0]['layer'] = "M1238";
            $wms[0]['title'] = "Liguria CTR";

            $wms[1]['url'] = "https://geoservizi.regione.liguria.it/geoserver/M2248/ows?";
            $wms[1]['layer'] = "L8256";
            $wms[1]['title'] = "Liguria Ortofoto";

            $wms[2]['url'] = "http://sgi2.isprambiente.it/arcgis/services/raster/geo_100k_italia/ImageServer/WMSServer?";
            $wms[2]['layer'] = "0";
            $wms[2]['title'] = "Geologica 100k";

            $wms[3]['url'] = "http://sgi2.isprambiente.it/arcgis/services/raster/geo_50k_italia/ImageServer/WMSServer?";
            $wms[3]['layer'] = "0";
            $wms[3]['title'] = "Geologica 50k";

            $wms[4]['url'] = "http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/QUOTE/1356.asp?";
            $wms[4]['layer'] = "M1356";
            $wms[4]['title'] = "DTM liguria";

            //http://wms.pcn.minambiente.it/ogc?map=/ms_ogc/WMS_v1.3/servizi-LiDAR/LIDAR_LIGURIA.map&SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&BBOX=44.10639466425749333,8.407549313711612626,44.70000000000000284,9.638559708722013397&CRS=EPSG:4326&WIDTH=955&HEIGHT=461&LAYERS=EL.LIDAR.LIGURIA.1x1.DSM_FIRST&STYLES=&FORMAT=image/png&DPI=96&MAP_RESOLUTION=96&FORMAT_OPTIONS=dpi:96&TRANSPARENT=TRUE
            $wms[5]['url'] = "http://wms.pcn.minambiente.it/ogc?map=/ms_ogc/WMS_v1.3/servizi-LiDAR/LIDAR_LIGURIA.map&";
            $wms[5]['layer'] = "EL.LIDAR.LIGURIA.1x1.DSM_FIRST";
            $wms[5]['title'] = "DSM liguria";

            /* DTM - Modello Digitale del Terreno - Liguria ed. 2017	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/QUOTE/2056.asp
                  DTM - Modello Digitale del Terreno da CTR 1:5000 - 2007/2013- II ed. 3D/DB Topografico	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/QUOTE/1356.asp
                  DTM - Modello Digitale del Terreno da CTR Parco di Portofino sc. 1:2000 - ed. 2003 passo 10 m	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/QUOTE/1660.asp */

            //?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=jpeg&TRANSPARENT=true&LAYERS=M1361&PROJECTION=EPSG%3A900913&srs=EPSG%3A900913&WIDTH=512&HEIGHT=512&CRS=EPSG%3A900913&STYLES=&FORMAT_OPTIONS=dpi%3A180&BBOX=939258.2035682462%2C5400734.670517413%2C1017529.7205322667%2C5479006.187481433

            /*
                 *  gdal_translate ./dtm.tif -b 1 -a_srs EPSG:32632  ./dtm.out.tif
                 *  gdal_translate -of AAIGrid dem.tif dem.txt
                 * 
                 * 
                 *   surface # declaration of the height model
                  grid 4549000 5279000 30 30 267 267 # grid x_min y_min x_resolution y_resolution n_cols n_rows
                  # followed by the matrix of the elevation data (from dem.txt):
                  1123 1234 ...
                  ...
                  # (data not shown completely)
                  endsurface
                 * 
                 */

            $wms_base = isset($_REQUEST['wms_base']) ? $_REQUEST['wms_base'] : $wms[0]['url'];
            $layer = isset($_REQUEST['layer']) ? $_REQUEST['layer'] : $wms[0]['layer'];
            $wms_base_dtm = $dtm['url'];
            $layer_dtm = $dtm['layer'];



            echo "<form method=\"get\" action=\"" . siteurl() . "\" >";

            echo "<h3>WMS downloader</h3>";

            echo "<h3>Area selezionata</h3>";
            echo "Longitudine:<input type=\"text\" id=\"lon\" name=\"lon\" value=\"$lon\" /><br />";
            echo "Latitudine:<input type=\"text\"  id=\"lat\" name=\"lat\" value=\"$lat\" /><br />";
            echo "Lato Y in metri:<input name=\"hsize\" type=\"text\" value=\"$hsize\" /><br />";
            echo "Lato X in metri:<input name=\"wsize\" type=\"text\" value=\"$wsize\" /><br />";
            echo "<input id=\"zoom\" name=\"zoom\" type=\"hidden\" value=\"$zoom\" />";

            echo "<h4>Server WMS</h4>";
            echo "<h6>Server predefiniti</h6>";
            foreach ($wms as $wmslayer) {
                echo "<button class=\"btn btn-primary\" type=\"button\" onclick=\"setLayer('" . ($wmslayer['url']) . "','" . ($wmslayer['layer']) . "');\" >{$wmslayer['title']}</button> ";
            }
            echo "<br />URL WMS base:<br /><textarea id=\"wmsUrl\" cols=\"80\" rows=\"4\"  name=\"wms_base\">$wms_base</textarea><br />";
            echo "Layer:<input name=\"layer\"  id=\"wmsLayer\" type=\"text\" value=\"$layer\" /><br />";


            echo "<h3>Immagine:</h3>";
            echo "Altezza immagine in pixel:<input name=\"h\" type=\"text\" value=\"$h\" /><br />";
            echo "Larghezza immagine in pixel:<input name=\"w\" type=\"text\" value=\"$w\" /><br />";
            echo "Formato: <select name=\"format\">";

            foreach ($formats as $cformat) {
                $s = ($cformat == $format) ? "selected=\"selected\"" : "";
                echo "\n\t<option $s value=\"" . $cformat . "\">$cformat</option>";
            }
            echo "</select>";
            echo "<button class=\"btn btn-primary\" type=\"submit\" >Visualizza anteprima immagine</button>";
            echo "&nbsp;&nbsp;&nbsp;<input class=\"btn btn-danger\" type=\"submit\" name = \"download\" value=\"Scarica immagine su disco\" />";
            echo "&nbsp;&nbsp;&nbsp;<input class=\"btn btn-danger\" type=\"submit\" name = \"download_txt\" value=\"Scarica txt dtm su disco\" />";


            echo "</fieldset>";
            echo "</form>";

            $h_m_size = round($hsize / 2);
            $w_m_size = round($wsize / 2);

            $b1 = $x - $w_m_size;
            $b2 = $y - $h_m_size;
            $b3 = $x + $w_m_size;
            $b4 = $y + $h_m_size;
            $b1_utm = $b1;
            $b2_utm = $b2;
            $b3_utm = $b3;
            $b4_utm = $b4;

            $conv = new openkis_geoconverter($b2, $b1, "UTMWGS8432");
            $b1lanlot = $conv->getWGS84Geo("WGS84");
            $b1_gbcoord = $conv->getGAUSSBOAGA_ZONE_1();

            //dprint_r($b1lanlot);
            $b1 = $b1lanlot['lon'];
            $b2 = $b1lanlot['lat'];
            $b1_gb = $b1_gbcoord['lon'];
            $b2_gb = $b1_gbcoord['lat'];

            $conv = new openkis_geoconverter($b4, $b3, "UTMWGS8432");
            $b1lanlot = $conv->getWGS84Geo("WGS84");
            $b1_gbcoord = $conv->getGAUSSBOAGA_ZONE_1();
            $b3 = $b1lanlot['lon'];
            $b4 = $b1lanlot['lat'];
            $b3_gb = $b1_gbcoord['lon'];
            $b4_gb = $b1_gbcoord['lat'];





            //bbox xy xy

            $projection = urlencode("EPSG:4326");
            //$SRS = urlencode("EPSG:3003");
            $SRS = urlencode("EPSG:4326");

            $uformat = urlencode($format);


            //$options = "&LAYERS=$layer&PROJECTION=$projection&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&FORMAT=$uformat&SRS=$SRS&BBOX=$b1,$b2,$b3,$b4&WIDTH=$w&HEIGHT=$h";
            //?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=jpeg    &TRANSPARENT=true &LAYERS=M1361 &PROJECTION=EPSG%3A900913& srs=EPSG%3A900913&WIDTH=512&HEIGHT=512&CRS=EPSG%3A900913&STYLES=&FORMAT_OPTIONS=dpi%3A180&BBOX=939258.2035682462%2C5400734.670517413%2C1017529.7205322667%2C5479006.187481433
            $options_geo = "SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=$uformat&LAYERS=$layer&PROJECTION=$projection&srs=$SRS&BBOX=$b1,$b2,$b3,$b4&WIDTH=$w&HEIGHT=$h&CRS=EPSG%3A900913";
            $options_utm = "SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=$uformat&LAYERS=$layer&PROJECTION=$projection&srs=$SRS&BBOX=$b1_utm,$b2_utm,$b3_utm,$b4_utm&WIDTH=$w&HEIGHT=$h&CRS=EPSG%3A32632";
            $options_gb = "SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=$uformat&LAYERS=$layer&PROJECTION=$projection&srs=$SRS&BBOX=$b1_gb,$b2_gb,$b3_gb,$b4_gb&WIDTH=$w&HEIGHT=$h&EPSG%3A3003&CRS=EPSG:3003";

            $options = $options_gb;


            //ok:http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/CARTE_TECNICHE/1238.asp?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=jpeg&TRANSPARENT=true&LAYERS=M1238&PROJECTION=EPSG%3A900913&srs=EPSG%3A900913&WIDTH=512&HEIGHT=512&CRS=EPSG%3A900913&STYLES=&FORMAT_OPTIONS=dpi%3A180&BBOX=983897.4280867875%2C5534958.092186183%2C984203.1761999282%2C5535263.840299323
            //ko:http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/CARTE_TECNICHE/1238.asp?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=jpeg&TRANSPARENT=false&LAYERS=M1238&PROJECTION=EPSG%3A4326&srs=EPSG%3A4326&BBOX=8.8450581206044,44.440745123211,8.8500753942986,44.444352942546&WIDTH=600&HEIGHT=600&CRS=EPSG%3A900913

            $url = $wms_base . $options;

            echo "URL WMS:<br /><textarea readonly=\"readonly\" cols=\"80\" rows=\"4\" type=\"text\" value=\"$url\" >$url</textarea><br />$format<br />";
            if ($format != "image/tiff") {
                echo "<img loading=\"lazy\" src=\"$url\" />";
                if (!empty($_GET['debug'])) {
                    echo "<pre style=\"border:1px solid red\">";
                    echo (file_get_contents($url));
                    echo "</pre>";
                }
            } else {
                /*
                      echo "<pre style=\"border:1px solid red\">";
                      echo (file_get_contents($url));
                      echo "</pre>";

                     */
            }

            if ($download) {
                $extension = explode("/", $format);
                $extension = $extension[1];
                if ($extension == "tiff")
                    $extension = "tif";
                $imagename = $lat . "-" . $lon . "_{$hsize}x$wsize";
                $str = file_get_contents($url);
                FN_SaveFile($str, $imagename . "." . $extension);
            }

            //---------DTM:------>
            $h_wms = max(0, intval($hsize / 2));
            $w_wms = max(0, intval($wsize / 2));
            $options_dtm = "SERVICE=WMS&VERSION=1.1.0&REQUEST=GetMap&FORMAT=image/png&LAYERS=$layer_dtm&PROJECTION=$projection&srs=$SRS&BBOX=$b1,$b2,$b3,$b4&WIDTH=$w_wms&HEIGHT=$h_wms&CRS=EPSG%3A900913";

            $x_resolution = ($b3_utm - $b1_utm) / $w_wms;
            $y_resolution = ($b4_utm - $b2_utm) / $h_wms;
            $str_txt = "surface
cs utm32
grid $b1_utm $b2_utm $x_resolution $y_resolution $w_wms $h_wms # grid x_min y_min x_resolution y_resolution n_cols n_rows
bitmap ./bitmap.png [0 0 $b1_utm $b2_utm $h $w $b3_utm $b4_utm]\n";

            $url_dtm = $wms_base_dtm . $options_dtm;
            $tmp_file = "misc/tmp_img.png";
            //dprint_r("$h_wms x $w_wms");
            if ($download_txt) {
                file_put_contents($tmp_file, file_get_contents($url_dtm));
                $im = imagecreatefrompng("$tmp_file");

                for ($y = 0; $y < $h_wms; $y++) {
                    for ($x = 0; $x < $w_wms; $x++) {
                        $rgb = imagecolorat($im, $x, $y);
                        $colors = imagecolorsforindex($im, $rgb);
                        //                            $str_txt .= ($colors['red'] + $colors['green'] + $colors['blue']) . " ";
                        $quote = $colors['red'];
                        if ($quote == 255) {
                            $quote = 0;
                        }
                        $quote = $quote;
                        $str_txt .= "$quote ";
                    }
                    $str_txt .= "\n";
                }
                //---------DTM:------<
                $str_txt .= "\nendsurface";

                FN_SaveFile($str_txt, "dem_web.txt");
            } else {
                $str_txt .= "\nendsurface";
            }


            echo "<br />Codice Therion: bitmap ./bitmap.png [0 0 $b1_utm $b2_utm $hsize $wsize $b3_utm $b4_utm] ";
            echo "<br /><textarea style=\"width:100%;height:100px;\">$str_txt</textarea>";
            //               echo "<br />bitmap ./bitmap.png [0 0 $b1 $b2 $hsize $hsize $b3 $b4] ";
            ?>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
    <script>
        function setLayer(url, layer) {
            var el;
            el = document.getElementById("wmsUrl");
            el.innerText = url;
            el = document.getElementById("wmsLayer");
            el.value = layer;
        }
    </script>
    <script type="text/javascript">
        var localmap = new ol.Map({
            layers: [
                new ol.layer.Tile({
                    source: new ol.source.OSM()
                })
            ],
            target: 'map',
            view: new ol.View({
                center: [0, 0],
                zoom: 2
            })
        });

        function getPosition(event) {
            console.log(localmap.getEventCoordinate(event));
        }
        // window.setTimeout(function () {
        localmap.on('singleclick', function(evt) {
            console.log(evt.coordinate);

            // convert coordinate to EPSG-4326
            var latlon = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
            console.log();
            document.getElementById("lat").value = latlon[1];
            document.getElementById("lon").value = latlon[0];
            document.getElementById("zoom").value = localmap.getView().getZoom();
            //alert(document.getElementById("zoom").value);

        });
        var getUrlVar = function(name, defaultValue) {
            if (defaultValue === undefined) {
                defaultValue = "";
            }
            name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
            var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                results = regex.exec(location.search);
            return results === null ? defaultValue : decodeURIComponent(results[1].replace(/\+/g, " "));
        };

        var lat = getUrlVar("lat", 44);
        var lon = getUrlVar("lon", 9);
        var zoom = getUrlVar("zoom", 10);
        localmap.getView().setCenter(ol.proj.transform([lon, lat], 'EPSG:4326', 'EPSG:3857'));
        localmap.getView().setZoom(zoom);
    </script>


    <script>
        function initialize() {
            var input = document.getElementById('searchText');
            var autocomplete = new google.maps.places.Autocomplete(input);

            google.maps.event.addListener(autocomplete, 'place_changed', function() {
                var place = autocomplete.getPlace();
                var location = "Lat: " + place.geometry.location.lat() + " Long: " + place.geometry.location.lng();
                //localmap.getView().setCenter(parseFloat(place.geometry.location.lng()), parseFloat(place.geometry.location.lat()));
                var lat = parseFloat(place.geometry.location.lat());
                var lon = parseFloat(place.geometry.location.lng());
                localmap.getView().setCenter(ol.proj.transform([lon, lat], 'EPSG:4326', 'EPSG:3857'));
            });
        }
        google.maps.event.addDomListener(window, 'load', initialize);
    </script>
    <br /><a target="_blank" href="http://srvcarto.regione.liguria.it/geoviewer2/pages/apps/geoportale/index.html">Geoportale Liguria</a>
</body>

</html>
<?php

/**
 *
 * @param type $var
 * @param type $str 
 */
function dprint_r($var, $str = "")
{
    echo "<pre style=\"font-size:10px;line-height:12px;border:1px solid green\">";
    echo "$str\n";
    print_r($var);
    echo "</pre>";
}

/**
 *
 * @return string 
 */
function siteurl()
{
    return $_SERVER['PHP_SELF'];
    $dirname = dirname($_SERVER['PHP_SELF']);
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $serverpath = dirname($_SERVER['SCRIPT_FILENAME']);
    } elseif (isset($_SERVER['PATH_TRANSLATED'])) {
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
        $serverpath = dirname($_SERVER['PATH_TRANSLATED']);
    }
    if ($dirname == "/" || $dirname == "\\")
        $dirname = "";
    // server windows
    $dirname = str_replace("\\", "/", $dirname);
    $protocol = "http://";
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
        $protocol = "https://";
    if (isset($_SERVER['HTTP_HOST'])) {
        $siteurl = "$protocol" . $_SERVER['HTTP_HOST'] . $dirname;
        if (substr($siteurl, strlen($siteurl) - 1, 1) != "/") {
            $siteurl = $siteurl . "/";
        }
    } else
        $siteurl = "";
    return $siteurl;
}

/**
 *
 * @param type $filecontents
 * @param string $filename
 * @param type $HeaderContentType 
 */
function FN_SaveFile($filecontents, $filename, $HeaderContentType = "application/force-download")
{
    while (
        false !== ob_get_clean()
    );
    if (!$filename) {
        $filename = "esportazione.xls";
    }
    header("Content-Type: $HeaderContentType");
    header("Content-Disposition: inline; filename=$filename");
    echo "$filecontents";
    die();
}
?>