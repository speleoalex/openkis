<?php
/**
 * OpenKIS first-run installer.
 *
 * Copies the OpenKIS table definitions (misc/fndatabase/ctl_*.php) into the
 * data directory when they are missing. Existing definitions are never
 * overwritten, so local customisations are preserved.
 *
 * The MySQL driver creates missing tables and adds missing columns
 * automatically from these definitions, so no SQL dump is needed.
 */
defined('_FNEXEC') or die('Restricted access');

/**
 * Install missing table definitions. Safe to call on every request:
 * a sentinel file in the data directory records the list of definitions
 * already processed and the function returns immediately when nothing changed.
 *
 * @return array names of the definitions copied in this call
 */
function openkis_InstallDefinitions()
{
    global $_FN;
    $datadir = empty($_FN['datadir']) ? "misc" : $_FN['datadir'];
    $database = empty($_FN['database']) ? "fndatabase" : $_FN['database'];
    $src = __DIR__ . "/misc/fndatabase";
    $copied = array();
    if (!is_dir($datadir) || !is_writable($datadir))
    {
        return $copied;
    }
    $files = glob("$src/ctl_*.php");
    if (!is_array($files) || count($files) == 0)
    {
        return $copied;
    }
    sort($files);
    $names = array_map('basename', $files);
    $signature = md5(implode("\n", $names));
    $sentinel = "$datadir/openkis_definitions.installed";
    if (file_exists($sentinel) && trim(@file_get_contents($sentinel)) === $signature)
    {
        return $copied;
    }
    $dest = "$datadir/$database";
    if (!is_dir($dest))
    {
        @mkdir($dest, 0777, true);
    }
    if (!is_dir($dest) || !is_writable($dest))
    {
        return $copied;
    }
    foreach ($files as $file)
    {
        $name = basename($file);
        if (!file_exists("$dest/$name"))
        {
            if (@copy($file, "$dest/$name"))
            {
                $copied[] = $name;
            }
        }
    }
    @file_put_contents($sentinel, $signature);
    return $copied;
}

openkis_InstallDefinitions();
