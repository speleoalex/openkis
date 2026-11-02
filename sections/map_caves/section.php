<?php
global $_FN;
global $_CARS;
$karsconfig=FN_LoadConfig("extra/openkis/config.php");
echo FN_HtmlStaticContent("sections/".$_FN['mod']);
?>
<iframe id="iframemap" style="border:0px;width:100%;height:calc(100vh - 190px);min-height:400px;" src="<?php echo $_FN['siteurl'];?>bs_map.htm?mod=caves&lat=<?php echo $_FN['default_latitude'];?>&lon=<?php echo $_FN['default_longitude'];?>&zoom=<?php echo $_FN['default_zoom'];?>"></iframe>
<button class="btn btn-primary btn-sm mt-2" onclick="openFullscreen()" ><?php echo FN_Translate("open map in full screen");?></button>
<details class="openkis-legend small my-2">
    <summary class="text-muted"><?php echo FN_Translate("legend");?></summary>
    <div class="row no-gutters mt-1">
            <?php
            $icons=glob("extra/openkis/icons/caves/*.png");
            foreach($icons as $icon)
            {
                $name=FN_Translate(str_replace("_"," ",str_replace(".png","",basename("$icon"))));
                echo "<div class=\"col-4 col-sm-3 col-md-2 mb-1\"><img style=\"height:24px;border:1px solid #ccc;border-radius:6px;margin-right:4px\" src=\"{$_FN['siteurl']}$icon\" alt=\"\" /> $name</div> ";
            }
            ?></div>
</details>
<?php
// regional content below the map (e.g. the summary of the caves, extra/openkis/cavestats)
if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/footer_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/footer_{$_FN['mod']}.php");
}
?>
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
// the static content is printed above the map: the "finis" module must not print it again after this file
$SECTION = null;
