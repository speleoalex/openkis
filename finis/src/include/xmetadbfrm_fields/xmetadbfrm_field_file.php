<?php
//---------file--------------------------------------->
class xmetadbfrm_field_file
{

    function __construct()
    {
        
    }

    function show($params)
    {
        $html = "";
        $toltips = "";
        $size = isset($params['frm_size']) ? $params['frm_size'] : 20;
        $oldvalues = $params['oldvalues'];
        $tablepath = $params['fieldform']->xmltable->FindFolderTable($oldvalues);
        $oldval = $params['value'];
        $primarykey = $params['oldvalues_primarikey'];
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        //die($attributes);
        $required = "";
        if ($oldval == "")
        {
            $required = (isset($params['frm_required']) && $params['frm_required'] == 1 ) ? "required=\"required\"" : "";
        }
        $html .= "<input $required  $attributes $toltips size=\"$size\" name=\"" . $params['name'] . "\" type=\"file\" />\n";
        $html .= "<br />";
        if ($oldval != "" && isset($oldvalues[$primarykey]))
        {
            $url = $params['fieldform']->xmltable->getFilePath($oldvalues, $params['name']);
//            $html .= "<br /><a href=\"{$params['fieldform']->siteurl}" . $params['fieldform']->path . "/" . $params['fieldform']->databasename . "/" . $tablepath . "/" . $oldvalues[$primarykey] . "/" . $params['name'] . "/$oldval\" >$oldval</a>";
            $html .= "<br /><a href=\"$url\" >$oldval</a>";
            $html .= "<input $toltips type=\"checkbox\" value=\"null\" name=\"__isnull__" . $params['name'] . "\" />" . $params['messages']["_XMLDBDELETE"];
        }
        return $html;
    }

    function view($params)
    {
        $databasename = $params['fieldform']->databasename;
        $tablename = $params['fieldform']->tablename;
        $path = $params['fieldform']->path;
        $value = $params['value'];
        $values = $params['values'];
        $tablepath = $params['fieldform']->xmltable->FindFolderTable($values);
        $table = XMETATable::xmetadbTable($databasename, $tablename);
        $htmlout = "";
        $fileimage = isset($values[$table->primarykey]) ? "$path/$databasename/$tablepath/" . $values[$table->primarykey] . "/" . $params['name'] . "/" . $values[$params['name']] : "";

        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";

        $htmlout .= "\n<a $attributes title=\"Download $value\" href=\"{$params['fieldform']->siteurl}$fileimage\"  >$value</a>";
        $downloadfile = FN_GetParam("xmetadb_ddfile_{$params['name']}", $_GET);
        $fsize = 0;
        if (file_exists($fileimage))
            $fsize = filesize($fileimage);
        $suff = "bytes";
        if ($fsize > 1024)
        {
            $fsize = round($fsize / 1024, 2);
            $suff = "Kb";
        }
        if ($fsize > 1024)
        {
            $fsize = round($fsize / 1024, 2);
            $suff = "Mb";
        }
        $htmlout .= "&nbsp;($fsize $suff)";
        return $htmlout;
    }

}

//---------file----------------------------------------<
