OPS_Map.MyAddLayers = function ()
{
    OPS_Map.addOSMLayer("Satellite", "https://api.maptiler.com/tiles/satellite/{z}/{x}/{y}.jpg?key=vcy9GHtGmS1pAPPsJYdW", true, false);
    OPS_Map.addOSMLayer("OSM Landscape", "https://a.tile.thunderforest.com/landscape/{z}/{x}/{y}.png?apikey=ae3c5645f1f3440bb1999f77b56164ad", true, false);
    OPS_Map.addOSMLayer("OSM Cyclemap", 'https://a.tile.thunderforest.com/cycle/{z}/{x}/{y}.png?apikey=ae3c5645f1f3440bb1999f77b56164ad', true, false);
    //addWMSLayer: function (title, url, layername, isBaselayer, visible,format)
    OPS_Map.addWMSLayer("IGM 1:25000", "http://bbsuite.nivolacloud.com:8080/geoserver/IGM/wms?map=/ms_ogc/WMS_v1.3/raster/IGM_25000.map&", 'igm25000_256', true, true, "image/jpeg");
//    OPS_Map.addWMSLayer("IGM 1:25000_miniambiente", "http://wms.pcn.minambiente.it/ogc?map=/ms_ogc/WMS_v1.3/raster/IGM_25000.map&", 'CB.IGM25000', true, true);

    OPS_Map.addWMSLayer("Ortofoto2007", "http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/ORTOFOTO/1361.asp?", 'M1361', true, true, "jpeg");
    OPS_Map.addWMSLayer("CTR 1:10000", "http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/CARTE_TECNICHE/1238.asp?", 'M1238', true, true);
    OPS_Map.addWMSLayer("Cartografia catastale", "http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/PIANIFICAZIONE/1162.asp?", 'M1162', false, false);

    var geo = new Array();
    geo["CARG Rossiglione 10000"] = "1288";
    geo["CARG Monte Tobbio 10000"] = "1287";
    geo["CARG Monte delle Figne 10000"] = "1289";
    geo["CARG Busalla 10000"] = "1290";
    geo["CARG Campoligure 10000"] = "1291";
    geo["CARG Praglia 10000"] = "1292";
    geo["CARG Campomorone 10000"] = "1293";
    geo["CARG Serra Ricco 10000"] = "1294";
    geo["CARG P.del Turchino 10000"] = "1295";
    geo["CARG Acquasanta 10000"] = "1296";
    geo["CARG Pontedecimo 10000"] = "1285";
    geo["CARG Sant Olcese 10000"] = "1297";
    geo["CARG Arenzano 10000"] = "1298";
    geo["CARG Pegli 10000"] = "1299";
    geo["CARG Sestri Ponente 10000"] = "1286";
    geo["CARG Genova 10000"] = "1300";
    geo["CARG Boccadasse 10000"] = "1301";
    geo["CARG Campomorone 25000"] = "664";
    geo["CARG Genova 25000"] = "666";
    geo["CARG Genova Pegli 25000"] = "668";
    geo["CARG Campoligure 25000"] = "670";
    geo["CARG Rovegno 25000"] = "1094";
    geo["CARG Cicagna 25000"] = "1096";
    geo["CARG Torriglia 25000"] = "1098";
    geo["CARG Borzonasca 25000"] = "660";
    geo["CARG S StefanoDAveto 25000"] = "662";
    geo["CARG Varese Ligure"] = "560";
    geo["CARG Deiva Marina 25000"] = "559";
    geo["CARG Sestri Levante 25000"] = "558";
    geo["CARG Lavagna 25000"] = "557";
    geo["CARG Sarzana 25000"] = "975";
    geo["CARG Lerici 25000"] = "976";
    geo["CARG Fabiano 25000"] = "977";
    geo["CARG La Spezia 25000"] = "978";
    geo["CARG Badalucco 25000"] = "565";
    geo["CARG Taggia 25000"] = "564";
    geo["CARG Sanremo 25000"] = "563";
    geo["CARG Triora 25000"] = "562";
    geo["CARG Buggio 25000"] = "561";
    geo["CARG La Spezia 50000"] = "672";
    geo["CGR Chiavari Recco 25000"] = "671";
    geo["CGR Bargagli 25000"] = "566";
    geo["CGR Varazze 25000"] = "569";
    geo["CGR Vado Ligure 25000"] = "568";
    geo["CGR Savona 25000"] = "555";
    geo["Geomorf.Campoligure 25000"] = "576";
    geo["Geomorf.Rovegno 25000"] = "572";
    geo["Geomorf.Moneglia 25000"] = "578";
    geo["Geomorf.Framura 25000"] = "574";



    for (var i in geo)
    {
        var transparent;
        if (false && i.toString().indexOf("CARG") == -1)
        {
            transparent = true;
        } else
        {
            transparent = false;
        }
        OPS_Map.addWMSLayer("R.L." + i, "http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/GEOSCIENTIFICHE/" + geo[i] + ".asp?", 'M' + geo[i], false, false);
    }


    OPS_Map.addWMSLayer("Idrografia", "http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/ACQUE_INTERNE/1172.asp?", 'M1172', false, false);


    /*
     CARG - Carte Geologiche sc. 1:10000 riferite al Foglio 213 Genova sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/GEOSCIENTIFICHE/1432.asp    
     CARG - Carte Geologiche sc. 1:10000 riferite al Foglio 228 Cairo Montenotte sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/GEOSCIENTIFICHE/1419.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 213 Genova - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/LAVORIINCORSO/1437.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 213 Genova - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/LAVORIINCORSO/1436.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 213 Genova - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/GEOSCIENTIFICHE/1433.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 213 Genova - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/LAVORIINCORSO/1438.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 213 Genova - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/LAVORIINCORSO/1435.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 214 Bargagli - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/GEOSCIENTIFICHE/1439.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 214 Bargagli - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/LAVORIINCORSO/1434.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 215 Bedonia - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/GEOSCIENTIFICHE/1440.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 228 Cairo Montenotte - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/GEOSCIENTIFICHE/1421.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 232 Sestri Levante - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/GEOSCIENTIFICHE/1441.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 248 La Spezia - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/GEOSCIENTIFICHE/1442.asp
     CARG - Carte Geologiche sc. 1:25000 riferite al Foglio 258 Sanremo - sc. 1:50000	http://www.cartografiarl.regione.liguria.it/mapfiles/repertoriocartografico/GEOSCIENTIFICHE/1443.asp
     */

    geo["CARG.Genova 10000"] = "1432";
    geo["CARG.Cairo Montenotte 10000"] = "1419";
    //geo["CARG.Genova 25000 1"]= "1437";
    //geo["CARG.Genova 25000 2"]= "1436";
    geo["CARG.Genova 25000 3"] = "1433";
    //geo["CARG.Genova 25000 4"]= "1438";
    //geo["CARG.Genova 25000 5"]= "1435";

    geo["CARG.Bargagli 25000"] = "1439";
    //geo["CARG.Bargagli 25000 2"]= "1434";
    geo["CARG.Bedonia 25000"] = "1440";
    geo["CARG.Cairo Montenotte 25000"] = "1421";
    geo["CARG.Sestri Levante 25000"] = "1441";
    geo["CARG.La Spezia 25000"] = "1442";
    geo["CARG.Sanremo 25000"] = "1443";






    var num = getUrlVar("num", "");
    var area = getUrlVar("area", "");
    var layers = getUrlVar("layers", "");
    var table = getUrlVar("table", "");





    OPS_Map.addKmlLayer("Cavita naturali", "openkis_kml.php?mod=caves&minimal=1", false, true);

    // splx.include("bs_rilievi.js.php?"+Math.random());


    //OPS_Map.addMarkerPopup(8.4, 44.2, "mapdata/placemark_circle.png", "test due", "descrizione due");
};
