<?php
/**
 * @package Finis_googlesitemap
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
if (file_exists("plugins/googlesitemap/functions.php"))
{
	defined('_FNEXEC') or die('Restricted access');
	require_once ("plugins/googlesitemap/functions.php");
	FNGOOGLESITEMAP_CreateGoogleSitemap("sitemap.xml");
}

