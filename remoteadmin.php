<?php
/**
 * @package FlatnuxRemoreAdmin
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
ob_start();
global $_FN;
require_once "include/flatnux.php";
require_once "include/classes/FN_Unzipper.php";
$_FN['charset_page'] = "UTF-8";
header("Content-Type: text/xml; charset={$_FN['charset_page']}"); 
/**
 * op=update extract files and overwrite old files.
 * sample zip contents:
 * sections/
 * sections/MySection/
 * sections/MySection/section.it.html
 * sections/MySection/section.en.html
 * 
 * ?op=query&q=SELECT * FROM fn_users:
 * return xml result
 * 
 * ?op=get&file=images/add.png:
 * return file contents
 * 
 * 
 * 
 */
$mod = FN_GetParam("mod", $_GET);
if (FN_IsAdmin())
{
    if ($_FN['sectionvalues']['type'] == "" && $mod != "" && file_exists("modules/{$_FN['sectionvalues']['type']}/remote_admin/remote_admin.php"))
    {
        include "modules/{$_FN['sectionvalues']['type']}/remote_admin/remote_admin.php";
    }
    elseif ($_FN['sectionvalues']['type'] != "" && $mod != "" && file_exists("sections/{$_FN['sectionvalues']['id']}/remote_admin/remote_admin.php"))
    {
        include "sections/{$_FN['sectionvalues']['id']}/remote_admin/remote_admin.php";
    }
    else
    {
        $remoteadomin = new FN_RemoteAdmin();
        $ret = $remoteadomin->run();
        if (is_array($ret))
        {
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            echo "<pages>\n";
            echo ($remoteadomin->array_to_xml($ret,"p_"));
            echo "\n</pages>\n";
        }
        else
        {
            echo $ret;
        }
    }
}
/**
 * 
 */
class FN_RemoteAdmin
{
    function run()
    {
        global $_FN;
        $op = FN_GetParam("op", $_GET);
        switch ($op)
        {
            default:
                $sections = FN_GetSections("", true);
                return $sections;
                break;
            case "tree":
                $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                $xml .= $this->XmlMenuTree();
                if (isset($_GET['debug']))
                    dprint_xml($xml);
                return $xml;
                break;
            case "update":
                if (!empty($_FILES['file']['tmp_name']))
                {
                    if (!InitFolders())
                        return "error to create folders";
                }
                break;
            case "query":
                return FN_XMLQuery(FN_GetParam("q", $_GET));
                break;
            case "get":
                $filename = FN_GetParam("file", $_GET);
                if (file_exists($filename) && !is_dir($filename))
                {
                    FN_SaveFile($filename, basename($filename));
                }
                else
                {
                    return "the file does not exist";
                }
                break;
        }
    }
    /**
     *
     * @return boolean 
     */
    function InitFolders()
    {
        global $_FN;
        if (!file_exists("{$FN['datadir']}/tmp"))
        {
            if (!FN_MkDir("{$FN['datadir']}/tmp"))
                return false;
        }
        if (file_exists("{$FN['datadir']}/tmp/remoteadmin"))
        {
            FN_RemoveDir("{$FN['datadir']}/tmp/remoteadmin");
        }
        if (!FN_MkDir("{$FN['datadir']}/tmp/remoteadmin"))
            return false;
        return true;
    }
    /**
     * 
     */
    function FN_RemoteAdmin()
    {
        
    }
    /**
     *
     * @staticvar int $t
     * @param type $var
     * @return type 
     */
    function array_to_xml($var,$prefix="")
    {
        static $t = 0;
        $t++;
        $xml = "";
        $tt = "";
        for ($i = 0; $i < $t; $i++)
        {
            $tt .="\t";
        }
        foreach ($var as $k => $v)
        {
            
            $k = "$prefix$k";
            $xml .="\n$tt<$k>";
            if (is_array($v))
            {
                $xml .= $this->array_to_xml($v,$prefix);
                $xml .="\n$tt</$k>";
            }
            else
            {
                $xml .= FN_FixEncoding($v);
                $xml .="</$k>";
            }
        }
        $t--;
        $xml .= "";
        return $xml;
    }
    /**
     *
     * @param type $file
     * @param type $path
     * @return type 
     */
    function unzip($file, $path)
    {
        $zip = new dUnzip2($file);
        $zip->unzipAll($path);
        return;
        $zip = zip_open($file);
        $sep = DIRECTORY_SEPARATOR;
        if ($zip)
        {
            while (false != ($zip_entry = zip_read($zip)))
            {
                if (!preg_match('/\\' . str_replace('/', '\\/', $sep) . '$/s', zip_entry_name($zip_entry)))
                {
                    // str_replace must be used under windows to convert "/" into "\"
                    $complete_path = $path . str_replace('/', $sep, dirname(zip_entry_name($zip_entry)));
                    $complete_name = $path . str_replace('/', $sep, zip_entry_name($zip_entry));
                    if (!file_exists($complete_path))
                    {
                        $tmp = '';
                        foreach (explode($sep, $complete_path) as $k)
                        {
                            $tmp .= $k . $sep;
                            if (!file_exists($tmp))
                            {
                                mkdir($tmp, 0777);
                            }
                        }
                    }
                    if (zip_entry_open($zip, $zip_entry, "r"))
                    {
                        $fd = fopen($complete_name, 'w');
                        fwrite($fd, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
                        fclose($fd);
                        zip_entry_close($zip_entry);
                    }
                }
                else
                {
                    //dprint_r(zip_entry_name($zip_entry));
                }
            }
        }
        zip_close(
                $zip
        );
    }
    /**
     *
     * @global array $_FN
     * @staticvar int $lev
     * @staticvar string $html
     * @param string $parent
     * @return string
     */
    function XmlMenuTree($parent = "", $recursive = true)
    {
        global $_FN;
        static $lev = 0;
        $html = "";
        $sections = FN_GetSections($parent);
        if (empty($sections) || count($sections) == 0)
            return "";
        if ($parent == "")
            $html.="<pages> ";
        foreach ($sections as $section)
        {
            $lev++;
            $html .="\n";
            for ($i = 0; $i < $lev; $i++)
            {
                $html .= "\t";
            }
            $title = str_replace("\\","\\\\",$section['title']);
            $title = str_replace("\"","\\\"",$title);
            $title = FN_FixEncoding($title);
            $html .= "<p_{$section['id']} title=\"{$title}\" type=\"{$section['type']}\" id=\"{$section['id']}\" >";
            if ($recursive)
            {
                $html .= $this->XmlMenuTree($section['id']);
            }
            $html .="\n";
            for ($i = 0; $i < $lev; $i++)
            {
                $html .= "\t";
            }
            $html .= "</p_{$section['id']}>";
            $lev--;
        }
        if ($parent == "")
            $html.="\n</pages>";
        return $html;
    }
}
?>