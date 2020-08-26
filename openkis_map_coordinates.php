<!doctype html>
<html lang="it">
    <head>
        <title>Map</title>
    </head>
    <body style="margin:0px;padding:0px;overflow:hidden">
<?php
/**
 * @package Flatnux
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
ob_start();
global $_FN;
require_once "include/flatnux.php";
$lat=FN_GetParam("lat",$_GET);
$lon=FN_GetParam("lon",$_GET);
$coordtype=FN_GetParam("coordinates_type",$_GET);

$t=FN_XmlTable("ctl_coordinatestypes");
$rec_coordinates_type=$t->GetRecord(array("coordinates_type"=>$coordtype));
$proj=isset($rec_coordinates_type['proj4'])?$rec_coordinates_type['proj4']:"";
$geo=new openkis_geoconverter($lat,$lon,$coordtype,$proj);
if (!empty($geo->wgs84lat))
{
    $zoom=18;
    ?>
    <iframe frameborder="0" style="overflow:hidden;overflow-x:hidden;overflow-y:hidden;height:100%;width:100%;position:absolute;top:0px;left:0px;right:0px;bottom:0px" height="100%" width="100%" src="bs_map.htm?point=circle&lat=<?php echo $geo->wgs84lat;?>&lon=<?php echo $geo->wgs84lon;?>&zoom=<?php echo $zoom?>"></iframe>
    <?php
}
else
{
    echo FN_Translate("unknown coordinates");
}
?></body>
</html>
