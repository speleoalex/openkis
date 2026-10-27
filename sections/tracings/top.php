<?php

global $_FN;
// map of the positive tracings above the list, framed on the arrows
echo "<div class=\"card openkis-card mb-3\"><div class=\"card-body p-0\">";
echo "<iframe style=\"border:0px;width:100%;height:450px;display:block\" src=\"{$_FN['siteurl']}bs_map.htm?mod=caves&tracings=1\"></iframe>";
echo "</div><div class=\"card-footer small text-muted\">" . FN_Translate("only the positive tracings are drawn on the map") . "</div></div>";
