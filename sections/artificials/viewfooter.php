<?php
global $_FN;
openkis_UpdateCoords("artificials",true);
if (!empty($row['latitude']))
{
    $zoom=17;
    $baselayer="";
    if (false!== strstr($row['coordinates_type'],"IGM") || false!== strstr($row['original_coordinates_type'],"IGM"))
    {
        $baselayer="IGM 1:25000";
    }
    echo "<iframe style=\"border:0px;width:100%;height:400px;\" src=\"{$_FN['siteurl']}bs_map.htm?mod={$_FN['mod']}&baselayer={$baselayer}&point=circle&lat={$row['latitude']}&lon={$row['longitude']}&zoom=$zoom\"></iframe>";
}

?>
