<?php

/**
 * Karst system sheet footer. A regional installation may provide its own
 * map/list rendering in extra/openkis/custom/{custom}/viewfooter_karstsystems.php.
 */
global $_FN;
if (!empty($_FN['openkis_custom']))
{
    $regional_footer = "extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_karstsystems.php";
    if (file_exists($regional_footer))
    {
        // Keep the shared viewfooter: region-specific files add content to the
        // sheet, they do not replace its existing record output.
        include($regional_footer);
    }
}
?>