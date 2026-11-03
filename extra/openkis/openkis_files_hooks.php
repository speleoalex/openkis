<?php
/**
 * Registers the finis hooks of the protected files (see
 * extra/openkis/openkis_files.inc.php) and closes their folders in the data
 * folder; the records of the restricted caves (surveys, sightings...) are
 * hidden from who may not view the cave. Queued with FN_IncludeScript() so
 * that it runs after openkis_config.local.php, where
 * $_FN['openkis_protected_files'] = false keeps the old direct links.
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$_FN['dbview_excluded_values'] = "openkis_HiddenRecordValues";
if (openkis_ProtectedFiles() && function_exists("sodium_crypto_secretbox"))
{
    $_FN['xmetadb_file_url'] = "openkis_FileUrl";
    $_FN['xmetadb_file_access'] = "openkis_FileAccess";
    openkis_FileGuardFolders();
}
