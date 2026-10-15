OPS_Map.MyAddLayers = function ()
{
    // Base layers — Topo as default (first = checked by default in switcher)
    OPS_Map.addOSMLayer("Topo", 'https://a.tile.opentopomap.org/{z}/{x}/{y}.png', true, true, null, 17);
    OPS_Map.addOSMLayer("OSM", 'https://{a-c}.tile.openstreetmap.org/{z}/{x}/{y}.png', true, false);
    OPS_Map.addOSMLayer("Satellite", "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", true, false);

    // Blank/white base layer (no tiles — shows map container white background)
    var blankLayer = new ol.layer.Tile({ name: "Bianco", visible: false });
    OPS_Map.addLayer(blankLayer, true);

    var mod = getUrlVar("mod", "");
    var nv_areas = getUrlVar("nv_areas", "");
    var filter_code = getUrlVar("filter_code", "");
    var history = getUrlVar("history", "");

    if (filter_code !== "")
    {
        OPS_Map.addKmlLayer("Cavita selezionate", "openkis_kml.php?mod=caves&filter_code=" + filter_code + "&iconsize=3", false, true, true);
    }
    else if (nv_areas !== "")
    {
        OPS_Map.addKmlLayer("Grotte naturali area", "openkis_kml.php?mod=caves&nv_areas=" + nv_areas + "&iconsize=3", false, true);
    }
    else
    {
        OPS_Map.addKmlLayer("Grotte naturali", "openkis_kml.php?mod=caves&iconsize=3", false, true);
    }

    if (history !== "")
        OPS_Map.addKmlLayer("Storico coordinate", "openkis_kml_history.php?mod=caves&history=" + history, false, false, true);

    OPS_Map.addKmlLayer("Cavita artificiali", "openkis_kml.php?mod=artificials&minimal=1&iconsize=3", false, mod === "artificials");
    OPS_Map.addKmlLayer("Gruppi speleologici", "openkis_kml.php?mod=groups&iconsize=3", false, true);
};

// jQuery 3 made $(document).ready() async (Promise-based), so
// addLayerSwitcher's $(function(){...}) no longer runs synchronously.
// When switchBaseLayer() is called right after, the radio buttons don't
// exist yet → checkedLayer=undefined → all layers setVisible(0) → white map.
// Fix: ensure printLayerSwitcher has run before reading the checked value.
OPS_Map.Custom = function ()
{
    var _orig = OPS_Map.switchBaseLayer.bind(OPS_Map);
    OPS_Map.switchBaseLayer = function ()
    {
        if ($('#ops_baselayerswitcher').length === 0)
        {
            OPS_Map.printLayerSwitcher();
            OPS_Map.printLayerTree();
        }
        _orig();
    };
};
