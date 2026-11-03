<?php
/**
 * Offline use of the site installed as an application: offline search in the record
 * indexes, download of the sheets of whole sections, space used. The page is drawn by
 * openkis_offline.js (OKIS.offlinePage) in #okis-offline-app; the worker shows it again,
 * without network, for the pages that were not kept. Server side:
 * extra/openkis/openkis_offline.inc.php, openkis_offline.php.
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
if (!function_exists("openkis_OfflineEnabled") || !openkis_OfflineEnabled())
{
    echo "<p>" . FN_Translate("offline use is turned off on this site") . "</p>";
    return;
}
?>
<div id="okis-offline-app">
    <p><?php echo FN_Translate("loading"); ?>…</p>
</div>
<noscript><p><?php echo FN_Translate("the offline use needs javascript"); ?></p></noscript>
