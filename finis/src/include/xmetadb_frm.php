<?php

/**
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2009
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * @package xmetadb
 *
 */

/**
 *
 * @param string $databasename
 * @param string $tablename
 * @param string $path
 * @param array $params
 * @return object XMETATable
 */
function xmetadb_frm($databasename, $tablename, $path = "misc", $lang = "en", $languages = "en,it", $params = false)
{
    static $tables = array();
    if (!is_array($tablename))
    {
        $id = "$databasename,$tablename,$path,$lang,$languages";
        if (!isset($tables[$id]))
        {

            $tables[$id] = new FieldFrm($databasename, $tablename, $path, $lang, $languages, $params);
        }
        return $tables[$id];
    }
    else
    {
        return new FieldFrm($databasename, $tablename, $path, $lang, $languages, $params);
    }
}

/**
 *
 * LoadFieldsForm
 *
 * Carica i fields pronti per essere utilizzati dai forms con i titoli giï¿½ tradotti.
 * Come devono essere gestiti i campi per il form ï¿½ descritto nella tabella stessa.
 *
 *
 *
 * PROPRIETA' RELATIVE A I FORM:
 * frm_setonlyadmin : visualizza solo l'amministratore
 *
 *
 * foreignkey        : tabella lincata
 * fk_link_field     : campo da lincare alla tabella (deve essere univoco, conviene sempre usare la chiave primaria)
 * fk_show_field     : campo da visualizzare
 * title             : titolo
 * title_xx          : titolo tradotto
 * frm_type          : tipo di dato, file,image,text,string,check,html,password,separator(mostra solo il nome del campo)
 * frm_help          : tooltip
 * frm_help_xx       : tooltip tradotto nella lingua xx
 * frm_checkon       : valore se il check ï¿½ settato
 * frm_multilanguage : abita l' inserimento di un dato in piï¿½ lingue
 * frm_required      : il dato deve essere inserito
 * frm_setonlyadmin  : solo l' amministratore puï¿½ inserire il dato
 * frm_allowupdate   : il campo non puï¿½ essere mai aggiornato
 * frm_assoc         : il cmpo viene visualizzato solo se ne ï¿½ presente un altro
 * frm_show          : il campo viene visualizzato
 * frm_retype        : chiede il campo due volte
 * frm_rows          : dimensioni, altezza
 * frm_cols          : dimensioni, colonne
 * frm_size          : dimensioni
 * frm_allowhtml     : permette l'inserimento di codice html
 * frm_maximagesize  : dimensione masima file
 * frm_required_condition : condizione per cui il campo e' obbligatorio es
 *
 */
class FieldFrm extends stdClass
{

    var $databasename;
    var $table;
    var $tablename;
    var $tableparams;
    var $path;
    var $siteurl;
    var $lang;
    var $languages;
    var $charset_page;
    var $charset_storage;
    var $langdefault;
    var $frm_i18n;
    var $formvals;
    var $requiredtext;
    var $innertables;
    var $formclass;
    var $messages;
    var $templateobjects;
    var $xmltable;
    var $templateviewobjects;
    var $fieldname_active;
    var $fieldname_user;
    var $fieldname_password;
    var $escapechar;
    var $fieldspath;

    function __construct($databasename, $tablename, $path = "misc", $lang = "en", $languages = "en,it", $params = false)
    {
        $this->tableparams = $params;
        $this->fieldspath = array(__DIR__ . "/xmetadbfrm_fields/");
        $this->databasename = $databasename;
        if (is_array($tablename))
        {
            if (empty($tablename['tablename']))
                $tablename['tablename'] = "____empty_____";
            $this->tablename = $tablename['tablename'];
        }
        else
        {
            $this->tablename = $tablename;
        }
        $this->table = $tablename;
        $this->path = $path;
        $this->siteurl = empty($params['siteurl']) ? "" : $params['siteurl'];
        $this->lang = $lang;
        $this->languages = $languages;
        $this->charset_page = empty($params['charset_page']) ? "UTF-8" : $params['charset_page'];
        $this->charset_storage = empty($params['charset_storage']) ? "UTF-8" : $params['charset_storage'];
        $t = explode(",", $this->languages);
        $this->langdefault = $t[0];

        $this->formvals = $this->LoadFieldsForm();
        $this->LoadFieldsClasses();
        $this->initmessages();
        $this->requiredtext = isset($params['requiredtext']) ? $params['requiredtext'] : "*";
        if (is_array($this->xmltable->primarykey))
        {
            foreach ($this->xmltable->primarykey as $pkf)
            {
                if (!(isset($this->xmltable->fields[$pkf]->extra) && $this->xmltable->fields[$pkf]->extra == 'autoincrement'))
                {
                    $this->formvals[$pkf]['frm_required'] = 1;
                }
            }
        }
        elseif (!(isset($this->xmltable->fields[$this->xmltable->primarykey]->extra) && $this->xmltable->fields[$this->xmltable->primarykey]->extra == 'autoincrement'))
        {
            $this->formvals[$this->xmltable->primarykey]['frm_required'] = 1;
        }
        $this->Setlayout("table");
        $this->SetLayoutView();
    }

    function i18n($string)
    {
        return XMETADB_i18n($string);
    }

    function LoadFieldsClasses()
    {
        foreach ($this->formvals as $field)
        {
            if (!empty($field['name']))
            {
                if (!isset($field['frm_type']) || $field['frm_type'] == "varchar")
                {
                    if (!empty($field['foreignkey']))
                    {
                        $field['frm_type'] = "select";
                    }
                }
                if (!empty($field['frm_type']))
                    $classname = 'xmetadbfrm_field_' . $field['frm_type'];
                else
                {
                    if (!empty($field['frm_type']) && isset($field['type']) && $field['type'] == "text")
                        $classname = 'xmetadbfrm_field_text';
                    elseif (!empty($field['frm_type']) && isset($field['type']) && $field['type'] == "file")
                        $classname = 'xmetadbfrm_field_file';
                    else
                        $classname = 'xmetadbfrm_field_varchar';
                }
                $param['fieldvalues'] = $field;
                if (!class_exists($classname))
                {
                    foreach ($this->fieldspath as $path_field)
                    {
                        if (file_exists("{$path_field}$classname.php"))
                        {
                            require_once ("{$path_field}$classname.php");
                            break;
                        }
                    }
                }
                if (!class_exists($classname))
                {
                    $classname = "xmetadbfrm_field_varchar";
                }
                $this->formclass[$field['name']] = new $classname($param);
            }
        }
    }

    /**
     * set languages
     */
    function InitMessages()
    {
        $this->messages["_XMLDBREQUIRED"] = $this->I18N("this field is required");
        $this->messages["_XMLDBNOTVALIDFIED"] = $this->I18N("the value is not valid");
        $this->messages["_XMLDBNOTVALIDIMAGE"] = $this->I18N("the value is not valid image");
        $this->messages["_XMLDBTOOBIG"] = $this->I18N("the image must be smaller than");
        $this->messages["_XMLDBEXISTS"] = $this->I18N("this value already exists");
        $this->messages["_XMLDBERRORRETYPE"] = $this->I18N("the values are not equal");
        $this->messages["_XMLDBRETYPE"] = $this->I18N("retype");
        $this->messages["_XMLDBDELETE"] = $this->I18N("delete");
        $this->messages["_XMLDNOTVALIDCHARS"] = $this->I18N("have entered illegal characters");
    }

    /**
     *
     * @param string $str
     * @param string $fieldname 
     */
    function SetlayoutTemplateView($str = "", $fieldname = "", $suffix = "")
    {
        $tmp = (object) array();
        $tmp->templateview = "$str";
        if ($fieldname == "")
        {
            foreach ($this->formvals as $k => $v)
            {
                if (!isset($v['name']))
                    continue;
                $type = $v['frm_type'];
                $__fieldname = $v['name'];
                $this->SetlayoutTemplateView($str, $__fieldname);
                $this->SetlayoutTemplateView($str, $__fieldname, "_type_$type");
                $this->SetlayoutTemplateView($str, $__fieldname, "_$__fieldname");
            }
        }
        else
        {
            $type = $this->formvals[$fieldname]['frm_type'] ? $this->formvals[$fieldname]['frm_type'] : $this->formvals[$fieldname]['type'];
            if (preg_match('/(<!-- item' . $suffix . ' -->)(.*)(<!-- end_item' . $suffix . ' -->)/is', $tmp->templateview, $out))
            {
                $tmp->templateviewItem = empty($out[2]) ? "{title}<br />{input}" : $out[2];

                preg_match('/(<!-- group -->)(.*)(<!-- end_group -->)/is', $tmp->templateview, $out);
                $tmp->templateviewGroup = empty($out[2]) ? "<div class=\"xmetadbgroup{groupname}\">{groupname}</div>" : $out[2];
                preg_match('/(<!-- endgroup -->)(.*)(<!-- end_endgroup -->)/is', $tmp->templateview, $out);
                $tmp->templateviewEndGroup = empty($out[2]) ? "<hr />" : $out[2];

                if (!empty($this->formvals[$fieldname]['view_group']))
                {
                    $groupname = $this->formvals[$fieldname]['view_group'];
                    preg_match('/(<!-- group_' . $groupname . ' -->)(.*)(<!-- end_group_' . $groupname . ' -->)/is', $tmp->templateview, $out);
                    if (!empty($out[2]))
                    {
                        $this->templateviewGroups[$groupname] = empty($out[2]) ? $tmp->templateviewGroup : $out[2];
                        //dprint_xml($this->templateviewGroups);
                    }
                }

                if (!empty($this->formvals[$fieldname]['view_endgroup']))
                {
                    //dprint_r($fieldname);
                    $groupname = $this->formvals[$fieldname]['view_endgroup'];
                    $out = "";
                    preg_match('/(<!-- endgroup_' . $groupname . ' -->)(.*)(<!-- end_endgroup_' . $groupname . ' -->)/is', $tmp->templateview, $out);
                    if (!empty($out[2]))
                    {
                        $this->templateviewEndGroups[$groupname] = empty($out[2]) ? $tmp->templateviewEndGroup : $out[2];
                        //dprint_xml($this->templateviewEndGroups);
                    }
                }


                preg_match('/(<!-- error -->)(.*)(<!-- end_error -->)/is', $tmp->templateview, $out);
                $tmp->templateviewError = empty($out[2]) ? "{error}" : $out[2];
                $tp_out = preg_replace('/<!-- contents -->(.*)<!-- end_contents -->/is', '{formcontents}', $tmp->templateview);
                $tmp->templateviewContents = $tp_out;
                $tmp->templateviewItemError = $tmp->templateviewItem;
                $tp_item = preg_replace('/<!-- error -->(.*)<!-- end_error -->/is', '', $tmp->templateviewItem);
                $tmp->templateviewItem = $tp_item;
                $tmp->templateItem = $tp_item;
                $this->templateviewobjects[$fieldname] = $tmp;
            }
        }
    }

    /**
     *
     * @param string $str
     * @param string $fieldname 
     */
    function SetlayoutTemplate($str = "", $fieldname = "", $suffix = "")
    {
        $tmp = (object) array();
        $tmp->template = "$str";
        if ($fieldname == "")
        {
            foreach ($this->formvals as $k => $v)
            {
                if (!isset($v['name']))
                    continue;
                $type = $v['frm_type'];
                $__fieldname = $v['name'];
                $this->SetlayoutTemplate($str, $__fieldname);
                $this->SetlayoutTemplate($str, $__fieldname, "_type_$type");
                $this->SetlayoutTemplate($str, $__fieldname, "_$__fieldname");
            }
        }
        else
        {
            $type = $this->formvals[$fieldname]['frm_type'] ? $this->formvals[$fieldname]['frm_type'] : $this->formvals[$fieldname]['type'];
            if (preg_match('/(<!-- item' . $suffix . ' -->)(.*)(<!-- end_item' . $suffix . ' -->)/is', $tmp->template, $out))
            {
                $tmp->templateItem = empty($out[2]) ? "{title}<br />{input}" : $out[2];
                preg_match('/(<!-- group -->)(.*)(<!-- end_group -->)/is', $tmp->template, $out);
                $tmp->templateGroup = empty($out[2]) ? "<div>{groupname}</div>" : $out[2];
                preg_match('/(<!-- endgroup -->)(.*)(<!-- end_endgroup -->)/is', $tmp->template, $out);
                $tmp->templateEndGroup = empty($out[2]) ? "<hr />" : $out[2];
                preg_match('/(<!-- error -->)(.*)(<!-- end_error -->)/is', $tmp->template, $out);
                $tmp->templateError = empty($out[2]) ? "{error}" : $out[2];
                $tp_out = preg_replace('/<!-- contents -->(.*)<!-- end_contents -->/is', '{formcontents}', $tmp->template);
                $tmp->templateContents = $tp_out;
                $tmp->templateItemError = $tmp->templateItem;
                $tp_item = preg_replace('/<!-- error -->(.*)<!-- end_error -->/is', '', $tmp->templateItem);
                $tmp->templateItem = $tp_item;
                $this->templateobjects[$fieldname] = $tmp;
            }
        }
    }

    /**
     *
     * @param string $mode 
     */
    function SetLayoutView($mode = "default")
    {
        switch ($mode)
        {
            default:
                $template = "
<div>
<!-- contents -->
<!-- group -->
<div class=\"xmetadbgroup{groupname}\">
<!-- end_group -->
<!-- item -->
<span class=\"xmetadbcontent xmetadbcontent{fieldtype} xmetadbcontent{fieldname} \">
<span class=\"xmetadbtitle xmetadbtitletype{fieldtype} xmetadbtitle{fieldname} \">{title}</span>
<span class=\"xmetadbvaluetype xmetadbvaluetype{fieldtype} xmetadbvalue{fieldname} \">
{input}</span></span>
<!-- end_item -->
<!-- endgroup -->
</div>
<!-- end_endgroup -->
<!-- end_contents -->
</div>
";
                $this->SetlayoutTemplateView($template);
                break;
            case "table" :
                $template = "
<table>
<!-- contents -->
<!-- group -->
	<tr><td colspan=\"2\" style=\"text-align:center\"><b>{groupname}</b></td></tr>
<!-- end_group -->
<!-- item -->
	<tr>
		<td valign=\"top\">{title}<!-- error --><span style=\"color:red\"><br />{error}</span><!-- end_error --></td>
		<td valign=\"top\">{input}</td>
	</tr>
<!-- end_item -->
<!-- endgroup -->
<!-- end_endgroup -->
<!-- end_contents -->
</table>
";
                $this->SetlayoutTemplateView($template);
                break;
        }
    }

    /**
     *
     * @param type $mode 
     */
    function SetLayout($mode = "table")
    {
        switch ($mode)
        {
            case "view" :
                $template = "
<div>
<!-- contents -->
<!-- group -->
<div class=\"xmetadbgroup{groupname}\">
<!-- end_group -->
<!-- item -->
<span class=\"xmetadbcontent xmetadbcontent{fieldtype} xmetadbcontent{fieldname} \">
<span class=\"xmetadbtitle xmetadbtitletype{fieldtype} xmetadbtitle{fieldname} \">{title}</span>
<span class=\"xmetadbvaluetype xmetadbvaluetype{fieldtype} xmetadbvalue{fieldname} \">
{input}</span></span>\"
<!-- end_item -->
<!-- endgroup -->
</div>
<!-- end_endgroup -->
<!-- end_contents -->
</div>
";
                break;
            case "table" :
                $template = "
<table>
<!-- contents -->
<!-- group -->
	<tr><td colspan=\"2\" style=\"text-align:center\"><b>{groupname}</b></td></tr>
<!-- end_group -->
<!-- item -->
	<tr>
		<td valign=\"top\">{title}<!-- error --><span style=\"color:red\"><br />{error}</span><!-- end_error --></td>
		<td valign=\"top\">{input}</td>
	</tr>
<!-- end_item -->
<!-- endgroup -->
<!-- end_endgroup -->
<!-- end_contents -->
</table>
";

                break;
            case "flat" :
            default:
                $template = "
<!-- contents -->
<!-- group -->
<fieldset><legend>{groupname}</legend>
<!-- end_group -->
<!-- item -->
<b>{title}</b><!-- error --> <span style=\"color:red\">{error}</span><!-- end_error -->:<br />
{input}<br />
<!-- end_item -->
<!-- endgroup -->
</fieldset>
<!-- end_endgroup -->
<!-- end_contents -->
";
                break;
        }
        $this->SetlayoutTemplate($template);
    }

    /**
     * setlayoutTags
     * set all propriety
     *
     * @param string $frm_starttagtitle
     * @param string $frm_endtagtitle
     * @param string $frm_starttagvalue
     * @param string $frm_endtagvalue
     * @param string $frm_startgroupheader
     * @param string $frm_endgroupheader
     * @param string $frm_startgroupfooter
     * @param string $frm_endgroupfooter
     */
    function SetlayoutTags($frm_starttagtitle, $frm_endtagtitle, $frm_starttagvalue, $frm_endtagvalue, $frm_startgroupheader = "", $frm_endgroupheader = "", $frm_startgroupfooter = "", $frm_endgroupfooter = "")
    {
        $template = "
<!-- contents -->
<!-- group -->
$frm_startgroupheader{groupname}$frm_endgroupheader
<!-- end_group -->
<!-- item -->
$frm_starttagtitle{title}<!-- error --><span style=\"color:red\">{error}</span><!-- end_error -->$frm_endtagtitle
$frm_starttagvalue{input}$frm_endtagvalue
<!-- end_item -->
<!-- endgroup -->
$frm_startgroupfooter
$frm_endgroupfooter
<!-- end_endgroup -->
<!-- end_contents -->
";
        $this->SetlayoutTemplate($template);
    }

    /**
     * setlayoutTag
     * set field propriety
     *
     * @param string $fieldname
     * @param string $frm_starttagtitle
     * @param string $frm_endtagtitle
     * @param string $frm_starttagvalue
     * @param string $frm_endtagvalue
     * @param string $frm_startgroupheader
     * @param string $frm_endgroupheader
     * @param string $frm_startgroupfooter
     * @param string $frm_endgroupfooter
     */
    function SetlayoutTag($fieldname, $frm_starttagtitle, $frm_endtagtitle, $frm_starttagvalue, $frm_endtagvalue, $frm_startgroupheader = "", $frm_endgroupheader = "", $frm_startgroupfooter = "", $frm_endgroupfooter = "")
    {
        $template = "
<!-- contents -->
<!-- group -->
$frm_startgroupheader{groupname}$frm_endgroupheader
<!-- end_group -->
<!-- item -->
$frm_starttagtitle{title}<!-- error --><span style=\"color:red\">{error}</span><!-- end_error -->$frm_endtagtitle
$frm_starttagvalue{input}$frm_endtagvalue
<!-- end_item -->
<!-- endgroup -->
$frm_startgroupfooter
$frm_endgroupfooter
<!-- end_endgroup -->
<!-- end_contents -->
";
        $this->SetlayoutTemplate($template, $fieldname);
    }
    function setField($fieldname,$values)
    {
        if ($fieldname && is_array($values))
        {
            foreach ($values as $propriety_name => $value)
            {
                $this->xmltable->fields[$fieldname]->$propriety_name = $value;
            }            
            foreach ($values as $propriety_name => $value)
            {
                $this->formvals[$fieldname][$propriety_name] = $value;
            }            
            $this->LoadFieldsForm("",$values);
        }
    }
    /**
     *
     * @staticvar boolean $options
     * @param <type> $values
     * @return <type>
     */
    function LoadFieldsForm($values = "",$fieldvalues=array())
    {
        static $options = array();
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $lang = $this->lang;
        $path = $this->path;
        $this->xmltable = XMETATable::xmetadbTable($databasename, $this->table, $path, $this->tableparams);
        $table = $this->xmltable;
        $records = $table->fields;
        if (isset($this->xmltable->xmldescriptor))
            $this->innertables = xmetadb_xml2array($this->xmltable->xmldescriptor, "innertable", false);
        else
            $this->innertables = false;
        $ret = array();
        foreach ($records as $record)
        {
            $record = get_object_vars($record);
            if (is_array($fieldvalues))
            {
                foreach ($fieldvalues as $k => $v)
                    $record[$k] = $v;            
            }
            $tmp = array();
            foreach ($record as $k => $v)
                $tmp[$k] = $v;
            
            // add missing fields
            if (!empty($record['frm_multilanguages']))
            {

                if ($record['frm_multilanguages'] == "auto")
                {
                    $record['frm_multilanguages'] = $this->languages;
                    $tmp['frm_multilanguages'] = $this->languages;
                }
                $languages = explode(",", $record['frm_multilanguages']);
                $f = false;
                foreach ($languages as $l)
                {
                    $tfield['name'] = $record['name'] . "_$l";
                    $tfield['type'] = $record['type'];
                    $tfield['frm_multilanguage'] = "1";
                    if (!isset($records[$record['name'] . "_$l"]))
                    {
                        addxmltablefield($this->databasename, $this->table, $tfield, $this->path);
                    }
                }
            }
            /*             * ******************************************************** */
            if (!isset($options[$tmp['name'] . $tablename]))
            {
                $options[$tmp['name'] . $tablename] = $this->LoadOptions($record, $values);
            }
            $tmp['options'] = &$options[$tmp['name'] . $tablename];
            if (!isset($tmp['frm_type']))
                $tmp['frm_type'] = isset($tmp['type']) ? $tmp['type'] : "text";

            $tmp['title'] = $this->i18n($record['name']);

            if (!empty($record['frm_group_' . $lang]))
                $tmp['frm_group'] = $record['frm_group_' . $lang];
            if (!empty($record['frm_group_i18n']))
                $tmp['frm_group'] = $this->i18n($record['frm_group_i18n']);

            if (!empty($record['frm_endgroup_' . $lang]))
                $tmp['frm_endgroup'] = $record['frm_endgroup_' . $lang];
            if (!empty($record['frm_endgroup_i18n']))
                $tmp['frm_endgroup'] = $this->i18n($record['frm_endgroup_i18n']);

            //--set field title--->
            if (!empty($record['frm_' . $lang]))
                $tmp['title'] = $record['frm_' . $lang];
            elseif (!empty($record['frm_i18n']))
                $tmp['title'] = $this->i18n($record['frm_i18n']);
            elseif (!empty($record['en']))
                $tmp['title'] = $this->i18n(strtolower($record['frm_en']));


            if (!empty($record['frm_title_insert_' . $lang]))
                $tmp['title_insert'] = $record['frm_title_insert_' . $lang];
            elseif (!empty($record['frm_title_insert_i18n']))
                $tmp['title_insert'] = $this->i18n($record['frm_title_insert_i18n']);
            elseif (!empty($record['en']))
                $tmp['title_insert'] = $this->i18n(strtolower($record['frm_title_insert_en']));
            else
                $tmp['title_insert'] = $tmp['title'];

            //--set field title----<
            if (!empty($record['frm_help_' . $lang]))
                $tmp['frm_help'] = $record['frm_help_' . $lang];
            elseif (!empty($record['frm_help_i18n']))
                $tmp['frm_help'] = $this->i18n($record['frm_help_i18n']);
            elseif (!empty($record['frm_help_en']))
                $tmp['frm_help'] = $this->i18n(strtolower($record['frm_help_en']));

            $tmp['frm_checkon'] = isset($record['frm_checkon']) ? $record['frm_checkon'] : 1;
            $ret[$record['name']] = $tmp;
        }


        return $ret;
    }

    /**
     *
     * @param object $record
     * @param array $restr_values
     * @return array
     */
    function LoadOptions($record, $restr_values = "")
    {

        $databasename = $this->databasename;
        $path = $this->path;
        $lang = $this->lang;
        $tmp['options'] = false;
        if (!empty($record['foreignkey']) && isset($record['fk_link_field']) && isset($record['fk_show_field']))
        {

            $databasename_fk = $databasename;
            $path_fk = $path;
            if (isset($record['fk_databasepath']) && $record['fk_databasepath'] != "")
            {
                $path_fk = $record['fk_databasepath'];
            }
            if (isset($record['fk_databasename']) && $record['fk_databasename'] != "")
            {
                $databasename_fk = $record['fk_databasename'];
            }
            //if (FN_IsAdmin())
            $listoptionstabella = XMETATable::xmetadbTable($databasename_fk, $record['foreignkey'], $path_fk, $this->tableparams);
            if (!isset($listoptionstabella->driver))
            {
                $listoptions = array();
            }
            else
            {
                $restr = false;
                //esempio clausula su foreignkey
                // $Table->formvals['periodo']['fk_filter_field']="id_procurement='1',Abilitato='True'";
                if (isset($record['fk_filter_field']) && $record['fk_filter_field'] != "")
                {
                    //if exist a clause
                    $clausule = explode("=", $record['fk_filter_field']);
                    if (isset($clausule[1]))
                    {
                        //all clauses separated by commas
                        $clausules = explode(",", $record['fk_filter_field']);
                        $restr = array();
                        foreach ($clausules as $clk => $claus_item)
                        {
                            $clausule = explode("=", $claus_item);
                            $cname1 = trim(ltrim($clausule[0]));
                            $cname2 = trim(ltrim($clausule[1]));
                            //left of equal is the name of field
                            if (isset($listoptionstabella->fields[$cname1]))
                            {
                                if (isset($restr_values[$cname2]))
                                    $restr[$cname1] = $restr_values[$cname2];
                                else
                                {
                                    // if is  key='pippo'
                                    if ($cname2[0] == "'" && $cname2[strlen($cname2) - 1] == "'")
                                        $restr[$cname1] = substr($cname2, 1, strlen($cname2) - 2);
                                    else
                                        $restr[$cname1] = $cname2;
                                }
                            }
                        }
                        $listoptions = $listoptionstabella->GetRecords($restr);
                    }
                    else
                    {
                        $listoptions = array();
                    }
                }
                else
                {
                    $listoptions = $listoptionstabella->GetRecords();
                }
            }
            $retopzioni = array();
            $tmp2 = array();
            $tmp_optionvalues = array();
            if (is_array($listoptions))
                foreach ($listoptions as $opzione)
                {
                    $tmp2['value'] = isset($opzione[$record['fk_link_field']]) ? $opzione[$record['fk_link_field']] : "";

                    $showfields = explode(",", $record['fk_show_field']);
                    $tmp2['title'] = "";
                    $sep = "";
                    foreach ($showfields as $showfield)
                    {
                        $tmp2['title'] .= $sep;
                        if (isset($opzione[$showfield . "_$lang"]) && $opzione[$showfield . "_$lang"] != "")
                            $tmp2['title'] .= $opzione[$showfield . "_$lang"];
                        else
                            $tmp2['title'] .= isset($opzione[$showfield]) ? $opzione[$showfield] : "";
                        $sep = " ";
                    }
                    if (isset($record['frm_show_image']) && isset($opzione[$record['frm_show_image']]))
                    {
                        $tmp2['frm_show_image'] = $path_fk . "/" . $databasename_fk . "/" . $record['foreignkey'] . "/" . $opzione[$listoptionstabella->primarykey] . "/" . $record['frm_show_image'] . "/thumbs/" . $opzione[$record['frm_show_image']] . ".jpg";
                        $tmp2['thumbsize'] = isset($listoptionstabella->fields[$record['frm_show_image']]->thumbsize) ? $listoptionstabella->fields[$record['frm_show_image']]->thumbsize : 16; //dprint_r($tmp2);
                    }
                    if (!isset($tmp_optionvalues[$tmp2['value']]))
                    {
                        $retopzioni[] = $tmp2;
                        $tmp_optionvalues[$tmp2['value']] = true;
                    }
                }
            unset($tmp_optionvalues);
            $tmp['options'] = $retopzioni;
        }
        else
        {

            //-------options written statically in the descriptor  ----->
            if (isset($record['frm_options']) && $record['frm_options'] !== "")
            {
                $tmp_optionvalues = array();
                $retopzioni = array();
                $listoptions = explode(",", $record['frm_options']);
                $iopz = 0;
                foreach ($listoptions as $opzione)
                {
                    if (!isset($tmp_optionvalues[$opzione]))
                    {
                        $tmp_optionvalues[$opzione] = true;
                        $retopzioni[$iopz]['title'] = $opzione;
                        $retopzioni[$iopz]['value'] = $opzione;
                        $iopz++;
                    }
                }

                if (isset($record['frm_options_' . $this->lang]) && $record['frm_options_' . $this->lang] !== "")
                {
                    $iopz = 0;
                    $listoptions = explode(",", $record['frm_options_' . $this->lang]);
                    foreach ($listoptions as $opzione)
                    {
                        $retopzioni[$iopz]['title'] = $opzione;
                        $iopz++;
                    }
                }
                if (isset($record['frm_options_i18n']) && $record['frm_options_i18n'] !== "")
                {

                    $iopz = 0;
                    $listoptions = explode(",", $record['frm_options_i18n']);

                    foreach ($listoptions as $opzione)
                    {
                        $retopzioni[$iopz]['title'] = $this->I18N($opzione);
                        $iopz++;
                    }
                }

                $tmp['options'] = $retopzioni;
                //-------options written statically in the descriptor  -----<
            }
        }

        return $tmp['options'];
    }

    /**
     * HtmlShowUpdateForm
     * show update form
     *
     * @param string $pk
     * @param int $currentleveladmin
     * @param array values
     * @param array errors
     */
    function HtmlShowUpdateForm($pk, $currentleveladmin = false, $nvalues = false, $errors = false)
    {
        $table = XMETATable::xmetadbTable($this->databasename, $this->table, $this->path, $this->tableparams);
        $values = $table->GetRecordByPrimaryKey($pk);
        if (!is_array($values))
            return "";
        foreach ($values as $k => $v)
        {
            $values[$k] = XMETADB_FixEncoding($v, $this->charset_page);
        }
//riempe la traduzione di default col campo originale se questo e' vuoto ------>
        //Es field='pippo' e field_it ='' mette field_it ='pippo'
        foreach ($values as $k => $v)
        {

            if (isset($this->formvals[$k]))
            {
                $fieldform_values = $this->formvals[$k];
                if (isset($fieldform_values['frm_multilanguage']) && $fieldform_values['frm_multilanguage'] == 1)
                {
                    $noprefix = substr($k, 0, strrpos($k, "_"));
                    $lang = explode("_", $k);
                    $lang = $lang[count($lang) - 1];
                    if ($lang == $this->langdefault && empty($values[$k]) && $values[$noprefix] != "")
                        $values[$k] = $values[$noprefix];
                }
            }
        }
//riempe la traduzione di default col campo originale se questo e' vuoto ------<
        if ($nvalues != false)
        {
            foreach ($nvalues as $k => $v)
            {

                $values[$k] = $v;
            }
        }

        return $this->HtmlShowForm(true, $values, $currentleveladmin, $errors);
    }

    /**
     *
     * @param string $pk
     * @param boolean $currentleveladmin
     * @param boolean $nvalues
     * @param boolean $errors 
     */
    function ShowUpdateForm($pk, $currentleveladmin = false, $nvalues = false, $errors = false)
    {
        echo $this->HtmlShowUpdateForm($pk, $currentleveladmin, $nvalues, $errors);
    }

    /**
     * ShowInsertForm
     * show form insert
     *
     * @param int $currentleveladmin
     * @param array $values
     * @param array $errors
     */
    function HtmlShowInsertForm($currentleveladmin = false, $values = false, $errors = false)
    {
        return $this->HtmlShowForm(false, $values, $currentleveladmin, $errors);
    }

    function ShowInsertForm($currentleveladmin = false, $values = false, $errors = false)
    {
        echo $this->HtmlShowInsertForm($currentleveladmin, $values, $errors);
    }

    function EncodeValue($str)
    {
        static $escape = 0;
        $escape++;
        $this->escapechar = empty($this->escapechar) ? "$escape{" . "_" : $this->escapechar;
        if (is_string($str))
            return str_replace("{", $this->escapechar, $str);
        return $str;
    }

    function DecodeValues($str)
    {
        return str_replace($this->escapechar, "{", $str);
    }

    /**
     * ShowForm
     * show the form
     * @param bool $update
     * @param array $oldvalues
     * @param bool $currentleveladmin
     */
    function HtmlShowForm($update = false, $oldvalues = false, $currentleveladmin = false, $errors = false)
    {
        static $formid = 0;
        $formid++;
        foreach ($this->formvals as $k => $fv)
        {
            if (isset($fv['primarykey']) && $fv['primarykey'] == "1")
                $primarykey = $k;
        }

        //if primarykey is missing I force viewing
        $strhiddenfield = "";
        if ($update == true)
            $strhiddenfield = "<input readonly=\"readonly\" type=\"hidden\" name=\"_xmetadbform_pk_$primarykey\" value=\"" . $oldvalues[$primarykey] . "\" />";
        if ($update == true && isset($this->formvals[$primarykey]['frm_show']) && ($this->formvals[$primarykey]['frm_show'] === "0" || $this->formvals[$primarykey]['frm_show'] === 0))
        {
            $strhiddenfield .= "<input type=\"hidden\" name=\"$primarykey\" value=\"" . $oldvalues[$primarykey] . "\" />";
        }
        if ($update == true && isset($this->formvals[$primarykey]['frm_allowupdate']) && $this->formvals[$primarykey]['frm_allowupdate'] == 0)
        {
            $this->formvals[$primarykey]['frm_readonly'] = 1;
            $strhiddenfield .= "<input type=\"hidden\" name=\"$primarykey\" value=\"" . $oldvalues[$primarykey] . "\" />";
        }
        if ($update == true && isset($this->formvals[$primarykey]['frm_setonlyadmin']) && $this->formvals[$primarykey]['frm_setonlyadmin'] != "" && $currentleveladmin < $this->formvals[$primarykey]['frm_setonlyadmin'])
        {
            $strhiddenfield .= "<input type=\"hidden\" name=\"$primarykey\" value=\"" . $oldvalues[$primarykey] . "\" />";
        }
        $htmlitems = "";
        foreach ($this->formvals as $fieldform_valuesk => $fieldform_values)
        {

            if (!isset($fieldform_values['name']))
            {

                //dprint_r("$fieldform_valuesk is incomplete:");
                //dprint_r($fieldform_values);
                continue;
            }
            $fieldform_values['title'] = $fieldform_values['title_insert'];
            if (isset($this->templateobjects[$fieldform_valuesk]))
                $tpobject = $this->templateobjects[$fieldform_valuesk];
            else
                $tpobject = $this->templateobject;
            $fieldform_values['template_id'] = $tpobject;

            //----------------------filters------------------------------------>
            if (isset($fieldform_values['fk_filter_field']) && $fieldform_values['fk_filter_field'] != "")
            {
                if ($update == false)  //check default values
                {
                    $clausule = explode("=", $fieldform_values['fk_filter_field']);
                    if (isset($clausule[1]))
                    {
                        $clausules = explode(",", $fieldform_values['fk_filter_field']);
                        $restr = array();
                        foreach ($clausules as $claus_item)
                        {
                            $clausule = explode("=", $claus_item);
                            if (isset($clausule[1]))
                            {
                                $cname2 = $clausule[1];
                                //if not xxx='yyy'
                                if ($cname2[0] != "'" && $cname2[strlen($cname2) - 1] != "'")
                                {
                                    if (isset($this->formvals[$cname2]['frm_default']) && empty($oldvalues[$cname2]))
                                    {
                                        $oldvalues[$cname2] = $this->formvals[$cname2]['frm_default'];
                                    }
                                }
                            }
                        }
                    }
                }
                $fieldform_values['options'] = $this->LoadOptions($fieldform_values, $oldvalues);
            }

            //----------------------filters------------------------------------<
            if (isset($fieldform_values['frm_group']) && $fieldform_values['frm_group'] != "")
            {
                $gtitle = $fieldform_values['frm_group'];
                $htmlitems .= str_replace("{groupname}", $gtitle, str_replace("{fieldname}", $fieldform_values['name'], $tpobject->templateGroup));
            }
            //--------multilanguage -------------->
            if (isset($fieldform_values['frm_multilanguage']) && $fieldform_values['frm_multilanguage'] == 1)
            {
                continue;
            }
            $fieldform_values['realname'] = $fieldform_values['name'];
            $multilanguage = false;
            $oldval = isset($oldvalues[$fieldform_valuesk]) ? $oldvalues[$fieldform_valuesk] : "";
            $languagesfield = "";
            $lang_user = $this->lang;
            if (isset($fieldform_values['frm_multilanguages']) && $fieldform_values['frm_multilanguages'] != "")
            {
                $multilanguage = true;
                $languagesfield = explode(",", $fieldform_values['frm_multilanguages']);
                if (!in_array($this->lang, $languagesfield))
                    $lang_user = $languagesfield[0];
            }
            // --------multilanguage --------------<
            if (isset($fieldform_values['frm_required']) && $fieldform_values['frm_required'] == 1)
                $fieldform_values['title'] .= " " . $this->requiredtext;
            //------------gestione visualizzazione----------->
            $showfield = true;
            if (isset($fieldform_values['frm_setonlyadmin']) && $fieldform_values['frm_setonlyadmin'] == 1 && $currentleveladmin < $fieldform_values['frm_setonlyadmin'])
                $showfield = false;
            if ($update == true && isset($fieldform_values['frm_allowupdate']))
            {
                if ($fieldform_values['frm_allowupdate'] === "0" || $fieldform_values['frm_allowupdate'] > $currentleveladmin)
                {
                    $showfield = false;
                }
                if ($fieldform_values['frm_allowupdate'] !== "0" && $fieldform_values['frm_allowupdate'] < $currentleveladmin)
                {
                    $showfield = true;
                }
            }
            if (isset($fieldform_values['frm_assoc']) && $fieldform_values['frm_assoc'] != "")
            {
                if (!isset($oldvalues[$fieldform_values['frm_assoc']]) || $oldvalues[$fieldform_values['frm_assoc']] == "")
                {
                    $showfield = false;
                }
            }
            if (isset($fieldform_values['frm_show']) && ($fieldform_values['frm_show'] === "0" || $fieldform_values['frm_show'] === 0 || $fieldform_values['frm_show'] === false))
                $showfield = false;
            $html = "";
            $tplvars = array();
            //if exist custom function ----->
            if (isset($fieldform_values['frm_functionform']) && $fieldform_values['frm_functionform'] != "" && function_exists($fieldform_values['frm_functionform']))
            {
                eval("\$html = " . $fieldform_values['frm_functionform'] . '($oldvalues,$fieldform_valuesk);');
                $showfield = false;
            }
            //if exist custom function -----<
            else
            {
                if ($showfield == false)
                    continue;
                //------------gestione visualizzazione-----------<

                $fieldform_values['messages'] = $this->messages;
                //valore di default
                if (isset($fieldform_values['frm_default']) && $update == false)
                {
                    if (empty($oldvalues[$fieldform_valuesk]))
                    {
                        $oldval = $fieldform_values['frm_default'];
                    }
                }
                $fieldform_values['value'] = $oldval;
                $fieldform_values['is_update'] = $update;
                $fieldform_values['fieldform'] = $this;
                $fieldform_values['oldvalues'] = $oldvalues;
                $fieldform_values['oldvalues_primarikey'] = $primarykey;
                $fieldform_values['multilanguage'] = $multilanguage;
                $fieldform_values['lang_user'] = $lang_user;
                $fieldform_values['lang'] = $this->lang;
                $fieldform_values['languagesfield'] = $languagesfield;
                $fieldform_values['frm_help'] = isset($fieldform_values['frm_help']) ? $fieldform_values['frm_help'] : "";
                $htmlitem = (isset($errors[$fieldform_valuesk])) ? $tpobject->templateItemError : $tpobject->templateItem;
                if (strpos($htmlitem, "inputattributes:") !== false)
                {
                    $t = preg_match("/<!-- inputattributes:([^>]*)-->/is", $htmlitem, $matches);
                    if (!empty($matches[1]))
                    {
                        $fieldform_values['htmlattributes'] = $matches[1];
                    }
                }
                if (strpos($htmlitem, "inputattributes {$fieldform_values['frm_type']}:") !== false)
                {
                    $t = preg_match("/<!-- inputattributes {$fieldform_values['frm_type']}:([^>]*)-->/is", $htmlitem, $matches);
                    if (!empty($matches[1]))
                    {
                        $fieldform_values['htmlattributes'] = $matches[1];
                    }
                }
                if (!empty($fieldform_values['htmlattributes']))
                {
                    $fieldform_values['htmlattributes'] = $this->ApplyTplString($fieldform_values['htmlattributes'], $fieldform_values);
                    $fieldform_values['htmlattributes'] = str_replace("{fieldname}", "{$fieldform_values['name']}", $fieldform_values['htmlattributes']);
                }
                //$skeepsimbol=uniqid("s");
                if ($multilanguage)
                {

                    $html = "<table style=\"border-collapse:collapse;border:0px;padding:0px;width:100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr style=\"padding:0px:\"><td style=\"border:0px;padding:0px;margin:0px\" >";
                    $html .= xmetadb_frm_draw_languages($fieldform_values, $fieldform_values['lang'], $formid);
                    $html .= "</td></tr><tr><td style=\"border:0px;padding:0px;margin:0px\"><div style=\"padding:0px;border:0px;position:relative;display:block;\">";
                    $margin = 0;
                    foreach ($languagesfield as $tl)
                    {
                        $htmlitem_l = "{input}";
                        $suffix = "_$tl";
                        $fieldform_values['name'] = $fieldform_values['realname'] . $suffix;
                        $fieldform_values['value'] = isset($oldvalues[$fieldform_values['realname'] . $suffix]) ? $oldvalues[$fieldform_values['realname'] . $suffix] : "";
                        if ($tl != $lang_user)
                        {
                            if (!isset($oldvalues[$fieldform_values['realname'] . $suffix]))
                                $oldvalues[$fieldform_values['realname'] . $suffix] = "";
                            $html .= "\n<div style=\"top:0px;left:0px;position:absolute;background-color:transparent;border:0px;padding:0px;margin:{$margin}px;display:block;overflow:hidden;height:1px;width:1px;\" id=\"__frmdb_$formid" . $fieldform_values['realname'] . "$suffix" . "\" >";
                            // ml $html .= $this->formclass[$fieldform_valuesk]->show($fieldform_values);
                            $html .= "" . $this->HtmlField($tpobject, $fieldform_valuesk, $fieldform_values, $htmlitem_l);
                        }
                        else
                        {
                            $html .= "\n<div style=\"top:0px;left:0px;position:relative;background-color:transparent;border:0px;padding:0px;margin:{$margin}px;display:block;overflow:hidden;\" id=\"__frmdb_$formid" . $fieldform_values['name'] . "\" >";
                            //ml $html .= $this->formclass[$fieldform_valuesk]->show($fieldform_values);
                            $html .= $this->HtmlField($tpobject, $fieldform_valuesk, $fieldform_values, $htmlitem_l);
                        }
                        $html .= "</div>";
                    }
                    $html .= "</div></td></tr></table>";
                    //die();
                    // dprint_xml($htmlitem);
                    $htmlitem = (isset($errors[$fieldform_valuesk])) ? $tpobject->templateItemError : $tpobject->templateItem;
                    $htmlitem = $this->ApplyTplString($htmlitem, array("title" => $fieldform_values['title'], "input" => $htmlitem_l));
                    //  dprint_xml($htmlitem);
                    //$strhiddenfield = "";
                }
                else
                {
                    $fieldUseTemplate = false;
                    if ($showfield && $fieldform_valuesk != "")
                    {
                        $html = $this->HtmlField($tpobject, $fieldform_valuesk, $fieldform_values, $htmlitem);
                    }
                }
                $tplvars['title'] = $fieldform_values['title'];
                $tplvars['help'] = isset($fieldform_values['frm_help']) ? $fieldform_values['frm_help'] : "";
                $tplvars['fieldname'] = $fieldform_values['name'];
                $tplvars['fieldtype'] = $fieldform_values['frm_type'];
                if (isset($errors[$fieldform_valuesk]['error']))
                    $tplvars['error'] = $errors[$fieldform_valuesk]['error'];
                $htmlitem = $this->ApplyTplString($htmlitem, $tplvars);
            }


            $tplvars['input'] = $this->EncodeValue($html . $strhiddenfield);
            $tplvars['fieldname'] = $fieldform_values['name'];
            $tplvars['fieldtype'] = $fieldform_values['frm_type'];
            $tplvars['help'] = $fieldform_values['frm_help'];
            $strhiddenfield = "";
            if (isset($fieldform_values['frm_endgroup']))
            {
                $htmlitem .= $tpobject->templateEndGroup;
            }
            $htmlitems .= $this->ApplyTplString($htmlitem, $tplvars);
            //----retype---->
            if (isset($fieldform_values['frm_retype']) && $fieldform_values['frm_retype'] == 1)
            {
                $htmlitem = $tpobject->templateItem;
                $fieldform_values['name'] = $fieldform_values['name'] . "_retype";
                $fieldform_values['title'] = $fieldform_values['title'] . " ({$this->messages["_XMLDBRETYPE"]})";
                $fieldform_values['value'] = isset($_POST[$fieldform_values['name']]) ? $_POST[$fieldform_values['name']] : "";
                $html = $this->HtmlField($tpobject, $fieldform_valuesk, $fieldform_values, $htmlitem);
                $tplvars = $fieldform_values;
                $tplvars['input'] = $html;
                $tplvars['fieldname'] = $fieldform_values['name'];
                $tplvars['fieldtype'] = $fieldform_values['frm_type'];
                $tplvars['help'] = "";
                $htmlitem = $this->ApplyTplString($htmlitem, $tplvars);
                $htmlitems .= $htmlitem;
            }
            //----retype----<
        }

        $htmlform = str_replace("{formcontents}", $htmlitems, $tpobject->templateContents);

        return $this->DecodeValues($this->DecodeValues($htmlform));
    }

    function ApplyTplString($str, $vars)
    {
        if (function_exists("FN_TPL_ApplyTplString"))
        {
            foreach ($vars as $k => $v)
            {
                if (is_object($v))
                {
                    unset($vars[$k]);
                }
            }
            return FN_TPL_ApplyTplString($str, $vars);
        }
        foreach ($vars as $key => $value)
        {
            if (is_string($value) || is_numeric($value))
            {
                $str = str_replace("{" . $key . "}", $this->EncodeValue($value), $str);
            }
        }
        // dprint_xml($str);
        return $this->DecodeValues($str);
    }

    function HtmlField($tpobject, $fieldform_valuesk, $fieldform_values, &$htmlitem)
    {
        $tp_str = $tpobject->templateItem;
        $out = array();
        if (preg_match("/<!-- input -->(.*)<!-- endinput -->/is", $tp_str, $out) || preg_match("/<!-- input -->(.*)<!-- end input -->/is", $tp_str, $out))
        {
            $htmlInput = empty($out[0]) ? "" : $out[0];
            $htmlInput = $this->ApplyTplString($htmlInput, $fieldform_values);
            $html = $htmlInput;
            $htmlitem = str_replace($out[0], '{input}', $htmlitem);
        }
        else
        {
            $tmp = array();
            foreach ($fieldform_values as $k => $v)
            {
                $tmp[$k] = $this->EncodeValue($v);
            }
            $html = $this->formclass[$fieldform_valuesk]->show($tmp);
        }

        return $html;
    }

    function ShowForm($update = false, $oldvalues = false, $currentleveladmin = false, $errors = false)
    {
        echo $this->HtmlShowForm($update, $oldvalues, $currentleveladmin, $errors);
    }

    function ShowView($values, $currentleveladmin = false, $errors = false)
    {
        echo $this->HtmlShowView($values, $currentleveladmin, $errors);
    }

    /**
     * show the record
     * @param bool $update
     * @param array $oldvalues
     * @param bool $currentleveladmin
     */
    function HtmlShowView($values, $currentleveladmin = false, $errors = false)
    {
        foreach ($this->formvals as $k => $fv)
        {
            if (isset($fv['primarykey']) && $fv['primarykey'] == "1")
                $primarykey = $k;
        }
        //if primarykey is missing I force viewing
        //dprint_r($this->templateviewobjects);
        $htmlitems = "";
        $gtitle = "";
        foreach ($this->formvals as $fieldform_valuesk => $fieldform_values)
        {

            $oldval = isset($values[$fieldform_valuesk]) ? $values[$fieldform_valuesk] : "";

            if (isset($this->templateviewobjects[$fieldform_valuesk]))
            {
                $tpobject = $this->templateviewobjects[$fieldform_valuesk];
            }
            else
            {
                $tpobject = $this->templateviewobject;
            }
            //$fieldform_values['template']=$tpobject;
            //if ( isset($fieldform_values['fk_filter_field']) && $fieldform_values['fk_filter_field'] != "" )
            //	$fieldform_values['options'] = $this->LoadOptions($fieldform_values, $values);
            if (isset($fieldform_values['view_group']) && $fieldform_values['view_group'] != "")
            {
                $gtitle = $fieldform_values['view_group'];
                $gtitle_i18n = XMETADB_i18n($fieldform_values['view_group']);
                if (isset($fieldform_values['view_group_i18n']) && $fieldform_values['view_group_i18n'] != "")
                {
                    $gtitle_i18n = XMETADB_i18n($fieldform_values['view_group_i18n']);
                }
                if (!empty($this->templateviewGroups[$gtitle]))
                    $_name_group = str_replace("{groupname}", $gtitle, $this->templateviewGroups[$gtitle]);
                else
                    $_name_group = str_replace("{groupname}", $gtitle, $tpobject->templateviewGroup);
                $_tile_group = str_replace("{grouptitle}", $gtitle_i18n, $_name_group);
                $htmlitems .= $_tile_group;
            }

            //----foreignkey---->>
            if ($oldval != "" && is_array($fieldform_values['options']) && count($fieldform_values['options']) > 0 /* && empty($fieldform_values['foreignkey']) */)
            {
                $tit = "";
                $foreignkeyvalues = explode(",", $oldval);
                $sep = "";
                foreach ($foreignkeyvalues as $foreignkeyVal)
                {
                    foreach ($fieldform_values['options'] as $option)
                    {
                        if (isset($option['value']) && $option['value'] == $foreignkeyVal)
                        {
                            $tit .= $sep . $option['title'];
                            $sep = ",";
                        }
                    }
                }
                $oldval = $tit;
            }
            //--------multilanguage -------------->
            if (isset($fieldform_values['frm_multilanguage']) && $fieldform_values['frm_multilanguage'] == 1)
            {
                continue;
            }
            $fieldform_values['realname'] = $fieldform_values['name'];
            $multilanguage = false;
            $languagesfield = "";
            $lang_user = $this->lang;
            if (isset($fieldform_values['frm_multilanguages']) && $fieldform_values['frm_multilanguages'] != "")
            {
                $multilanguage = true;
                $languagesfield = explode(",", $fieldform_values['frm_multilanguages']);
                if (!in_array($this->lang, $languagesfield))
                    $lang_user = $languagesfield[0];
            }
            // --------multilanguage --------------<
            //------------gestione visualizzazione----------->
            $showfield = true;

            if (isset($fieldform_values['frm_viewonlyadmin']) && $fieldform_values['frm_viewonlyadmin'] == 1 && $currentleveladmin < $fieldform_values['frm_viewonlyadmin'])
                $showfield = false;
            if (isset($fieldform_values['frm_assoc']) && $fieldform_values['frm_assoc'] != "")
            {
                if (!isset($oldvalues[$fieldform_values['frm_assoc']]) || $oldvalues[$fieldform_values['frm_assoc']] == "")
                {
                    $showfield = false;
                }
            }

            if (isset($fieldform_values['view_show']))
                $fieldform_values['frm_show'] = $fieldform_values['view_show'];
            if (isset($fieldform_values['frm_show']) && ($fieldform_values['frm_show'] === "0" || $fieldform_values['frm_show'] === 0))
                $showfield = false;
            $html = "";

            //if exist custom function ----->
            if (isset($fieldform_values['frm_functionview']) && $fieldform_values['frm_functionview'] != "" && function_exists($fieldform_values['frm_functionview']))
            {
                eval("\$html = " . $fieldform_values['frm_functionview'] . '($values,$fieldform_valuesk);');
                $showfield = false;
            }
            //if exist custom function -----<
            else
            {

                if ($showfield == false || $oldval == "")
                {
                    $htmlitem = "";
                }
                else
                {


                    //------------gestione visualizzazione-----------<
                    $fieldform_values['name'] = $fieldform_valuesk;
                    $fieldform_values['messages'] = $this->messages;

                    $fieldform_values['value'] = XMETADB_FixEncoding($oldval, $this->charset_page);
                    $fieldform_values['values'] = $values;
                    $fieldform_values['fieldform'] = $this;
                    $fieldform_values['oldvalues'] = $values;
                    $fieldform_values['oldvalues_primarikey'] = $primarykey;
                    $fieldform_values['multilanguage'] = $multilanguage;
                    $fieldform_values['lang_user'] = $lang_user;
                    $fieldform_values['lang'] = $this->lang;
                    $fieldform_values['languagesfield'] = $languagesfield;
                    $fieldform_values['frm_help'] = isset($fieldform_values['frm_help']) ? $fieldform_values['frm_help'] : "";

                    if ($showfield && $fieldform_valuesk != "")
                    {
                        //attributes--->
                        if (strpos($tpobject->templateviewItemError, "inputattributes:") !== false)
                        {

                            $t = preg_match("/<!-- inputattributes:([^>]*)-->/is", $tpobject->templateviewItemError, $matches);
                            if (!empty($matches[1]))
                            {
                                $fieldform_values['htmlattributes'] = $matches[1];
                            }
                        }
                        if (strpos($tpobject->templateviewItemError, "inputattributes {$fieldform_values['frm_type']}:") !== false)
                        {
                            $t = preg_match("/<!-- inputattributes {$fieldform_values['frm_type']}:([^>]*)-->/is", $tpobject->templateviewItemError, $matches);
                            if (!empty($matches[1]))
                            {
                                $fieldform_values['htmlattributes'] = $matches[1];
                            }
                        }
                        if (!empty($fieldform_values['htmlattributes']))
                        {
                            $fieldform_values['htmlattributes'] = $this->ApplyTplString($fieldform_values['htmlattributes'], $fieldform_values);
                            $fieldform_values['htmlattributes'] = str_replace("{fieldname}", "{$fieldform_values['name']}", $fieldform_values['htmlattributes']);
                        }

                        //attributes---<                           
                        if ($oldval != "")
                        {
                            //dprint_r ("xmetadb_frm_view_".$fieldform_values['frm_type']);
                            $fname = "xmetadb_frm_view_" . $fieldform_values['frm_type'];
                            if (function_exists($fname))
                            {




                                $html = $fname($fieldform_values);
                            }
                            elseif (method_exists($this->formclass[$fieldform_valuesk], "view"))
                            {
                                $html = $this->formclass[$fieldform_valuesk]->view($fieldform_values);
                            }
                            else
                            {
                                $html = $oldval;
                            }
                        }
                    }
                    if (isset($errors[$fieldform_valuesk]))
                    {
                        $htmlitem = str_replace("{title}", $fieldform_values['title'], $tpobject->templateviewItemError);
                        $htmlitem = str_replace("{error}", $errors[$fieldform_valuesk]['error'], $htmlitem);
                    }
                    else
                        $htmlitem = str_replace("{title}", $fieldform_values['title'], $tpobject->templateviewItem);

                    $htmlitem = str_replace("{fieldname}", $fieldform_values['name'], $htmlitem);
                    $htmlitem = str_replace("{fieldtype}", $fieldform_values['frm_type'], $htmlitem);
                    $htmlitem = str_replace("{input}", $this->EncodeValue($html), $htmlitem);
                }
            }
            if (isset($fieldform_values['view_endgroup']))
            {
                if (!empty($this->templateviewEndGroups[$gtitle]))
                {
                    $htmlitem .= $this->templateviewEndGroups[$gtitle];
                }
                else
                {
                    $htmlitem .= $tpobject->templateviewEndGroup;
                }
            }
            $htmlitems .= $htmlitem;
        }
        $htmlform = str_replace("{formcontents}", $htmlitems, $tpobject->templateviewContents);
        return $htmlform;
    }

    /**
     * 
     * @param type $oldvalues
     * @return type
     */
    function GetByPostJson($oldvalues = array())
    {
        $_VAR = json_decode(file_get_contents('php://input'), JSON_OBJECT_AS_ARRAY);
        return $this->GetByVar($oldvalues, $_VAR);
    }

    /**
     * 
     * @param type $oldvalues
     * @return type
     */
    function GetByPost($oldvalues = array())
    {
        return $this->GetByVar($oldvalues, $_POST);
    }

    /**
     * getbypost
     *
     * riempe un array con i valori ricevuti tramite post
     * @param array $oldvalues i valori che non sono in post vanno mantenuti
     */
    function GetByVar($oldvalues = array(), $_VAR = array())
    {
        $newvalues = array();
        ////dprint_r($_POST);
        foreach ($this->formvals as $key => $value)
        {

            if ((isset($value['type']) && ($value['type'] == 'check')) || (isset($value['frm_type']) && ($value['frm_type'] == 'check')))
            {
                if (isset($_VAR["__check__$key"]) && !isset($_VAR["$key"]))
                {
                    $newvalues[$key] = "";
                }
                if (isset($_VAR["__check__$key"]) && isset($_VAR[$key]))
                {
                    $newvalues[$key] = $_VAR[$key];
                }
                if (isset($oldvalues[$key]) && !isset($newvalues[$key]))
                {
                    $newvalues[$key] = $oldvalues[$key];
                }
            }
            else
            if (isset($value['type']) && ($value['type'] == 'image' || $value['type'] == 'file') && isset($_FILES[$key]['name']))
            {
                if (isset($_VAR["__isnull__$key"]) && $_VAR["__isnull__$key"] == "null")
                {
                    $newvalues[$key] = "";
                }
                else
                if ($_FILES[$key]['name'] != "")
                {
                    $newvalues[$key] = $_FILES[$key]['name'];
                    if (ini_get('magic_quotes_gpc') == 1)
                    {
                        $newvalues[$key] = stripslashes($newvalues[$key]);
                    }
                    //non puo' contenere barre
                    $newvalues[$key] = str_replace("\\", "", $newvalues[$key]);
                    $newvalues[$key] = str_replace("/", "", $newvalues[$key]);
                }
            }
            else
            {
                if (isset($_VAR[$key]))
                {
                    $newvalues[$key] = $_VAR[$key];
                    if (ini_get('magic_quotes_gpc') == 1)
                    {
                        $newvalues[$key] = stripslashes($newvalues[$key]);
                    }
                    if (isset($value['frm_allowhtml']) && $value['frm_allowhtml'] != "true")
                    {
                        $newvalues[$key] = htmlentities($_VAR[$key]);
                    }
                }
                if (isset($oldvalues[$key]) && !isset($newvalues[$key]))
                {
                    $newvalues[$key] = $oldvalues[$key];
                }
            }
        }
        foreach ($this->formvals as $key => $value)
        {

            if (!empty($this->formvals[$key]['frm_multilanguages']))
            {
                $multilanguagevalue = "";
                $ll = explode(",", $this->formvals[$key]['frm_multilanguages']);
                foreach ($ll as $l)
                {
                    if (!empty($newvalues[$key . "_{$l}"]))
                    {
                        $newvalues[$key] = $newvalues[$key . "_{$l}"];
                        break;
                    }
                }
                if (!isset($newvalues[$key]) && isset($newvalues[$key . "_{$l}"]))
                {
                    $newvalues[$key] = "";
                }
            }
        }

        return $newvalues;
    }

    /**
     *
     * @param array $newvalues
     * @return array
     */
    function InsertRecord($newvalues)
    {

        foreach ($newvalues as $key => $v)
        {
            if (!empty($this->formclass[$key]) && method_exists($this->formclass[$key], "formtovalue"))
            {
                $newvalues[$key] = $this->formclass[$key]->formtovalue($v, $this->formvals[$key]);
                $newvalues[$key] = XMETADB_ConvertEncoding($newvalues[$key], $this->charset_page, $this->charset_storage);
            }
            if (isset($newvalues[$key . "_{$this->langdefault}"]) && !empty($this->formvals[$key]['frm_multilanguages']))
            {
                $newvalues[$key] = $newvalues[$key . "_{$this->langdefault}"];
                $newvalues[$key] = XMETADB_ConvertEncoding($newvalues[$key], $this->charset_page, $this->charset_storage);
            }
        }
        return $this->xmltable->InsertRecord($newvalues);
    }

    /**
     *
     * @param array $newvalues
     * @param strung $pkvalue
     * @return array
     */
    function UpdateRecord($newvalues, $pkvalue = false)
    {


        foreach ($newvalues as $k => $v)
        {

            if (isset($this->formclass[$k]) && method_exists($this->formclass[$k], "formtovalue"))
            {
                $newvalues[$k] = $this->formclass[$k]->formtovalue($v, $this->formvals[$k]);
            }
            if (isset($newvalues[$k . "_{$this->langdefault}"]) && !empty($this->formvals[$k]['frm_multilanguages']))
            {
                $newvalues[$k] = $newvalues[$k . "_{$this->langdefault}"];
            }
        }
        foreach ($newvalues as $k => $v)
            $newvalues[$k] = XMETADB_ConvertEncoding($newvalues[$k], $this->charset_page, $this->charset_storage);
        return $this->xmltable->UpdateRecord($newvalues, $pkvalue);
    }

    /**
     *
     * @param string $pkvalue
     * @return array
     */
    function GetRecordTranslatedByPrimarykey($pkvalue, $keep_fk = true)
    {
        $rec = $this->xmltable->GetRecordByPrimaryKey($pkvalue);
        return $this->GetRecordTranslated($rec, $keep_fk);
    }

    /**
     *
     * @param array $newvalues
     * @return array 
     */
    function VerifyInsert($newvalues)
    {
        return $this->Verify($newvalues, false);
    }

    /**
     *
     * @param array $newvalues
     * @return array
     */
    function VerifyUpdate($newvalues, $pk = null)
    {
        return $this->Verify($newvalues, true, $pk);
    }

    /**
     *
     * @param string $newvalues
     * @param bool $update
     * @return array
     */
    function Verify($newvalues, $update = false, $pk = null)
    {
        $ret = array();
        $_VAR = $_POST;
        $allerrors = "";
        $allerrors_sep = "";
        foreach ($this->formvals as $key => $value)
        {
            if (empty($value['name']))
                continue;
            $err = "";
            $errsep = "";

            if (!empty($_FILES[$key]['error']) && $_FILES[$key]['error'] != 4)
            {
                $errorMsg = array(
                    0 => "There is no error, the file uploaded with success",
                    1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
                    2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
                    3 => "The uploaded file was only partially uploaded",
                    4 => "No file was uploaded",
                    6 => "Missing a temporary folder"
                );
                $err .= $errsep . isset($errorMsg[$_FILES[$key]['error']]) ? XMETADB_i18n($errorMsg[$_FILES[$key]['error']]) : XMETADB_i18n("error");
                $errsep = " - ";
            }
            if ($value['type'] == 'image' && isset($_FILES[$key]['tmp_name']) && $_FILES[$key]['tmp_name'] != "")
            {
                if (!function_exists("getimagesize"))
                    $err .= _FNNOGDINSTALL;
                if (!@ getimagesize($_FILES[$key]['tmp_name']))
                    $err .= $this->messages["_XMLDBNOTVALIDIMAGE"];
                if (isset($value['frm_maximagesize']) && $value['frm_maximagesize'] != "")
                {
                    list($width, $height) = getimagesize($_FILES[$key]['tmp_name']);
                    if ($width > $value['frm_maximagesize'] || $height > $value['frm_maximagesize'])
                    {
                        $err .= $errsep . $this->messages["_XMLDBTOOBIG"] . " " . $value['frm_maximagesize'] . " pixels";
                        $errsep = " - ";
                    }
                }
            }
            $skip_required_check = false;
            if ($value['type'] == 'image' || $value['type'] == 'file')
            {
                if ($update == true)
                {
                    $skip_required_check = true;
                }
            }
            if (!$skip_required_check &&
                    (!empty($value['frm_required']) || !empty($value['required'])) &&
                    (empty($newvalues[$key]) || trim($newvalues[$key]) == "")
            )
            {
                if (!empty($value['frm_error']))
                    $err .= $errsep . XMETADB_i18n($value['frm_error']);
                else
                    $err .= $errsep . $this->messages["_XMLDBREQUIRED"];
                $errsep = " - ";
            }

            if (isset($value['frm_required_condition']) && $value['frm_required_condition'] != "")
            {
                $cond_ = preg_replace("/(\\w+)( = )/", 'isset($newvalues[\'${1}\']) && \$newvalues[\'${1}\'] == ', trim(ltrim($value['frm_required_condition'])));
                $cond_ = preg_replace("/(\\w+)( > )/", 'isset($newvalues[\'${1}\']) && \$newvalues[\'${1}\'] > ', trim(ltrim($cond_)));
                $cond_ = preg_replace("/(\\w+)( < )/", 'isset($newvalues[\'${1}\']) && \$newvalues[\'${1}\'] < ', trim(ltrim($cond_)));
                $cond_ = preg_replace("/(\\w+)( ! )/", 'isset($newvalues[\'${1}\']) && \$newvalues[\'${1}\'] != ', trim(ltrim($cond_)));
                $req = false;
                eval("if ($cond_){\$req=true;}");
                if ($req == true && (!isset($newvalues[$key]) || trim($newvalues[$key]) == ""))
                {
                    if (!empty($value['frm_error']))
                        $err .= $errsep . XMETADB_i18n($value['frm_error']);
                    else
                        $err .= $errsep . $this->messages["_XMLDBREQUIRED"];
                    $errsep = " - ";
                }
            }
            $checkDuplicate = false;
            if ($update == false && !empty($newvalues[$key]) && (!empty($value['unique']) || !empty($value['primarykey'])))
            {
                $checkDuplicate = true;
            }
            if ($update == true && $pk !== null && (!empty($value['unique']) || !empty($value['primarykey'])) && $pk != $newvalues[$key])
            {
                $checkDuplicate = true;
            }
            if ($checkDuplicate)
            {
                $restr = array($key => $newvalues[$key]);
                if (!empty($value['primarykey']) && is_array($this->xmltable->primarykey))
                {
                    $restr = array();
                    foreach ($this->xmltable->primarykey as $pkk)
                    {
                        $restr[$pkk] = $newvalues[$pkk];
                    }
                    $t = $this->xmltable->GetRecords($restr);
                }
                else
                    $t = $this->xmltable->GetRecords($restr);
                if (is_array($t) && count($t) > 0)
                {

                    if ($update != true)
                    {
                        $err .= $errsep . $this->messages["_XMLDBEXISTS"];
                        $errsep = " - ";
                    }
                    //check duplicate in update -------->
                    else
                    {
                        $duplicate = false;
                        if (count($t) > 1)
                        {
                            $duplicate = true;
                        }
                        else
                        {
                            if (!is_array($this->xmltable->primarykey))
                            {
                                if ($t[0][$this->xmltable->primarykey] != $newvalues[$this->xmltable->primarykey])
                                {
                                    $duplicate = true;
                                }
                            }
                            else
                            {
                                foreach ($this->xmltable->primarykey as $pkk)
                                {
                                    if ($t[0][$pkk] != $newvalues[$pkk])
                                    {
                                        $duplicate = true;
                                    }
                                }
                            }
                        }
                        if ($duplicate)
                        {
                            $err .= $errsep . $this->messages["_XMLDBEXISTS"];
                            $errsep = " - ";
                        }
                        //check duplicate in update --------<
                    }
                }
            }
            if (isset($value['frm_retype']) && $value['frm_retype'] == 1)
            {
                if (isset($_VAR[$key . "_retype"]) && isset($_VAR[$key]))
                    if (($_VAR[$key] != "" && $_VAR[$key] != $_VAR[$key . "_retype"]) || ($_VAR[$key . "_retype"] != "" && $_VAR[$key . "_retype"] != $_VAR[$key]))
                    {
                        $err .= $errsep . $this->messages["_XMLDBERRORRETYPE"];
                        $errsep = " - ";
                    }
            }
            if (isset($value['frm_validator']) && $value['frm_validator'] != "")
            {
                if (isset($newvalues[$key]) && $newvalues[$key] != "" || (isset($value['frm_validateifnull']) && $value['frm_validateifnull'] == 1)) // chiamo la funzione di validazione del campo
                    if (function_exists($value['frm_validator']))
                        if (($retvalidator = $value['frm_validator']($newvalues[$key], $update, $newvalues)) == false)
                        {
                            $err .= $errsep . $this->messages["_XMLDBNOTVALIDFIED"];
                            $errsep = " - ";
                        }
                        else
                        {
                            if (is_string($retvalidator))
                            {
                                $err .= $errsep . $retvalidator;
                                $errsep = " - ";
                            }
                        }
            }
            if (isset($value['frm_preg_match_validator']) && $value['frm_preg_match_validator'] != "")
            {
                if (isset($newvalues[$key]) && $newvalues[$key] != "" || (isset($value['frm_validateifnull']) && $value['frm_validateifnull'] == 1)) // chiamo la funzione di validazione del campo
                {
                    if (($retvalidator = preg_match($value['frm_preg_match_validator'], $newvalues[$key])) != true)
                    {
                        $err .= $errsep . $this->messages["_XMLDBNOTVALIDFIED"];
                        $errsep = " - ";
                    }
                    else
                    {
                        
                    }
                }
            }
            if (isset($value['frm_validchars']) && $value['frm_validchars'] != "")
            {
                for ($i = 0; $i < strlen($newvalues[$key]); $i++)
                {
                    if (false === strpos($value['frm_validchars'], $newvalues[$key][$i]))
                    {
                        $err .= $errsep . $this->messages["_XMLDNOTVALIDCHARS"];
                        $errsep = " - ";
                        break;
                    }
                }
            }
            if ($err != "")
            {
                $ret[$key]['title'] = $value['title'];
                $ret[$key]['field'] = $key;
                $ret[$key]['error'] = $err;
                $allerrors .= "$allerrors_sep{$value['title']}: " . $err;
                $allerrors_sep = " - ";
            }
        }
        if ($err)
        {

            $ret['_errors']['title'] = "error";
            $ret['_errors']['field'] = "_errors";
            $ret['_errors']['error'] = trim(ltrim($allerrors));
        }
        //dprint_r($ret);
        return $ret;
    }

    /**
     * translate record
     * if field is "record" and lang is "it" replaces it with "record_it"
     *
     * @param array $rec
     * @return array
     */
    function GetRecordTranslated($rec, $keep_fk = true)
    {

        if (!is_array($this->xmltable->primarykey) && !isset($rec[$this->xmltable->primarykey]))
            return false;
        if (count($rec) != count($this->xmltable->fields))
        {
            $rec = $this->xmltable->GetRecordByPrimaryKey($rec[$this->xmltable->primarykey]);
        }


        if (is_array($rec))
        {
            $tmp = $rec;
            $ret = $rec;

            foreach ($tmp as $key => $value)
            {
                $found = false;
                if (isset($tmp[$key . "_" . $this->lang]) && $tmp[$key . "_" . $this->lang] != "" && isset($this->formvals[$key . "_" . $this->lang]['frm_multilanguage']) && $this->formvals[$key . "_" . $this->lang]['frm_multilanguage'] == 1)
                {
                    $ret[$key] = $tmp[$key . "_" . $this->lang];
                    unset($ret[$key . "_" . $this->lang]);
                }
                if (isset($ret[$key]))
                    $ret[$key] = XMETADB_FixEncoding($ret[$key], $this->charset_page);
            }
            //----------------foreign key ------------------------------------->
            foreach ($this->formvals as $key => $field)
            {
                $value = "";
                if (!empty($field['type']) && $field['type'] == "image")
                {
                    $ret['_url_' . $key] = $this->xmltable->get_file($rec, $field['name']);
                }
            }
            if (!$keep_fk)
            {
                foreach ($this->formvals as $key => $field)
                {
                    if (!empty($field['foreignkey']) && !empty($field['fk_show_field']))
                    {
                        $r = array();
                        $tfk[$field['fk_link_field']] = XMETATable::xmetadbTable($this->xmltable->databasename, $field['foreignkey'], $this->xmltable->path,$this->tableparams);
                        $tablefk = $tfk[$field['fk_link_field']];
                        $f = $field['fk_link_field'];
                        if ($field['fk_link_field'] != $field['fk_show_field'])
                        {
                            $f .= "|" . $field['fk_show_field'];
                        }
                        if (isset($tfk[$field['fk_link_field']]->fields[$field['fk_link_field'] . "_{$this->lang}"]))
                            $f .= "|" . $field['fk_show_field'] . "_{$this->lang}";
                        //echo $f;

                        $r[$field['fk_link_field']] = $rec[$field['name']];
                        //dprint_r($value);
                        $showfields = explode(",", $field['fk_show_field']);
                        $value = "";
                        $sep = "";
                        foreach ($showfields as $showfield)
                        {
                            $tvalue = $tablefk->GetRecord(array($field['fk_link_field'] => $rec[$field['name']]));
                            if (isset($tvalue["{$showfield}_{$this->lang}"]) && $tvalue["{$showfield}_{$this->lang}"] != "")
                                $value .= $sep . $tvalue["{$showfield}_{$this->lang}"];
                            elseif (isset($tvalue["{$showfield}_en"]) && $tvalue["{$showfield}_en"] != "")
                                $value .= $sep . $tvalue[$showfield . "_en"];
                            elseif (isset($tvalue[$showfield]))
                                $value .= $sep . $tvalue[$showfield];
                            $sep = "-";
                        }
                        $ret[$key] = $value;
                    }
                    else
                    {
                        if (isset($field['options']) && is_array($field['options']))
                        {
                            $values = array();
                            $values_opt = explode(",", $rec[$field['name']]);
                            foreach ($field['options'] as $opt)
                            {
                                foreach ($values_opt as $value_opt)
                                {
                                    if (!isset($opt['value']))
                                    {
                                        dprint_r("error xmetadb_frm: {$opt['value']} not exists ");
                                    }
                                    if ($value_opt == $opt['value'])
                                    {
                                        $values[] = $opt['title'];
                                    }
                                }
                            }
                            $value = implode(",", $values);

                            $ret[$key] = $value;
                        }
                    }
                }
            }
            //----------------foreign key -------------------------------------<

            return $ret;
        }
        return null;
    }
}

/**
 * draw languages in form
 *
 * @param array $fieldform_values
 * @param string $current
 */
function xmetadb_frm_draw_languages($fieldform_values, $current, $formid)
{
    $languages_list = explode(",", $fieldform_values['frm_multilanguages']);
    $html = "";
    if (isset($languages_list[0]))
    {
        foreach ($languages_list as $tab_language_id)
        {
            $iddiv1 = "__frmdb_$formid" . $fieldform_values['realname'] . "_$tab_language_id";
            $jsdb = "";

            foreach ($languages_list as $tl2)
            {
                if ($tl2 != $tab_language_id)
                {
                    $iddiv = "__frmdb_$formid" . $fieldform_values['realname'] . "_$tl2";
                    $jsdb .= ";document.getElementById('img_$iddiv').style.borderStyle='solid'";
                    $jsdb .= ";document.getElementById('$iddiv').style.position='absolute'";
                    $jsdb .= ";document.getElementById('$iddiv').style.height='1px'";
                    $jsdb .= ";document.getElementById('$iddiv').style.width='1px'";
                }
            }
            $jsdb .= ";document.getElementById('$iddiv1').style.position='relative'";
            $jsdb .= ";document.getElementById('$iddiv1').style.height='auto'";
            $jsdb .= ";document.getElementById('$iddiv1').style.width='auto'";
            $jsdb .= ";document.getElementById('$iddiv1').style.zoom='1'";
            $border = ($current != $tab_language_id) ? "solid" : "inset";
            $html .= "<button title=\"{$fieldform_values['title']} : $tab_language_id\" 
			style=\"border:2px $border #dddddd;padding:1px;background-color:#dddddd;margin:0px;background-image:none;height:auto;width:auto;box-shadow:none;border-radius:0px\"  
			id=\"img_$iddiv1\"  
			onclick=\"$jsdb;document.getElementById('img_$iddiv1').style.opacity='1';document.getElementById('img_$iddiv1').style.borderStyle='inset';return false;\"
			>" . xmetadb_frm_get_lang_img_local($tab_language_id) . "</button>";
        }
    }
    return $html;
}

/**
 * xmetadb_get_lang_img
 * return the html of selected language
 *
 * @param string $lang
 * @return string
 */
function xmetadb_frm_get_lang_img_local($lang)
{
    if (function_exists("xmetadb_get_lang_img"))
        return xmetadb_get_lang_img($lang);
    return "$lang";
}

//---------------------print fields------------------------------------------
/**
 * xmetadbform_print_field_text
 * show text field
 *
 */
function xmetadbform_frm_field_text($name, $value, $rows, $cols, $tooltip)
{

    $html = "";
    $html .= "<textarea  title=\"$tooltip\" cols=\"" . $cols . "\"  rows=\"" . $rows . "\"  name=\"$name\"  >";
    $html .= htmlspecialchars($value);
    $html .= "</textarea>";
    return $html;
}

/**
 *
 * @param string $charsetFrom
 * @param string $charsetTo
 * @param string $str
 * @return string 
 */
function XMETADB_FixEncoding($str, $charsetTo)
{

    if (XMETADB_IsIso8859($str) && $charsetTo != "ISO-8859-1" && !XMETADB_IsUtf8($str))
    {
        return XMETADB_ConvertEncoding($str, "ISO-8859-1", $charsetTo);
    }
    elseif (XMETADB_IsUtf8($str) && $charsetTo != "UTF-8" && !XMETADB_IsIso8859($str))
    {
        return XMETADB_ConvertEncoding($str, "UTF-8", $charsetTo);
    }
    return $str;
}

/**
 *
 * @param type $str
 * @return type 
 */
function XMETADB_IsUtf8($str)
{
    $c = 0;
    $b = 0;
    $bits = 0;
    $str = "$str";
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++)
    {
        $c = ord($str[$i]);
        if ($c > 128)
        {
            if (($c >= 254))
                return false;
            elseif ($c >= 252)
                $bits = 6;
            elseif ($c >= 248)
                $bits = 5;
            elseif ($c >= 240)
                $bits = 4;
            elseif ($c >= 224)
                $bits = 3;
            elseif ($c >= 192)
                $bits = 2;
            else
                return false;
            if (($i + $bits) > $len)
                return false;
            while ($bits > 1)
            {
                $i++;
                $b = ord($str[$i]);
                if ($b < 128 || $b > 191)
                    return false;
                $bits--;
            }
        }
    }
    return true;
}

/**
 *
 * @param type $str
 * @return type 
 */
function XMETADB_IsIso8859($str)
{
    //if (preg_match("/^[\\x00-\\xFF]*$/u",$str) === 1)
    //	return true;
    if ($str)
    {
        $chars = array(
            "&agrave;",
            "&egrave;",
            "&igrave;",
            "&ograve;",
            "&ugrave;",
            "&aacute;",
            "&eacute;",
            "&iacute;",
            "&oacute;",
            "&uacute;",
            "&Agrave;",
            "&Egrave;",
            "&Igrave;",
            "&Ograve;",
            "&Ugrave;",
            "&Aacute;",
            "&Eacute;",
            "&Iacute;",
            "&Oacute;",
            "&Uacute;",
            "&deg;"
        );
        foreach ($chars as $char)
        {
            if (strpos($str, html_entity_decode($char, ENT_QUOTES, "ISO-8859-1")))
            {
                return true;
            }
        }
    }
    return false;
}

/**
 *
 * @param string $charsetFrom
 * @param string $charsetTo
 * @param string $str
 * @return string 
 */
function XMETADB_ConvertEncoding($str, $charsetFrom, $charsetTo)
{
    if ($charsetFrom == $charsetTo || $charsetTo == "" || $charsetFrom == "")
        return $str;

    if (function_exists("mb_convert_encoding"))
    {
        //dprint_r("$charsetTo,$charsetFrom");
        $str = mb_convert_encoding($str, $charsetTo, $charsetFrom);
        return $str;
    }
    if (function_exists("iconv"))
    {
        $ret = @iconv($charsetFrom, $charsetTo, $str);
        if ($ret != "")
        {
            return $ret;
        }
    }

    $ret = htmlentities($str, ENT_QUOTES, $charsetFrom);
    $ret = html_entity_decode($ret, ENT_QUOTES, $charsetTo);
    if ($ret == "")
    {
        trigger_error("error convert string in xmetadb", E_USER_WARNING);
        return $str;
    }
    return $ret;
}

/**
 *
 * @param type $str
 * @return type 
 */
function XMETADB_i18n($string, $uppercasemode = "Aa")
{
    if (function_exists("FN_Translate"))
    {
        return FN_Translate($string, $uppercasemode);
    }
    return $string;
}
