<?php

if (!empty($row['filelox']))
{
    $file = urlencode("misc/fndatabase/ctl_surveys/{$row['id']}/filelox/{$row['filelox']}");
    $iframe_href = "{$_FN['siteurl']}openkis_cave_viewer.php?f={$file}";
    echo "<br/><a href=\"$iframe_href\" target=\"_blank\">" . FN_Translate("open") . "</a><br />";
    echo "<iframe style=\"width:100%;height:800px;border:0px\" src=\"$iframe_href\"></iframe>";
}
?>
