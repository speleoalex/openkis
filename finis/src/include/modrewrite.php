<?php
/**
 * @package Finis
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */

global $_FN;
if ($_FN['enable_mod_rewrite'] > 0 && !empty($_FN['links_mode']) && file_exists("{$_FN['src_finis']}/include/mod_rewrite/{$_FN['links_mode']}/modrewrite.php"))
{
    require_once ("{$_FN['src_finis']}/include/mod_rewrite/{$_FN['links_mode']}/modrewrite.php");
}
else
{
    /**
     * 
     */
    function FN_BuildHtaccess()
    {
        
    }

    /**
     *
     * @global type $_FN
     * @param string $href
     * @param type $sep
     * @param type $full
     * @return string 
     */
    function FN_RewriteLink($href,$sep = "",$full = false)
    {
        global $_FN;
        if ($sep == "")
        {
            if (fn_erg("&amp;",$href))
            {
                $sep = "&amp;";
            }
            else
            {
                $sep = "&";
            }
        }
        if ($full)
        {
            $siteurl = empty($_FN['use_urlserverpath']) ? $_FN['siteurl'] : $_FN['sitepath'];
            $href = $siteurl.$href;
        }
        return $href;
    }

}