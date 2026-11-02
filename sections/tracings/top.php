<?php

global $_FN;
// map of the positive tracings above the list, framed on the arrows (the centre of
// the region is used when there are none)
echo "<div class=\"card openkis-card mb-3\"><div class=\"card-body p-0\">";
echo "<iframe style=\"border:0px;width:100%;height:450px;display:block\" src=\"{$_FN['siteurl']}bs_map.htm?tracings=1&lat={$_FN['default_latitude']}&lon={$_FN['default_longitude']}&zoom={$_FN['default_zoom']}\"></iframe>";
echo "</div><div class=\"card-footer small text-muted\">" . FN_Translate("only the positive tracings are drawn on the map") . "</div></div>";
// results as a list by default, with the grid/list switch
if (function_exists("openkis_ListViewSwitch"))
{
    echo openkis_ListViewSwitch("tracings", "list");
}
