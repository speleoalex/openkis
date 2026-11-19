<?php

global $_FN;
// map of the measurement points above the list, framed on the points (the
// centre of the region is used when there are none), and the exports in the
// layout of the UCC project sheets
$extra = function_exists("openkis_TemperaturesUccLinks") ? openkis_TemperaturesUccLinks() : "";
echo openkis_ListMapCard("temperatures=1", FN_Translate("the colour of a point is the mean of its air temperatures"), "", $extra);
// results as a list by default: openkis_ListSettings() (shared list template, grid/list switch)
