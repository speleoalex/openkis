<?php
/**
 * @package Flatnux
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */


class FN_TableEditor
{
    /**
     *
     * @param string $table
     * @param type $params 
     */
    function __construct($table, $params = false)
    {
        $this->config = $params;
        $this->config['records_per_page'] = isset($this->config['records_per_page']) ? $this->config['records_per_page'] : 10;
        $this->config['fields'] = isset($this->config['fields']) ? $this->config['fields'] : "";

        if (!is_object($table))
        {
            $this->tablename = $table;
            $this->table = FN_XmlForm($table);
        }
        else
        {
            $this->tablename = $table->xmltable->tablename;
            $this->table = FN_XmlForm($table);
        }
        //------------fields--------------------------------------------------->
        if ($this->config['fields'] == "")
        {
            foreach ($this->table->formvals as $k => $v)
            {
                $this->config['fields'][] = $k;
            }
            $this->config['fields'] = implode(",", $this->config['fields']);
        }
        $this->config['fields_listmode'] = isset($this->config['fields_listmode']) ? $this->config['fields_listmode'] : $this->config['fields'];
        $this->config['fields_viewmode'] = isset($this->config['fields_viewmode']) ? $this->config['fields_viewmode'] : $this->config['fields'];
        //------------fields---------------------------------------------------<

        $postgetkey = "__xdb_{$this->tablename}";
        if (isset($params['requestkey']))
        {
            $postgetkey = $params['requestkey'];
        }
        $this->postgetkey = $postgetkey;
        $fields = "*";
        if (!empty($params['fields']))
        {
            $fields = $params['fields'];
        }
        $this->fields = $fields;
    }
    function Run()
    {
        $op = $this->GetParam("op", $_GET, "flat");
        switch ($op)
        {
            default:
                echo $this->HtmlList();
                break;
        }
    }
    function HtmlSearchForm()
    {
        global $_FN;
        if (empty($this->config['search_fields']))
            return;
        $fields = explode(",", $this->config['search_fields']);
        $table = $this->form;
        if (count($fields) == 0)
            return "";
        $html = "";
        $html.= "<fieldset>";
        $html.= "<table>";
        $html.= "<form method=\"post\" action=\"" . FN_RewriteLink("index.php?mod={$_FN['mod']}") . "\">";
        foreach ($fields as $field)
        {
            $post_search = $this->GetParam('search_' . $field, $_POST, "html");
            if (!empty($_GET['kv']))
            {
                $get = unserialize(urldecode(FN_StripPostSlashes($_GET['kv'])));
                if ($post_search == "")
                    $post_search = $this->GetParam($field, $get, "html");
            }
            $html.= "<tr><td>" . ucfirst($table->formvals[$field]['title']) . "</td><td>";
            if ($table->formvals[$field]['frm_type'] == "stringselect")
            {
                $allvalues = FN_XMLQuery("SELECT DISTINCT $field FROM {$this->tablename} ORDER BY $field");
                $html.= "<select name=\"search_$field\">";
                $html.= "<option value=\"\" >" . FN_Translate("select an item from the list") . "</option>";
                foreach ($allvalues as $value)
                {
                    //dprint_r("($post_search == {$value[$field]})");
                    $s = ($post_search == $value[$field]) ? "selected=\"selected\"" : "";
                    $html.= "\n<option $s value=\"{$value[$field]}\">{$value[$field]}</option>";
                }
                $html.= "</select>";
            }
            elseif ($table->formvals[$field]['frm_type'] == "select")
            {
                $allvalues = $table->formvals[$field]['options'];
                $html.= "<select name=\"search_$field\">";
                $html.= "<option value=\"\" >" . FN_Translate("select an item from the list") . "</option>";
                foreach ($allvalues as $value)
                {
                    //dprint_r($value);
                    $s = ($post_search == $value['value']) ? "selected=\"selected\"" : "";
                    $html.= "\n<option $s value=\"{$value['value']}\">{$value['title']}</option>";
                }
                $html.= "</select>";
            }
            else
            {
                $html.="<input name=\"search_{$field}\" type=\"text\" value=\"{$post_search}\" /></td></tr>";
            }
            $html.= "</td></tr>";
        }
        $html.= "</table>";
        $html.= "<button type=\"submit\">" . FN_Translate("search") . "</button>";
        $html.= "<button onclick=\"window.location='" . FN_RewriteLink("index.php?mod={$_FN['mod']}", "&") . "';return false;\">" . FN_Translate("new search") . "</button>";
        $html.= "</form>";
        $html.= "</fieldset>";
        return $html;
    }
    /**
     *
     * @return type 
     */
    function EncodeSearchPost($page = false)
    {
        $newpost = $this->DecodeSearchPost();
        $kv = "";
        if ($newpost !== false)
        {
            $kv = urlencode(serialize($newpost));
            $kv = "&amp;kv=$kv";
        }
        if ($page)
        {
            $page = $this->GetParam("page", $_GET, "html");
            $kv.="&amp;page=$page";
        }
        return $kv;
    }
    function DecodeSearchPost()
    {
        $newpost = false;
        if (!empty($this->config['search_fields']))
        {
            $fields = explode(",", $this->config['search_fields']);
            foreach ($fields as $field)
            {
                $post_search = $this->GetParam('search_' . $field, $_POST, "html");
                if (!empty($_GET['kv']))
                {
                    $get = unserialize(urldecode(FN_StripPostSlashes($_GET['kv'])));
                    if ($post_search == "")
                        $post_search = $this->GetParam($field, $get, "html");
                }
                if ($post_search != "")
                {
                    $newpost[$field] = $post_search;
                }
            }
        }
        return $newpost;
    }
    /**
     *
     * @param type $ListIds
     * @param type $functionHtmlList
     * @param type $functionHtmlItemList
     * @return type 
     */
    function HtmlList()
    {
        global $_FN;
        $where = "";
        if (!empty($this->config['append_where']))
        {
            $where = "WHERE {$this->config['append_where']}";
        }
        $num_records = FN_XMLQuery("select count(*) as c FROM $this->tablename $where");
        $num_records = isset($num_records[0]['c']) ? $num_records[0]['c'] : 0;
        $html = "";
        //----------------------------------Pages ---------------------------->
        $html .= "<div>";
        $page = $this->GetParam("page", $_GET, "html");
        $numPages = 1;
        $recordsperpage = intval($this->config['records_per_page']);
        if ($recordsperpage != false)
        {
            if ($page == "")
                $page = 1;
            $numPages = ceil($num_records / $recordsperpage);
            if ($page > $numPages)
                $page = $numPages;
            $start = ($page * $recordsperpage - $recordsperpage) + 1;
            $limits = "LIMIT $start,$recordsperpage";
        }
        else
        {
            $start = false;
            $limits = "";
        }
        $html.= FN_Translate("pages") . ":";
        if ($recordsperpage && $numPages > 0)
        {
            $kv = $this->EncodeSearchPost();
            for ($i = 1; $i <= $numPages; $i++)
            {
                $tlink = FN_RewriteLink("?mod={$_FN['mod']}&amp;{$this->postgetkey}page=$i{$kv}");
                if ($page == $i)
                    $html .= " <a href=\"$tlink\"><b>$i</b></a>";
                else
                    $html .= " <a href=\"$tlink\">$i</a>";
            }
        }
        $html .= "</div>";
        //----------------------------------Pages ----------------------------<
        $ftoread = explode(",", $this->config['fields_listmode']);
        $ftodisplay = explode(",", $this->config['fields_listmode']);

        if (!in_array($this->table->xmltable->primarykey, $ftoread))
            $ftoread[] = $this->table->xmltable->primarykey;
        $ftoreadArray = array();
        foreach ($ftoread as $k => $v)
        {
            if (isset($this->table->formvals[$v]))
            {
                $ftoreadArray[] = $v;
            }
            else
                unset($ftodisplay[$k]);
        }
        $ftoread = implode(",", $ftoreadArray);
        $query = "select $ftoread FROM $this->tablename $where $limits";
        $AllInPage = FN_XMLQuery($query);
        $i = 1;
        $html.= "<table>";
        if (is_array($AllInPage))
        {
            //---header table ----------------------------------------------------->
            $html.= "<tr>";
            foreach ($ftodisplay as $fieldkey)
            {
                $html.= "<td>" . $this->table->formvals[$fieldkey]['title'] . "</td>";
            }
            $html.= "</tr>";
            //---header table -----------------------------------------------------<
            foreach ($AllInPage as $item)
            {
                $html.= "<tr>";
                foreach ($ftodisplay as $fieldkey)
                {
                    $html.= "<td>{$item[$fieldkey]}</td>";
                }
                $html.= "</tr>";
            }
        }
        $html.= "</table>";
        return $html;
    }
    /**
     *
     * @param type $key
     * @param type $var
     * @param type $type
     * @return type 
     */
    function GetParam($key, $var, $type = "")
    {
        return FN_GetParam($this->postgetkey . $key, $var, $type);
    }
}
?>
