<?php
global $_FN;
global $_CARS;
$karsconfig=FN_LoadConfig("extra/openkis/config.php");
$staticcontent=FN_HtmlStaticContent("sections/".$_FN['mod']);
// nothing above the map: the themes with the gc-* layout show it on the whole page,
// like the map of Grottocenter (class openkis-fullmap, rules in the custom.css of the theme)
$fullmap=(trim($staticcontent) === "");
echo $staticcontent;
?>
<style>
    .openkis-map-frame{display:block;border:0;width:100%;height:calc(100vh - 190px);min-height:400px}
    .openkis-map .openkis-legend{margin:.5rem 0}
</style>
<div class="openkis-map<?php echo $fullmap ? " openkis-fullmap" : "";?>">
<iframe id="iframemap" class="openkis-map-frame" title="<?php echo FN_Translate("map");?>" src="<?php echo $_FN['siteurl'];?>bs_map.htm?baselayer=Satellite&mod=artificials&lat=<?php echo $_FN['default_latitude'];?>&lon=<?php echo $_FN['default_longitude'];?>&zoom=<?php echo $_FN['default_zoom'];?>"></iframe>
<button class="btn btn-primary btn-sm mt-2 openkis-map-fullscreen" onclick="openFullscreen()" ><?php echo FN_Translate("open map in full screen");?></button>
<details class="openkis-legend small">
    <summary class="text-muted"><?php echo FN_Translate("legend");?></summary>
    <div class="row no-gutters mt-1">
            <?php
            $icons=glob("extra/openkis/icons/artificials/*.png");
            foreach($icons as $icon)
            {
                $name=FN_Translate(str_replace(".png","",basename("$icon")));
                echo "<div class=\"col-6 col-md-3 mb-1\"><img style=\"height:24px;margin-right:4px\" src=\"{$_FN['siteurl']}$icon\" alt=\"\" />$name</div> ";
            }
            ?></div>
</details>
</div>
<script>
    // the map fills the window below what is above it (app bar, header of the home page):
    // --openkis-map-top is used by the custom.css of the gc-* themes
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
</script>

<script>
    /* View in fullscreen */
    function openFullscreen() {
        var elem = document.getElementById("iframemap");
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) { /* Firefox */
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari and Opera */
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { /* IE/Edge */
            elem.msRequestFullscreen();
        }
    }
    /* Close fullscreen */
    function closeFullscreen() {
        var elem = document.getElementById("iframemap");

        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.mozCancelFullScreen) { /* Firefox */
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) { /* Chrome, Safari and Opera */
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) { /* IE/Edge */
            document.msExitFullscreen();
        }
    }
</script>

<?php
if (!$fullmap)
{
    echo "<hr />";
}
// the static content is printed above the map: the "finis" module must not print it again after this file
$SECTION = null;
