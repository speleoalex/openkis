<?php
/**
 * Refreshes the cached map exports (KML, GPX, JSON, markers) and recalculates
 * the coordinates, e.g. after the caves data were changed directly in the
 * database. Same as Control center -> Utilities -> Refresh map data.
 *
 *   php refresh-cache.php
 *   docker compose exec -u www-data web php /var/www/html/refresh-cache.php
 */
if (php_sapi_name() !== 'cli')
{
    die();
}
chdir(__DIR__);
global $_FN;
require_once "loadfinis.php";
foreach (openkis_RefreshMapData() as $line)
{
    echo "$line\n";
}
