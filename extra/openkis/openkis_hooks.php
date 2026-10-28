<?php
/**
 * Runs after the regional customisation (queued at the end of
 * extra/openkis_config.inc.php, after both local configuration files).
 * A region that sets its own insert callback for the caves (the cadastre
 * number) replaces openkis_OnSaveUpdateCaveSystems, so the cave systems were
 * not recalculated when a cave is added: the regional callback is kept in
 * $_FN['openkis_caves_on_insert'] and openkis_OnInsertCave() runs both.
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$openkis_on_insert = isset($_FN['modparams']['caves']['editorparams']['table']['function_on_insert']) ? $_FN['modparams']['caves']['editorparams']['table']['function_on_insert'] : "";
if ($openkis_on_insert != "" && $openkis_on_insert != "openkis_OnSaveUpdateCaveSystems" && $openkis_on_insert != "openkis_OnInsertCave")
{
    $_FN['openkis_caves_on_insert'] = $openkis_on_insert;
    $_FN['modparams']['caves']['editorparams']['table']['function_on_insert'] = "openkis_OnInsertCave";
}
