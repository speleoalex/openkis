<?php
global $_FN;

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php");
}
else
{
    if (!empty($row['latitude']))
    {
        $zoom=17;
        $baselayer="";
        if (false!== strstr($row['coordinates_type'],"IGM") || false!== strstr($row['original_coordinates_type'],"IGM")) //
        {
            $baselayer="IGM 1:25000";
        }
        echo "<iframe style=\"border:0px;width:100%;height:400px;\" src=\"{$_FN['siteurl']}bs_map.htm?mod={$_FN['mod']}&baselayer={$baselayer}&point=circle&lat={$row['latitude']}&lon={$row['longitude']}&zoom=$zoom\"></iframe>";
    }
   
}
?>