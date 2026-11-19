<?php

global $_FN;
// map of the positive tracings above the list, framed on the arrows (the centre of
// the region is used when there are none)
echo openkis_ListMapCard("tracings=1", FN_Translate("only the positive tracings are drawn on the map"));
// results as a list by default: openkis_ListSettings() (shared list template, grid/list switch)
