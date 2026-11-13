<?php
/**
 * Runs after the regional customisation (queued at the end of
 * extra/openkis_config.inc.php, after both local configuration files).
 * A region that sets its own insert or update callback for the caves (the
 * cadastre number, the Lombardia FSLo code) replaces
 * openkis_OnSaveUpdateCaveSystems, so the cave systems were not recalculated
 * on save: the regional callback is kept in $_FN['openkis_caves_on_<event>']
 * and openkis_OnInsertCave()/openkis_OnUpdateCave() run both.
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
foreach (array("insert" => "openkis_OnInsertCave", "update" => "openkis_OnUpdateCave") as $openkis_event => $openkis_chain)
{
    $openkis_regional = isset($_FN['modparams']['caves']['editorparams']['table']["function_on_$openkis_event"]) ? $_FN['modparams']['caves']['editorparams']['table']["function_on_$openkis_event"] : "";
    if ($openkis_regional != "" && $openkis_regional != "openkis_OnSaveUpdateCaveSystems" && $openkis_regional != $openkis_chain)
    {
        $_FN["openkis_caves_on_$openkis_event"] = $openkis_regional;
        $_FN['modparams']['caves']['editorparams']['table']["function_on_$openkis_event"] = $openkis_chain;
    }
}
unset($openkis_event, $openkis_chain, $openkis_regional);
