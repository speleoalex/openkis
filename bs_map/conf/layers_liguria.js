OPS_Map.MyAddLayers = function ()
{
//title, url, isBaselayer, visible
//
    OPS_Map.addOSMLayer("Topo", 'https://a.tile.opentopomap.org/{z}/{x}/{y}.png', true, true);
    OPS_Map.addOSMLayer("Satellite", "https://api.maptiler.com/tiles/satellite/{z}/{x}/{y}.jpg?key=vcy9GHtGmS1pAPPsJYdW", true, false);
    OPS_Map.addOSMLayer("OSM Landscape", "https://a.tile.thunderforest.com/landscape/{z}/{x}/{y}.png?apikey=ae3c5645f1f3440bb1999f77b56164ad", true, false);
    //(title, url, layername, isBaselayer, visible, format, projecton)
    //OPS_Map.addWMSLayer("IGM 1:25000 Liguria", "http://bbsuite.nivolacloud.com:8080/geoserver/IGM/wms", 'IGM:igm25000_256', true, true, "image/jpeg");
    OPS_Map.addWMSLayer("IGM 1:25000 Liguria", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/igm25k_liguria_wgs/ImageServer/WMSServer", 'igm25k_piemonte_wgs', false, false, "png", 'EPSG:4326');
    OPS_Map.addWMSLayer("Geologica 1:50000", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/geo_50k_italia/ImageServer/WMSServer", '0', false, false, "png", 'EPSG:4326');
    OPS_Map.addWMSLayer("Geologica 1:100000", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/geo_100k_italia/ImageServer/WMSServer", '0', false, false, "png", 'EPSG:4326');
    OPS_Map.addWMSLayer("Ortofoto2007", "openkis_proxy.php/http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/ORTOFOTO/1361.asp?", 'M1361', true, true, "jpeg");
    OPS_Map.addWMSLayer("CTR 1:10000", "openkis_proxy.php/http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/CARTE_TECNICHE/1238.asp?", 'M1238', true, true);
    OPS_Map.addWMSLayer("Cartografia catastale", "openkis_proxy.php/http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/PIANIFICAZIONE/1162.asp?", 'M1162', false, false);
    OPS_Map.addWMSLayer("Idrografia", "openkis_proxy.php/http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/ACQUE_INTERNE/1172.asp?", 'M1172', false, false);

    var layers = getUrlVar("layers", "");
    var mod = getUrlVar("mod", "");
    var nv_areas = getUrlVar("nv_areas", "");
    var filter_code = getUrlVar("filter_code", "");
    if (filter_code !== "")
    {
        OPS_Map.addKmlLayer("Cavita selezionate", "openkis_kml.php?mod=caves&filter_code=" + filter_code, false, mod === "caves");
    } else
    {
        if (nv_areas != "")
            OPS_Map.addKmlLayer("Cavita naturali area", "openkis_kml.php?mod=caves&nv_areas=" + nv_areas, false, mod === "caves");
        else
        {
            OPS_Map.addKmlLayer("Cavita naturali", "openkis_kml.php" + location.search, false, mod === "caves");
        }
    }
    var history = getUrlVar("history", "");
    if (history !== "")
    {
        OPS_Map.addKmlLayer("Storico coordinate", "openkis_kml_history.php?mod=caves&history=" + history, false, false, true);
    }

    OPS_Map.addKmlLayer("Sorgenti carsiche", "openkis_kml.php?mod=springs", false, mod === "springs");
    OPS_Map.addKmlLayer("Cavita artificiali", "openkis_kml.php?mod=artificials", false, mod === "artificials");
    OPS_Map.addKmlLayer("Catasto grotte Piemonte", "openkis_proxy.php/http://catastogrotte-piemonte.net/openkis_kml.php?mod=caves", false, false);
    OPS_Map.addKmlLayer("Mappa grotte del Finalese OSM", "bs_map/mapdata/mappa_grotte_del_finalese.kml", false, false);

    OPS_Map.addKmlLayer("Mappa grotte del Finalese E.M.", "grotte_finalese_em.kml.php", false, false);
    
    
    //    OPS_Map.addJsLayer("openkis_markers.js.php?mod=caves", false);
    splx.include("bs_surveys.js.php?t=areas&mod=" + mod);
    splx.include("bs_surveys.js.php?mod=" + mod);
    splx.include("bs_surveys.js.php?t=artificials&mod=" + mod);
    
};
