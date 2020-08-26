<?php
/**
 * @package Flatnux_GoogleAnalitics
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
##<fnmodule>google-analytics</fnmodule>
global $_FN;
if (file_exists("{$_FN['datadir']}/google_analytics.php"))
{
    $_FN['section_header_footer'] .= file_get_contents("{$_FN['datadir']}/google_analytics.php");
}
?>