<?php
global $_FN;
include ("loadfinis.php");
$filelox = FN_GetParam("f", $_GET);
?><!DOCTYPE html>
<html>
    <head>
        <title>3d cave viewer</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link type="text/css" href="CaveView/css/caveview.css" rel="stylesheet"/>
        <meta name="Description" content="CaveView - 3d Cave model viewer" />
        <script type="text/javascript" src="CaveView/js/CaveView2.js" ></script>
    </head>
    <body onload="onload();" style="padding:0px;margin:0px;background-color:#000000">
        <!--<div style="padding:0px;margin:0px;width:100%" id="scene"></div>-->
        <div style="padding:0px;margin:0px;width:100%;position:absolute;height:100%;width:100%" id="scene"  oncontextmenu="return false;"></div>
        <button style="position:absolute;bottom:0px;right:0px" onclick="openFullscreen(document.getElementById('scene'))">Full screen</button>
        <script type="text/javascript" >
            function onload() {
                const viewer = new CV2.CaveViewer("scene", {
                    terrainDirectory: "<?php echo $_FN['siteurl']; ?>/",
                    surveyDirectory: "<?php echo $_FN['siteurl']; ?>/",
                    home: "<?php echo $_FN['siteurl']; ?>/",
                    theme: {
                        stations: {
                            entrances: { fontsize: 10, angle: 0 },
                            default: { fontsize: 12 },
                            junctions: { fontsize: 12 },
                            linked: { fontsize: 12 }
                        }
                    },
                    view: {
                        autoRotate: false,

                        cameraType: CV2.CAMERA_PERSPECTIVE,
                        splays: true,
                        stations: false,
                        stationLabels: true,
                        terrain: true,
                        terrainOpacity: 0.4,
                        walls: true,
                        scraps: true,
                        terrainShading: CV2.SHADING_OVERLAY,
                        cameraType: 1
                    }
                });
                const ui = new CV2.CaveViewUI(viewer);
                ui.loadCave('<?php echo $filelox ?>');
            }

            function openFullscreen(elem) {
                if (typeof (this.oldfullscreen) === 'undefined')
                {
                    oldfullscreen = false;
                }
                if (oldfullscreen)
                {
                    console.log("old=" + oldfullscreen.id + " new=" + elem.id);
                }
                try {
                    elem = (typeof (elem) !== 'undefined') ? elem : document.getElementsByTagName("body")[0];
                    if (!window.screenTop && !window.screenY) {
                        if (oldfullscreen && oldfullscreen.id === elem.id)
                        {
                            ops_closeFullscreen(elem);
                            oldfullscreen = false;
                            return;
                        }
                    }

                    if (elem.requestFullscreen) {
                        elem.requestFullscreen();
                    } else if (elem.mozRequestFullScreen) { /* Firefox */
                        elem.mozRequestFullScreen();
                    } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari and Opera */
                        elem.webkitRequestFullscreen();
                    } else if (elem.msRequestFullscreen) { /* IE/Edge */
                        elem.msRequestFullscreen();
                    } else {
//alert("no full screen");
                    }
                    oldfullscreen = elem;
                } catch (e) {
                    alert(e);
                }
            }
        </script>
    </body>
</html>
