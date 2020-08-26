<?php
global $_FN;
global $_CARS;
$karsconfig=FN_LoadConfig("extra/openkis/config.php");
echo FN_HtmlStaticContent("sections/".$_FN['mod']);
?>
<iframe id="iframemap" style="border:0px;width:100%;height:400px;" src="<?php echo $_FN['siteurl'];?>bs_map.htm?baselayer=Satellite&mod=artificials&lat=<?php echo $karsconfig['default_latitude'];?>&lon=<?php echo $karsconfig['default_longitude'];?>&zoom=<?php echo $karsconfig['default_zoom'];?>"></iframe>
<div class="alert alert-info alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>    
    <div class="table">
        <div class="row">
            <?php
            $icons=glob("extra/openkis/icons/artificials/*.png");
            foreach($icons as $icon)
            {
                $name=FN_Translate(str_replace(".png","",basename("$icon")));
                echo "<div class=\"col-6 col-md-3\"><img style=\"height:30px;\" src=\"{$_FN['siteurl']}$icon\" />$name</div> ";
            }
            ?></div>
    </div>

</div>

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
<button class="btn btn-primary" onclick="openFullscreen()" ><?php echo FN_Translate("open map in full screen");?></button>
<hr />