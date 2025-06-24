<?php
/**
 * @package Finis_googlesitemap
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
##<fnmodule>googlesitemap</fnmodule>
defined('_FNEXEC') or die('Restricted access');

/**
 *
 * @global array $_FN
 * @param string $filename 
 */
function FNGOOGLESITEMAP_CreateGoogleSitemap($filename)
{
	global $_FN;
	$oldenable_mod_rewrite=$_FN['enable_mod_rewrite'];
	$oldlang=$_FN['lang'];
	$_FN['enable_mod_rewrite']=$_FN['enable_mod_rewrite_default'];
	$_FN['lang']=$_FN['lang_default'];
	$modlist=FN_GetSections("sections",true,true);
	$str="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.84\">";
	foreach ($modlist as $mod)
	{
		$str .= "\n\t<url><loc>{$_FN['siteurl']}" . FN_RewriteLink("index.php?mod={$mod['id']}","&amp;") . "</loc></url>";
		if (count($_FN['listlanguages']) > 1)
		{
			foreach ($_FN['listlanguages'] as $l)
			{
				if ($l != $_FN['lang_default'])
					$str .= "\n\t<url><loc>{$_FN['siteurl']}" .
							FN_RewriteLink("index.php?mod={$mod['id']}&amp;lang=$l","&amp;") . "</loc></url>";
			}
		}
	}
	$str .= "\n</urlset><!-- end -->";
	FN_Write($str,$filename);
	$_FN['lang']=$oldlang;
	$_FN['enable_mod_rewrite']=$oldenable_mod_rewrite;
}


?>
