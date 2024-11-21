OPS_Map.MyAddLayers = function ()
{

    OPS_Map.addOSMLayer("Topo", 'https://a.tile.opentopomap.org/{z}/{x}/{y}.png', true, false);

    OPS_Map.addOSMLayer("Satellite", "https://api.maptiler.com/tiles/satellite/{z}/{x}/{y}.jpg?key=REDACTED_MAPTILER_KEY", true, false);


    OPS_Map.addOSMLayer("OSM Landscape", "https://a.tile.thunderforest.com/landscape/{z}/{x}/{y}.png?apikey=REDACTED_THUNDERFOREST_KEY", true, false);

    // Lontra 3-5-2024
    //addWMSLayer: function (title, url, layername, isBaselayer, visible, format, projecton, attributions)
    OPS_Map.addWMSLayer("CTR_DBT-Regione Lombardia","https://www.cartografia.servizirl.it/arcgis2/rest/services/BaseMap/CTR_DBT/MapServer","0",true,true,"png", 'EPSG:32632',"aaa");
    
    OPS_Map.addWMSLayer("IGM 1:25000 Piemonte", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/igm25k_piemonte_wgs/ImageServer/WMSServer", 'igm25k_piemonte_wgs', false, false, "png", 'EPSG:4326');

    //OPS_Map.addWMSLayer("IGM 1:25000 Liguria", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/igm25k_liguria_wgs/ImageServer/WMSServer", 'igm25k_piemonte_wgs', false, false, "png", 'EPSG:4326');

    OPS_Map.addWMSLayer("Geologica 1:50000", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/geo_50k_italia/ImageServer/WMSServer", '0', false, false, "png", 'EPSG:4326');

    OPS_Map.addWMSLayer("Geologica 1:100000", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/geo_100k_italia/ImageServer/WMSServer", '0', false, false, "png", 'EPSG:4326');


  


    var layers = getUrlVar("layers", "");
    var mod = getUrlVar("mod", "");
    var nv_areas = getUrlVar("nv_areas", "");
    var filter_code = getUrlVar("filter_code", "");

    if (filter_code !== "")
    {
        OPS_Map.addKmlLayer("Cavita selezionate", "openkis_kml.php?iconsize=1.5&mod=caves&filter_code=" + filter_code, false, mod === "caves", true);
    } else
    {
        if (nv_areas != "")
            OPS_Map.addKmlLayer("Cavita naturali area", "openkis_kml.php?iconsize=1.5&mod=caves&nv_areas=" + nv_areas, false, mod === "caves");
        else
        {
            OPS_Map.addKmlLayer("Cavita naturali", "openkis_kml.php" + location.search+"&iconsize=1.5", false, mod === "caves");
        }
    }

    var history = getUrlVar("history", "");
    if (history !== "")
    {
        OPS_Map.addKmlLayer("Storico coordinate", "openkis_kml_history.php?mod=caves&history=" + history, false, false, true);
    }

    OPS_Map.addKmlLayer("Sorgenti carsiche", "openkis_kml.php?mod=springs", false, mod === "springs");

    OPS_Map.addKmlLayer("Cavita artificiali", "openkis_kml.php?mod=artificials&minimal=1", false, mod === "artificials");

    //OPS_Map.addKmlLayer("Catasto grotte Liguria", "openkis_proxy.php/https://catastogrotte.net/liguria/openkis_kml.php?mod=caves&time=1&minimal=1", false, false);

    //OPS_Map.addKmlLayer("Catasto grotte Piemonte", "openkis_proxy.php/http://catastogrotte-piemonte.net/openkis_kml.php?mod=caves", false, false);
    
    splx.include("bs_surveys.js.php?mod=" + mod);

};
