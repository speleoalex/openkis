<?php
/**
 * Default map position for the templates ({default_latitude}, {default_longitude},
 * {default_zoom}). Values come from extra/openkis/config.php, editable from the
 * admin section "configopenkis". A regional customisation in
 * extra/openkis/custom/<region>/<region>.php may set its own values.
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
if (empty($_FN['default_latitude']))
{
    $openkis_mapconfig = FN_LoadConfig("extra/openkis/config.php");
    $_FN['default_latitude'] = $openkis_mapconfig['default_latitude'];
    $_FN['default_longitude'] = $openkis_mapconfig['default_longitude'];
    $_FN['default_zoom'] = $openkis_mapconfig['default_zoom'];
}
