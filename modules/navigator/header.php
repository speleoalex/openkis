<?php
/**
 * @package Flatnux_module_navigator
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
#<fnmodule>navigator</fnmodule>

defined('_FNEXEC') or die('Restricted access');
$op = FN_GetParam("op", $_GET);
//--config-->
$config = FN_LoadConfig("modules/navigator/config.php");
//--config--<
//dprint_r($config);
$tables = explode(",", $config['tables']);
$_tablename = $tables[0];
if ($_tablename == "")
{
    $tablename = $tables[0];
}
else
{
    $tablename = $_tablename;
}

if ($op == "view")
{
    $id = FN_GetParam("id", $_GET);
    $Table = FN_XmlForm($tablename);
    $row = $Table->xmltable->GetRecordByPrimaryKey($id);
    if (is_array($row))
    {
        $titles = explode(",", $config['titlefield']);
        $t = array();
        foreach ($titles as $tt)
        {
            $t[] = isset($row[$tt]) ? $row[$tt] : "";
        }
        $title = implode(" ", $t);
        $_FN['site_title'] .= " - " . $title;
    }
}
if ($config['enable_rss'])
{
    $_FN['rss_link'] = $_FN['datadir'] . "/rss/$tablename/{$_FN['lang']}/rss.xml";
}


if (file_exists("themes/{$_FN['theme']}/modules/navigator/style.css"))
{
    $_FN['section_header_footer'].= "\n<style>\n".file_get_contents("themes/{$_FN['theme']}/modules/navigator/style.css")."\n</style>\n";
}
?>