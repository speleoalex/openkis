<?php
/**
 * @package Flatnux_module_dbview
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
#<fnmodule>dbview</fnmodule>
defined('_FNEXEC') or die('Restricted access');
global $_FN;
//extra editor params 
$_FN['modparams'][$_FN['mod']]['editorparams'] = isset($_FN['modparams'][$_FN['mod']]['editorparams']) ? $_FN['modparams'][$_FN['mod']]['editorparams'] : array();

if (isset($_GET['debug']))
{
    dprint_r(__FILE__ . " " . __LINE__ . " : " . FN_GetExecuteTimer());    
}
require_once "modules/dbview/FNDBVIEW.php";
if (isset($_GET['debug']))
{
    dprint_r(__FILE__ . " " . __LINE__ . " : " . FN_GetExecuteTimer());
}
if (file_exists("sections/{$_FN['mod']}/custom_functions.php"))
    require_once "sections/{$_FN['mod']}/custom_functions.php";

$file = FN_GetParam("file", $_GET, "flat");
$config = FN_LoadConfig();
$dbview = new FNDBVIEW($config);

if ((false === strpos($file, "..")) && $file != "" && file_exists("sections/{$_FN['mod']}/$file"))
{
    include "sections/{$_FN['mod']}/$file";
}
else
{
    $dbview->Init();
    $id = FN_GetParam("id", $_GET, "html");
    $op = FN_GetParam("op", $_GET, "html");
    $downloadfile = FN_GetParam("downloadfile", $_GET);
    $mode = FN_GetParam("mode", $_GET);
//-------------------------------config---------------------------------------->    
    $tablename = $config['tables'];
    $recordsperpage = $config['recordsperpage'];
    $config['search_orders'] = explode(",", $config['search_orders']);
    $config['search_options'] = explode(",", $config['search_options']);
    $config['navigate_groups'] = explode(",", $config['navigate_groups']);
    $config['search_fields'] = explode(",", $config['search_fields']);
    $config['search_partfields'] = explode(",", $config['search_partfields']);
//-------------------------------config----------------------------------------<
    if ($id != "" && $op == "")
    {
        $op = "view";
    }
    if ($mode == "go")
    {
        $dbview->GoDownload($downloadfile);
        exit();
    }
    $html = "";
    require_once $_FN['filesystempath'] . "/include/xmldb_frm_search.php";
    $Table = FN_XmlForm($tablename);
    $Search = new xmldb_searchform($_FN['database'], $tablename, $_FN['datadir'], $_FN['lang'], $_FN['languages'], false);
    $html .= $Search->HtmlSearchForm();
    if ($config['enable_permissions_each_records'])
    {
        if (!isset($Table->formvals['groupview']))
        {
            $field = array();
            $field['name'] = 'groupview';
            $field['frm_i18n'] = 'limits the display of the content in these groups';
            $field['foreignkey'] = 'fn_groups';
            $field['fk_link_field'] = 'groupname';
            $field['fk_show_field'] = 'groupname';
            $field['frm_type'] = 'multicheck';
            $field['type'] = 'string';
            addxmltablefield($_FN['database'], $tablename, $field, $_FN['datadir']);
        }
        $Table->formvals['groupview']['frm_show'] = 1;
        if ($config['permissions_records_groups'] != "")
        {
            $allAllowedGroups = explode(",", $config['permissions_records_groups']);
            if (isset($Table->formvals['groupview']['options']))
            {
                foreach ($Table->formvals['groupview']['options'] as $k => $v)
                {
                    if (!in_array($v['value'], $allAllowedGroups))
                    {
                        unset($Table->formvals['groupview']['options'][$k]);
                    }
                }
            }
        }
    }
    else
    {
        if (isset($Table->formvals['groupview']))
        {
            $Table->formvals['groupview']['frm_show'] = 0;
        }
    }


    if ($config['enable_permissions_edit_each_records'])
    {
        if (!isset($Table->formvals['groupinsert']))
        {
            $field = array();
            $field['name'] = 'groupinsert';
            $field['frm_i18n'] = 'limits the edit of the content to these groups';
            $field['foreignkey'] = 'fn_groups';
            $field['fk_link_field'] = 'groupname';
            $field['fk_show_field'] = 'groupname';
            $field['frm_type'] = 'multicheck';
            $field['type'] = 'string';
            $field['frm_setonlyadmin'] = '1';
            $field['frm_allowupdate'] = 'onlyadmin';
            $field['type'] = 'string';
            addxmltablefield($_FN['database'], $tablename, $field, $_FN['datadir']);
        }
        if (!empty($config['groupadmin']) && FN_UserInGroup($_FN['user'], $config['groupadmin']))
        {
            $Table->formvals['groupinsert']['frm_show'] = 1;
            unset($Table->formvals['groupinsert']['frm_setonlyadmin']);
            $Table->formvals['groupinsert']['frm_allowupdate'] = "";
        }

        if ($config['permissions_records_edit_groups'] != "")
        {
            $allAllowedGroups = explode(",", $config['permissions_records_edit_groups']);
            if (isset($Table->formvals['groupinsert']['options']))
            {
                foreach ($Table->formvals['groupinsert']['options'] as $k => $v)
                {
                    if (!in_array($v['value'], $allAllowedGroups))
                    {
                        unset($Table->formvals['groupinsert']['options'][$k]);
                    }
                }
            }
        }
    }
    else
    {
        if (isset($Table->formvals['groupinsert']))
        {
            $Table->formvals['groupinsert']['frm_show'] = 0;
        }
    }
//-----------------------------principale ------------------------------------->
    if ($dbview->CanViewRecords())
    {
        switch ($op)
        {
            case "offlineform":
                if ($id)
                    $dbview->GenOfflineUpdate($id);
                else
                    $dbview->GenOfflineInsert();
                break;
            case "history" :
                $shownavigatebar = true;
                if ($config['enable_history'])
                    $html .= $dbview->ViewRecordHistory($id, false, $shownavigatebar); // visualizza la pagina col record
                break;
            case "view" :
                $shownavigatebar = true;
                if (isset($_GET['embed']))
                    $shownavigatebar = false;
                if (!empty($_GET['inner']))
                    $shownavigatebar = false;
                $html .= $dbview->ViewRecordPage($id, false, $shownavigatebar); // visualizza la pagina col record
                break;
            case "writecomment" :
                $html .= $dbview->WriteComment($id);
                break;
            case "request" :
                $html .= $dbview->Request($id);
                break;
            case "edit" :

                $html .= $dbview->EditRecordForm($id, $Table); // form edita record
                if (file_exists("sections/{$_FN['mod']}/bottom_edit.php"))
                {
                    include ("sections/{$_FN['mod']}/bottom_edit.php");
                }


                break;
            case "new" :
                if (isset($_POST['xmldbsave']))
                {
                    $html .= $dbview->InsertRecord($Table);
                    $html .= $dbview->WriteSitemap();
                }
                else
                {
                    $html .= $dbview->NewRecordForm($Table); //  form nuovo record
                }
                break;
            case "users" :
                $html .= $dbview->UsersForm($id); //  form nuovo record
                break;
            case "admingroups" :
                $html .= $dbview->AdminPerm($id); //  permessi records
                break;
            case "delcomment" :
                $html .= $dbview->DelComment($id); //  form nuovo record
                break;
            case "del" :
                $html .= $dbview->DelRecordForm($id); //  form nuovo record
                break;
            case "updaterecord" :
                if (count($_POST) == 0 || isset($_POST['__NOSAVE']) || !isset($_POST['xmldbsave']))
                {
                    $html .= $dbview->EditRecordForm($id, $Table);
                }
                else
                {
                    $html .= $dbview->UpdateRecord($Table); // esegue aggiornamento record
                }
                break;
            case "insertrecord" : // esegue inserimento record
                break;
            case "updatesitemap" :
                $dbview->WriteSitemap();
                dprint_xml(file_get_contents("sitemap-$tablename.xml"));
                dprint_xml(file_get_contents("index-$tablename.html"));
                break;
            default :
                if (FN_IsAdmin() && isset($_GET["refresh_rss"]))
                {
                    $dbview->GenerateRSS();
                }
                $html .= FN_HtmlContent("sections/{$_FN['mod']}");
                $html .= $dbview->ViewGrid(); //griglia con tutti i records
                ob_start();
                if (file_exists("sections/{$_FN['mod']}/bottom.php"))
                {
                    include ("sections/{$_FN['mod']}/bottom.php");
                }
                $html .= ob_get_clean();
                break;
        }
    }
    else
    {
        $html = FN_i18n("you are not authorized to view the data");
    }
    echo $html;
//-----------------------------principale -------------------------------------<
}
?>

<script type="text/javascript">
    function call_ajax(url, div)
    {
        var xsreq;
        loading(div);
        if (window.XMLHttpRequest) {
            xsreq = new XMLHttpRequest();
        } else if (window.ActiveXObject) {
            xsreq = new ActiveXObject("Microsoft.XMLHTTP");
        }
        if (xsreq) {
            xsreq.onreadystatechange = function () {
                try {
                    ajDone(url, div, xsreq);
                } catch (e) {
                    alert(e)
                }
            };
            xsreq.open("GET", url, true);
            xsreq.send("");
        }
    }
    function ajDone(url, div, xsreq)
    {

        if (xsreq.readyState == 4)
        {
            var divs = div.split(",");
            var output = xsreq.responseText;
            for (var i in divs)
            {
                div = divs[i];
                console.log(div);
                //alert (div);
                try {
                    var d = document.getElementsByTagName('body')[0];
                    var olddiv = document.getElementById("__fnloading");
                    d.removeChild(olddiv);
                } catch (e) {
                    //alert(e);
                }

                var el = document.createElement("div");
                el.innerHTML = output;
                var nodes = el.getElementsByTagName('div');
                for (var i = 0; i < nodes.length; i++) {
                    if (nodes[i].id == div)
                    {
                        document.getElementById(div).innerHTML = nodes[i].innerHTML;
                    }
                }
                nodes = el.getElementsByTagName('span');
                for (var i = 0; i < nodes.length; i++) {
                    if (nodes[i].id == div)
                    {
                        document.getElementById(div).innerHTML = nodes[i].innerHTML;
                    }
                }
            }
        }
    }
    function getScrollY() {
        var scrOfX = 0, scrOfY = 0;
        if (typeof (window.pageYOffset) == 'number') {
            //Netscape compliant
            scrOfY = window.pageYOffset;
            scrOfX = window.pageXOffset;
        } else if (document.body && (document.body.scrollLeft || document.body.scrollTop)) {
            //DOM compliant
            scrOfY = document.body.scrollTop;
            scrOfX = document.body.scrollLeft;
        } else if (document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)) {
            //IE6 standards compliant mode
            scrOfY = document.documentElement.scrollTop;
            scrOfX = document.documentElement.scrollLeft;
        }
        return scrOfY;
        //return [ scrOfX, scrOfY ];
    }
    function getScrollX() {
        var scrOfX = 0, scrOfY = 0;
        if (typeof (window.pageYOffset) == 'number') {
            //Netscape compliant
            scrOfY = window.pageYOffset;
            scrOfX = window.pageXOffset;
        } else if (document.body && (document.body.scrollLeft || document.body.scrollTop)) {
            //DOM compliant
            scrOfY = document.body.scrollTop;
            scrOfX = document.body.scrollLeft;
        } else if (document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)) {
            //IE6 standards compliant mode
            scrOfY = document.documentElement.scrollTop;
            scrOfX = document.documentElement.scrollLeft;
        }
        return scrOfX;
    }
    function loading(_div)
    {

        var divs = new Array();
        divs = _div.split(",");
        _div = divs[0];
        try {
            var d = document.getElementsByTagName('body')[0];
            var olddiv = document.getElementById("__fnloading");
            d.removeChild(olddiv);
        } catch (e) {
        }



        var div;
        div = document.createElement('div');
        div.innerHTML = 'loading...';
        div.setAttribute('id', "__fnloading");
        oHeight = document.getElementsByTagName('body')[0].clientHeight + getScrollY();
        oWidth = document.getElementsByTagName('body')[0].clientWidth + getScrollX();
        oHeight = oHeight + "px";
        oWidth = oWidth + "px";
        try {
            div.style.backgroundColor = '#000000';
            div.style.color = '#ffffff';
            div.style.zIndex = '8';
            div.style.display = 'block';
            div.style.position = 'absolute';
            div.style.width = oWidth;
            div.style.height = "auto";
            div.style.top = '0px';
            div.style.left = '0px';
            div.style.textAlign = 'center';
            div.style.opacity = '0.5';
            div.style.filter = 'alpha(opacity=50)';
        } catch (e)
        {
        }
        div.innerHTML = "<div style=\"color:#ffffff;margin-top:" + getScrollY() + "px\" ><br />loading...<br /><br /><img  src='<?php echo "{$_FN['siteurl']}modules/dbview/" ?>loading.gif' /><br /><br /></div>";
        document.getElementsByTagName('body')[0].appendChild(div);
    }
</script>