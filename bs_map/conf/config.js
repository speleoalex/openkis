getParameterByName = function (name)
{
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regexS = "[\\?&]" + name + "=([^&#]*)";
    var regex = new RegExp(regexS);
    var results = regex.exec(window.location.search);
    if (results === null)
        return "";
    else
        return decodeURIComponent(results[1].replace(/\+/g, " "));
}
var lang = getParameterByName("lang");
if (lang === "" || lang === "auto")
{
    // the language of the page around the map (<html lang>), else the one of the browser
    lang = "";
    try {
        if (window.parent !== window)
        {
            lang = window.parent.document.documentElement.lang || "";
        }
    }
    catch (e)
    {
        // map embedded in a page of another site
    }
    try {
        lang = (lang || navigator.language).split("-")[0].toLowerCase();
    }
    catch (e)
    {
        lang = "en";
    }
}
// it names the file of the translations (bs_map/languages/<lang>.js)
lang = lang.replace(/[^a-z]/gi, "").toLowerCase() || "en";
var config = {
    
};
config.lang=lang;

geocodepath = "";
//8.90785 E
//44.48089 N
OPS_Map.defaultLat = 44.48;
OPS_Map.defaultLon = 8.9;
OPS_Map.defaultZoom = 10;
OPS_Map.basepath = "bs_map/";
OPS_Map.lang = lang;
OPS_Map.defaultProj ='EPSG:4326'; 
OPS_Map.defaultProj ='EPSG:4326'; 
