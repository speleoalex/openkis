<?php
/**
 * Caves list header: legend of the icons used on the map of the results.
 * Rendered by dbview into {html_header} of list.tp.html, right below the map.
 */
$openkis_legend_icons = glob("extra/openkis/icons/caves/*.png");
if (is_array($openkis_legend_icons) && count($openkis_legend_icons) > 0) {
    echo "<details class=\"openkis-legend small mb-2\">";
    echo "<summary class=\"text-muted\">" . FN_Translate("legend") . "</summary>";
    echo "<div class=\"row no-gutters mt-1\">";
    foreach ($openkis_legend_icons as $openkis_legend_icon) {
        $openkis_legend_name = FN_Translate(str_replace("_", " ", str_replace(".png", "", basename($openkis_legend_icon))));
        echo "<div class=\"col-4 col-sm-3 col-md-2 mb-1\"><img style=\"height:24px;border:1px solid #ccc;border-radius:6px;margin-right:4px\" src=\"{$_FN['siteurl']}$openkis_legend_icon\" alt=\"\" /> " . htmlspecialchars($openkis_legend_name) . "</div>";
    }
    echo "</div></details>";
}
