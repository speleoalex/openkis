<?php
/**
 * Map of the caves and artificial cavities with fauna sightings (section
 * fauna_surveys): the points of openkis_fauna.php on a light base layer,
 * coloured by ecological category or by class of the species observed, sized
 * by their number (OPS_Map.addFaunaLayerFromUrl). The page is listed in the
 * menu for the groups of its definition (the same as fauna_surveys) and
 * shows the map only to the users who may view fauna_surveys: whatever the
 * groups of this page, the points are those of the sightings the user can see.
 * Layout of the full-page maps (map_caves): class openkis-fullmap when the
 * section has no text, --openkis-map-top for the gc-* themes.
 */
global $_FN;
$staticcontent = FN_HtmlStaticContent("sections/" . $_FN['mod']);
$fullmap = (trim($staticcontent) === "");
echo $staticcontent;
if (!file_exists("sections/fauna_surveys") || !FN_UserCanViewSection("fauna_surveys"))
{
    echo "<div class=\"alert alert-warning\">" . FN_Translate("the fauna map is reserved to the users who may view the fauna surveys") . "</div>";
    $SECTION = null;
    return;
}
?>
<style>
    .openkis-map-frame{display:block;border:0;width:100%;height:calc(100vh - 190px);min-height:400px}
    .openkis-map .openkis-legend{margin:.5rem 0}
</style>
<div class="openkis-map<?php echo $fullmap ? " openkis-fullmap" : ""; ?>">
<iframe id="iframemap" class="openkis-map-frame" title="<?php echo FN_Translate("fauna map"); ?>" src="<?php echo $_FN['siteurl']; ?>bs_map.htm?fauna=1&lat=<?php echo $_FN['default_latitude']; ?>&lon=<?php echo $_FN['default_longitude']; ?>&zoom=<?php echo $_FN['default_zoom']; ?>"></iframe>
<button class="btn btn-primary btn-sm mt-2 openkis-map-fullscreen" onclick="openFullscreen()"><?php echo FN_Translate("open map in full screen"); ?></button>
<p class="small text-muted mt-2"><?php echo FN_Translate("each point is a cave or an artificial cavity with fauna surveys: its colour is the ecological category of the species most bound to the underground among those observed there (or, switching the legend, their most frequent class), its size is the number of species; the legend hides the categories and the popup lists the species"); ?></p>
</div>
<script>
    (function () {
        var frame = document.getElementById("iframemap");
        var box = frame ? frame.parentNode : null;
        if (!box || !/\bopenkis-fullmap\b/.test(box.className))
        {
            return;
        }
        function fit() {
            var top = Math.round(frame.getBoundingClientRect().top + window.pageYOffset);
            document.documentElement.style.setProperty("--openkis-map-top", top + "px");
        }
        fit();
        window.addEventListener("load", fit);
        window.addEventListener("resize", fit);
        if (window.ResizeObserver)
        {
            new ResizeObserver(fit).observe(box);
        }
    })();
    function openFullscreen() {
        var elem = document.getElementById("iframemap");
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        }
    }
</script>
<?php
// the static content is printed above the map: the "finis" module must not print it again after this file
$SECTION = null;
