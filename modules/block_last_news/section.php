<?php

/**
 * @package Flatnux_module_newsarhive
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$config=FN_LoadConfig("modules/block_last_news/config.php");
$tablename=$config['tablename'];
$sections=explode(",",$config['show_in_section']);
if ($config['show_in_section']== "" || in_array($_FN['mod'],$sections))
{
    $section=$config['section'];
    $max_items=$config['max_items'];
    $query="SELECT * FROM $tablename WHERE status LIKE '1' LIMIT 1,{$max_items} ORDER BY date DESC";
    $all=FN_XMLQuery($query);
    if (is_array($all))
    {
        echo "\n<ul>";
        foreach($all as $item)
        {
            $date=FN_GetDateTime($item['date']);
            echo "\n<li><a href=\"".FN_RewriteLink("index.php?mod={$section}&amp;op={$item['txtid']}")."\">".$item['title']." </a> <em>{$date}</em></li>";
        }
        echo "\n</ul>";
    }
}
?>
