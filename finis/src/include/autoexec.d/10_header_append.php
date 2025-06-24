<?php
/**
 * @package Finis_header_append
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
##<fnmodule>header_footer</fnmodule>
global $_FN;
if (file_exists("{$_FN['datadir']}/header_append.php"))
{
    $_FN['section_header_footer'] .= file_get_contents("{$_FN['datadir']}/header_append.php");
}
?>