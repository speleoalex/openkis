<?php

global $_FN;
// map of the measurement points above the list, framed on the points (the
// centre of the region is used when there are none), and the exports in the
// layout of the UCC project sheets
echo "<div class=\"card openkis-card mb-3\"><div class=\"card-body p-0\">";
echo "<iframe style=\"border:0px;width:100%;height:450px;display:block\" src=\"{$_FN['siteurl']}bs_map.htm?temperatures=1&lat={$_FN['default_latitude']}&lon={$_FN['default_longitude']}&zoom={$_FN['default_zoom']}\"></iframe>";
echo "</div><div class=\"card-footer small text-muted\">" . FN_Translate("the colour of a point is the mean of its air temperatures");
if (function_exists("openkis_TemperaturesUccLinks"))
{
    echo " - " . openkis_TemperaturesUccLinks();
}
echo "</div></div>";
// results as a list by default: openkis_ListSettings() (shared list template, grid/list switch)
