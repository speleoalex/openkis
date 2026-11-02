OPS_Map.MyAddLayers = function ()
{
    // Lontra 25-10-2026
    OPS_Map.addOSMLayer("OSM", 'https://{a-c}.tile.openstreetmap.org/{z}/{x}/{y}.png', true, false);
    // CARTO basemaps now require an API key: use Esri Light Gray Canvas (keyless, native tiles up to z16)
    OPS_Map.addOSMLayer("Dark Gray", "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}", true, false, "Anonymous", 16);
    OPS_Map.addOSMLayer("Light Gray", "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}", true, false, "Anonymous", 16);
    OPS_Map.addOSMLayer("Satellite", "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", true, false);
    OPS_Map.addOSMLayer("Topo", 'https://a.tile.opentopomap.org/{z}/{x}/{y}.png', true, false, null, 17);

    //OPS_Map.addOSMLayer("OSM Landscape", "https://a.tile.thunderforest.com/landscape/{z}/{x}/{y}.png?apikey=" + API_KEYS.THUNDERFOREST, true, false);
    // Lontra 14-7-2024
    //addWMSLayer: function (title, url, layername, isBaselayer, visible, format, projecton, attributions)
    // Regione Lombardia WMS: EPSG:3857 (the map works in EPSG:900913, which these servers reject)
    OPS_Map.addWMSLayer("DBTR ToniGrigio-Regione Lombardia","https://www.cartografia.servizirl.it/arcgis2/services/BaseMap/dbtr_toni_grigio/MapServer/WmsServer?","0",true,false,"png", 'EPSG:3857',"Credits Regione Lombardia");
    OPS_Map.addWMSLayer("CTR_DBT-Regione Lombardia","https://www.cartografia.servizirl.it/arcgis2/services/BaseMap/CTR_DBT/MapServer/WmsServer?","0",true,false,"png", 'EPSG:3857',"Credits Regione Lombardia");
    OPS_Map.addWMSLayer("Ortofoto Agea 2021 - Regione Lombardia","https://www.cartografia.servizirl.it/arcgis2/services/BaseMap/Ortofoto2021/ImageServer/WMSServer?","0",true,false,"png", 'EPSG:3857',"Le riprese aeree sono state fatte da AGEA (Agenzia per le erogazioni in agricoltura) nel corso del 2021 e sono ancora in fase di collaudo.");
    //OPS_Map.addOSMLayer("Satellite", "https://api.maptiler.com/tiles/satellite/{z}/{x}/{y}.jpg?key=" + API_KEYS.MAPTILER, true, false);
    //OPS_Map.addOSMLayer("Topo", 'https://a.tile.opentopomap.org/{z}/{x}/{y}.png', true, false);


    //OPS_Map.addWMSLayer("IGM 1:25000 Piemonte", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/igm25k_piemonte_wgs/ImageServer/WMSServer", 'igm25k_piemonte_wgs', false, false, "png", 'EPSG:4326');

    //OPS_Map.addWMSLayer("IGM 1:25000 Liguria", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/igm25k_liguria_wgs/ImageServer/WMSServer", 'igm25k_piemonte_wgs', false, false, "png", 'EPSG:4326');

    OPS_Map.addWMSLayer("Geologica 1:50000", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/geo_50k_italia/ImageServer/WMSServer", '0', false, false, "png", 'EPSG:4326');

    OPS_Map.addWMSLayer("Geologica 1:100000", "openkis_proxy.php/http://sgi2.isprambiente.it/arcgis/services/raster/geo_100k_italia/ImageServer/WMSServer", '0', false, false, "png", 'EPSG:4326');

    //console.log(OPS_Map.addOSMLayer.toString());
    //console.log(OPS_Map.addWMSLayer.toString());


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

    // shown and framed on the arrows when the page asks for it (tracings section: tracings=1)
    OPS_Map.addTracingsLayerFromUrl("Tracciamenti");
    OPS_Map.addTemperaturesLayerFromUrl("Temperature");

    //OPS_Map.addKmlLayer("Catasto grotte Liguria", "openkis_proxy.php/https://catastogrotte.net/liguria/openkis_kml.php?mod=caves&time=1&minimal=1", false, false);

    //OPS_Map.addKmlLayer("Catasto grotte Piemonte", "openkis_proxy.php/http://catastogrotte-piemonte.net/openkis_kml.php?mod=caves", false, false);
    
    splx.include("bs_surveys.js.php?mod=" + mod);

};